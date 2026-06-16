<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

header('Content-Type: application/json; charset=UTF-8');

function json_fail(string $msg): never {
    echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function save_requests(string $arFile, array $requests): void {
    $json = json_encode(
        $requests,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );
    if (file_put_contents($arFile, $json, LOCK_EX) === false) {
        json_fail('파일 저장에 실패했습니다.');
    }
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

$action = trim((string)($_POST['action'] ?? 'create'));
$arFile = DC_DATA_DIR . '/apply_requests.json';

/* ── apply_requests.json 로드 공통 ── */
$requests = [];
if (is_file($arFile)) {
    $decoded = json_decode(file_get_contents($arFile), true);
    if (is_array($decoded)) { $requests = $decoded; }
}

/* ════════════════════════════════════
   action = set_target
   ════════════════════════════════════ */
if ($action === 'set_target') {
    $requestId       = trim((string)($_POST['request_id']        ?? ''));
    $targetProjectId = trim((string)($_POST['target_project_id'] ?? ''));

    if ($requestId === '' || $targetProjectId === '') {
        json_fail('request_id와 target_project_id는 필수입니다.');
    }

    /* projects.json에서 유효한 ID인지 확인 */
    $projFile = DC_DATA_DIR . '/projects.json';
    if (!is_file($projFile)) {
        json_fail('프로젝트 데이터를 찾을 수 없습니다.');
    }
    $projects = json_decode(file_get_contents($projFile), true);
    if (!is_array($projects)) {
        json_fail('프로젝트 데이터를 읽을 수 없습니다.');
    }
    $validIds = array_column($projects, 'id');
    if (!in_array($targetProjectId, $validIds, true)) {
        json_fail('유효하지 않은 프로젝트 ID입니다.');
    }

    /* request_id로 항목 찾아 업데이트 */
    $found = false;
    foreach ($requests as &$r) {
        if (($r['id'] ?? '') === $requestId) {
            $r['target_project_id'] = $targetProjectId;
            $found = true;
            break;
        }
    }
    unset($r);

    if (!$found) {
        json_fail('존재하지 않는 요청 ID입니다.');
    }

    save_requests($arFile, $requests);

    echo json_encode(
        ['ok' => true, 'message' => '대상 프로젝트를 저장했습니다.'],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

/* ════════════════════════════════════
   action = generate_prompt
   ════════════════════════════════════ */
if ($action === 'generate_prompt') {
    $requestId = trim((string)($_POST['request_id'] ?? ''));
    if ($requestId === '') { json_fail('request_id는 필수입니다.'); }

    /* 요청 찾기 */
    $found = false;
    $idx   = -1;
    foreach ($requests as $i => $r) {
        if (($r['id'] ?? '') === $requestId) { $found = true; $idx = $i; break; }
    }
    if (!$found) { json_fail('존재하지 않는 요청 ID입니다.'); }

    $req      = $requests[$idx];
    $recordId = $req['record_id'] ?? '';
    $targetId = $req['target_project_id'] ?? '';
    if ($recordId === '') { json_fail('record_id가 없습니다.'); }
    if ($targetId === '') { json_fail('대상 프로젝트가 지정되지 않았습니다.'); }

    /* record 로드 */
    $recFile = DC_DATA_DIR . '/records/' . basename($recordId) . '.json';
    if (!is_file($recFile)) { json_fail('기능 기록 파일을 찾을 수 없습니다.'); }
    $rec = json_decode(file_get_contents($recFile), true);
    if (!is_array($rec)) { json_fail('기능 기록 파일을 읽을 수 없습니다.'); }

    /* 대상 프로젝트 로드 */
    $projFile = DC_DATA_DIR . '/projects.json';
    if (!is_file($projFile)) { json_fail('프로젝트 데이터를 찾을 수 없습니다.'); }
    $projects = json_decode(file_get_contents($projFile), true);
    if (!is_array($projects)) { json_fail('프로젝트 데이터를 읽을 수 없습니다.'); }
    $proj = null;
    foreach ($projects as $p) {
        if (($p['id'] ?? '') === $targetId) { $proj = $p; break; }
    }
    if ($proj === null) { json_fail('대상 프로젝트를 프로젝트 목록에서 찾을 수 없습니다.'); }

    /* adapt_notes 결정 */
    $adaptNotes = $rec['adapt_notes'][$proj['type'] ?? ''] ?? '대상 프로젝트 구조에 맞춰 최소 변경으로 적용하세요.';

    /* validation 목록 */
    $validationList = '';
    if (!empty($rec['validation']) && is_array($rec['validation'])) {
        foreach ($rec['validation'] as $v) {
            $validationList .= '  - ' . $v . "\n";
        }
    }
    $validationList .= '  - 수정된 PHP 파일마다 php -l 실행';

    /* 소스 파일 목록 */
    $filesList = '';
    if (!empty($rec['files']) && is_array($rec['files'])) {
        $filesList = implode(', ', $rec['files']);
    }

    /* 프롬프트 생성 */
    $prompt = <<<PROMPT
Task
  Apply feature package "{$rec['title']}" to project "{$proj['name']}".

Feature source
  - Record ID: {$rec['id']}
  - Original project: {$rec['project_id']}
  - Category: {$rec['category']}
  - Summary: {$rec['summary']}
  - Source files: {$filesList}

Implementation spec
{$rec['prompt']}

Target project
  - ID: {$proj['id']}
  - Name: {$proj['name']}
  - Type: {$proj['type']}
  - Path: {$proj['path']}
  - URL: {$proj['url']}

Adaptation notes
  {$adaptNotes}

Reuse caution
  {$rec['reuse_notes']}

Constraints
  - Do not copy code blindly from the original project.
  - Do not change authentication/login logic.
  - Do not install external libraries without explicit user approval.
  - Keep existing file naming and coding conventions of the target project.
  - Keep the final diff focused on this feature only.

Validation
{$validationList}

When done
  - Summarize in Korean: what changed, which files changed, why it changed.
  - Say what validation passed.
PROMPT;

    $requests[$idx]['generated_prompt'] = $prompt;
    $requests[$idx]['status']           = 'pending';
    save_requests($arFile, $requests);

    echo json_encode(
        ['ok' => true, 'message' => 'Claude 프롬프트를 생성했습니다.', 'generated_prompt' => $prompt],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

/* ════════════════════════════════════
   action = create (기본)
   ════════════════════════════════════ */
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
    'generated_prompt' => '',
    'codex_checklist'  => '',
    'resolved_at'      => null,
];

save_requests($arFile, $requests);

echo json_encode(
    ['ok' => true, 'message' => '반영 요청 초안을 만들었습니다.'],
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);
