<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/google_drive.php';

session_start();
if (empty($_SESSION['dc_csrf_token'])) {
    $_SESSION['dc_csrf_token'] = bin2hex(random_bytes(32));
}

$folderId = trim((string) ($_GET['folder'] ?? ''));
$flash    = null;   // ['type'=>'ok|error', 'msg'=>...]

// ── 업로드 처리 (POST) ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['dc_csrf_token'] ?? '', $csrf)) {
        $flash = ['type' => 'error', 'msg' => 'CSRF 검증 실패. 새로고침 후 다시 시도하세요.'];
    } else {
        $targetFolder = trim((string) ($_POST['folder'] ?? ''));
        $res = gd_upload_file($_FILES['file'] ?? [], $targetFolder);
        if ($res['ok']) {
            $flash = ['type' => 'ok', 'msg' => '업로드 완료: ' . (string) ($res['file']['name'] ?? '') . ' (id: ' . (string) ($res['file']['id'] ?? '') . ')'];
        } else {
            $flash = ['type' => 'error', 'msg' => (string) $res['err']];
        }
        $folderId = $targetFolder;
    }
}

// ── 연결 상태 + 목록 ────────────────────────────────────────────────────────
$configured = gd_configured();
$conn       = $configured ? gd_access_token() : ['ok' => false, 'err' => '자격증명 미설정'];
$list       = ['ok' => false, 'files' => [], 'err' => ''];
if ($conn['ok']) {
    $list = gd_list_files($folderId);
}

function gd_e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function gd_size(?int $bytes): string {
    if ($bytes === null) { return '—'; }
    $u = ['B', 'KB', 'MB', 'GB']; $i = 0; $n = (float) $bytes;
    while ($n >= 1024 && $i < count($u) - 1) { $n /= 1024; $i++; }
    return round($n, $n < 10 && $i > 0 ? 1 : 0) . $u[$i];
}
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Google Drive — <?= gd_e(DEV_CENTER_NAME) ?></title>
  <?php $cssVer = is_file(__DIR__ . '/assets/css/dev_center.css') ? filemtime(__DIR__ . '/assets/css/dev_center.css') : time(); ?>
  <link rel="stylesheet" href="assets/css/dev_center.css?v=<?= $cssVer ?>">
</head>
<body>

<?php $activeNav = 'google_drive'; require __DIR__ . '/includes/dev_center_nav.php'; ?>

<main class="dc-main">
  <div class="dc-hero">
    <div class="dc-hero-row">
      <div>
        <h1>Google Drive</h1>
        <p>자료실 파일을 구글 드라이브에 업로드하고 폴더 내용을 확인합니다.</p>
      </div>
      <div>
        <?php if ($conn['ok']): ?>
          <span class="dc-badge-env" style="background:#1f7a3d">연결됨</span>
        <?php else: ?>
          <span class="dc-badge-env" style="background:#a33">연결 안 됨</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php if ($flash !== null): ?>
  <div class="dc-alert dc-alert-<?= $flash['type'] === 'ok' ? 'ok' : 'error' ?>"><?= gd_e($flash['msg']) ?></div>
<?php endif; ?>

<?php if (!$configured): ?>
  <div class="dc-alert dc-alert-error">
    자격증명이 없습니다. <code>data/google_drive_credentials.local.json</code> 에
    <code>client_id</code>, <code>client_secret</code>, <code>refresh_token</code> 을 설정하세요.
  </div>
<?php elseif (!$conn['ok']): ?>
  <div class="dc-alert dc-alert-error">구글 연결 실패: <?= gd_e((string) ($conn['err'] ?? '')) ?></div>
<?php endif; ?>

  <div class="rc-wrap">
    <!-- 폴더 선택 + 새로고침 -->
    <form method="get" class="kn-toolbar">
      <input
        type="text" name="folder"
        value="<?= gd_e($folderId) ?>"
        placeholder="폴더 ID (비우면 내 드라이브 루트)"
        class="kn-search-input">
      <button type="submit" class="rc-btn primary">목록 보기</button>
    </form>

    <!-- 업로드 -->
    <form method="post" enctype="multipart/form-data" class="kn-toolbar" style="margin-top:10px">
      <input type="hidden" name="csrf_token" value="<?= gd_e($_SESSION['dc_csrf_token']) ?>">
      <input type="hidden" name="folder" value="<?= gd_e($folderId) ?>">
      <input type="file" name="file" required class="kn-search-input">
      <button type="submit" class="rc-btn primary">이 폴더에 업로드</button>
    </form>

<?php if ($conn['ok']): ?>
<?php if (!$list['ok']): ?>
    <div class="dc-alert dc-alert-error"><?= gd_e((string) ($list['err'] ?? '목록을 불러오지 못했습니다.')) ?></div>
<?php else: ?>
    <div class="rc-count"><?= count($list['files']) ?>개 항목<?= $folderId !== '' ? ' (폴더 ' . gd_e($folderId) . ')' : ' (내 드라이브 루트)' ?></div>
    <div class="rc-list">
<?php if (empty($list['files'])): ?>
      <div class="rc-empty">이 폴더에 파일이 없습니다.</div>
<?php else: ?>
<?php foreach ($list['files'] as $f):
    $isFolder = ($f['mimeType'] ?? '') === 'application/vnd.google-apps.folder';
    $fid      = (string) ($f['id'] ?? '');
?>
      <div class="rc-item">
        <div class="rc-item-header">
          <div class="rc-item-title"><?= $isFolder ? '📁 ' : '📄 ' ?><?= gd_e((string) ($f['name'] ?? '')) ?></div>
          <div class="rc-item-badges">
            <span class="rc-storage-badge"><?= gd_e($isFolder ? '폴더' : gd_size(isset($f['size']) ? (int) $f['size'] : null)) ?></span>
          </div>
        </div>
        <div class="rc-item-meta">
          <span class="rc-pid">id: <?= gd_e($fid) ?></span>
          <?php if (!empty($f['modifiedTime'])): ?>
            <span class="rc-cat"><?= gd_e(substr((string) $f['modifiedTime'], 0, 10)) ?></span>
          <?php endif; ?>
        </div>
        <div class="rc-item-footer">
          <div class="rc-item-actions">
            <?php if ($isFolder): ?>
              <a href="?folder=<?= urlencode($fid) ?>" class="rc-btn primary">열기</a>
            <?php endif; ?>
            <?php if (!empty($f['webViewLink'])): ?>
              <a href="<?= gd_e((string) $f['webViewLink']) ?>" target="_blank" rel="noopener" class="rc-btn primary">Drive에서 보기</a>
            <?php endif; ?>
            <button type="button" class="rc-btn rc-copy-btn" data-path="<?= gd_e($fid) ?>">ID 복사</button>
          </div>
        </div>
      </div>
<?php endforeach; ?>
<?php endif; ?>
    </div>
<?php endif; ?>
<?php endif; ?>
  </div>

  <footer class="dc-footer">
    <?= gd_e(DEV_CENTER_NAME) ?> &mdash; 로컬 개발 전용. 외부 공개 금지.
  </footer>
</main>

<div id="rc-toast"></div>
<script>
document.addEventListener('click', function (e) {
  var btn = e.target.closest('.rc-copy-btn');
  if (!btn) return;
  var text = btn.getAttribute('data-path') || '';
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(function () {
      var t = document.getElementById('rc-toast');
      if (t) { t.textContent = '복사됨: ' + text; t.classList.add('show'); setTimeout(function(){ t.classList.remove('show'); }, 1500); }
    });
  }
});
</script>
</body>
</html>
