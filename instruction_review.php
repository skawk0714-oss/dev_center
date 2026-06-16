<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['dc_csrf_token'])) {
    $_SESSION['dc_csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['dc_csrf_token'];

$reviewsFile = DC_DATA_DIR . '/instruction_reviews.json';
$reportsDir  = __DIR__ . '/docs/instruction_reviews';

/* ── helpers ── */
function ir_e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ir_load_reviews(string $path): array {
    if (!is_file($path)) { return []; }
    $data = json_decode((string)file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function ir_save_reviews(string $path, array $data): bool {
    return (bool)file_put_contents(
        $path,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

function ir_next_id(array $reviews, string $dateStr): string {
    $prefix = 'ir-' . $dateStr . '-';
    $max = 0;
    foreach ($reviews as $r) {
        $id = (string)($r['id'] ?? '');
        if (str_starts_with($id, $prefix)) {
            $num = (int)substr($id, strlen($prefix));
            if ($num > $max) { $max = $num; }
        }
    }
    return $prefix . str_pad((string)($max + 1), 3, '0', STR_PAD_LEFT);
}

/* ── reviewed_files per type ── */
function ir_default_files(string $type): array {
    $base = ['AGENTS.md'];
    $roles = [
        'templates/ai_roles/project-manager.md',
        'templates/ai_roles/knowledge.md',
        'templates/ai_roles/lab.md',
        'templates/ai_roles/prompts.md',
        'templates/ai_roles/settings.md',
    ];
    $instructions = [
        'templates/instructions/base.md',
        'templates/instructions/php-local-tool.md',
    ];
    if ($type === 'codex') {
        return array_merge($base, $roles, ['data/prompts.json']);
    }
    if ($type === 'claude') {
        return array_merge($base, $instructions, ['data/prompts.json']);
    }
    // integrated
    return array_merge($base, $roles, $instructions, [
        'data/prompts.json',
        'docs/INCIDENT_LOG.md',
    ]);
}

/* ── markdown template per type ── */
function ir_markdown(string $type, string $id, string $now): string {
    $typeLabel = match($type) {
        'codex'      => 'Codex',
        'claude'     => 'Claude',
        'integrated' => 'Integrated',
        default      => $type,
    };
    $focusBlock = match($type) {
        'codex' => <<<MD
        - 리뷰 역할 수행 품질
        - 분석 깊이와 정확성
        - CURRENT_TASK 처리 방식
        - 검증 규율 준수 여부
        - 프롬프트 작성 품질
        - 코드 직접 수정 경계 준수
        MD,
        'claude' => <<<MD
        - 범위 한정 구현 여부
        - 최종 diff 규율
        - 검증 명령 실행 여부
        - 완료 요약 품질
        - 무관한 리팩터링 미수행 여부
        - 사용자 승인 필요 작업 사전 확인 여부
        MD,
        default => <<<MD
        - 중복 규칙 여부
        - 충돌 규칙 여부
        - 보안 규칙 상태
        - DB 마이그레이션 규칙 상태
        - 인증 규칙 상태
        - 인시던트 로그 관리
        - 모델 업그레이드 가이드 최신화
        - 유지보수 우선 원칙 일관성
        MD,
    };
    $filesBlock = implode("\n", array_map(
        fn($f) => "- {$f}",
        ir_default_files($type)
    ));

    $typeChecks = match($type) {
        'codex' => <<<MD
        ## Codex-Specific Checks
        - [ ] Codex does not directly modify code when the project rule says review-only.
        - [ ] Codex writes Claude-ready prompts clearly.
        - [ ] Codex checks CURRENT_TASK.md before making recommendations.
        - [ ] Codex separates findings, risks, and next actions.
        - [ ] Codex does not approve unverified changes.

        ## Claude-Specific Checks
        - [ ] Claude keeps final diff scoped to the requested task.
        - [ ] Claude does not change auth/DB/schema without approval.
        - [ ] Claude validates modified PHP/PowerShell/JSON files.
        - [ ] Claude summarizes what/where/why in Korean.
        - [ ] Claude does not leave unrelated formatting changes.
        MD,
        'integrated' => <<<MD
        ## Integrated Checks
        - [ ] Codex and Claude role boundaries are clear.
        - [ ] Security, auth, DB migration, and validation rules do not conflict.
        - [ ] Maintainability-first rule is present and does not allow broad refactors.
        - [ ] Incident logging rules are clear.
        - [ ] Approval-required operations are listed clearly.
        - [ ] Prompt/library/resource/executable roles are not mixed.
        MD,
        default => '',
    };

    return <<<MD
    # Instruction Review Report

    **ID**: {$id}
    **Type**: {$typeLabel}
    **Generated**: {$now}

    > ⚠️ 이 보고서는 초안입니다. AI가 자동으로 지침 파일을 수정하지 않습니다.
    > 모든 변경은 사용자 검토 및 승인 후 수동으로 적용합니다.

    ---

    ## Type
    {$typeLabel}

    ## Focus Areas
    {$focusBlock}

    ## Reviewed Files
    {$filesBlock}

    ---

    ## Findings
    | Severity | Location | Problem | Why it matters | Suggested fix |
    |---|---|---|---|---|
    | — | — | — | — | — |

    ## Duplicates
    - Duplicate rule:
    - Locations:
    - Suggested consolidation:

    ## Conflicts
    - Conflicting rules:
    - Locations:
    - Suggested resolution:

    ## Weak Or Unclear Rules
    - Rule:
    - Issue:
    - Suggested rewrite:

    ## Upgrade Suggestions
    - Suggested new rule:
    - Reason:
    - Expected benefit:

    {$typeChecks}

    ## Recommended Prompt Rewrite
    - Original rule:
    - Improved wording:
    - Reason:

    ## Decision
    - [ ] Keep as-is
    - [ ] Rewrite proposed
    - [ ] Move to another file
    - [ ] Archive/remove
    - [ ] Needs user approval

    ## Patch Proposal
    Do not apply automatically.
    Proposed changes must be reviewed by the user first.

    ## Validation Checklist
    - [ ] No role boundary conflict
    - [ ] No security rule weakening
    - [ ] No DB migration rule weakening
    - [ ] No auth rule weakening
    - [ ] No validation rule weakening
    - [ ] Maintainability-first rule preserved
    - [ ] User approval required before applying
    MD;
}

/* ── POST: generate report ── */
$postError   = null;
$postSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenOk = isset($_POST['csrf_token'])
        && hash_equals($csrf, (string)$_POST['csrf_token']);
    if (!$tokenOk) {
        $postError = 'CSRF 검증 실패. 페이지를 새로고침 후 다시 시도하세요.';
    } else {
        $genType = trim((string)($_POST['gen_type'] ?? ''));
        if (!in_array($genType, ['codex', 'claude', 'integrated'], true)) {
            $postError = '알 수 없는 리뷰 유형입니다.';
        } else {
            if (!is_dir($reportsDir)) {
                mkdir($reportsDir, 0755, true);
            }
            $now      = date('Y-m-d H:i:s');
            $dateStr  = date('Ymd');
            $reviews  = ir_load_reviews($reviewsFile);
            $newId    = ir_next_id($reviews, $dateStr);
            $seq      = substr($newId, -3);
            $filename = "{$dateStr}_{$seq}_{$genType}_review.md";
            $relPath  = "docs/instruction_reviews/{$filename}";
            $absPath  = $reportsDir . '/' . $filename;

            $md = ir_markdown($genType, $newId, $now);
            // strip leading 4-space indent added by heredoc inside function
            $md = preg_replace('/^    /m', '', $md);

            if (file_put_contents($absPath, $md) === false) {
                $postError = "보고서 파일 생성 실패: {$relPath}";
            } else {
                $typeLabel = match($genType) {
                    'codex'      => 'Codex 지침 점검',
                    'claude'     => 'Claude 지침 점검',
                    'integrated' => '통합 지침 점검',
                    default      => $genType,
                };
                $entry = [
                    'id'             => $newId,
                    'type'           => $genType,
                    'title'          => $typeLabel,
                    'status'         => 'draft',
                    'reviewed_files' => ir_default_files($genType),
                    'report_path'    => $relPath,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                    'summary'        => '',
                    'risk_level'     => 'low',
                    'next_action'    => 'review',
                ];
                array_unshift($reviews, $entry);
                if (!ir_save_reviews($reviewsFile, $reviews)) {
                    $postError = 'instruction_reviews.json 저장 실패.';
                } else {
                    $postSuccess = "보고서가 생성되었습니다: {$relPath}";
                    // refresh CSRF to prevent double-submit
                    $_SESSION['dc_csrf_token'] = bin2hex(random_bytes(32));
                    $csrf = $_SESSION['dc_csrf_token'];
                }
            }
        }
    }
}

/* ── GET: filter ── */
$typeFilt = trim((string)($_GET['type'] ?? ''));
$reviews  = ir_load_reviews($reviewsFile);

$filtered = $typeFilt === ''
    ? $reviews
    : array_values(array_filter($reviews, fn($r) => ($r['type'] ?? '') === $typeFilt));

$STATUS_LABELS = [
    'draft'    => '초안',
    'reviewed' => '검토됨',
    'applied'  => '반영됨',
    'archived' => '보관됨',
];
$RISK_LABELS = [
    'low'    => '낮음',
    'medium' => '중간',
    'high'   => '높음',
];
$TYPE_LABELS = [
    'codex'      => 'Codex',
    'claude'     => 'Claude',
    'integrated' => '통합',
];
$ACTION_LABELS = [
    'review'  => '검토 필요',
    'approve' => '승인 대기',
    'done'    => '완료',
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>지침점검 — 개발센터</title>
  <link rel="stylesheet" href="assets/css/dev_center.css">
</head>
<body>

<?php $activeNav = 'instruction_review'; require __DIR__ . '/includes/dev_center_nav.php'; ?>

<main class="dc-main">

  <div class="dc-hero">
    <div class="dc-hero-row">
      <div>
        <h1>지침점검</h1>
        <p>Codex · Claude · 통합 지침을 주기적으로 점검하고 개선안을 작성합니다.<br>
           AI가 지침 파일을 자동으로 수정하지 않습니다. 모든 변경은 사용자 검토 후 수동 적용합니다.</p>
      </div>
      <div class="ws-action-group">
        <form method="post" style="display:contents">
          <input type="hidden" name="csrf_token" value="<?= ir_e($csrf) ?>">
          <input type="hidden" name="gen_type"   value="codex">
          <button type="submit" class="ws-launch-btn">Codex 리뷰 템플릿 생성</button>
        </form>
        <form method="post" style="display:contents">
          <input type="hidden" name="csrf_token" value="<?= ir_e($csrf) ?>">
          <input type="hidden" name="gen_type"   value="claude">
          <button type="submit" class="ws-launch-btn">Claude 리뷰 템플릿 생성</button>
        </form>
        <form method="post" style="display:contents">
          <input type="hidden" name="csrf_token" value="<?= ir_e($csrf) ?>">
          <input type="hidden" name="gen_type"   value="integrated">
          <button type="submit" class="ws-launch-btn">통합 리뷰 템플릿 생성</button>
        </form>
      </div>
    </div>
  </div>

  <div class="ir-guide">
    <div class="ir-guide-item">
      <span class="ir-guide-label">Codex 리뷰</span>
      <span class="ir-guide-desc">분석/검토/프롬프트 작성/범위 통제 규칙을 점검합니다.</span>
    </div>
    <div class="ir-guide-item">
      <span class="ir-guide-label">Claude 리뷰</span>
      <span class="ir-guide-desc">구현/수정/검증/최종 diff 규칙을 점검합니다.</span>
    </div>
    <div class="ir-guide-item">
      <span class="ir-guide-label">통합 리뷰</span>
      <span class="ir-guide-desc">중복 규칙, 충돌 규칙, 보안/DB/인증/유지보수 원칙을 함께 점검합니다.</span>
    </div>
    <div class="ir-guide-warn">
      ⚠️ 이 기능은 지침 파일을 자동 수정하지 않습니다. 보고서 초안을 만들고, 실제 반영은 사용자 승인 후 별도 작업으로 진행합니다.
    </div>
  </div>

<?php if ($postError !== null): ?>
  <div class="dc-alert dc-alert-error"><?= ir_e($postError) ?></div>
<?php endif; ?>
<?php if ($postSuccess !== null): ?>
  <div class="dc-alert dc-alert-ok"><?= ir_e($postSuccess) ?></div>
<?php endif; ?>

  <div class="rc-wrap">
    <div class="kn-toolbar">
      <div class="kn-filters">
        <a href="instruction_review.php"
           class="kn-filter<?= $typeFilt === '' ? ' active' : '' ?>">전체</a>
        <a href="instruction_review.php?type=codex"
           class="kn-filter<?= $typeFilt === 'codex' ? ' active' : '' ?>">Codex</a>
        <a href="instruction_review.php?type=claude"
           class="kn-filter<?= $typeFilt === 'claude' ? ' active' : '' ?>">Claude</a>
        <a href="instruction_review.php?type=integrated"
           class="kn-filter<?= $typeFilt === 'integrated' ? ' active' : '' ?>">통합</a>
      </div>
    </div>

    <div class="rc-count"><?= count($filtered) ?>건 / 전체 <?= count($reviews) ?>건</div>

    <div class="rc-list">
<?php if (empty($filtered)): ?>
      <div class="rc-empty">
        <?= $typeFilt !== '' ? '해당 유형의 점검 기록이 없습니다.' : '아직 생성된 점검 보고서가 없습니다.' ?>
      </div>
<?php else: ?>
<?php foreach ($filtered as $r):
    $rid        = ir_e((string)($r['id']          ?? ''));
    $rtitle     = ir_e((string)($r['title']        ?? ''));
    $rtype      = (string)($r['type']              ?? '');
    $rstatus    = (string)($r['status']            ?? '');
    $rrisk      = (string)($r['risk_level']        ?? '');
    $raction    = (string)($r['next_action']       ?? '');
    $rcreated   = ir_e((string)($r['created_at']   ?? ''));
    $rpath      = ir_e((string)($r['report_path']  ?? ''));
    $rfiles     = (array)($r['reviewed_files']     ?? []);
    $typeLabel  = ir_e($TYPE_LABELS[$rtype]         ?? $rtype);
    $statusLabel= ir_e($STATUS_LABELS[$rstatus]     ?? $rstatus);
    $riskLabel  = ir_e($RISK_LABELS[$rrisk]         ?? $rrisk);
    $actionLabel= ir_e($ACTION_LABELS[$raction]     ?? $raction);
?>
      <div class="rc-item">
        <div class="rc-item-header">
          <div class="rc-item-title"><?= $rtitle ?></div>
          <div class="rc-item-badges">
            <span class="rc-storage-badge rc-storage-<?= ir_e($rtype) ?>"><?= $typeLabel ?></span>
            <span class="rc-usage-badge rc-usage-badge-<?= ir_e($rstatus) ?>"><?= $statusLabel ?></span>
          </div>
        </div>
        <div class="rc-item-desc">
          위험도: <?= $riskLabel ?> &nbsp;|&nbsp; 다음 조치: <?= $actionLabel ?>
        </div>
        <div class="rc-item-meta">
          <span class="rc-pid"><?= $rid ?></span>
          <span class="rc-cat"><?= ir_e(date('Y-m-d', strtotime($r['created_at'] ?? 'now'))) ?></span>
          <?php foreach ($rfiles as $rf): ?>
            <span class="rc-tag"><?= ir_e((string)$rf) ?></span>
          <?php endforeach; ?>
        </div>
        <div class="rc-item-footer">
          <?php if ($rpath !== ''): ?>
            <div class="rc-path"><?= $rpath ?></div>
            <div class="rc-item-actions">
              <button type="button"
                class="rc-btn primary rc-copy-btn"
                data-path="<?= $rpath ?>">경로 복사</button>
            </div>
          <?php endif; ?>
        </div>
      </div>
<?php endforeach; ?>
<?php endif; ?>
    </div>
  </div>

  <footer class="dc-footer">
    <?= ir_e(DEV_CENTER_NAME) ?> &mdash; 로컬 개발 전용. 외부 공개 금지.
  </footer>

</main>

<div id="ir-toast"></div>

<script>
(function () {
  function irCopy(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        irToast('경로가 복사되었습니다.', 'success');
      }, function () {
        irCopyFallback(text);
      });
      return;
    }
    irCopyFallback(text);
  }
  function irCopyFallback(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left     = '-9999px';
    ta.style.opacity  = '0';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    ta.setSelectionRange(0, ta.value.length);
    var ok = false;
    try { ok = document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
    irToast(ok ? '경로가 복사되었습니다.' : '경로 복사에 실패했습니다.', ok ? 'success' : 'error');
  }
  function irToast(msg, type) {
    var el = document.getElementById('ir-toast');
    el.textContent  = msg;
    el.className    = type;
    el.style.display = 'block';
    setTimeout(function () { el.style.display = 'none'; }, 2200);
  }
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.rc-copy-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        irCopy(btn.dataset.path);
      });
    });
  });
})();
</script>
</body>
</html>
