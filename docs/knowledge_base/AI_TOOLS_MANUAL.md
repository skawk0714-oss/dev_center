# 🤖 AI 개발환경 사용 설명서

> 지금 이 PC에서 실제로 돌아가고 있는 AI 개발 도구 전체 설명서입니다.
> 작성일: 2026-07-13 · 대상 PC: Windows 11 / XAMPP / dev_center

---

## 1. 전체 구조 한눈에 보기

```
                 ┌──────────────────────────────┐
                 │      Claude Code (메인)      │
                 │   모델: Opus 4.8             │
                 │   여기서 모든 걸 지시함      │
                 └──────────────┬───────────────┘
                                │
     ┌──────────────┬───────────┼───────────┬──────────────┐
     │              │           │           │              │
 ┌───▼────┐   ┌─────▼─────┐ ┌───▼────┐ ┌────▼─────┐  ┌─────▼─────┐
 │ Codex  │   │  스킬     │ │ 메모리 │ │   MCP    │  │  Chrome   │
 │(GPT-5) │   │ (/명령어) │ │ 자동   │ │ 외부연동 │  │  브라우저 │
 │ 2차검토│   │ 워크플로우│ │ 기억   │ │ 카톡/캔바│  │  자동화   │
 └────────┘   └───────────┘ └────────┘ └──────────┘  └───────────┘
```

**핵심**: Claude Code가 사령탑이고, 나머지는 전부 그 안에서 호출됩니다.
AI끼리 결과물을 주고받을 때 **복사-붙여넣기가 필요 없습니다.** (Zapier나 n8n 같은 외부 자동화 도구가 불필요한 이유)

---

## 2. 설치되어 있는 것들

| 구분 | 이름 | 하는 일 |
|------|------|---------|
| **플러그인** | `codex` (openai-codex) | GPT-5 기반 Codex CLI 연동. 2차 검토·구조 진단 담당 |
| **플러그인** | `ui-ux-pro-max` | UI/UX 디자인 지능 (색상·폰트·레이아웃·차트) |
| **플러그인** | `claude-dashboard` | 하단 상태줄 + AI CLI 사용량 확인 |
| **MCP** | KakaoTalk (PlayMCP) | 카톡 나에게 메모 보내기 |
| **MCP** | UsStockInfo | 미국 주식 정보 조회 |
| **MCP** | Canva | 디자인 생성·편집 |
| **MCP** | Claude in Chrome | 크롬 브라우저 자동화 (클릭·폼입력·스크린샷) |
| **메모리** | 자동 메모리 | `~/.claude/projects/.../memory/` 에 학습 내용 자동 저장 |

**설정 파일 위치**
- 전역 설정: `C:\Users\USERLEE\.claude\settings.json`
- 전역 지침: `C:\Users\USERLEE\.claude\CLAUDE.md` (Lee 개인 취향·규칙)
- 프로젝트 지침: `C:\xampp\htdocs\dev_center\CLAUDE.md` (Hermes/Codex/Claude 협업 규칙)
- 메모리 색인: `~/.claude/projects/C--xampp-htdocs-dev-center/memory/MEMORY.md`

**현재 최적화 설정**
```json
MAX_THINKING_TOKENS: 4000       // 생각 토큰 제한 (비용 절약)
CLAUDE_AUTOCOMPACT_PCT_OVERRIDE: 50   // 컨텍스트 50% 차면 자동 압축
model: opus
```

---

## 3. `/` 명령어 전체 목록

### 3-1. 매일 쓰는 기본 명령어 (Claude Code 내장)

| 명령어 | 설명 |
|--------|------|
| `/clear` | 대화 기록 완전 초기화. **새 주제로 넘어갈 때 필수** |
| `/compact` | 대화를 요약해서 압축. 맥락은 유지하면서 토큰 절약 |
| `/config` | 모델·테마 등 설정 변경 |
| `/init` | 프로젝트에 CLAUDE.md 새로 생성 |
| `/fast` | 빠른 모드 토글 (Opus 그대로, 출력만 빨라짐) |
| `/help` | 도움말 |

> 💡 `!명령어` 형태로 입력하면 셸 명령이 바로 실행되고 결과가 대화에 들어옵니다.
> 예: `!git status`, `!codex login`

---

### 3-2. Codex 연동 (GPT-5와 협업)

이게 **"AI 두 개를 연결한다"의 실체**입니다.

| 명령어 | 설명 |
|--------|------|
| `/codex:setup` | Codex 설치·로그인 상태 점검 |
| `/codex:setup --enable-review-gate` | 작업 끝낼 때 Codex 리뷰를 **강제**하도록 설정 |
| `/codex:rescue` | Claude가 막혔거나 2차 의견이 필요할 때 Codex에게 넘김 |

**언제 쓰나**
- Claude가 같은 버그를 두 번 이상 못 고칠 때 → `/codex:rescue`
- 큰 리팩토링 전에 구조 진단이 필요할 때 → `/codex:rescue`
- 중요한 커밋 전 2차 검토 → `/codex:rescue`

**현재 상태**: Codex CLI 0.144.1, ChatGPT 로그인 활성(skawk0714@gmail.com), 리뷰 게이트 **켜짐** (2026-07-13)

> 🔒 **리뷰 게이트가 켜져 있음**
> Claude가 작업을 끝내고 멈추려 할 때, 변경사항에 대한 Codex 리뷰가 최신이 아니면 통과되지 않습니다.
> 커밋 전 2차 검토가 자동으로 강제됩니다.
> 끄고 싶으면: `/codex:setup --disable-review-gate`

---

### 3-3. 코드 리뷰 & 검증

| 명령어 | 설명 |
|--------|------|
| `/code-review` | 현재 변경사항(diff) 리뷰. 버그 + 개선점 |
| `/code-review high` | 더 깊게 (놓치는 것 줄이되 오탐 증가) |
| `/code-review ultra` | 클라우드 멀티 에이전트 심층 리뷰 (별도 과금) |
| `/code-review --fix` | 리뷰 후 발견된 것 자동 수정까지 |
| `/security-review` | 보안 관점 리뷰 |
| `/verify` | 코드가 **실제로 동작하는지** 브라우저·실행으로 확인 |
| `/run` | 앱 띄워서 화면 확인 |
| `/simplify` | 코드 단순화·중복 제거 (버그는 안 봄) |

> ⚠️ 커밋 전에는 `/verify` 한 번 돌리는 습관을 권장합니다. 테스트 통과 ≠ 실제 동작.

---

### 3-4. 기획 · 구조 (설치한 커스텀 명령어)

| 명령어 | 설명 |
|--------|------|
| `/superpowers` | 체계적 기획 → 구현 워크플로우 |
| `/improve-arch` | 코드 구조 분석 & 리팩토링 제안 |
| `/focused-fix` | "이 기능 전체를 제대로 작동하게" — 모듈 전체 심층 수리 |

---

### 3-5. DB · SQL

| 명령어 | 설명 |
|--------|------|
| `/sql-database-assistant` | SQL 작성·성능 튜닝·마이그레이션 |
| `/database-designer` | 스키마 설계, 관계 모델링 |
| `/database-schema-designer` | ERD 다이어그램, 정규화 |

---

### 3-6. UI / 디자인

| 명령어 | 설명 |
|--------|------|
| `/ui-ux-pro-max` | 색상 팔레트, 폰트 조합, 레이아웃, 스타일 (161개 팔레트 내장) |
| `/dataviz` | 차트·그래프·대시보드 만들 때 (**차트 그리기 전에 먼저**) |
| `/artifact-design` | 공유 가능한 웹페이지 만들 때 |

---

### 3-7. 점검 · 관리

| 명령어 | 설명 |
|--------|------|
| `/claude-dashboard:check-usage` | Claude·Codex·Gemini 사용량 한도 확인 |
| `/tech-debt-tracker` | 기술부채 스캔·우선순위 |
| `/dependency-auditor` | 라이브러리 취약점·라이선스 점검 |
| `/codebase-onboarding` | 낯선 코드베이스 파악 문서 자동 생성 |
| `/fewer-permission-prompts` | 권한 확인창 줄이기 (자주 쓰는 명령 허용목록 등록) |

---

### 3-8. 자동화 · 예약

| 명령어 | 설명 |
|--------|------|
| `/loop 5m /명령어` | 5분마다 반복 실행 |
| `/schedule` | cron 방식 예약 실행 (클라우드 에이전트) |
| `/update-config` | 훅(hook) 설정 — "X 할 때마다 자동으로 Y" |

---

## 4. 실전 사용 시나리오

### 시나리오 A — 새 기능 개발
```
1. /clear                    ← 깨끗한 상태로 시작
2. "○○ 기능 만들어줘"       ← 그냥 말로 지시
3. /verify                   ← 진짜 되는지 확인
4. /code-review              ← 버그 점검
5. (중요하면) /codex:rescue  ← GPT-5 2차 검토
6. "커밋해줘"
```

### 시나리오 B — 버그가 안 잡힐 때
```
1. Claude에게 2번 시켰는데 못 고침
2. /codex:rescue             ← GPT-5에게 진단 넘김
3. Codex 결과가 Claude 대화창으로 자동 회수됨
4. Claude가 그 진단으로 수정
```

### 시나리오 C — 토큰이 아까울 때
```
- 주제 바뀌면 무조건 /clear
- 같은 주제로 길어지면 /compact
- /claude-dashboard:check-usage 로 잔량 확인
```

---

## 5. 협업 규칙 (dev_center/CLAUDE.md에 박혀 있음)

**Claude가 작업 전 반드시 확인하는 것**
- Hermes 카드에 `[APPROVED]` 표시 없으면 → 작업 안 하고 확인 요청
- 변경 대상 파일 경로, 허용 변경, 금지 항목, 검증 기준 없으면 → 요청

**항상 금지 (사용자 확인 없이 절대 안 함)**
1. 범위 밖 파일 수정
2. 보안 약화 (인증 우회, 권한 완화)
3. DB 스키마 변경
4. 인증/결제 로직 변경

**핸드오프 프롬프트 형식**
```
Task: [한 줄 요약]
Target files: path/to/file.php (수정)
Allowed changes: [구체적 변경]
Do NOT change: [금지 항목]
Validation: [완료 기준]
Risk flags: [없음 / DB변경 / 인증 / 결제]
```

---

## 6. 자주 하는 실수 (메모리에 기록된 것들)

| 실수 | 대응 |
|------|------|
| PowerShell `+` 문자열 연결 버그 | `[string]::Concat()` 사용 |
| 한글 PS1 파일 BOM 누락 | UTF-8 **BOM 포함**으로 저장 |
| `mysqldump --databases` 복원 사고 | 격리 import 시 `USE` 문 제거 필수 |
| 구글드라이브 백업 멈춤 | refresh_token 만료 의심 → `gd_reauth.php` |
| 수집기 IP 충돌 | 에이전트 보유 회사는 중앙 collector에서 제외 |

> 이 목록은 `~/.claude/.../memory/MEMORY.md` 에서 자동 관리됩니다.

---

## 7. 알아둘 것

**Zapier / n8n / API 직접연동이 필요한가?**

| 목적 | 맞는 도구 |
|------|-----------|
| 코딩할 때 AI 둘이 협업 | **Claude Code + Codex** ← 이미 완비 |
| 백업 실패 알림 등 외부 이벤트 | n8n / Zapier (아직 미도입) |
| dev_center에 AI 기능 **탑재** | Anthropic API 직접 호출 (아직 미도입) |

지금 워크플로우는 파일시스템과 프로젝트 컨텍스트를 Claude·Codex가 **공유**하기 때문에,
텍스트만 넘기는 Zapier식 자동화보다 강력합니다. 굳이 도입할 이유가 없습니다.

---

*최종 수정: 2026-07-13*
