## 7. PowerShell 자동화 특화 규칙

- PowerShell 5.1 (Windows PowerShell) 기준으로 작성한다. `&&` 파이프 체인 연산자 사용 금지.
- 스크립트 상단에 `#Requires -Version 5.1` 과 `Set-StrictMode -Version Latest` 를 추가한다.
- 경로에는 `[System.IO.Path]::GetFullPath()` 로 정규화하고 `../` 트래버설을 차단한다.
- 다른 PC/계정에서 실행될 스크립트에 현재 사용자명(`USERLEE` 등)을 하드코딩하지 않는다.
- 새 `.ps1` 파일을 만들거나 수정하면 PowerShell Parser 문법 검사를 실행한다.
  ```
  [System.Management.Automation.Language.Parser]::ParseFile('파일.ps1',[ref]$null,[ref]$errors)
  ```
- ZIP 생성 시 `System.IO.Compression` 과 `System.IO.Compression.FileSystem` 을 모두 로드한다.

### 초기 작업
{{INITIAL_TASK}}
