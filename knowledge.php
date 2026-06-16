<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/* ── 기능 목록 + 상세 레코드 로드 ── */
$featuresFile = DC_DATA_DIR . '/features.json';
$features = [];
if (is_file($featuresFile)) {
    $raw = json_decode(file_get_contents($featuresFile), true);
    if (is_array($raw)) {
        foreach ($raw as $feat) {
            $id = $feat['id'] ?? '';
            $recordFile = DC_DATA_DIR . '/records/' . $id . '.json';
            $record = [];
            if ($id !== '' && is_file($recordFile)) {
                $rec = json_decode(file_get_contents($recordFile), true);
                if (is_array($rec)) {
                    $record = $rec;
                }
            }
            $features[] = [
                'feat'   => $feat,
                'record' => $record,
            ];
        }
    }
}

/* JS에 넘길 데이터 (HTML 이스케이프 후 JSON) */
$jsData = json_encode($features, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

/* ── ws-knowledge 프롬프트 로드 ── */
require_once __DIR__ . '/includes/ai_workspace_prompt.php';
$wsPromptText = buildAiWorkspacePrompt('ws-knowledge');
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
            if (($_ws['id'] ?? '') === 'ws-knowledge') {
                $wsLaunchAllowed = (array)($_ws['ai_tools'] ?? []);
                break;
            }
        }
    }
}
unset($_wsFile, $_wsAll, $_ws);

/* URL q 파라미터 */
$urlQ = trim((string)($_GET['q'] ?? ''));
$jsQ  = json_encode($urlQ, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>기능 보관함 — 개발센터</title>
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
    <li><a href="knowledge.php" class="active">기능 보관함</a></li>
    <li><a href="lab.php">실험실</a></li>
    <li><a href="prompts.php">프롬프트</a></li>
    <li><a href="resources.php">자료실</a></li>
    <li><a href="settings.php">설정</a></li>
  </ul>
  <div class="dc-topnav-right">
    <span class="dc-badge-env">LOCAL</span>
  </div>
</nav>

<main class="dc-main">

  <div class="dc-hero">
    <div class="dc-hero-row">
      <div>
        <h1>기능 보관함</h1>
        <p>완성된 기능, 재사용 패턴, 구현 기록을 검색합니다.</p>
      </div>
      <div class="ws-action-group">
      <?php if ($wsPromptText !== ''): ?>
      <button class="ws-prompt-btn" id="ws-prompt-copy" title="기능 보관함 AI 시작 프롬프트를 클립보드에 복사합니다">
        <span class="ws-prompt-btn-icon">🤖</span> 기능 보관함 AI 프롬프트 복사
      </button>
      <?php endif; ?>
      <?php if (in_array('codex', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-knowledge" data-action="codex">⚡ Codex 열기</button>
      <?php endif; ?>
      <?php if (in_array('claude', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-knowledge" data-action="claude">🤖 Claude 열기</button>
      <?php endif; ?>
      <?php if (in_array('vscode-codex', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-knowledge" data-action="vscode-codex">🖥️ VSCode + Codex</button>
      <?php endif; ?>
      <?php if (in_array('vscode-claude', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-knowledge" data-action="vscode-claude">🖥️ VSCode + Claude</button>
      <?php endif; ?>
      </div>
    </div>
  </div>
  <span id="ws-launch-msg" class="ws-launch-msg"></span>

  <div class="kn-toolbar">
    <input type="search" id="kn-search" class="kn-search-input"
           placeholder="검색 (제목, 카테고리, 태그, 파일명 …)" autocomplete="off">
    <div class="kn-filters" id="kn-filters">
      <button class="kn-filter active" data-cat="">전체</button>
    </div>
  </div>

  <?php if ($urlQ !== ''): ?>
  <p class="kn-url-hint">URL 검색어 적용: <code><?= htmlspecialchars($urlQ, ENT_QUOTES, 'UTF-8') ?></code></p>
  <?php endif; ?>

  <div class="kn-layout" id="kn-layout">
    <div class="kn-list" id="kn-list">
      <!-- JS가 채운다 -->
    </div>
    <aside class="kn-detail" id="kn-detail">
      <div class="kn-detail-empty">
        <div class="kn-detail-empty-icon">📦</div>
        <p>왼쪽에서 기능을 선택하세요.</p>
      </div>
    </aside>
  </div>

  <footer class="dc-footer">
    <?= htmlspecialchars(DEV_CENTER_NAME, ENT_QUOTES, 'UTF-8') ?>
    v<?= htmlspecialchars(DEV_CENTER_VERSION, ENT_QUOTES, 'UTF-8') ?> &mdash;
    로컬 개발 전용. 외부 공개 금지.
  </footer>

</main>

<script>
(function () {
  const DATA = <?= $jsData ?>;

  /* ── 카테고리 목록 ── */
  const cats = [...new Set(DATA.map(d => d.feat.category).filter(Boolean))];
  const filterBar = document.getElementById('kn-filters');
  cats.forEach(cat => {
    const btn = document.createElement('button');
    btn.className = 'kn-filter';
    btn.dataset.cat = cat;
    btn.textContent = cat;
    filterBar.appendChild(btn);
  });

  let activeCat = '';
  let activeId  = null;

  filterBar.addEventListener('click', e => {
    const btn = e.target.closest('.kn-filter');
    if (!btn) return;
    activeCat = btn.dataset.cat;
    filterBar.querySelectorAll('.kn-filter').forEach(b => b.classList.toggle('active', b === btn));
    render();
  });

  document.getElementById('kn-search').addEventListener('input', render);

  function haystack(d) {
    const f = d.feat, r = d.record;
    return [
      f.id, f.title, f.category,
      (f.tags || []).join(' '),
      r.summary, r.prompt, r.review_notes, r.reuse_notes,
      (r.files || []).join(' '),
    ].map(v => (v || '').toLowerCase()).join(' ');
  }

  function render() {
    const q = document.getElementById('kn-search').value.toLowerCase().trim();
    const list = document.getElementById('kn-list');
    list.innerHTML = '';

    const filtered = DATA.filter(d => {
      if (activeCat && d.feat.category !== activeCat) return false;
      if (q && !haystack(d).includes(q)) return false;
      return true;
    });

    if (filtered.length === 0) {
      list.innerHTML = '<div class="kn-empty">검색 결과가 없습니다.</div>';
      return null;
    }

    filtered.forEach(d => {
      const f = d.feat;
      const card = document.createElement('div');
      card.className = 'kn-item' + (f.id === activeId ? ' active' : '');
      card.dataset.id = f.id;
      card.innerHTML = `
        <div class="kn-item-head">
          <span class="kn-item-title">${esc(f.title)}</span>
          <span class="kn-item-cat">${esc(f.category)}</span>
        </div>
        <div class="kn-item-tags">${(f.tags || []).map(t => `<span class="kn-tag">${esc(t)}</span>`).join('')}</div>
        <div class="kn-item-summary">${esc(d.record.summary || '')}</div>
      `;
      card.addEventListener('click', () => showDetail(d));
      list.appendChild(card);
    });

    return filtered[0];
  }

  function showDetail(d) {
    activeId = d.feat.id;
    document.querySelectorAll('.kn-item').forEach(el =>
      el.classList.toggle('active', el.dataset.id === activeId)
    );

    const f = d.feat, r = d.record;
    const panel = document.getElementById('kn-detail');

    const files = (r.files || []).map(f => `<code class="kn-code">${esc(f)}</code>`).join('');
    const tags  = (f.tags || []).map(t => `<span class="kn-tag">${esc(t)}</span>`).join('');
    const valid = (r.validation || []).map(v => `<li>${esc(v)}</li>`).join('');

    panel.innerHTML = `
      <div class="kn-detail-inner">
        <div class="kn-detail-header">
          <h2 class="kn-detail-title">${esc(f.title)}</h2>
          <span class="kn-item-cat">${esc(f.category)}</span>
          <span class="kn-status kn-status-${esc(f.status || 'active')}">${esc(f.status || '')}</span>
        </div>
        <div class="kn-detail-tags">${tags}</div>

        ${r.summary ? `<div class="kn-detail-section">
          <div class="kn-detail-label">요약</div>
          <div class="kn-detail-text">${esc(r.summary)}</div>
        </div>` : ''}

        ${files ? `<div class="kn-detail-section">
          <div class="kn-detail-label">관련 파일</div>
          <div class="kn-files">${files}</div>
        </div>` : ''}

        ${r.prompt ? `<div class="kn-detail-section">
          <div class="kn-detail-label">프롬프트 / 구현 힌트</div>
          <pre class="kn-pre">${esc(r.prompt)}</pre>
        </div>` : ''}

        ${r.review_notes ? `<div class="kn-detail-section">
          <div class="kn-detail-label">리뷰 노트</div>
          <div class="kn-detail-text kn-review">${esc(r.review_notes)}</div>
        </div>` : ''}

        ${r.reuse_notes ? `<div class="kn-detail-section">
          <div class="kn-detail-label">재사용 시 주의</div>
          <div class="kn-detail-text kn-reuse">${esc(r.reuse_notes)}</div>
        </div>` : ''}

        ${valid ? `<div class="kn-detail-section">
          <div class="kn-detail-label">검증 기록</div>
          <ul class="kn-valid-list">${valid}</ul>
        </div>` : ''}

        <div class="kn-detail-footer">
          <span>ID: <code>${esc(f.id)}</code></span>
          ${r.updated_at ? `<span>업데이트: ${esc(r.updated_at)}</span>` : ''}
        </div>
      </div>
    `;
  }

  function esc(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ── URL q 파라미터 초기 적용 ── */
  const URL_Q = <?= $jsQ ?>;
  if (URL_Q !== '') {
    document.getElementById('kn-search').value = URL_Q;
  }

  const firstResult = render();
  if (URL_Q !== '' && firstResult) {
    showDetail(firstResult);
  }
})();

</script>
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
