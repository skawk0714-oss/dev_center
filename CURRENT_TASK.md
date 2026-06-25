# Current Task

## 목표 (Goal)
최근 구현 및 안정화된 '구글 드라이브 연동 및 자료실 업로드 통합 기능'을 개발센터의 기능 보관함(features)에 공식 자산으로 정리하고 문서화합니다.

## 작업 범위 (Scope)
1. **data/records/google-drive-integration.json 생성**
   - 구글 드라이브 연동 기능의 개요, 기술 스택(cURL, REST API v3), 주요 기능(업로드, 자동 등록, 폴더 매핑)을 상세히 기록합니다.
2. **data/features.json 업데이트**
   - 새로 생성한 레코드 파일의 메타데이터를 `features.json` 배열에 추가하여 개발센터 지식창고 화면에 노출되도록 합니다.

## 제외 범위 (Out of Scope)
- 기존 PHP 코드(`google_drive.php`, `resources.php` 등) 수정 절대 금지.
- 구글 드라이브 상의 실제 폴더 구조 변경 금지.

## 완료 조건 (Done Criteria)
- `data/records/google-drive-integration.json` 파일이 형식에 맞게 생성될 것.
- `data/features.json`에 `google-drive-integration` 항목이 추가될 것.
- JSON 문법 오류가 없어야 함(`php -l` 또는 JSON 파서 확인).
