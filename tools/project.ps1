$ErrorActionPreference = 'Stop'

if ($args.Count -lt 1) {
    throw 'Usage: project.ps1 php|composer|npm [arguments]'
}

$practiceCommands = @{
    php = 'php.exe'
    composer = 'composer.bat'
    npm = 'npm.cmd'
}
$practiceToolName = [string]$args[0]
if (-not $practiceCommands.ContainsKey($practiceToolName)) {
    throw 'Supported commands: php, composer, npm.'
}
$practiceToolArguments = @($args | Select-Object -Skip 1)
$practiceExecutable = (Get-Command $practiceCommands[$practiceToolName] -CommandType Application).Source
$practiceRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$practiceRuntime = Join-Path $practiceRoot 'temp\runtime'
$practiceEnvironment = @{
    PHP_INI_SCAN_DIR = (Join-Path $PSScriptRoot 'php\conf.d')
    PRACTICE_TEMP_DIR = (Join-Path $practiceRuntime 'php')
    COMPOSER_CACHE_DIR = (Join-Path $practiceRuntime 'composer-cache')
    npm_config_cache = (Join-Path $practiceRuntime 'npm-cache')
    TEMP = (Join-Path $practiceRuntime 'os')
    TMP = (Join-Path $practiceRuntime 'os')
}
$practicePreviousEnvironment = @{}
$practiceExitCode = 1

try {
    foreach ($practiceKey in $practiceEnvironment.Keys) {
        $practicePreviousEnvironment[$practiceKey] = [Environment]::GetEnvironmentVariable($practiceKey, 'Process')
        [Environment]::SetEnvironmentVariable($practiceKey, $practiceEnvironment[$practiceKey], 'Process')
    }
    foreach ($practiceDirectory in @('php', 'composer-cache', 'npm-cache', 'os')) {
        New-Item -ItemType Directory -Path (Join-Path $practiceRuntime $practiceDirectory) -Force | Out-Null
    }
    Push-Location -LiteralPath (Join-Path $practiceRoot 'site')
    try {
        & $practiceExecutable @practiceToolArguments
        $practiceExitCode = $LASTEXITCODE
    }
    finally {
        Pop-Location
    }
}
finally {
    foreach ($practiceKey in $practicePreviousEnvironment.Keys) {
        [Environment]::SetEnvironmentVariable($practiceKey, $practicePreviousEnvironment[$practiceKey], 'Process')
    }
}

exit $practiceExitCode
