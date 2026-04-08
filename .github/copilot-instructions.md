# Instructions Copilot - Migration Prevarisc

**Contexte :** Migration Zend 1.12 → Symfony 4.4 | **PHP 7.1.33**

---

## 🎯 Mission

Migrer l'application Prevarisc de Zend vers Symfony en respectant **100% l'iso-fonctionnalité** du legacy.

**Répertoires :**
- Legacy (lecture seule) : `prevarisc/`
- Code migré : `prevarisc-migration/`
- API Plat'AU : `prevarisc-passerelle-platau/`

---

## ⚙️ Stack technique

| Techno | Version | Contrainte |
|--------|---------|------------|
| PHP | **7.1.33** | Pas de propriétés typées, pas de void, DocBlocks obligatoires |
| Symfony | 4.4 LTS | Framework cible |
| Zend | 1.12 | Framework legacy |
| Doctrine | 2.14 | ORM |
| Twig | 2.x | Templates (Bootstrap 3) |
| PHPStan | Niveau 10 | Qualité code |

**PHP 7.1 - Exemple valide :**
```php
class MyService {
    /** @var LoggerInterface */
    private $logger;
    
    /** @return string[] */
    public function getItems(): array { return []; }
}
```

---

## 🔄 Mapping Zend → Symfony (synthèse)

**Templates :** `.phtml` → `.html.twig` | Bootstrap 2 → Bootstrap 3  
**Controllers :** `Zend_Controller_Action` → `AbstractController`  
**Forms :** `Zend_Form` → `AbstractType` (FormType)  
**Models :** `DbTable` → `Entity` (Doctrine)  
**Routing :** `/module/controller/action` → Annotations `@Route`

**Détails complets :** Voir `docs/tech/migration/MAPPING_LEGACY_REFERENCE.md`

---

## 🛠️ Environnement

**Docker :** `prevarisc-infra-app-1` (PHP), `prevarisc-infra-mysql-1` (DB)

**Commandes (depuis l'hôte) :**
```bash
castor symfony:analyse  # PHPStan niveau 10
castor symfony:cs       # Code Style
castor symfony:test     # Tests PHPUnit
```

---

## 🚦 Stratégie de migration : Port direct par défaut

### Règle fondamentale

**Reproduire le code legacy tel quel.** Ne pas refactoriser, ne pas réorganiser, ne pas améliorer — sauf si le code Zend est techniquement impossible à porter tel quel.

**Raison :** Tout écart par rapport au legacy, même mineur, introduit un risque de régression que le développeur doit vérifier manuellement. Un port direct donne une garantie structurelle : même logique = même comportement.

### Ce qui est mécanique (adapter sans risque)
- Syntaxe Zend → Symfony (routing, DI, Twig, Doctrine)
- `$this->view->x` → `return $this->render(..., ['x' => ...])`
- `$this->_getParam()` → `$request->get()`
- `$this->_redirect()` → `return $this->redirectToRoute()`
- Requêtes SQL brutes → QueryBuilder **à structure identique**

### Ce qui est interdit sans validation explicite
- Extraire de la logique dans un service si elle est inline dans le legacy
- Modifier l'ordre des opérations (même si "plus propre")
- Changer la gestion des cas null/vide/0
- Ajouter des validations absentes du legacy
- Fusionner ou découper des blocs conditionnels

### Livraison obligatoire : rapport d'écarts

À chaque livraison, fournir systématiquement le bloc suivant (même si vide) :

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
RAPPORT D'ÉCARTS PAR RAPPORT AU LEGACY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Niveau de confiance global : ✅ / ⚠️ / 🔴

✅ PORT DIRECT — aucune vérification nécessaire sur ces points :
- [liste des parties portées mécaniquement]

⚠️ ADAPTATIONS — vérifier ces points précis :
- [Fichier:ligne] Ce qui a changé → Tester : [action UI précise]

🔴 LOGIQUE COMPLEXE — vérification approfondie recommandée :
- [Fichier:ligne] Ce qui est incertain → Scénarios à tester : [liste]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

**Interdiction d'écrire "iso-fonctionnalité 100%"** sans ce rapport détaillé.

### Niveaux de confiance

| Niveau | Signification | Action requise |
|--------|--------------|----------------|
| ✅ **Port direct** | Code legacy reproduit tel quel | Aucune vérification logique |
| ⚠️ **Adaptation** | Glue Symfony nécessaire, logique inchangée | Vérifier les points listés |
| 🔴 **Logique modifiée** | Réorganisation inévitable | Tester les scénarios listés |

---

## ✅ Workflow développement

### 1. Analyser le legacy
```bash
grep -r "DATEVISITE" prevarisc/application/
find prevarisc/application/controllers/ -name "*Dossier*"
```

### 2. Implémenter (port direct)
1. Porter le legacy mécaniquement (controller → template → JS)
2. Identifier et documenter les écarts inévitables
3. Valider : `castor symfony:analyse && castor symfony:cs && castor symfony:test`
4. Commiter : `feat(scope): description`
5. Mettre à jour `docs/tech/REPRENDRE_ICI.md`

### 3. Validation avant commit (obligatoire)
- ✅ PHPStan 0 erreur
- ✅ CS 0 erreur
- ✅ Tests 100% passent
- ✅ Rapport d'écarts fourni

---

## 📚 Documentation par tâche

| Tâche | Fichiers à consulter |
|-------|---------------------|
| **Édition dossiers** | `.github/edition-dossier.md`<br>`docs/tech/dossier/PLAN_MIGRATION_EDITION_DOSSIER.md` |
| **Affichage dossiers** | `docs/tech/dossier/migration-affichage-dossiers.md` |
| **Mapping champs** | `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md` |
| **État avancement** | `docs/tech/REPRENDRE_ICI.md` |

## 🧩 Instructions spécialisées (activer via `/instructions`)

| Domaine | Fichier | Auto-activé sur |
|---------|---------|-----------------|
| **Tests** | `.github/instructions/testing.instructions.md` | `tests/**`, `*Test.php` |
| **Revue de code** | `.github/instructions/code-review.instructions.md` | Manuel ou `/review` |

---

## 🎯 Règles absolues

**INTERDIT ❌**
- `findAll()` sans pagination sur les listes
- Modifier le legacy sans autorisation explicite
- Fonctionnalités PHP > 7.1
- Requêtes N+1 **si déjà résolues dans le legacy** (porter la même optimisation)
- Écrire "iso-fonctionnalité 100%" sans rapport d'écarts détaillé

**REQUIS ✅**
- Type hints + DocBlocks PHP 7.1
- PHPStan niveau 10 + CS sans erreur
- Rapport d'écarts à chaque livraison
- Port direct comme point de départ systématique

**Optionnel (ne pas imposer sauf demande explicite) :**
- Extraction en service (seulement si le legacy est déjà structuré ainsi)
- QueryBuilder custom (seulement si requête complexe existante dans le legacy)
- Tests unitaires (seulement sur logique métier non triviale)

---

## 📝 Commits

**Format :** `<type>(<scope>): <description>`

**Exemples :**
```
feat(dossier): ajout nature 21 - Phase 3
fix(dossier): correction affichage date commission
docs(dossier): mise à jour REPRENDRE_ICI.md
```

---

**Version :** 4.0 port-direct | **Dernière màj :** 8 avril 2026
