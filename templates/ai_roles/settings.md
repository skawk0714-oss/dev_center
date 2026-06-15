# AI 역할: 설정

## 역할
이 세션은 Dev Center **설정** 메뉴 전용이다.
`config.php` 상수 수정, `settings.php` UI 개선,
`scripts/*.ps1` 실행 스크립트 유지보수를 담당한다.

---

## 허용 파일
- `config.php`
- `settings.php`
- `scripts/*.ps1`

---

## 절대 건드리지 않는 파일
- `data/projects.json`
- `data/features.json`
- `data/lab_experiments.json`
- `data/prompts.json`
- `data/ai_workspaces.json`
- `project_manager.php`, `knowledge.php`, `lab.php`, `prompts.php`
- `C:\xampp\htdocs\copier` 하위 파일 일체

---

## 작업 방식
1. 요청이 허용 파일 범위 안인지 먼저 확인한다. 범위 밖이면 작업하지 않고 사용자에게 알린다.
2. `config.php` 수정 시 `php -l config.php` PASS 확인 후 완료 처리한다.
3. `settings.php` 수정 시 `php -l settings.php` PASS 확인 후 완료 처리한다.
4. `scripts/*.ps1` 수정 시 PowerShell Parser 검사를 실행한다:
   ```
   [System.Management.Automation.Language.Parser]::ParseFile('scripts/파일명.ps1',[ref]$t,[ref]$e); $e
   ```
   오류가 없어야 완료 처리한다.
5. `DEV_ALLOWED_ROOTS` 등 보안 관련 상수 변경 전 반드시 사용자 승인을 받는다.
6. 새 PC/다른 계정에서 실행될 스크립트에는 현재 PC 전용 사용자명·경로를 하드코딩하지 않는다.

---

## 검증
- `php -l config.php` PASS
- `php -l settings.php` PASS
- 수정된 `.ps1` PowerShell Parser 오류 0

---

## 완료 요약 규칙
완료 후 반드시 다음 3줄로 요약한다:
1. **무엇을** 바꿨는가
2. **어느 파일에서** 바꿨는가
3. **왜** 바꿨는가
