# CLI-first architecture (condensed)

- Skills: docs & orchestration only (no repo changes).
- Agents: executable, payload-driven; explicit invocation required.
- Config: .github/skills-config.yml controls run_context and auto-invoke policy.
- Safety: auto_invoke_agents=false; agents must dry-run and require explicit perform_commit to change code.
