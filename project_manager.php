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
  <div class="dc-hero">
    <h1>프로젝트 관리</h1>
    <p>등록된 프로젝트 목록입니다. 프로젝트명을 클릭하면 메모를 확인하거나 수정할 수 있습니다.</p>
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

<script>
const MEMOS     = <?= $projectsJson ?>;
const PM_CSRF   = <?= json_encode($pmCsrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
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
