[CmdletBinding()]
param(
    [string]$SourceDir,
    [string]$TargetDir = "D:\1DEV\pkgs\r3d-comments"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($SourceDir)) {
    throw "SourceDir ist leer. Bitte -SourceDir angeben."
}

if (-not (Test-Path -LiteralPath $SourceDir)) {
    throw "SourceDir nicht gefunden: $SourceDir"
}

if (-not (Test-Path -LiteralPath $TargetDir)) {
    New-Item -ItemType Directory -Path $TargetDir -Force | Out-Null
}

$roboCopy = Get-Command robocopy -ErrorAction SilentlyContinue
if ($roboCopy) {
    $null = robocopy $SourceDir $TargetDir /MIR /R:1 /W:1 /XJ /NFL /NDL /NP /XD ".idea" ".vscode"
    $exitCode = $LASTEXITCODE
    if ($exitCode -gt 7) {
        throw "robocopy fehlgeschlagen. ExitCode: $exitCode"
    }
}
else {
    if (Test-Path -LiteralPath $TargetDir) {
        Get-ChildItem -LiteralPath $TargetDir -Force | Remove-Item -Recurse -Force
    }
    Copy-Item -Path (Join-Path $SourceDir '*') -Destination $TargetDir -Recurse -Force
    $exitCode = 0
}

Write-Host "Projekt synchronisiert." -ForegroundColor Green
Write-Host "Quelle: $SourceDir"
Write-Host "Ziel:   $TargetDir"
Write-Host "Robocopy ExitCode: $exitCode"

