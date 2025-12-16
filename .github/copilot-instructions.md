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

## ✅ Workflow développement

### 1. Analyser le legacy
```bash
grep -r "DATEVISITE" prevarisc/application/
find prevarisc/application/controllers/ -name "*Dossier*"
```

### 2. Implémenter (incrémental)
1. Entité → Repository → FormType → Controller → Template → JS
2. Valider : `castor symfony:analyse && castor symfony:cs && castor symfony:test`
3. Commiter : `feat(scope): description`
4. Mettre à jour `docs/tech/REPRENDRE_ICI.md`

### 3. Validation avant commit (obligatoire)
- ✅ PHPStan 0 erreur
- ✅ CS 0 erreur
- ✅ Tests 100% passent
- ✅ Vérification manuelle UI

---

## 📚 Documentation par tâche

| Tâche | Fichiers à consulter |
|-------|---------------------|
| **Édition dossiers** | `.github/edition-dossier.md`<br>`docs/tech/dossier/PLAN_MIGRATION_EDITION_DOSSIER.md` |
| **Affichage dossiers** | `docs/tech/dossier/migration-affichage-dossiers.md` |
| **Mapping champs** | `docs/tech/dossier/MAPPING_CHAMPS_DOSSIER.md` |
| **État avancement** | `docs/tech/REPRENDRE_ICI.md` |

---

## 🎯 Règles absolues

**INTERDIT ❌**
- `findAll()` sans pagination
- Logique métier dans controllers
- Requêtes N+1 (manque jointures)
- Modifier legacy sans autorisation
- Fonctionnalités PHP > 7.1

**REQUIS ✅**
- Pagination (KnpPaginatorBundle)
- Services pour logique métier
- QueryBuilder avec jointures
- Type hints + DocBlocks
- Conformité 100% legacy avant optimisation

**Gestion écarts :** Implémenter conformité → Documenter suggestion → Attendre validation

---

## 🚀 Best Practices (résumé)

**Controller :**
```php
public function edit(int $id, Request $request, DossierService $service): Response {
    $form = $this->createForm(DossierType::class, $dossier);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $service->save($dossier); // Pas dans le controller
        return $this->redirectToRoute('dossier_show', ['id' => $id]);
    }
    return $this->render('dossier/edit.html.twig', ['form' => $form->createView()]);
}
```

**Repository :**
```php
public function findWithRelations(int $limit = 50): array {
    return $this->createQueryBuilder('d')
        ->leftJoin('d.type', 't')->addSelect('t')
        ->setMaxResults($limit)
        ->getQuery()->getResult();
}
```

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

**Version :** 3.0 optimisée | **Dernière màj :** 16 décembre 2025
