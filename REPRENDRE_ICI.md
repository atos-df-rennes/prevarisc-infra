# 🔖 Où reprendre - Migration JavaScript Dossier

**Session :** 27 janvier 2026 - 16h20  
**Branche :** `migration/dossier-informations-edition`  
**Développeur :** Maxime Merrien + Copilot

---

## 📊 État d'avancement global

**Migration JavaScript formulaire édition dossier**

- **Progression :** 7/10 blocs terminés (70%)
- **Fichiers migrés :** ~2000 lignes legacy → 6 fichiers JS modulaires
- **Tests :** Tous les blocs validés manuellement

### Blocs terminés (7/10)

| Bloc | Titre | Statut | Commits |
|------|-------|--------|---------|
| 1 | Gestion Type/Nature | ✅ 100% | Validé |
| 2 | Calcul nature auto | ✅ 100% | Validé |
| 3 | Calendrier commissions | ✅ 100% | `d7f4467`, `e7850e0` |
| 4 | Documents urbanisme | ✅ 100% | Validé |
| 6 | Avis masquage | ✅ 100% | Validé |
| 7 | Boutons "Aujourd'hui" | ✅ 100% | `d7f4467`, `e7850e0` |
| **8** | **Gestion Plat'AU** | ✅ **100%** | **`2e4c458`, `93fd398`, `104db02`, `0b2a4a0`** |

### Blocs restants (3/10)

| Bloc | Titre | Durée estimée | Priorité |
|------|-------|---------------|----------|
| 5 | Validation formulaire | 2h | Reporté* |
| 9 | Modals/Alertes | 1-2h | Haute |
| 10 | Cleanup/Refactoring | 3-4h | Haute |

**\*Bloc 5 reporté :** POST-Redirect-GET nécessite plan dédié

---

## ✅ Bloc 8 : Gestion Plat'AU (TERMINÉ 27 janvier)

### Fonctionnalités

1. **Détection et affichage**
   - Section Plat'AU si `id_platau` présent
   - Bloc pièces jointes conditionnel

2. **Boutons retry export** (nouveau)
   - Retry PEC : statut `PRISE_EN_COMPTE` + `date_avis = null`
   - Retry Avis : statut `TRAITE`
   - Sans reload page (pas de perte données)
   - Badge contextuel (warning/info)

### Tests (10/10 PASS)

1. ✅ Dossier Plat'AU détecté
2. ✅ Incomplet → Bloc affiché
3. ✅ Avis défini → Bloc affiché
4. ✅ Badge + scroll
5. ✅ Retry PEC avec pièces → Badge warning
6. ✅ Retry Avis sans pièces → Badge info
7. ✅ Badge persiste au 2ème clic

### Commits

- `2e4c458` - fix: sélecteur détection
- `93fd398` - feat: boutons retry
- `104db02` - fix: classes Bootstrap 3
- `0b2a4a0` - refactor: toggle → remove/add

---

## 🎯 Prochaine étape : Bloc 9 (Modals/Alertes)

**Estimation :** 1-2h  
**Objectif :** Alertes JavaScript et modals de confirmation

**Éléments à migrer :**
- Confirmation suppression documents
- Alertes validation
- TinyMCE (si applicable)

---

## 📁 Fichiers JavaScript

| Fichier | Lignes | Rôle |
|---------|--------|------|
| `form.js` | ~770 | Orchestrateur |
| `gestion-incomplet.js` | ~150 | Incomplet |
| `commission.js` | ~170 | Calendrier |
| `calendar-init.js` | ~200 | FullCalendar |
| `platau.js` | **224** | **Plat'AU** |
| `add-collection-widget.js` | ~83 | CollectionType |

**Total :** ~1597 lignes

---

## ⚙️ Commandes

```bash
# Tests
castor symfony:analyse
castor symfony:cs
castor symfony:test

# Git
git log --oneline -10
git show <commit>
```

---

**Prêt pour Bloc 9 !** 🚀
