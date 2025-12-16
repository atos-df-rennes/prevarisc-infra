# Instructions spécifiques - Édition de dossiers

**Date de création :** 16 décembre 2025  
**Contexte :** Migration de la fonctionnalité d'édition/création de dossiers (Zend 1.12 → Symfony 4.4)  
**Fichier parent :** `.github/copilot-instructions.md`

---

## 🎯 Objectif de cette tâche

Migrer la page d'**édition et création de dossiers** depuis le legacy Zend vers Symfony, en conservant **100% de l'iso-fonctionnalité**.

**Complexité** : Cette page est l'une des plus complexes de l'application en raison :
- De **68 natures différentes** de dossiers
- D'**affichage/masquage dynamique** de nombreux champs selon type et nature
- D'**interactions JavaScript** dépendant de valeurs d'autres champs
- De **validations conditionnelles** selon le contexte

---

## ⚙️ Prérequis techniques spécifiques

### Versions (rappel)
- **PHP** : 7.1.33 (legacy + migré)
- **Zend Framework** : 1.12
- **Symfony** : 4.4 LTS
- **Templates legacy** : PHTML + Bootstrap 2
- **Templates migrés** : Twig + Bootstrap 3

### Principe fondamental

⚠️ **Le point crucial est de conserver le code fonctionnel EXACTEMENT comme avant la migration.**

Cela inclut :
- L'ordre d'affichage des champs
- Les conditions d'affichage/masquage
- Les interactions JavaScript
- Les validations
- Les labels et messages

---

## 📋 Architecture de la fonctionnalité

### Legacy (Zend)

**Approche** : 1 page unique avec affichage dynamique JavaScript

**Fichiers principaux** :
```
prevarisc/application/controllers/DossierController.php
├── addAction()           # Création nouveau dossier (forward vers indexAction)
├── indexAction()         # Édition dossier existant
├── savenewAction()       # Sauvegarde nouveau dossier
└── saveAction()          # Sauvegarde dossier existant

prevarisc/application/views/scripts/dossier/index.phtml
├── Section inline        # Informations générales
├── descriptif.phtml      # Include - Descriptif
├── avis-et-derogations-edit.phtml  # Include - Avis/dérogations
├── formdocmanquant.phtml            # Include - Documents manquants
├── edit-textes-applicables.phtml   # Include - Textes applicables
├── edit-verifications-techniques.phtml  # Include - Vérif. techniques
├── effectifs-degagements-dossier-edit.phtml  # Include - Effectifs
└── prescription-edit.phtml          # Include - Prescriptions
```

**JavaScript embarqué** : Directement dans `index.phtml` (pas de fichier séparé)

### Symfony (cible)

**Approche** : Même logique avec séparation consultation/édition

**Fichiers principaux** :
```
prevarisc-migration/src/Controller/DossierController.php
├── create()  # Création nouveau dossier
├── edit()    # Édition dossier existant
└── save()    # Sauvegarde (nouveau + existant)

prevarisc-migration/src/Form/DossierType.php
└── FormType Symfony avec champs dynamiques

prevarisc-migration/templates/dossier/edit.html.twig
└── Template Twig avec Bootstrap 3

prevarisc-migration/public/js/dossier/form.js
└── JavaScript vanilla/jQuery pour affichage dynamique

prevarisc-migration/src/Service/Dossier/DossierFieldsService.php
└── Configuration des champs par nature
```

---

## 🗂️ Configuration des champs par nature

### Source legacy : `$listeChamps`

**Fichier** : `prevarisc/application/controllers/DossierController.php`

**Structure** :
```php
private $listeChamps = [
    '1' => ['DATEINSERT', 'NUMDOCURBA', 'DATEREP', ...],  // Nature 1 (PC)
    '2' => ['DATEINSERT', 'NUMDOCURBA', ...],             // Nature 2 (AT)
    // ... 68 natures
];
```

**Correspondance** :
- Clé = ID de la nature
- Valeur = Array des noms de colonnes de la table `dossier`

### Mapping legacy → Symfony

| Legacy (nom colonne)        | Symfony (propriété entité) | Notes                          |
|-----------------------------|----------------------------|--------------------------------|
| `DATEINSERT_DOSSIER`        | `dateInsert`               | Date création                  |
| `OBJET_DOSSIER`             | `objet`                    | Objet du dossier               |
| `DEMANDEUR_DOSSIER`         | `demandeur`                | Demandeur                      |
| `LIEUREUNION_DOSSIER`       | `lieuVisite`               | Lieu réunion                   |
| `NUM_DOCURBA`               | `documentsUrbanisme`       | Collection Documents Urbanisme |
| `DATEMAIRIE_DOSSIER`        | `dateReceptionMairie`      | Date réception mairie          |
| `DATESECRETARIAT_DOSSIER`   | `dateReceptionSecretariat` | Date réception secrétariat     |
| `DATEENVTRANSIT_DOSSIER`    | `dateEnvoiTransit`         | Date envoi/transit             |
| `DATESDIS_DOSSIER`          | `dateReception`            | Date réception SDIS            |
| `SERVICEINSTRUC_DOSSIER`    | `serviceInstructeur`       | Service instructeur            |
| `COMMISSION_DOSSIER`        | `commission`               | Commission (relation)          |
| `DATEVISITE_DOSSIER`        | `dateVisite`               | Date visite                    |
| `DATERVRAT_DOSSIER`         | `dateReceptionRvrat`       | Date réception RVRAT           |
| `DATECOMM_DOSSIER`          | `dateCommission`           | Date commission en salle       |
| `DESCANAL_DOSSIER`          | `descriptifAnalyseRisque`  | Descriptif analyse risque      |
| `REGLEDEROG_DOSSIER`        | `reglesDerogation`         | Règles dérogation              |
| `JUSTIFDEROG_DOSSIER`       | `justificationDerogation`  | Justification dérogation       |
| `MESURESCOMPENS_DOSSIER`    | `mesuresCompensatoires`    | Mesures compensatoires         |
| `MESURESCOMPLE_DOSSIER`     | `mesuresComplementaires`   | Mesures complémentaires        |
| `INCOMPLET_DOSSIER`         | `incomplet`                | Dossier incomplet              |
| `HORSDELAI_DOSSIER`         | `horsDelai`                | Hors délai                     |
| `DIFFEREAVIS_DOSSIER`       | `avisDiffere`              | Diffère l'avis                 |
| `NPSP_DOSSIER`              | `nepeutSePrononcer`        | Ne peut se prononcer           |
| `ABSQUORUM_DOSSIER`         | `absenceDeQuorum`          | Absence de quorum              |
| `CNE_DOSSIER`               | `celluleNonExploitee`      | Cellule non exploitée          |
| `AVIS_DOSSIER`              | `avis`                     | Avis rapporteur/groupe         |
| `DELAIPRESC_DOSSIER`        | `dateLimitePrescription`   | Date limite prescriptions      |
| `AVISCOMMISSION_DOSSIER`    | `avisCommission`           | Avis commission (relation)     |
| `FACTDANGE_DOSSIER`         | `facteurDangerosite`       | Facteur de dangerosité         |
| `ECHEANCIERTRAV_DOSSIER`    | `echeancierTravaux`        | Échéancier travaux             |
| `ANOMALIE_DOSSIER`          | `analyseRisque`            | Analyse de risque              |
| `OBSERVATION_DOSSIER`       | `observations`             | Observations                   |
| `DATEPREF_DOSSIER`          | `datePrefecture`           | Date réception préfecture      |
| `DATEREP_DOSSIER`           | `dateReponse`              | Date de réponse                |
| `DATETRANSFERTCOMM_DOSSIER` | `dateTransfertCommission`  | Date transfert commission      |
| `DATERECEPTIONCOMM_DOSSIER` | `dateReceptionCommission`  | Date réception commission      |
| `DATEREUN_DOSSIER`          | `dateReunion`              | Date réunion                   |
| `OPERSDIS_DOSSIER`          | `operationSdis`            | Opération SDIS                 |
| `RCCI_DOSSIER`              | `rcci`                     | RCCI                           |
| `REX_DOSSIER`               | `rex`                      | REX                            |
| `CHARGESEC_DOSSIER`         | `chargeSecurite`           | Chargé de sécurité             |
| `DUREEDEPL_DOSSIER`         | `dureeDeplacement`         | Durée déplacement              |
| `GRAVPRESC_DOSSIER`         | `gravitePrescription`      | Gravité prescription           |
| `NUMINTERV_DOSSIER`         | `numeroIntervention`       | N° intervention                |
| `DATEINTERV_DOSSIER`        | `dateIntervention`         | Date/heure intervention        |
| `DUREEINTERV_DOSSIER`       | `dureeIntervention`        | Durée intervention             |
| `DATESIGN_DOSSIER`          | `dateSignature`            | Date signature                 |

**Note** : La correspondance complète est documentée dans `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md`

---

## 📐 Ordre d'affichage des champs (section "Informations sur le dossier")

L'ordre ci-dessous est celui défini dans le legacy et **DOIT être respecté** :

1. **Date de création du dossier** (`DATEINSERT_DOSSIER` → `dateInsert`)
2. **Objet** (`OBJET_DOSSIER` → `objet`)
3. **Demandeur** (`DEMANDEUR_DOSSIER` → `demandeur`)
4. **Lieu réunion** (`LIEUREUNION_DOSSIER` → `lieuVisite`)
5. **Numéro document d'urbanisme** (`NUM_DOCURBA` → `numeroDocumentUrbanisme`)
6. **Date réception mairie** (`DATEMAIRIE_DOSSIER` → `dateReceptionMairie`)
7. **Date réception secrétariat commission** (`DATESECRETARIAT_DOSSIER` → `dateReceptionSecretariat`)
8. **Date d'envoi/transit** (`DATEENVTRANSIT_DOSSIER` → `dateEnvoiTransit`)
9. **Date de réception SDIS** (`DATESDIS_DOSSIER` → `dateReponse`)
10. **Service instructeur** (`SERVICEINSTRUC_DOSSIER` → `serviceInstructeur`)
11. **Commission concernée** (`COMMISSION_DOSSIER` → `commission`)
12. **Date de visite** (`DATEVISITE_DOSSIER` → `dateVisite`)
13. **Date de réception du RVRAT** (`DATERVRAT_DOSSIER` → `dateReceptionRvrat`)
14. **Date de commission en salle** (`DATECOMM_DOSSIER` → `dateCommission`)
15. **Descriptif analyse de risque** (`DESCANAL_DOSSIER` → `descriptifAnalyseRisque`)
16. **Règles auxquelles il est demandé de déroger** (`REGLEDEROG_DOSSIER` → `reglesDerogation`)
17. **Justification de la dérogation** (`JUSTIFDEROG_DOSSIER` → `justificationDerogation`)
18. **Mesures compensatoires** (`MESURESCOMPENS_DOSSIER` → `mesuresCompensatoires`)
19. **Mesures complémentaires** (`MESURESCOMPLE_DOSSIER` → `mesuresComplementaires`)
20. **État dossier** (`INCOMPLET_DOSSIER` → `incomplet`) + documents manquants
21. **Hors délai** (`HORSDELAI_DOSSIER` → `horsDelai`)
22. **Diffère l'avis** (`DIFFEREAVIS_DOSSIER` → `avisDiffere`)
23. **Ne peut se prononcer** (`NPSP_DOSSIER` → `nepeutSePrononcer`)
24. **Absence de quorum** (`ABSQUORUM_DOSSIER` → `absenceDeQuorum`)
25. **Cellule non exploitée** (`CNE_DOSSIER` → `celluleNonExploitee`)
26. **Avis du rapporteur/du groupe** (`AVIS_DOSSIER` → `avis`)
27. **Date limite prescriptions** (`DELAIPRESC_DOSSIER` → `dateLimitePrescription`)
28. **Avis de la commission** (`AVISCOMMISSION_DOSSIER` → `avisCommission`)
29. **Facteur de dangerosité** (`FACTDANGE_DOSSIER` → `facteurDangerosite`)
30. **Échéancier de travaux** (`ECHEANCIERTRAV_DOSSIER` → `echeancierTravaux`)
31. **Analyse de risque** (`ANOMALIE_DOSSIER` → `analyseRisque`)
32. **Observations** (`OBSERVATION_DOSSIER` → `observations`)
33. **Date réception préfecture** (`DATEPREF_DOSSIER` → `dateReceptionPrefecture`)
34. **Date de réponse** (`DATEREP_DOSSIER` → `dateReponse`)
35. **Date transfert commission** (`DATETRANSFERTCOMM_DOSSIER` → `dateTransfertCommission`)
36. **Date réception commission** (`DATERECEPTIONCOMM_DOSSIER` → `dateReceptionCommission`)
37. **Date réunion** (`DATEREUN_DOSSIER` → `dateVisite`)
38. **Opération SDIS** (`OPERSDIS_DOSSIER` → `operationSdis`)
39. **RCCI** (`RCCI_DOSSIER` → `rcci`)
40. **REX** (`REX_DOSSIER` → `rex`)
41. **Chargé de sécurité** (`CHARGESEC_DOSSIER` → `chargeSecurite`)
42. **Durée de déplacement** (`DUREEDEPL_DOSSIER` → `dureeDeplacement`)
43. **Gravité prescription** (`GRAVPRESC_DOSSIER` → `gravitePrescription`)
44. **Numéro d'intervention** (`NUMINTERV_DOSSIER` → `numeroIntervention`)
45. **Date et heure d'intervention** (`DATEINTERV_DOSSIER` → `dateIntervention`)
46. **Durée intervention** (`DUREEINTERV_DOSSIER` → `dureeIntervention`)
47. **Date de signature** (`DATESIGN_DOSSIER` → `dateSignature`)
48. **Préventionniste(s)** (relation Many-to-Many)

---

## 🎭 Affichage/masquage dynamique des champs

### Principe legacy

**Dans le legacy** :
```html
<label>Nom du champ</label>
<div>Valeur du champ</div>
<input style="display: none;">Input du champ</input>
```

Au clic sur "Modifier le dossier" :
- La `<div>` avec la valeur est masquée
- L'`<input>` correspondant est affiché

### Principe Symfony (simplifié)

**Dans Symfony** : Séparation consultation/édition → Pas de duplication div/input

- Page de **consultation** (`index.html.twig`) : Affichage des valeurs uniquement
- Page d'**édition** (`edit.html.twig`) : Formulaire uniquement

**Affichage dynamique** : Géré par JavaScript selon type/nature sélectionnée

---

## 🧩 Gestion des champs conditionnels

### Cas 1 : Champs dépendant de la nature

**Exemple** : Nature 31 (Réunion SDIS) affiche `dateVisite`, `demandeur`, `dateReponse`

**Implémentation** :
1. **Service `DossierFieldsService`** : Retourne la liste des champs à afficher pour une nature
2. **JavaScript** : Écoute le changement de nature et affiche/masque les champs
3. **Template Twig** : Tous les champs sont présents, initialement masqués

**Code JavaScript** :
```javascript
// public/js/dossier/form.js
const fieldsConfig = JSON.parse(document.getElementById('fields-config').textContent);

document.getElementById('dossier_nature').addEventListener('change', function() {
    const natureId = this.value;
    const fieldsToShow = fieldsConfig[natureId] || [];
    
    // Masquer tous les champs
    document.querySelectorAll('.dossier-field').forEach(field => {
        field.style.display = 'none';
    });
    
    // Afficher les champs de la nature sélectionnée
    fieldsToShow.forEach(fieldName => {
        const field = document.getElementById('field-' + fieldName);
        if (field) {
            field.style.display = 'block';
        }
    });
});
```

### Cas 2 : Champs dépendant de la valeur d'un autre champ

**Exemple** : Si `avisCommission` = "Défavorable" (ID=2), afficher `facteurDangerosite` et `echeancierTravaux`

**Implémentation JavaScript** :
```javascript
document.getElementById('dossier_avisCommission').addEventListener('change', function() {
    const avisId = parseInt(this.value);
    const fieldsAvisDefavorable = document.getElementById('fields-avis-defavorable');
    
    if (avisId === 2) { // Avis défavorable
        fieldsAvisDefavorable.style.display = 'block';
    } else {
        fieldsAvisDefavorable.style.display = 'none';
        // Vider les champs si masqués
        document.getElementById('dossier_facteurDangerosite').value = '';
        document.getElementById('dossier_echeancierTravaux').value = '';
    }
});
```

### Cas 3 : Champs désactivés selon contexte

**Exemple** : Pour les visites (type 2 ou 3), la commission est liée à la date de visite → champ disabled

**Implémentation** :
```javascript
const dossierType = parseInt(document.getElementById('dossier_type').value);

if ([2, 3].includes(dossierType)) {
    const commissionField = document.getElementById('dossier_commission');
    commissionField.disabled = true;
    commissionField.title = 'La commission est liée à la date de visite';
}
```

---

## 📁 Fichiers clés du projet

### Documentation à consulter AVANT de commencer

| Fichier | Contenu | Usage |
|---------|---------|-------|
| `.github/edition-dossier.md` | Ce fichier | Instructions spécifiques édition |
| `docs/tech/dossier/PLAN_MIGRATION_EDITION_DOSSIER.md` | Plan incrémental complet | Roadmap par phases |
| `docs/tech/dossier/GUIDE_IMPLEMENTATION_VISITES.md` | Guide technique visites | Exemple complet nature 21 |
| `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md` | Mapping colonnes ↔ propriétés | Référence complète |
| `docs/tech/REPRENDRE_ICI.md` | Point de reprise | État d'avancement actuel |

### Code à analyser (legacy)

| Fichier | Contenu |
|---------|---------|
| `prevarisc/application/controllers/DossierController.php` | Controller principal + `$listeChamps` |
| `prevarisc/application/views/scripts/dossier/index.phtml` | Template principal + JavaScript embarqué |
| `prevarisc/application/views/scripts/dossier/*.phtml` | Includes (descriptif, avis, etc.) |

### Code à développer (Symfony)

| Fichier | Rôle |
|---------|------|
| `src/Controller/DossierController.php` | Actions create/edit/save |
| `src/Form/DossierType.php` | FormType avec champs dynamiques |
| `src/Service/Dossier/DossierFieldsService.php` | Configuration champs par nature |
| `templates/dossier/edit.html.twig` | Template formulaire |
| `public/js/dossier/form.js` | JavaScript affichage dynamique |

---

## 🚀 Approche incrémentale (rappel du plan)

**Référence complète** : `docs/tech/dossier/PLAN_MIGRATION_EDITION_DOSSIER.md`

### Phases terminées (17 natures)

| Phase | Description | Natures | Statut |
|-------|-------------|---------|--------|
| **Phase 0** | Infrastructure de base | - | ✅ |
| **Phase 1** | Réunions (Type 4) | 31, 32, 43 | ✅ |
| **Phase 2** | Courriers (Type 7) | 49-55, 57-60, 65 | ✅ |
| **Phase 3** | Visites périodiques | 21, 28 | ✅ (Nature 29 en cours) |

### Prochaines phases

| Phase | Description | Natures | Priorité |
|-------|-------------|---------|----------|
| **Phase 3 (suite)** | Visite inopinée | 29 | **En cours** |
| **Phase 4** | Études simples (PC/AT) | 1-13 | Haute |
| **Phase 5** | Interventions | 37-39 | Moyenne |
| **Phase 6** | Autres types | Variables | Basse |

**Principe** : Commencer par les natures les plus simples, commit après chaque incrément validé.

---

## ✅ Checklist de validation (avant chaque commit)

### 1. Code Symfony
```bash
# PHPStan niveau 10 (0 erreur)
castor symfony:analyse

# Code Style (0 erreur)
castor symfony:cs

# Tests (100% passent)
castor symfony:test
```

### 2. Vérification fonctionnelle

- [ ] Les champs s'affichent dans le bon ordre
- [ ] Les champs conditionnels apparaissent/disparaissent correctement
- [ ] Les labels sont identiques au legacy
- [ ] Les validations fonctionnent
- [ ] Le formulaire se soumet sans erreur
- [ ] La sauvegarde persiste correctement les données

### 3. Vérification JavaScript

- [ ] Pas d'erreur dans la console navigateur
- [ ] Les events sont bien attachés
- [ ] Les champs se masquent/affichent de manière fluide
- [ ] Les valeurs sont bien réinitialisées si champs masqués

### 4. Documentation

- [ ] `docs/tech/REPRENDRE_ICI.md` mis à jour
- [ ] Avancement documenté dans `docs/tech/dossier/AVANCEMENT_*.md`
- [ ] Commit message respecte la convention

---

## 💡 Conseils spécifiques

### Gérer la complexité

1. **Ne PAS essayer de tout faire d'un coup** : Travailler nature par nature
2. **Réutiliser le code existant** : Templates, services, JavaScript déjà développés
3. **Commiter fréquemment** : Chaque nature = 1 commit minimum
4. **Documenter les écarts** : Si comportement différent du legacy, le noter

### Debugging JavaScript

```javascript
// Ajouter des logs pour comprendre le comportement
console.log('Nature sélectionnée:', natureId);
console.log('Champs à afficher:', fieldsToShow);

// Vérifier que les éléments DOM existent
const field = document.getElementById('field-' + fieldName);
if (!field) {
    console.warn('Champ introuvable:', fieldName);
}
```

### Gestion des types de champs

| Type Symfony | Widget HTML | Usage |
|--------------|-------------|-------|
| `TextType` | `<input type="text">` | Champs texte court |
| `TextareaType` | `<textarea>` | Champs texte long |
| `DateType` (widget: 'single_text') | `<input type="date">` | Dates |
| `DateTimeType` (widget: 'single_text') | `<input type="datetime-local">` | Dates + heures |
| `CheckboxType` | `<input type="checkbox">` | Cases à cocher |
| `EntityType` | `<select>` | Relations (commission, avis, etc.) |
| `ChoiceType` | `<select>` | Listes déroulantes simples |

### Champs jamais utilisés

Si certains champs ne sont **jamais affichés** dans aucune nature :
```php
// Dans DossierType.php
// Commenter avec un commentaire explicite :

// Champ CHARGESEC_DOSSIER jamais utilisé dans le legacy (vérifié dans $listeChamps)
// ->add('chargeSecurite', TextType::class)
```

---

## 🔗 Liens vers documentation complémentaire

### Documentation technique interne
- **Plan complet** : `docs/tech/dossier/PLAN_MIGRATION_EDITION_DOSSIER.md`
- **Guide visites** : `docs/tech/dossier/GUIDE_IMPLEMENTATION_VISITES.md`
- **Mapping champs** : `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md`
- **Point de reprise** : `docs/tech/REPRENDRE_ICI.md`
- **Suggestions** : `docs/tech/suggestions/SUGGESTIONS_AMELIORATION_EDITION_DOSSIER.md`

### Documentation externe
- [Symfony Forms](https://symfony.com/doc/4.4/forms.html)
- [Twig Documentation](https://twig.symfony.com/doc/2.x/)
- [Doctrine Relations](https://www.doctrine-project.org/projects/doctrine-orm/en/2.14/reference/association-mapping.html)

---

## 📊 État d'avancement actuel

**Dernière mise à jour** : Voir `docs/tech/REPRENDRE_ICI.md`

**Natures opérationnelles** : 17/68
- Réunions : 3/3 ✅
- Courriers : 12/12 ✅
- Visites : 2/3 ⏳ (Nature 29 en cours)

**Prochaine étape** : Implémenter Nature 29 (Visite inopinée)

---

## 🎯 Résumé des points clés

1. ✅ **Conformité 100%** avec le legacy (fonctionnel, ordre, labels)
2. ✅ **Approche incrémentale** nature par nature
3. ✅ **Validation systématique** PHPStan + CS + Tests avant commit
4. ✅ **JavaScript dynamique** pour affichage/masquage des champs
5. ✅ **Documentation à jour** après chaque incrément
6. ✅ **PHP 7.1.33** : DocBlocks obligatoires, pas de propriétés typées
7. ✅ **Mapping legacy → Symfony** : Colonnes MAJUSCULES → propriétés camelCase

---

**Dernière mise à jour :** 16 décembre 2025  
**Version :** 2.0  
**Auteur :** Équipe Prevarisc
