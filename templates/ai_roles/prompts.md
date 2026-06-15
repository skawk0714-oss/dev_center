# AI 역할: 프롬프트 라이브러리

## 역할
이 세션은 Dev Center **프롬프트** 메뉴 전용이다.
`data/prompts.json`에 저장된 프롬프트 항목의 추가·수정·태그 관리,
`prompts.php` UI 개선을 담당한다.

---

## 허용 파일
- `data/prompts.json`
- `prompts.php`

---

## 절대 건드리지 않는 파일
- `data/projects.json`
- `data/features.json`
- `data/lab_experiments.json`
- `data/ai_workspaces.json`
- `project_manager.php`, `knowledge.php`, `lab.php`, `settings.php`
- `C:\xampp\htdocs\copier` 하위 파일 일체

---

## 작업 방식
1. 요청이 허용 파일 범위 안인지 먼저 확인한다. 범위 밖이면 작업하지 않고 사용자에게 알린다.
2. `data/prompts.json` 수정 전 JSON 파싱 검사:
   ```
   php -r "json_decode(file_get_contents('data/prompts.json')); echo json_last_error();"
   ```
   결과가 `0`이어야 수정을 진행한다.
3. 프롬프트 본문(`prompt` 필드) 안에 줄바꿈이 필요할 경우 `\n`으로 표현한다.
4. `prompts.php` 수정 시 `php -l prompts.php` PASS 확인 후 완료 처리한다.
5. 기존 프롬프트 삭제 전 반드시 사용자 승인을 받는다.

---

## 검증
- `php -l prompts.php` PASS
- `data/prompts.json` JSON 파싱 오류 0

---

## 완료 요약 규칙
완료 후 반드시 다음 3줄로 요약한다:
1. **무엇을** 바꿨는가
2. **어느 파일에서** 바꿨는가
3. **왜** 바꿨는가
