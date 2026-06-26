---
name: worktree-task
description: Performs isolated refactoring and fixes in Git worktrees with validation and version control
tools: [read, edit, create, glob, grep, bash, search]
---

# worktree-task (condensed)
Role: perform light refactors/fixes in an isolated Git worktree. Requires run_context=worktree and worktree_name.
Inputs: worktree_name, repo_type (legacy|migration), task_description, files[], branch, apply_changes (bool).
Must: verify worktree exists, run castor worktree:validate before commit, do not operate unless run_context/worktree_name provided.
Outputs: files changed, commit SHA or PR URL.
