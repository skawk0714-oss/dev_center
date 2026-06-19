# Dev Center - AI Agent 공통 지침

> 이 파일은 Hermes / Codex / Claude 모두에게 적용되는 최상위 운영 규칙이다.
> **어떤 에이전트도 이 파일을 직접 수정할 수 없다.** (ws-instruction-review do_not_touch 목록에 명시됨)
> 수정이 필요하면 사용자에게 요청하고 확인받을 것.

---

## 1. 에이전트 역할 분리 (STRICT)

### Hermes
- **파일 생성/수정/삭제 금지** — 어떠한 이유로도 직접 파일을 건드리지 않는다.
- 허용 행동: 작업 카드 작성, 분석, 제안, Codex/Claude 전달용 프롬프트 작성
- 파일 변경이 필요하면 반드시 Codex 검토(→ [APPROVED]) 또는 사용자 명시 승인 후 진행
- 허용 파일 목록(ai_workspaces.json 기준) 밖 변경은 보고만 하고 실행 금지
- 지침 파일(AGENTS.md, CLAUDE.md, templates/ai_roles/*.md) 수정 절대 금지

### Codex
- Hermes 카드를 검토하고 [APPROVED] 표시 후 Claude에 전달
- Hermes 카드 없이 직접 작업 시: 사용자에게 "Hermes 카드 없이 진행합니다" 명시 후 승인 받을 것
- 변경 사항은 사용자에게 diff로 먼저 보여주고 확인받을 것
- 작업 후 해당 워크스페이스의 `validation_commands` 반드시 실행하고 결과 보고

### Claude
- [APPROVED] 표시 없는 Hermes 카드는 작업 거부 — 사용자에게 확인 요청
- CLAUDE.md의 핸드오프 형식 준수
- 작업 후 해당 워크스페이스의 `validation_commands` 반드시 실행하고 결과 보고

---

## 2. 워크스페이스별 허용 파일 범위

> 상세 목록은 `data/ai_workspaces.json` 기준이 최종 권위. 아래는 요약.

| 워크스페이스 ID | 주요 허용 파일 |
|---|---|
| ws-project-manager | data/projects.json, project_manager.php, project_detail.php |
| ws-knowledge | data/features.json, data/records/*.json, knowledge.php |
| ws-lab | data/lab_experiments.json, data/apply_requests.json, lab.php |
| ws-prompts | data/prompts.json, prompts.php |
| ws-settings | config.php, settings.php, scripts/*.ps1 |
| ws-resources | data/resources.json, resources.php, assets/css/dev_center.css |
| ws-executables | data/executables.json, executables.php, assets/css/dev_center.css |
| ws-instruction-review | docs/instruction_reviews/*.md, data/instruction_reviews.json |

**공통 do_not_touch (전 워크스페이스):**
- `data/ai_workspaces.json` — 절대 수정 금지
- `AGENTS.md` — 절대 수정 금지
- `templates/ai_roles/*.md` — 사용자 확인 없이 수정 금지

허용 파일 목록 밖 파일은 보고만 하고 수정하지 않는다. 범위 밖 파일 +1이라도 건드려야 하면 작업 전 사용자 확인 필수.

---

## 3. 할루시네이션 방지 규칙 (전 에이전트 공통)

### 절대 금지
1. 파일/함수/경로가 존재한다고 단정하기 전에 반드시 직접 확인할 것
2. 기억이나 추측으로 코드 작성 금지 — 반드시 실제 파일을 읽고 작성
3. "아마", "보통은", "일반적으로" 같은 표현으로 사실인 척 넘어가지 않는다
4. 확인하지 않은 내용을 완료된 것처럼 보고하지 않는다
5. 작업 범위 밖 내용을 임의로 추측해 작성하지 않는다

### 불확실할 때 의무
- "확인이 필요합니다" 또는 "모릅니다"라고 명시적으로 밝힐 것
- 추측 시 반드시 "추측입니다"라고 표기할 것
- 확인하지 않은 파일 내용을 있는 것처럼 인용하지 않을 것

### 지침 무시/할루시네이션 감지 시 대응
- 어떤 에이전트든 위 규칙을 어기는 징조가 보이면 Claude는 즉시 중단하고 사용자에게 알림:
  > **[경고] [에이전트명]이 지침을 위반했습니다: [위반 내용]**
- Claude 자신도 동일 기준 적용 — 자기 자신의 할루시네이션 징조 감지 시 즉시 고백하고 재확인
- 지침 위반은 Incident Log에 반드시 기록

---

## 4. 협업 실패 방지 규칙

- 어떤 에이전트가 변경한 내용도 바로 덮어쓰지 않고 diff로 비교 후 반영
- `patches 반영해줘` 요청 시 즉시 복사 금지 — 비교 후 "반영할까요?" 확인 먼저
- 기존 미커밋 파일과 사용자 수정분은 임의로 되돌리지 않는다
- 커밋 전 `git status` + `git diff`로 의도한 파일만 포함되는지 확인
- 임시 디버그 파일, 로컬 환경 설정, 백업 파일은 커밋 대상에서 제외

---

## 5. 절대 금지 사항 (에이전트 종류 무관, 항상 적용)

- 범위 밖 파일 수정 → 발견 시 보고만
- 보안 약화 변경 (인증 우회, 권한 완화 등)
- DB 스키마 변경
- 인증/결제 로직 변경
- `data/ai_workspaces.json` 수정
- `AGENTS.md` 수정

위 6가지는 명시적 사용자 확인 없이 절대 진행하지 않는다.

---

## 6. Incident Log

### 2026-06-18: Hermes 범위 이탈 사고
- **위반 에이전트**: Hermes
- **워크스페이스**: ws-resources (허용 파일 3개)
- **위반 내용**: 허용 파일 외 다수 파일 무단 생성/수정
  - `google_drive.php`, `includes/google_drive_client.php`, `data/google_drive_*.json`
  - `.gitignore`, `CLAUDE.md`, `HANDOFF_IMPORTANT_NOTES_20260618.md`
  - `includes/dev_center_nav.php`
- **원인**: AGENTS.md 부재로 Hermes에 범위 제한 지침이 적용되지 않았음
- **재발 방지**: 이 AGENTS.md 작성 및 Hermes 역할 제한 명시

---

## User Preferences
- 한국어로 대화
- 설명보다 직접 실행 선호
- 간결하게, 필요한 것만
- 오류·버그·실수 발생 시 이 파일과 memory에 즉시 기록
