# Architecture CLI-first — Skills et Agents

But: conserver le workflow CLI actuel (prompts) et rendre l'utilisation des skills déterministe.

Résumé
- Skills: documentation et orchestration (fournissent plans, checklists, payloads d'exécution). Ne modifient pas le dépôt.
- Agents: exécutables (fichiers `.agent.md`) qui acceptent une payload structurée et effectuent les actions (git, castor) **seulement** lorsqu'ils sont invoqués explicitement via une commande CLI/prompt.
- Configuration centrale: `.github/skills-config.yml` (run_context, cli_first, auto_invoke_agents).

Flux recommandé (CLI-first)
1. Analyse via skill (lecture seule) → produce plan + agent_payload.
2. L'utilisateur relance l'agent explicitement via prompt CLI: "run agent migrate-feature with {payload}".
3. Agent effectue un dry‑run (lint/tests) et retourne un rapport. Si tout OK et `perform_commit=true`, agent commit/push; sinon l'agent propose PR ou instructions.
4. Agent produit le `rapport d'écarts` et met à jour `MANIFEST.yaml` si commit effectué.

Règles de sécurité et garanties
- `auto_invoke_agents: false` : aucun skill n'exécute de changement sans instruction explicite.
- Agents doivent faire un dry‑run et exiger `perform_commit=true` pour committer.
- `run_context` explicite : `main` (par défaut) ou `worktree` (opt‑in).

Avantages
- Déterminisme : pas d'ambiguïté sur quand exécuter.
- Sécurité : les actions modifiantes sont explicites.
- Continuité du workflow CLI actuel.

Inconvénients
- Nécessite discipline d'invocation explicite (user-driven).
- Agents demandent des payloads stricts et doivent gérer validations.

Fichiers de référence
- `.github/skills-config.yml` — configuration centrale
- `.github/agents/*.agent.md` — contrats d'agent exécutables
- `.github/skills/*.md` — documentation et checklists (lecture seule)

Exemples rapides d'usage dans `.github/tech/cli-agent-usage.md`.
