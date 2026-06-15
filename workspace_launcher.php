<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

// CSRF 토큰은 세션에 보관 (project_detail.php와 동일한 패턴)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

function safeUtf8(string $s): string {
    // 비-UTF-8 바이트를 ?로 대체해 json_encode 실패 방지
    $converted = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    return $converted === false ? preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/u', '?', $s) : $converted;
}

function jsonFail(string $msg): never {
    $payload = json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        $payload = json_encode(['ok' => false, 'message' => safeUtf8($msg)], JSON_UNESCAPED_UNICODE);
    }
    echo $payload ?: '{"ok":false,"message":"unknown error"}';
    exit;
}

function jsonOk(string $msg): never {
    $payload = json_encode(['ok' => true, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        $payload = json_encode(['ok' => true, 'message' => safeUtf8($msg)], JSON_UNESCAPED_UNICODE);
    }
    echo $payload ?: '{"ok":true,"message":"done"}';
    exit;
}

// ── 1) POST 전용 ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonFail('POST 요청만 허용됩니다.');
}

// ── 2) 로컬 접속 확인 ─────────────────────────────────────────
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocal    = in_array($remoteAddr, ['127.0.0.1', '::1', '0:0:0:0:0:0:0:1'], true);
if (!$isLocal) {
    jsonFail('외부 접속에서는 실행할 수 없습니다.');
}

// ── 3) 실행 기능 활성화 확인 ──────────────────────────────────
if (!DC_LAUNCH_ENABLED) {
    jsonFail('실행 기능이 비활성화되어 있습니다.');
}

// ── 4) CSRF 검증 ──────────────────────────────────────────────
$sessionToken = (string)($_SESSION['dc_csrf_token'] ?? '');
$postToken    = (string)($_POST['csrf_token'] ?? '');
if ($sessionToken === '' || !hash_equals($sessionToken, $postToken)) {
    jsonFail('CSRF 검증에 실패했습니다. 페이지를 새로고침 후 다시 시도하세요.');
}

// ── 5) 입력 수신 (경로 미포함) ────────────────────────────────
$workspaceId = (string)($_POST['workspace_id'] ?? '');
$action      = (string)($_POST['action'] ?? '');

if ($workspaceId === '') {
    jsonFail('workspace_id가 필요합니다.');
}

// action 화이트리스트 — workspace launcher는 codex/claude만 허용
$allowedActions = ['codex', 'claude'];
if (!in_array($action, $allowedActions, true)) {
    jsonFail('허용되지 않은 action입니다. (codex 또는 claude)');
}

// ── 6) ai_workspaces.json 로드 ────────────────────────────────
$wsFile = DC_DATA_DIR . '/ai_workspaces.json';
if (!is_file($wsFile)) {
    jsonFail('ai_workspaces.json 파일을 찾을 수 없습니다.');
}
$workspaces = json_decode(file_get_contents($wsFile), true);
if (!is_array($workspaces)) {
    jsonFail('ai_workspaces.json 파싱 실패.');
}

// ── 7) workspace_id 조회 ─────────────────────────────────────
$workspace = null;
foreach ($workspaces as $ws) {
    if (is_array($ws) && ($ws['id'] ?? '') === $workspaceId) {
        $workspace = $ws;
        break;
    }
}
if ($workspace === null) {
    jsonFail("워크스페이스 '$workspaceId'를 찾을 수 없습니다.");
}

// ── 8) 워크스페이스가 허용하는 ai_tools 확인 ─────────────────
$wsTools = (array)($workspace['ai_tools'] ?? []);
if (!in_array($action, $wsTools, true)) {
    jsonFail("이 워크스페이스는 '$action' 액션을 허용하지 않습니다.");
}

// ── 9) 스크립트 호출 — workspace_id·action만 전달 ─────────────
// 경로는 PowerShell 스크립트가 projects.json에서 직접 해석한다.
$scriptPath = __DIR__ . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'launch_workspace.ps1';
if (!is_file($scriptPath)) {
    jsonFail('launch_workspace.ps1 스크립트를 찾을 수 없습니다.');
}

$cmd = 'powershell.exe -NonInteractive -ExecutionPolicy Bypass -File '
     . escapeshellarg($scriptPath)
     . ' -WorkspaceId ' . escapeshellarg($workspaceId)
     . ' -Action '      . escapeshellarg($action)
     . ' 2>&1';

$output   = [];
$exitCode = 0;
exec($cmd, $output, $exitCode);
$outputStr = implode("\n", $output);

if ($exitCode !== 0) {
    $safeOut = mb_convert_encoding($outputStr, 'UTF-8', 'UTF-8,CP949,EUC-KR') ?: $outputStr;
    jsonFail("실행 실패 (exit $exitCode): " . substr(strip_tags($safeOut), 0, 200));
}

// 성공 시 PowerShell 출력을 응답에 포함하지 않는다 (인코딩 문제 방지)
jsonOk("워크스페이스 실행 요청을 보냈습니다.");
