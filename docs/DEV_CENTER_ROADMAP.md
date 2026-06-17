# Dev Center Roadmap

**작성일**: 2026-06-17
**기준 스냅샷**: [docs/DEV_CENTER_CURRENT_STATE.md](DEV_CENTER_CURRENT_STATE.md)

---

## Vision

Dev Center는 단일 프로젝트(copier) 전용 도구가 아니라,
**모든 개발 프로젝트의 중앙 운영 허브**다.

- 여러 프로젝트에서 반복 사용되는 기능(feature)을 보관하고 어디에 적용됐는지 추적한다.
- 실험 아이디어 → 반영 요청 → 프로젝트 적용의 흐름을 관리한다.
- 문서·자료·실행파일·프롬프트·지침을 한 곳에서 인덱싱한다.
- 미래에는 배포 관리, 장비/모델 프로파일 관리까지 담당한다.

각 메뉴는 하나의 데이터 소유자를 가지며, 다른 메뉴의 데이터를 직접 수정하지 않는다.

---

## Core Principles

1. **유지보수 우선** — 새 기능을 빠르게 붙이는 것보다, 나중에 찾고 고치고 확장하기 쉬운 구조를 우선한다.
2. **프로젝트 코드와 메타데이터 분리** — Dev Center는 각 프로젝트의 코드를 직접 수정하지 않는다. 메타데이터(CURRENT_TASK.md, 보고서, 인덱스)만 관리한다.
3. **기능 추적 가능성** — 재사용 가능한 기능은 어느 프로젝트에 적용됐는지 항상 추적할 수 있어야 한다.
4. **위험한 자동화 금지** — AGENTS.md 자동 수정, 원격 에이전트 자동 업데이트, 임의 파일 자동 실행은 사용자 승인 없이 하지 않는다.
5. **대용량 자산은 인덱싱** — 프린터 드라이버, 스캔 파일 같은 대용량 파일은 복사하지 않고 경로/URL만 인덱싱한다.

---

## Current State

현재 메뉴·데이터·템플릿 구조는 [DEV_CENTER_CURRENT_STATE.md](DEV_CENTER_CURRENT_STATE.md)를 참고한다.

요약:
- 메뉴 9개 운영 중 (홈, 프로젝트관리, 기능 보관함, 실험실, 프롬프트, 자료실, 실행파일, 지침점검, 설정)
- 데이터 파일 10개, ai_workspaces 8개, instruction templates 8개, ai_roles 8개

---

## Target Structure

완성 목표 구조는 다음과 같다. 각 섹션은 독립된 데이터 소유권을 갖는다.

| 영역 | 데이터 소유 | 설명 |
|------|------------|------|
| 프로젝트 | `data/projects.json` | 프로젝트 목록, 상태, CURRENT_TASK.md 생성 |
| 기능 보관함 | `data/features.json`, `data/records/*.json` | 재사용 가능한 기능 레코드 + 적용 프로젝트 추적 |
| 기능 적용 맵 | `data/apply_requests.json` (확장) | feature → projects, project → features 양방향 조회 |
| 자료실 | `data/resources.json` | 참고 자료 / 프로젝트 에셋 / 배포 패키지 / 드라이버·스캔 분류 |
| 실행파일 | `data/executables.json` | 현장 실행용 + 내부 도구, 메타데이터 + 경로 복사 |
| 프롬프트/지침 | `data/prompts.json`, `templates/` | 프롬프트 라이브러리 + 역할 템플릿 + 프로젝트 규칙 템플릿 |
| 지침점검 | `data/instruction_reviews.json`, `docs/instruction_reviews/` | 리뷰 보고서 생성, 개선 제안, 사용자 승인 흐름 |
| 배포/업데이트 관리 | (미래) `data/deployments.json` | 설치 패키지 버전, 에이전트 버전, 고객 PC 배포 상태 |
| 장비/모델 프로파일 | (미래) `data/device_profiles.json` | D410/D420/D450/D470 OID, WebProbe 상태, 에이전트 호환성 |

---

## Phase 1 — Feature Application Map

**목표**: 기능이 어느 프로젝트에 적용됐는지, 프로젝트에 어떤 기능이 쓰였는지 조회할 수 있게 한다.

**왜 먼저 하는가**: 현재 features.json에 기능은 있지만 "이 기능을 실제로 어디에 썼는가"가 추적되지 않는다. 기능 재사용률이 낮아지는 원인이다.

**대상 파일**:
- `data/features.json` — `applied_projects` 필드 추가 (배열)
- `data/records/*.json` — 적용 프로젝트 참조 추가
- `data/apply_requests.json` — 현재 빈 배열; 구조 정의 필요
- `knowledge.php` — feature 카드에 "적용 프로젝트" 표시
- `project_detail.php` — 프로젝트별 적용 기능 목록 표시

**출력 형태**:
- Feature → Projects: 이 기능이 적용된 프로젝트 목록
- Project → Features: 이 프로젝트에 적용된 기능 목록

**구현 방식**: 읽기 전용 먼저. data 수정 → UI 표시 순서로 진행.

---

## Phase 2 — Project Detail Hub

**목표**: `project_detail.php`가 단순 정보 표시를 넘어, 프로젝트와 연결된 모든 자산을 한 화면에서 볼 수 있게 한다.

**프로젝트 상세에서 보여줄 항목**:
- 적용된 기능 목록 (Phase 1 기반)
- 대기 중인 apply requests
- 관련 실험실 항목
- 최신 지침 리뷰 노트
- 관련 자료/실행파일 링크

**전제 조건**: Phase 1 완료 후 진행.

---

## Phase 3 — Resource Classification

**목표**: 자료실 항목을 종류와 저장 위치로 세분화하여, 향후 Google Drive 연동 기반을 만든다.

**스키마 확장안**:

```json
{
  "resource_kind": "reference | project_asset | deployment_package | driver_package | scan_package",
  "storage_type": "local | url | google_drive | external_disk"
}
```

| `resource_kind` | 설명 |
|-----------------|------|
| `reference` | 참고 문서, 링크, 메뉴얼 |
| `project_asset` | 특정 프로젝트에서 사용하는 에셋 |
| `deployment_package` | 배포용 ZIP, 설치 패키지 |
| `driver_package` | 프린터 드라이버 등 대용량 파일 |
| `scan_package` | 스캔 유틸리티, 스캔 설정 파일 |

**Google Drive 연동**:
- Phase 3에서는 schema + placeholder 수준만 구현한다.
- 실제 Drive API 연동은 별도 Phase로 분리한다.

---

## Phase 4 — AI Work Management

**목표**: 현재 분리된 프롬프트(`prompts.php`)와 지침점검(`instruction_review.php`) 메뉴를 **AI 작업관리** 섹션으로 통합하는 방안을 검토한다.

**통합 후 예상 탭 구성**:
- 프롬프트
- 지침점검
- 역할 템플릿 (templates/ai_roles/)
- 프로젝트 규칙 템플릿 (templates/instructions/)

**조건**: 현재 메뉴는 그대로 유지한다. 통합이 실제로 유지보수에 유리하다고 판단될 때만 진행한다. 메뉴 통합은 "5개 이상 파일 변경" 기준에 해당하므로 사용자 승인이 필요하다.

---

## Phase 5 — Deployment and Update Management

**목표**: 고객 PC 배포 현황과 에이전트 버전 관리를 Dev Center에서 중앙 관리한다.

**관리 대상**:
- 설치 패키지 버전 이력
- 에이전트(SNMP 수집기) 버전
- 고객별 배포 상태 (설치됨 / 업데이트 필요 / 미설치)
- 업데이트 요청 큐

**데이터**: `data/deployments.json` 신규 설계 필요.
**전제 조건**: copier 에이전트 리뉴얼 완료 후 진행.

---

## Phase 6 — Device / Model Profile Center

**목표**: 복사기 모델별 수집 프로파일과 에이전트 호환성을 Dev Center에서 관리한다.

**관리 대상**:
- D410 / D420 / D450 / D470 OID 프로파일
- WebProbe 폴백 상태 (SNMP 실패 시 대체 수집 방식)
- 각 모델별 프로덕션 에이전트 호환성
- 수집 실패 이력 및 원인 분류

**데이터**: `data/device_profiles.json` 신규 설계 필요.
**전제 조건**: Phase 5 이후 또는 병행 진행 가능.

---

## Not Now

아직 만들지 않는 것들. 요청이 있어도 사용자 명시적 승인 없이는 진행하지 않는다.

| 항목 | 이유 |
|------|------|
| Google Drive 자동 동기화 | API 인증, 용량, 권한 관리 복잡도가 높음 |
| AGENTS.md 자동 패치 | 지침 파일은 항상 사람이 검토 후 적용 |
| 원격 에이전트 자동 업데이트 | 고객 PC 영향 범위가 크므로 수동 승인 필요 |
| 브라우저에서 파일 직접 실행 | 보안 위험; 경로 복사 방식 유지 |
| 전체 페이지 대규모 리팩터링 | 기능 추가와 혼합하지 않고 별도 작업으로 분리 |

---

## Next Recommended Step

**Phase 1 — Feature Application Map, 읽기 전용부터 시작**

1. `data/features.json` 스키마에 `applied_projects` 배열 필드 추가
2. `knowledge.php` 기능 카드에 적용 프로젝트 표시 (읽기 전용)
3. `project_detail.php`에 "이 프로젝트에 적용된 기능" 섹션 추가

작업 범위가 작고 기존 기능을 깨지 않으며, 이후 Phase 2의 기반이 된다.
시작 전 `CURRENT_TASK.md`를 먼저 작성하고 사용자 승인을 받는다.
