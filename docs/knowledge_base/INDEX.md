# 📚 통합 문서 색인 (Knowledge Base)

> 여러 프로젝트에 흩어진 마크다운 문서를 한곳에서 찾기 위한 **색인(목차)** 입니다.
> 원본 파일은 각 프로젝트에 그대로 있으며, 이 색인은 **제목·경로·요약만** 모읍니다.
> 생성일: 2026-06-26 · 대상: `copier`, `dev_center`

---

## 프로젝트별 색인

| 프로젝트 | 색인 파일 | 문서 수 | 설명 |
|----------|-----------|---------|------|
| **copier** (복사기 임대·유지보수 ERP) | [copier.md](copier.md) | 83 | 운영 ERP 본체. 규칙·워크플로우·기획·리뷰·기술로직 |
| **dev_center** (개발 전용 허브) | [dev_center.md](dev_center.md) | 28 | 프로덕션과 분리된 개발 허브. 지침·템플릿·역할·로드맵 |
| **AI 자동 메모리** (실수·협업·학습) | [ai_memory.md](ai_memory.md) | 17 | Claude 자동 메모리. 프로젝트 폴더 밖이라 PC 이전 시 별도 보존 필요 |

> `stockguard`, vendor/node_modules/backups 안의 라이브러리 문서는 이번 정리 대상에서 제외했습니다.

---

## 빠른 참조 (자주 찾는 문서)

| 무엇이 필요할 때 | 문서 | 위치 |
|------------------|------|------|
| **AI 도구·`/`명령어 사용법** | **AI_TOOLS_MANUAL.md** | `dev_center/docs/knowledge_base/` |
| copier 작업 규칙 전체 | AGENTS.md | `copier/AGENTS.md` |
| copier 구조 파악 | ARCHITECTURE.md | `copier/ARCHITECTURE.md` |
| 지금 하는 작업 확인 | CURRENT_TASK.md | `copier/CURRENT_TASK.md`, `dev_center/CURRENT_TASK.md` |
| 과거 실수·사고 기록 | INCIDENT_LOG.md | `copier/docs/INCIDENT_LOG.md` |
| SNMP 소모품 수집 로직 | SNMP_SUPPLY_LOGIC.md | `copier/docs/SNMP_SUPPLY_LOGIC.md` |
| 새 PC 복원 | recovery/README.md | `copier/recovery/README.md` |
| UI 컴포넌트 기준 | ui_components.md / UI_GUIDE.md | `copier/docs/`, `copier/` |
| 공유 UI 컴포넌트 라이브러리 | COMPONENT_LIBRARY.md / Component Playground | `dev_center/docs/COMPONENT_LIBRARY.md`, `http://localhost/dev_center/component_playground/` |
| 개발센터 현황·로드맵 | DEV_CENTER_CURRENT_STATE / ROADMAP | `dev_center/docs/` |
| 새 프로젝트 지침 템플릿 | templates/instructions/ | `dev_center/templates/instructions/` |

---

## 분류 체계 (공통)

두 프로젝트 색인은 아래 범주로 묶여 있습니다.

1. **규칙·지침** — AI 협업 규칙, 아키텍처, 프로젝트 헌장
2. **워크플로우·협업** — 작업 흐름, 리뷰 체크리스트, 에이전트 핸드오프
3. **기획·로드맵** — 제품 완성 계획, 기능 도입 계획
4. **기술 로직·지식** — 수집 로직, 사고 기록, 작업 로그
5. **리뷰·기준선** — diff 리뷰, 마이그레이션/기준선 점검 (대부분 날짜 스냅샷)
6. **UI/UX** — 컴포넌트 가이드, 디자인 레퍼런스, 템플릿
7. **운영·배포·복원** — 배포 체크리스트, QA, 복원 가이드
8. **프롬프트·템플릿** — 재사용 프롬프트, 지침 템플릿
9. **아이디어·보고서** — 백로그, 작업 보고서
