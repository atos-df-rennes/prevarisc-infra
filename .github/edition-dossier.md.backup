Tu agis toujours dans le contexte de migration du code legacy **Zend 1.12** vers **Symfony 4.4**.
Le code utilise PHP en version **7.1.33, aussi bien pour le legacy que celui migré**. Le code doit être migré pour être iso-fonctionnel. Les templates legacy sont en **PHTML et utilisent Bootstrap 2** tandis que les templates migrés sont en **Twig et utilisent Bootstrap 3**.

Sur cette action d'édition, de nombreux champs sont présents en fonction du type et de la nature du dossier et de nombreuses interactions Javascript sont effectuées pour afficher/masquer les champs. Certaines de ces interactions dépendent de la valeur d'un autre champ. Encore une fois, **le point important est de conserver le code fonctionnel exactement comme avant la migration**.

Il m'est difficile de lister ici toutes les interactions et particularités du code étant donné la complexité de cette page. Je vais en revanche fournir une amorce avec les éléments pertinents dans le code **legacy** :
- Liste des champs à afficher en fonction de la **nature** du dossier : `private $listeChamps` dans le fichier _prevarisc/application/controllers/DossierController.php_ => Cette liste de champs correspond aux noms des colonnes de la table `dossier`. Dans le code migré, le nom des champs correspond aux propriétés de l'entité `Dossier` (avec annotations pointant vers les colonnes de la table `dossier`).
- Ordre d'affichage des champs (à afficher/masquer en fonction de la nature du dossier) de la section "Informations sur le dossier" :
  - Date de création du dossier (DATEINSERT_DOSSIER)
  - Objet (OBJET_DOSSIER)
  - Demandeur (DEMANDEUR_DOSSIER)
  - Lieu réunion (LIEUREUNION_DOSSIER)
  - Numéro document d'urbanisme (NUM_DOCURBA)
  - Date réception mairie (DATEMAIRIE_DOSSIER)
  - Date réception secrétariat commission (DATESECRETARIAT_DOSSIER)
  - Date d'envoi/transit (DATEENVTRANSIT_DOSSIER)
  - Date de réception SDIS (DATESDIS_DOSSIER)
  - Service instructeur (SERVICEINSTRUC_DOSSIER)
  - Commission concernée (COMMISSION_DOSSIER)
  - Date de visite (DATEVISITE_DOSSIER)
  - Date de réception du RVRAT et attestation solidité et MO (DATERVRAT_DOSSIER)
  - Date de commission en salle (DATECOMM_DOSSIER)
  - Descriptif analyse de risque (DESCANAL_DOSSIER)
  - Règles auxquelles il est demandé de déroger (REGLEDEROG_DOSSIER)
  - Justification de la dérogation (JUSTIFDEROG_DOSSIER)
  - Mesures compensatoires proposées (MESURESCOMPENS_DOSSIER)
  - Mesures complémentaires à respecter (MESURESCOMPLE_DOSSIER)
  - Etat dossier (INCOMPLET_DOSSIER) avec éventuellement documents manquants (liste docsmanquants)
  - Hors délai (HORSDELAI_DOSSIER)
  - Differe l'avis (DIFFEREAVIS_DOSSIER)
  - Ne peut se prononcer (NPSP_DOSSIER)
  - Absence de quorum (ABSQUORUM_DOSSIER)
  - Cellule non exploitée (CNE_DOSSIER)
  - Avis du rapporteur/du groupe de visite (AVIS_DOSSIER)
  - Date avant laquelle les prescriptions doivent être levées (DELAIPRESC_DOSSIER)
  - Avis de la commission (AVISCOMMISSION_DOSSIER)
  - Facteur de criticité (FACTDANGE_DOSSIER)
  - Echéancier de travaux / Schéma de mise en sécurité (ECHEANCIERTRAV_DOSSIER)
  - Analyse de risque (ANOMALIE_DOSSIER)
  - Observations (OBSERVATION_DOSSIER)
  - Date réception préfecture (DATEPREF_DOSSIER)
  - Date de réponse (DATEREP_DOSSIER)
  - Date de transfert à la commissions compétente (DATETRANSFERTCOMM_DOSSIER)
  - Date de réception à la commissions compétente (DATERECEPTIONCOMM_DOSSIER)
  - Date réunion (DATEREUN_DOSSIER)
  - Opération (intervention) SDIS (OPERSDIS_DOSSIER)
  - RCCI (oui/non) lien rapport (RCCI_DOSSIER)
  - REX (REX_DOSSIER)
  - Chargé de sécurité (T) (CHARGESEC_DOSSIER)
  - Durée de déplacement (DUREEDEPL_DOSSIER)
  - Gravité prescription (GRAVPRESC_DOSSIER)
  - Numéro d'intervention (NUMINTERV_DOSSIER)
  - Date et heure d'intervention (DATEINTERV_DOSSIER)
  - Durée intervention (DUREEINTERV_DOSSIER)
  - Date de signature (DATESIGN_DOSSIER)
  - Préventionniste(s) du dossier (liste préventionnistes liés au dossier)

Si certains champs ne sont jamais utilisés/affichés, tu pourras commenter les parties pertinentes du code avec un commentaire explicite.
Dans le legacy, la plupart des champs sont affichés de la manière suivante :
```html
<label>Nom du champ</label>
<div>Valeur du champ</div>
<input style="display: none;">Input du champ</input>
```
Au clic sur le bouton "Modifier le dossier", la div avec la valeur est alors masquée et l'input correspondant est affiché. Nous n'aurons pas ce problème là dans le code migré puisque nous séparons la page d'affichage de la page d'édition. Cela peut néanmoins t'aider à te repérer dans le code legacy pour savoir quoi afficher/masquer et de quelle manière le faire.