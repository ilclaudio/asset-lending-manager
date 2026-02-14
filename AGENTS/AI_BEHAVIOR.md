# AI_BEHAVIOR.md

## Purpose

Operational rules for AI assistants working on this codebase.

## Execution Rules
- Be concise, precise, and action-oriented.
- Work one objective at a time, end-to-end.
- Ask clarifying questions only when ambiguity blocks implementation.
- After meaningful progress, summarize what changed and what remains.
- Keep quality gates active: security, accessibility, maintainability.

## Excluded Directories

Always ignore these folders for review/refactoring/fixes:
- `vendor/`
- `node_modules/`
- `assets/bootstrap-italia/`

## Issue Management

### When to add an issue

Create or update issues in `ISSUES_TODO.md` when you find:
- Security vulnerabilities
- Bugs
- Refactoring opportunities
- Code style violations against `CODING_STANDARDS.md`
- Performance problems
- Accessibility problems
- Documentation gaps
- New features or improvement ideas

Feature ideas not implementation-ready still go to category `Feature` with status like `Idea` or `Under Evaluation`.

### Issue template

```markdown
### [PRIORITY] Short descriptive title
- **Status:** Open
- **Date:** YYYY-MM-DD
- **Category:** Security | Bug | Refactoring | CodeStyle | Performance | Accessibility | Feature | Documentation
- **Description:** Detailed description of the problem
- **Steps to reproduce:** (if applicable)
  1. ...
  2. ...
- **Expected behavior:** (if applicable)
- **Notes:** Additional information, workarounds, references
```

### Priority levels

- `Critical`: security/data-loss/major service breakage.
- `High`: major user impact.
- `Medium`: relevant but non-blocking.
- `Low`: minor impact or polish.

### Resolution flow

When an issue is resolved:
1. Move it from `ISSUES_TODO.md` to `ISSUES_RESOLVED.md`.
2. Add `Resolution date` and `Fix summary`.
3. Keep issue statistics aligned.
4. Add commit/PR references when available.

## Documentation Update Rules

After code changes, update related docs:
- Feature work: `PROJECT.md`, `ARCHITECTURE.md`, and issue files if tracked.
- Bug fixes: `ISSUES_RESOLVED.md`; update other docs only if behavior/rules changed.
- Refactors: `ARCHITECTURE.md` and coding docs only when conventions/runtime changed.
- Path/structure changes: update `AGENTS_README.md` and affected references.

Before closing a task, verify:
- Code follows `CODING_STANDARDS.md`.
- Relevant AGENTS docs were updated.
- Issue tracking is consistent.

## Batch and Mass Updates

- For a global rule update, apply changes consistently across all affected files and report the edited file list.
- For mass updates touching many files, list impact first and request confirmation before applying.

## Git Workflow Usage

Git branch/commit/PR conventions are defined in `AGENTS/GIT_WORKFLOW.md`.
Read and apply that file only when the user asks for VCS actions.
