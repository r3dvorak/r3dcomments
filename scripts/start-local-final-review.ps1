param()

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$repoRoot = Split-Path -Parent $PSScriptRoot
$checklistPath = Join-Path $repoRoot '03_docs\CHECKLISTE_FINALES_LOKALES_REVIEW_J5_J6_2026-03-13.md'
$todoPath = Join-Path $repoRoot '03_docs\TODO_GESAMTREVIEW_UI_WORKFLOW_2026-03-12.md'
$projectJsonPath = Join-Path $repoRoot 'project.json'

if (-not (Test-Path $projectJsonPath)) {
    throw "project.json not found: $projectJsonPath"
}

$project = Get-Content $projectJsonPath -Raw | ConvertFrom-Json
$version = [string]$project.project.defaults.version

$j5Url = 'https://r3d-clawd-j5.test/administrator'
$j6Url = 'https://r3d-clawd-joomla6.test/administrator'

Write-Host ('R3D Comments local final review prep') -ForegroundColor Cyan
Write-Host ('Version:   ' + $version)
Write-Host ('J5 Admin:  ' + $j5Url)
Write-Host ('J6 Admin:  ' + $j6Url)
Write-Host ('Checklist: ' + $checklistPath)
Write-Host ('TODO:      ' + $todoPath)

if (Test-Path $checklistPath) {
    Start-Process $checklistPath
}

if (Test-Path $todoPath) {
    Start-Process $todoPath
}

Start-Process $j5Url
Start-Process $j6Url

