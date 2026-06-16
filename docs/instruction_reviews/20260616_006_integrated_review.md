# Instruction Review Report

**ID**: ir-20260616-006
**Type**: Integrated
**Generated**: 2026-06-16 17:57:19
**Reviewed**: 2026-06-17
**Reviewer**: Claude (real review pass)

---

## Integrated Checks

- [x] Codex and Claude role boundaries are clear. *(copier AGENTS.md에는 명확히 정의됨 — 단, base.md에는 없음. 신규 프로젝트에 미전파)*
- [x] Security, auth, DB migration, and validation rules do not conflict. *(충돌은 없으나 base.md 보안 규칙이 php-erp/php-local-tool 대비 현저히 약함)*
- [x] Maintainability-first rule is present and does not allow broad refactors. *(base.md §3-1에 존재하며 "범위 밖 정리는 별도 작업" 경계가 명확함)*
- [ ] Incident logging rules are clear. *(base.md에 인시던트 로깅 규칙 없음 — copier AGENTS.md §9에만 존재)*
- [x] Approval-required operations are listed clearly. *(base.md §6, ai_roles 각 파일 모두 명시됨)*
- [ ] Prompt/library/resource/executable roles are not mixed. *(자료실·실행파일·지침점검 메뉴에 대응하는 ai_roles 파일이 없음)*

---

## Findings

### F-1 [HIGH] ai_roles 미작성 메뉴 3개 — 자료실·실행파일·지침점검

**위치**: `templates/ai_roles/` 폴더

현재 ai_roles 파일이 존재하는 메뉴: knowledge, lab, project-manager, prompts, settings (5개)
ai_roles 파일이 없는 메뉴: **resources (자료실), executables (실행파일), instruction_review (지침점검)** (3개)

실제 구현된 페이지(`resources.php`, `executables.php`, `instruction_review.php`)는 존재하지만,
해당 메뉴에서 AI 작업 세션을 시작할 때 사용할 역할 경계 파일이 없다.
ai_roles 없이 세션을 열면 허용 파일 범위와 절대 건드리지 않는 파일 목록이 정의되지 않은 채 작업이 시작된다.

**제안**: `templates/ai_roles/resources.md`, `executables.md`, `instruction_review.md` 작성.
기존 ai_roles 파일 구조(역할 / 허용 파일 / 절대 건드리지 않는 파일 / 작업 방식 / 검증 / 완료 요약 규칙)를 그대로 따른다.

---

### F-2 [MEDIUM] base.md 보안 규칙이 type-specific 템플릿보다 현저히 약함

**위치**: `templates/instructions/base.md` §4 vs `php-erp.md` §7 vs `php-local-tool.md` §7

`base.md` §4에는 두 줄만 있다:
- `.env` / config 민감정보 출력 금지
- 사용자 입력 검증·이스케이프

반면 `php-erp.md` §7에는:
- PDO prepared statement 의무화
- DB 에러 메시지 화면 노출 금지
- 관리 페이지 로그인 세션 확인 의무화

`php-local-tool.md` §7에는:
- REMOTE_ADDR 127.0.0.1/::1 체크 의무화
- CSRF 토큰(세션 기반) 의무화
- `realpath()` 경로 정규화 의무화

`base.md`만 적용된 프로젝트(예: `static-design`, `research`, `other` 타입 파생 프로젝트)는
PDO, CSRF, realpath 규칙 없이 생성된다.
PHP가 포함될 경우 치명적 보안 공백이 된다.

**제안**: `base.md` §4에 최소 공통 보안 항목 2개 추가:
- "PHP 쿼리는 PDO prepared statement 사용. 문자열 직접 이어붙이기 금지."
- "type-specific 템플릿 §7의 보안 규칙이 base.md보다 우선한다."

---

### F-3 [MEDIUM] "완료 요약 규칙" 중복 — ai_roles 5개 모두 동일 내용 반복

**위치**: `templates/ai_roles/*.md` 전체 (5개 파일 하단부)

모든 ai_roles 파일 끝에 동일한 블록이 있다:
```
## 완료 요약 규칙
완료 후 반드시 다음 3줄로 요약한다:
1. 무엇을 바꿨는가
2. 어느 파일에서 바꿨는가
3. 왜 바꿨는가
```

`base.md` §2에 이미 "작업이 끝나면 '무엇을, 어느 파일에서, 왜' 바꿨는지 3~5줄로 요약한다"가 있다.
ai_roles는 base.md 위에 올려서 쓰는 구조이므로 중복이다.
base.md §2의 표현이 "3~5줄"인데 ai_roles는 "3줄"로 표현이 달라 혼동 가능성이 있다.

**제안**: ai_roles의 완료 요약 규칙 블록을 제거하고 base.md §2를 따르도록 통일.
단, ai_roles 파일이 base.md 없이 단독으로 쓰이는 경우가 있다면 유지.

---

### F-4 [LOW] README.md 파일명 오류 및 구조 stale

**위치**: `README.md` 구조 섹션

README.md의 구조 표에 `projects.php`로 기재되어 있으나 실제 파일은 `project_manager.php`이다.
또한 `knowledge.php`, `projects.php`, `lab.php`, `prompts.php`가 "(TODO)"로 표시되어 있으나
모두 이미 구현되어 있다. `resources.php`, `executables.php`, `instruction_review.php`도
구조에 없다.

**제안**: README.md 구조 섹션을 현재 파일 목록 기준으로 갱신.

---

### F-5 [LOW] project-manager 역할이 templates/instructions/*.md 전체 수정 허용

**위치**: `templates/ai_roles/project-manager.md` 허용 파일 목록

`templates/instructions/*.md` 가 허용 파일로 등록되어 있어 project-manager 세션이
`base.md`를 포함한 모든 instruction 템플릿을 수정할 수 있다.
`base.md`는 보안·검증 규칙의 기반이므로 사용자 승인 없이 수정되면 위험하다.

**제안**: 허용 파일을 `templates/instructions/*.md`에서
`templates/instructions/php-local-tool.md`, `php-erp.md` 등 type-specific 파일만으로 제한하고,
`base.md` 수정은 "반드시 사용자 승인" 항목에 명시적으로 추가.

---

### F-6 [LOW] instruction_reviews.json title 필드 인코딩 깨짐

**위치**: `data/instruction_reviews.json`

모든 레코드의 `title` 필드가 `"Integrated ?? ??"`, `"Codex ?? ??"`, `"Claude ?? ??"` 형태로
한글이 깨진 상태이다. 이는 PHP에서 JSON 저장 시 `JSON_UNESCAPED_UNICODE` 옵션이 누락되었거나
파일 인코딩 문제일 가능성이 있다.
현재는 표시 오류에 그치지만 검색·필터 기능 구현 시 오동작 원인이 된다.

**제안**: `instruction_review.php`의 `ir_save_reviews()` 함수에서
`json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)` 확인.

---

### F-7 [LOW] Codex/Claude 역할 구분이 base.md에 없음

**위치**: `templates/instructions/base.md`

copier 프로젝트의 `AGENTS.md` §3에는 Codex(분석·검토·범위통제)와 Claude(코드 작성·수정)의
역할 구분이 명확히 정의되어 있다. 그러나 `base.md`에는 이 구분이 없다.
새 프로젝트를 base.md 기반으로 생성하면 AI 역할 경계 없이 시작된다.

**제안**: `base.md` §3에 Codex/Claude 역할 구분 1~2줄 추가, 또는
"프로젝트별 AI 역할은 `templates/ai_roles/` 파일을 참고" 안내 추가.

---

## Recommended Prompt Rewrite

### base.md §4 보안 규칙 보완 (F-2 대응)

- **Original rule**:
  ```
  ## 4. 보안 규칙 (예외 없음)
  - `.env` / `config` 파일의 민감정보를 코드나 답변에 그대로 출력하지 않는다.
  - 사용자가 입력한 값은 검증·이스케이프한 뒤 사용한다.
  ```
- **Improved wording**:
  ```
  ## 4. 보안 규칙 (예외 없음)
  - `.env` / `config` 파일의 민감정보를 코드나 답변에 그대로 출력하지 않는다.
  - 사용자가 입력한 값은 검증·이스케이프한 뒤 사용한다.
  - PHP를 포함하는 프로젝트는 DB 쿼리에 PDO prepared statement를 사용한다. 문자열 직접 이어붙이기 금지.
  - type-specific 템플릿(§7)의 보안 규칙은 이 섹션보다 엄격하게 적용된다.
  ```
- **Reason**: 현재 base.md만으로 생성된 PHP 프로젝트가 PDO 의무 규칙 없이 시작될 수 있음.

---

## Decision

- [ ] Keep as-is
- [x] Rewrite proposed — F-1(ai_roles 3개 신규 작성), F-2(base.md §4 보완) 우선 진행 권장
- [ ] Move to another file
- [ ] Archive/remove
- [x] Needs user approval — base.md 수정은 사용자 승인 필요 (§6 기준)
