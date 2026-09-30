#Requires -Version 5.1
<#
.SYNOPSIS
    Ejecuta la app Flutter en un teléfono Android físico (misma Wi‑Fi que el PC).

.DESCRIPTION
    Usa dart_defines.physical_device.json (local, no versionado) o -PcIp.
    Crea el JSON con: { "API_HOST": "192.168.x.x" }

.EXAMPLE
    .\scripts\run-android-physical.ps1
.EXAMPLE
    .\scripts\run-android-physical.ps1 -PcIp 192.168.1.50
#>
[CmdletBinding()]
param(
    [string] $PcIp = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$FlutterDir = Join-Path $PSScriptRoot '..\frontend-flutter'
$DefinesFile = Join-Path $FlutterDir 'dart_defines.physical_device.json'

if ($PcIp) {
    $defines = @{ API_HOST = $PcIp } | ConvertTo-Json
    Set-Content -Path $DefinesFile -Value $defines -Encoding utf8
    Write-Host "API_HOST temporal escrito en dart_defines.physical_device.json: $PcIp"
}
elseif (-not (Test-Path $DefinesFile)) {
    Write-Host @"
ERR Falta la IP del PC.

Opciones:
  1) Crear frontend-flutter/dart_defines.physical_device.json:
     { "API_HOST": "192.168.x.x" }
  2) Ejecutar: .\scripts\run-android-physical.ps1 -PcIp 192.168.x.x

Obtén la IP con: ipconfig (IPv4 de Wi‑Fi).
"@ -ForegroundColor Red
    exit 1
}

Write-Host 'Asegurate de: backend activo (npm run start:dev) y telefono en la misma WiFi.'
Write-Host ''

Set-Location $FlutterDir
flutter run --dart-define-from-file=dart_defines.physical_device.json @args
