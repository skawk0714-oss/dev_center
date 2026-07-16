# 📁 dev_center 문서 색인

> 개발 전용 허브(`C:\xampp\htdocs\dev_center`)의 마크다운 문서 목록입니다.
> 프로덕션 ERP와 분리된 독립 PHP 프로젝트. 경로는 `dev_center/` 기준 상대경로.
> 총 28개 · 생성일 2026-06-26 · [← 통합 색인](INDEX.md)

---

## 1. 규칙·지침

| 문서 | 경로 | 요약 |
|------|------|------|
| AGENTS.md | `AGENTS.md` | Hermes·Codex·Claude 공통 최상위 운영 규칙 |
| CLAUDE.md | `CLAUDE.md` | Claude 작업 지침 (`[APPROVED]` 없는 카드 확인 요청) |
| CURRENT_TASK.md | `CURRENT_TASK.md` | 현재 작업: 구글 드라이브 연동·자료실 업로드 통합 |
| README.md | `README.md` | 개발센터 소개 (CopierRMS 개발 전용 로컬 허브) |

## 2. 현황·로드맵

| 문서 | 경로 | 요약 |
|------|------|------|
| 현재 상태 스냅샷 | `docs/DEV_CENTER_CURRENT_STATE.md` | Dev Center 현재 상태 (기준 2026-06-17) |
| 로드맵 | `docs/DEV_CENTER_ROADMAP.md` | Dev Center 로드맵 (2026-06-17) |

## 3. 지침 점검 리뷰 (2026-06-16)

| 문서 | 경로 | 요약 |
|------|------|------|
| ir-001 Codex 리뷰 | `docs/instruction_reviews/20260616_001_codex_review.md` | 지침 점검 보고 (Codex) |
| ir-002 Claude 리뷰 | `docs/instruction_reviews/20260616_002_claude_review.md` | 지침 점검 보고 (Claude) |
| ir-003 통합 리뷰 | `docs/instruction_reviews/20260616_003_integrated_review.md` | 통합 지침 점검 보고 |
| ir-004 Codex 리뷰 | `docs/instruction_reviews/20260616_004_codex_review.md` | 지침 점검 보고 (Codex) |
| ir-005 Claude 리뷰 | `docs/instruction_reviews/20260616_005_claude_review.md` | 지침 점검 보고 (Claude) |
| ir-006 통합 리뷰 | `docs/instruction_reviews/20260616_006_integrated_review.md` | 통합 지침 점검 보고 |

## 4. AI 역할 템플릿 (메뉴별 세션 지침)

| 문서 | 경로 | 요약 |
|------|------|------|
| 프로젝트관리 | `templates/ai_roles/project-manager.md` | 프로젝트관리 메뉴 전용 역할 |
| 기능 보관함 | `templates/ai_roles/knowledge.md` | 기능 보관함 메뉴 전용 역할 |
| 실험실 | `templates/ai_roles/lab.md` | 실험실 메뉴 전용 역할 |
| 실행파일 보관함 | `templates/ai_roles/executables.md` | 실행파일 메뉴 전용 역할 |
| 지침점검 | `templates/ai_roles/instruction-review.md` | 지침점검 메뉴 전용 역할 |
| 프롬프트 | `templates/ai_roles/prompts.md` | 프롬프트 라이브러리 역할 |
| 자료실 | `templates/ai_roles/resources.md` | 자료실 메뉴 전용 역할 |
| 설정 | `templates/ai_roles/settings.md` | 설정 메뉴 전용 역할 |

## 5. 프로젝트 지침 템플릿 (새 프로젝트 부트스트랩)

| 문서 | 경로 | 요약 |
|------|------|------|
| 베이스 | `templates/instructions/base.md` | AGENTS.md 기본 골격 ({{PROJECT_NAME}}) |
| PHP ERP | `templates/instructions/php-erp.md` | PHP ERP 특화 규칙 (PDO 강제 등) |
| PHP 로컬 도구 | `templates/instructions/php-local-tool.md` | 로컬 전용 도구 보안 가드 |
| Laravel SaaS | `templates/instructions/laravel-saas.md` | Laravel SaaS 특화 규칙 |
| PowerShell 자동화 | `templates/instructions/powershell-automation.md` | PS 5.1 기준 자동화 규칙 |
| 정적 디자인 | `templates/instructions/static-design.md` | HTML/CSS/JS 프론트엔드 규칙 |
| 리서치/분석 | `templates/instructions/research.md` | 출처 명시 등 리서치 규칙 |
| 기타 | `templates/instructions/other.md` | 기타 프로젝트 특화 규칙 |

## 6. UI/UX 컴포넌트 지식베이스

| 문서/도구 | 경로 | 요약 |
|-----------|------|------|
| 공유 UI 컴포넌트 라이브러리 | `docs/COMPONENT_LIBRARY.md` | copier용 검증 UI 컴포넌트의 정본·기여·검증 규칙 (Phase 1 MVP) |
| Component Playground | `component_playground/index.html` (`http://localhost/dev_center/component_playground/`) | 컴포넌트 미리보기·초안 작성·기여 JSON 내보내기. 정본=`data/component_library/registry.json` |
