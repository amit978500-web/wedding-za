param(
    [Parameter(Mandatory=$true)]
    [string]$RepositoryUrl
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "Install Git for Windows first: https://git-scm.com/download/win" -ForegroundColor Red
    exit 1
}

$hasOrigin = git remote get-url origin 2>$null
if ($LASTEXITCODE -eq 0 -and $hasOrigin) {
    git remote set-url origin $RepositoryUrl
} else {
    git remote add origin $RepositoryUrl
}

git push -u origin main
git switch develop
git push -u origin develop

Write-Host "GitHub remote connected. main and develop are now tracking origin." -ForegroundColor Green
