<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$featuresFile = DC_DATA_DIR . '/features.json';
$knowledgeCount = 0;
if (is_file($featuresFile)) {
    $idx = json_decode(file_get_contents($featuresFile), true);
    $knowledgeCount = is_array($idx) ? count($idx) : 0;
}

$projectsFile = DC_DATA_DIR . '/projects.json';
$projectCount = 0;
if (is_file($projectsFile)) {
    $proj = json_decode(file_get_contents($projectsFile), true);
    $projectCount = is_array($proj) ? count($proj) : 0;
}

$recordsDir = DC_DATA_DIR . '/records';
$recordCount = is_dir($recordsDir) ? count(glob($recordsDir . '/*.json') ?: []) : 0;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>개발센터</title>
  <link rel="stylesheet" href="assets/css/dev_center.css">
</head>
<body>

<nav class="dc-topnav">
  <a href="index.php" class="dc-brand">
    🛠️ 개발센터
    <span class="dc-brand-badge">DEV</span>
  </a>
  <ul class="dc-nav-links">
    <li><a href="index.php" class="active">홈</a></li>
    <li><a href="project_manager.php">프로젝트 관리</a></li>
    <li><a href="knowledge.php">기능 보관함</a></li>
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
    <h1>개발센터</h1>
    <p>CopierRMS 개발 전용 허브 — 프로젝트, 기능 보관함, 실험 내용을 여기서 관리합니다.</p>
  </div>

  <div class="dc-alert dc-alert-info">
    이 도구는 로컬 개발 전용입니다. 프로덕션 CopierRMS 메뉴에는 표시되지 않습니다.
  </div>

  <div class="dc-stats">
    <div class="dc-stat">
      <span class="dc-stat-value"><?= $projectCount ?></span>
      <span class="dc-stat-label">프로젝트</span>
    </div>
    <div class="dc-stat">
      <span class="dc-stat-value"><?= $knowledgeCount ?></span>
      <span class="dc-stat-label">기능 보관 건수</span>
    </div>
    <div class="dc-stat">
      <span class="dc-stat-value"><?= $recordCount ?></span>
      <span class="dc-stat-label">상세 기록</span>
    </div>
  </div>

  <p class="dc-section-title">개발 도구</p>
  <div class="dc-grid">

    <a href="project_manager.php" class="dc-card">
      <div class="dc-card-icon">🗂️</div>
      <div class="dc-card-title">프로젝트 관리</div>
      <div class="dc-card-desc">프로젝트 목록을 보고 VS Code · Codex · Claude를 실행합니다.</div>
      <div class="dc-card-meta"><?= $projectCount ?>개 프로젝트</div>
    </a>

    <a href="knowledge.php" class="dc-card">
      <div class="dc-card-icon">📦</div>
      <div class="dc-card-title">기능 보관함</div>
      <div class="dc-card-desc">완성된 기능, 재사용 패턴, 구현 기록을 검색합니다.</div>
      <div class="dc-card-meta"><?= $knowledgeCount ?>개 기록</div>
    </a>

    <a href="<?= htmlspecialchars(COPIER_URL, ENT_QUOTES, 'UTF-8') ?>" class="dc-card external" target="_blank">
      <div class="dc-card-icon">🖨️</div>
      <div class="dc-card-title">CopierRMS 바로가기</div>
      <div class="dc-card-desc">프로덕션 ERP로 이동합니다.</div>
      <div class="dc-card-meta">localhost/copier</div>
    </a>

    <a href="README.md" class="dc-card external" target="_blank">
      <div class="dc-card-icon">📄</div>
      <div class="dc-card-title">Dev Center README</div>
      <div class="dc-card-desc">프로젝트 구조와 사용 방법을 확인합니다.</div>
      <div class="dc-card-meta">README.md</div>
    </a>

  </div>

  <footer class="dc-footer">
    <?= htmlspecialchars(DEV_CENTER_NAME, ENT_QUOTES, 'UTF-8') ?>
    v<?= htmlspecialchars(DEV_CENTER_VERSION, ENT_QUOTES, 'UTF-8') ?> &mdash;
    로컬 개발 전용. 외부 공개 금지.
  </footer>

</main>
</body>
</html>
