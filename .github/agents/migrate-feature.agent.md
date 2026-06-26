---
name: migrate-feature
description: Orchestrates the Zend to Symfony migration pipeline with validation and version control integration
tools: [read, edit, create, glob, grep, bash, search]
---

# migrate-feature (condensed)
Role: orchestrate migration pipeline and optionally modify repo (commit/push) when perform_commit=true.
Inputs (JSON): legacy_path, actions[], run_context (main|worktree), worktree_name, branch, commit_message, perform_commit.
Must: run dry-run checks (castor symfony:analyse, cs, test) before commit. Ask user (ask_user) on ambiguous behavior.
Outputs: modified files list, lint/test summary, commit SHA or PR URL.
