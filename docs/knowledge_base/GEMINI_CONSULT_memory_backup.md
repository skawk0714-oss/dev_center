# Gemini 검토 요청 — AI 자동 메모리의 PC 이전 보존 방식

> 작성: Claude · 2026-06-26 · 상태: **Gemini 의견 대기**
> 목적: 자동 메모리를 새 PC 이전 시에도 잃지 않도록 백업/복원 방식을 확정.

---

## 배경 (현재 상황)

- Claude 자동 메모리는 `%USERPROFILE%\.claude\projects\C--xampp-htdocs-copier\memory\` 에 17개 파일로 존재.
- 이 경로는 **프로젝트 폴더(htdocs) 밖**이라, 매일 도는 구글 드라이브 백업(`dev_center/backup_dev_pc.php`, 스케줄러 `DevCenter_DailyBackup`)의 `paths` 에 안 들어감 → **PC 이전 시 소실 위험**.
- backup_dev_pc.php는 `backup_config.json`의 `paths` 폴더들을 zip 하며, `node_modules/vendor`만 제외(.gitignore는 무시).

## Claude가 이미 한 임시 조치 (되돌리기 쉬움)

1. 메모리 17개를 `dev_center/data/claude_memory/` 로 **복사**(스냅샷). → dev_center 폴더가 백업 대상이라 자동으로 같이 백업됨.
2. `dev_center/.gitignore` 에 `data/claude_memory/` 추가 → git에는 안 올라가고 백업에만 포함.
3. 색인 `ai_memory.md` 에 메모리 목록 + 복원 안내 추가.

→ **오늘 시점 데이터 유실 위험은 막음.** 단, 이 사본은 **수동 스냅샷**이라 라이브 메모리가 바뀌면 낡음.

---

## 결정이 필요한 것 (Gemini 의견 요청)

### Q1. 동기화 방식 — 사본을 어떻게 최신으로 유지할까?
- **(A) 백업 스크립트가 직접 수집**: `backup_dev_pc.php` 가 zip 만들 때 `%USERPROFILE%\.claude\...\memory\` 를 읽어 `claude_memory/` 로 넣는다. → config(`backup_config.json`)에 절대경로 1줄 추가가 깔끔할지, 코드에 전용 단계로 넣을지?
- **(B) 별도 sync 단계**: 백업 직전 memory→data/claude_memory 복사하는 작은 스크립트를 스케줄러/백업 전처리로.
- **(C) 현행 수동 유지**: 메모리 추가될 때 Claude가 사본도 같이 갱신.
- 어느 쪽이 유지보수·안전성 면에서 나은가?

### Q2. 복원 wiring — 새 PC에서 .claude로 되돌리기
- 현재 `copier/recovery/2_restore.ps1` 은 DB·에이전트·스케줄러만 처리. 여기에 "`claude_memory/` → `%USERPROFILE%\.claude\projects\C--xampp-htdocs-copier\memory\` 복사" 단계를 추가할지?
- 주의: 사용자명 하드코딩 금지(AGENTS.md). `%USERPROFILE%` 사용. 프로젝트 경로가 동일하면 해시 폴더명 `C--xampp-htdocs-copier` 도 동일.
- 기존 메모리가 있으면 덮어쓸지/병합할지?

### Q3. 위치가 dev_center/data 가 맞나?
- data/ 는 앱 데이터(json)와 섞이는 곳. 메모리 사본을 여기 두는 게 적절한지, 아니면 `dev_center/backups/` 같은 전용 폴더가 나은지?

### Q4. 범위 — 이게 backup_dev_pc.php / 2_restore.ps1 수정을 동반하면 별도 작업 카드로?
- Q1(A)·Q2 채택 시 백업 생성기와 복원 스크립트 양쪽을 건드림. AGENTS.md상 별도 검토 대상.

---

## Gemini가 답할 형식 (제안)
- `판정`: Claude 임시 조치 유지 가능 여부 (통과/보완)
- `권장 방식`: Q1/Q2/Q3 각각에 대한 선택과 이유
- `Claude에게 줄 작업 카드`: 채택 시 backup_dev_pc.php / 2_restore.ps1 수정 범위를 「Gemini→Claude 작업 전달 필수 형식」으로
- `위험 플래그`: 백업/복원 스크립트 변경, 경로 처리

---

# Gemini 결정 (2026-06-26)

- **Q1 동기화**: (A) `backup_dev_pc.php`가 ZIP 생성 시 `%USERPROFILE%\.claude\...\memory`를 실시간으로 읽어 ZIP의 `claude_memory/`로 추가.
- **Q2 복원**: `2_restore.ps1`이 `C:\xampp\htdocs\claude_memory` 존재 시 `$env:USERPROFILE\.claude\projects\C--xampp-htdocs-copier\memory\`로 복사(-Force).
- **Q3 위치**: `data/`에 두지 않고 **ZIP 내부에만** 존재. 임시 사본 `data/claude_memory` 삭제.
- **Q4 범위**: backup_dev_pc.php + 2_restore.ps1 양쪽 수정 → 독립 작업 카드로.
- (작업 카드 원문은 사용자 메시지에 포함)

---

# Claude 피드백 (2026-06-26)

## 판정: 방향 동의(통과) — 단, 구현 전 4가지 보강

### 1. 스케줄러 실행 계정 — 확인 완료, 이 PC는 안전 ✅ (但 가시성 보강)
- Gemini 안의 최대 위험은 "백업 작업이 SYSTEM으로 돌면 `getenv('USERPROFILE')`가 `C:\Windows\system32\config\systemprofile`로 잡혀 메모리를 못 읽는" 것이었다.
- **실측: `DevCenter_DailyBackup` 은 SYSTEM(S-1-5-18)이 아니라 일반 사용자 SID(…-1002)로 실행** → `USERPROFILE = C:\Users\USERLEE` 정상 해석. 함정 미발생.
- 단 `LogonType = S4U`(사용자 비로그인 상태로 실행)라, 프로필 미로드 엣지케이스 가능성은 남는다. → **메모리 폴더를 못 찾으면 조용히 건너뛰지 말고 `bk_log()`로 경고를 남길 것.** 안 그러면 "메모리 빠진 백업이 며칠 누적"되는 사고를 늦게 발견한다.

### 2. 경로를 코드에 박지 말고 `backup_config.json`으로 (강력 권장)
- `backup_dev_pc.php` 헤더 철학 = "설정은 `backup_config.json`에서 편집, **코드 수정 불필요**". 메모리 경로와 해시 폴더명(`C--xampp-htdocs-copier`)을 PHP에 하드코딩하면 이 철학과 충돌.
- → config 키(예: `claude_memory_path` 또는 `claude_project_hash`) 추가. 새 PC에서 프로젝트 경로가 다르면 해시 폴더명도 달라지므로, config로 빼면 그때 코드 수정 없이 대응 가능.

### 3. 임시 사본 삭제는 "첫 새-방식 백업 검증 후"
- `data/claude_memory` 삭제 자체는 동의. 단 **코드만 바뀌고 첫 백업이 돌기 전**에 지우면 보호 공백이 생긴다.
- → 순서: 코드 적용 → 백업 1회 실행 → **ZIP 안에 `claude_memory/`가 실제로 담겼는지 확인** → 그 다음 `data/claude_memory` + .gitignore 줄 제거.

### 4. 복원 측 마무리 2가지
- (a) `2_restore.ps1`이 `.claude`로 복사한 뒤 남는 `C:\xampp\htdocs\claude_memory`를 정리할지(선택). 안 지우면 htdocs에 잔여 폴더가 남는다.
- (b) `-Force` 덮어쓰기는 마이그레이션엔 맞지만, **기존 PC에서 잘못 실행하면 최신 라이브 메모리를 옛 백업으로 덮을 위험**. → README/스크립트에 "복원은 새 PC에서만" 경고 명시.

## 작업 카드 보강 제안 (그대로 쓰되 아래 추가)
- `Allowed changes`에 1·2 반영: PHP는 **config에서 경로를 읽도록**, 메모리 폴더 없으면 `bk_log` 경고 후 계속(백업 중단 금지).
- `Validation`에 추가: **백업 1회 실제 실행 → 생성 ZIP에 `claude_memory/` 포함 확인** (단순 문법검사만으로는 실효성 부족).
- 삭제(3)는 검증 통과 후 별도 커밋/단계로.

## 결론
Gemini 설계는 견고하다. **이 PC 한정으론 계정 함정이 없어 바로 가도 되지만**, 위 1~3을 반영하면 다른 PC·비로그인 백업에서도 안전하다. 작업 카드는 보강안 반영 후 진행 권장.

---

# ✅ 확정 작업 카드 (보강 반영본) — 2026-06-26

```text
Task: Implement Real-time AI Memory Backup and Restore (config-driven)

Target files (수정 가능):
- c:\xampp\htdocs\dev_center\backup_dev_pc.php
- c:\xampp\htdocs\dev_center\data\backup_config.json
- c:\xampp\htdocs\copier\recovery\2_restore.ps1
읽기 전용 참고:
- c:\xampp\htdocs\dev_center\docs\knowledge_base\ai_memory.md (복원 경로 설명)

Allowed changes:
1. backup_config.json:
   - 새 키 추가: "claude_memory_path" (기본값 "%USERPROFILE%/.claude/projects/C--xampp-htdocs-copier/memory").
     경로 하드코딩 금지 — PHP는 이 키에서 읽는다. 키가 없거나 빈 값이면 메모리 백업 단계는 건너뛴다.
2. backup_dev_pc.php:
   - ZIP 생성 단계에서 config의 claude_memory_path를 getenv('USERPROFILE') 등으로 실제 경로로 해석한다.
   - 해당 폴더가 존재하면 내부 파일을 ZIP의 "claude_memory/" 접두사로 추가한다(기존 addFile 패턴 그대로).
   - 폴더가 없거나 비어 있으면 bk_fail 하지 말고 bk_log()로 경고만 남기고 백업을 계속한다(메모리 누락을 조용히 넘기지 말 것).
   - 추가한 메모리 파일 수를 bk_log로 출력한다.
3. 2_restore.ps1:
   - 복원 단계 추가: "C:\xampp\htdocs\claude_memory" 가 존재하면
     "$env:USERPROFILE\.claude\projects\C--xampp-htdocs-copier\memory\" 로 복사(-Force).
     대상 폴더 없으면 New-Item으로 생성. 원본 없으면 경고만 하고 계속.
   - 복사 성공 후 잔여 "C:\xampp\htdocs\claude_memory" 는 정리(삭제)한다.
   - 체크리스트/안내에 "메모리 복원은 새 PC에서만 — 기존 PC 재실행 시 최신 메모리를 덮을 수 있음" 경고 1줄 추가.

Do NOT change:
- 기존 백업 워크플로(DB 덤프, 소스 폴더 zip, 드라이브 업로드, keep_count 보관정책) 로직.
- 기존 DB 복원/에이전트/스케줄러 복원 로직.
- data/claude_memory 삭제와 .gitignore 줄 제거는 이 카드에서 하지 않는다(아래 단계 3 별도).

Validation (완료 전 필수):
1. php -l backup_dev_pc.php  → PASS
2. PowerShell Parser: [System.Management.Automation.Language.Parser]::ParseFile(
   'C:\xampp\htdocs\copier\recovery\2_restore.ps1',[ref]$tokens,[ref]$errors) → 오류 0
3. 백업 1회 실제 실행(또는 ZIP 생성 단계까지) 후, 생성 ZIP 안에 "claude_memory/" 폴더와
   메모리 파일들이 실제로 담겼는지 확인. (문법검사만으로는 불충분)
4. claude_memory_path 키를 지운 상태에서도 백업이 정상 완료되는지(건너뛰기) 확인.

Risk flags: 경로 해석(폴더 없을 때 백업/복원 중단 금지), 덮어쓰기(-Force) 복원.

후속(별도 단계 — 위 Validation 3 통과 확인 후에만):
- data/claude_memory 폴더 삭제
- dev_center/.gitignore 에서 "data/claude_memory/" 줄 제거
```

> 이 카드는 Gemini 원안 + Claude 보강(config화 / 경고 로깅 / 삭제 분리 / 복원 마무리)을 합친 **확정본**이다.
> 사용자 승인 시 이 카드대로 구현 진행.
