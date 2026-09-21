param(
    [string]$Branch = "develop"
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "Git is not installed or not available in PATH." -ForegroundColor Red
    exit 1
}

$current = (git branch --show-current).Trim()
if ($current -ne $Branch) {
    git switch $Branch
}

git pull --rebase origin $Branch

$status = git status --porcelain
if (-not $status) {
    Write-Host "No local changes to commit. Pushing existing branch state..." -ForegroundColor Yellow
    git push origin $Branch
    exit 0
}

Write-Host "Reviewing changed files:" -ForegroundColor Cyan
git status --short

# Safety: blocked file patterns should never be committed.
$blocked = git status --porcelain | Select-String -Pattern "(^|\s)(\.env|storage/leads\.csv|.*\.pem|.*\.key)$"
if ($blocked) {
    Write-Host "Blocked sensitive/runtime file detected. Push cancelled." -ForegroundColor Red
    $blocked | ForEach-Object { Write-Host $_ }
    exit 1
}

git add -A
$stamp = Get-Date -Format "yyyy-MM-dd HH:mm"
git commit -m "chore: daily Wedding Za update $stamp"
git push origin $Branch

Write-Host "Daily push completed on $Branch." -ForegroundColor Green
