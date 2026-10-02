#Requires -Version 5.1
<#
.SYNOPSIS
    Fase 18 — Genera APKs Android release con API_BASE_URL fija (sin ngrok).

.DESCRIPTION
    Compila APKs split-per-abi apuntando a una URL de API estable (LAN o Tunnel).
    NO usa ngrok.
    NO hardcodea IP.
    NO contiene secretos.

.PARAMETER ApiBaseUrl
    URL completa de la API incluyendo /api, o host base sin /api.
    Ejemplos:
      http://192.168.1.50:3000/api
      http://192.168.1.50:3000
      https://api.tudominio.com/api

.PARAMETER SkipHealthCheck
    Omite verificación de health.

.PARAMETER UniversalApk
    También genera APK universal.

.EXAMPLE
    .\scripts\build-apk-release.ps1 -ApiBaseUrl "http://192.168.1.50:3000/api"

.EXAMPLE
    .\scripts\build-apk-release.ps1 -ApiBaseUrl "http://192.168.1.50:3000" -UniversalApk
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string] $ApiBaseUrl,
    [switch] $SkipHealthCheck,
    [switch] $UniversalApk
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$ProjectRoot = Split-Path -Parent $PSScriptRoot
$FlutterDir = Join-Path $ProjectRoot 'frontend-flutter'
$OutputDir = Join-Path $ProjectRoot 'apk-dist'

function Write-Step([string] $Message) {
    Write-Host "`n==> $Message" -ForegroundColor Cyan
}

function Write-Ok([string] $Message) {
    Write-Host "OK  $Message" -ForegroundColor Green
}

function Write-Warn([string] $Message) {
    Write-Host "!!  $Message" -ForegroundColor Yellow
}

function Write-Err([string] $Message) {
    Write-Host "ERR $Message" -ForegroundColor Red
}

function Normalize-ApiBaseUrl([string] $Raw) {
    $value = $Raw.Trim().TrimEnd('/')
    if ($value -match '/api$') {
        return $value
    }
    return "$value/api"
}

function Test-ApiHealth([string] $BaseApiUrl) {
    $healthUrl = "$BaseApiUrl/health"
    try {
        $response = Invoke-RestMethod -Uri $healthUrl -Method Get -TimeoutSec 15
        Write-Ok "Health check: $healthUrl"
        if ($null -ne $response.data -and $null -ne $response.data.details.database) {
            $dbStatus = $response.data.details.database.status
            if ($dbStatus -ne 'up') {
                Write-Warn "BD status: $dbStatus"
            }
        }
    }
    catch {
        Write-Warn "No se pudo validar $healthUrl. Se continúa con el build."
        Write-Warn $_.Exception.Message
    }
}

try {
    Write-Host ''
    Write-Host 'Iron Gym — Build APK release (sin ngrok)' -ForegroundColor White
    Write-Host '=========================================' -ForegroundColor White

    if (-not (Test-Path (Join-Path $FlutterDir 'pubspec.yaml'))) {
        throw "No se encontró el proyecto Flutter en: $FlutterDir"
    }

    $apiBase = Normalize-ApiBaseUrl -Raw $ApiBaseUrl
    if ($apiBase -match 'ngrok') {
        Write-Warn 'La URL contiene "ngrok". Para builds ngrok usa scripts/build-apk-ngrok.ps1 (LEGACY).'
    }

    Write-Ok "API_BASE_URL=$apiBase"
    Write-Host "WebSocket host (derivado): $($apiBase -replace '/api$','')"

    if (-not $SkipHealthCheck) {
        Write-Step 'Verificando health de la API'
        Test-ApiHealth -BaseApiUrl $apiBase
    }

    $dartDefine = "API_BASE_URL=$apiBase"
    Write-Step "Compilando APKs con: --dart-define=$dartDefine"

    Push-Location $FlutterDir
    try {
        flutter pub get
        if ($LASTEXITCODE -ne 0) { throw 'flutter pub get falló.' }

        & flutter build apk --release --split-per-abi "--dart-define=$dartDefine"
        if ($LASTEXITCODE -ne 0) { throw 'flutter build apk falló.' }

        if ($UniversalApk) {
            Write-Step 'Compilando APK universal adicional'
            & flutter build apk --release "--dart-define=$dartDefine"
            if ($LASTEXITCODE -ne 0) { throw 'flutter build apk (universal) falló.' }
        }
    }
    finally {
        Pop-Location
    }

    Write-Step 'Copiando APKs a apk-dist/'
    $apkSourceDir = Join-Path $FlutterDir 'build\app\outputs\flutter-apk'
    if (-not (Test-Path $apkSourceDir)) {
        throw "No se encontró la carpeta de salida: $apkSourceDir"
    }

    New-Item -ItemType Directory -Force -Path $OutputDir | Out-Null
    $timestamp = Get-Date -Format 'yyyyMMdd-HHmm'

    $map = [ordered]@{
        'app-arm64-v8a-release.apk'   = 'IronGym-lan-arm64.apk'
        'app-armeabi-v7a-release.apk' = 'IronGym-lan-arm32.apk'
        'app-x86_64-release.apk'      = 'IronGym-lan-x86_64.apk'
    }
    if ($UniversalApk) {
        $map['app-release.apk'] = 'IronGym-lan-universal.apk'
    }

    $copied = @()
    foreach ($entry in $map.GetEnumerator()) {
        $source = Join-Path $apkSourceDir $entry.Key
        if (-not (Test-Path $source)) { continue }

        $stableName = $entry.Value
        $destStable = Join-Path $OutputDir $stableName
        $destStamped = Join-Path $OutputDir ($stableName -replace '\.apk$', "-$timestamp.apk")
        Copy-Item -Path $source -Destination $destStamped -Force
        Copy-Item -Path $source -Destination $destStable -Force
        $sizeMb = [math]::Round((Get-Item $destStable).Length / 1MB, 1)
        $copied += [pscustomobject]@{ File = $stableName; SizeMB = $sizeMb; Path = $destStable }
    }

    if ($copied.Count -eq 0) {
        throw 'No se encontraron APK generadas para copiar.'
    }

    Write-Host ''
    Write-Host 'Build completado' -ForegroundColor Green
    Write-Host "API configurada: $apiBase"
    Write-Host "Carpeta destino: $OutputDir"
    $copied | Format-Table File, SizeMB, Path -AutoSize
    Write-Host 'Requisitos: backend accesible en esa URL; teléfono en la misma red (si es LAN).'
    Write-Host ''
}
catch {
    Write-Err $_.Exception.Message
    exit 1
}
