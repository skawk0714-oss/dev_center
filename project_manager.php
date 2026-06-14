<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$projectsFile = DC_DATA_DIR . '/projects.json';

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function pmCsrfToken(): string {
    if (empty($_SESSION['dc_project_manager_csrf'])) {
        $_SESSION['dc_project_manager_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['dc_project_manager_csrf'];
}

/** 페이지 렌더링용 — 파싱 실패 시 빈 배열 반환 */
function loadProjects(string $file): array {
    if (!is_file($file)) return [];
    $decoded = json_decode(file_get_contents($file), true);
    return is_array($decoded) ? $decoded : [];
}

/** 쓰기 전용 — 파일 없음/파싱 실패 시 null 반환하여 덮어쓰기 차단 */
function loadProjectsStrict(string $file): ?array {
    if (!is_file($file)) return null;
    $raw     = file_get_contents($file);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) return null;
    return $decoded;
}

// ── POST: 메모 저장 ──────────────────────────────────────────
$saveResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_memo') {
    header('Content-Type: application/json; charset=utf-8');

    // CSRF 검증
    $sessionToken = (string)($_SESSION['dc_project_manager_csrf'] ?? '');
    $postToken    = (string)($_POST['csrf_token'] ?? '');
    if ($sessionToken === '' || !hash_equals($sessionToken, $postToken)) {
        echo json_encode(['ok' => false, 'msg' => 'CSRF 검증 실패. 페이지를 새로고침 후 다시 시도하세요.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $postId   = (string)($_POST['project_id'] ?? '');
    $postMemo = (string)($_POST['memo']        ?? '');

    // strict 로더 — JSON 파싱 실패 시 저장 거부
    $projects = loadProjectsStrict($projectsFile);
    if ($projects === null) {
        echo json_encode(['ok' => false, 'msg' => 'projects.json을 읽을 수 없거나 형식이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // project_id 존재 확인
    $found = false;
    foreach ($projects as &$p) {
        if (($p['id'] ?? '') === $postId) {
            $p['memo'] = $postMemo;
            $found = true;
            break;
        }
    }
    unset($p);

    if (!$found) {
        echo json_encode(['ok' => false, 'msg' => '프로젝트를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $json = json_encode($projects, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        echo json_encode(['ok' => false, 'msg' => 'JSON 인코딩 실패.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (file_put_contents($projectsFile, $json, LOCK_EX) === false) {
        echo json_encode(['ok' => false, 'msg' => '파일 저장에 실패했습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => true, 'msg' => '메모가 저장됐습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 경로 정규화 유틸 (미존재 경로도 처리) ────────────────────
function normPath(string $p): string {
    return rtrim(str_replace('\\', '/', $p), '/');
}

/** 존재하지 않는 경로도 허용 루트 확인 — ../ 포함 시 무조건 거부 */
function isAllowedNewPath(string $path): bool {
    $norm = normPath($path);
    // ../ 세그먼트 거부
    if (preg_match('#(^|/)\.\.(/|$)#', $norm)) return false;
    foreach (DEV_ALLOWED_ROOTS as $root) {
        $nr = normPath($root);
        if (str_starts_with($norm . '/', $nr . '/')) return true;
    }
    return false;
}

/** 템플릿 파일 읽기, 없으면 빈 문자열 */
function readTpl(string $name): string {
    $f = __DIR__ . '/templates/instructions/' . $name . '.md';
    return is_file($f) ? file_get_contents($f) : '';
}

/** 플레이스홀더 치환 */
function fillTpl(string $tpl, array $vars): string {
    foreach ($vars as $k => $v) {
        $tpl = str_replace('{{' . $k . '}}', $v, $tpl);
    }
    return $tpl;
}

/** 파일이 없을 때만 생성 */
function writeIfNew(string $path, string $content): void {
    if (!is_file($path)) {
        file_put_contents($path, $content);
    }
}

// ── POST: 프로젝트 생성 ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_project') {
    header('Content-Type: application/json; charset=utf-8');

    // CSRF
    $sessionToken = (string)($_SESSION['dc_project_manager_csrf'] ?? '');
    $postToken    = (string)($_POST['csrf_token'] ?? '');
    if ($sessionToken === '' || !hash_equals($sessionToken, $postToken)) {
        echo json_encode(['ok' => false, 'msg' => 'CSRF 검증 실패.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 필수 필드 수집
    $newName       = trim((string)($_POST['project_name']  ?? ''));
    $newId         = trim((string)($_POST['project_id']    ?? ''));
    $newType       = trim((string)($_POST['project_type']  ?? ''));
    $newPath       = trim((string)($_POST['project_path']  ?? ''));
    $newUrl        = trim((string)($_POST['project_url']   ?? ''));
    $newPurpose    = trim((string)($_POST['purpose']       ?? ''));
    $newAudience   = trim((string)($_POST['audience']      ?? ''));
    $newGoal       = trim((string)($_POST['commercial_goal'] ?? ''));
    $newRisk       = trim((string)($_POST['risk_notes']    ?? ''));
    // 선택 필드
    $newDesc       = trim((string)($_POST['description']   ?? ''));
    $newTask       = trim((string)($_POST['initial_task']  ?? ''));
    $newStack      = trim((string)($_POST['tech_stack']    ?? ''));
    $createFiles   = !empty($_POST['create_instructions']);
    $regExisting   = !empty($_POST['register_existing_folder']);

    // 허용 타입
    $allowedTypes  = ['php-erp','php-local-tool','laravel-saas','powershell-automation','static-design','research','other'];

    // 필수 항목 검사
    if ($newName === '')    { echo json_encode(['ok'=>false,'msg'=>'프로젝트 이름을 입력하세요.'],JSON_UNESCAPED_UNICODE); exit; }
    if ($newPath === '')    { echo json_encode(['ok'=>false,'msg'=>'프로젝트 경로를 입력하세요.'],JSON_UNESCAPED_UNICODE); exit; }
    if ($newPurpose === '') { echo json_encode(['ok'=>false,'msg'=>'목적을 입력하세요.'],JSON_UNESCAPED_UNICODE); exit; }
    if ($newAudience==='') { echo json_encode(['ok'=>false,'msg'=>'대상 사용자를 입력하세요.'],JSON_UNESCAPED_UNICODE); exit; }
    if ($newGoal === '')   { echo json_encode(['ok'=>false,'msg'=>'상업적 목표를 입력하세요.'],JSON_UNESCAPED_UNICODE); exit; }
    if ($newRisk === '')   { echo json_encode(['ok'=>false,'msg'=>'리스크/주의사항을 입력하세요.'],JSON_UNESCAPED_UNICODE); exit; }

    // project_id 형식 검사
    if (!preg_match('/^[0-9]{6}-[a-z0-9-]+$/', $newId)) {
        echo json_encode(['ok'=>false,'msg'=>'프로젝트 ID 형식이 올바르지 않습니다. (예: 260615-my-project)'],JSON_UNESCAPED_UNICODE); exit;
    }

    // 타입 검사
    if (!in_array($newType, $allowedTypes, true)) {
        echo json_encode(['ok'=>false,'msg'=>'허용되지 않은 프로젝트 타입입니다.'],JSON_UNESCAPED_UNICODE); exit;
    }

    // 경로 허용 검사
    if (!isAllowedNewPath($newPath)) {
        echo json_encode(['ok'=>false,'msg'=>'허용된 루트 밖의 경로이거나 ../ 가 포함되어 있습니다.'],JSON_UNESCAPED_UNICODE); exit;
    }

    // 기존 projects.json strict 로드
    $existingProjects = loadProjectsStrict($projectsFile);
    if ($existingProjects === null) {
        echo json_encode(['ok'=>false,'msg'=>'projects.json을 읽을 수 없습니다.'],JSON_UNESCAPED_UNICODE); exit;
    }

    // 중복 ID 검사
    foreach ($existingProjects as $ep) {
        if (($ep['id'] ?? '') === $newId) {
            echo json_encode(['ok'=>false,'msg'=>"프로젝트 ID '$newId'가 이미 존재합니다."],JSON_UNESCAPED_UNICODE); exit;
        }
    }

    // 폴더 처리
    $nativePath = normPath($newPath);
    if (is_dir($nativePath)) {
        // 폴더가 존재함 — 비어있는지 확인
        $contents = array_diff(scandir($nativePath), ['.', '..']);
        if (count($contents) > 0 && !$regExisting) {
            echo json_encode(['ok'=>false,'msg'=>'폴더가 비어있지 않습니다. "기존 폴더 등록"을 체크하면 파일을 덮어쓰지 않고 등록만 합니다.'],JSON_UNESCAPED_UNICODE); exit;
        }
        // 비어있거나 register_existing_folder 체크됨 → 진행
    } else {
        // 폴더 생성
        if (!mkdir($nativePath, 0755, true)) {
            echo json_encode(['ok'=>false,'msg'=>'폴더를 생성할 수 없습니다: ' . $nativePath],JSON_UNESCAPED_UNICODE); exit;
        }
    }

    // 플레이스홀더 맵
    $vars = [
        'PROJECT_NAME'    => $newName,
        'PURPOSE'         => $newPurpose,
        'AUDIENCE'        => $newAudience,
        'TECH_STACK'      => $newStack ?: '미정',
        'COMMERCIAL_GOAL' => $newGoal,
        'RISK_NOTES'      => $newRisk,
        'PROJECT_PATH'    => $nativePath,
        'PROJECT_TYPE'    => $newType,
        'INITIAL_TASK'    => $newTask ?: '미정',
        'DATE'            => date('Y-m-d'),
    ];

    // 인스트럭션 파일 생성
    if ($createFiles) {
        $typeSlug = in_array($newType, $allowedTypes, true) ? $newType : 'other';
        $agentsMd = fillTpl(readTpl('base'), $vars) . "\n\n" . fillTpl(readTpl($typeSlug), $vars);
        writeIfNew($nativePath . '/AGENTS.md', $agentsMd);

        $currentTask = "# CURRENT_TASK.md — {$newName}\n\n"
            . "## 목표\n{$newTask}\n\n"
            . "## 작업 범위\n(여기에 작성)\n\n"
            . "## 제외 범위\n(여기에 작성)\n\n"
            . "## 완료 조건\n(여기에 작성)\n";
        writeIfNew($nativePath . '/CURRENT_TASK.md', $currentTask);

        $arch = "# ARCHITECTURE.md — {$newName}\n\n"
            . "## 기술 스택\n{$newStack}\n\n"
            . "## 폴더 구조\n(여기에 작성)\n\n"
            . "## 주요 파일\n(여기에 작성)\n";
        writeIfNew($nativePath . '/ARCHITECTURE.md', $arch);

        $readme = "# {$newName}\n\n{$newDesc}\n\n"
            . "## 목적\n{$newPurpose}\n\n"
            . "## 대상\n{$newAudience}\n\n"
            . "## 실행 방법\n(여기에 작성)\n";
        writeIfNew($nativePath . '/README.md', $readme);

        if (!is_dir($nativePath . '/docs')) { mkdir($nativePath . '/docs', 0755, true); }
        $roadmap = "# ROADMAP — {$newName}\n\n"
            . "## 상업적 목표\n{$newGoal}\n\n"
            . "## 단계별 계획\n- [ ] {$newTask}\n";
        writeIfNew($nativePath . '/docs/ROADMAP.md', $roadmap);
    }

    // setup_prompt 생성
    $setupPrompt = "## {$newName} 작업 시작 프롬프트\n\n"
        . "프로젝트 경로: `{$nativePath}`\n"
        . "프로젝트 목적: {$newPurpose}\n"
        . "대상 사용자: {$newAudience}\n"
        . "기술 스택: " . ($newStack ?: '미정') . "\n"
        . "현재 작업: " . ($newTask ?: '없음') . "\n\n"
        . "### 규칙\n"
        . "1. 작업 전에 반드시 `AGENTS.md`를 먼저 읽어라.\n"
        . "2. 이 프로젝트 폴더(`{$nativePath}`) 밖은 절대 건드리지 않는다.\n"
        . "3. 모든 변경 전 CURRENT_TASK.md의 작업 범위를 확인한다.\n"
        . "4. 완료 전 php -l 또는 해당 언어 문법 검사를 실행한다.\n";

    // 새 프로젝트 레코드
    $newProject = [
        'id'              => $newId,
        'date'            => date('Y-m-d'),
        'name'            => $newName,
        'type'            => $newType,
        'path'            => $nativePath,
        'url'             => $newUrl,
        'status'          => 'active',
        'description'     => $newDesc,
        'purpose'         => $newPurpose,
        'audience'        => $newAudience,
        'commercial_goal' => $newGoal,
        'risk_notes'      => $newRisk,
        'tech_stack'      => $newStack,
        'initial_task'    => $newTask,
        'commands'        => ['vscode' => true, 'codex' => true, 'claude' => true],
        'memo'            => '',
        'setup_prompt'    => $setupPrompt,
    ];

    $existingProjects[] = $newProject;
    $jsonOut = json_encode($existingProjects, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonOut === false) {
        echo json_encode(['ok'=>false,'msg'=>'JSON 인코딩 실패.'],JSON_UNESCAPED_UNICODE); exit;
    }
    if (file_put_contents($projectsFile, $jsonOut, LOCK_EX) === false) {
        echo json_encode(['ok'=>false,'msg'=>'projects.json 저장 실패.'],JSON_UNESCAPED_UNICODE); exit;
    }

    echo json_encode(['ok'=>true,'msg'=>'프로젝트가 생성됐습니다.','redirect'=>'project_detail.php?id='.urlencode($newId)],JSON_UNESCAPED_UNICODE);
    exit;
}

$projects = loadProjects($projectsFile);

$statusLabel = [
    'active'   => ['label' => '진행 중', 'class' => 'badge-active'],
    'paused'   => ['label' => '보류',    'class' => 'badge-paused'],
    'archived' => ['label' => '보관',    'class' => 'badge-archived'],
];

$pmCsrfToken = pmCsrfToken();

// JS에 넘길 프로젝트 데이터 (메모만 필요)
$projectsForJs = [];
foreach ($projects as $p) {
    $projectsForJs[$p['id'] ?? ''] = $p['memo'] ?? '';
}
$projectsJson = json_encode($projectsForJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>프로젝트 관리 — 개발센터</title>
  <link rel="stylesheet" href="assets/css/dev_center.css">
  <style>
    .pm-table { width:100%; border-collapse:collapse; }
    .pm-table th,
    .pm-table td { padding:10px 14px; text-align:left; border-bottom:1px solid var(--border); font-size:13px; }
    .pm-table th { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text3); background:var(--bg2); }
    .pm-table tr:hover td { background:var(--bg3); }
    .pm-path { font-family:monospace; font-size:12px; color:var(--text2); word-break:break-all; }
    .badge-active   { background:rgba(46,164,79,.15);  color:#2ea44f; border:1px solid rgba(46,164,79,.3);  }
    .badge-paused   { background:rgba(210,153,34,.15); color:#d29922; border:1px solid rgba(210,153,34,.3); }
    .badge-archived { background:rgba(110,118,129,.15);color:#8b949e; border:1px solid rgba(110,118,129,.3);}
    .status-badge { font-size:11px; font-weight:600; padding:2px 8px; border-radius:10px; white-space:nowrap; }
    .btn-detail { font-size:12px; padding:4px 12px; background:var(--bg3); border:1px solid var(--border); color:var(--text); border-radius:6px; cursor:pointer; text-decoration:none; }
    .btn-detail:hover { border-color:var(--accent); color:var(--accent); }
    .empty-msg { color:var(--text3); font-size:13px; padding:24px 14px; }

    /* ── 프로젝트명 + 팝오버 ── */
    .pm-name-wrap { position:relative; display:inline-block; }
    .pm-name-btn {
      background:none; border:none; color:var(--text); font-weight:700;
      font-size:13px; cursor:pointer; padding:0; text-align:left;
      font-family:inherit;
    }
    .pm-name-btn:hover { color:var(--accent); }

    .pm-popover {
      display:none;
      position:absolute;
      top:calc(100% + 6px);
      left:0;
      z-index:200;
      min-width:240px;
      max-width:320px;
      background:var(--bg2);
      border:1px solid var(--border);
      border-radius:8px;
      padding:14px;
      box-shadow:0 8px 24px rgba(0,0,0,.5);
      font-size:13px;
    }
    .pm-popover.open { display:block; }

    .pop-memo-text { color:var(--text2); margin-bottom:10px; min-height:18px; white-space:pre-wrap; word-break:break-word; }
    .pop-memo-text.empty { color:var(--text3); font-style:italic; }

    .pop-actions { display:flex; gap:8px; }
    .btn-pop { font-size:12px; padding:4px 10px; border-radius:5px; cursor:pointer; font-family:inherit; }
    .btn-pop-edit  { background:var(--bg3); border:1px solid var(--border); color:var(--text); }
    .btn-pop-edit:hover { border-color:var(--accent); color:var(--accent); }
    .btn-pop-save  { background:var(--green); border:1px solid var(--green); color:#fff; }
    .btn-pop-save:hover { opacity:.85; }
    .btn-pop-cancel{ background:var(--bg3); border:1px solid var(--border); color:var(--text2); }
    .btn-pop-cancel:hover { border-color:var(--text2); }

    .pop-edit-area { display:none; }
    .pop-edit-area.open { display:block; }
    .pop-textarea {
      width:100%; box-sizing:border-box;
      background:var(--bg3); border:1px solid var(--border); color:var(--text);
      border-radius:5px; padding:7px 9px; font-size:12px; font-family:inherit;
      resize:vertical; min-height:72px; margin-bottom:8px;
    }
    .pop-textarea:focus { outline:none; border-color:var(--accent); }
    .pop-save-msg { font-size:11px; margin-top:6px; }
    .pop-save-msg.ok  { color:#3fb950; }
    .pop-save-msg.err { color:#ff7b72; }

    @media (max-width:390px) {
      .pm-table th:nth-child(3),
      .pm-table td:nth-child(3),
      .pm-table th:nth-child(4),
      .pm-table td:nth-child(4) { display:none; }
      .pm-popover { min-width:200px; max-width:calc(100vw - 32px); left:auto; right:0; }
    }

    /* ── New Project button ── */
    .btn-new-project {
      background:var(--accent2); border:1px solid var(--accent); color:#fff;
      font-size:13px; font-weight:600; padding:8px 18px; border-radius:6px;
      cursor:pointer; white-space:nowrap; font-family:inherit;
      transition:opacity .15s;
    }
    .btn-new-project:hover { opacity:.85; }

    /* ── Modal overlay ── */
    .np-overlay {
      display:none; position:fixed; inset:0; z-index:500;
      background:rgba(0,0,0,.65); align-items:flex-start; justify-content:center;
      padding:32px 16px; overflow-y:auto;
    }
    .np-overlay.open { display:flex; }
    .np-modal {
      background:var(--bg2); border:1px solid var(--border); border-radius:10px;
      width:100%; max-width:640px; padding:28px 28px 24px;
      box-shadow:0 16px 48px rgba(0,0,0,.6);
    }
    .np-modal h2 { font-size:17px; font-weight:700; margin-bottom:20px; color:var(--text); }
    .np-section { font-size:11px; font-weight:700; color:var(--text3);
      text-transform:uppercase; letter-spacing:.06em; margin:18px 0 10px; }
    .np-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .np-field { display:flex; flex-direction:column; gap:5px; margin-bottom:12px; }
    .np-field label { font-size:12px; font-weight:600; color:var(--text2); }
    .np-field input, .np-field select, .np-field textarea {
      background:var(--bg3); border:1px solid var(--border); color:var(--text);
      border-radius:5px; padding:7px 10px; font-size:13px; font-family:inherit;
    }
    .np-field input:focus, .np-field select:focus, .np-field textarea:focus {
      outline:none; border-color:var(--accent);
    }
    .np-field textarea { resize:vertical; min-height:56px; }
    .np-field select option { background:var(--bg2); }
    .np-hint { font-size:11px; color:var(--text3); margin-top:2px; }
    .np-check { display:flex; align-items:center; gap:8px; font-size:13px; margin-bottom:8px; }
    .np-check input[type=checkbox] { width:15px; height:15px; cursor:pointer; accent-color:var(--accent); }
    .np-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; padding-top:16px; border-top:1px solid var(--border); }
    .btn-np-submit { background:var(--green); border:1px solid var(--green); color:#fff;
      font-size:13px; font-weight:600; padding:8px 22px; border-radius:6px; cursor:pointer; font-family:inherit; }
    .btn-np-submit:hover { opacity:.85; }
    .btn-np-cancel { background:var(--bg3); border:1px solid var(--border); color:var(--text2);
      font-size:13px; padding:8px 16px; border-radius:6px; cursor:pointer; font-family:inherit; }
    .btn-np-cancel:hover { border-color:var(--text2); }
    .np-err { font-size:13px; color:#ff7b72; margin-top:10px; min-height:20px; }
    .np-ok  { font-size:13px; color:#3fb950; margin-top:10px; }
    .np-field .req { color:#ff7b72; margin-left:2px; }
    @media (max-width:520px) { .np-row { grid-template-columns:1fr; } .np-modal { padding:20px 16px; } }
  </style>
</head>
<body>

<nav class="dc-topnav">
  <a href="index.php" class="dc-brand">🛠️ 개발센터 <span class="dc-brand-badge">DEV</span></a>
  <ul class="dc-nav-links">
    <li><a href="index.php">홈</a></li>
    <li><a href="project_manager.php" class="active">프로젝트 관리</a></li>
    <li><a href="knowledge.php">기능 보관함</a></li>
    <li><a href="lab.php">실험실</a></li>
    <li><a href="prompts.php">프롬프트</a></li>
    <li><a href="settings.php">설정</a></li>
  </ul>
  <div class="dc-topnav-right"><span class="dc-badge-env">LOCAL</span></div>
</nav>

<main class="dc-main">
  <div class="dc-hero" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div>
      <h1>프로젝트 관리</h1>
      <p>등록된 프로젝트 목록입니다. 프로젝트명을 클릭하면 메모를 확인하거나 수정할 수 있습니다.</p>
    </div>
    <button type="button" class="btn-new-project" onclick="openNewModal()">+ 새 프로젝트</button>
  </div>

  <?php if (empty($projects)): ?>
    <div class="empty-msg">등록된 프로젝트가 없습니다. <code>data/projects.json</code>을 확인하세요.</div>
  <?php else: ?>
  <table class="pm-table">
    <thead>
      <tr>
        <th>날짜</th>
        <th>프로젝트</th>
        <th>타입</th>
        <th>경로</th>
        <th>상태</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($projects as $p): ?>
        <?php
          $sid     = $statusLabel[$p['status'] ?? ''] ?? ['label' => e($p['status'] ?? '-'), 'class' => 'badge-archived'];
          $dateStr = e($p['date'] ?? '—');
          $pid     = e($p['id'] ?? '');
          $memo    = $p['memo'] ?? '';
        ?>
        <tr>
          <td><?= $dateStr ?></td>
          <td>
            <div class="pm-name-wrap" data-pid="<?= $pid ?>">
              <button type="button" class="pm-name-btn" onclick="togglePop(this)">
                <?= e($p['name'] ?? '') ?>
              </button>
              <div class="pm-popover" id="pop-<?= $pid ?>">
                <div class="pop-view">
                  <div class="pop-memo-text <?= $memo === '' ? 'empty' : '' ?>"
                       id="pop-text-<?= $pid ?>"><?= $memo === '' ? '메모 없음' : e($memo) ?></div>
                  <div class="pop-actions">
                    <button type="button" class="btn-pop btn-pop-edit"
                            onclick="openEdit('<?= $pid ?>')">메모 수정</button>
                  </div>
                </div>
                <div class="pop-edit-area" id="pop-edit-<?= $pid ?>">
                  <textarea class="pop-textarea" id="pop-ta-<?= $pid ?>"><?= e($memo) ?></textarea>
                  <div class="pop-actions">
                    <button type="button" class="btn-pop btn-pop-save"
                            onclick="saveMemo('<?= $pid ?>')">저장</button>
                    <button type="button" class="btn-pop btn-pop-cancel"
                            onclick="cancelEdit('<?= $pid ?>')">취소</button>
                  </div>
                  <div class="pop-save-msg" id="pop-msg-<?= $pid ?>"></div>
                </div>
              </div>
            </div>
          </td>
          <td><?= e($p['type'] ?? '') ?></td>
          <td class="pm-path"><?= e($p['path'] ?? '') ?></td>
          <td><span class="status-badge <?= $sid['class'] ?>"><?= $sid['label'] ?></span></td>
          <td><a href="project_detail.php?id=<?= urlencode($p['id'] ?? '') ?>" class="btn-detail">상세 보기</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <footer class="dc-footer">
    <?= e(DEV_CENTER_NAME) ?> v<?= e(DEV_CENTER_VERSION) ?> &mdash; 로컬 개발 전용.
  </footer>
</main>

<!-- ── New Project Modal ── -->
<div class="np-overlay" id="np-overlay" onclick="closeNewModalOutside(event)">
 <div class="np-modal" role="dialog" aria-modal="true" aria-labelledby="np-title">
  <h2 id="np-title">+ 새 프로젝트 생성</h2>

  <div class="np-section">기본 정보</div>
  <div class="np-row">
    <div class="np-field">
      <label>프로젝트 이름 <span class="req">*</span></label>
      <input type="text" id="np-name" placeholder="My Project" oninput="suggestId()">
    </div>
    <div class="np-field">
      <label>프로젝트 ID <span class="req">*</span></label>
      <input type="text" id="np-id" placeholder="260615-my-project">
      <span class="np-hint">형식: YYMMDD-소문자-숫자-하이픈</span>
    </div>
  </div>
  <div class="np-row">
    <div class="np-field">
      <label>타입 <span class="req">*</span></label>
      <select id="np-type">
        <option value="php-erp">PHP ERP</option>
        <option value="php-local-tool">PHP 로컬 도구</option>
        <option value="laravel-saas">Laravel SaaS</option>
        <option value="powershell-automation">PowerShell 자동화</option>
        <option value="static-design">정적/디자인</option>
        <option value="research">리서치</option>
        <option value="other">기타</option>
      </select>
    </div>
    <div class="np-field">
      <label>프로젝트 URL</label>
      <input type="text" id="np-url" placeholder="http://localhost/my-project">
    </div>
  </div>
  <div class="np-field">
    <label>프로젝트 경로 <span class="req">*</span></label>
    <input type="text" id="np-path" placeholder="C:/xampp/htdocs/my-project">
    <span class="np-hint">허용 루트: <?= implode(', ', array_map('htmlspecialchars', DEV_ALLOWED_ROOTS)) ?></span>
  </div>

  <div class="np-section">프로젝트 배경</div>
  <div class="np-field">
    <label>목적 <span class="req">*</span></label>
    <textarea id="np-purpose" rows="2" placeholder="이 프로젝트가 해결하려는 문제"></textarea>
  </div>
  <div class="np-row">
    <div class="np-field">
      <label>대상 사용자 <span class="req">*</span></label>
      <input type="text" id="np-audience" placeholder="사내 직원, 일반 사용자 등">
    </div>
    <div class="np-field">
      <label>상업적 목표 <span class="req">*</span></label>
      <input type="text" id="np-goal" placeholder="SaaS 월 구독, 내부 효율화 등">
    </div>
  </div>
  <div class="np-field">
    <label>리스크/주의사항 <span class="req">*</span></label>
    <input type="text" id="np-risk" placeholder="개인정보 처리, 외부 API 의존 등">
  </div>

  <div class="np-section">선택 항목</div>
  <div class="np-field">
    <label>설명 (한 줄)</label>
    <input type="text" id="np-desc" placeholder="프로젝트 한 줄 설명">
  </div>
  <div class="np-row">
    <div class="np-field">
      <label>기술 스택</label>
      <input type="text" id="np-stack" placeholder="PHP 8.x, MySQL, vanilla JS">
    </div>
    <div class="np-field">
      <label>첫 번째 작업</label>
      <input type="text" id="np-task" placeholder="로그인 페이지 만들기 등">
    </div>
  </div>
  <label class="np-check">
    <input type="checkbox" id="np-create-files" checked>
    인스트럭션 파일 자동 생성 (AGENTS.md, CURRENT_TASK.md, ARCHITECTURE.md, README.md, docs/ROADMAP.md)
  </label>
  <label class="np-check">
    <input type="checkbox" id="np-reg-existing">
    기존 폴더 등록 (폴더가 비어있지 않아도 파일을 덮어쓰지 않고 등록만 함)
  </label>

  <div class="np-err" id="np-err"></div>
  <div class="np-footer">
    <button type="button" class="btn-np-cancel" onclick="closeNewModal()">취소</button>
    <button type="button" class="btn-np-submit" id="np-submit" onclick="submitNewProject()">프로젝트 생성</button>
  </div>
 </div>
</div>

<script>
const MEMOS     = <?= $projectsJson ?>;
const PM_CSRF   = <?= json_encode($pmCsrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

// ── New Project Modal ────────────────────────────────────────
function openNewModal() {
    document.getElementById('np-overlay').classList.add('open');
    document.getElementById('np-name').focus();
}
function closeNewModal() {
    document.getElementById('np-overlay').classList.remove('open');
    document.getElementById('np-err').textContent = '';
}
function closeNewModalOutside(e) {
    if (e.target === document.getElementById('np-overlay')) closeNewModal();
}

function slugify(s) {
    return s.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .substring(0, 30);
}

function suggestId() {
    const name = document.getElementById('np-name').value;
    const today = new Date();
    const yy = String(today.getFullYear()).slice(2);
    const mm = String(today.getMonth()+1).padStart(2,'0');
    const dd = String(today.getDate()).padStart(2,'0');
    const slug = slugify(name);
    if (slug) document.getElementById('np-id').value = yy+mm+dd+'-'+slug;
}

async function submitNewProject() {
    const btn = document.getElementById('np-submit');
    const errEl = document.getElementById('np-err');
    errEl.textContent = '';
    btn.disabled = true;
    btn.textContent = '생성 중…';

    const fd = new FormData();
    fd.append('action',                   'create_project');
    fd.append('csrf_token',               PM_CSRF);
    fd.append('project_name',             document.getElementById('np-name').value);
    fd.append('project_id',               document.getElementById('np-id').value);
    fd.append('project_type',             document.getElementById('np-type').value);
    fd.append('project_path',             document.getElementById('np-path').value);
    fd.append('project_url',              document.getElementById('np-url').value);
    fd.append('purpose',                  document.getElementById('np-purpose').value);
    fd.append('audience',                 document.getElementById('np-audience').value);
    fd.append('commercial_goal',          document.getElementById('np-goal').value);
    fd.append('risk_notes',               document.getElementById('np-risk').value);
    fd.append('description',              document.getElementById('np-desc').value);
    fd.append('tech_stack',               document.getElementById('np-stack').value);
    fd.append('initial_task',             document.getElementById('np-task').value);
    if (document.getElementById('np-create-files').checked)   fd.append('create_instructions', '1');
    if (document.getElementById('np-reg-existing').checked)   fd.append('register_existing_folder', '1');

    try {
        const res  = await fetch('project_manager.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.ok) {
            closeNewModal();
            if (data.redirect) window.location.href = data.redirect;
            else               window.location.reload();
        } else {
            errEl.textContent = data.msg || '생성 실패';
        }
    } catch(err) {
        errEl.textContent = '네트워크 오류';
    } finally {
        btn.disabled = false;
        btn.textContent = '프로젝트 생성';
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeNewModal();
});
let editing   = null; // 현재 편집 중인 pid
let hoverOpen = null; // 마우스 호버로 열린 pid

function getWrap(pid)  { return document.querySelector(`.pm-name-wrap[data-pid="${pid}"]`); }
function getPop(pid)   { return document.getElementById(`pop-${pid}`); }
function getView(pid)  { return getPop(pid)?.querySelector('.pop-view'); }
function getEdit(pid)  { return document.getElementById(`pop-edit-${pid}`); }
function getTa(pid)    { return document.getElementById(`pop-ta-${pid}`); }
function getMsg(pid)   { return document.getElementById(`pop-msg-${pid}`); }
function getText(pid)  { return document.getElementById(`pop-text-${pid}`); }

function closeAll(exceptPid) {
    document.querySelectorAll('.pm-popover.open').forEach(el => {
        const pid = el.id.replace('pop-', '');
        if (pid !== exceptPid) closePop(pid);
    });
}

function openPop(pid) {
    closeAll(pid);
    getPop(pid)?.classList.add('open');
}

function closePop(pid) {
    getPop(pid)?.classList.remove('open');
    cancelEdit(pid, true);
}

function togglePop(btn) {
    const pid = btn.closest('.pm-name-wrap').dataset.pid;
    const pop = getPop(pid);
    if (pop.classList.contains('open')) { closePop(pid); }
    else { openPop(pid); }
}

// 마우스 호버
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.pm-name-wrap').forEach(wrap => {
        const pid = wrap.dataset.pid;
        wrap.addEventListener('mouseenter', () => {
            if (editing !== pid) { openPop(pid); hoverOpen = pid; }
        });
        wrap.addEventListener('mouseleave', () => {
            if (editing !== pid) { closePop(pid); hoverOpen = null; }
        });
    });

    // 팝오버 위로 마우스가 들어오면 닫히지 않게
    document.querySelectorAll('.pm-popover').forEach(pop => {
        pop.addEventListener('mouseenter', () => { /* keep open */ });
        pop.addEventListener('mouseleave', e => {
            const pid = pop.id.replace('pop-', '');
            if (editing !== pid) { closePop(pid); hoverOpen = null; }
        });
    });

    // 팝오버 바깥 클릭 시 닫기
    document.addEventListener('click', e => {
        if (!e.target.closest('.pm-name-wrap')) {
            document.querySelectorAll('.pm-popover.open').forEach(el => {
                closePop(el.id.replace('pop-', ''));
            });
        }
    });
});

function openEdit(pid) {
    editing = pid;
    getView(pid).style.display  = 'none';
    getEdit(pid).classList.add('open');
    getTa(pid).focus();
}

function cancelEdit(pid, silent = false) {
    if (getEdit(pid)) {
        getEdit(pid).classList.remove('open');
        getView(pid).style.display = '';
        if (getMsg(pid)) getMsg(pid).textContent = '';
    }
    if (editing === pid) editing = null;
}

function updateDisplay(pid, memo) {
    const t = getText(pid);
    if (!t) return;
    if (memo === '') {
        t.textContent = '메모 없음';
        t.classList.add('empty');
    } else {
        t.textContent = memo;
        t.classList.remove('empty');
    }
    MEMOS[pid] = memo;
}

async function saveMemo(pid) {
    const memo = getTa(pid).value;
    const msg  = getMsg(pid);
    msg.textContent = '저장 중…';
    msg.className   = 'pop-save-msg';

    const fd = new FormData();
    fd.append('action',     'save_memo');
    fd.append('project_id', pid);
    fd.append('memo',       memo);
    fd.append('csrf_token', PM_CSRF);

    try {
        const res  = await fetch('project_manager.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.ok) {
            msg.textContent = '✅ 저장됐습니다.';
            msg.classList.add('ok');
            updateDisplay(pid, memo);
            setTimeout(() => cancelEdit(pid), 900);
        } else {
            msg.textContent = '❌ ' + (data.msg || '저장 실패');
            msg.classList.add('err');
        }
    } catch (err) {
        msg.textContent = '❌ 네트워크 오류';
        msg.classList.add('err');
    }
}
</script>
</body>
</html>
