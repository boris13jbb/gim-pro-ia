#Requires -Version 5.1
<#
.SYNOPSIS
    Fase 18 — Arranca Cloudflare Tunnel hacia NestJS :3000 (opcional).

.DESCRIPTION
    Requiere cloudflared instalado y un token de túnel YA creado por el usuario
    (Cloudflare Zero Trust / dashboard). Este script NO crea cuentas, NO registra
    dominios y NO modifica DNS.

    Variable de entorno (no se escribe en el repo):
      CLOUDFLARE_TUNNEL_TOKEN=<token del túnel>

    Alternativa: pasar -Token (no se guarda en disco).

.PARAMETER Token
    Token del túnel. Si se omite, se lee $env:CLOUDFLARE_TUNNEL_TOKEN.

.PARAMETER LocalUrl
    Destino local del túnel. Default: http://127.0.0.1:3000

.EXAMPLE
    $env:CLOUDFLARE_TUNNEL_TOKEN = '...'
    .\scripts\start-cloudflare-tunnel.ps1

.NOTES
    Estado esperado sin token: PENDIENTE DE CONFIGURACIÓN EXTERNA.
#>
[CmdletBinding()]
param(
    [string] $Token = '',
    [string] $LocalUrl = 'http://127.0.0.1:3000'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Write-Err([string] $Message) {
    Write-Host "ERR $Message" -ForegroundColor Red
}

Write-Host ''
Write-Host 'Iron Gym — Cloudflare Tunnel (Fase 18, opcional)' -ForegroundColor White
Write-Host '=================================================' -ForegroundColor White

$cloudflared = Get-Command cloudflared -ErrorAction SilentlyContinue
if (-not $cloudflared) {
    Write-Err @"
CLOUDFLARE TUNNEL: BLOQUEADO POR SOFTWARE/CONFIGURACIÓN AUSENTE

cloudflared no está en PATH.
Instálalo manualmente desde:
  https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/

Luego crea un túnel en el dashboard de Cloudflare y exporta el token:
  `$env:CLOUDFLARE_TUNNEL_TOKEN = '<token>'
  .\scripts\start-cloudflare-tunnel.ps1
"@
    exit 1
}

Write-Host "cloudflared: $($cloudflared.Source)"

if (-not $Token) {
    $Token = $env:CLOUDFLARE_TUNNEL_TOKEN
}

if (-not $Token) {
    Write-Err @"
CLOUDFLARE TUNNEL: PREPARADO PERO PENDIENTE DE CONFIGURACIÓN EXTERNA

Software: OK ($($cloudflared.Source))
Falta: token de túnel (cuenta Cloudflare + hostname/DNS).

Pasos externos (manuales, fuera de este repo):
  1) Cuenta Cloudflare (Zero Trust / Tunnels).
  2) Crear túnel apuntando a $LocalUrl (NestJS).
  3) Publicar hostname HTTPS (DuckDNS u otro dominio).
  4) En esta sesión (NO subir al git):
       `$env:CLOUDFLARE_TUNNEL_TOKEN = '<token>'
       .\scripts\start-cloudflare-tunnel.ps1
  5) CORS / Flutter:
       CORS_ORIGINS=https://tu-hostname
       flutter run --dart-define=API_BASE_URL=https://tu-hostname/api

La Fase 18 puede operar solo con LAN sin Tunnel.
"@
    exit 2
}

Write-Host "Destino local: $LocalUrl"
Write-Host 'Iniciando: cloudflared tunnel run --token <redacted>'
Write-Host 'Ctrl+C para detener.'
Write-Host ''

# El token no se imprime ni se escribe a disco.
& cloudflared tunnel run --token $Token
