# AGENTS_README.md

## Purpose
Canonical index for AI documentation in this project.

## Canonical Sources
- Reading order (all AI agents): this file.
- Issue categories and template: `AGENTS/AI_BEHAVIOR.md`.
- Open issues and priorities: `AGENTS/ISSUES_TODO.md`.
- Coding rules and JS policy: `AGENTS/CODING_STANDARDS.md`.
- Runtime architecture map: `AGENTS/ARCHITECTURE.md`.
- VCS workflow: `AGENTS/GIT_WORKFLOW.md` (only for git tasks).

## Session Reading Order
1. `AGENTS/AI_RULES_CHATGPT.md` or `AGENTS/AI_RULES_CLAUDE.md` (agent-specific entry point)
2. `AGENTS/PROJECT.md`
3. `AGENTS/ARCHITECTURE.md`
4. `AGENTS/CODING_STANDARDS.md`
5. `AGENTS/AI_BEHAVIOR.md`
6. `AGENTS/ISSUES_TODO.md`

## File Map
- `PROJECT.md`: product scope, roles, implemented vs planned capabilities.
- `ARCHITECTURE.md`: modules, bootstrap, public hooks/endpoints, data model.
- `CODING_STANDARDS.md`: coding constraints and quality standards.
- `AI_BEHAVIOR.md`: workflow rules and issue management.
- `ISSUES_TODO.md`: active backlog.
- `ISSUES_RESOLVED.md`: resolved issue archive.
- `AI_RULES_CHATGPT.md` / `AI_RULES_CLAUDE.md`: minimal agent-specific instructions.

## Update Rules
- If behavior/process changes: update `AI_BEHAVIOR.md`.
- If architecture/runtime changes: update `ARCHITECTURE.md`.
- If coding constraints change: update `CODING_STANDARDS.md`.
- If backlog changes: update `ISSUES_TODO.md` and, when closed, move to `ISSUES_RESOLVED.md`.

## Practical Update Matrix
- New feature implemented:
  Update `PROJECT.md` (scope/capability), `ARCHITECTURE.md` (runtime/data model), and related issue status.
- Bug fixed:
  Move/update issue in `ISSUES_RESOLVED.md`; update `ARCHITECTURE.md` only if runtime behavior changed.
- Refactor without behavior changes:
  Update docs only if architecture/conventions actually changed; otherwise only issue tracking.
- Coding rule/tooling change:
  Update `CODING_STANDARDS.md` and cross-references in `AI_BEHAVIOR.md` if process impact exists.
- New/removed AGENTS file:
  Update `AGENTS_README.md` canonical sources, reading order, and file map.
