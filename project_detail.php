<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

// 세션은 CSRF 토큰 보관용으로만 사용
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function loadRelatedRecords(string $projectId): array {
    $featuresFile = DC_DATA_DIR . '/features.json';
    if (!is_file($featuresFile)) return ['records' => [], 'skip' => 0];
    $features = json_decode(file_get_contents($featuresFile), true);
    if (!is_array($features)) return ['records' => [], 'skip' => 0];

    $related   = [];
    $skipCount = 0;
    foreach ($features as $feat) {
        $id = (string)($feat['id'] ?? '');
        if ($id === '') continue;
        $recPath = DC_DATA_DIR . '/records/' . $id . '.json';
        if (!is_file($recPath)) continue;
        $rec = json_decode(file_get_contents($recPath), true);
        if (!is_array($rec)) { $skipCount++; continue; }
        if (($rec['project_id'] ?? '') !== $projectId) continue;
        $related[] = [
            'id'      => $id,
            'title'   => (string)($rec['title']    ?? $feat['title']    ?? $id),
            'summary' => (string)($rec['summary']   ?? ''),
            'category'=> (string)($rec['category']  ?? $feat['category'] ?? ''),
            'tags'    => (array)($rec['tags'] ?? $feat['tags'] ?? []),
        ];
    }
    return ['records' => $related, 'skip' => $skipCount];
}

function loadRelatedLabExperiments(string $projectId): array {
    $labFile = DC_DATA_DIR . '/lab_experiments.json';
    if (!is_file($labFile)) return [];
    $experiments = json_decode(file_get_contents($labFile), true);
    if (!is_array($experiments)) return [];

    $related = [];
    foreach ($experiments as $exp) {
        if (!is_array($exp)) continue;
        if (($exp['project_id'] ?? '') !== $projectId) continue;
        $related[] = [
            'id'          => (string)($exp['id']           ?? ''),
            'title'       => (string)($exp['title']        ?? ''),
            'summary'     => (string)($exp['summary']      ?? ''),
            'category'    => (string)($exp['category']     ?? ''),
            'subcategory' => (string)($exp['subcategory']  ?? ''),
            'status'      => (string)($exp['status']       ?? ''),
            'completion'  => (int)($exp['completion']      ?? 0),
            'priority_score' => (int)($exp['priority_score'] ?? 0),
            'tags'        => (array)($exp['tags']          ?? []),
        ];
    }
    return $related;
}

function loadProjects(): array {
    $file = DC_DATA_DIR . '/projects.json';
    if (!is_file($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function isAllowedPath(string $path): bool {
    // realpath()로 ../ 및 심볼릭 링크를 해소. 존재하지 않는 경로는 false 반환
    $canonical = realpath($path);
    if ($canonical === false) {
        return false;
    }
    // Windows 백슬래시를 통일하고 끝 슬래시 제거
    $canonical = rtrim(str_replace('\\', '/', $canonical), '/');

    foreach (DEV_ALLOWED_ROOTS as $root) {
        $canonicalRoot = realpath($root);
        if ($canonicalRoot === false) {
            continue; // 존재하지 않는 루트는 건너뜀
        }
        $canonicalRoot = rtrim(str_replace('\\', '/', $canonicalRoot), '/');
        // 디렉터리 경계로 비교 — htdocs_bad 가 htdocs 에 매칭되지 않도록 '/' 추가
        if (str_starts_with($canonical . '/', $canonicalRoot . '/')) {
            return true;
        }
    }
    return false;
}

/** 요청이 로컬호스트에서 온 것인지 확인 */
function isLocalRequest(): bool {
    $addr = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($addr, ['127.0.0.1', '::1', '0:0:0:0:0:0:0:1'], true);
}

/** 세션에 CSRF 토큰이 없으면 생성 */
function csrfToken(): string {
    if (empty($_SESSION['dc_csrf_token'])) {
        $_SESSION['dc_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['dc_csrf_token'];
}

// ── 실행 가능 여부 플래그 ─────────────────────────────────────
$isLocal        = isLocalRequest();
$launchEnabled  = DC_LAUNCH_ENABLED && $isLocal;

$projects       = loadProjects();
$allowedActions = ['vscode', 'codex', 'claude', 'all'];
$launchResult   = null;

// ── POST: 실행 요청 ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'launch') {

    // 1) 로컬 접속 확인
    if (!$isLocal) {
        $launchResult = ['ok' => false, 'msg' => '외부 접속에서는 실행할 수 없습니다.'];
    }
    // 2) 실행 기능 활성화 확인
    elseif (!DC_LAUNCH_ENABLED) {
        $launchResult = ['ok' => false, 'msg' => '실행 기능이 비활성화되어 있습니다.'];
    }
    // 3) CSRF 검증
    elseif (!hash_equals((string)($_SESSION['dc_csrf_token'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
        $launchResult = ['ok' => false, 'msg' => 'CSRF 검증에 실패했습니다. 페이지를 새로고침 후 다시 시도하세요.'];
    }
    else {
        $postId     = (string)($_POST['project_id']    ?? '');
        $postAction = (string)($_POST['launch_action'] ?? '');

        // 4) action 화이트리스트
        if (!in_array($postAction, $allowedActions, true)) {
            $launchResult = ['ok' => false, 'msg' => '허용되지 않은 실행 액션입니다.'];
        } else {
            // 5) project_id로 프로젝트 찾기
            $foundProject = null;
            foreach ($projects as $p) {
                if (($p['id'] ?? '') === $postId) { $foundProject = $p; break; }
            }

            if ($foundProject === null) {
                $launchResult = ['ok' => false, 'msg' => '프로젝트를 찾을 수 없습니다.'];
            } elseif (!isAllowedPath((string)($foundProject['path'] ?? ''))) {
                $launchResult = ['ok' => false, 'msg' => '허용된 경로 범위 밖의 프로젝트입니다.'];
            } else {
                // 6) 고정 스크립트 호출 — project_id·action만 전달
                $scriptPath = __DIR__ . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'launch_project.ps1';
                $cmd = 'powershell.exe -NonInteractive -ExecutionPolicy Bypass -File '
                     . escapeshellarg($scriptPath)
                     . ' -ProjectId ' . escapeshellarg($postId)
                     . ' -Action '    . escapeshellarg($postAction)
                     . ' 2>&1';
                $output   = [];
                $exitCode = 0;
                exec($cmd, $output, $exitCode);
                $outputStr    = trim(implode("\n", $output));
                $launchResult = $exitCode === 0
                    ? ['ok' => true,  'msg' => $outputStr ?: '실행 명령을 보냈습니다.']
                    : ['ok' => false, 'msg' => $outputStr ?: '실행 중 오류가 발생했습니다. (exit ' . $exitCode . ')'];
            }
        }
    }
}

// ── GET: 프로젝트 로드 ───────────────────────────────────────
$requestId = (string)($_GET['id'] ?? '');
$project   = null;
foreach ($projects as $p) {
    if (($p['id'] ?? '') === $requestId) { $project = $p; break; }
}
if ($project === null && isset($_POST['project_id'])) {
    $requestId = (string)$_POST['project_id'];
    foreach ($projects as $p) {
        if (($p['id'] ?? '') === $requestId) { $project = $p; break; }
    }
}

$csrfToken   = csrfToken();
$relatedData = ($project !== null)
    ? loadRelatedRecords((string)($project['id'] ?? ''))
    : ['records' => [], 'skip' => 0];
$relatedLab  = ($project !== null)
    ? loadRelatedLabExperiments((string)($project['id'] ?? ''))
    : [];

$statusLabel = [
    'active'   => ['label' => '진행 중', 'class' => 'badge-active'],
    'paused'   => ['label' => '보류',    'class' => 'badge-paused'],
    'archived' => ['label' => '보관',    'class' => 'badge-archived'],
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $project ? e($project['name']) . ' — 개발센터' : '프로젝트 상세 — 개발센터' ?></title>
  <link rel="stylesheet" href="assets/css/dev_center.css">
  <style>
    .detail-box{background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:24px;margin-bottom:28px;}
    .detail-row{display:flex;gap:12px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px;}
    .detail-row:last-child{border-bottom:none;}
    .detail-label{color:var(--text3);width:100px;flex-shrink:0;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;padding-top:1px;}
    .detail-value{color:var(--text);}
    .detail-value.mono{font-family:monospace;font-size:12px;color:var(--text2);}
    .badge-active{background:rgba(46,164,79,.15);color:#2ea44f;border:1px solid rgba(46,164,79,.3);}
    .badge-paused{background:rgba(210,153,34,.15);color:#d29922;border:1px solid rgba(210,153,34,.3);}
    .badge-archived{background:rgba(110,118,129,.15);color:#8b949e;border:1px solid rgba(110,118,129,.3);}
    .status-badge{font-size:11px;font-weight:600;padding:2px 8px;border-radius:10px;}
    .launch-section{margin-bottom:28px;}
    .launch-section h2{font-size:14px;font-weight:700;color:var(--text);margin-bottom:8px;}
    .launch-note{font-size:12px;color:var(--text3);margin-bottom:14px;}
    .launch-note.warn{color:#d29922;}
    .launch-btns{display:flex;gap:10px;flex-wrap:wrap;}
    .btn-launch{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:6px;font-size:13px;font-weight:600;border:1px solid var(--border);background:var(--bg3);color:var(--text);cursor:pointer;transition:border-color .15s,background .15s;}
    .btn-launch:hover{border-color:var(--accent);background:var(--bg2);}
    .btn-launch.btn-all{border-color:var(--accent2);color:#79c0ff;}
    .btn-launch.btn-all:hover{background:rgba(56,139,253,.1);}
    .btn-launch:disabled,.btn-launch[disabled]{opacity:.4;cursor:not-allowed;pointer-events:none;}
    .launch-result{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:24px;border:1px solid transparent;}
    .launch-result.ok{background:rgba(46,164,79,.1);border-color:rgba(46,164,79,.3);color:#3fb950;}
    .launch-result.err{background:rgba(248,81,73,.1);border-color:rgba(248,81,73,.3);color:#ff7b72;}
    .result-pre{margin-top:6px;font-family:monospace;font-size:12px;white-space:pre-wrap;word-break:break-all;}
    .back-link{font-size:13px;color:var(--text3);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-bottom:20px;}
    .back-link:hover{color:var(--text);}
    .not-found{color:var(--text3);font-size:14px;padding:40px 0;}
    .disabled-msg{padding:10px 14px;border-radius:8px;font-size:13px;background:rgba(110,118,129,.1);border:1px solid rgba(110,118,129,.25);color:var(--text3);}
    .prompt-section{margin-bottom:28px;}
    .prompt-section h2{font-size:14px;font-weight:700;color:var(--text);margin-bottom:8px;}
    .prompt-box{position:relative;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:16px;}
    .prompt-pre{font-family:monospace;font-size:12px;color:var(--text2);white-space:pre-wrap;word-break:break-word;margin:0;line-height:1.7;}
    .btn-copy-prompt{position:absolute;top:10px;right:10px;font-size:11px;font-weight:600;padding:4px 12px;border-radius:5px;background:var(--bg3);border:1px solid var(--border);color:var(--text2);cursor:pointer;transition:border-color .15s;}
    .btn-copy-prompt:hover{border-color:var(--accent);color:var(--accent);}
    .btn-copy-prompt.copied{border-color:var(--green);color:#3fb950;}
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
    <li><a href="resources.php">자료실</a></li>
    <li><a href="executables.php">실행파일</a></li>
    <li><a href="settings.php">설정</a></li>
  </ul>
  <div class="dc-topnav-right"><span class="dc-badge-env">LOCAL</span></div>
</nav>

<main class="dc-main">
  <a href="project_manager.php" class="back-link">← 프로젝트 목록</a>

  <?php if ($project === null): ?>
    <div class="not-found">
      <?php if ($requestId === ''): ?>
        프로젝트 ID가 없습니다. <a href="project_manager.php">목록으로 돌아가기</a>
      <?php else: ?>
        ID <code><?= e($requestId) ?></code>에 해당하는 프로젝트를 찾을 수 없습니다.
        <a href="project_manager.php">목록으로 돌아가기</a>
      <?php endif; ?>
    </div>

  <?php else:
    $sid  = $statusLabel[$project['status'] ?? ''] ?? ['label' => e($project['status'] ?? '-'), 'class' => 'badge-archived'];
    $cmds = $project['commands'] ?? [];
  ?>

    <div class="dc-hero" style="margin-bottom:20px;">
      <h1><?= e($project['name']) ?></h1>
      <?php if (!empty($project['description'])): ?>
        <p><?= e($project['description']) ?></p>
      <?php endif; ?>
    </div>

    <div class="proj-overview">
      <div class="proj-overview-meta">
        <div class="pom-item">
          <span class="pom-label">상태</span>
          <span class="status-badge <?= $sid['class'] ?>"><?= $sid['label'] ?></span>
        </div>
        <?php if (!empty($project['badge'])): ?>
        <div class="pom-item">
          <span class="pom-label">분류</span>
          <span class="proj-badge"><?= e($project['badge']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($project['commercial'])): ?>
        <div class="pom-item">
          <span class="pom-label">상업성</span>
          <span class="pom-value"><?= e($project['commercial']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (isset($project['priority'])): ?>
        <div class="pom-item">
          <span class="pom-label">우선순위</span>
          <span class="pom-value"><?= (int)$project['priority'] ?></span>
        </div>
        <?php endif; ?>
      </div>

      <?php if (isset($project['completion'])): ?>
      <div class="proj-progress-wrap">
        <div class="proj-progress-label">
          <span>진행률</span>
          <span><?= (int)$project['completion'] ?>%</span>
        </div>
        <div class="proj-progress">
          <div class="proj-progress-bar" style="width:<?= max(0, min(100, (int)$project['completion'])) ?>%"></div>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($project['next'])): ?>
      <div class="proj-next">
        <span class="proj-next-label">다음:</span><?= e($project['next']) ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($project['memo'])): ?>
      <div class="proj-memo">
        <span class="proj-next-label">메모:</span><?= e($project['memo']) ?>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($launchResult !== null): ?>
      <div class="launch-result <?= $launchResult['ok'] ? 'ok' : 'err' ?>">
        <?= $launchResult['ok'] ? '✅ 실행됐습니다.' : '❌ 실행 실패.' ?>
        <?php if (!empty($launchResult['msg'])): ?>
          <div class="result-pre"><?= e($launchResult['msg']) ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="detail-box">
      <div class="detail-row">
        <span class="detail-label">날짜</span>
        <span class="detail-value"><?= e($project['date'] ?? '—') ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">타입</span>
        <span class="detail-value"><?= e($project['type'] ?? '—') ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">경로</span>
        <span class="detail-value mono"><?= e($project['path'] ?? '—') ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">URL</span>
        <span class="detail-value">
          <?php if (!empty($project['url'])): ?>
            <a href="<?= e($project['url']) ?>" target="_blank" style="color:var(--accent)"><?= e($project['url']) ?></a>
          <?php else: ?>—<?php endif; ?>
        </span>
      </div>
      <div class="detail-row">
        <span class="detail-label">상태</span>
        <span class="detail-value"><span class="status-badge <?= $sid['class'] ?>"><?= $sid['label'] ?></span></span>
      </div>
    </div>

    <?php if (!empty($project['setup_prompt'])): ?>
    <div class="prompt-section">
      <h2>📋 작업 시작 프롬프트</h2>
      <div class="prompt-box">
        <button type="button" class="btn-copy-prompt" id="btn-copy" onclick="copyPrompt()">복사</button>
        <pre class="prompt-pre" id="prompt-text"><?= e($project['setup_prompt']) ?></pre>
      </div>
    </div>
    <?php endif; ?>

    <div class="launch-section">
      <h2>🚀 실행</h2>

      <?php if (!DC_LAUNCH_ENABLED): ?>
        <div class="disabled-msg">실행 기능이 비활성화되어 있습니다.</div>

      <?php elseif (!$isLocal): ?>
        <div class="disabled-msg launch-note warn">
          ⚠️ 외부 접속에서는 실행 기능을 사용할 수 없습니다. localhost에서 접속하세요.
        </div>
        <div class="launch-btns">
          <?php foreach (['📝 VS Code 열기','🤖 Codex 실행','✨ Claude Code 실행','⚡ 전체 실행'] as $label): ?>
          <button type="button" class="btn-launch" disabled><?= $label ?></button>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
        <p class="launch-note">로컬 PC 전용 실행 기능입니다. 외부 접속에서는 실행되지 않습니다.</p>
        <form method="post" action="project_detail.php?id=<?= urlencode($requestId) ?>">
          <input type="hidden" name="action"      value="launch">
          <input type="hidden" name="project_id"  value="<?= e($project['id']) ?>">
          <input type="hidden" name="csrf_token"  value="<?= e($csrfToken) ?>">
          <div class="launch-btns">
            <?php if (!empty($cmds['vscode'])): ?>
            <button type="submit" name="launch_action" value="vscode" class="btn-launch">
              📝 VS Code 열기
            </button>
            <?php endif; ?>
            <?php if (!empty($cmds['codex'])): ?>
            <button type="submit" name="launch_action" value="codex" class="btn-launch">
              🤖 Codex 실행
            </button>
            <?php endif; ?>
            <?php if (!empty($cmds['claude'])): ?>
            <button type="submit" name="launch_action" value="claude" class="btn-launch">
              ✨ Claude Code 실행
            </button>
            <?php endif; ?>
            <?php if (!empty($cmds['vscode']) || !empty($cmds['codex']) || !empty($cmds['claude'])): ?>
            <button type="submit" name="launch_action" value="all" class="btn-launch btn-all">
              ⚡ VS Code + Codex + Claude 실행
            </button>
            <?php endif; ?>
          </div>
        </form>
      <?php endif; ?>
    </div>

  <?php endif; /* $project !== null */

  /* ── 관련 기능 보관함 섹션 ── */
  if ($project !== null):
    $relRecords = $relatedData['records'];
    $relSkip    = $relatedData['skip'];
  ?>
  <section class="rel-section">
    <h2 class="rel-title">관련 기능 보관함</h2>
    <?php if ($relSkip > 0): ?>
      <p class="rel-warn">JSON 파싱 오류로 <?= $relSkip ?>개 레코드를 건너뜀.</p>
    <?php endif; ?>
    <?php if (empty($relRecords)): ?>
      <p class="rel-empty">이 프로젝트에 연결된 기능 보관함 항목이 없습니다.</p>
    <?php else: ?>
      <div class="rel-grid">
        <?php foreach ($relRecords as $r): ?>
        <a href="knowledge.php?q=<?= urlencode($r['id']) ?>" class="rel-card">
          <div class="rel-card-head">
            <span class="rel-card-title"><?= e($r['title']) ?></span>
            <?php if ($r['category'] !== ''): ?>
              <span class="rel-card-cat"><?= e($r['category']) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($r['summary'] !== ''): ?>
            <div class="rel-card-summary"><?= e($r['summary']) ?></div>
          <?php endif; ?>
          <?php if (!empty($r['tags'])): ?>
            <div class="rel-card-tags">
              <?php foreach (array_slice($r['tags'], 0, 5) as $tag): ?>
                <span class="kn-tag"><?= e($tag) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($project !== null && !empty($relatedLab)):
    $labStatusClass = [
      'done'        => 'lb-status-done',
      'archived'    => 'lb-status-archived',
      'in-progress' => 'lb-status-progress',
      'idea'        => 'lb-status-idea',
      'paused'      => 'lb-status-paused',
    ];
    $labStatusLabel = [
      'done'        => '완료',
      'archived'    => '보관',
      'in-progress' => '진행중',
      'idea'        => '아이디어',
      'paused'      => '보류',
    ];
  ?>
  <section class="rel-section">
    <h2 class="rel-title">관련 실험실</h2>
    <div class="rel-grid">
      <?php foreach ($relatedLab as $exp):
        $sCls = $labStatusClass[$exp['status']] ?? 'lb-status-idea';
        $sLbl = $labStatusLabel[$exp['status']] ?? e($exp['status']);
        $pct  = max(0, min(100, $exp['completion']));
      ?>
      <a href="lab.php?id=<?= urlencode($exp['id']) ?>" class="rel-card">
        <div class="rel-card-head">
          <span class="rel-card-title"><?= e($exp['title']) ?></span>
          <span class="lb-status <?= $sCls ?>"><?= $sLbl ?></span>
        </div>
        <div class="lb-card-meta" style="margin-bottom:6px;">
          <?php if ($exp['category'] !== ''): ?>
            <span class="lb-cat-chip"><?= e($exp['category']) ?></span>
          <?php endif; ?>
          <?php if ($exp['subcategory'] !== ''): ?>
            <span class="lb-subcat-chip"><?= e($exp['subcategory']) ?></span>
          <?php endif; ?>
          <?php if ($exp['priority_score'] > 0): ?>
            <span class="lb-priority">P<?= $exp['priority_score'] ?></span>
          <?php endif; ?>
        </div>
        <div class="lb-progress-wrap" style="margin-bottom:6px;">
          <div class="lb-progress"><div class="lb-progress-bar" style="width:<?= $pct ?>%"></div></div>
          <span class="lb-pct"><?= $pct ?>%</span>
        </div>
        <?php if ($exp['summary'] !== ''): ?>
          <div class="rel-card-summary"><?= e($exp['summary']) ?></div>
        <?php endif; ?>
        <?php if (!empty($exp['tags'])): ?>
          <div class="rel-card-tags">
            <?php foreach (array_slice($exp['tags'], 0, 4) as $tag): ?>
              <span class="kn-tag"><?= e($tag) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <footer class="dc-footer">
    <?= e(DEV_CENTER_NAME) ?> v<?= e(DEV_CENTER_VERSION) ?> &mdash; 로컬 개발 전용.
  </footer>
</main>
<script>
function copyPrompt() {
    const text = document.getElementById('prompt-text')?.textContent ?? '';
    const btn  = document.getElementById('btn-copy');
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = '✅ 복사됨';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = '복사'; btn.classList.remove('copied'); }, 1800);
    }).catch(() => {
        btn.textContent = '실패';
        setTimeout(() => { btn.textContent = '복사'; }, 1500);
    });
}
</script>
</body>
</html>
