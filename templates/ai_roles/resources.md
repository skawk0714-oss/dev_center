# AI 역할: 자료실

## 역할
이 세션은 Dev Center **자료실** 메뉴 전용이다.
문서·도구·자료 인덱스(`data/resources.json`) 항목의 추가·수정·태그 관리,
`resources.php` UI 개선을 담당한다.

---

## 허용 파일
- `data/resources.json`
- `resources.php`
- `assets/css/dev_center.css` — 자료실 UI 스타일에 한정

---

## 절대 건드리지 않는 파일
- `data/executables.json`
- `data/instruction_reviews.json`
- `data/projects.json`
- `data/features.json`
- `data/lab_experiments.json`
- `data/prompts.json`
- `data/ai_workspaces.json`
- `scripts/launch_project.ps1`, `scripts/apply_workspace.php`
- `project_manager.php`, `knowledge.php`, `lab.php`, `prompts.php`, `settings.php`, `executables.php`, `instruction_review.php`
- `C:\xampp\htdocs\copier` 하위 파일 일체

---

## 작업 방식
1. 요청이 허용 파일 범위 안인지 먼저 확인한다. 범위 밖이면 작업하지 않고 사용자에게 알린다.
2. `data/resources.json` 수정 전 JSON 파싱 검사:
   ```
   php -r "json_decode(file_get_contents('data/resources.json')); echo json_last_error();"
   ```
   결과가 `0`이어야 수정을 진행한다.
3. 자료실은 문서·도구·자료의 **인덱스**다. 실제 파일 실행 기능은 만들지 않는다.
4. 실행할 파일 목록은 실행파일(`executables`) 쪽에 둔다. 자료실에 섞지 않는다.
5. Google Drive 연동은 Phase 1 범위에서 schema/placeholder 수준만 허용한다.
6. `resources.php` 수정 시 `php -l resources.php` PASS 확인 후 완료 처리한다.
7. 기존 자료 항목 삭제 전 반드시 사용자 승인을 받는다.

---

## 검증
- `php -l resources.php` PASS
- `data/resources.json` JSON 파싱 오류 0

---

## 완료 요약 규칙
완료 후 반드시 다음 3줄로 요약한다:
1. **무엇을** 바꿨는가
2. **어느 파일에서** 바꿨는가
3. **왜** 바꿨는가
