---
name: symfony-migration
description: Ports Zend legacy code to Symfony 4.4 respecting PHP 7.1 compatibility and migration strategy
tools: [read, edit, create, glob, grep, bash, search]
---

# symfony-migration (condensed)
Role: port legacy Zend -> Symfony respecting PHP7.1 rules and port-direct strategy.
Rules: never modify prevarisc/ (legacy). For pure-PHP use port-direct; for Zend-dependent blocks, adapt to Symfony equivalents. Do not change observable behaviour without ask_user.
Checks before commit: grep Bootstrap2 patterns, castor symfony:analyse && cs && test.
Outputs: modified files, MANIFEST.yaml updates, report d'écarts.
