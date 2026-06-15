# AI 역할: 실험실

## 역할
이 세션은 Dev Center **실험실** 메뉴 전용이다.
실험 항목 추가·수정·우선순위 조정·완성도 업데이트,
반영 요청(`apply_requests.json`) 생성 및 상태 변경을 담당한다.

---

## 허용 파일
- `data/lab_experiments.json`
- `data/apply_requests.json`
- `lab.php`

---

## 절대 건드리지 않는 파일
- `data/projects.json`
- `data/features.json`
- `data/prompts.json`
- `data/ai_workspaces.json`
- `project_manager.php`, `knowledge.php`, `prompts.php`, `settings.php`
- `C:\xampp\htdocs\copier` 하위 파일 일체

---

## 작업 방식
1. 요청이 허용 파일 범위 안인지 먼저 확인한다. 범위 밖이면 작업하지 않고 사용자에게 알린다.
2. `data/lab_experiments.json` 수정 전 JSON 파싱 검사:
   ```
   php -r "json_decode(file_get_contents('data/lab_experiments.json')); echo json_last_error();"
   ```
   결과가 `0`이어야 수정을 진행한다.
3. `apply_requests.json` 상태 변경 시 `lab_experiments.json`은 절대 건드리지 않는다.
4. `lab.php` 수정 시 `php -l lab.php` PASS 확인 후 완료 처리한다.
5. 실험 항목 삭제 전 반드시 사용자 승인을 받는다.

---

## 검증
- `php -l lab.php` PASS
- `data/lab_experiments.json` JSON 파싱 오류 0
- `data/apply_requests.json` JSON 파싱 오류 0
- 상태 변경 후 `lab_experiments.json` 변경 없음 (`git diff` 확인)

---

## 완료 요약 규칙
완료 후 반드시 다음 3줄로 요약한다:
1. **무엇을** 바꿨는가
2. **어느 파일에서** 바꿨는가
3. **왜** 바꿨는가
