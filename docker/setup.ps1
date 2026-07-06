# Creates .env.docker from the template and helps set LAN_HOST for office network sharing.
# Run from the project root:  .\docker\setup.ps1

$ErrorActionPreference = "Stop"
$projectRoot = Split-Path -Parent $PSScriptRoot
$envDocker = Join-Path $projectRoot ".env.docker"
$envExample = Join-Path $projectRoot "docker\.env.example"

if (-not (Test-Path $envExample)) {
    Write-Error "Missing template: docker\.env.example"
}

if (-not (Test-Path $envDocker)) {
    Copy-Item $envExample $envDocker
    Write-Host "Created .env.docker from docker\.env.example"
} else {
    Write-Host ".env.docker already exists — leaving it unchanged."
}

function Get-LanIPv4 {
    $addresses = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
        Where-Object {
            $_.IPAddress -notlike "127.*" -and
            $_.IPAddress -notlike "169.254.*" -and
            $_.PrefixOrigin -ne "WellKnown"
        } |
        Sort-Object InterfaceMetric

    if ($addresses) {
        return $addresses[0].IPAddress
    }

    return $null
}

$detectedIp = Get-LanIPv4
$currentLanHost = (Select-String -Path $envDocker -Pattern "^LAN_HOST=(.+)$").Matches.Groups[1].Value

Write-Host ""
Write-Host "Current LAN_HOST in .env.docker: $currentLanHost"

if ($detectedIp) {
    Write-Host "Detected LAN IPv4 on this PC: $detectedIp"
    $useDetected = Read-Host "Use $detectedIp as LAN_HOST? (Y/n)"
    if ($useDetected -eq "" -or $useDetected -match "^[Yy]") {
        (Get-Content $envDocker) -replace "^LAN_HOST=.*", "LAN_HOST=$detectedIp" | Set-Content $envDocker
        Write-Host "Updated LAN_HOST=$detectedIp"
    }
} else {
    Write-Host "Could not auto-detect LAN IP. Edit LAN_HOST manually in .env.docker."
}

$appKeyLine = Select-String -Path $envDocker -Pattern "^APP_KEY=(.*)$"
if ($null -eq $appKeyLine -or [string]::IsNullOrWhiteSpace($appKeyLine.Matches.Groups[1].Value)) {
    Write-Host ""
    Write-Host "Generating APP_KEY..."
    $bytes = New-Object byte[] 32
    [System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    $generatedKey = "base64:$([Convert]::ToBase64String($bytes))"
    (Get-Content $envDocker) -replace "^APP_KEY=.*", "APP_KEY=$generatedKey" | Set-Content $envDocker
    Write-Host "APP_KEY written to .env.docker"
}

Write-Host ""
Write-Host "Next steps:"
Write-Host "  1. docker compose --env-file .env.docker up -d --build"
Write-Host "  2. docker compose --env-file .env.docker exec backend php artisan db:seed"
Write-Host "  3. Share http://<LAN_HOST>:3000 with colleagues (login: demo@event-saas.test / password)"
