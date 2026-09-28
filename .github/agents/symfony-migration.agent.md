---
name: symfony-migration
description: Continues Zend to Symfony 7.4 migration respecting PHP 8.5 standards and codebase conventions
tools: [read, edit, create, glob, grep, bash, search]
---

# symfony-migration (condensed)
Role: port migrated code forward and improve Symfony 7.4 implementation respecting PHP 8.5 features and standards.
Rules: focus on prevarisc-migration/ only (legacy backup not mounted). Use port-direct for pure-PHP; refactor Zend patterns to Symfony 7.4 equivalents. Do not change observable behaviour without ask_user.
Checks before commit: grep Bootstrap2 patterns, castor symfony:analyse && cs && test.
Outputs: modified files, MANIFEST.yaml updates, migration notes.
