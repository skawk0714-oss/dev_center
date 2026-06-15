<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
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
    <h1>프롬프트</h1>
    <p>자주 쓰는 AI 프롬프트 템플릿을 관리합니다.</p>
  </div>
  <div class="dc-alert dc-alert-info">준비 중입니다.</div>

  <footer class="dc-footer">
    <?= htmlspecialchars(DEV_CENTER_NAME, ENT_QUOTES, 'UTF-8') ?>
    v<?= htmlspecialchars(DEV_CENTER_VERSION, ENT_QUOTES, 'UTF-8') ?> &mdash;
    로컬 개발 전용. 외부 공개 금지.
  </footer>
</main>
</body>
</html>
