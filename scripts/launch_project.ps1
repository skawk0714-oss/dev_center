#Requires -Version 5.1
<#
.SYNOPSIS
    Dev Center 프로젝트 실행 스크립트.
    PHP project_detail.php에서만 호출된다.
    project_id와 action만 받아 projects.json에서 경로를 직접 읽으므로
    외부 경로 주입이 불가능하다.
#>
param(
    [Parameter(Mandatory=$true)]  [string] $ProjectId,
    [Parameter(Mandatory=$true)]  [string] $Action
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# ── 상수 ──────────────────────────────────────────────────────
$AllowedActions = @('vscode', 'codex', 'claude', 'all')
$AllowedRoots   = @(
    'C:/xampp/htdocs',
    'D:/projects',
    'C:/projects'
)

# ── 유효성: Action ─────────────────────────────────────────────
if ($Action -notin $AllowedActions) {
    Write-Output "ERROR: 허용되지 않은 action '$Action'"
    exit 1
}

# ── projects.json 로드 ─────────────────────────────────────────
$ScriptDir    = Split-Path -Parent $MyInvocation.MyCommand.Definition
$ProjectsFile = Join-Path $ScriptDir '..\data\projects.json'
$ProjectsFile = [System.IO.Path]::GetFullPath($ProjectsFile)

if (-not (Test-Path $ProjectsFile)) {
    Write-Output "ERROR: projects.json 파일을 찾을 수 없습니다: $ProjectsFile"
    exit 1
}

try {
    $Projects = Get-Content $ProjectsFile -Raw -Encoding UTF8 | ConvertFrom-Json
} catch {
    Write-Output "ERROR: projects.json 파싱 실패 — $_"
    exit 1
}

# ── 유효성: ProjectId → project 찾기 ─────────────────────────
$Project = $null
foreach ($p in $Projects) {
    if ($p.id -eq $ProjectId) {
        $Project = $p
        break
    }
}

if ($null -eq $Project) {
    Write-Output "ERROR: 프로젝트 ID '$ProjectId'를 찾을 수 없습니다."
    exit 1
}

# ── 유효성: 경로 존재 여부 ──────────────────────────────────────
$RawPath    = $Project.path -replace '/', '\'
$NativePath = [System.IO.Path]::GetFullPath($RawPath)

if (-not (Test-Path -LiteralPath $NativePath)) {
    Write-Output "ERROR: 프로젝트 경로가 존재하지 않습니다: $NativePath"
    exit 1
}

# ── 유효성: 허용된 루트 확인 (정규화 후 디렉터리 경계 비교) ──────
$PathAllowed = $false
foreach ($root in $AllowedRoots) {
    $canonicalRoot = [System.IO.Path]::GetFullPath(($root -replace '/', '\'))
    # 디렉터리 경계 보장: htdocs_bad 가 htdocs 로 매칭되지 않도록 '\\' 추가
    if (($NativePath + '\').StartsWith($canonicalRoot.TrimEnd('\') + '\', [System.StringComparison]::OrdinalIgnoreCase)) {
        $PathAllowed = $true
        break
    }
}

if (-not $PathAllowed) {
    Write-Output "ERROR: 허용된 루트 밖의 경로입니다: $NativePath"
    exit 1
}

# ── 실행 함수 ──────────────────────────────────────────────────
function Invoke-VSCode {
    param([string]$Path)
    if (-not (Get-Command 'code' -ErrorAction SilentlyContinue)) {
        Write-Output "WARNING: 'code' 명령을 찾을 수 없습니다. VS Code PATH 설정을 확인하세요."
        return $false
    }
    Start-Process 'code' -ArgumentList $Path -WindowStyle Normal
    Write-Output "OK: VS Code를 열었습니다 — $Path"
    return $true
}

function Invoke-Codex {
    param([string]$Path)
    if (-not (Get-Command 'codex' -ErrorAction SilentlyContinue)) {
        Write-Output "WARNING: 'codex' 명령을 찾을 수 없습니다. npm install -g @openai/codex 실행 여부를 확인하세요."
        return $false
    }
    $cdCmd = "Set-Location -LiteralPath '$($Path -replace "'","''")'; codex"
    Start-Process 'powershell.exe' -ArgumentList @('-NoExit', '-Command', $cdCmd) -WindowStyle Normal
    Write-Output "OK: Codex를 실행했습니다 — $Path"
    return $true
}

function Invoke-ClaudeCode {
    param([string]$Path)
    if (-not (Get-Command 'claude' -ErrorAction SilentlyContinue)) {
        Write-Output "WARNING: 'claude' 명령을 찾을 수 없습니다. Claude Code CLI 설치를 확인하세요."
        return $false
    }
    $cdCmd = "Set-Location -LiteralPath '$($Path -replace "'","''")'; claude"
    Start-Process 'powershell.exe' -ArgumentList @('-NoExit', '-Command', $cdCmd) -WindowStyle Normal
    Write-Output "OK: Claude Code를 실행했습니다 — $Path"
    return $true
}

# ── 액션 실행 ──────────────────────────────────────────────────
switch ($Action) {
    'vscode' { Invoke-VSCode     -Path $NativePath | Out-Null }
    'codex'  { Invoke-Codex      -Path $NativePath | Out-Null }
    'claude' { Invoke-ClaudeCode -Path $NativePath | Out-Null }
    'all' {
        Invoke-VSCode     -Path $NativePath | Out-Null
        Start-Sleep -Milliseconds 300
        Invoke-Codex      -Path $NativePath | Out-Null
        Start-Sleep -Milliseconds 300
        Invoke-ClaudeCode -Path $NativePath | Out-Null
    }
}

# 함수 내 Write-Output이 파이프로 버려졌으므로 최종 결과 출력
switch ($Action) {
    'vscode' {
        if (Get-Command 'code'   -ErrorAction SilentlyContinue) { Write-Output "OK: VS Code 실행 — $NativePath" }
        else { Write-Output "WARNING: code 명령 없음"; exit 2 }
    }
    'codex' {
        if (Get-Command 'codex'  -ErrorAction SilentlyContinue) { Write-Output "OK: Codex 실행 — $NativePath" }
        else { Write-Output "WARNING: codex 명령 없음"; exit 2 }
    }
    'claude' {
        if (Get-Command 'claude' -ErrorAction SilentlyContinue) { Write-Output "OK: Claude Code 실행 — $NativePath" }
        else { Write-Output "WARNING: claude 명령 없음"; exit 2 }
    }
    'all' {
        $missing = @()
        if (-not (Get-Command 'code'   -ErrorAction SilentlyContinue)) { $missing += 'code' }
        if (-not (Get-Command 'codex'  -ErrorAction SilentlyContinue)) { $missing += 'codex' }
        if (-not (Get-Command 'claude' -ErrorAction SilentlyContinue)) { $missing += 'claude' }
        if ($missing.Count -gt 0) {
            Write-Output "WARNING: 다음 명령을 찾을 수 없습니다: $($missing -join ', ')"
            exit 2
        }
        Write-Output "OK: VS Code + Codex + Claude 실행 — $NativePath"
    }
}
exit 0
