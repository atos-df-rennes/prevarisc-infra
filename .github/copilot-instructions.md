# Copilot instructions (condensed)

Context: Migration Zend 1.12 → Symfony 5.4 (PHP 8.0). Active migration in prevarisc-migration/. Legacy backup at prevarisc/ (not mounted).

Repository structure:
- prevarisc-migration/ → `/home/dev/prevarisc-infra/prevarisc-migration/` (active migrated code)
- prevarisc/ → `/home/dev/prevarisc-infra/prevarisc/` (legacy backup, not mounted — reference only in case of bug detection)

Absolute rules:
- Focus on prevarisc-migration/ only. Legacy code (prevarisc/) is not called in production.
- Use PHP 8.0 features (typed properties, null coalescing, etc.) in migrated code.
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
