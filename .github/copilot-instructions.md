# Copilot instructions (condensed)

Context: Symfony 4.4 (PHP 7.1.33). prevarisc-migration/ is the current production application; prevarisc/ is a frozen Zend 1.12 reference, used only to check whether a bug predates the migration (legacy bug) or was introduced by it (regression).

Stack: PHP 7.1.33, Symfony 4.4, Doctrine ORM 2.14, Twig 2/3, PHPUnit 7.5.

Repository structure:
- prevarisc-migration/ → `/home/dev/prevarisc-infra/prevarisc-migration/` (current application code)
- prevarisc/ → `/home/dev/prevarisc-infra/prevarisc/` (read-only, see Context above)

Absolute rules:
- Never modify prevarisc/ in automated runs.
- PHP must remain 7.1-compatible (no typed properties, no PHP8 features).
- findAll() without pagination is forbidden.
- No symfony/messenger, scheduler, or other feature requiring an extra long-running worker process: the app is delivered on client infra and an additional process/restart is too costly to roll out for now.

Essential commands (host) — see the `run-linters` skill for details/nuances:
- castor symfony:analyse  # PHPStan lvl10
- castor symfony:cs       # Rector + PHP-CS-Fixer + Twig-CS-Fixer
- castor symfony:test     # unit/non-functional tests (no --all)
- castor symfony:validate # runs all of the above with --dry-run

Commit format (required, Conventional Commits — types observed in git log: fix, feat, chore, perf, style, refactor, migration):
type(scope): description

Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>

See .github/skills-config.yml for run_context and invocation policy.
