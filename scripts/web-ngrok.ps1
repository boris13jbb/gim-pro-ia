#Requires -Version 5.1
<#
.SYNOPSIS
    [LEGACY / OPCIONAL] Ejecuta o publica Flutter Web apuntando a API ngrok.

.DESCRIPTION
    Fase 18: flujo principal = LAN (`scripts/run-lan-dev.ps1 -StartWeb`, puerto 8888).
    Este script se mantiene como fallback ngrok.

    Modos:
      Run   — Desarrollo local en Chrome contra API ngrok.
      Share — Compila web, sirve build/web y muestra URLs para compartir.

.PARAMETER Mode
    Run (default) o Share.

.PARAMETER ApiUrl
    URL HTTPS de ngrok para la API (sin /api). Si se omite, se detecta del túnel api/3000.

.PARAMETER WebPort
    Puerto local para Flutter Web. Default: 8080.

.PARAMETER SkipHealthCheck
    Omite verificación de backend y health.

.EXAMPLE
    .\scripts\web-ngrok.ps1

.EXAMPLE
    .\scripts\web-ngrok.ps1 -Mode Share

.EXAMPLE
    .\scripts\web-ngrok.ps1 -ApiUrl "https://xxxx.ngrok-free.app"
#>
[CmdletBinding()]
param(
    [ValidateSet('Run', 'Share')]
    [string] $Mode = 'Run',
    [string] $ApiUrl,
    [int] $WebPort = 8888,
    [switch] $SkipHealthCheck
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$ProjectRoot = Split-Path -Parent $PSScriptRoot
$FlutterDir = Join-Path $ProjectRoot 'frontend-flutter'
$WebBuildDir = Join-Path $FlutterDir 'build\web'
$WebDistDir = Join-Path $ProjectRoot 'web-dist'
$NgrokApi = 'http://127.0.0.1:4040/api/tunnels'
$BackendPort = 3000
$EnvFile = Join-Path $ProjectRoot 'backend-nest\.env'

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

function Get-NgrokTunnels {
    try {
        $response = Invoke-RestMethod -Uri $NgrokApi -Method Get -TimeoutSec 5
        return @($response.tunnels)
    }
    catch {
        throw @"
No se pudo leer ngrok en $NgrokApi.
Inicia los túneles:
  ngrok start --config scripts/ngrok-gym.yml api web
o solo API:
  ngrok http 3000
"@
    }
}

function Get-NgrokApiUrl([string] $ManualUrl) {
    if ($ManualUrl) {
        return $ManualUrl.TrimEnd('/')
    }

    $tunnels = Get-NgrokTunnels
    $apiTunnel = $tunnels |
        Where-Object {
            $_.public_url -like 'https://*' -and
            ($_.config.addr -match ":$BackendPort$" -or $_.config.addr -eq "http://localhost:$BackendPort")
        } |
        Select-Object -First 1

    if (-not $apiTunnel) {
        $apiTunnel = $tunnels | Where-Object { $_.public_url -like 'https://*' } | Select-Object -First 1
    }

    if (-not $apiTunnel) {
        throw 'No hay túnel HTTPS en ngrok. Ejecuta: ngrok http 3000'
    }

    return $apiTunnel.public_url.TrimEnd('/')
}

function Get-NgrokWebUrl([int] $Port) {
    $tunnels = Get-NgrokTunnels
    $webTunnel = $tunnels |
        Where-Object {
            $_.public_url -like 'https://*' -and
            ($_.config.addr -match ":$Port$" -or $_.config.addr -eq "http://localhost:$Port")
        } |
        Select-Object -First 1

    if ($webTunnel) {
        return $webTunnel.public_url.TrimEnd('/')
    }

    return $null
}

function Test-BackendPort([int] $Port) {
    $listener = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
        Select-Object -First 1
    if (-not $listener) {
        throw "El backend no escucha en puerto $Port. Ejecuta: cd backend-nest; npm run start:dev"
    }
}

function Test-ApiHealth([string] $ApiBaseUrl) {
    $healthUrl = "$ApiBaseUrl/health"
    try {
        $response = Invoke-RestMethod -Uri $healthUrl -Method Get -TimeoutSec 15 -Headers @{
            'ngrok-skip-browser-warning' = 'true'
        }
        Write-Ok "Health: $healthUrl (db: $($response.data.details.database.status))"
    }
    catch {
        Write-Warn "No se pudo validar health en $healthUrl"
    }
}

function Update-CorsOrigins([string[]] $OriginsToAdd) {
    if (-not (Test-Path $EnvFile)) {
        Write-Warn "No se encontró $EnvFile para actualizar CORS."
        return
    }

    $content = Get-Content $EnvFile -Raw
    if ($content -notmatch 'CORS_ORIGINS="([^"]*)"') {
        Write-Warn 'CORS_ORIGINS no encontrado en .env'
        return
    }

    $current = $Matches[1] -split ',' | ForEach-Object { $_.Trim() } | Where-Object { $_ }
    $merged = [System.Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
    foreach ($item in $current) { [void]$merged.Add($item) }
    foreach ($item in $OriginsToAdd) { [void]$merged.Add($item) }

    $newValue = ($merged | Sort-Object) -join ','
    $updated = $content -replace 'CORS_ORIGINS="[^"]*"', "CORS_ORIGINS=`"$newValue`""

    if ($updated -ne $content) {
        Set-Content -Path $EnvFile -Value $updated -NoNewline
        Write-Ok "CORS_ORIGINS actualizado en backend-nest/.env"
        Write-Warn 'Reinicia el backend (npm run start:dev) para aplicar CORS.'
    }
    else {
        Write-Ok 'CORS_ORIGINS ya contenía los orígenes necesarios.'
    }
}

function Invoke-FlutterWebBuild([string] $PublicApiUrl) {
    $dartDefine = "API_BASE_URL=$PublicApiUrl/api"
    Write-Step "Compilando Flutter Web con: $dartDefine"

    Push-Location $FlutterDir
    try {
        flutter pub get
        if ($LASTEXITCODE -ne 0) { throw 'flutter pub get falló.' }

        & flutter build web --release "--dart-define=$dartDefine"
        if ($LASTEXITCODE -ne 0) { throw 'flutter build web falló.' }
    }
    finally {
        Pop-Location
    }
}

function Copy-WebDist {
    if (-not (Test-Path $WebBuildDir)) {
        throw "No existe build web en: $WebBuildDir"
    }

    if (Test-Path $WebDistDir) {
        Remove-Item $WebDistDir -Recurse -Force
    }

    Copy-Item $WebBuildDir $WebDistDir -Recurse
    Write-Ok "Web copiada a: $WebDistDir"
}

function Start-WebServer([int] $Port) {
    $existing = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
    if ($existing) {
        Write-Warn "Puerto $Port ya está en uso. Se reutiliza el servidor existente."
        return
    }

    Write-Step "Sirviendo web en http://localhost:$Port"
    $serveScript = @"
`$root = '$WebDistDir'
`$listener = New-Object System.Net.HttpListener
`$listener.Prefixes.Add('http://localhost:$Port/')
`$listener.Start()
Write-Host 'Sirviendo' `$root 'en http://localhost:$Port/' -ForegroundColor Green
while (`$listener.IsListening) {
  `$context = `$listener.GetContext()
  `$path = `$context.Request.Url.LocalPath
  if (`$path -eq '/') { `$path = '/index.html' }
  `$file = Join-Path `$root (`$path.TrimStart('/'))
  if (Test-Path `$file -PathType Leaf) {
    `$bytes = [System.IO.File]::ReadAllBytes(`$file)
    `$ext = [System.IO.Path]::GetExtension(`$file).ToLower()
    `$mime = switch (`$ext) {
      '.html' { 'text/html; charset=utf-8' }
      '.js'   { 'application/javascript; charset=utf-8' }
      '.json' { 'application/json; charset=utf-8' }
      '.css'  { 'text/css; charset=utf-8' }
      '.png'  { 'image/png' }
      '.jpg'  { 'image/jpeg' }
      '.svg'  { 'image/svg+xml' }
      '.woff2'{ 'font/woff2' }
      default { 'application/octet-stream' }
    }
    `$context.Response.ContentType = `$mime
    `$context.Response.OutputStream.Write(`$bytes, 0, `$bytes.Length)
  } else {
    `$fallback = Join-Path `$root 'index.html'
    `$bytes = [System.IO.File]::ReadAllBytes(`$fallback)
    `$context.Response.ContentType = 'text/html; charset=utf-8'
    `$context.Response.OutputStream.Write(`$bytes, 0, `$bytes.Length)
  }
  `$context.Response.Close()
}
"@

    Start-Process powershell -ArgumentList @(
        '-NoExit', '-ExecutionPolicy', 'Bypass', '-Command', $serveScript
    ) | Out-Null

    Start-Sleep -Seconds 2
    Write-Ok "Servidor web local: http://localhost:$Port"
}

try {
    Write-Host ''
    Write-Host 'Iron Gym — Flutter Web + ngrok' -ForegroundColor White
    Write-Host '===============================' -ForegroundColor White

    if (-not (Test-Path (Join-Path $FlutterDir 'pubspec.yaml'))) {
        throw "No se encontró Flutter en: $FlutterDir"
    }

    Write-Step 'Obteniendo URL de API (ngrok)'
    $publicApiUrl = Get-NgrokApiUrl -ManualUrl $ApiUrl
    $apiBase = "$publicApiUrl/api"
    Write-Ok "API ngrok: $publicApiUrl"

    if (-not $SkipHealthCheck) {
        Write-Step 'Verificando backend'
        Test-BackendPort -Port $BackendPort
        Test-ApiHealth -ApiBaseUrl $apiBase
    }

    $localWebOrigin = "http://localhost:$WebPort"
    $originsToAdd = @($localWebOrigin, 'http://127.0.0.1:8080')

    if ($Mode -eq 'Run') {
        Update-CorsOrigins -OriginsToAdd $originsToAdd

        Write-Step 'Iniciando Flutter Web en Chrome'
        Write-Host "Abrirá: $localWebOrigin"
        Write-Host "API:    $apiBase"
        Write-Host ''
        Write-Host 'Presiona Ctrl+C para detener.' -ForegroundColor Yellow

        Push-Location $FlutterDir
        try {
            & flutter run -d chrome `
                --web-port=$WebPort `
                "--dart-define=API_BASE_URL=$apiBase"
            if ($LASTEXITCODE -ne 0) { throw 'flutter run falló.' }
        }
        finally {
            Pop-Location
        }
    }
    else {
        Invoke-FlutterWebBuild -PublicApiUrl $publicApiUrl
        Copy-WebDist
        Start-WebServer -Port $WebPort

        $webPublicUrl = Get-NgrokWebUrl -Port $WebPort
        if ($webPublicUrl) {
            Update-CorsOrigins -OriginsToAdd @($webPublicUrl, $localWebOrigin)
        }
        else {
            Update-CorsOrigins -OriginsToAdd $originsToAdd
            Write-Warn @"
No hay túnel ngrok para el puerto $WebPort.
Para compartir por internet, en otra terminal ejecuta:

  .\scripts\start-ngrok-gym.ps1

Luego reinicia backend y abre la URL 'web' que muestre ngrok.
"@
        }

        Write-Host ''
        Write-Host 'Web lista para probar' -ForegroundColor Green
        Write-Host "  Local:  http://localhost:$WebPort"
        if ($webPublicUrl) {
            Write-Host "  Pública: $webPublicUrl"
        }
        Write-Host "  API:    $apiBase"
        Write-Host ''
        Write-Host 'Guía rápida:'
        Write-Host '  1) Backend activo (npm run start:dev)'
        Write-Host '  2) ngrok con túneles api + web'
        Write-Host '  3) Reinicia backend si se actualizó CORS'
        Write-Host '  4) Abre la URL pública web y prueba login'
        Write-Host ''
    }
}
catch {
    Write-Err $_.Exception.Message
    exit 1
}
