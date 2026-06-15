<?php
declare(strict_types=1);

define('DEV_CENTER_NAME',    '개발센터');
define('DEV_CENTER_VERSION', '1.1.0');
define('DEV_CENTER_BASE_URL', 'http://localhost/dev_center');
define('COPIER_PATH',        'C:/xampp/htdocs/copier');
define('COPIER_URL',         'http://localhost/copier');
define('DC_DATA_DIR',        __DIR__ . '/data');

define('DEV_ALLOWED_ROOTS', [
    'C:/xampp/htdocs',
    'D:/projects',
    'C:/projects',
]);

// 실행 기능 활성화 여부. false로 바꾸면 launch 버튼이 비활성화됩니다.
define('DC_LAUNCH_ENABLED', true);
