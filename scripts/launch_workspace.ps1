#Requires -Version 5.1
<#
.SYNOPSIS
    Dev Center 워크스페이스 실행 스크립트.
    workspace_launcher.php에서만 호출된다.
    WorkspaceId와 Action만 받아 ai_workspaces.json + projects.json에서
    경로를 직접 읽으므로 외부 경로 주입이 불가능하다.
#>
param(
    [Parameter(Mandatory=$true)]  [string] $WorkspaceId,
    [Parameter(Mandatory=$true)]  [string] $Action
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# ── 상수 ──────────────────────────────────────────────────────
$AllowedActions = @('codex', 'claude')
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

# ── 파일 경로 계산 ─────────────────────────────────────────────
$ScriptDir      = Split-Path -Parent $MyInvocation.MyCommand.Definition
$WorkspacesFile = [System.IO.Path]::GetFullPath((Join-Path $ScriptDir '..\data\ai_workspaces.json'))
$ProjectsFile   = [System.IO.Path]::GetFullPath((Join-Path $ScriptDir '..\data\projects.json'))

# ── ai_workspaces.json 로드 ───────────────────────────────────
if (-not (Test-Path $WorkspacesFile)) {
    Write-Output "ERROR: ai_workspaces.json 파일을 찾을 수 없습니다: $WorkspacesFile"
    exit 1
}
try {
    $Workspaces = Get-Content $WorkspacesFile -Raw -Encoding UTF8 | ConvertFrom-Json
} catch {
    Write-Output "ERROR: ai_workspaces.json 파싱 실패 — $_"
    exit 1
}

# ── WorkspaceId 조회 ──────────────────────────────────────────
$Workspace = $null
foreach ($ws in $Workspaces) {
    if ($ws.id -eq $WorkspaceId) {
        $Workspace = $ws
        break
    }
}
if ($null -eq $Workspace) {
    Write-Output "ERROR: 워크스페이스 ID '$WorkspaceId'를 찾을 수 없습니다."
    exit 1
}

# ── ai_tools 확인 ─────────────────────────────────────────────
if ($Action -notin $Workspace.ai_tools) {
    Write-Output "ERROR: 워크스페이스 '$WorkspaceId'는 '$Action' 액션을 허용하지 않습니다."
    exit 1
}

# ── default_project_id 추출 ───────────────────────────────────
$DefaultProjectId = $Workspace.default_project_id
if ([string]::IsNullOrWhiteSpace($DefaultProjectId)) {
    Write-Output "ERROR: 워크스페이스 '$WorkspaceId'에 default_project_id가 없습니다."
    exit 1
}

# ── projects.json 로드 ────────────────────────────────────────
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

# ── default_project_id → project.path 해석 ───────────────────
$Project = $null
foreach ($p in $Projects) {
    if ($p.id -eq $DefaultProjectId) {
        $Project = $p
        break
    }
}
if ($null -eq $Project) {
    Write-Output "ERROR: 프로젝트 ID '$DefaultProjectId'를 projects.json에서 찾을 수 없습니다."
    exit 1
}

# ── 경로 정규화 및 존재 확인 ──────────────────────────────────
$RawPath    = $Project.path -replace '/', '\'
$NativePath = [System.IO.Path]::GetFullPath($RawPath)

if (-not (Test-Path -LiteralPath $NativePath)) {
    Write-Output "ERROR: 프로젝트 경로가 존재하지 않습니다: $NativePath"
    exit 1
}

# ── 허용된 루트 확인 ──────────────────────────────────────────
$PathAllowed = $false
foreach ($root in $AllowedRoots) {
    $canonicalRoot = [System.IO.Path]::GetFullPath(($root -replace '/', '\'))
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
function Invoke-Codex {
    param([string]$Path)
    if (-not (Get-Command 'codex' -ErrorAction SilentlyContinue)) {
        Write-Output "WARNING: 'codex' 명령을 찾을 수 없습니다. npm install -g @openai/codex 실행 여부를 확인하세요."
        return $false
    }
    $cdCmd = "Set-Location -LiteralPath '$($Path -replace "'","''")'; codex"
    Start-Process 'powershell.exe' -ArgumentList @('-NoExit', '-Command', $cdCmd) -WindowStyle Normal
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
    return $true
}

# ── 액션 실행 ──────────────────────────────────────────────────
switch ($Action) {
    'codex' {
        $ok = Invoke-Codex -Path $NativePath
        if (-not $ok) { exit 2 }
        Write-Output "OK: Codex 실행 — $NativePath (워크스페이스: $WorkspaceId)"
    }
    'claude' {
        $ok = Invoke-ClaudeCode -Path $NativePath
        if (-not $ok) { exit 2 }
        Write-Output "OK: Claude Code 실행 — $NativePath (워크스페이스: $WorkspaceId)"
    }
}

exit 0
