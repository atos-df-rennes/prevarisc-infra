---
applyTo: "prevarisc-migration/tests/**,**/*Test.php"
---

# Testing (condensed)
Principles: test business logic (unit), complex interactions (integration), critical flows (functional). Avoid testing trivial getters/setters or framework internals.

Commands: castor symfony:test (use groups). Aim: tests that add clear value, 70–80% coverage on critical code.
