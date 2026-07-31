<?php
declare(strict_types=1);
/**
 * 개발 PC 전체 백업 → 구글 드라이브 (CLI 전용)
 *
 * 흐름:
 *   1) MySQL 사용자 DB 전체 + copier_rms 별도 mysqldump
 *   2) 지정 소스 폴더들 + .sql 덤프를 ZIP 1개로 압축(ZipArchive, 디스크 스트리밍)
 *   3) gd_upload_local_file()로 구글 드라이브 '개발PC_백업' 폴더에 resumable 업로드
 *   4) 임시 파일 정리
 *
 * 보안:
 *   - DB 비밀번호는 명령줄에 노출하지 않고 MYSQL_PWD 환경변수로 mysqldump 에 전달.
 *   - 브라우저 접근 차단(CLI 전용).
 *
 * 설정: data/backup_config.json 에서 폴더/DB 목록을 편집한다(코드 수정 불필요).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI 전용 스크립트입니다.');
}

require_once __DIR__ . '/config.php';                 // DC_DATA_DIR, COPIER_PATH
require_once __DIR__ . '/includes/google_drive.php';  // gd_* 헬퍼
require_once COPIER_PATH . '/config.php';             // DB_HOST, DB_PORT, DB_USER, DB_PASS

// ── 유틸 ────────────────────────────────────────────────────────────────────
function bk_log(string $msg): void
{
    echo '[' . date('H:i:s') . '] ' . $msg . PHP_EOL;
}

function bk_fail(string $msg): never
{
    echo '[ERROR] ' . $msg . PHP_EOL;
    exit(1);
}

$started = microtime(true);
bk_log('=== 개발 PC 백업 시작 ===');

// ── 설정 로드 ───────────────────────────────────────────────────────────────
$cfgPath = DC_DATA_DIR . '/backup_config.json';
$cfg = is_file($cfgPath) ? json_decode((string) file_get_contents($cfgPath), true) : null;
if (!is_array($cfg)) {
    bk_fail('backup_config.json 을 읽을 수 없습니다.');
}
$driveFolderName = (string) ($cfg['drive_folder'] ?? '개발PC_백업');
$paths           = array_values(array_filter((array) ($cfg['paths'] ?? []), 'is_string'));
$excludeDirs     = array_map('strtolower', (array) ($cfg['exclude_dirs'] ?? []));
$dbAllUser       = (bool) ($cfg['db_all_user'] ?? true);
$dbExtra         = (array) ($cfg['db_extra'] ?? []);
$dbSysExclude    = (array) ($cfg['db_system_exclude'] ?? ['information_schema', 'performance_schema', 'mysql', 'sys']);
$claudeMemPath   = (string) ($cfg['claude_memory_path'] ?? '');   // Claude 자동 메모리 폴더(%USERPROFILE% 등 환경변수 허용). 비면 건너뜀.

// ── 임시 작업 폴더 ─────────────────────────────────────────────────────────
$stamp   = date('Ymd_His');
$workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'devbackup_' . $stamp;
if (!mkdir($workDir, 0700, true) && !is_dir($workDir)) {
    bk_fail('임시 폴더 생성 실패: ' . $workDir);
}
$tempFiles = [];   // 정리 대상

// ── 1) DB 덤프 ──────────────────────────────────────────────────────────────
$mysqldump = 'C:/xampp/mysql/bin/mysqldump.exe';
if (!is_file($mysqldump)) {
    bk_fail('mysqldump.exe 를 찾을 수 없습니다: ' . $mysqldump);
}

// 비밀번호는 환경변수로(프로세스 목록 노출 방지)
putenv('MYSQL_PWD=' . DB_PASS);
$connArgs = sprintf('--host=%s --port=%d --user=%s', escapeshellarg(DB_HOST), (int) DB_PORT, escapeshellarg(DB_USER));
$dumpOpts = '--single-transaction --routines --events --default-character-set=utf8mb4';

// 사용자 DB 목록 조회
$userDbs = [];
if ($dbAllUser) {
    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, (int) DB_PORT),
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        foreach ($pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN) as $db) {
            if (!in_array($db, $dbSysExclude, true)) {
                $userDbs[] = (string) $db;
            }
        }
        $pdo = null;
    } catch (Throwable $e) {
        bk_fail('DB 목록 조회 실패(접속 정보 확인): ' . $e->getMessage());
    }
}

// (a) 사용자 DB 전체 덤프
if ($userDbs) {
    $allSql = $workDir . DIRECTORY_SEPARATOR . 'alldb_' . $stamp . '.sql';
    $tempFiles[] = $allSql;
    $dbArgs = implode(' ', array_map('escapeshellarg', $userDbs));
    $cmd = sprintf('"%s" %s %s --databases %s > %s 2>%s',
        $mysqldump, $connArgs, $dumpOpts, $dbArgs,
        escapeshellarg($allSql), escapeshellarg($allSql . '.err'));
    $tempFiles[] = $allSql . '.err';
    system($cmd, $rc);
    if ($rc !== 0 || !is_file($allSql) || filesize($allSql) === 0) {
        bk_fail('사용자 DB 덤프 실패 (rc=' . $rc . ') — ' . trim((string) @file_get_contents($allSql . '.err')));
    }
    bk_log('사용자 DB 덤프 완료: ' . count($userDbs) . '개 (' . round(filesize($allSql) / 1048576, 1) . ' MB)');
}

// (b) db_extra 개별 덤프 (copier_rms 등)
foreach ($dbExtra as $db) {
    $db = (string) $db;
    $sql = $workDir . DIRECTORY_SEPARATOR . $db . '_' . $stamp . '.sql';
    $tempFiles[] = $sql;
    $cmd = sprintf('"%s" %s %s --databases %s > %s 2>%s',
        $mysqldump, $connArgs, $dumpOpts, escapeshellarg($db),
        escapeshellarg($sql), escapeshellarg($sql . '.err'));
    $tempFiles[] = $sql . '.err';
    system($cmd, $rc);
    if ($rc !== 0 || !is_file($sql) || filesize($sql) === 0) {
        bk_fail($db . ' 덤프 실패 (rc=' . $rc . ') — ' . trim((string) @file_get_contents($sql . '.err')));
    }
    bk_log($db . ' 덤프 완료 (' . round(filesize($sql) / 1048576, 1) . ' MB)');
}
putenv('MYSQL_PWD');   // 환경변수 즉시 제거

// ── 2) ZIP 압축 (소스 폴더 + .sql 덤프) ─────────────────────────────────────
$zipPath = $workDir . DIRECTORY_SEPARATOR . 'DevBackup_' . $stamp . '.zip';
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    bk_fail('ZIP 생성 실패: ' . $zipPath);
}

// .sql 덤프를 db/ 하위로
foreach ($tempFiles as $tf) {
    if (substr($tf, -4) === '.sql' && is_file($tf)) {
        $zip->addFile($tf, 'db/' . basename($tf));
    }
}

// 소스 폴더 재귀 추가 (exclude_dirs 제외)
$fileCount = 0;
foreach ($paths as $src) {
    $src = rtrim(str_replace('\\', '/', $src), '/');
    if (!is_dir($src)) {
        bk_log('  (건너뜀: 폴더 없음) ' . $src);
        continue;
    }
    $base = basename($src);
    $it = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
            static function ($cur) use ($excludeDirs) {
                if ($cur->isDir()) {
                    return !in_array(strtolower($cur->getFilename()), $excludeDirs, true);
                }
                return true;
            }
        ),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $full  = str_replace('\\', '/', $file->getPathname());
        $local = $base . '/' . ltrim(substr($full, strlen($src)), '/');
        $zip->addFile($full, $local);
        $fileCount++;
    }
    bk_log('  추가: ' . $base);
}

// Claude 자동 메모리 폴더 추가 (config의 claude_memory_path)
// - 프로젝트 폴더 밖(.claude)이라 paths로는 안 잡혀 여기서 직접 수집.
// - 폴더가 없거나 비어 있어도 백업을 멈추지 않고 경고만 남긴다(메모리 누락 조기 발견용).
$memCount = 0;
if ($claudeMemPath !== '') {
    // %USERPROFILE% 같은 윈도우 환경변수 치환 (무인 S4U 백업에서도 해석되도록)
    $memResolved = preg_replace_callback('/%([^%]+)%/', static function (array $m): string {
        $v = getenv($m[1]);
        return $v !== false ? $v : $m[0];
    }, $claudeMemPath);
    $memResolved = rtrim(str_replace('\\', '/', $memResolved), '/');
    if (is_dir($memResolved)) {
        foreach (new DirectoryIterator($memResolved) as $fi) {
            if ($fi->isFile()) {
                $zip->addFile($fi->getPathname(), 'claude_memory/' . $fi->getFilename());
                $memCount++;
            }
        }
        if ($memCount > 0) {
            bk_log('  추가: claude_memory (' . $memCount . '개 메모리 파일)');
        } else {
            bk_log('  [경고] 메모리 폴더가 비어 있음 — claude_memory 미포함: ' . $memResolved);
        }
    } else {
        bk_log('  [경고] 메모리 폴더 없음 — claude_memory 미포함: ' . $memResolved);
    }
} else {
    bk_log('  [정보] claude_memory_path 미설정 — 메모리 백업 건너뜀');
}

$zip->close();
if (!is_file($zipPath) || filesize($zipPath) === 0) {
    bk_fail('ZIP 압축 결과가 비었습니다.');
}
$zipMb = round(filesize($zipPath) / 1048576, 1);
bk_log('ZIP 완료: ' . basename($zipPath) . ' (' . $zipMb . ' MB, 파일 ' . $fileCount . '개)');

// ── 3) 구글 드라이브 업로드 (resumable) ─────────────────────────────────────
if (!gd_configured()) {
    bk_fail('구글 드라이브 자격증명이 없습니다.');
}
$folder = gd_create_folder($driveFolderName, '');   // 없으면 생성, 있으면 재사용
if (!$folder['ok']) {
    bk_fail('백업 폴더 준비 실패: ' . $folder['err']);
}
bk_log('업로드 시작 → 드라이브/' . $driveFolderName . ' (' . $zipMb . ' MB)');
$up = gd_upload_local_file($zipPath, basename($zipPath), $folder['id'], 'application/zip');
if (!$up['ok']) {
    bk_fail('업로드 실패: ' . $up['err'] . ' (임시 ZIP 보존: ' . $zipPath . ')');
}
bk_log('업로드 완료: ' . (string) ($up['file']['name'] ?? '') . ' (id=' . (string) ($up['file']['id'] ?? '') . ')');

// ── 4) 보관 정책: 최신 keep_count 개만 유지, 오래된 백업 삭제 ───────────────
// 안전장치(이중):
//   (a) 방금 사용한 '개발PC_백업' 폴더($folder['id']) 안의 파일만 대상 — 다른 폴더 절대 안 건드림
//   (b) 우리 백업 명명규칙(DevBackup_YYYYMMDD_HHMMSS.zip)에 정확히 맞는 파일만 삭제 대상
$keepCount = (int) ($cfg['keep_count'] ?? 0);
if ($keepCount > 0) {
    $listed = gd_list_files($folder['id']);   // (a) 해당 폴더 한정 조회
    if ($listed['ok']) {
        $backups = array_values(array_filter($listed['files'], static function (array $f): bool {
            // (b) 폴더가 아니고, 백업 파일명 패턴에 정확히 일치하는 것만
            return ($f['mimeType'] ?? '') !== 'application/vnd.google-apps.folder'
                && preg_match('/^DevBackup_\d{8}_\d{6}\.zip$/', (string) ($f['name'] ?? '')) === 1;
        }));
        // 이름 내림차순 = 최신 우선 (이름에 타임스탬프 포함)
        usort($backups, static fn($a, $b) => strcmp((string) $b['name'], (string) $a['name']));

        $toDelete = array_slice($backups, $keepCount);   // 최신 keepCount 개 제외한 나머지
        $deleted  = 0;
        foreach ($toDelete as $old) {
            $d = gd_delete_file((string) $old['id']);
            if ($d['ok']) {
                $deleted++;
                bk_log('  오래된 백업 삭제: ' . (string) $old['name']);
            } else {
                bk_log('  삭제 실패(' . (string) $old['name'] . '): ' . (string) $d['err']);
            }
        }
        bk_log('보관 정책: 백업 ' . count($backups) . '개 중 최신 ' . $keepCount . '개 유지, ' . $deleted . '개 삭제');
    } else {
        bk_log('보관 정책: 목록 조회 실패로 건너뜀 — ' . (string) $listed['err']);
    }
}

// ── 5) 임시 파일 정리 ───────────────────────────────────────────────────────
foreach ($tempFiles as $tf) {
    if (is_file($tf)) { @unlink($tf); }
}
@unlink($zipPath);
@rmdir($workDir);

$elapsed = round(microtime(true) - $started, 1);
bk_log('=== 백업 완료 (' . $elapsed . '초) ===');
exit(0);
