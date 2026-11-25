# Vérifier LDAP et LDAPS en local

Cette procédure est réservée à la stack de développement `prevarisc-infra`.
Elle utilise uniquement le compte de test `testldap` / `Prevarisc2024` :
ne jamais capturer des identifiants réels. La capture LDAP en clair révèle
le mot de passe ; ne pas conserver ni partager cette capture.

## Préparation

Depuis la racine de `prevarisc-infra`, démarrer la stack principale, puis lancer
`castor symfony:authentification` et sélectionner `ldap`. Cette tâche modifie la
configuration Apache locale, recrée le conteneur applicatif et peut arrêter
d'autres services SSO : ne pas l'utiliser sur une stack partagée sans accord.
Elle prépare le compte de test en base ; s'il existait déjà, vérifier qu'il est
actif et que sa case SSO est cochée. L'annuaire utilise les données de `ldap/bootstrap.ldif`.

La tâche peut utiliser le réseau pour récupérer les images Docker manquantes.
L'extension Compose active le port LDAP 389 et le port LDAPS 636 dans le réseau
Docker, exposés sur l'hôte respectivement en 3893 et 6363. Le serveur LDAP local
ne demande pas de certificat client ; ce réglage ne désactive pas la vérification
du certificat serveur par Prevarisc.

Dans un autre terminal, lancer la capture ci-dessous. Elle utilise l'image Docker
`nicolaka/netshoot` et nécessite les capacités réseau correspondantes :

```bash
docker run --rm --network container:prevarisc-infra-ldap-1 \
  --cap-add NET_RAW --cap-add NET_ADMIN nicolaka/netshoot \
  tcpdump -i any -s 0 -A 'tcp port 389 or tcp port 636'
```

Arrêter la capture avec Ctrl+C après chaque essai. Ne pas écrire sa sortie dans
un fichier partagé.

## LDAP en clair

Avec le mode `ldap`, ouvrir `http://localhost:7080/session/login` et se connecter
avec le compte de test. La connexion doit réussir ; la capture doit montrer des
échanges sur le port 389 et le mot de passe de test lisible dans le bind LDAP.
Une absence de texte lisible ne suffit pas à démontrer le chiffrement : vérifier
qu'une nouvelle authentification a bien eu lieu pendant la capture.

Dans Administration > Informations, le serveur doit apparaître en
`ldap://ldap:389`. Ce mode reproduit le comportement par défaut, sans LDAPS.
Pour vérifier aussi l'absence de la nouvelle variable, retirer temporairement
la ligne `PREVARISC_LDAP_ENCRYPTION` de `apache/httpd-auth-main.conf`, puis
recréer le conteneur applicatif et refaire l'essai. Le mode LDAP sur 389 doit
rester actif. Le prochain changement de mode Castor régénère ce fichier.

## LDAPS

Relancer `castor symfony:authentification` et sélectionner `ldaps`. La tâche
installe la CA générée par l'annuaire dans le magasin de confiance du conteneur
applicatif puis recharge Apache. Cette CA de test disparaît à la recréation du
conteneur applicatif : relancer le mode `ldaps` pour la réinstaller.

Redémarrer la capture, se déconnecter et se reconnecter avec le compte de test.
La connexion doit réussir ; les échanges sont sur le port 636 et commencent par
une négociation TLS. Le mot de passe et les requêtes LDAP ne doivent pas apparaître
en clair. Un analyseur comme Wireshark peut confirmer la présence de trames TLS ;
la seule absence du mot de passe n'est pas une preuve suffisante.

Dans Administration > Informations, vérifier `ldaps://ldap:636`. Pour contrôler
indépendamment la confiance et le nom du certificat depuis le conteneur PHP :

```bash
docker exec prevarisc-infra-app-1 openssl s_client -connect ldap:636 \
  -servername ldap -verify_hostname ldap -verify_return_error </dev/null
```

La vérification doit réussir (`Verify return code: 0`). Si le certificat est
refusé, vérifier le nom d'hôte et le magasin CA dans le conteneur applicatif.

## Certificat non approuvé

Sur cette stack de test uniquement, retirer la CA de test, mettre à jour le
magasin et recharger Apache :

```bash
docker exec --user root prevarisc-infra-app-1 rm /usr/local/share/ca-certificates/prevarisc-ldap-test.crt
docker exec --user root prevarisc-infra-app-1 update-ca-certificates --fresh
docker exec --user root prevarisc-infra-app-1 apachectl -k graceful
```

Après déconnexion, une tentative avec `testldap` doit échouer sans repli en
LDAP en clair. Vérifier le journal d'authentification et l'absence de bind sur
389 dans la capture. L'échec peut incrémenter le compteur de tentatives du compte.
Relancer le mode `ldaps` pour rétablir la CA, ou sélectionner `form` pour revenir
à l'authentification locale.
