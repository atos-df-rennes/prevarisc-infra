---
name: performance-review
description: Detects N+1 queries, inefficient database access patterns, and missing indexes in migrated code
tools: [read, grep, glob, bash, search]
---

# performance-review (condensed)
Role: detect N+1, find inefficient queries, missing indexes, hydration issues; do not modify code by default.
Checks: Twig loops accessing relations, repositories without JOIN FETCH, use of findAll() without pagination, COUNT via hydration.
Output: prioritized audit with regression vs legacy flag and suggested fixes (JOIN FETCH, pagination, scalar queries).
