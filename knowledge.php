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

/* 프로젝트 이름 맵 (id → name) */
$_pMap = [];
$_pFile = DC_DATA_DIR . '/projects.json';
if (is_file($_pFile)) {
    $_pd = json_decode(file_get_contents($_pFile), true);
    if (is_array($_pd)) {
        foreach ($_pd as $_p) {
            if (!empty($_p['id'])) {
                $_pMap[$_p['id']] = (string)($_p['name'] ?? $_p['id']);
            }
        }
    }
}
$jsProjects = json_encode($_pMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
unset($_pFile, $_pd, $_p, $_pMap);

/* 반영 요청 데이터 */
$_arFile = DC_DATA_DIR . '/apply_requests.json';
$_arRaw  = is_file($_arFile) ? (json_decode(file_get_contents($_arFile), true) ?? []) : [];
$jsApplyRequests = json_encode(is_array($_arRaw) ? $_arRaw : [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
unset($_arFile, $_arRaw);

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

/* ── POST: applied_projects 저장 ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_applied_projects') {
    header('Content-Type: application/json; charset=utf-8');

    // CSRF
    $sessionToken = (string)($_SESSION['dc_csrf_token'] ?? '');
    $postToken    = (string)($_POST['csrf_token'] ?? '');
    if ($sessionToken === '' || !hash_equals($sessionToken, $postToken)) {
        echo json_encode(['ok' => false, 'msg' => 'CSRF 검증 실패.'], JSON_UNESCAPED_UNICODE); exit;
    }

    $featId      = trim((string)($_POST['feature_id'] ?? ''));
    $rawProjects = $_POST['applied_projects'] ?? [];
    if (!is_array($rawProjects)) $rawProjects = [];

    // 허용 project id 목록 로드
    $_pFile2 = DC_DATA_DIR . '/projects.json';
    $_pd2    = is_file($_pFile2) ? (json_decode(file_get_contents($_pFile2), true) ?? []) : [];
    $allowedPids = array_column(is_array($_pd2) ? $_pd2 : [], 'id');

    // 입력 검증: 빈 문자열 제거 + 허용 목록 필터
    $newApplied = array_values(array_filter(
        array_unique(array_map('strval', $rawProjects)),
        fn($pid) => $pid !== '' && in_array($pid, $allowedPids, true)
    ));

    // features.json strict 로드
    $featRaw = file_get_contents($featuresFile);
    $featArr = json_decode($featRaw, true);
    if (!is_array($featArr)) {
        echo json_encode(['ok' => false, 'msg' => 'features.json 읽기 실패.'], JSON_UNESCAPED_UNICODE); exit;
    }

    $found = false;
    foreach ($featArr as &$f) {
        if (($f['id'] ?? '') === $featId) {
            $f['applied_projects'] = $newApplied;
            $found = true;
            break;
        }
    }
    unset($f);

    if (!$found) {
        echo json_encode(['ok' => false, 'msg' => '기능을 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE); exit;
    }

    $jsonOut = json_encode($featArr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonOut === false || file_put_contents($featuresFile, $jsonOut, LOCK_EX) === false) {
        echo json_encode(['ok' => false, 'msg' => '저장 실패.'], JSON_UNESCAPED_UNICODE); exit;
    }

    echo json_encode(['ok' => true, 'applied_projects' => $newApplied], JSON_UNESCAPED_UNICODE);
    exit;
}
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

<?php $activeNav = 'knowledge'; require __DIR__ . '/includes/dev_center_nav.php'; ?>

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
  const DATA            = <?= $jsData ?>;
  const PROJECTS        = <?= $jsProjects ?>;
  const APPLY_REQUESTS  = <?= $jsApplyRequests ?>;
  const KN_CSRF         = <?= json_encode($wsLaunchCsrf, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

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

        <div class="kn-detail-section kn-apply-section">
          <div class="kn-detail-label">적용 현황</div>
          ${buildApplySection(f, r)}
        </div>

        <div class="kn-detail-footer">
          <span>ID: <code>${esc(f.id)}</code></span>
          ${r.updated_at ? `<span>업데이트: ${esc(r.updated_at)}</span>` : ''}
        </div>
      </div>
    `;

    // data-fid 기반 이벤트 리스너 (onclick 인라인 대체)
    panel.querySelectorAll('.kn-ap-edit-btn').forEach(btn =>
      btn.addEventListener('click', () => openApEdit(btn.dataset.fid))
    );
    panel.querySelectorAll('.kn-ap-save-btn').forEach(btn =>
      btn.addEventListener('click', () => saveApplied(btn.dataset.fid))
    );
    panel.querySelectorAll('.kn-ap-cancel-btn').forEach(btn =>
      btn.addEventListener('click', () => closeApEdit(btn.dataset.fid))
    );
  }

  function buildApplySection(f, r) {
    const srcId   = r.project_id || '';
    const srcName = srcId ? (PROJECTS[srcId] || srcId) : '—';
    const srcHtml = `<div class="kn-apply-row"><span class="kn-apply-label">원본 프로젝트</span><span class="kn-apply-val">${esc(srcName)}</span></div>`;

    const appliedIds = Array.isArray(f.applied_projects) ? f.applied_projects : [];
    const chipsHtml = appliedIds.length > 0
      ? appliedIds.map(pid => `<span class="kn-applied-chip">${esc(PROJECTS[pid] || pid)}</span>`).join('')
      : `<span class="kn-apply-empty-inline">—</span>`;

    const editBtnHtml = `<button type="button" class="kn-ap-edit-btn" data-fid="${esc(f.id)}">편집</button>`;

    // 체크박스 목록 (모든 프로젝트)
    const checkboxes = Object.entries(PROJECTS).map(([pid, pname]) => {
      const checked = appliedIds.includes(pid) ? 'checked' : '';
      return `<label class="kn-ap-check"><input type="checkbox" value="${esc(pid)}" ${checked}> ${esc(pname)}</label>`;
    }).join('');

    const editAreaHtml = `
      <div class="kn-ap-edit-area" id="kn-ap-edit-${esc(f.id)}" style="display:none">
        <div class="kn-ap-checks">${checkboxes || '<span style="color:var(--text3);font-size:12px">등록된 프로젝트 없음</span>'}</div>
        <div class="kn-ap-edit-actions">
          <button type="button" class="kn-ap-save-btn" data-fid="${esc(f.id)}">저장</button>
          <button type="button" class="kn-ap-cancel-btn" data-fid="${esc(f.id)}">취소</button>
          <span class="kn-ap-msg" id="kn-ap-msg-${esc(f.id)}"></span>
        </div>
      </div>`;

    const appliedHtml = `<div class="kn-apply-row kn-ap-row" id="kn-ap-row-${esc(f.id)}">
      <span class="kn-apply-label">적용된 프로젝트</span>
      <span class="kn-apply-val">
        <span class="kn-applied-chips" id="kn-ap-chips-${esc(f.id)}">${chipsHtml}</span>
        ${editBtnHtml}
      </span>
      ${editAreaHtml}
    </div>`;

    const matched = APPLY_REQUESTS.filter(a => a.record_id === f.id);
    let arHtml = '';
    if (matched.length > 0) {
      arHtml = `<div class="kn-apply-row kn-apply-requests-row"><span class="kn-apply-label">반영 요청</span><span class="kn-apply-val">` +
        matched.map(a => {
          const tName  = PROJECTS[a.target_project_id] || a.target_project_id || '—';
          const status = a.status || '—';
          return `<span class="kn-apply-status kn-apply-status-${esc(status)}">${esc(tName)} · ${esc(status)}</span>`;
        }).join('') +
        `</span></div>`;
    }
    return srcHtml + appliedHtml + arHtml;
  }

  // ── applied_projects 편집 ──────────────────────────────────
  let _apCurrentFid = null;

  function openApEdit(fid) {
    if (_apCurrentFid && _apCurrentFid !== fid) closeApEdit(_apCurrentFid);
    _apCurrentFid = fid;
    const editEl  = document.getElementById(`kn-ap-edit-${fid}`);
    const chipsEl = document.getElementById(`kn-ap-chips-${fid}`);
    const btnEl   = document.querySelector(`#kn-ap-row-${fid} .kn-ap-edit-btn`);
    if (editEl)  editEl.style.display  = 'block';
    if (chipsEl) chipsEl.style.display = 'none';
    if (btnEl)   btnEl.style.display   = 'none';
  }

  function closeApEdit(fid) {
    const edit = document.getElementById(`kn-ap-edit-${fid}`);
    if (edit) edit.style.display = 'none';
    const chips = document.getElementById(`kn-ap-chips-${fid}`);
    if (chips) chips.style.display = '';
    const btn = document.querySelector(`#kn-ap-row-${fid} .kn-ap-edit-btn`);
    if (btn) btn.style.display = '';
    const msg = document.getElementById(`kn-ap-msg-${fid}`);
    if (msg) msg.textContent = '';
    if (_apCurrentFid === fid) _apCurrentFid = null;
  }

  async function saveApplied(fid) {
    const editArea = document.getElementById(`kn-ap-edit-${fid}`);
    const checked  = [...editArea.querySelectorAll('input[type=checkbox]:checked')].map(el => el.value);
    const msgEl    = document.getElementById(`kn-ap-msg-${fid}`);
    msgEl.textContent = '저장 중…';
    msgEl.className   = 'kn-ap-msg';

    const fd = new FormData();
    fd.append('action',     'save_applied_projects');
    fd.append('csrf_token', KN_CSRF);
    fd.append('feature_id', fid);
    checked.forEach(pid => fd.append('applied_projects[]', pid));

    try {
      const res  = await fetch('knowledge.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.ok) {
        // DATA 배열 업데이트
        const entry = DATA.find(d => d.feat.id === fid);
        if (entry) entry.feat.applied_projects = data.applied_projects;

        // chips 갱신
        const newIds = data.applied_projects;
        const chipsEl = document.getElementById(`kn-ap-chips-${fid}`);
        chipsEl.innerHTML = newIds.length > 0
          ? newIds.map(pid => `<span class="kn-applied-chip">${esc(PROJECTS[pid] || pid)}</span>`).join('')
          : `<span class="kn-apply-empty-inline">—</span>`;

        msgEl.textContent = '✅ 저장됐습니다.';
        msgEl.classList.add('kn-ap-msg-ok');
        setTimeout(() => closeApEdit(fid), 800);
      } else {
        msgEl.textContent = '❌ ' + (data.msg || '저장 실패');
        msgEl.classList.add('kn-ap-msg-err');
      }
    } catch {
      msgEl.textContent = '❌ 네트워크 오류';
      msgEl.classList.add('kn-ap-msg-err');
    }
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
