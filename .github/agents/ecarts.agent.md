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
- **Ecart tracking history:** `/home/dev/prevarisc-infra/prevarisc-migration/docs/migration/ecarts-history.yml`

## User feedback pattern
User feedback is located at the text marker: `**Retour utilisateur** :`
Extract and process all user-provided guidance after this marker.

## History tracking

**Each time you process an ecart (fix or check action), you MUST update BOTH files:**

### 1. Ecart specification file (MOD-XX-ECARTS.md)
- Update the ecart section with final status:
  - `✅ RÉSOLU` for fixes (implementation done)
  - `✅ VALIDÉ` for checks (user feedback validated)
  - `🟢 FERMÉ` for no-action ecarts
- Add/update the **Recommandations** section at the end of the file with a dated summary

### 2. Ecart tracking history (ecarts-history.yml)
- Add/update the entry under the module section
- Include: number, title, action (fix|check), status (closed|open|blocked), date_processed, validated_by, notes, files_modified
- Update `metadata.last_updated` and `metadata.total_ecarts_processed`

**Why both?**
- Markdown files provide **inline context** in specification docs (readers see status immediately)
- YAML file provides **structured tracking** and audit trail (for automation, reporting, and history)

**Commit strategy:** Commit both files together in a single commit with clear message referencing the ecart(s) addressed
