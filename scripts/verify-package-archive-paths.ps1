param(
    [string]$PackagePath = ""
)

$ErrorActionPreference = 'Stop'

Set-StrictMode -Version Latest

Add-Type -AssemblyName System.IO.Compression.FileSystem

function Get-ZipEntries {
    param(
        [Parameter(Mandatory = $true)]
        [string]$ZipPath
    )

    $archive = [System.IO.Compression.ZipFile]::OpenRead($ZipPath)

    try {
        return @($archive.Entries | ForEach-Object { $_.FullName })
    } finally {
        $archive.Dispose()
    }
}

function Assert-NoBackslashEntries {
    param(
        [Parameter(Mandatory = $true)]
        [string]$ZipPath,
        [Parameter(Mandatory = $true)]
        [string[]]$Entries
    )

    $badEntries = @($Entries | Where-Object { $_ -match '\\' })

    if ($badEntries.Count -gt 0) {
        Write-Host "BAD_BACKSLASH_PATHS in $ZipPath" -ForegroundColor Red
        $badEntries | ForEach-Object { Write-Host "  $_" -ForegroundColor Red }
        throw "Archive contains Windows path separators: $ZipPath"
    }

    Write-Host "OK_NO_BACKSLASH_PATHS $ZipPath ($($Entries.Count) entries)" -ForegroundColor Green
}

if ([string]::IsNullOrWhiteSpace($PackagePath)) {
    $distDir = Join-Path $PSScriptRoot '..\\04_dist'
    $resolvedDist = Resolve-Path $distDir
    $latest = Get-ChildItem -Path $resolvedDist -Filter 'pkg_r3dcomments-*.zip' |
        Sort-Object LastWriteTimeUtc -Descending |
        Select-Object -First 1

    if ($null -eq $latest) {
        throw 'No package ZIP found under 04_dist.'
    }

    $PackagePath = $latest.FullName
} else {
    $PackagePath = (Resolve-Path $PackagePath).Path
}

if (-not (Test-Path $PackagePath)) {
    throw "Package not found: $PackagePath"
}

$entries = Get-ZipEntries -ZipPath $PackagePath
Assert-NoBackslashEntries -ZipPath $PackagePath -Entries $entries

$tempRoot = Join-Path $env:TEMP ('r3d-pkg-verify-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $tempRoot | Out-Null

try {
    [System.IO.Compression.ZipFile]::ExtractToDirectory($PackagePath, $tempRoot)

    $innerZips = Get-ChildItem -Path $tempRoot -Filter '*.zip' -File | Sort-Object Name

    foreach ($zip in $innerZips) {
        $innerEntries = Get-ZipEntries -ZipPath $zip.FullName
        Assert-NoBackslashEntries -ZipPath $zip.Name -Entries $innerEntries
    }

    Write-Host 'PACKAGE_ARCHIVE_PATH_VERIFICATION_OK' -ForegroundColor Green
} finally {
    if (Test-Path $tempRoot) {
        Remove-Item -Path $tempRoot -Recurse -Force
    }
}

