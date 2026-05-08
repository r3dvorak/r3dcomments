[CmdletBinding()]
param(
    [string]$ProjectDir = "D:\1DEV\pkgs\r3dcomments",
    [string]$SiteJ5Root = "G:\laragon\www\r3d-clawd-j5",
    [string]$SiteJ6Root = "G:\laragon\www\r3d-clawd-joomla6",
    [string]$DropDir,
    [string]$SiteJ5Url = "http://r3d-clawd-j5.test/",
    [string]$SiteJ6Url = "http://r3d-clawd-joomla6.test/",
    [string]$PhpExe,
    [switch]$SkipInstall,
    [switch]$SkipHttpChecks
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Ensure-Directory {
    param([string]$Path)

    if (-not (Test-Path -LiteralPath $Path)) {
        New-Item -ItemType Directory -Path $Path -Force | Out-Null
    }
}

function Read-VersionFromPackageXml {
    param([string]$XmlPath)

    [xml]$xml = Get-Content -LiteralPath $XmlPath -Raw
    $node = $xml.SelectSingleNode('/extension/version')
    if ($null -eq $node -or [string]::IsNullOrWhiteSpace($node.InnerText)) {
        throw "Keine <version> in $XmlPath gefunden."
    }

    return $node.InnerText.Trim()
}

function New-ZipFromDirectory {
    param(
        [string]$SourceDir,
        [string]$DestinationZip
    )

    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem

    if (Test-Path -LiteralPath $DestinationZip) {
        Remove-Item -LiteralPath $DestinationZip -Force
    }

    $sourceRoot = [System.IO.Path]::GetFullPath($SourceDir)
    if (-not $sourceRoot.EndsWith([System.IO.Path]::DirectorySeparatorChar)) {
        $sourceRoot += [System.IO.Path]::DirectorySeparatorChar
    }

    $fileStream = [System.IO.File]::Open($DestinationZip, [System.IO.FileMode]::CreateNew)
    try {
        $archive = New-Object System.IO.Compression.ZipArchive($fileStream, [System.IO.Compression.ZipArchiveMode]::Create, $false)
        try {
            Get-ChildItem -LiteralPath $SourceDir -Recurse -File | ForEach-Object {
                $fullName = [System.IO.Path]::GetFullPath($_.FullName)
                $entryName = $fullName.Substring($sourceRoot.Length).Replace('\', '/')
                [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                    $archive,
                    $fullName,
                    $entryName,
                    [System.IO.Compression.CompressionLevel]::Optimal
                ) | Out-Null
            }
        }
        finally {
            $archive.Dispose()
        }
    }
    finally {
        $fileStream.Dispose()
    }
}

function Resolve-LaragonPhpExe {
    param([string]$ExplicitPath)

    if (-not [string]::IsNullOrWhiteSpace($ExplicitPath)) {
        if (-not (Test-Path -LiteralPath $ExplicitPath)) {
            throw "PHP.exe nicht gefunden: $ExplicitPath"
        }
        return $ExplicitPath
    }

    $candidates = Get-ChildItem -LiteralPath "G:\laragon\bin\php" -Directory -ErrorAction SilentlyContinue |
        Where-Object { Test-Path -LiteralPath (Join-Path $_.FullName 'php.exe') }

    if (-not $candidates) {
        throw "Keine Laragon-PHP-Installation gefunden unter G:\laragon\bin\php"
    }

    $ranked = foreach ($candidate in $candidates) {
        $name = $candidate.Name
        $versionText = "0.0.0"
        if ($name -match 'php-([0-9]+\.[0-9]+\.[0-9]+)') {
            $versionText = $Matches[1]
        }

        $phpPath = Join-Path $candidate.FullName 'php.exe'
        $modules = & $phpPath -m 2>$null
        $hasMysqli = $modules -contains 'mysqli'

        [pscustomobject]@{
            Path = $phpPath
            Version = [version]$versionText
            HasMysqli = [bool]$hasMysqli
        }
    }

    $selected = $ranked |
        Where-Object { $_.HasMysqli } |
        Sort-Object Version -Descending |
        Select-Object -First 1

    if ($null -eq $selected) {
        throw "Keine passende Laragon-PHP-Installation mit mysqli gefunden."
    }

    return $selected.Path
}

function Invoke-Checked {
    param(
        [string]$Exe,
        [string[]]$Args,
        [string]$ErrorContext
    )

    & $Exe @Args
    if ($LASTEXITCODE -ne 0) {
        throw "$ErrorContext fehlgeschlagen. ExitCode: $LASTEXITCODE"
    }
}

function Install-PackageToJoomlaSite {
    param(
        [string]$Php,
        [string]$SiteRoot,
        [string]$PackageZip,
        [string]$SiteLabel
    )

    $cliPath = Join-Path $SiteRoot 'cli\joomla.php'
    if (-not (Test-Path -LiteralPath $cliPath)) {
        throw "Joomla CLI nicht gefunden fuer ${SiteLabel}: ${cliPath}"
    }

    $tempZip = Join-Path $env:TEMP ("pkg_r3dcomments-install-{0}.zip" -f [guid]::NewGuid())
    Copy-Item -LiteralPath $PackageZip -Destination $tempZip -Force
    Write-Host "[$SiteLabel] Installiere Package: $tempZip" -ForegroundColor Cyan

    try {
        $global:LASTEXITCODE = 0
        & $Php $cliPath extension:install "--path=$tempZip"
        $exitCode = if (Get-Variable -Name LASTEXITCODE -Scope Global -ErrorAction SilentlyContinue) { $global:LASTEXITCODE } else { if ($?) { 0 } else { 1 } }

        if ($exitCode -ne 0) {
            Write-Host "[$SiteLabel] Fallback mit positional path versucht..." -ForegroundColor Yellow
            $global:LASTEXITCODE = 0
            & $Php $cliPath extension:install $tempZip
            $exitCode = if (Get-Variable -Name LASTEXITCODE -Scope Global -ErrorAction SilentlyContinue) { $global:LASTEXITCODE } else { if ($?) { 0 } else { 1 } }
        }

        if ($exitCode -ne 0) {
            throw "[$SiteLabel] extension:install fehlgeschlagen. ExitCode: $exitCode"
        }

        Write-Host "[$SiteLabel] Install OK" -ForegroundColor Green
    }
    finally {
        if (Test-Path -LiteralPath $tempZip) {
            Remove-Item -LiteralPath $tempZip -Force -ErrorAction SilentlyContinue
        }
    }
}

function Invoke-HttpSmoke {
    param(
        [string]$Url,
        [string]$Label
    )

    $lastError = $null
    for ($attempt = 1; $attempt -le 3; $attempt++) {
        try {
            [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12
            [System.Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }
            $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 20
            Write-Host "[$Label] $Url -> HTTP $($response.StatusCode)" -ForegroundColor Green
            return
        }
        catch {
            $lastError = $_.Exception.Message
            Start-Sleep -Seconds 2
        }
    }

    throw "[$Label] HTTP-Check fehlgeschlagen fuer ${Url}: $lastError"
}

$srcRoot = Join-Path $ProjectDir '01_src'
$buildRoot = Join-Path $ProjectDir '02_build'
$distRoot = Join-Path $ProjectDir '04_dist'
$updatesRoot = Join-Path $ProjectDir '05_updates'
if ([string]::IsNullOrWhiteSpace($DropDir)) {
    $DropDir = Join-Path $buildRoot 'drop'
}

$packageXml = Join-Path $srcRoot 'pkg_r3dcomments.xml'
$componentDir = Join-Path $srcRoot 'com_r3dcomments'
$moduleDir = Join-Path $srcRoot 'mod_r3dcomments'
$packageScript = Join-Path $srcRoot 'script.pkg_r3dcomments.php'

foreach ($path in @($ProjectDir, $srcRoot, $packageXml, $componentDir, $moduleDir, $packageScript, $SiteJ5Root, $SiteJ6Root)) {
    if (-not (Test-Path -LiteralPath $path)) {
        throw "Pfad fehlt: $path"
    }
}

Ensure-Directory -Path $buildRoot
Ensure-Directory -Path $distRoot
Ensure-Directory -Path $updatesRoot
Ensure-Directory -Path $DropDir

$version = Read-VersionFromPackageXml -XmlPath $packageXml
$stageRoot = Join-Path $buildRoot ("pkg_r3dcomments_stage_" + [guid]::NewGuid())
$packageStageDir = Join-Path $stageRoot 'pkg_r3dcomments'

$componentZip = Join-Path $packageStageDir 'com_r3dcomments.zip'
$moduleZip = Join-Path $packageStageDir 'mod_r3dcomments.zip'

$packageZipVersioned = Join-Path $distRoot ("pkg_r3dcomments-{0}.zip" -f $version)
$packageZipLatest = Join-Path $distRoot 'pkg_r3dcomments-latest.zip'
$dropZip = Join-Path $DropDir ("pkg_r3dcomments-{0}.zip" -f $version)

$logRoot = Join-Path $updatesRoot ("dual-test-" + (Get-Date -Format "yyyyMMdd-HHmmss"))
Ensure-Directory -Path $logRoot
$summaryPath = Join-Path $logRoot 'summary.txt'

$phpPath = Resolve-LaragonPhpExe -ExplicitPath $PhpExe
Write-Host "Verwende PHP: $phpPath"

Ensure-Directory -Path $stageRoot
Ensure-Directory -Path $packageStageDir

try {
    New-ZipFromDirectory -SourceDir $componentDir -DestinationZip $componentZip
    New-ZipFromDirectory -SourceDir $moduleDir -DestinationZip $moduleZip

    Copy-Item -LiteralPath $packageXml -Destination (Join-Path $packageStageDir 'pkg_r3dcomments.xml') -Force
    Copy-Item -LiteralPath $packageScript -Destination (Join-Path $packageStageDir 'script.pkg_r3dcomments.php') -Force

    if (Test-Path -LiteralPath $packageZipVersioned) {
        Remove-Item -LiteralPath $packageZipVersioned -Force
    }

    New-ZipFromDirectory -SourceDir $packageStageDir -DestinationZip $packageZipVersioned
    Copy-Item -LiteralPath $packageZipVersioned -Destination $packageZipLatest -Force
    Copy-Item -LiteralPath $packageZipVersioned -Destination $dropZip -Force

    if (-not $SkipInstall) {
        Install-PackageToJoomlaSite -Php $phpPath -SiteRoot $SiteJ5Root -PackageZip $packageZipVersioned -SiteLabel 'J5'
        Install-PackageToJoomlaSite -Php $phpPath -SiteRoot $SiteJ6Root -PackageZip $packageZipVersioned -SiteLabel 'J6'
    }

    if (-not $SkipHttpChecks) {
        $j5Base = $SiteJ5Url.TrimEnd('/')
        $j6Base = $SiteJ6Url.TrimEnd('/')

        Invoke-HttpSmoke -Url "$j5Base/" -Label 'J5 Frontend'
        Invoke-HttpSmoke -Url "$j5Base/administrator/" -Label 'J5 Admin'
        Invoke-HttpSmoke -Url "$j6Base/" -Label 'J6 Frontend'
        Invoke-HttpSmoke -Url "$j6Base/administrator/" -Label 'J6 Admin'
    }

    @(
        "Version:      $version",
        "PHP:          $phpPath",
        "Package ZIP:  $packageZipVersioned",
        "Latest ZIP:   $packageZipLatest",
        "Dropzone ZIP: $dropZip",
        "J5 Site:      $SiteJ5Root",
        "J6 Site:      $SiteJ6Root"
    ) | Set-Content -LiteralPath $summaryPath -Encoding UTF8
}
finally {
    if (Test-Path -LiteralPath $stageRoot) {
        Remove-Item -LiteralPath $stageRoot -Recurse -Force
    }
}

Write-Host "Build/Install/Test abgeschlossen." -ForegroundColor Green
Write-Host "Summary: $summaryPath"
Write-Host "Package: $packageZipVersioned"

