# PROJECT.md

## Overview
`asset-lending-manager` is a WordPress plugin for managing shared physical assets and loan workflows.
Origin: born within an astronomy association (AAGG, Italy) to manage telescopes, eyepieces, books
and shared equipment. Designed to be generic and redistributable: any organization that manages
shared physical assets can install and use it (clubs, schools, public bodies, laboratories,
libraries, makerspaces, etc.).

## Distribution
- The plugin is published as a general-purpose open-source tool, not tied to AAGG.
- AAGG is the founding use case, not the intended target audience.

## Core Domain
- Asset types:
  - `Component`: single item (eyepiece, filter, mount, book, etc.).
  - `Kit`: collection of components.
- Constraint: a kit cannot contain other kits.
- Roles:
  - `alm_member`
  - `alm_operator`

## Implemented Capabilities (Current)
- Custom post type and taxonomies for assets.
- Frontend asset list and detail views.
- Search/filters by text, structure, type, state, level.
- Loan request submission from frontend.
- Approve/reject workflow via AJAX handlers.
- Ownership tracking and loan history persistence.
- Operator/admin back-office menu and tools.

## Planned / Not Fully Implemented
- Real email notification sending (notification module is still a stub).
- Full plugin settings UI and runtime use of settings.
- Some workflow/security hardening tracked in `AGENTS/ISSUES_TODO.md`.

## Product Workflow (Target)
1. Member browses assets and requests a loan.
2. Current assignee approves/rejects (operators can monitor requests in read-only mode when not current assignee).
3. Physical handover happens offline.
4. System records ownership transition and full history.

## References
- Active backlog: `AGENTS/ISSUES_TODO.md`
- Architecture map: `AGENTS/ARCHITECTURE.md`
- Version history: `CHANGELOG.md`
