# Usage CLI pour agents (exemples)

But: conserver interaction par prompt et exécuter un agent explicitement.

1) Exemple — Demander l'analyse

Invite (prompt):
"Assistant, analyse le controller legacy `prevarisc/application/controllers/NomController.php` et fournis un plan de migration (checklist) et un payload pour l'agent `migrate-feature`."

Réponse attendue (résumé):
- Checklist (Phase 0..5)
- agent_payload (JSON):
{
  "agent": "migrate-feature",
  "payload": {
    "legacy_path": "prevarisc/application/controllers/NomController.php",
    "actions": ["indexAction","voirAction"],
    "run_context": "main",
    "perform_commit": false,
    "branch": "migration/nom-20260512"
  }
}

2) Exemple — Lancer l'agent (exécution explicite)

Invite (prompt):
"Assistant, exécute l'agent `migrate-feature` avec le payload suivant: <coller payload JSON>." 

Comportement agent:
- Dry‑run : `castor symfony:analyse` + `castor symfony:cs --dry-run` + `castor symfony:test`.
- Rapporte les résultats (erreurs/warnings).
- Si `perform_commit=true` et checks OK → commit & push (ou création PR selon config).
- Produit `rapport d'écarts` et met à jour `MANIFEST.yaml` si demandé.

3) Exemple — Worktree (opt‑in)

Invite (prompt):
"Assistant, exécute l'agent `worktree-task` avec payload: { \"worktree_name\": \"dev-joe\", \"task_description\": \"refactor X\", \"apply_changes\": false }"

Notes pratiques
- Toujours demander le `agent_payload` avec la première analyse; relancer l'agent explicitement réduit les erreurs d'interprétation.
- Pour les actions à fort impact, utiliser `apply_changes=false` puis revoir le diff localement avant confirmation.
