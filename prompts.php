<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/* ── 프롬프트 목록 로드 ── */
$promptsFile = DC_DATA_DIR . '/prompts.json';
$prompts = [];
$loadError = false;
if (!is_file($promptsFile)) {
    $loadError = true;
} else {
    $raw = json_decode(file_get_contents($promptsFile), true);
    if (!is_array($raw)) {
        $loadError = true;
    } else {
        $prompts = $raw;
    }
}

$jsData = json_encode($prompts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>프롬프트 — 개발센터</title>
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
    <li><a href="prompts.php" class="active">프롬프트</a></li>
    <li><a href="settings.php">설정</a></li>
  </ul>
  <div class="dc-topnav-right">
    <span class="dc-badge-env">LOCAL</span>
  </div>
</nav>

<main class="dc-main">

  <div class="dc-hero">
    <h1>프롬프트 라이브러리</h1>
    <p>Claude · WinCo · Codex · Git 워크플로에서 자주 쓰는 프롬프트를 검색하고 복사합니다.</p>
  </div>

<?php if ($loadError): ?>
  <div class="dc-alert dc-alert-warn">
    prompts.json을 불러올 수 없습니다. 파일이 없거나 JSON 형식이 잘못되었습니다.
  </div>
<?php else: ?>

  <div class="kn-toolbar">
    <input type="search" id="pm-search" class="kn-search-input"
           placeholder="검색 (제목, 대상, 태그, 내용 …)" autocomplete="off">
    <div class="kn-filters" id="pm-filters">
      <button class="kn-filter active" data-cat="">전체</button>
    </div>
  </div>

  <div class="kn-layout" id="pm-layout">
    <div class="kn-list" id="pm-list"></div>
    <aside class="kn-detail" id="pm-detail">
      <div class="kn-detail-empty">
        <div class="kn-detail-empty-icon">💬</div>
        <p>왼쪽에서 프롬프트를 선택하세요.</p>
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

  /* ── 카테고리 버튼 생성 ── */
  const cats = [...new Set(DATA.map(d => d.category).filter(Boolean))];
  const filterBar = document.getElementById('pm-filters');
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

  document.getElementById('pm-search').addEventListener('input', render);

  function haystack(d) {
    return [
      d.id, d.category, d.title, d.target,
      d.summary, d.prompt,
      (d.tags || []).join(' '),
    ].map(v => (v || '').toLowerCase()).join(' ');
  }

  function render() {
    const q = document.getElementById('pm-search').value.toLowerCase().trim();
    const list = document.getElementById('pm-list');
    list.innerHTML = '';

    const filtered = DATA.filter(d => {
      if (activeCat && d.category !== activeCat) return false;
      if (q && !haystack(d).includes(q)) return false;
      return true;
    });

    if (filtered.length === 0) {
      list.innerHTML = '<div class="kn-empty">검색 결과가 없습니다.</div>';
      return;
    }

    filtered.forEach(d => {
      const card = document.createElement('div');
      card.className = 'kn-item' + (d.id === activeId ? ' active' : '');
      card.dataset.id = d.id;
      const tags = (d.tags || []).map(t => `<span class="kn-tag">${esc(t)}</span>`).join('');
      card.innerHTML = `
        <div class="kn-item-head">
          <span class="kn-item-title">${esc(d.title)}</span>
          <span class="kn-item-cat">${esc(d.target || d.category)}</span>
        </div>
        <div class="kn-item-tags">${tags}</div>
        <div class="kn-item-summary">${esc(d.summary || '')}</div>
      `;
      card.addEventListener('click', () => showDetail(d));
      list.appendChild(card);
    });
  }

  function showDetail(d) {
    activeId = d.id;
    document.querySelectorAll('.kn-item').forEach(el =>
      el.classList.toggle('active', el.dataset.id === activeId)
    );

    const tags = (d.tags || []).map(t => `<span class="kn-tag">${esc(t)}</span>`).join('');
    const panel = document.getElementById('pm-detail');

    panel.innerHTML = `
      <div class="kn-detail-inner">
        <div class="kn-detail-header">
          <h2 class="kn-detail-title">${esc(d.title)}</h2>
          <span class="kn-item-cat">${esc(d.category)}</span>
        </div>
        <div class="kn-detail-tags">${tags}</div>

        <div class="pm-meta-row">
          <span class="pm-meta-item"><span class="kn-detail-label">대상</span> ${esc(d.target || '')}</span>
          ${d.created_at ? `<span class="pm-meta-item"><span class="kn-detail-label">작성일</span> ${esc(d.created_at)}</span>` : ''}
          ${d.updated_at && d.updated_at !== d.created_at ? `<span class="pm-meta-item"><span class="kn-detail-label">수정일</span> ${esc(d.updated_at)}</span>` : ''}
        </div>

        ${d.summary ? `<div class="kn-detail-section">
          <div class="kn-detail-label">요약</div>
          <div class="kn-detail-text">${esc(d.summary)}</div>
        </div>` : ''}

        <div class="kn-detail-section">
          <div class="pm-prompt-label-row">
            <span class="kn-detail-label">프롬프트 전문</span>
            <button class="pm-copy-btn" id="pm-copy-btn" data-text="${esc(d.prompt || '')}">복사</button>
          </div>
          <pre class="kn-pre pm-prompt-pre" id="pm-prompt-text">${esc(d.prompt || '')}</pre>
        </div>
      </div>
    `;

    document.getElementById('pm-copy-btn').addEventListener('click', function () {
      const text = this.dataset.text;
      if (!navigator.clipboard) {
        fallbackCopy(text, this);
        return;
      }
      navigator.clipboard.writeText(text).then(() => {
        showCopied(this);
      }).catch(() => {
        fallbackCopy(text, this);
      });
    });
  }

  function showCopied(btn) {
    btn.textContent = '복사됨 ✓';
    btn.classList.add('pm-copy-done');
    setTimeout(() => {
      btn.textContent = '복사';
      btn.classList.remove('pm-copy-done');
    }, 2000);
  }

  function fallbackCopy(text, btn) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0;';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    try { document.execCommand('copy'); showCopied(btn); } catch (e) {}
    document.body.removeChild(ta);
  }

  function esc(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  render();
})();
</script>
<?php endif; ?>
</body>
</html>
