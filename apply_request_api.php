<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

header('Content-Type: application/json; charset=UTF-8');

function json_fail(string $msg): never {
    echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/* POST 전용 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_fail('허용되지 않은 메서드입니다.');
}

/* CSRF 검증 */
$token   = trim((string)($_POST['csrf_token'] ?? ''));
$session = (string)($_SESSION['dc_csrf_token'] ?? '');
if ($token === '' || !hash_equals($session, $token)) {
    json_fail('CSRF 토큰이 유효하지 않습니다.');
}

/* 입력값 */
$expId    = trim((string)($_POST['exp_id']    ?? ''));
$recordId = trim((string)($_POST['record_id'] ?? ''));
if ($expId === '' || $recordId === '') {
    json_fail('exp_id와 record_id는 필수입니다.');
}

/* exp_id 존재 확인 */
$labFile = DC_DATA_DIR . '/lab_experiments.json';
if (!is_file($labFile)) {
    json_fail('실험 데이터를 찾을 수 없습니다.');
}
$experiments = json_decode(file_get_contents($labFile), true);
if (!is_array($experiments)) {
    json_fail('실험 데이터를 읽을 수 없습니다.');
}
$exp = null;
foreach ($experiments as $e) {
    if (($e['id'] ?? '') === $expId) { $exp = $e; break; }
}
if ($exp === null) {
    json_fail('존재하지 않는 실험 ID입니다.');
}

/* record_id 일치 확인 */
if (($exp['record_id'] ?? '') !== $recordId) {
    json_fail('record_id가 실험 데이터와 일치하지 않습니다.');
}

/* apply_requests.json 로드 */
$arFile    = DC_DATA_DIR . '/apply_requests.json';
$requests  = [];
if (is_file($arFile)) {
    $decoded = json_decode(file_get_contents($arFile), true);
    if (is_array($decoded)) { $requests = $decoded; }
}

/* 중복 draft/pending 방지 */
$dupStatuses = ['draft', 'pending', 'reviewing'];
foreach ($requests as $r) {
    if (($r['exp_id'] ?? '') === $expId
        && ($r['record_id'] ?? '') === $recordId
        && in_array($r['status'] ?? '', $dupStatuses, true)) {
        json_fail('이미 처리 중인 반영 요청이 있습니다.');
    }
}

/* 새 요청 항목 생성 */
$now = date('Y-m-d H:i:s');
$id  = 'ar-' . date('Ymd-His') . '-' . $recordId;
$requests[] = [
    'id'               => $id,
    'exp_id'           => $expId,
    'record_id'        => $recordId,
    'target_project_id'=> null,
    'requested_at'     => $now,
    'status'           => 'draft',
    'codex_checklist'  => '',
    'resolved_at'      => null,
];

/* 파일 저장 */
$json = json_encode(
    $requests,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
if (file_put_contents($arFile, $json, LOCK_EX) === false) {
    json_fail('파일 저장에 실패했습니다.');
}

echo json_encode(
    ['ok' => true, 'message' => '반영 요청 초안을 만들었습니다.'],
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);
