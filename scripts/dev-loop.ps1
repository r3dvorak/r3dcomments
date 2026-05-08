[CmdletBinding()]
param(
    [string]$ProjectDir = "D:\1DEV\pkgs\r3d-comments",
    [string]$SourceDir,
    [switch]$SkipSync,
    [switch]$SkipInstall,
    [switch]$SkipHttpChecks
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$scriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$syncScript = Join-Path $scriptRoot 'sync-r3d-comments-project.ps1'
$buildScript = Join-Path $scriptRoot 'build-install-test-r3d-comments.ps1'

if (-not (Test-Path -LiteralPath $syncScript)) {
    throw "Sync-Script nicht gefunden: $syncScript"
}
if (-not (Test-Path -LiteralPath $buildScript)) {
    throw "Build-Script nicht gefunden: $buildScript"
}

$startedAt = Get-Date

if (-not $SkipSync -and -not [string]::IsNullOrWhiteSpace($SourceDir)) {
    Write-Host "[1/2] Sync startet..." -ForegroundColor Cyan
    & $syncScript -SourceDir $SourceDir -TargetDir $ProjectDir
}
else {
    Write-Host "[1/2] Sync uebersprungen (kein SourceDir oder -SkipSync)." -ForegroundColor Yellow
}

Write-Host "[2/2] Build + Install + Test startet..." -ForegroundColor Cyan

$buildArgs = @{
    ProjectDir = $ProjectDir
}
if ($SkipInstall) {
    $buildArgs['SkipInstall'] = $true
}
if ($SkipHttpChecks) {
    $buildArgs['SkipHttpChecks'] = $true
}

& $buildScript @buildArgs

$finishedAt = Get-Date
$duration = New-TimeSpan -Start $startedAt -End $finishedAt
$durationText = "{0:00}:{1:00}" -f [int]$duration.TotalMinutes, [int]$duration.Seconds

Write-Host ""
Write-Host "ERFOLG: Dev-Loop abgeschlossen." -ForegroundColor Green
Write-Host ("Start:   {0}" -f $startedAt.ToString('yyyy-MM-dd HH:mm:ss'))
Write-Host ("Ende:    {0}" -f $finishedAt.ToString('yyyy-MM-dd HH:mm:ss'))
Write-Host ("Dauer:   {0}" -f $durationText)
Write-Host ("Projekt: {0}" -f $ProjectDir)

