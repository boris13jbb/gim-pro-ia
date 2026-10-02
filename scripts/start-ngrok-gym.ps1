#Requires -Version 5.1
<#
.SYNOPSIS
    [LEGACY / OPCIONAL] Inicia túneles ngrok para Iron Gym (API :3000 + Web :8888).

.DESCRIPTION
    Fase 18: el flujo principal de desarrollo es LAN (`scripts/run-lan-dev.ps1`).
    Este script se mantiene solo como fallback de emergencia / demos legacy.

    Combina tu ngrok.yml global (authtoken) con scripts/ngrok-gym.yml (túneles).
    Sin esto, ngrok falla con ERR_NGROK_4018 si solo usas el yml del proyecto.

    Preferido:
      .\scripts\run-lan-dev.ps1
      .\scripts\start-cloudflare-tunnel.ps1   # URL estable (si hay token)

.EXAMPLE
    .\scripts\start-ngrok-gym.ps1
#>
[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$ProjectRoot = Split-Path -Parent $PSScriptRoot
$ProjectConfig = Join-Path $PSScriptRoot 'ngrok-gym.yml'
$GlobalConfig = Join-Path $env:LOCALAPPDATA 'ngrok\ngrok.yml'

function Write-Err([string] $Message) {
    Write-Host "ERR $Message" -ForegroundColor Red
}

if (-not (Test-Path $GlobalConfig)) {
    Write-Err @"
No se encontró la configuración global de ngrok:
  $GlobalConfig

Configura tu cuenta (solo una vez):
  1) Crea cuenta: https://dashboard.ngrok.com/signup
  2) Copia tu authtoken: https://dashboard.ngrok.com/get-started/your-authtoken
  3) Ejecuta: ngrok config add-authtoken TU_TOKEN
"@
    exit 1
}

if (-not (Test-Path $ProjectConfig)) {
    Write-Err "No se encontró: $ProjectConfig"
    exit 1
}

Write-Host ''
Write-Host 'Iron Gym — ngrok (api + web)' -ForegroundColor White
Write-Host '============================' -ForegroundColor White
Write-Host "Global:  $GlobalConfig"
Write-Host "Túneles: $ProjectConfig"
Write-Host ''
Write-Host 'Túneles:'
Write-Host '  api -> localhost:3000 (NestJS)'
Write-Host '  web -> localhost:8888 (Flutter Web)'
Write-Host ''
Write-Host 'Panel: http://127.0.0.1:4040' -ForegroundColor Cyan
Write-Host 'Ctrl+C para detener.' -ForegroundColor Yellow
Write-Host ''

& ngrok start --config $GlobalConfig --config $ProjectConfig api web
