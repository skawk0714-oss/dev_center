# Instruction Review Report

**ID**: ir-20260716-001
**Type**: Integrated
**Generated**: 2026-07-16 07:01:29

> ⚠️ 이 보고서는 초안입니다. AI가 자동으로 지침 파일을 수정하지 않습니다.
> 모든 변경은 사용자 검토 및 승인 후 수동으로 적용합니다.

---

## Type
Integrated

## Focus Areas
- 중복 규칙 여부
- 충돌 규칙 여부
- 보안 규칙 상태
- DB 마이그레이션 규칙 상태
- 인증 규칙 상태
- 인시던트 로그 관리
- 모델 업그레이드 가이드 최신화
- 유지보수 우선 원칙 일관성

## Reviewed Files
- AGENTS.md
- templates/ai_roles/project-manager.md
- templates/ai_roles/knowledge.md
- templates/ai_roles/lab.md
- templates/ai_roles/prompts.md
- templates/ai_roles/settings.md
- templates/instructions/base.md
- templates/instructions/php-local-tool.md
- data/prompts.json
- docs/INCIDENT_LOG.md

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

## Integrated Checks
- [ ] Codex and Claude role boundaries are clear.
- [ ] Security, auth, DB migration, and validation rules do not conflict.
- [ ] Maintainability-first rule is present and does not allow broad refactors.
- [ ] Incident logging rules are clear.
- [ ] Approval-required operations are listed clearly.
- [ ] Prompt/library/resource/executable roles are not mixed.

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