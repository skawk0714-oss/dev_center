<?php
declare(strict_types=1);
/**
 * 구글 드라이브 refresh_token 재발급 (CLI 전용, 1회성 대화형)
 *
 * 사용 이유:
 *   refresh_token 이 만료/폐기(invalid_grant)되면 백업 업로드가 실패한다.
 *   이 스크립트로 브라우저 동의를 거쳐 새 refresh_token 을 발급받아
 *   data/google_drive_credentials.local.json 의 refresh_token 만 교체한다.
 *
 * 흐름:
 *   1) php gd_reauth.php            → 구글 동의 URL 출력, 코드 입력 대기(stdin)
 *      또는
 *      php gd_reauth.php "<코드 또는 리다이렉트 URL>"   → 바로 교환
 *   2) 브라우저에서 승인 → http://localhost/?code=... 로 이동(페이지는 안 떠도 정상)
 *   3) 주소창의 code 값(또는 전체 URL)을 붙여넣으면 교환·저장·검증
 *
 * 보안: client_secret/refresh_token 은 화면에 전체 출력하지 않는다(길이만 표시).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI 전용 스크립트입니다.');
}

const GD_CRED_PATH  = __DIR__ . '/data/google_drive_credentials.local.json';
const GD_TOKEN_URL  = 'https://oauth2.googleapis.com/token';
const GD_AUTH_URL   = 'https://accounts.google.com/o/oauth2/v2/auth';
const GD_SCOPE      = 'https://www.googleapis.com/auth/drive.file';
const GD_REDIRECT   = 'http://localhost';   // Desktop 앱 타입이면 자동 허용. Web 타입이면 이 URI를 승인된 리디렉션 URI에 등록해야 함.

function out(string $s): void { echo $s . PHP_EOL; }
function die_err(string $s): never { echo '[ERROR] ' . $s . PHP_EOL; exit(1); }

/**
 * refresh_token 을 자격증명 파일에 저장(기존 백업)하고, 즉시 access_token 발급으로 검증한다.
 * 인증코드 교환 경로와 Playground 직접 붙여넣기 경로 양쪽에서 공용으로 사용.
 */
function gd_save_and_verify(string $newRefresh, array $cred, string $clientId, string $clientSecret): never
{
    $backup = GD_CRED_PATH . '.bak_' . date('YmdHis');
    if (!copy(GD_CRED_PATH, $backup)) {
        die_err('기존 자격증명 백업 실패 — 저장 중단.');
    }
    $cred['refresh_token'] = $newRefresh;
    $cred['reauth_at']     = date('c');
    $out = json_encode($cred, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($out === false || file_put_contents(GD_CRED_PATH, $out, LOCK_EX) === false) {
        die_err('자격증명 저장 실패 (백업: ' . $backup . ')');
    }
    out('새 refresh_token 저장 완료 (' . strlen($newRefresh) . 'chars). 기존 파일 백업: ' . basename($backup));

    out('');
    out('검증: 새 토큰으로 access_token 발급 테스트...');
    $ch = curl_init(GD_TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $newRefresh,
            'grant_type'    => 'refresh_token',
        ]),
    ]);
    $vb = curl_exec($ch);
    $vs = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $vj = json_decode((string) $vb, true);
    if ($vs === 200 && is_array($vj) && !empty($vj['access_token'])) {
        out('✅ 검증 성공 — 재인증 완료. 이제 백업이 정상 업로드됩니다.');
        out('   확인: php backup_dev_pc.php  (또는 작업 스케줄러 DevCenter_DailyBackup 실행)');
        exit(0);
    }
    $vmsg = is_array($vj) ? (string) ($vj['error_description'] ?? $vj['error'] ?? '알 수 없음') : '파싱 실패';
    die_err('검증 실패 (HTTP ' . $vs . '): ' . $vmsg . ' — 저장은 됐으나 즉시 확인 실패, 백업 실행으로 재확인 필요.');
}

// ── 자격증명 로드 ────────────────────────────────────────────────────────────
if (!is_file(GD_CRED_PATH)) {
    die_err('자격증명 파일 없음: ' . GD_CRED_PATH);
}
$cred = json_decode((string) file_get_contents(GD_CRED_PATH), true);
if (!is_array($cred) || empty($cred['client_id']) || empty($cred['client_secret'])) {
    die_err('client_id / client_secret 이 자격증명 파일에 없습니다.');
}
$clientId     = (string) $cred['client_id'];
$clientSecret = (string) $cred['client_secret'];

// ── 입력에서 인증 코드 추출 ───────────────────────────────────────────────────
$raw = trim((string) ($argv[1] ?? ''));

if ($raw === '') {
    // 대화형: 동의 URL 안내 후 stdin 대기
    $authUrl = GD_AUTH_URL . '?' . http_build_query([
        'client_id'     => $clientId,
        'redirect_uri'  => GD_REDIRECT,
        'response_type' => 'code',
        'scope'         => GD_SCOPE,
        'access_type'   => 'offline',
        'prompt'        => 'consent',   // 항상 새 refresh_token 강제 발급
    ]);
    out('');
    out('── 구글 드라이브 재인증 ──────────────────────────────────');
    out('1) 아래 URL 을 브라우저에서 여세요 (본인 구글 계정으로 로그인):');
    out('');
    out('   ' . $authUrl);
    out('');
    out('2) 권한 승인 후 브라우저가 http://localhost/?code=... 로 이동합니다.');
    out('   (페이지 로드 실패는 정상 — 주소창의 code 값이 필요합니다.)');
    out('3) 주소창 전체 URL 또는 code 값을 여기에 붙여넣고 Enter:');
    out('');
    echo '> ';
    $raw = trim((string) fgets(STDIN));
    if ($raw === '') {
        die_err('입력이 비었습니다.');
    }
}

// OAuth Playground 등에서 refresh_token 을 직접 붙여넣은 경우:
// 구글 refresh_token 은 '1//' 로 시작 → 교환 없이 바로 저장·검증.
if (strncmp($raw, '1//', 3) === 0) {
    out('');
    out('refresh_token 직접 입력 감지 — 교환 없이 저장·검증합니다.');
    gd_save_and_verify($raw, $cred, $clientId, $clientSecret);   // 내부에서 종료
}

// URL 을 붙여넣었으면 code 파라미터만 추출
$code = $raw;
if (stripos($raw, 'http') === 0 || strpos($raw, 'code=') !== false) {
    $q = parse_url($raw, PHP_URL_QUERY);
    if (is_string($q)) {
        parse_str($q, $parts);
        if (!empty($parts['code'])) {
            $code = (string) $parts['code'];
        }
        if (!empty($parts['error'])) {
            die_err('구글이 인증을 거부함: ' . (string) $parts['error']);
        }
    }
}
$code = trim($code);
if ($code === '') {
    die_err('인증 코드를 찾지 못했습니다.');
}

// ── 코드 → 토큰 교환 ─────────────────────────────────────────────────────────
out('');
out('토큰 교환 중...');
$ch = curl_init(GD_TOKEN_URL);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_POSTFIELDS     => http_build_query([
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'code'          => $code,
        'grant_type'    => 'authorization_code',
        'redirect_uri'  => GD_REDIRECT,
    ]),
]);
$body   = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr   = curl_error($ch);
curl_close($ch);

if ($body === false) {
    die_err('네트워크 오류: ' . $cerr);
}
$json = json_decode((string) $body, true);
if ($status !== 200 || !is_array($json) || empty($json['refresh_token'])) {
    $msg = is_array($json)
        ? (string) ($json['error_description'] ?? $json['error'] ?? '알 수 없음')
        : '응답 파싱 실패';
    if (is_array($json) && empty($json['refresh_token']) && !empty($json['access_token'])) {
        $msg .= ' (refresh_token 미포함 — URL 의 prompt=consent/access_type=offline 확인, '
              . '또는 계정 권한 페이지에서 앱 접근 제거 후 재시도)';
    }
    die_err('토큰 교환 실패 (HTTP ' . $status . '): ' . $msg);
}

$newRefresh = (string) $json['refresh_token'];

// ── 저장 + 검증 (공용 함수) ─────────────────────────────────────────────────
gd_save_and_verify($newRefresh, $cred, $clientId, $clientSecret);
