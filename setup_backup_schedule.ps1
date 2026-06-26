#Requires -Version 3.0
<#
  개발 PC 백업 매일 자동 실행 작업 등록/해제 스크립트.
  관리자 PowerShell에서 실행한다.

  등록:  powershell -ExecutionPolicy Bypass -File setup_backup_schedule.ps1 -Time "03:00"
  해제:  powershell -ExecutionPolicy Bypass -File setup_backup_schedule.ps1 -Remove
#>
param(
    [string]$Time = '03:00',
    [switch]$Remove
)

$ErrorActionPreference = 'Stop'
$TaskName = 'DevCenter_DailyBackup'
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$Php  = 'C:\xampp\php\php.exe'
$Target = Join-Path $ScriptDir 'backup_dev_pc.php'

if ($Remove) {
    if (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue) {
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
        Write-Host "[OK] 자동 백업 작업을 해제했습니다: $TaskName"
    } else {
        Write-Host "[INFO] 등록된 작업이 없습니다: $TaskName"
    }
    return
}

if (-not (Test-Path $Php))    { throw "PHP 를 찾을 수 없습니다: $Php" }
if (-not (Test-Path $Target)) { throw "백업 스크립트를 찾을 수 없습니다: $Target" }

# php.exe 를 직접 실행(콘솔 최소화). 작업 폴더를 dev_center 로 지정.
$action  = New-ScheduledTaskAction -Execute $Php `
    -Argument "-d memory_limit=512M `"$Target`"" `
    -WorkingDirectory $ScriptDir
$trigger = New-ScheduledTaskTrigger -Daily -At $Time
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd `
    -ExecutionTimeLimit (New-TimeSpan -Hours 3)
# 로그인 안 해도 실행되도록 현재 사용자 권한으로 등록
$principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType S4U -RunLevel Limited

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger `
    -Settings $settings -Principal $principal -Force | Out-Null

Write-Host "[OK] 매일 $Time 자동 백업 작업을 등록했습니다: $TaskName"
Write-Host "     해제하려면: powershell -ExecutionPolicy Bypass -File setup_backup_schedule.ps1 -Remove"
