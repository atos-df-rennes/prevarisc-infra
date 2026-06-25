# Copilot instructions (condensed)

Context: Migration Zend 1.12 → Symfony 4.4 (PHP 7.1.33). Repos: prevarisc/ (legacy, read-only), prevarisc-migration/ (migrated).

Absolute rules:
- Never modify prevarisc/ (legacy) in automated runs.
- PHP must remain 7.1-compatible (no typed properties, no PHP8 features).
- findAll() without pagination is forbidden.

Essential commands (host):
- castor symfony:analyse  # PHPStan lvl10
- castor symfony:cs
- castor symfony:test
- castor symfony:validate
- castor migration:progress

Commit format (required):
feat(scope): description

Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>

See .github/skills-config.yml for run_context and invocation policy.
