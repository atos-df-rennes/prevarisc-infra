# Copilot instructions — Prevarisc infrastructure

This repository contains the shared Docker and Castor infrastructure for the Prevarisc projects. The application code lives in separate Git repositories, notably `prevarisc-migration/` and `prevarisc-passerelle-platau/`; the historical Zend reference in `prevarisc/` is read-only.

## Scope and safety

- Make infrastructure changes in this repository. For application bug fixes and features, work in the relevant application's own Git repository.
- Never modify the frozen `prevarisc/` reference.
- Never switch or check out a different branch without explicit user permission.
- Do not add Symfony Messenger, a scheduler, or another feature requiring a long-running worker process; client infrastructure changes are too costly to roll out.
- Run Castor commands from this repository root unless a command's documentation says otherwise. `CASTOR.md` documents orchestration and validation tasks.
- Do not commit unless requested. When committing is requested, use Conventional Commits (`type(scope): description`) and include `Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>`.

The `run-linters` skill documents Castor validation tasks. The application repository contains the `analyse-legacy` skill for comparing current behavior with the frozen reference.

See `.github/skills-config.yml` for skill run-context and invocation policy.
