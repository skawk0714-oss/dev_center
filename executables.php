<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

session_start();
if (empty($_SESSION['dc_csrf_token'])) {
    $_SESSION['dc_csrf_token'] = bin2hex(random_bytes(32));
}

$executables = [];
$loadError   = null;
$jsonPath    = DC_DATA_DIR . '/executables.json';

if (file_exists($jsonPath)) {
    $raw  = file_get_contents($jsonPath);
    $data = json_decode((string)$raw, true);
    if (is_array($data)) {
        $executables = $data;
    } else {
        $loadError = 'executables.json 파싱 실패: ' . json_last_error_msg();
    }
}

$USAGE_TYPE_LABELS = [
    'field_run'     => '현장 실행용',
    'installer'     => '에이전트 설치용',
    'uninstaller'   => '에이전트 삭제용',
    'internal_tool' => '내부 도구',
    'script'        => '스크립트',
    'other'         => '기타',
];

$q         = trim((string)($_GET['q']    ?? ''));
$typeFilt  = trim((string)($_GET['type'] ?? ''));

$filtered = array_values(array_filter($executables, static function (array $r) use ($q, $typeFilt): bool {
    if ($typeFilt !== '' && (string)($r['usage_type'] ?? '') !== $typeFilt) {
        return false;
    }
    if ($q !== '') {
        $haystack = mb_strtolower(implode(' ', [
            (string)($r['title']          ?? ''),
            (string)($r['description']    ?? ''),
            (string)($r['project_id']     ?? ''),
            (string)($r['target_project'] ?? ''),
            (string)($r['usage_label']    ?? ''),
            implode(' ', (array)($r['tags'] ?? [])),
        ]), 'UTF-8');
        if (mb_strpos($haystack, mb_strtolower($q, 'UTF-8'), 0, 'UTF-8') === false) {
            return false;
        }
    }
    return true;
}));

/* usage_type 필터 목록 — 실제 데이터에 있는 것만 */
$allTypes = [];
foreach ($executables as $r) {
    $t = (string)($r['usage_type'] ?? '');
    if ($t !== '' && !isset($allTypes[$t])) {
        $allTypes[$t] = $USAGE_TYPE_LABELS[$t] ?? $t;
    }
}

/* 프로젝트별 그룹 */
$groups = [];
foreach ($filtered as $r) {
    $gKey = (string)($r['target_project'] ?? ($r['project_id'] ?? '기타'));
    $groups[$gKey][] = $r;
}

function ex_e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>실행파일 — <?= ex_e(DEV_CENTER_NAME) ?></title>
  <?php $cssVer = is_file(__DIR__ . '/assets/css/dev_center.css') ? filemtime(__DIR__ . '/assets/css/dev_center.css') : time(); ?>
  <link rel="stylesheet" href="assets/css/dev_center.css?v=<?= $cssVer ?>">
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
    <li><a href="executables.php" class="active">실행파일</a></li>
    <li><a href="settings.php">설정</a></li>
  </ul>
</nav>

<main class="dc-main">
  <div class="dc-hero">
    <div class="dc-hero-row">
      <div>
        <h1>실행파일</h1>
        <p>거래처 PC에서 실행하거나 배포하는 파일만 모아둔 목록입니다. 문서·설계 자료는 <a href="resources.php">자료실</a>에 있습니다.</p>
      </div>
    </div>
  </div>

<?php if ($loadError !== null): ?>
  <div class="dc-alert dc-alert-error"><?= ex_e($loadError) ?></div>
<?php endif; ?>

  <div class="ex-wrap">
    <form method="get" class="kn-toolbar" id="ex-form">
      <input
        type="search" name="q"
        value="<?= ex_e($q) ?>"
        placeholder="파일명, 설명, 프로젝트, 태그 검색…"
        class="kn-search-input"
        id="ex-search-input">
      <div class="kn-filters">
        <button type="submit" name="type" value=""
          class="kn-filter<?= $typeFilt === '' ? ' active' : '' ?>">전체</button>
        <?php foreach ($allTypes as $key => $label): ?>
          <button type="submit" name="type" value="<?= ex_e($key) ?>"
            class="kn-filter<?= $typeFilt === $key ? ' active' : '' ?>"><?= ex_e($label) ?></button>
        <?php endforeach; ?>
      </div>
    </form>

    <div class="ex-count"><?= count($filtered) ?>개 / 전체 <?= count($executables) ?>개</div>

<?php if (empty($filtered)): ?>
    <div class="ex-empty">
      <?= $q !== '' || $typeFilt !== '' ? '검색 결과가 없습니다.' : '등록된 실행파일이 없습니다.' ?>
    </div>
<?php else: ?>
<?php foreach ($groups as $groupName => $items): ?>
    <div class="ex-group">
      <div class="ex-group-header"><?= ex_e($groupName) ?></div>
      <div class="ex-list">
<?php foreach ($items as $r):
    $usageType  = (string)($r['usage_type']  ?? '');
    $usageLabel = (string)($r['usage_label'] ?? ($USAGE_TYPE_LABELS[$usageType] ?? ''));
    $fileType   = (string)($r['file_type']   ?? '');
    $path       = (string)($r['path']        ?? '');
    $tags       = (array)($r['tags']         ?? []);
    $version    = (string)($r['version']     ?? '');
    $runInstr   = (string)($r['run_instruction'] ?? '');
    $isInternal = in_array($usageType, ['internal_tool', 'script'], true);
?>
        <div class="ex-item ex-usage-<?= ex_e($usageType) ?>">
          <div class="ex-item-header">
            <div class="ex-item-title"><?= ex_e((string)($r['title'] ?? '')) ?></div>
            <div class="ex-item-badges">
              <?php if ($usageLabel !== ''): ?>
                <span class="ex-usage-badge ex-usage-badge-<?= ex_e($usageType) ?>"><?= ex_e($usageLabel) ?></span>
              <?php endif; ?>
              <?php if ($fileType !== ''): ?>
                <span class="ex-filetype-badge">.<?= ex_e($fileType) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($runInstr !== ''): ?>
            <div class="ex-run-instr ex-run-instr-<?= ex_e($usageType) ?>">
              <?php if ($isInternal): ?>⚠️ <?php endif; ?>
              <?= ex_e($runInstr) ?>
            </div>
          <?php endif; ?>
          <div class="ex-item-desc"><?= ex_e((string)($r['description'] ?? '')) ?></div>
          <div class="ex-item-meta">
            <?php if (!empty($r['project_id'])): ?>
              <span class="ex-pid"><?= ex_e((string)$r['project_id']) ?></span>
            <?php endif; ?>
            <?php if ($version !== ''): ?>
              <span class="ex-version">v<?= ex_e($version) ?></span>
            <?php endif; ?>
            <?php foreach ($tags as $tag): ?>
              <span class="ex-tag"><?= ex_e((string)$tag) ?></span>
            <?php endforeach; ?>
          </div>
          <?php if ($path !== ''): ?>
            <div class="ex-item-footer">
              <div class="ex-path"><?= ex_e($path) ?></div>
              <div class="ex-item-actions">
                <button type="button"
                  class="ex-btn primary ex-copy-btn"
                  data-path="<?= ex_e($path) ?>"
                >경로 복사</button>
              </div>
            </div>
          <?php endif; ?>
        </div>
<?php endforeach; ?>
      </div>
    </div>
<?php endforeach; ?>
<?php endif; ?>
  </div>

  <footer class="dc-footer">
    <?= ex_e(DEV_CENTER_NAME) ?> &mdash; 로컬 개발 전용. 외부 공개 금지.
  </footer>

</main>

<div id="ex-toast"></div>

<script>
function exCopy(text) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(function() {
      exToast('경로가 복사되었습니다.', 'success');
    }, function() {
      exCopyFallback(text);
    });
    return;
  }
  exCopyFallback(text);
}
function exCopyFallback(text) {
  var ta = document.createElement('textarea');
  ta.value = text;
  ta.style.position = 'fixed';
  ta.style.left     = '-9999px';
  ta.style.opacity  = '0';
  document.body.appendChild(ta);
  ta.focus();
  ta.select();
  ta.setSelectionRange(0, ta.value.length);
  var ok = false;
  try { ok = document.execCommand('copy'); } catch (e) {}
  document.body.removeChild(ta);
  exToast(ok ? '경로가 복사되었습니다.' : '경로 복사에 실패했습니다.', ok ? 'success' : 'error');
}
function exToast(msg, type) {
  var el = document.getElementById('ex-toast');
  el.textContent = msg;
  el.className   = type;
  el.style.display = 'block';
  setTimeout(function() { el.style.display = 'none'; }, 2200);
}
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.ex-copy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      exCopy(btn.dataset.path);
    });
  });
});
</script>
</body>
</html>
