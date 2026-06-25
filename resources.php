<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

session_start();
if (empty($_SESSION['dc_csrf_token'])) {
    $_SESSION['dc_csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/google_drive.php';

// 개발센터 Drive 폴더 맵 (drive_folders.json: 폴더명 => folderId)
$driveFolders = [];
$dfPath = DC_DATA_DIR . '/drive_folders.json';
if (is_file($dfPath)) {
    $dfRaw = json_decode((string) file_get_contents($dfPath), true);
    if (is_array($dfRaw)) {
        $driveFolders = $dfRaw;
    }
}

$jsonPath = DC_DATA_DIR . '/resources.json';

// ── 업로드 처리 (POST): 파일을 개발센터 Drive 폴더에 올리고 자료로 등록 ──────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    if (!hash_equals($_SESSION['dc_csrf_token'] ?? '', (string) ($_POST['csrf_token'] ?? ''))) {
        $_SESSION['rc_flash'] = ['type' => 'error', 'msg' => 'CSRF 검증 실패. 새로고침 후 다시 시도하세요.'];
        header('Location: resources.php'); exit;
    }
    if (!gd_configured()) {
        $_SESSION['rc_flash'] = ['type' => 'error', 'msg' => '구글 드라이브 자격증명이 없습니다.'];
        header('Location: resources.php'); exit;
    }

    $folderKey = trim((string) ($_POST['drive_folder'] ?? ''));
    $folderId  = (string) ($driveFolders[$folderKey] ?? '');
    if ($folderId === '') {
        $_SESSION['rc_flash'] = ['type' => 'error', 'msg' => '대상 폴더를 선택하세요.'];
        header('Location: resources.php'); exit;
    }

    $up = gd_upload_file($_FILES['file'] ?? [], $folderId);
    if (!$up['ok']) {
        $_SESSION['rc_flash'] = ['type' => 'error', 'msg' => (string) $up['err']];
        header('Location: resources.php'); exit;
    }

    // resources.json 에 새 자료로 등록
    $titleIn = trim((string) ($_POST['title'] ?? ''));
    $title   = $titleIn !== '' ? $titleIn : (string) ($up['file']['name'] ?? '업로드 파일');
    $newRes  = [
        'id'             => 'drive-' . date('ymdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
        'title'          => $title,
        'category'       => 'other',
        'project_id'     => '260614-copier-rms',
        'description'    => trim((string) ($_POST['description'] ?? '')),
        'usage_type'     => 'reference',
        'usage_note'     => '개발센터 구글 드라이브(' . $folderKey . ' 폴더)에 업로드된 자료입니다.',
        'storage_type'   => 'google_drive',
        'path'           => '',
        'url'            => (string) ($up['file']['webViewLink'] ?? ''),
        'drive_file_id'  => (string) ($up['file']['id'] ?? ''),
        'drive_folder_id'=> $folderId,
        'mime_type'      => (string) ($up['file']['mimeType'] ?? ''),
        'tags'           => ['drive', $folderKey],
        'resource_kind'  => 'project_asset',
        'status'         => 'active',
        'created_at'     => date('Y-m-d'),
        'updated_at'     => date('Y-m-d'),
    ];

    $existing = is_file($jsonPath) ? json_decode((string) file_get_contents($jsonPath), true) : [];
    if (!is_array($existing)) { $existing = []; }
    $existing[] = $newRes;
    // JSON_INVALID_UTF8_SUBSTITUTE: 제목 등에 깨진 바이트가 있어도 등록이 실패하지 않게(고아 업로드 방지)
    $out = json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($out === false || file_put_contents($jsonPath, $out, LOCK_EX) === false) {
        $_SESSION['rc_flash'] = ['type' => 'error', 'msg' => '드라이브 업로드는 됐지만 자료 등록(저장)에 실패했습니다.'];
    } else {
        $_SESSION['rc_flash'] = ['type' => 'ok', 'msg' => '업로드 완료: ' . $title . ' → 개발센터/' . $folderKey];
    }
    header('Location: resources.php'); exit;
}

// 업로드 결과 플래시 (PRG)
$rcFlash = $_SESSION['rc_flash'] ?? null;
unset($_SESSION['rc_flash']);

$resources = [];
$loadError = null;

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

$KIND_LABELS = [
    'reference'          => '참고 자료',
    'project_asset'      => '프로젝트 자료',
    'deployment_package' => '배포 패키지',
    'diagnostic_tool'    => '진단 도구',
    'driver_package'     => '드라이버',
    'scan_package'       => '스캔 패키지',
    'script'             => '스크립트',
    'note'               => '노트',
    'other'              => '기타',
];

$STORAGE_LABELS = [
    'local'        => '로컬',
    'url'          => 'URL',
    'google_drive' => 'Google Drive',
    'note'         => '메모',
];

$q        = trim((string)($_GET['q'] ?? ''));
$catFilt  = trim((string)($_GET['cat'] ?? ''));
$kindFilt = trim((string)($_GET['kind'] ?? ''));

$filtered = array_values(array_filter($resources, static function (array $r) use ($q, $catFilt, $kindFilt): bool {
    if ($catFilt !== '' && (string)($r['category'] ?? '') !== $catFilt) {
        return false;
    }
    if ($kindFilt !== '' && (string)($r['resource_kind'] ?? '') !== $kindFilt) {
        return false;
    }
    if ($q !== '') {
        $haystack = mb_strtolower(implode(' ', [
            (string)($r['title'] ?? ''),
            (string)($r['description'] ?? ''),
            (string)($r['project_id'] ?? ''),
            (string)($r['storage_type'] ?? ''),
            (string)($r['resource_kind'] ?? ''),
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

$allKinds = [];
foreach ($resources as $r) {
    $k = (string)($r['resource_kind'] ?? '');
    if ($k !== '' && !isset($allKinds[$k])) {
        $allKinds[$k] = $KIND_LABELS[$k] ?? $k;
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

<?php $activeNav = 'resources'; require __DIR__ . '/includes/dev_center_nav.php'; ?>

<main class="dc-main">
  <div class="dc-hero">
    <div class="dc-hero-row">
      <div>
        <h1>자료실</h1>
        <p>진단 도구, 설치 패키지, 문서, 프롬프트 등 프로젝트 자료를 한 곳에서 관리합니다.</p>
      </div>
    </div>
  </div>

<?php if ($rcFlash !== null): ?>
  <div class="dc-alert dc-alert-<?= $rcFlash['type'] === 'ok' ? 'ok' : 'error' ?>"><?= rc_e((string) $rcFlash['msg']) ?></div>
<?php endif; ?>

<?php if ($loadError !== null): ?>
  <div class="dc-alert dc-alert-error"><?= rc_e($loadError) ?></div>
<?php endif; ?>

<?php
// 업로드 대상 폴더 후보 (_root 제외)
$uploadFolders = array_values(array_filter(array_keys($driveFolders), static fn($k) => $k !== '_root'));
?>
<?php if (!empty($uploadFolders) && gd_configured()): ?>
  <form method="post" enctype="multipart/form-data" class="rc-upload-form kn-toolbar" style="gap:8px;flex-wrap:wrap;align-items:center">
    <input type="hidden" name="action" value="upload">
    <input type="hidden" name="csrf_token" value="<?= rc_e($_SESSION['dc_csrf_token']) ?>">
    <strong style="margin-right:4px">📤 개발센터 드라이브에 업로드</strong>
    <input type="file" name="file" required class="kn-search-input" style="flex:0 0 auto">
    <input type="text" name="title" placeholder="제목(선택, 비우면 파일명)" class="kn-search-input" style="flex:0 0 auto">
    <select name="drive_folder" class="kn-search-input" style="flex:0 0 auto">
      <?php foreach ($uploadFolders as $fk): ?>
        <option value="<?= rc_e($fk) ?>"<?= $fk === '기타' ? ' selected' : '' ?>>개발센터/<?= rc_e($fk) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="rc-btn primary">업로드</button>
  </form>
<?php elseif (gd_configured() && empty($uploadFolders)): ?>
  <div class="dc-alert dc-alert-error">개발센터 드라이브 폴더가 없습니다. <code>data/drive_folders.json</code> 을 먼저 생성하세요.</div>
<?php endif; ?>

  <div class="rc-wrap">
    <form method="get" class="kn-toolbar" id="rc-form">
      <input type="hidden" name="cat"  value="<?= rc_e($catFilt) ?>">
      <input type="hidden" name="kind" value="<?= rc_e($kindFilt) ?>">
      <input
        type="search" name="q"
        value="<?= rc_e($q) ?>"
        placeholder="제목, 설명, 태그, 종류 검색…"
        class="kn-search-input"
        id="rc-search-input">
      <div class="kn-filters" id="rc-cat-filters">
        <button type="button" data-filter="cat" data-value=""
          class="kn-filter<?= $catFilt === '' ? ' active' : '' ?>">전체</button>
        <?php foreach ($allCats as $key => $label): ?>
          <button type="button" data-filter="cat" data-value="<?= rc_e($key) ?>"
            class="kn-filter<?= $catFilt === $key ? ' active' : '' ?>"><?= rc_e($label) ?></button>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($allKinds)): ?>
      <div class="kn-filters rc-kind-filters" id="rc-kind-filters">
        <span class="rc-filter-label">종류</span>
        <button type="button" data-filter="kind" data-value=""
          class="kn-filter kn-filter-sm<?= $kindFilt === '' ? ' active' : '' ?>">전체</button>
        <?php foreach ($allKinds as $key => $label): ?>
          <button type="button" data-filter="kind" data-value="<?= rc_e($key) ?>"
            class="kn-filter kn-filter-sm<?= $kindFilt === $key ? ' active' : '' ?>"><?= rc_e($label) ?></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </form>

    <div class="rc-count"><?= count($filtered) ?>건 / 전체 <?= count($resources) ?>건</div>

    <div class="rc-list">
<?php if (empty($filtered)): ?>
      <div class="rc-empty">
        <?= $q !== '' || $catFilt !== '' ? '검색 결과가 없습니다.' : '등록된 자료가 없습니다.' ?>
      </div>
<?php else: ?>
<?php
$USAGE_TYPE_LABELS = [
    'field_run'         => '현장 실행용',
    'internal_tool'     => '내부 도구',
    'document'          => '개발 문서',
    'installer_package' => '설치 패키지',
    'reference'         => '참고 자료',
];
foreach ($filtered as $r):
    $storageType  = (string)($r['storage_type'] ?? 'other');
    $catKey       = (string)($r['category'] ?? '');
    $catLabel     = $CATEGORY_LABELS[$catKey] ?? $catKey;
    $storageLabel = $STORAGE_LABELS[$storageType] ?? $storageType;
    $tags         = (array)($r['tags'] ?? []);
    $path         = (string)($r['path'] ?? '');
    $url          = (string)($r['url'] ?? '');
    $usageType    = (string)($r['usage_type'] ?? '');
    $usageNote    = (string)($r['usage_note'] ?? '');
    $usageLabel   = $USAGE_TYPE_LABELS[$usageType] ?? '';
    $kindKey      = (string)($r['resource_kind'] ?? '');
    $kindLabel    = $KIND_LABELS[$kindKey] ?? $kindKey;
?>
      <div class="rc-item rc-usage-<?= rc_e($usageType) ?>">
        <div class="rc-item-header">
          <div class="rc-item-title"><?= rc_e((string)($r['title'] ?? '')) ?></div>
          <div class="rc-item-badges">
            <?php if ($kindLabel !== ''): ?>
              <span class="rc-kind-badge rc-kind-<?= rc_e($kindKey) ?>"><?= rc_e($kindLabel) ?></span>
            <?php endif; ?>
            <?php if ($usageLabel !== ''): ?>
              <span class="rc-usage-badge rc-usage-badge-<?= rc_e($usageType) ?>"><?= rc_e($usageLabel) ?></span>
            <?php endif; ?>
            <span class="rc-storage-badge rc-storage-<?= rc_e($storageType) ?>"><?= rc_e($storageLabel) ?></span>
          </div>
        </div>
        <?php if ($usageNote !== ''): ?>
          <div class="rc-usage-note rc-usage-note-<?= rc_e($usageType) ?>"><?= rc_e($usageNote) ?></div>
        <?php endif; ?>
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
                class="rc-btn primary rc-copy-btn"
                data-path="<?= rc_e($path) ?>"
              >경로 복사</button>
            <?php elseif ($storageType === 'url' && $url !== ''): ?>
              <a href="<?= rc_e($url) ?>" target="_blank" rel="noopener" class="rc-btn primary">열기</a>
            <?php elseif ($storageType === 'google_drive'): ?>
              <?php $driveId = (string)($r['drive_file_id'] ?? ''); ?>
              <a href="<?= $driveId !== '' ? 'https://drive.google.com/file/d/' . rc_e($driveId) . '/view' : 'google_drive.php' ?>"
                 target="_blank" rel="noopener" class="rc-btn primary">Google Drive 열기</a>
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
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(function() {
      rcToast('경로가 복사되었습니다.', 'success');
    }, function() {
      rcCopyFallback(text);
    });
    return;
  }
  rcCopyFallback(text);
}
function rcCopyFallback(text) {
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
  rcToast(ok ? '경로가 복사되었습니다.' : '경로 복사에 실패했습니다.', ok ? 'success' : 'error');
}
function rcToast(msg, type) {
  var el = document.getElementById('rc-toast');
  el.textContent = msg;
  el.className   = type;
  el.style.display = 'block';
  setTimeout(function() { el.style.display = 'none'; }, 2200);
}
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.rc-copy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      rcCopy(btn.dataset.path);
    });
  });

  var form = document.getElementById('rc-form');
  document.querySelectorAll('[data-filter]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var filterName = btn.dataset.filter;
      var filterVal  = btn.dataset.value;
      form.querySelector('input[name="' + filterName + '"]').value = filterVal;
      form.submit();
    });
  });
});
</script>
</body>
</html>
