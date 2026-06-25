# CLI agent usage (condensed)

- Skill → produce plan + agent_payload. Agents are executed explicitly via CLI prompts.
- Example payload: {"agent":"migrate-feature","payload":{...}}
- Dry-run behaviour: agent runs castor checks and reports; perform_commit=true required to commit.
- For worktree runs set run_context=worktree and provide worktree_name.
