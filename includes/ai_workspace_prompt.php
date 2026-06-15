<?php
declare(strict_types=1);

/**
 * Returns a formatted AI workspace startup prompt for the given workspace ID.
 * Reads data/ai_workspaces.json, resolves the role template, and appends
 * allowed_files, do_not_touch, and validation_commands sections.
 *
 * @param string $workspaceId  e.g. 'ws-lab', 'ws-settings'
 * @param string $extraNote    Optional line inserted after workspace header (used by settings.php).
 * @return string              Empty string if workspace not found or template unreadable.
 */
function buildAiWorkspacePrompt(string $workspaceId, string $extraNote = ''): string
{
    $wsFile = DC_DATA_DIR . '/ai_workspaces.json';
    if (!is_file($wsFile)) {
        return '';
    }
    $wsAll = json_decode(file_get_contents($wsFile), true);
    if (!is_array($wsAll)) {
        return '';
    }
    foreach ($wsAll as $ws) {
        if (($ws['id'] ?? '') !== $workspaceId) {
            continue;
        }
        $tplPath = __DIR__ . '/../' . ($ws['startup_prompt_template'] ?? '');
        $roleMd  = is_file($tplPath) ? file_get_contents($tplPath) : '';
        $allowed = implode("\n", array_map(fn($f) => "  - $f", $ws['allowed_files'] ?? []));
        $dnt     = implode("\n", array_map(fn($f) => "  - $f", $ws['do_not_touch'] ?? []));
        $valid   = implode("\n", array_map(fn($c) => "  - $c", $ws['validation_commands'] ?? []));

        $body = $roleMd
            . "\n---\n"
            . "## 워크스페이스: " . ($ws['label'] ?? '') . "\n\n";

        if ($extraNote !== '') {
            $body .= $extraNote . "\n\n";
        }

        return $body
            . "### 허용 파일\n" . $allowed . "\n\n"
            . "### 절대 건드리지 않는 파일\n" . $dnt . "\n\n"
            . "### 검증 명령\n" . $valid . "\n";
    }
    return '';
}

/**
 * Outputs a standalone <script> block with the prompt copy button handler.
 * Clipboard API first; textarea+execCommand fallback; 2-second copied feedback.
 * Call this just before </body> when $wsPromptText is not empty.
 */
function renderWsPromptCopyScript(string $jsWsPrompt): void
{
    echo <<<HTML
<script>
/* ── AI 워크스페이스 프롬프트 복사 ── */
(function () {
  const btn = document.getElementById('ws-prompt-copy');
  if (!btn) return;
  const PROMPT = {$jsWsPrompt};
  const label  = btn.innerHTML;
  const fallback = () => {
    const ta = document.createElement('textarea');
    ta.value = PROMPT; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select(); document.execCommand('copy');
    document.body.removeChild(ta); ok();
  };
  const ok = () => {
    btn.innerHTML = '✅ 복사됨';
    btn.classList.add('ws-prompt-btn--copied');
    setTimeout(() => { btn.innerHTML = label; btn.classList.remove('ws-prompt-btn--copied'); }, 2000);
  };
  btn.addEventListener('click', () => {
    if (navigator.clipboard) { navigator.clipboard.writeText(PROMPT).then(ok, fallback); }
    else { fallback(); }
  });
})();
</script>
HTML;
}
