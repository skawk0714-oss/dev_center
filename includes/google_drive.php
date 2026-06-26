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
 * 대용량 로컬 파일을 resumable(분할) 업로드로 Drive 에 올린다.
 * gd_upload_file 과 달리 HTTP 업로드($_FILES)가 아니라 디스크의 실제 경로를 받으며,
 * 8MB 청크로 스트리밍해 메모리를 거의 쓰지 않는다(백업 ZIP 등 수백 MB~GB 대응).
 *
 * @return array{ok:bool, file?:array, err?:string}
 */
function gd_upload_local_file(string $localPath, string $name, string $folderId = '', string $mime = 'application/octet-stream'): array
{
    if (!is_file($localPath)) {
        return ['ok' => false, 'err' => '로컬 파일 없음: ' . $localPath];
    }
    $tok = gd_access_token();
    if (!$tok['ok']) {
        return ['ok' => false, 'err' => $tok['err']];
    }
    $size = (int) filesize($localPath);

    // 1) resumable 세션 시작 → 응답 헤더의 Location(세션 URI) 확보
    $meta = ['name' => $name];
    if ($folderId !== '') {
        $meta['parents'] = [$folderId];
    }
    $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&fields=id,name,webViewLink,size,mimeType');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $tok['token'],
            'Content-Type: application/json; charset=UTF-8',
            'X-Upload-Content-Type: ' . $mime,
            'X-Upload-Content-Length: ' . $size,
        ],
        CURLOPT_POSTFIELDS     => json_encode($meta, JSON_UNESCAPED_UNICODE),
    ]);
    $resp   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsize  = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $cerr   = curl_error($ch);
    curl_close($ch);
    if ($resp === false || $status < 200 || $status >= 300) {
        return ['ok' => false, 'err' => '업로드 세션 시작 실패 (HTTP ' . $status . ' ' . $cerr . ')'];
    }
    $headers = substr((string) $resp, 0, $hsize);
    if (!preg_match('/^location:\s*(\S+)/im', $headers, $m)) {
        return ['ok' => false, 'err' => '업로드 세션 URI(Location) 없음'];
    }
    $sessionUri = trim($m[1]);

    // 2) 8MB(256KB 배수) 청크로 PUT
    $chunkSize = 8 * 1024 * 1024;
    $fh = fopen($localPath, 'rb');
    if (!$fh) {
        return ['ok' => false, 'err' => '파일 열기 실패'];
    }
    $offset = 0;
    $final  = null;
    while ($offset < $size) {
        $data = fread($fh, $chunkSize);
        $len  = strlen($data);
        $end  = $offset + $len - 1;

        $c = curl_init($sessionUri);
        curl_setopt_array($c, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 600,
            CURLOPT_HTTPHEADER     => [
                'Content-Length: ' . $len,
                'Content-Range: bytes ' . $offset . '-' . $end . '/' . $size,
            ],
            CURLOPT_POSTFIELDS     => $data,
        ]);
        $r   = curl_exec($c);
        $st  = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
        $ce  = curl_error($c);
        curl_close($c);

        if ($r === false) {
            fclose($fh);
            return ['ok' => false, 'err' => '청크 업로드 네트워크 오류: ' . $ce];
        }
        if ($st === 308) {            // Resume Incomplete → 다음 청크
            $offset += $len;
            continue;
        }
        if ($st === 200 || $st === 201) {  // 완료
            $final = json_decode((string) $r, true);
            $offset += $len;
            break;
        }
        fclose($fh);
        return ['ok' => false, 'err' => '청크 업로드 실패 (HTTP ' . $st . ')'];
    }
    fclose($fh);

    if (!is_array($final) || empty($final['id'])) {
        return ['ok' => false, 'err' => '업로드 완료 응답 파싱 실패'];
    }
    return ['ok' => true, 'file' => $final];
}

/**
 * 업로드된 Drive 파일을 자료실(resources.json)에 자료로 등록한다.
 * 자료실/Google Drive 화면 어디서 올리든 동일하게 등록되도록 공용화한 함수.
 *
 * @param array  $driveFile gd_upload_file 결과의 file (id,name,webViewLink,mimeType)
 * @return bool 저장 성공 여부
 */
function dc_register_drive_resource(array $driveFile, string $folderId, string $title = '', string $desc = ''): bool
{
    $path = DC_DATA_DIR . '/resources.json';
    $r = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
    if (!is_array($r)) { $r = []; }

    // 폴더 ID -> 폴더명 (drive_folders.json 역매핑). 못 찾으면 ID 그대로.
    $folderName = $folderId !== '' ? $folderId : '루트';
    $dfPath = DC_DATA_DIR . '/drive_folders.json';
    if ($folderId !== '' && is_file($dfPath)) {
        $df = json_decode((string) file_get_contents($dfPath), true);
        if (is_array($df)) {
            $key = array_search($folderId, $df, true);
            if ($key !== false && $key !== '_root') { $folderName = (string) $key; }
        }
    }

    $title = trim($title) !== '' ? trim($title) : (string) ($driveFile['name'] ?? '업로드 파일');
    $r[] = [
        'id'             => 'drive-' . date('ymdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
        'title'          => $title,
        'category'       => 'other',
        'project_id'     => '260614-copier-rms',
        'description'    => trim($desc),
        'usage_type'     => 'reference',
        'usage_note'     => '개발센터 구글 드라이브(' . $folderName . ' 폴더)에 업로드된 자료입니다.',
        'storage_type'   => 'google_drive',
        'path'           => '',
        'url'            => (string) ($driveFile['webViewLink'] ?? ''),
        'drive_file_id'  => (string) ($driveFile['id'] ?? ''),
        'drive_folder_id'=> $folderId,
        'mime_type'      => (string) ($driveFile['mimeType'] ?? ''),
        'tags'           => ['drive', $folderName],
        'resource_kind'  => 'project_asset',
        'status'         => 'active',
        'created_at'     => date('Y-m-d'),
        'updated_at'     => date('Y-m-d'),
    ];
    $out = json_encode($r, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    return $out !== false && file_put_contents($path, $out, LOCK_EX) !== false;
}

/**
 * Drive 파일 삭제. 기본은 영구 삭제(용량 즉시 회수). $permanent=false 면 휴지통 이동(복구 가능).
 *
 * @return array{ok:bool, err?:string}
 */
function gd_delete_file(string $fileId, bool $permanent = true): array
{
    if ($fileId === '') {
        return ['ok' => false, 'err' => 'fileId 없음'];
    }
    $tok = gd_access_token();
    if (!$tok['ok']) {
        return ['ok' => false, 'err' => $tok['err']];
    }

    if ($permanent) {
        $r = gd_http('DELETE', GD_API_FILES . '/' . rawurlencode($fileId),
            ['headers' => ['Authorization: Bearer ' . $tok['token']]]);
        if ($r['status'] === 204 || $r['ok']) {
            return ['ok' => true];
        }
        return ['ok' => false, 'err' => '삭제 실패 (HTTP ' . $r['status'] . ')'];
    }

    // 휴지통 이동
    $r = gd_http('PATCH', GD_API_FILES . '/' . rawurlencode($fileId) . '?fields=id', [
        'headers' => ['Authorization: Bearer ' . $tok['token'], 'Content-Type: application/json'],
        'body'    => json_encode(['trashed' => true]),
    ]);
    return ['ok' => $r['ok'], 'err' => $r['ok'] ? '' : '휴지통 이동 실패 (HTTP ' . $r['status'] . ')'];
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
