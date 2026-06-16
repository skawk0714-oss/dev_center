<?php
declare(strict_types=1);
/**
 * Shared top navigation for all Dev Center pages.
 *
 * Usage — before this include, set the active page key:
 *   $activeNav = 'projects';   // or 'knowledge', 'lab', etc.
 *   require __DIR__ . '/../includes/dev_center_nav.php';
 *
 * To add a menu item, edit $NAV_ITEMS only.
 */

$activeNav = $activeNav ?? '';

$NAV_ITEMS = [
    ['key' => 'home',        'label' => '홈',           'href' => 'index.php'],
    ['key' => 'projects',    'label' => '프로젝트 관리', 'href' => 'project_manager.php'],
    ['key' => 'knowledge',   'label' => '기능 보관함',   'href' => 'knowledge.php'],
    ['key' => 'lab',         'label' => '실험실',         'href' => 'lab.php'],
    ['key' => 'prompts',     'label' => '프롬프트',       'href' => 'prompts.php'],
    ['key' => 'resources',   'label' => '자료실',         'href' => 'resources.php'],
    ['key' => 'executables', 'label' => '실행파일',       'href' => 'executables.php'],
    ['key' => 'settings',    'label' => '설정',           'href' => 'settings.php'],
];

function _nav_e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<nav class="dc-topnav">
  <a href="index.php" class="dc-brand">
    🛠️ 개발센터
    <span class="dc-brand-badge">DEV</span>
  </a>
  <ul class="dc-nav-links">
<?php foreach ($NAV_ITEMS as $item): ?>
    <li><a href="<?= _nav_e($item['href']) ?>"<?= $activeNav === $item['key'] ? ' class="active"' : '' ?>><?= _nav_e($item['label']) ?></a></li>
<?php endforeach; ?>
  </ul>
  <div class="dc-topnav-right">
    <span class="dc-badge-env">LOCAL</span>
  </div>
</nav>
