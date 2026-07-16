# 📁 AI 자동 메모리 색인 (실수·협업·학습)

> Claude 자동 메모리(`MEMORY.md` + 개별 메모리 파일)의 색인입니다.
> **원본 위치**: `%USERPROFILE%\.claude\projects\C--xampp-htdocs-copier\memory\`
> (프로젝트 폴더 밖이라 기본 백업에 안 잡혀, 별도 백업 사본을 둡니다 — 아래 "PC 이전" 참고)
> 총 18개 · 생성일 2026-06-26 · [← 통합 색인](INDEX.md)

---

## 1. 협업·작업 방식 (feedback)

| 메모리 | 파일 | 요약 |
|--------|------|------|
| patches 반영 전 확인 | `feedback_patches.md` | patches 적용은 바로 복사 말고 비교 후 물어보기 |
| 완료 기능 성공 사례 보관 | `feedback_save_success_patterns.md` | 버그 없이 끝난 기능은 dev_center 기능 보관함에 record로 저장 |
| 업로드 후 등록 실패 = 고아 | `feedback_upload_then_register_orphan.md` | 원격 업로드→로컬 등록 순서일 때 등록 실패가 고아 파일 남김 |

## 2. 실수·기술 함정 (feedback)

| 메모리 | 파일 | 요약 |
|--------|------|------|
| ⭐ UTF-8/CP949 오판 통합 | `feedback_utf8_encoding_master.md` | 한글 인코딩 오판 마스터: 도구별 기본 인코딩·바이트 확인·오진 신호 |
| 한글 .ps1 UTF-8 BOM | `feedback_ps1_utf8_bom.md` | BOM 없으면 CP949 오인 → 가짜 "missing terminator" 오류 |
| Bash 한글 CP949 함정 | `feedback_bash_korean_cp949.md` | Bash+curl 한글이 CP949로 인코딩돼 UTF-8 서버와 불일치 |
| Inno Setup UTF-8 함정 | `feedback_inno_utf8.md` | SaveStringToFile 3번째 인자는 append; UTF-8은 별도 함수 |
| PS 문자열 연결 빈값 버그 | `feedback_ps_string_concat.md` | `'str' + $var` → 빈 문자열; `[string]::Concat()` 사용 |
| VBScript 따옴표 처리 | `feedback_vbs_quoting.md` | PS에서 VBS 생성 시 `&`·따옴표 이스케이프 오류 패턴 |

## 3. 프로젝트 진행·결정 (project)

| 메모리 | 파일 | 요약 |
|--------|------|------|
| D450/D470 WebAPI 카운터 | `project_d450_webapi_counter.md` | POST /wcd/api/... PS만으로 수집 성공 |
| D470 폐토너 waste_pct | `project_d470_waste_logic.md` | waste_pct=남은 용량%; 사용량=100-waste_pct |
| D470 네트워크 경로 없음 | `project_d470_network.md` | WiFi 단절로 도달 불가, 코드 문제 아님 |
| stockio 모바일 출고 뷰 | `project_stockio_mobile.md` | ?mobile=1 분기, process_movement 재사용 |
| device_detail 모바일 뷰 | `project_mobile_view_status.md` | ?mobile=1 분기 완료, CSRF는 별도 보안 후보 |
| login 비활성 계정 차단 | `project_login_isactive_fix.md` | is_active=1 로그인 검사 추가 (QA PASS) |
| 공유 UI 컴포넌트 라이브러리 | `project_component_library.md` | 정본=`dev_center/data/component_library/registry.json`; bundle·cache는 파생물, localStorage는 초안, GDrive는 거울 |

## 4. 참조 (reference)

| 메모리 | 파일 | 요약 |
|--------|------|------|
| SNMP 토너/드럼 로직 | `reference_snmp_supply_logic.md` | docs/SNMP_SUPPLY_LOGIC.md 실데이터·OID·판별규칙 |

## 5. 인덱스

| 메모리 | 파일 | 요약 |
|--------|------|------|
| 메모리 인덱스 | `MEMORY.md` | 세션마다 로드되는 한 줄 요약 목록 (위 항목들의 원본 인덱스) |

---

## ⚠️ PC 이전 시 메모리 보존 (중요)

자동 메모리는 `%USERPROFILE%\.claude\...\memory\` 에 있어 **프로젝트 백업에 기본 포함되지 않습니다.**
따라서 백업 사본을 프로젝트 안에 둡니다.

- **백업 사본 위치**: `dev_center/data/claude_memory/` → `dev_center` 폴더가 매일 구글 드라이브 백업에 통째로 포함되므로 함께 백업됨 (git에서는 무시).
- **새 PC 복원 시**: 압축 해제 후 `dev_center\data\claude_memory\` 안의 파일을 아래 경로로 복사하면 메모리가 살아남.
  ```
  %USERPROFILE%\.claude\projects\C--xampp-htdocs-copier\memory\
  ```
  (프로젝트 경로가 `C:\xampp\htdocs\copier` 로 동일하면 폴더명 `C--xampp-htdocs-copier` 도 동일)

> 단, 이 사본은 **수동/주기적 갱신**이 필요합니다(라이브 메모리가 계속 바뀜).
> 자동 동기화 방식은 Gemini 검토 후 확정 예정 — `docs/knowledge_base/GEMINI_CONSULT_memory_backup.md` 참고.
