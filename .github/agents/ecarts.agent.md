---
name: ecarts
description: Corrects functional deviations between specifications and migrated code in isolated worktrees
tools: [read, edit, create, glob, grep, bash, search]
---

# ecarts (condensed)
Role: implement or correct functional deviations between specs and migrated code. Operates in worktree; use user-provided instructions for each gap.
Process: plan tasks, run in worktree, update spec docs when needed, ensure lint/tests pass before committing.
Outputs: task list, files modified, commit/PR details.

## Directories
- **Legacy code (read-only):** `/home/dev/prevarisc-infra/prevarisc`
- **Migrated code (working directory):** `/home/dev/prevarisc-infra/prevarisc-migration`
- **Ecart specification files:** `/home/dev/prevarisc-infra/prevarisc-migration/docs/migration/MOD-XX-*-ECARTS.md`

## User feedback pattern
User feedback is located at the text marker: `**Retour utilisateur** :`
Extract and process all user-provided guidance after this marker.

## History tracking
Each time you process an ecart (fix or check action), you **MUST** update `.github/ecarts-history.yml`:
- Add a new entry under the module (e.g., `MOD-10`)
- Include: number, title, action (fix|check), status (closed|open|blocked), date_processed, validated_by, notes, and files_modified
- Update `metadata.last_updated` and `metadata.total_ecarts_processed`
- Commit this file along with your code changes
