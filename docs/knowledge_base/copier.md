# 📁 copier 문서 색인

> 복사기 임대·유지보수 관리 ERP(`C:\xampp\htdocs\copier`)의 마크다운 문서 목록입니다.
> 경로는 `copier/` 기준 상대경로. 원본은 해당 위치에 그대로 있습니다.
> 총 83개 · 생성일 2026-06-26 · [← 통합 색인](INDEX.md)

---

## 1. 규칙·지침

| 문서 | 경로 | 요약 |
|------|------|------|
| AGENTS.md | `AGENTS.md` | Gemini·Claude가 세션 시작 시 읽는 최상위 규칙 (모든 규칙의 원본) |
| CLAUDE.md | `CLAUDE.md` | AGENTS.md를 그대로 참조 |
| GEMINI.md | `GEMINI.md` | AGENTS.md를 그대로 참조 |
| ARCHITECTURE.md | `ARCHITECTURE.md` | 프로젝트 전체 구조 설명서 (구조 파악 필요 시 읽음) |
| AI Project Rules | `.ai/PROJECT_RULES.md` | AI 에이전트용 헌법 문서 (single source of truth) |
| AI Architecture Ref | `.ai/ARCHITECTURE.md` | 코드 작성 전 읽는 아키텍처 레퍼런스 |
| Workflow Guardrails | `.ai/WORKFLOW_GUARDRAILS.md` | 무분별한 AI 편집 루프 방지 규칙 |
| Menu/Section Map | `.ai/MENU_SECTION_MAP.md` | 기능↔메뉴/페이지 오연결 방지 지도 |
| AI Ops Index | `.ai/README.md` | .ai 폴더 운영 규칙 색인 |
| Project Plan | `.ai/PROJECT_PLAN.md` | 프로젝트 계획 (변경이력은 patch_notes) |
| Codex Start | `.ai/CODEX_START.md` | Codex 붙여넣기용 시작 템플릿 |

## 2. 워크플로우·협업

| 문서 | 경로 | 요약 |
|------|------|------|
| Development Charter | `docs/development_charter.md` | CopierRMS를 운영급 시스템으로 만드는 헌장 |
| Agent Roles | `docs/workflows/agent_roles.md` | Codex·Claude·오너 협업 방식 정의 |
| Feature Workflow | `docs/workflows/feature_workflow.md` | 기능/버그/개선 작업 흐름 |
| Review Checklist | `docs/workflows/review_checklist.md` | 머지·푸시·운영 전 점검표 |
| Benchmark Log | `docs/workflows/benchmark_log.md` | 주요 기능 참고 레퍼런스 기록 |
| Branching Strategy | `docs/branching_strategy.md` | Git 브랜치 전략 |
| Agent Inbox | `docs/agent_inbox/README.md` | Codex·Claude·오너 파일 기반 논의 공간 |
| → decisions | `docs/agent_inbox/decisions.md` | 오너 승인 결정 기록 |
| → claude_to_codex | `docs/agent_inbox/claude_to_codex.md` | Claude→Codex 전달 |
| → codex_to_claude | `docs/agent_inbox/codex_to_claude.md` | Codex→Claude 전달 |
| Auto Handoff | `.ai_handoff/auto/README.md` | Claude 결과를 Codex가 읽는 자동 전달 공간 |
| → claude_task / done / fix_prompt / codex_review | `.ai_handoff/auto/*.md` | 핸드오프 상태 파일 4종 |

## 3. 프롬프트 (재사용)

| 문서 | 경로 | 요약 |
|------|------|------|
| 새 기능 | `.ai/prompts/01_new_feature.md` | 새 기능 추가 프롬프트 |
| 버그 수정 | `.ai/prompts/02_bug_fix.md` | 버그 수정 프롬프트 |
| 새 페이지 | `.ai/prompts/03_new_page.md` | 신규 PHP 페이지 프롬프트 |
| DB 변경 | `.ai/prompts/04_db_change.md` | 테이블/컬럼 변경 프롬프트 |
| API 엔드포인트 | `.ai/prompts/05_api_endpoint.md` | AJAX/API 추가 프롬프트 |
| 영향 분석 | `.ai/prompts/06_impact_analysis.md` | 수정 전 영향 범위 분석 |
| 보안 점검 | `.ai/prompts/07_security_audit.md` | 단일 파일 보안 취약점 점검 |

## 4. 기획·로드맵

| 문서 | 경로 | 요약 |
|------|------|------|
| 제품 완성 로드맵 | `docs/PRODUCT_COMPLETION_ROADMAP.md` | CopierRMS 완성까지 로드맵 (기준 2026-06-04) |
| 에이전트 리뉴얼 계획 | `docs/AGENT_RENEWAL_PLAN_20260612.md` | 에이전트 설치/등록 리뉴얼 (2026-06-12) |
| 수집 경로 설계 | `docs/COPIER_COLLECTION_PLAN.md` | SNMP vs 웹 Playwright 수집 설계 |
| SNMP 학습형 수집기 | `docs/SNMP_LEARNING_COLLECTOR_PLAN_20260605.md` | 학습형 수집기 도입 계획 |
| 웹 폴백 추출기 | `docs/WEB_FALLBACK_EXTRACTOR_PLAN.md` | D450/D470 웹 UI Playwright 탐침 계획 |
| UI/UX 개선 계획 | `UI_UX_PLAN.md` | UI/UX 작업 후보 목록 (2026-05-30) |

## 5. 기술 로직·지식

| 문서 | 경로 | 요약 |
|------|------|------|
| SNMP 소모품 로직 | `docs/SNMP_SUPPLY_LOGIC.md` | 토너/드럼 수집·표시 로직 (재적용 전 필독) |
| 지식 아카이브 | `docs/knowledge/README.md` | 완료·리뷰된 핵심 기능 지식 보관 |
| 사고 기록 | `docs/INCIDENT_LOG.md` | 과거 버그·QA 실패·에이전트 실수 기록 |
| 작업 로그 | `docs/WORK_LOG.md` | 업무 기록 (한국어) |
| 중요 작업 기준 | `docs/HANDOFF_IMPORTANT_NOTES_20260618.md` | 휘발 방지용 중요 기준 메모 (2026-06-18) |

## 6. 리뷰·기준선 (날짜 스냅샷)

| 문서 | 경로 | 요약 |
|------|------|------|
| billing diff | `docs/BILLING_DIFF_REVIEW_20260605.md` | billing.php diff 리뷰 |
| companies diff | `docs/COMPANIES_DIFF_REVIEW_20260605.md` | companies.php diff 리뷰 |
| dashboard diff | `docs/DASHBOARD_DIFF_REVIEW_20260605.md` | dashboard.php diff 리뷰 |
| scrape_meter diff | `docs/SCRAPE_METER_DIFF_REVIEW_20260605.md` | scrape_meter.php diff 리뷰 |
| stockio diff | `docs/STOCKIO_DIFF_REVIEW_20260605.md` | stockio.php diff 리뷰 |
| toner diff | `docs/TONER_DIFF_REVIEW_20260605.md` | toner.php diff 리뷰 |
| 큰 diff 대상 | `docs/LARGE_DIFF_REVIEW_TARGETS_20260605.md` | 큰 diff 리뷰 대상 목록 |
| 민감내용 점검 | `docs/BASELINE_SENSITIVE_REVIEW_20260611.md` | 커밋 전 위험 항목 점검 (2026-06-11) |
| 정리 승인 체크 | `docs/CLEANUP_APPROVAL_CHECKLIST_20260611.md` | 정리 승인 체크리스트 |
| 잔여 정리 리뷰 | `docs/REMAINING_CLEANUP_REVIEW_20260611.md` | 잔여 정리 항목 코드 점검 |
| Git 기준선 분류 (6/5) | `docs/GIT_BASELINE_FILE_CLASSIFICATION_20260605.md` | git status 파일 분류 |
| Git 기준선 분류 (6/11) | `docs/GIT_BASELINE_FILE_CLASSIFICATION_20260611.md` | git status 파일 분류 |
| 마이그레이션 점검 | `docs/MIGRATION_BASELINE_REVIEW_20260605.md` | 마이그레이션 기준선 점검 |
| 마이그레이션 적용현황 | `docs/MIGRATION_APPLIED_STATUS_20260611.md` | copier_rms 스키마 검증 |
| 제품 기준선 결정 | `docs/PRODUCT_BASELINE_DECISION_20260605.md` | 제품 기준선 최종 결정안 |
| 제품 기준선 커밋계획 | `docs/PRODUCT_BASELINE_COMMIT_PLAN_20260611.md` | 기준선 커밋 계획 |

## 7. UI/UX

| 문서 | 경로 | 요약 |
|------|------|------|
| UI 컴포넌트 가이드 | `UI_GUIDE.md` | 통일된 공통 UI 컴포넌트 기준 |
| UI Component Guide | `docs/ui_components.md` | 모든 UI 작업의 기준(영문) |
| UI 프레임워크 결정 | `docs/ui_framework_decision.md` | UI 프레임워크 결정 (2026-05-27) |
| UI 통일 적용 기준 | `docs/UI_STANDARD_ROLLOUT.md` | UI/UX 통일 적용 기준 (2026-06-02) |
| UI 바디 디자인 레퍼런스 | `docs/design/UI_BODY_DESIGN_REFERENCE.md` | 윤비서풍 디자인 시스템 (ERP·StockGuard 공통) |
| 모달 템플릿 | `docs/templates/modal/README.md` | 승인된 모달 디자인 재사용 |
| → PHP 사용법 | `docs/templates/modal/usage-php.md` | Plain PHP 적용법 |
| → Laravel 사용법 | `docs/templates/modal/usage-laravel.md` | Blade 적용법 |

## 8. 운영·배포·복원

| 문서 | 경로 | 요약 |
|------|------|------|
| 운영 문서 모음 | `docs/README.md` | 운영자 참고 문서 목록 |
| 배포 체크리스트 | `docs/deploy_checklist.md` | XAMPP→운영 배포 순서 |
| 배포 보고서 | `docs/deploy_report_20260526.md` | 2026-05-26 배포 보고 |
| 수동 QA 체크리스트 | `docs/MANUAL_QA_CHECKLIST_20260606.md` | 핵심 수동 QA 점검표 |
| 새 PC 복원 가이드 | `recovery/README.md` | 구글 드라이브 백업본 복원 (4단계) |
| PC 이전 가이드 | `recovery/PC_MIGRATION_FINAL_GUIDE.md` | PC 이전 최종 가이드 (2026-06-15) |
| 실전 운영 매뉴얼 | `사용설명서.md` | AI 협업 개발 초보자용 매뉴얼 |

## 9. 아이디어·보고서·패치

| 문서 | 경로 | 요약 |
|------|------|------|
| 아이디어 보관 | `docs/ideas/README.md` | 기능·사업화·구조 아이디어 보관 |
| → 백로그 | `docs/ideas/BACKLOG.md` | 아이디어 백로그 |
| → 디자인랩 | `docs/ideas/design-lab.md` | 디자인 실험실 아이디어 |
| → 수익화 | `docs/ideas/monetization-ideas.md` | 수익화 아이디어 |
| → 프로젝트 분류 | `docs/ideas/project-classification.md` | 상태별 프로젝트 분류 |
| 보고서 템플릿 | `reports/REPORT_TEMPLATE.md` | 작업 보고서 양식 |
| 정산 취소 보고 | `reports/report_20260527_billing_cancel.md` | billing 확정취소 기능 |
| 판매장비 개편 | `reports/report_20260527_sales_device_workflow.md` | 판매장비 구조 개편 1단계 |
| 판매장비 버그수정 | `reports/report_20260527_sales_device_workflow_fix.md` | 워크플로우 버그 수정 |
| 패치 README (5/13) | `patches/2026-05-13_web-scraping/README.md` | 웹 스크래핑 컬러/흑백 카운터 |
| 패치 CHANGELOG | `patches/2026-05-13_web-scraping/CHANGELOG.md` | 패치 변경 내역 |
