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

## CRITICAL: Git repository structure
⚠️ **`prevarisc-migration/` is an INDEPENDENT git repository.**
- Parent directory `/home/dev/prevarisc-infra/` is the `prevarisc-infra` git repo
- Subdirectory `/home/dev/prevarisc-infra/prevarisc-migration/` is its own git repo
- **ALL commits must be made INSIDE `prevarisc-migration/` repo, not the parent**
- When committing changes: use `cd /home/dev/prevarisc-infra/prevarisc-migration && git commit ...`
- Never attempt to commit from `/home/dev/prevarisc-infra/` when changes are in `prevarisc-migration/`
- Each repo has its own `.git/`, remote, and branch history

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

## Commit strategy (ESSENTIAL)

**ALWAYS commit from within the `prevarisc-migration/` repository directory**, not the parent:

1. Before committing, verify your working directory:
   ```bash
   pwd  # Must show: /home/dev/prevarisc-infra/prevarisc-migration
   git status  # Must show prevarisc-migration's git status
   ```

2. Add and commit changes ONLY from within `prevarisc-migration/`:
   ```bash
   cd /home/dev/prevarisc-infra/prevarisc-migration
   git add <modified files>
   git commit -m "feat(MOD-XX): description"
   ```

3. **Never** attempt to commit from `/home/dev/prevarisc-infra/` parent directory when modifying `prevarisc-migration/` code

4. Commit both spec files (MOD-XX-ECARTS.md + ecarts-history.yml) together in a single commit

5. Push to the `prevarisc-migration` remote (not prevarisc-infra)
