#Requires -Version 5.1
<#
.SYNOPSIS
    [LEGACY / OPCIONAL] Genera APKs Android apuntando a la URL pública de ngrok.

.DESCRIPTION
    Fase 18: preferir `scripts/build-apk-release.ps1 -ApiBaseUrl http://IP-LAN:3000/api`
    (sin ngrok). Este script se conserva como fallback cuando aún uses ngrok.

    1. Lee la URL HTTPS activa desde la API local de ngrok (http://127.0.0.1:4040).
    2. Verifica que el backend NestJS escuche en el puerto 3000.
    3. Compila APKs release con --split-per-abi.
    4. Copia los artefactos a apk-dist/ con nombres claros.

.PARAMETER ApiUrl
    URL base de la API (sin /api al final). Si se omite, se detecta desde ngrok.

.PARAMETER SkipHealthCheck
    Omite la verificación del puerto 3000.

.PARAMETER UniversalApk
    Además de las APK por arquitectura, genera una APK universal (más pesada).

.EXAMPLE
    .\scripts\build-apk-ngrok.ps1

.EXAMPLE
    .\scripts\build-apk-ngrok.ps1 -ApiUrl "https://xxxx.ngrok-free.app"

.EXAMPLE
    .\scripts\build-apk-ngrok.ps1 -UniversalApk
#>
[CmdletBinding()]
param(
    [string] $ApiUrl,
    [switch] $SkipHealthCheck,
    [switch] $UniversalApk
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$ProjectRoot = Split-Path -Parent $PSScriptRoot
$FlutterDir = Join-Path $ProjectRoot 'frontend-flutter'
$OutputDir = Join-Path $ProjectRoot 'apk-dist'
$NgrokApi = 'http://127.0.0.1:4040/api/tunnels'
$BackendPort = 3000

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

function Get-NgrokPublicUrl {
    try {
        $response = Invoke-RestMethod -Uri $NgrokApi -Method Get -TimeoutSec 5
    }
    catch {
        throw @"
No se pudo leer ngrok en $NgrokApi.
Asegúrate de tener ngrok corriendo:
  ngrok http 3000
"@
    }

    $httpsTunnel = $response.tunnels |
        Where-Object { $_.public_url -like 'https://*' } |
        Select-Object -First 1

    if (-not $httpsTunnel) {
        throw 'ngrok está activo pero no hay túnel HTTPS. Ejecuta: ngrok http 3000'
    }

    return $httpsTunnel.public_url.TrimEnd('/')
}

function Test-BackendPort([int] $Port) {
    $listener = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
        Select-Object -First 1

    if (-not $listener) {
        throw @"
El backend no está escuchando en el puerto $Port.
Inicia el backend en otra terminal:
  cd backend-nest
  npm run start:dev
"@
    }
}

function Test-ApiHealth([string] $BaseApiUrl) {
    $healthUrl = "$BaseApiUrl/health"
    try {
        $response = Invoke-RestMethod -Uri $healthUrl -Method Get -TimeoutSec 15 -Headers @{
            'ngrok-skip-browser-warning' = 'true'
        }
        $dbStatus = $response.data.details.database.status
        if ($dbStatus -ne 'up') {
            Write-Warn "Health respondió pero la BD no está 'up' (status: $dbStatus)."
        }
        else {
            Write-Ok "Health check: $healthUrl"
        }
    }
    catch {
        Write-Warn "No se pudo validar $healthUrl. Se continúa con el build."
        Write-Warn $_.Exception.Message
    }
}

function Ensure-FlutterProject {
    if (-not (Test-Path (Join-Path $FlutterDir 'pubspec.yaml'))) {
        throw "No se encontró el proyecto Flutter en: $FlutterDir"
    }
}

function Invoke-FlutterBuild([string] $BaseApiUrl, [switch] $WithUniversal) {
    $dartDefine = "API_BASE_URL=$BaseApiUrl/api"
    $buildArgs = @(
        'build', 'apk', '--release',
        '--split-per-abi',
        "--dart-define=$dartDefine"
    )

    Write-Step "Compilando APKs con: $dartDefine"
    Push-Location $FlutterDir
    try {
        flutter pub get
        if ($LASTEXITCODE -ne 0) { throw 'flutter pub get falló.' }

        & flutter @buildArgs
        if ($LASTEXITCODE -ne 0) { throw 'flutter build apk falló.' }

        if ($WithUniversal) {
            Write-Step 'Compilando APK universal adicional'
            & flutter build apk --release "--dart-define=$dartDefine"
            if ($LASTEXITCODE -ne 0) { throw 'flutter build apk (universal) falló.' }
        }
    }
    finally {
        Pop-Location
    }
}

function Copy-BuildArtifacts([string] $BaseApiUrl, [switch] $IncludeUniversal) {
    $apkSourceDir = Join-Path $FlutterDir 'build\app\outputs\flutter-apk'
    if (-not (Test-Path $apkSourceDir)) {
        throw "No se encontró la carpeta de salida: $apkSourceDir"
    }

    New-Item -ItemType Directory -Force -Path $OutputDir | Out-Null

    $timestamp = Get-Date -Format 'yyyyMMdd-HHmm'

    $map = [ordered]@{
        'app-arm64-v8a-release.apk'   = 'IronGym-ngrok-arm64.apk'
        'app-armeabi-v7a-release.apk' = 'IronGym-ngrok-arm32.apk'
        'app-x86_64-release.apk'      = 'IronGym-ngrok-x86_64.apk'
    }

    if ($IncludeUniversal) {
        $map['app-release.apk'] = 'IronGym-ngrok-universal.apk'
    }

    $copied = @()

    foreach ($entry in $map.GetEnumerator()) {
        $source = Join-Path $apkSourceDir $entry.Key
        if (-not (Test-Path $source)) {
            continue
        }

        $stableName = $entry.Value
        $destStable = Join-Path $OutputDir $stableName
        $destStamped = Join-Path $OutputDir ($stableName -replace '\.apk$', "-$timestamp.apk")

        Copy-Item -Path $source -Destination $destStamped -Force
        Copy-Item -Path $source -Destination $destStable -Force

        $sizeMb = [math]::Round((Get-Item $destStable).Length / 1MB, 1)
        $copied += [pscustomobject]@{
            File   = $stableName
            SizeMB = $sizeMb
            Path   = $destStable
        }
    }

    if ($copied.Count -eq 0) {
        throw 'No se encontraron APK generadas para copiar.'
    }

    return $copied
}

try {
    Write-Host ''
    Write-Host 'Iron Gym — Build APK con ngrok' -ForegroundColor White
    Write-Host '================================' -ForegroundColor White

    Ensure-FlutterProject

    if ($ApiUrl) {
        $publicUrl = $ApiUrl.TrimEnd('/')
        Write-Ok "URL manual: $publicUrl"
    }
    else {
        Write-Step 'Obteniendo URL pública de ngrok'
        $publicUrl = Get-NgrokPublicUrl
        Write-Ok "ngrok: $publicUrl"
    }

    $apiBase = if ($publicUrl.EndsWith('/api')) { $publicUrl } else { "$publicUrl/api" }

    if (-not $SkipHealthCheck) {
        Write-Step "Verificando backend en puerto $BackendPort"
        Test-BackendPort -Port $BackendPort
        Write-Ok "Backend escuchando en puerto $BackendPort"

        Write-Step 'Verificando health de la API'
        Test-ApiHealth -BaseApiUrl $apiBase
    }

    Invoke-FlutterBuild -BaseApiUrl $publicUrl -WithUniversal:$UniversalApk

    Write-Step 'Copiando APKs a apk-dist/'
    $artifacts = Copy-BuildArtifacts -BaseApiUrl $publicUrl -IncludeUniversal:$UniversalApk

    Write-Host ''
    Write-Host 'Build completado' -ForegroundColor Green
    Write-Host "API configurada: $apiBase"
    Write-Host "Carpeta destino: $OutputDir"
    Write-Host ''
    $artifacts | Format-Table File, SizeMB, Path -AutoSize

    Write-Host 'Guía rápida:' -ForegroundColor White
    Write-Host '  - Celular moderno  -> IronGym-ngrok-arm64.apk'
    Write-Host '  - Celular antiguo  -> IronGym-ngrok-arm32.apk'
    Write-Host '  - Emulador PC      -> IronGym-ngrok-x86_64.apk'
    Write-Host ''
    Write-Host 'Requisitos para que la app funcione:'
    Write-Host '  1) Backend activo (npm run start:dev)'
    Write-Host '  2) ngrok activo (ngrok http 3000)'
    Write-Host '  3) Si reinicias ngrok, vuelve a ejecutar este script.'
    Write-Host ''
}
catch {
    Write-Err $_.Exception.Message
    exit 1
}
