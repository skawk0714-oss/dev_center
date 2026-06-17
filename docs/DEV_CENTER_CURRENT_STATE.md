# Dev Center 현재 상태 스냅샷

**기준일**: 2026-06-17
**목적**: 다음 로드맵 작업 전 현재 메뉴·데이터·템플릿 구조를 기록한다.

---

## 1. 현재 메뉴 구성

| 메뉴 | 파일 | 상태 | 설명 |
|------|------|------|------|
| 홈 | `index.php` | 운영 중 | 대시보드, AI 워크스페이스 버튼 |
| 프로젝트관리 | `project_manager.php` + `project_detail.php` | 운영 중 | 프로젝트 목록, CURRENT_TASK.md 생성, instruction 템플릿 선택 |
| 기능 보관함 | `knowledge.php` | 운영 중 | 재사용 가능한 기능 레코드 인덱스 |
| 실험실 | `lab.php` | 운영 중 | 실험 아이디어 관리, apply request 생성 |
| 프롬프트 | `prompts.php` | 운영 중 | 프롬프트 라이브러리 관리 |
| 자료실 | `resources.php` | 운영 중 | 문서·도구·자료 인덱스 (경로 복사) |
| 실행파일 | `executables.php` | 운영 중 | 실행파일 메타데이터 + 경로 복사 |
| 지침점검 | `instruction_review.php` | 운영 중 | AI 지침 리뷰 보고서 생성 |
| 설정 | `settings.php` | 운영 중 | config.php 상수, 스크립트 관리 |

---

## 2. 데이터 파일 현황

| 파일 | 항목 수 | 역할 |
|------|---------|------|
| `data/projects.json` | 2 | 프로젝트 목록 (copier, dev_center) |
| `data/features.json` | 8 | 기능 보관함 인덱스 |
| `data/records/*.json` | 9개 파일 | 기능 상세 레코드 |
| `data/lab_experiments.json` | 13 | 실험 아이디어 목록 |
| `data/apply_requests.json` | 0 | 반영 요청 큐 (현재 비어 있음) |
| `data/prompts.json` | 7 | 프롬프트 항목 |
| `data/resources.json` | 7 | 자료실 항목 |
| `data/executables.json` | 5 | 실행파일 항목 |
| `data/instruction_reviews.json` | 6 | 지침점검 보고서 메타데이터 |
| `data/ai_workspaces.json` | 8 | AI 워크스페이스 정의 |

### ai_workspaces 구성 (8개)

| ID | 메뉴 | AI 도구 |
|----|------|---------|
| ws-project-manager | 프로젝트관리 | codex, claude, vscode-codex, vscode-claude |
| ws-knowledge | 기능 보관함 | codex, claude, vscode-codex, vscode-claude |
| ws-lab | 실험실 | codex, claude, vscode-codex, vscode-claude |
| ws-prompts | 프롬프트 | claude, vscode-claude |
| ws-settings | 설정 | codex, vscode-codex |
| ws-resources | 자료실 | codex, claude, vscode-codex, vscode-claude |
| ws-executables | 실행파일 | codex, claude, vscode-codex, vscode-claude |
| ws-instruction-review | 지침점검 | codex, claude, vscode-codex, vscode-claude |

---

## 3. 템플릿 현황

### templates/instructions/ (8개 파일)

| 파일 | 용도 |
|------|------|
| `base.md` | 모든 프로젝트 공통 기반 규칙 (§1~§7, §3-1 유지보수 원칙 포함) |
| `php-local-tool.md` | PHP 로컬 도구 특화 (CSRF, REMOTE_ADDR, realpath) |
| `php-erp.md` | PHP ERP 특화 (PDO, 세션 인증, DB 에러 숨김) |
| `laravel-saas.md` | Laravel SaaS 특화 (Migration, 멀티테넌트) |
| `powershell-automation.md` | PowerShell 5.1 특화 (StrictMode, Parser 검사) |
| `research.md` | 리서치/분석 특화 (출처 명시, data/ 저장) |
| `static-design.md` | 정적 프론트엔드 특화 (mobile-first, CSS 변수) |
| `other.md` | 기타 프로젝트용 기본 틀 |

### templates/ai_roles/ (8개 파일)

| 파일 | 대상 메뉴 |
|------|----------|
| `project-manager.md` | 프로젝트관리 |
| `knowledge.md` | 기능 보관함 |
| `lab.md` | 실험실 |
| `prompts.md` | 프롬프트 |
| `settings.md` | 설정 |
| `resources.md` | 자료실 *(2026-06-17 신규 추가)* |
| `executables.md` | 실행파일 *(2026-06-17 신규 추가)* |
| `instruction-review.md` | 지침점검 *(2026-06-17 신규 추가)* |

모든 ai_roles 파일은 동일한 구조를 따른다:
역할 / 허용 파일 / 절대 건드리지 않는 파일 / 작업 방식 / 검증 / 완료 요약 규칙

---

## 4. 현재 주요 워크플로

### 4-1. 실험실 → 반영 요청 → 프로젝트

1. `lab.php`에서 실험 아이디어를 `data/lab_experiments.json`에 등록
2. 실험이 충분히 검증되면 apply request(`data/apply_requests.json`)로 이동
3. apply request는 대상 프로젝트와 연결, 프롬프트/체크리스트 포함
4. 현재 apply_requests.json은 비어 있음 — 큐 활성화 전 단계

### 4-2. 자료실 / 실행파일

- 항목 등록 후 경로 복사 버튼으로 로컬 파일 경로를 클립보드에 복사
- 브라우저 직접 실행 없음 — metadata + path-copy 방식
- 자료실: 문서·링크·도구 인덱스 / 실행파일: .ps1, .exe, .bat 등 실행파일 메타데이터

### 4-3. 지침점검

1. `instruction_review.php`에서 리뷰 타입(codex/claude/integrated) 선택 후 보고서 생성
2. 생성된 마크다운 보고서(`docs/instruction_reviews/`)를 수동으로 채움
3. 보고서 → 개선 제안 → 사용자 승인 → 별도 작업으로 지침 수정
4. **지침 파일 자동 수정 없음** — Phase 1 범위

### 4-4. AI 워크스페이스 버튼

- 각 메뉴 페이지에 Codex / Claude / VSCode(Codex) / VSCode(Claude) 버튼
- `data/ai_workspaces.json`으로 버튼 구성 관리
- 버튼 클릭 → `scripts/launch_project.ps1` 실행 → 해당 ai_roles 프롬프트 복사 후 세션 시작

---

## 5. 알려진 다음 방향

아래는 확정된 계획이 아니라 현재 논의 중인 방향이다.

| 항목 | 설명 | 상태 |
|------|------|------|
| 기능-프로젝트 연결 맵 | features.json 항목을 어느 프로젝트에 적용할지 매핑 | 미착수 |
| 프로젝트 상세 기능 요약 | project_detail.php에 적용된 기능 목록 표시 | 미착수 |
| 자료실 Google Drive 연동 | Phase 1은 schema/placeholder만, 실제 연동은 Phase 2 | 설계 중 |
| 프롬프트/지침 통합 | prompts.json과 instruction templates 역할 경계 명확화 | F-7 제안됨 |
| 배포/업데이트 관리 | copier 배포본 버전 관리, 업데이트 체크 | 미착수 |
| README.md 갱신 | 파일명 오류(projects.php → project_manager.php), TODO 항목 제거 | F-4 제안됨 |
| base.md 보안 보강 | PDO 규칙 및 type-specific 우선 명시 | F-2 제안됨 |

---

## 6. 미해결 기술 부채

| 코드 | 설명 | 출처 |
|------|------|------|
| F-2 | base.md 보안 규칙이 php-erp/php-local-tool 대비 약함 | 통합 지침 리뷰 ir-20260616-006 |
| F-3 | ai_roles 완료 요약 규칙 중복 (base.md §2와 동일) | 통합 지침 리뷰 ir-20260616-006 |
| F-4 | README.md stale (파일명 오류, TODO 항목 잔존) | 통합 지침 리뷰 ir-20260616-006 |
| F-5 | project-manager 역할이 base.md 수정 허용 | 통합 지침 리뷰 ir-20260616-006 |
| F-6 | instruction_reviews.json title 필드 한글 깨짐 | 통합 지침 리뷰 ir-20260616-006 |
| F-7 | Codex/Claude 역할 구분이 base.md에 없음 | 통합 지침 리뷰 ir-20260616-006 |
