# AI 역할: 실행파일 보관함

## 역할
이 세션은 Dev Center **실행파일** 메뉴 전용이다.
실행파일 메타데이터(`data/executables.json`) 항목의 추가·수정·분류 관리,
`executables.php` UI 개선을 담당한다.

---

## 허용 파일
- `data/executables.json`
- `executables.php`
- `assets/css/dev_center.css` — 실행파일 보관함 UI 스타일에 한정

---

## 절대 건드리지 않는 파일
- `data/resources.json`
- `data/instruction_reviews.json`
- `data/projects.json`
- `data/features.json`
- `data/lab_experiments.json`
- `data/prompts.json`
- `data/ai_workspaces.json`
- `scripts/launch_project.ps1`, `scripts/apply_workspace.php`
- `project_manager.php`, `knowledge.php`, `lab.php`, `prompts.php`, `settings.php`, `resources.php`, `instruction_review.php`
- `C:\xampp\htdocs\copier` 하위 파일 일체

---

## 작업 방식
1. 요청이 허용 파일 범위 안인지 먼저 확인한다. 범위 밖이면 작업하지 않고 사용자에게 알린다.
2. `data/executables.json` 수정 전 JSON 파싱 검사:
   ```
   php -r "json_decode(file_get_contents('data/executables.json')); echo json_last_error();"
   ```
   결과가 `0`이어야 수정을 진행한다.
3. 실행파일 보관함은 **메타데이터와 경로 복사** 기능만 제공한다. 브라우저에서 실제 파일을 실행하는 기능을 만들지 않는다.
4. 현장 실행용 파일(설치 파일, 배포 스크립트)과 내부 도구(분석 스크립트, 유틸리티)를 `type` 또는 `category` 필드로 명확히 구분한다.
5. `executables.php` 수정 시 `php -l executables.php` PASS 확인 후 완료 처리한다.
6. 기존 실행파일 항목 삭제 전 반드시 사용자 승인을 받는다.

---

## 검증
- `php -l executables.php` PASS
- `data/executables.json` JSON 파싱 오류 0

---

## 완료 요약 규칙
완료 후 반드시 다음 3줄로 요약한다:
1. **무엇을** 바꿨는가
2. **어느 파일에서** 바꿨는가
3. **왜** 바꿨는가
