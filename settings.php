<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function badge(string $level, string $text): string {
    return '<span class="st-badge st-badge-' . $level . '">' . e($text) . '</span>';
}

function ok(bool $cond, string $yes = 'OK', string $no = 'ERROR'): string {
    return badge($cond ? 'ok' : 'error', $cond ? $yes : $no);
}

function warn_if(bool $bad, string $yes = 'OK', string $no = 'WARN'): string {
    return badge($bad ? 'warn' : 'ok', $bad ? $no : $yes);
}

/* ── A. Dev Center ── */
$phpVersion  = PHP_VERSION;
$serverTime  = date('Y-m-d H:i:s');

/* ── B. CopierRMS ── */
$copierExists = is_dir(COPIER_PATH);

/* ── C. Data directory ── */
$dataDir      = DC_DATA_DIR;
$dataDirExist = is_dir($dataDir);
$dataDirRead  = $dataDirExist && is_readable($dataDir);
$dataDirWrite = $dataDirExist && is_writable($dataDir);

/* ── D. Allowed roots ── */
$roots = DEV_ALLOWED_ROOTS;

/* ── E. Launch safety ── */
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocal    = in_array($remoteAddr, ['127.0.0.1', '::1', '0:0:0:0:0:0:0:1'], true);

/* ── F. Data files ── */
$dataFiles = [
    'projects.json'        => ['label' => '프로젝트'],
    'features.json'        => ['label' => '기능 보관함'],
    'prompts.json'         => ['label' => '프롬프트'],
    'lab_experiments.json' => ['label' => '실험실'],
];

$fileStats = [];
foreach ($dataFiles as $name => $meta) {
    $path   = $dataDir . '/' . $name;
    $exists = is_file($path);
    $valid  = false;
    $count  = null;
    $mtime  = null;
    $size   = null;
    if ($exists) {
        $raw = file_get_contents($path);
        $decoded = json_decode($raw, true);
        $valid = ($decoded !== null);
        if ($valid && is_array($decoded)) {
            $count = count($decoded);
        }
        $mtime = date('Y-m-d H:i:s', filemtime($path));
        $size  = round(filesize($path) / 1024, 1);
    }
    $fileStats[$name] = [
        'label'  => $meta['label'],
        'exists' => $exists,
        'valid'  => $valid,
        'count'  => $count,
        'mtime'  => $mtime,
        'size'   => $size,
    ];
}

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

/* ── ws-settings 프롬프트 로드 ── */
require_once __DIR__ . '/includes/ai_workspace_prompt.php';
$wsPromptText = buildAiWorkspacePrompt(
    'ws-settings',
    '⚠️ Do not change config, credentials, auth, environment, or scripts unless the user explicitly approves.'
);
$jsWsPrompt   = json_encode($wsPromptText, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

/* ── 워크스페이스 런처: CSRF + 허용 툴 ── */
if (empty($_SESSION['dc_csrf_token'])) {
    $_SESSION['dc_csrf_token'] = bin2hex(random_bytes(32));
}
$wsLaunchCsrf    = $_SESSION['dc_csrf_token'];
$wsLaunchAllowed = [];
$_wsFile = DC_DATA_DIR . '/ai_workspaces.json';
if (is_file($_wsFile)) {
    $_wsAll = json_decode(file_get_contents($_wsFile), true);
    if (is_array($_wsAll)) {
        foreach ($_wsAll as $_ws) {
            if (($_ws['id'] ?? '') === 'ws-settings') {
                $wsLaunchAllowed = (array)($_ws['ai_tools'] ?? []);
                break;
            }
        }
    }
}
unset($_wsFile, $_wsAll, $_ws);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>설정 — 개발센터</title>
  <link rel="stylesheet" href="assets/css/dev_center.css">
</head>
<body>

<nav class="dc-topnav">
  <a href="index.php" class="dc-brand">
    🛠️ 개발센터
    <span class="dc-brand-badge">DEV</span>
  </a>
  <ul class="dc-nav-links">
    <li><a href="index.php">홈</a></li>
    <li><a href="project_manager.php">프로젝트 관리</a></li>
    <li><a href="knowledge.php">기능 보관함</a></li>
    <li><a href="lab.php">실험실</a></li>
    <li><a href="prompts.php">프롬프트</a></li>
    <li><a href="resources.php">자료실</a></li>
    <li><a href="settings.php" class="active">설정</a></li>
  </ul>
  <div class="dc-topnav-right">
    <span class="dc-badge-env">LOCAL</span>
  </div>
</nav>

<main class="dc-main">

  <div class="dc-hero">
    <div class="dc-hero-row">
      <div>
        <h1>설정 · 상태</h1>
        <p>Dev Center 환경 설정과 데이터 상태를 읽기 전용으로 확인합니다.</p>
      </div>
      <div class="ws-action-group">
      <?php if ($wsPromptText !== ''): ?>
      <button class="ws-prompt-btn" id="ws-prompt-copy" title="설정 AI 시작 프롬프트를 클립보드에 복사합니다">
        <span class="ws-prompt-btn-icon">🤖</span> 설정 AI 프롬프트 복사
      </button>
      <?php endif; ?>
      <?php if (in_array('codex', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-settings" data-action="codex">⚡ Codex 열기</button>
      <?php endif; ?>
      <?php if (in_array('claude', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-settings" data-action="claude">🤖 Claude 열기</button>
      <?php endif; ?>
      <?php if (in_array('vscode-codex', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-settings" data-action="vscode-codex">🖥️ VSCode + Codex</button>
      <?php endif; ?>
      <?php if (in_array('vscode-claude', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-settings" data-action="vscode-claude">🖥️ VSCode + Claude</button>
      <?php endif; ?>
      </div>
    </div>
  </div>
  <span id="ws-launch-msg" class="ws-launch-msg"></span>

  <!-- A. Dev Center -->
  <p class="dc-section-title">Dev Center</p>
  <div class="st-card st-card-grid mb24">
    <div class="st-row">
      <span class="st-key">이름</span>
      <span class="st-val"><?= e(DEV_CENTER_NAME) ?></span>
    </div>
    <div class="st-row">
      <span class="st-key">버전</span>
      <span class="st-val">v<?= e(DEV_CENTER_VERSION) ?></span>
    </div>
    <div class="st-row">
      <span class="st-key">Base URL</span>
      <code class="st-code"><?= e(DEV_CENTER_BASE_URL) ?></code>
    </div>
    <div class="st-row">
      <span class="st-key">PHP 버전</span>
      <span class="st-val"><?= e($phpVersion) ?></span>
    </div>
    <div class="st-row">
      <span class="st-key">서버 시각</span>
      <span class="st-val"><?= e($serverTime) ?></span>
    </div>
  </div>

  <!-- B. CopierRMS -->
  <p class="dc-section-title">CopierRMS 연결</p>
  <div class="st-card mb24">
    <div class="st-row">
      <span class="st-key">경로</span>
      <code class="st-code"><?= e(COPIER_PATH) ?></code>
      <?= ok($copierExists, '존재함', '없음') ?>
    </div>
    <div class="st-row">
      <span class="st-key">URL</span>
      <code class="st-code"><?= e(COPIER_URL) ?></code>
    </div>
    <div class="st-row">
      <span class="st-key">바로가기</span>
      <a href="<?= e(COPIER_URL) ?>" target="_blank" class="st-link-btn">CopierRMS 열기 ↗</a>
    </div>
  </div>

  <!-- C. Data Directory -->
  <p class="dc-section-title">데이터 디렉터리</p>
  <div class="st-card mb24">
    <div class="st-row">
      <span class="st-key">경로</span>
      <code class="st-code"><?= e($dataDir) ?></code>
    </div>
    <div class="st-row">
      <span class="st-key">존재</span>
      <?= ok($dataDirExist, '있음', '없음') ?>
    </div>
    <div class="st-row">
      <span class="st-key">읽기</span>
      <?= ok($dataDirRead, '가능', '불가') ?>
    </div>
    <div class="st-row">
      <span class="st-key">쓰기</span>
      <?= warn_if(!$dataDirWrite, '가능', '불가') ?>
    </div>
  </div>

  <!-- D. Allowed Roots -->
  <p class="dc-section-title">허용된 프로젝트 루트</p>
  <div class="st-card mb24">
    <?php foreach ($roots as $root): ?>
    <?php $rExists = is_dir($root); $rWrite = $rExists && is_writable($root); ?>
    <div class="st-row st-row-path">
      <code class="st-code st-code-grow"><?= e($root) ?></code>
      <div class="st-badges">
        <?= ok($rExists, '있음', '없음') ?>
        <?= warn_if(!$rWrite, '쓰기 가능', '쓰기 불가') ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- E. Launch Safety -->
  <p class="dc-section-title">실행 안전</p>
  <div class="st-card mb24">
    <div class="st-row">
      <span class="st-key">DC_LAUNCH_ENABLED</span>
      <?= DC_LAUNCH_ENABLED ? badge('ok', 'true') : badge('warn', 'false') ?>
    </div>
    <div class="st-row">
      <span class="st-key">접속 IP</span>
      <code class="st-code"><?= e($remoteAddr) ?></code>
      <?= ok($isLocal, '로컬', '외부') ?>
    </div>
    <div class="st-row">
      <span class="st-key">안내</span>
      <span class="st-note">실행 버튼(VS Code · Claude · Codex)은 로컬 접속(127.0.0.1 / ::1)에서만 동작합니다.</span>
    </div>
  </div>

  <!-- F. Data Files -->
  <p class="dc-section-title">데이터 파일 상태</p>
  <div class="st-file-grid mb24">
    <?php foreach ($fileStats as $name => $s): ?>
    <?php $healthy = $s['exists'] && $s['valid']; ?>
    <div class="st-file-card">
      <div class="st-file-header">
        <span class="st-file-label"><?= e($s['label']) ?></span>
        <?= $healthy ? badge('ok', 'OK') : ($s['exists'] ? badge('warn', 'WARN') : badge('error', 'ERROR')) ?>
      </div>
      <code class="st-code st-file-name"><?= e($name) ?></code>
      <div class="st-file-rows">
        <div class="st-row">
          <span class="st-key">파일</span>
          <?= ok($s['exists'], '있음', '없음') ?>
        </div>
        <?php if ($s['exists']): ?>
        <div class="st-row">
          <span class="st-key">JSON</span>
          <?= ok($s['valid'], '유효', '파싱 오류') ?>
        </div>
        <?php if ($s['count'] !== null): ?>
        <div class="st-row">
          <span class="st-key">항목 수</span>
          <span class="st-val st-count"><?= e((string)$s['count']) ?>개</span>
        </div>
        <?php endif; ?>
        <div class="st-row">
          <span class="st-key">수정일</span>
          <span class="st-val"><?= e($s['mtime'] ?? '') ?></span>
        </div>
        <div class="st-row">
          <span class="st-key">크기</span>
          <span class="st-val"><?= e((string)$s['size']) ?> KB</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <footer class="dc-footer">
    <?= e(DEV_CENTER_NAME) ?>
    v<?= e(DEV_CENTER_VERSION) ?> &mdash;
    로컬 개발 전용. 외부 공개 금지.
  </footer>

</main>

<?php if ($wsPromptText !== '') renderWsPromptCopyScript($jsWsPrompt); ?>
<script>
(function () {
  var CSRF = <?= json_encode($wsLaunchCsrf, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  document.querySelectorAll('.ws-launch-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var wsId   = btn.dataset.workspace;
      var action = btn.dataset.action;
      var msg    = document.getElementById('ws-launch-msg');
      btn.disabled = true;
      var body = new URLSearchParams({ workspace_id: wsId, action: action, csrf_token: CSRF });
      fetch('workspace_launcher.php', { method: 'POST', body: body })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (msg) { msg.textContent = d.message || (d.ok ? '실행됨' : '실패'); msg.className = 'ws-launch-msg ' + (d.ok ? 'ws-launch-ok' : 'ws-launch-err'); }
        })
        .catch(function () { if (msg) { msg.textContent = '요청 실패'; msg.className = 'ws-launch-msg ws-launch-err'; } })
        .finally(function () { btn.disabled = false; });
    });
  });
})();
</script>
</body>
</html>
