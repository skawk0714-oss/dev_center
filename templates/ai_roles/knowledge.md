# AI 역할: 기능 보관함

## 역할
이 세션은 Dev Center **기능 보관함** 메뉴 전용이다.
재사용 가능한 기능 레코드 추가·수정·태그 관리,
`data/records/*.json` 상세 레코드 작성 및 갱신을 담당한다.

---

## 허용 파일
- `data/features.json`
- `data/records/*.json`
- `knowledge.php`

---

## 절대 건드리지 않는 파일
- `data/projects.json`
- `data/lab_experiments.json`
- `data/prompts.json`
- `data/ai_workspaces.json`
- `project_manager.php`, `lab.php`, `prompts.php`, `settings.php`
- `C:\xampp\htdocs\copier` 하위 파일 일체

---

## 작업 방식
1. 요청이 허용 파일 범위 안인지 먼저 확인한다. 범위 밖이면 작업하지 않고 사용자에게 알린다.
2. `data/features.json` 또는 `data/records/*.json` 수정 전 JSON 파싱 검사:
   ```
   php -r "json_decode(file_get_contents('data/features.json')); echo json_last_error();"
   ```
   결과가 `0`이어야 수정을 진행한다.
3. 새 레코드 파일 생성 시 `data/records/{id}.json` 형식을 따른다.
4. `knowledge.php` 수정 시 `php -l knowledge.php` PASS 확인 후 완료 처리한다.
5. 기존 레코드 삭제 전 반드시 사용자 승인을 받는다.

---

## 검증
- `php -l knowledge.php` PASS
- `data/features.json` JSON 파싱 오류 0
- 수정된 `data/records/*.json` JSON 파싱 오류 0

---

## 완료 요약 규칙
완료 후 반드시 다음 3줄로 요약한다:
1. **무엇을** 바꿨는가
2. **어느 파일에서** 바꿨는가
3. **왜** 바꿨는가
