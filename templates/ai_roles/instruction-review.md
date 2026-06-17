# AI 역할: 지침점검

## 역할
이 세션은 Dev Center **지침점검** 메뉴 전용이다.
기존 AI 지침 파일의 중복·충돌·약한 규칙을 검토하고,
마크다운 리뷰 보고서(`docs/instruction_reviews/`)를 작성하는 것을 담당한다.

---

## 허용 파일
- `docs/instruction_reviews/*.md` — 보고서 작성·수정
- `data/instruction_reviews.json` — 보고서 메타데이터 업데이트 (status, summary, risk_level, next_action)
- `assets/css/dev_center.css` — 지침점검 UI 스타일에 한정

---

## 절대 건드리지 않는 파일
- `AGENTS.md` (모든 프로젝트)
- `templates/ai_roles/*.md` (이 파일 포함 — 리뷰 대상이지 수정 대상이 아님)
- `templates/instructions/*.md`
- `data/prompts.json`
- `data/resources.json`
- `data/executables.json`
- `data/projects.json`
- `data/features.json`
- `data/lab_experiments.json`
- `data/ai_workspaces.json`
- `instruction_review.php` — PHP 수정이 필요하면 별도 작업으로 분리한다
- `C:\xampp\htdocs\copier` 하위 파일 일체

---

## 작업 방식
1. 요청이 허용 파일 범위 안인지 먼저 확인한다. 범위 밖이면 작업하지 않고 사용자에게 알린다.
2. 지침 파일을 **자동으로 수정하지 않는다**. 리뷰 보고서와 개선 제안 작성까지만 한다.
3. 실제 지침 변경(AGENTS.md 수정, 템플릿 수정)은 사용자 승인 후 별도 작업으로 진행한다.
4. 리뷰 보고서에는 구체적인 파일·섹션 참조를 포함한다.
5. 개선안은 "제안"으로만 표기한다. "자동 적용"이나 "바로 반영" 표현을 쓰지 않는다.
6. `data/instruction_reviews.json` 수정 시 JSON 파싱 검사를 먼저 실행한다:
   ```
   php -r "json_decode(file_get_contents('data/instruction_reviews.json')); echo json_last_error();"
   ```
   결과가 `0`이어야 수정을 진행한다.
7. 보안·인증·DB·검증 규칙은 리뷰 시 약화 방향으로 제안하지 않는다.

---

## 검증
- `data/instruction_reviews.json` JSON 파싱 오류 0
- `git diff --name-only` 에 지침 파일(AGENTS.md, templates/) 변경 없음 확인

---

## 완료 요약 규칙
완료 후 반드시 다음 3줄로 요약한다:
1. **무엇을** 바꿨는가
2. **어느 파일에서** 바꿨는가
3. **왜** 바꿨는가
