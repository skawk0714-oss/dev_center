<?php
declare(strict_types=1);
/**
 * Google Drive 연동 헬퍼 (외부 라이브러리 없이 cURL + REST API v3).
 *
 * 인증: data/google_drive_credentials.local.json 의 refresh_token 으로
 *       access_token 을 발급(grant_type=refresh_token)해 Drive API 를 호출한다.
 *
 * 보안: 자격증명(client_secret/refresh_token)은 절대 화면/로그에 출력하지 않는다.
 *       호출자에는 access_token 도 노출하지 않고, 결과 데이터만 반환한다.
 */

if (!defined('DC_DATA_DIR')) {
    require_once __DIR__ . '/../config.php';
}

const GD_CRED_PATH   = DC_DATA_DIR . '/google_drive_credentials.local.json';
const GD_TOKEN_URL   = 'https://oauth2.googleapis.com/token';
const GD_API_FILES   = 'https://www.googleapis.com/drive/v3/files';
const GD_UPLOAD_URL  = 'https://www.googleapis.com/upload/drive/v3/files';

/**
 * 자격증명 로드. 없거나 키가 비면 null.
 * @return array{client_id:string,client_secret:string,refresh_token:string}|null
 */
function gd_credentials(): ?array
{
    if (!is_file(GD_CRED_PATH)) {
        return null;
    }
    $data = json_decode((string) file_get_contents(GD_CRED_PATH), true);
    if (!is_array($data)) {
        return null;
    }
    foreach (['client_id', 'client_secret', 'refresh_token'] as $k) {
        if (empty($data[$k])) {
            return null;
        }
    }
    return [
        'client_id'     => (string) $data['client_id'],
        'client_secret' => (string) $data['client_secret'],
        'refresh_token' => (string) $data['refresh_token'],
    ];
}

/** 자격증명 존재 여부(설정 안내용). */
function gd_configured(): bool
{
    return gd_credentials() !== null;
}

/**
 * access_token 발급. 세션에 만료 전까지 캐시한다.
 * @return array{ok:bool, token?:string, err?:string}
 */
function gd_access_token(): array
{
    if (session_status() === PHP_SESSION_ACTIVE
        && isset($_SESSION['gd_token'], $_SESSION['gd_token_exp'])
        && $_SESSION['gd_token_exp'] > time() + 30) {
        return ['ok' => true, 'token' => (string) $_SESSION['gd_token']];
    }

    $cred = gd_credentials();
    if ($cred === null) {
        return ['ok' => false, 'err' => '자격증명 파일이 없거나 비어 있습니다.'];
    }

    $resp = gd_http('POST', GD_TOKEN_URL, [
        'headers' => ['Content-Type: application/x-www-form-urlencoded'],
        'body'    => http_build_query([
            'client_id'     => $cred['client_id'],
            'client_secret' => $cred['client_secret'],
            'refresh_token' => $cred['refresh_token'],
            'grant_type'    => 'refresh_token',
        ]),
    ]);

    if (!$resp['ok']) {
        return ['ok' => false, 'err' => '토큰 요청 실패: ' . $resp['err']];
    }
    $json = json_decode($resp['body'], true);
    if (!is_array($json) || empty($json['access_token'])) {
        // 구글이 준 error_description 은 토큰값이 아니므로 노출해도 안전
        $msg = is_array($json) ? (string) ($json['error_description'] ?? $json['error'] ?? '알 수 없음') : '응답 파싱 실패';
        return ['ok' => false, 'err' => '토큰 발급 거부: ' . $msg];
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['gd_token']     = (string) $json['access_token'];
        $_SESSION['gd_token_exp'] = time() + (int) ($json['expires_in'] ?? 3600);
    }
    return ['ok' => true, 'token' => (string) $json['access_token']];
}

/**
 * 폴더 내 파일 목록. $folderId 가 빈값이면 내 드라이브 루트('root').
 * @return array{ok:bool, files?:array<int,array>, err?:string}
 */
function gd_list_files(string $folderId = ''): array
{
    $tok = gd_access_token();
    if (!$tok['ok']) {
        return ['ok' => false, 'err' => $tok['err']];
    }
    $parent = $folderId !== '' ? $folderId : 'root';
    // q 의 작은따옴표 인젝션 방지: Drive query 문법상 ' 를 \' 로 이스케이프
    $parentEsc = str_replace("'", "\\'", $parent);
    $query = http_build_query([
        'q'        => "'{$parentEsc}' in parents and trashed = false",
        'fields'   => 'files(id,name,mimeType,size,modifiedTime,webViewLink)',
        'orderBy'  => 'folder,name',
        'pageSize' => 100,
    ]);

    $resp = gd_http('GET', GD_API_FILES . '?' . $query, [
        'headers' => ['Authorization: Bearer ' . $tok['token']],
    ]);
    if (!$resp['ok']) {
        return ['ok' => false, 'err' => '목록 조회 실패: ' . $resp['err']];
    }
    $json = json_decode($resp['body'], true);
    if (!is_array($json)) {
        return ['ok' => false, 'err' => '목록 응답 파싱 실패'];
    }
    if (isset($json['error'])) {
        return ['ok' => false, 'err' => '목록 오류: ' . (string) ($json['error']['message'] ?? '알 수 없음')];
    }
    return ['ok' => true, 'files' => (array) ($json['files'] ?? [])];
}

/**
 * 폴더를 만든다. 같은 이름 폴더가 부모 안에 이미 있으면 재사용(idempotent).
 * $parentId 가 빈값이면 내 드라이브 루트에 만든다.
 * @return array{ok:bool, id?:string, name?:string, created?:bool, err?:string}
 */
function gd_create_folder(string $name, string $parentId = ''): array
{
    $name = trim($name);
    if ($name === '') {
        return ['ok' => false, 'err' => '폴더 이름이 비어 있습니다.'];
    }
    $tok = gd_access_token();
    if (!$tok['ok']) {
        return ['ok' => false, 'err' => $tok['err']];
    }

    // 기존 동일 이름 폴더 탐색 (중복 생성 방지)
    $parent    = $parentId !== '' ? $parentId : 'root';
    $parentEsc = str_replace("'", "\\'", $parent);
    $nameEsc   = str_replace("'", "\\'", $name);
    $q = http_build_query([
        'q'      => "name = '{$nameEsc}' and '{$parentEsc}' in parents "
                  . "and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
        'fields' => 'files(id,name)',
    ]);
    $find = gd_http('GET', GD_API_FILES . '?' . $q, ['headers' => ['Authorization: Bearer ' . $tok['token']]]);
    if ($find['ok']) {
        $fj = json_decode($find['body'], true);
        if (is_array($fj) && !empty($fj['files'][0]['id'])) {
            return ['ok' => true, 'id' => (string) $fj['files'][0]['id'], 'name' => $name, 'created' => false];
        }
    }

    // 신규 생성
    $meta = ['name' => $name, 'mimeType' => 'application/vnd.google-apps.folder'];
    if ($parentId !== '') {
        $meta['parents'] = [$parentId];
    }
    $resp = gd_http('POST', GD_API_FILES . '?fields=id,name', [
        'headers' => [
            'Authorization: Bearer ' . $tok['token'],
            'Content-Type: application/json; charset=UTF-8',
        ],
        'body' => json_encode($meta, JSON_UNESCAPED_UNICODE),
    ]);
    if (!$resp['ok']) {
        return ['ok' => false, 'err' => '폴더 생성 실패: ' . $resp['err']];
    }
    $json = json_decode($resp['body'], true);
    if (!is_array($json) || empty($json['id'])) {
        $msg = is_array($json) ? (string) ($json['error']['message'] ?? '알 수 없음') : '응답 파싱 실패';
        return ['ok' => false, 'err' => '폴더 생성 거부: ' . $msg];
    }
    return ['ok' => true, 'id' => (string) $json['id'], 'name' => $name, 'created' => true];
}

/**
 * 로컬 업로드 파일을 Drive 에 멀티파트로 올린다.
 * @param array $file  $_FILES['...'] 한 건 (tmp_name, name, type 사용)
 * @return array{ok:bool, file?:array, err?:string}
 */
function gd_upload_file(array $file, string $folderId = ''): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        return ['ok' => false, 'err' => '업로드된 파일이 없습니다.'];
    }
    $tok = gd_access_token();
    if (!$tok['ok']) {
        return ['ok' => false, 'err' => $tok['err']];
    }

    $name    = (string) ($file['name'] ?? 'untitled');
    $mime    = (string) ($file['type'] ?? '') ?: 'application/octet-stream';
    $content = (string) file_get_contents((string) $file['tmp_name']);

    $meta = ['name' => $name];
    if ($folderId !== '') {
        $meta['parents'] = [$folderId];
    }

    $boundary = 'dcgd' . bin2hex(random_bytes(8));
    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
    $body .= json_encode($meta, JSON_UNESCAPED_UNICODE) . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: {$mime}\r\n\r\n";
    $body .= $content . "\r\n";
    $body .= "--{$boundary}--";

    $resp = gd_http('POST', GD_UPLOAD_URL . '?uploadType=multipart&fields=id,name,webViewLink,mimeType,size', [
        'headers' => [
            'Authorization: Bearer ' . $tok['token'],
            'Content-Type: multipart/related; boundary=' . $boundary,
        ],
        'body' => $body,
    ]);
    if (!$resp['ok']) {
        return ['ok' => false, 'err' => '업로드 실패: ' . $resp['err']];
    }
    $json = json_decode($resp['body'], true);
    if (!is_array($json) || empty($json['id'])) {
        $msg = is_array($json) ? (string) ($json['error']['message'] ?? '알 수 없음') : '응답 파싱 실패';
        return ['ok' => false, 'err' => '업로드 거부: ' . $msg];
    }
    return ['ok' => true, 'file' => $json];
}

/**
 * 공통 cURL 래퍼.
 * @return array{ok:bool, status:int, body:string, err:string}
 */
function gd_http(string $method, string $url, array $opt = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $opt['headers'] ?? [],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if (isset($opt['body'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opt['body']);
    }
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr   = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'status' => 0, 'body' => '', 'err' => $cerr ?: '네트워크 오류'];
    }
    return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'body' => (string) $body, 'err' => 'HTTP ' . $status];
}
