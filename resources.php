<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

session_start();
if (empty($_SESSION['dc_csrf_token'])) {
    $_SESSION['dc_csrf_token'] = bin2hex(random_bytes(32));
}

$resources = [];
$loadError = null;
$jsonPath  = DC_DATA_DIR . '/resources.json';

if (file_exists($jsonPath)) {
    $raw  = file_get_contents($jsonPath);
    $data = json_decode((string)$raw, true);
    if (is_array($data)) {
        $resources = $data;
    } else {
        $loadError = 'resources.json 파싱 실패: ' . json_last_error_msg();
    }
}

$CATEGORY_LABELS = [
    'diagnostic_tool' => '진단 도구',
    'installer'       => '설치 패키지',
    'automation'      => '자동화',
    'document'        => '문서/템플릿',
    'prompt'          => '프롬프트',
    'library'         => '라이브러리',
    'other'           => '기타',
];

$STORAGE_LABELS = [
    'local'        => '로컬',
    'url'          => 'URL',
    'google_drive' => 'Google Drive',
    'note'         => '메모',
];

$q       = trim((string)($_GET['q'] ?? ''));
$catFilt = trim((string)($_GET['cat'] ?? ''));

$filtered = array_values(array_filter($resources, static function (array $r) use ($q, $catFilt): bool {
    if ($catFilt !== '' && (string)($r['category'] ?? '') !== $catFilt) {
        return false;
    }
    if ($q !== '') {
        $haystack = mb_strtolower(implode(' ', [
            (string)($r['title'] ?? ''),
            (string)($r['description'] ?? ''),
            (string)($r['project_id'] ?? ''),
            (string)($r['storage_type'] ?? ''),
            implode(' ', (array)($r['tags'] ?? [])),
        ]), 'UTF-8');
        if (mb_strpos($haystack, mb_strtolower($q, 'UTF-8'), 0, 'UTF-8') === false) {
            return false;
        }
    }
    return true;
}));

$allCats = [];
foreach ($resources as $r) {
    $c = (string)($r['category'] ?? '');
    if ($c !== '' && !isset($allCats[$c])) {
        $allCats[$c] = $CATEGORY_LABELS[$c] ?? $c;
    }
}

function rc_e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>자료실 — <?= rc_e(DEV_CENTER_NAME) ?></title>
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
    <li><a href="resources.php" class="active">자료실</a></li>
    <li><a href="settings.php">설정</a></li>
  </ul>
</nav>

<main class="dc-main">
  <div class="dc-hero">
    <div class="dc-hero-row">
      <div>
        <h1>자료실</h1>
        <p>진단 도구, 설치 패키지, 문서, 프롬프트 등 프로젝트 자료를 한 곳에서 관리합니다.</p>
      </div>
    </div>
  </div>

<?php if ($loadError !== null): ?>
  <div class="dc-alert dc-alert-error"><?= rc_e($loadError) ?></div>
<?php endif; ?>

  <div class="rc-wrap">
    <form method="get" class="kn-toolbar" id="rc-form">
      <input
        type="search" name="q"
        value="<?= rc_e($q) ?>"
        placeholder="제목, 설명, 태그, 프로젝트 검색…"
        class="kn-search-input"
        id="rc-search-input">
      <div class="kn-filters">
        <button type="submit" name="cat" value=""
          class="kn-filter<?= $catFilt === '' ? ' active' : '' ?>">전체</button>
        <?php foreach ($allCats as $key => $label): ?>
          <button type="submit" name="cat" value="<?= rc_e($key) ?>"
            class="kn-filter<?= $catFilt === $key ? ' active' : '' ?>"><?= rc_e($label) ?></button>
        <?php endforeach; ?>
      </div>
    </form>

    <div class="rc-count"><?= count($filtered) ?>건 / 전체 <?= count($resources) ?>건</div>

    <div class="rc-list">
<?php if (empty($filtered)): ?>
      <div class="rc-empty">
        <?= $q !== '' || $catFilt !== '' ? '검색 결과가 없습니다.' : '등록된 자료가 없습니다.' ?>
      </div>
<?php else: ?>
<?php foreach ($filtered as $r):
    $storageType  = (string)($r['storage_type'] ?? 'other');
    $catKey       = (string)($r['category'] ?? '');
    $catLabel     = $CATEGORY_LABELS[$catKey] ?? $catKey;
    $storageLabel = $STORAGE_LABELS[$storageType] ?? $storageType;
    $tags         = (array)($r['tags'] ?? []);
    $path         = (string)($r['path'] ?? '');
    $url          = (string)($r['url'] ?? '');
?>
      <div class="rc-item">
        <div class="rc-item-header">
          <div class="rc-item-title"><?= rc_e((string)($r['title'] ?? '')) ?></div>
          <span class="rc-storage-badge rc-storage-<?= rc_e($storageType) ?>"><?= rc_e($storageLabel) ?></span>
        </div>
        <div class="rc-item-desc"><?= rc_e((string)($r['description'] ?? '')) ?></div>
        <div class="rc-item-meta">
          <?php if ($catLabel !== ''): ?>
            <span class="rc-cat"><?= rc_e($catLabel) ?></span>
          <?php endif; ?>
          <?php if (!empty($r['project_id'])): ?>
            <span class="rc-pid"><?= rc_e((string)$r['project_id']) ?></span>
          <?php endif; ?>
          <?php foreach ($tags as $tag): ?>
            <span class="rc-tag"><?= rc_e((string)$tag) ?></span>
          <?php endforeach; ?>
        </div>
        <div class="rc-item-footer">
          <?php if ($storageType === 'local' && $path !== ''): ?>
            <div class="rc-path"><?= rc_e($path) ?></div>
          <?php endif; ?>
          <div class="rc-item-actions">
            <?php if ($storageType === 'local' && $path !== ''): ?>
              <button type="button"
                class="rc-btn primary"
                onclick="rcCopy(<?= json_encode($path, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)"
              >경로 복사</button>
            <?php elseif ($storageType === 'url' && $url !== ''): ?>
              <a href="<?= rc_e($url) ?>" target="_blank" rel="noopener" class="rc-btn primary">열기</a>
            <?php elseif ($storageType === 'google_drive'): ?>
              <button type="button" class="rc-btn disabled" disabled>Google Drive 연결 예정</button>
            <?php elseif ($storageType === 'note'): ?>
              <span class="rc-tag">메모</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
<?php endforeach; ?>
<?php endif; ?>
    </div>
  </div>

  <footer class="dc-footer">
    <?= rc_e(DEV_CENTER_NAME) ?> &mdash; 로컬 개발 전용. 외부 공개 금지.
  </footer>

</main>

<div id="rc-toast"></div>

<script>
function rcCopy(text) {
  if (!navigator.clipboard) { rcToast('클립보드를 사용할 수 없습니다.', 'error'); return; }
  navigator.clipboard.writeText(text).then(function() {
    rcToast('경로가 복사되었습니다.', 'success');
  }, function() {
    rcToast('복사에 실패했습니다.', 'error');
  });
}
function rcToast(msg, type) {
  var el = document.getElementById('rc-toast');
  el.textContent = msg;
  el.className   = type;
  el.style.display = 'block';
  setTimeout(function() { el.style.display = 'none'; }, 2200);
}
</script>
</body>
</html>
