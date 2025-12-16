# Édition de dossiers - Instructions spécifiques

**Contexte :** Migration édition/création dossiers (Zend → Symfony) | **PHP 7.1.33**  
**Parent :** `.github/copilot-instructions.md`

---

## 🎯 Objectif

Migrer l'édition de dossiers en conservant **100% l'iso-fonctionnalité** :
- **68 natures** différentes
- Affichage/masquage dynamique champs selon type/nature
- Interactions JavaScript conditionnelles
- Ordre d'affichage identique au legacy

---

## 📋 Architecture

**Legacy :** 1 page `dossier/index.phtml` + JavaScript inline + includes (descriptif, avis, etc.)

**Symfony :**
```
DossierController (create/edit/save)
├── DossierType.php (FormType)
├── DossierFieldsService.php (config champs par nature)
├── templates/dossier/edit.html.twig
└── public/js/dossier/form.js (affichage dynamique)
```

---

## 🗂️ Configuration champs par nature

**Source legacy :** `$listeChamps` dans `prevarisc/application/controllers/DossierController.php`

**Mapping (exemples - complet dans `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md`) :**

| Legacy | Symfony | Type |
|--------|---------|------|
| `DATEINSERT_DOSSIER` | `dateInsert` | Date |
| `OBJET_DOSSIER` | `objet` | Textarea |
| `COMMISSION_DOSSIER` | `commission` | EntityType |
| `DATEVISITE_DOSSIER` | `dateVisite` | Date |
| `NUM_DOCURBA` | `documentsUrbanisme` | Collection |
| `AVISCOMMISSION_DOSSIER` | `avisCommission` | EntityType |
| `FACTDANGE_DOSSIER` | `facteurDangerosite` | Integer |
| `ECHEANCIERTRAV_DOSSIER` | `echeancierTravaux` | Date |

**Ordre d'affichage :** 48 champs définis dans legacy - **respecter strictement** (voir section 📐 dans docs détaillées)

---

## 🎭 Gestion champs conditionnels

### Cas 1 : Dépend de la nature
```javascript
// fieldsConfig chargé depuis DossierFieldsService
document.getElementById('dossier_nature').addEventListener('change', function() {
    const fieldsToShow = fieldsConfig[this.value] || [];
    // Masquer tous → Afficher selon nature
});
```

### Cas 2 : Dépend d'un autre champ
```javascript
// Ex: Si avisCommission = Défavorable (ID=2) → afficher facteurDangerosite + echeancierTravaux
document.getElementById('dossier_avisCommission').addEventListener('change', function() {
    if (parseInt(this.value) === 2) {
        document.getElementById('fields-avis-defavorable').style.display = 'block';
    }
});
```

### Cas 3 : Champ disabled selon contexte
```javascript
// Ex: Commission disabled pour visites (type 2/3)
if ([2, 3].includes(dossierType)) {
    commissionField.disabled = true;
    commissionField.title = 'La commission est liée à la date de visite';
}
```

---

## 📁 Documentation détaillée

**OBLIGATOIRE avant de commencer :**
- `docs/tech/dossier/PLAN_MIGRATION_EDITION_DOSSIER.md` (roadmap)
- `docs/tech/dossier/GUIDE_IMPLEMENTATION_VISITES.md` (exemple complet nature 21)
- `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md` (mapping complet)
- `docs/tech/REPRENDRE_ICI.md` (état avancement)

**Code legacy :**
- `prevarisc/application/controllers/DossierController.php` (`$listeChamps`)
- `prevarisc/application/views/scripts/dossier/index.phtml` (template + JS)

---

## 🚀 Approche incrémentale

**Terminé (17 natures) :**
- Phase 0 : Infrastructure ✅
- Phase 1 : Réunions (31, 32, 43) ✅
- Phase 2 : Courriers (49-55, 57-60, 65) ✅
- Phase 3 : Visites (21, 28) ✅

**En cours :** Nature 29 (Visite inopinée)

**Principe :** 1 nature = 1 commit minimum après validation PHPStan + CS + Tests

---

## ✅ Checklist validation

```bash
# Avant CHAQUE commit
castor symfony:analyse  # 0 erreur
castor symfony:cs       # 0 erreur
castor symfony:test     # 100% passent
```

**Vérifications :**
- [ ] Champs dans bon ordre
- [ ] Champs conditionnels fonctionnent
- [ ] Labels identiques legacy
- [ ] Pas d'erreur console JS
- [ ] Sauvegarde persiste données
- [ ] `docs/tech/REPRENDRE_ICI.md` à jour

---

## 💡 Points clés

1. **Travailler nature par nature** (ne pas tout faire d'un coup)
2. **Réutiliser code existant** (services, templates, JS)
3. **PHP 7.1.33 :** DocBlocks obligatoires, pas propriétés typées
4. **Mapping :** Colonnes MAJUSCULES → propriétés camelCase
5. **Champs jamais utilisés :** Commenter avec explication
6. **Débugger JS :** `console.log()` + vérifier éléments DOM existent

**FormTypes Symfony :**
- `TextType` : texte court
- `TextareaType` : texte long
- `DateType` (widget: 'single_text') : dates
- `CheckboxType` : cases à cocher
- `EntityType` : relations (commission, avis)

---

**Version :** 3.0 optimisée | **Dernière màj :** 16 décembre 2025
