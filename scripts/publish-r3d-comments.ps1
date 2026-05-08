[CmdletBinding()]
param(
    [string]$ProjectRoot = "D:\1DEV\pkgs\r3d-comments",
    [string]$ToolsRoot = "D:\1DEV\_tools",
    [string]$EnvFile = ".env.release.local",
    [switch]$SkipLocalInstall,
    [switch]$DryRun,
    [switch]$NoPublish
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Resolve-ProjectPath {
    param(
        [string]$Root,
        [string]$Path
    )

    if ([System.IO.Path]::IsPathRooted($Path)) {
        return $Path
    }

    return (Join-Path $Root $Path)
}

function Invoke-CheckedScript {
    param(
        [string]$ScriptPath,
        [hashtable]$Arguments
    )

    if (-not (Test-Path -LiteralPath $ScriptPath -PathType Leaf)) {
        throw "Script not found: $ScriptPath"
    }

    & $ScriptPath @Arguments
    $exitCode = if (Get-Variable -Name LASTEXITCODE -Scope Global -ErrorAction SilentlyContinue) { $global:LASTEXITCODE } else { if ($?) { 0 } else { 1 } }
    if ($exitCode -ne 0) {
        throw "Script failed: $ScriptPath (ExitCode: $exitCode)"
    }
}

$resolvedProjectRoot = (Resolve-Path -LiteralPath $ProjectRoot).Path
$resolvedToolsRoot = (Resolve-Path -LiteralPath $ToolsRoot).Path
$resolvedEnvPath = Resolve-ProjectPath -Root $resolvedProjectRoot -Path $EnvFile

$buildScript = Join-Path $resolvedProjectRoot "scripts\build-install-test-r3d-comments.ps1"
$createDownloadScript = Join-Path $resolvedToolsRoot "31-create-download.ps1"
$publishUpdateScript = Join-Path $resolvedToolsRoot "32-publish-updateserver.ps1"

Write-Host "[1/3] Local build/install/test..." -ForegroundColor Cyan
if ($SkipLocalInstall) {
    Write-Host "Skipped local install/test because -SkipLocalInstall was set." -ForegroundColor Yellow
}
else {
    Invoke-CheckedScript -ScriptPath $buildScript -Arguments @{
        ProjectDir = $resolvedProjectRoot
    }
}

Write-Host "[2/3] Create DE/EN download release entries..." -ForegroundColor Cyan
$createArgs = @{
    ProjectRoot = $resolvedProjectRoot
    EnvFile = $resolvedEnvPath
}
if ($DryRun) {
    $createArgs["DryRun"] = $true
}
Invoke-CheckedScript -ScriptPath $createDownloadScript -Arguments $createArgs

Write-Host "[3/3] Publish update server XML..." -ForegroundColor Cyan
$updateArgs = @{
    ProjectRoot = $resolvedProjectRoot
    EnvFile = $resolvedEnvPath
}
if ($DryRun -or $NoPublish) {
    $updateArgs["DryRun"] = $true
}
Invoke-CheckedScript -ScriptPath $publishUpdateScript -Arguments $updateArgs

Write-Host "Publish workflow finished." -ForegroundColor Green
Write-Host ("Project: {0}" -f $resolvedProjectRoot)
Write-Host ("Env:     {0}" -f $resolvedEnvPath)

