#Requires -Version 5.1
<#
.SYNOPSIS
    Fase 18 - Guia e inicio del flujo de desarrollo por LAN (sin ngrok).

.DESCRIPTION
    Detecta la IPv4 Wi-Fi del PC, verifica NestJS en :3000 e imprime comandos
    para Flutter Web (:8888) y Android fisico.

    NO inicia ngrok.
    NO modifica .env real.
    NO contiene secretos.
    NO hardcodea una IP fija en el repositorio.

.PARAMETER LanIp
    IPv4 LAN a usar. Si se omite, se detecta desde la interfaz Wi-Fi.

.PARAMETER StartBackend
    Inicia npm run start:dev en una ventana nueva (opcional).

.PARAMETER StartWeb
    Lanza Flutter Web en Chrome puerto 8888 contra la API LAN.

.EXAMPLE
    .\scripts\run-lan-dev.ps1

.EXAMPLE
    .\scripts\run-lan-dev.ps1 -LanIp 192.168.1.50 -StartWeb
#>
[CmdletBinding()]
param(
    [string] $LanIp = '',
    [switch] $StartBackend,
    [switch] $StartWeb
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$ProjectRoot = Split-Path -Parent $PSScriptRoot
$BackendDir = Join-Path $ProjectRoot 'backend-nest'
$FlutterDir = Join-Path $ProjectRoot 'frontend-flutter'
$BackendPort = 3000
$WebPort = 8888

function Write-Step([string] $Message) {
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Write-Ok([string] $Message) {
    Write-Host "OK  $Message" -ForegroundColor Green
}

function Write-Warn([string] $Message) {
    Write-Host "!!  $Message" -ForegroundColor Yellow
}

function Get-LanIpv4 {
    $candidates = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
        Where-Object {
            $_.IPAddress -notlike '127.*' -and
            $_.IPAddress -notlike '169.254.*' -and
            $_.PrefixOrigin -ne 'WellKnown'
        }

    if (-not $candidates) {
        return $null
    }

    $wifi = $candidates |
        Where-Object { $_.InterfaceAlias -match 'Wi-?Fi|WLAN' } |
        Select-Object -First 1
    if ($wifi) {
        return $wifi.IPAddress
    }

    $ethernet = $candidates |
        Where-Object { $_.InterfaceAlias -match 'Ethernet' } |
        Select-Object -First 1
    if ($ethernet) {
        return $ethernet.IPAddress
    }

    return ($candidates | Select-Object -First 1).IPAddress
}

function Test-BackendHealth([string] $Ip) {
    $urls = @(
        "http://127.0.0.1:$BackendPort/api/health",
        "http://${Ip}:$BackendPort/api/health"
    )
    $anyOk = $false
    foreach ($url in $urls) {
        try {
            $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 5
            if ($response.StatusCode -eq 200) {
                Write-Ok "Health 200 - $url"
                $anyOk = $true
            }
        }
        catch {
            Write-Warn "Sin respuesta: $url"
        }
    }
    return $anyOk
}

Write-Host ''
Write-Host 'Iron Gym - Desarrollo LAN (Fase 18)' -ForegroundColor White
Write-Host '====================================' -ForegroundColor White
Write-Host 'Flujo principal: LAN. ngrok = LEGACY/opcional.' -ForegroundColor DarkGray
Write-Host ''

if (-not $LanIp) {
    Write-Step 'Detectando IPv4 LAN'
    $LanIp = Get-LanIpv4
}

if (-not $LanIp) {
    Write-Host 'ERR No se pudo detectar la IP LAN.' -ForegroundColor Red
    Write-Host 'Indicala manualmente:' -ForegroundColor Red
    Write-Host '  .\scripts\run-lan-dev.ps1 -LanIp 192.168.x.x' -ForegroundColor Red
    Write-Host 'Obten la IP con: ipconfig (IPv4 de Wi-Fi).' -ForegroundColor Red
    exit 1
}

Write-Ok "IP LAN: $LanIp"
$ApiBase = "http://${LanIp}:$BackendPort/api"
$SocketBase = "http://${LanIp}:$BackendPort"
Write-Host "API REST:     $ApiBase"
Write-Host "WebSocket:    $SocketBase  (namespaces /events y /ai)"
Write-Host "Flutter Web:  http://${LanIp}:$WebPort  (o localhost:$WebPort en el PC)"

if ($StartBackend) {
    Write-Step 'Iniciando backend NestJS en ventana nueva'
    if (-not (Test-Path (Join-Path $BackendDir 'package.json'))) {
        throw "No se encontro backend en: $BackendDir"
    }
    Start-Process powershell -ArgumentList @(
        '-NoExit',
        '-Command',
        "Set-Location '$BackendDir'; npm run start:dev"
    )
    Write-Ok 'Ventana backend lanzada (npm run start:dev)'
    Start-Sleep -Seconds 3
}

Write-Step 'Verificando backend'
$healthy = Test-BackendHealth -Ip $LanIp
if (-not $healthy) {
    Write-Warn 'Backend no responde aun. Inicia en otra terminal:'
    Write-Host '  cd backend-nest'
    Write-Host '  npm run start:dev'
    Write-Host "Escucha en 0.0.0.0:$BackendPort (LAN habilitado)."
}

Write-Host ''
Write-Host 'Comandos recomendados (sin ngrok)' -ForegroundColor White
Write-Host '---------------------------------' -ForegroundColor White
Write-Host ''
Write-Host '1) Backend (si no esta activo):'
Write-Host '   cd backend-nest'
Write-Host '   npm run start:dev'
Write-Host ''
Write-Host '2) Flutter Web (PC, puerto 8888):'
Write-Host '   cd frontend-flutter'
Write-Host "   flutter run -d chrome --web-port=$WebPort --dart-define=API_BASE_URL=$ApiBase"
Write-Host ''
Write-Host '   O con este script:'
Write-Host "   .\scripts\run-lan-dev.ps1 -LanIp $LanIp -StartWeb"
Write-Host ''
Write-Host '3) Android fisico (misma Wi-Fi):'
Write-Host "   .\scripts\run-android-physical.ps1 -PcIp $LanIp"
Write-Host '   # equivalente:'
Write-Host '   cd frontend-flutter'
Write-Host "   flutter run --dart-define=API_BASE_URL=$ApiBase"
Write-Host ''
Write-Host '4) APK release sin ngrok:'
Write-Host "   .\scripts\build-apk-release.ps1 -ApiBaseUrl $ApiBase"
Write-Host ''
Write-Host 'CORS (development):' -ForegroundColor White
Write-Host '  localhost + IPv4 privadas (RFC1918) aceptadas sin listar cada IP.'
Write-Host '  Opcional en .env: CORS_ORIGINS con http://localhost:8888'
Write-Host ''
Write-Host 'Cloudflare Tunnel (opcional, URL estable):' -ForegroundColor White
Write-Host '  .\scripts\start-cloudflare-tunnel.ps1'
Write-Host '  Requiere CLOUDFLARE_TUNNEL_TOKEN (no se crea cuenta desde este script).'
Write-Host ''

if ($StartWeb) {
    Write-Step "Iniciando Flutter Web en puerto $WebPort"
    if (-not (Test-Path (Join-Path $FlutterDir 'pubspec.yaml'))) {
        throw "No se encontro Flutter en: $FlutterDir"
    }
    Set-Location $FlutterDir
    flutter run -d chrome --web-port=$WebPort "--dart-define=API_BASE_URL=$ApiBase"
}
