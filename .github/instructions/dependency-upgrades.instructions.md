---
applyTo: "prevarisc-migration/composer.json,prevarisc-migration/composer.lock,prevarisc-migration/package.json,prevarisc-migration/package-lock.json"
---

# Dependency upgrades (condensed)

An upgrade is not done when tests merely pass on the new version. Also:
- Remove/replace deprecated API usage introduced by the new version (do not just silence/ignore deprecation notices).
- Update call sites to the new version's current idioms, not backward-compat shims kept "just in case".
- If a deprecation cannot reasonably be removed in this pass, flag it explicitly in the PR/report instead of leaving it unmentioned.

Goal: the codebase should be ready for the *next* version, not merely working on the current one.
