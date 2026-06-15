<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/* ── 실험 목록 로드 ── */
$labFile = DC_DATA_DIR . '/lab_experiments.json';
$experiments = [];
$loadError = false;
if (!is_file($labFile)) {
    $loadError = true;
} else {
    $raw = json_decode(file_get_contents($labFile), true);
    if (!is_array($raw)) {
        $loadError = true;
    } else {
        $experiments = $raw;
    }
}

$jsData = json_encode($experiments, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

/* ── ws-lab 프롬프트 로드 ── */
require_once __DIR__ . '/includes/ai_workspace_prompt.php';
$wsPromptText = buildAiWorkspacePrompt('ws-lab');
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
            if (($_ws['id'] ?? '') === 'ws-lab') {
                $wsLaunchAllowed = (array)($_ws['ai_tools'] ?? []);
                break;
            }
        }
    }
}
unset($_wsFile, $_wsAll, $_ws);

$urlQ    = trim((string)($_GET['q']    ?? ''));
$urlId   = trim((string)($_GET['id']   ?? ''));
$urlView = trim((string)($_GET['view'] ?? ''));
$jsQ     = json_encode($urlQ,    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
$jsId    = json_encode($urlId,   JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
$jsView  = json_encode($urlView, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>실험실 — 개발센터</title>
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
    <li><a href="lab.php" class="active">실험실</a></li>
    <li><a href="prompts.php">프롬프트</a></li>
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
        <h1>실험실</h1>
        <p>디자인 시도, 자동화, 실행파일, API 테스트, 아이디어를 기록하고 추적합니다.</p>
      </div>
      <?php if ($wsPromptText !== ''): ?>
      <button class="ws-prompt-btn" id="ws-prompt-copy" title="실험실 AI 시작 프롬프트를 클립보드에 복사합니다">
        <span class="ws-prompt-btn-icon">🤖</span> 실험실 AI 프롬프트 복사
      </button>
      <?php endif; ?>
      <?php if (in_array('codex', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-lab" data-action="codex">⚡ Codex 열기</button>
      <?php endif; ?>
      <?php if (in_array('claude', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-lab" data-action="claude">🤖 Claude 열기</button>
      <?php endif; ?>
      <?php if (in_array('vscode-codex', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-lab" data-action="vscode-codex">🖥️ VSCode + Codex</button>
      <?php endif; ?>
      <?php if (in_array('vscode-claude', $wsLaunchAllowed, true)): ?>
      <button class="ws-launch-btn" data-workspace="ws-lab" data-action="vscode-claude">🖥️ VSCode + Claude</button>
      <?php endif; ?>
    </div>
  </div>
  <span id="ws-launch-msg" class="ws-launch-msg"></span>

<?php if ($loadError): ?>
  <div class="dc-alert dc-alert-warn">
    lab_experiments.json을 불러올 수 없습니다. 파일이 없거나 JSON 형식이 잘못되었습니다.
  </div>
<?php else: ?>

  <?php if ($urlQ !== ''): ?>
  <p class="kn-url-hint">URL 검색어 적용: <code><?= htmlspecialchars($urlQ, ENT_QUOTES, 'UTF-8') ?></code></p>
  <?php endif; ?>

  <div class="lb-view-tabs" id="lb-view-tabs">
    <button class="lb-view-tab active" data-view="active">진행중</button>
    <button class="lb-view-tab" data-view="all">전체</button>
    <button class="lb-view-tab" data-view="done">완료/보관</button>
  </div>

  <div class="kn-toolbar">
    <input type="search" id="lb-search" class="kn-search-input"
           placeholder="검색 (제목, 카테고리, 태그, 내용 …)" autocomplete="off">
    <div class="kn-filters" id="lb-cat-filters">
      <button class="kn-filter active" data-cat="">전체</button>
    </div>
  </div>
  <div class="lb-subcat-row" id="lb-subcat-row"></div>

  <div class="kn-layout">
    <div class="kn-list" id="lb-list"></div>
    <aside class="kn-detail" id="lb-detail">
      <div class="kn-detail-empty">
        <div class="kn-detail-empty-icon">🔬</div>
        <p>왼쪽에서 실험을 선택하세요.</p>
      </div>
    </aside>
  </div>

<?php endif; ?>

  <footer class="dc-footer">
    <?= htmlspecialchars(DEV_CENTER_NAME, ENT_QUOTES, 'UTF-8') ?>
    v<?= htmlspecialchars(DEV_CENTER_VERSION, ENT_QUOTES, 'UTF-8') ?> &mdash;
    로컬 개발 전용. 외부 공개 금지.
  </footer>

</main>

<?php if (!$loadError): ?>
<script>
(function () {
  const DATA = <?= $jsData ?>;

  const CAT_LABELS = {
    design:     '디자인',
    automation: '자동화',
    executable: '실행파일/프로그램',
    api:        'API',
    idea:       '아이디어',
  };

  const STATUS_CLASS = {
    done:          'lb-status-done',
    archived:      'lb-status-archived',
    'in-progress': 'lb-status-progress',
    idea:          'lb-status-idea',
    paused:        'lb-status-paused',
  };

  const STATUS_LABEL = {
    done:          '완료',
    archived:      '보관됨',
    'in-progress': '진행중',
    idea:          '아이디어',
    paused:        '보류',
  };

  const APPLY_CLASS = {
    applied:   'lb-apply-applied',
    reviewing: 'lb-apply-reviewing',
    pending:   'lb-apply-pending',
    rejected:  'lb-apply-rejected',
    deferred:  'lb-apply-deferred',
  };

  const APPLY_LABEL = {
    applied:   '적용됨',
    reviewing: '검토중',
    pending:   '대기',
    rejected:  '반려',
    deferred:  '보류',
  };

  /* ── 카테고리 버튼 생성 ── */
  const cats = [...new Set(DATA.map(d => d.category).filter(Boolean))];
  const catBar = document.getElementById('lb-cat-filters');
  cats.forEach(cat => {
    const btn = document.createElement('button');
    btn.className = 'kn-filter';
    btn.dataset.cat = cat;
    btn.textContent = CAT_LABELS[cat] || cat;
    catBar.appendChild(btn);
  });

  let activeCat    = '';
  let activeSubcat = '';
  let activeId     = null;
  let activeView   = 'active';

  /* ── 뷰 탭 (진행중 / 전체 / 완료·보관) ── */
  const viewTabs = document.getElementById('lb-view-tabs');
  viewTabs.addEventListener('click', e => {
    const btn = e.target.closest('.lb-view-tab');
    if (!btn) return;
    activeView = btn.dataset.view;
    viewTabs.querySelectorAll('.lb-view-tab').forEach(b => b.classList.toggle('active', b === btn));
    render();
  });

  catBar.addEventListener('click', e => {
    const btn = e.target.closest('.kn-filter');
    if (!btn) return;
    activeCat    = btn.dataset.cat;
    activeSubcat = '';
    catBar.querySelectorAll('.kn-filter').forEach(b => b.classList.toggle('active', b === btn));
    renderSubcats();
    render();
  });

  function renderSubcats() {
    const row = document.getElementById('lb-subcat-row');
    const subcats = [...new Set(
      DATA
        .filter(d => !activeCat || d.category === activeCat)
        .map(d => d.subcategory)
        .filter(Boolean)
    )];
    if (subcats.length === 0) { row.innerHTML = ''; return; }
    row.innerHTML = '';
    const all = document.createElement('button');
    all.className = 'lb-subcat active';
    all.dataset.sub = '';
    all.textContent = '전체';
    row.appendChild(all);
    subcats.forEach(sub => {
      const btn = document.createElement('button');
      btn.className = 'lb-subcat';
      btn.dataset.sub = sub;
      btn.textContent = sub;
      row.appendChild(btn);
    });
    row.addEventListener('click', onSubcat);
  }

  function onSubcat(e) {
    const btn = e.target.closest('.lb-subcat');
    if (!btn) return;
    activeSubcat = btn.dataset.sub;
    document.querySelectorAll('.lb-subcat').forEach(b =>
      b.classList.toggle('active', b === btn)
    );
    render();
  }

  document.getElementById('lb-search').addEventListener('input', render);

  function isDone(d) {
    return d.status === 'done' || d.status === 'archived' || d.status === 'completed';
  }

  function haystack(d) {
    return [
      d.id, d.category, d.subcategory, d.title, d.target,
      d.summary, d.status, d.apply_status, d.note,
      (d.tags || []).join(' '),
    ].map(v => (v || '').toLowerCase()).join(' ');
  }

  function render() {
    const q = document.getElementById('lb-search').value.toLowerCase().trim();
    const list = document.getElementById('lb-list');
    list.innerHTML = '';

    const filtered = DATA.filter(d => {
      if (activeView === 'active' && isDone(d))  return false;
      if (activeView === 'done'   && !isDone(d)) return false;
      if (activeCat    && d.category    !== activeCat)    return false;
      if (activeSubcat && d.subcategory !== activeSubcat) return false;
      if (q && !haystack(d).includes(q)) return false;
      return true;
    });

    if (filtered.length === 0) {
      list.innerHTML = '<div class="kn-empty">검색 결과가 없습니다.</div>';
      return null;
    }

    filtered.forEach(d => {
      const card = document.createElement('div');
      card.className = 'kn-item' + (d.id === activeId ? ' active' : '') + (isDone(d) && activeView === 'all' ? ' kn-item--muted' : '');
      card.dataset.id = d.id;
      const tags  = (d.tags || []).slice(0, 4).map(t => `<span class="kn-tag">${esc(t)}</span>`).join('');
      const sCls  = STATUS_CLASS[d.status]  || 'lb-status-idea';
      const sLbl  = STATUS_LABEL[d.status]  || esc(d.status || '');
      const pct   = Math.min(100, Math.max(0, Number(d.completion) || 0));
      card.innerHTML = `
        <div class="kn-item-head">
          <span class="kn-item-title">${esc(d.title)}</span>
          <span class="lb-status ${esc(sCls)}">${sLbl}</span>
        </div>
        <div class="lb-card-meta">
          <span class="lb-cat-chip">${esc(CAT_LABELS[d.category] || d.category)}</span>
          ${d.subcategory ? `<span class="lb-subcat-chip">${esc(d.subcategory)}</span>` : ''}
          <span class="lb-priority">P${esc(String(d.priority_score || 0))}</span>
        </div>
        <div class="lb-progress-wrap">
          <div class="lb-progress"><div class="lb-progress-bar" style="width:${pct}%"></div></div>
          <span class="lb-pct">${pct}%</span>
        </div>
        <div class="kn-item-tags">${tags}</div>
        <div class="kn-item-summary">${esc(d.summary || '')}</div>
      `;
      card.addEventListener('click', () => showDetail(d));
      list.appendChild(card);
    });

    return filtered[0];
  }

  function showDetail(d) {
    activeId = d.id;
    document.querySelectorAll('.kn-item').forEach(el =>
      el.classList.toggle('active', el.dataset.id === activeId)
    );

    const tags   = (d.tags || []).map(t => `<span class="kn-tag">${esc(t)}</span>`).join('');
    const pct    = Math.min(100, Math.max(0, Number(d.completion) || 0));
    const sCls   = STATUS_CLASS[d.status]       || 'lb-status-idea';
    const sLbl   = STATUS_LABEL[d.status]       || esc(d.status || '');
    const aCls   = APPLY_CLASS[d.apply_status]  || 'lb-apply-pending';
    const aLbl   = APPLY_LABEL[d.apply_status]  || esc(d.apply_status || '');
    const panel  = document.getElementById('lb-detail');

    const permalink = location.origin + location.pathname + '?id=' + encodeURIComponent(d.id);
    panel.innerHTML = `
      <div class="kn-detail-inner">
        <div class="kn-detail-header">
          <h2 class="kn-detail-title">${esc(d.title)}</h2>
          <span class="lb-status ${esc(sCls)}">${sLbl}</span>
          <button class="lb-permalink-btn" data-link="${esc(permalink)}" title="링크 복사">🔗</button>
        </div>

        <div class="lb-badge-row">
          <span class="lb-cat-chip">${esc(CAT_LABELS[d.category] || d.category)}</span>
          ${d.subcategory ? `<span class="lb-subcat-chip">${esc(d.subcategory)}</span>` : ''}
          <span class="lb-apply ${esc(aCls)}">${aLbl}</span>
          <span class="lb-priority-badge">우선순위 ${esc(String(d.priority_score || 0))}</span>
        </div>

        <div class="kn-detail-tags">${tags}</div>

        <div class="lb-detail-progress">
          <div class="lb-detail-progress-label">
            <span>진행률</span><span>${pct}%</span>
          </div>
          <div class="lb-progress lb-progress-lg">
            <div class="lb-progress-bar" style="width:${pct}%"></div>
          </div>
        </div>

        <div class="lb-meta-grid">
          <div class="lb-meta-cell"><span class="kn-detail-label">대상</span><span>${esc(d.target || '—')}</span></div>
          <div class="lb-meta-cell"><span class="kn-detail-label">ID</span><code class="kn-code">${esc(d.id)}</code></div>
          ${d.created_at ? `<div class="lb-meta-cell"><span class="kn-detail-label">작성일</span><span>${esc(d.created_at)}</span></div>` : ''}
          ${d.updated_at ? `<div class="lb-meta-cell"><span class="kn-detail-label">수정일</span><span>${esc(d.updated_at)}</span></div>` : ''}
        </div>

        ${d.summary ? `<div class="kn-detail-section">
          <div class="kn-detail-label">요약</div>
          <div class="kn-detail-text">${esc(d.summary)}</div>
        </div>` : ''}

        ${d.note ? `<div class="kn-detail-section">
          <div class="kn-detail-label">노트</div>
          <div class="kn-detail-text lb-note">${esc(d.note)}</div>
        </div>` : ''}

        ${d.preview_path ? `<div class="kn-detail-section">
          <div class="kn-detail-label">프리뷰 경로</div>
          <code class="kn-code lb-preview-path">${esc(d.preview_path)}</code>
        </div>` : ''}
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

  /* ── 퍼머링크 복사 버튼 ── */
  document.getElementById('lb-detail').addEventListener('click', e => {
    const btn = e.target.closest('.lb-permalink-btn');
    if (!btn) return;
    const link = btn.dataset.link;
    navigator.clipboard
      ? navigator.clipboard.writeText(link).then(() => flashBtn(btn))
      : (document.execCommand('copy', false, link), flashBtn(btn));
  });
  function flashBtn(btn) {
    btn.textContent = '✅';
    setTimeout(() => { btn.textContent = '🔗'; }, 1500);
  }

  const URL_Q    = <?= $jsQ ?>;
  const URL_ID   = <?= $jsId ?>;
  const URL_VIEW = <?= $jsView ?>;

  /* ?view= 로 탭 직접 진입 */
  if (['active', 'all', 'done'].includes(URL_VIEW)) {
    activeView = URL_VIEW;
    viewTabs.querySelectorAll('.lb-view-tab').forEach(b =>
      b.classList.toggle('active', b.dataset.view === activeView)
    );
  }

  /* ?q= 검색어 적용 */
  if (URL_Q !== '') {
    document.getElementById('lb-search').value = URL_Q;
    if (activeView === 'active') {
      activeView = 'all';
      viewTabs.querySelectorAll('.lb-view-tab').forEach(b =>
        b.classList.toggle('active', b.dataset.view === 'all')
      );
    }
  }

  renderSubcats();
  const firstResult = render();

  /* ?id= 딥링크 — 특정 항목 바로 열기 */
  if (URL_ID !== '') {
    const target = DATA.find(d => d.id === URL_ID);
    if (target) {
      if (isDone(target) && activeView === 'active') {
        activeView = 'done';
        viewTabs.querySelectorAll('.lb-view-tab').forEach(b =>
          b.classList.toggle('active', b.dataset.view === 'done')
        );
        render();
      }
      showDetail(target);
      const el = document.querySelector('.kn-item[data-id="' + CSS.escape(URL_ID) + '"]');
      if (el) el.scrollIntoView({ block: 'nearest' });
    }
  } else if (URL_Q !== '' && firstResult) {
    showDetail(firstResult);
  }
})();

</script>
<?php endif; ?>
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
