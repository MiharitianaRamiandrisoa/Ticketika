# TODO de lancement — Ticketika

Objectif : lancer une première version de production utilisable et sûre le plus rapidement possible.

Dernière mise à jour : 27 novembre 2026

## Périmètre du MVP de lancement

À conserver pour le lancement :

- catalogue et fiches événements ;
- comptes utilisateurs ;
- réservation de billets ;
- paiement Papi Hosted Checkout ;
- webhook Papi ;
- génération et validation des billets ;
- espace organisateur ;
- un ou plusieurs événements réellement configurés.

À reporter après le lancement si nécessaire :

- newsletter ;
- édition avancée des événements ;
- statistiques avancées ;
- application mobile dédiée.

## État actuel et blocages

- [ ] Les accès SSH et les identifiants de la base de production ne sont pas encore disponibles.
- [ ] La base de production signale que la table `events` n’existe pas.
- [ ] La migration `Version20261126120000` est préparée localement, mais n’est pas appliquée sur le serveur.
- [x] Le code nécessaire à l’expiration des réservations et à l’idempotence des webhooks est maintenant préparé ; la migration serveur reste à appliquer.
- [ ] Des secrets Papi sont présents dans le fichier `.env` suivi par Git : ils doivent être révoqués.
- [ ] `doctrine:schema:validate` échoue actuellement en local à cause de la configuration de connexion à la base.
- [ ] Aucun test automatisé n’a encore été trouvé.

## Phase 0 — Accès et décision de lancement

### 0.1 Accès serveur

- [ ] Créer les secrets GitHub Actions de production : `SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY`, `SSH_PORT`, `SSH_SERVER_DIR`.
- [ ] Créer sur chaque serveur un fichier `.env` local contenant `DATABASE_URL`, `APP_SECRET`, `PAYMENT_MODE`, `PAYMENT_HOLD_MINUTES` et les variables Papi.
- [ ] Vérifier que ce `.env` contient `APP_ENV=prod` et `APP_DEBUG=0`.
- [ ] Les variables applicatives sont volontairement lues depuis le `.env` du serveur pour le lancement actuel.
- [ ] Ne jamais afficher les valeurs de ces variables dans les logs ou dans la conversation.
- [ ] Vérifier que le compte SQL n’est pas `root` et possède uniquement les droits nécessaires sur la base Ticketika.

### 0.2 Domaine et HTTPS

- [ ] Vérifier le domaine de production et le certificat HTTPS.
- [ ] Vérifier que `PAPI_PUBLIC_URL` pointe vers le domaine final en HTTPS.
- [ ] Vérifier que l’URL du webhook Papi est accessible publiquement en `POST`.
- [ ] Configurer le domaine de préproduction séparément du domaine de production.

## Phase 1 — Réparer et initialiser la base

### 1.1 Diagnostic sans modification

Depuis le dossier de l’application sur le serveur :

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:query:sql "SHOW TABLES" --env=prod
php bin/console doctrine:query:sql "SELECT DATABASE(), VERSION()" --env=prod
```

- [ ] Vérifier la présence de `doctrine_migration_versions`.
- [ ] Vérifier la présence de `events`, `orders`, `ticket_types` et `tickets`.
- [ ] Vérifier que `DATABASE_URL` pointe vers la bonne base.
- [ ] Vérifier que l’historique Doctrine correspond réellement aux tables présentes.

### 1.2 Réparation contrôlée

- [ ] Faire une sauvegarde complète de la base avant toute modification.
- [ ] Si la migration initiale n’est pas exécutée, lancer les migrations normales :

```bash
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
```

- [ ] Si la migration initiale est marquée comme exécutée mais que `events` manque, arrêter la procédure et restaurer/réparer après analyse de la sauvegarde.
- [ ] Ne pas utiliser `doctrine:schema:update --force` en production.
- [ ] Ne pas utiliser `doctrine:fixtures:load` en production.
- [ ] Ne pas marquer manuellement une migration comme exécutée pour contourner l’erreur.
- [ ] Appliquer `Version20261126120000` uniquement après validation de sa compatibilité avec le code déployé.
- [x] Préparer la commande `app:organizer:create` pour créer le premier compte organisateur sans charger les fixtures de démonstration.

## Phase 2 — Sécuriser le paiement

- [ ] Révoquer les anciennes valeurs `PAPI_API_KEY` et `PAPI_WEBHOOK_SECRET`.
- [ ] Générer de nouvelles valeurs dans le gestionnaire de secrets.
- [x] Ajouter les variables Papi et `PAYMENT_HOLD_MINUTES` dans `.env` local. La configuration serveur reste à vérifier.
- [x] Vérifier que toute valeur de `PAYMENT_MODE` inconnue bloque le paiement au lieu d’activer le mode fake.
- [ ] Vérifier dans le webhook : signature, timestamp, référence, montant, devise et token Papi.
- [x] Faire du webhook signé la seule source de finalisation d’une commande.
- [x] Dédupliquer les événements webhook avec `payment_webhook_events` et une clé unique.
- [x] Verrouiller la commande et les types de billets pendant la finalisation.
- [x] Ajouter une clé d’événement webhook unique et empêcher son traitement deux fois.
- [x] Ajouter une transaction et un verrouillage sur la commande lors de la finalisation.
- [x] Vérifier qu’une commande ne génère jamais deux fois ses billets.
- [ ] Enregistrer les erreurs détaillées dans les logs, mais afficher un message générique au client.

## Phase 3 — Garantir le stock et les réservations

- [x] Ajouter un champ `reservationExpiresAt` dans `Order`.
- [x] Renseigner une expiration lors de la création d’une commande pending.
- [x] Ajouter la commande `app:orders:expire` pour libérer les réservations expirées.
- [x] Vérifier la capacité globale de l’événement et la capacité de chaque type de billet.
- [x] Ajouter une limite maximale de billets par commande côté serveur.
- [x] Empêcher les quantités négatives, non numériques ou excessives.
- [x] Verrouiller les types de billets pendant la réservation, ou utiliser une mise à jour conditionnelle atomique.
- [ ] Planifier `php bin/console app:orders:expire --env=prod` avec le cron du serveur.
- [ ] Ajouter des tests de deux achats simultanés sur la dernière place.

## Phase 4 — Rendre les billets utilisables

- [ ] Remplacer le QR code visuel de démonstration par un vrai QR code signé.
- [ ] Définir le contenu du QR code : identifiant opaque ou code de billet, jamais de données sensibles.
- [x] Rendre le scan atomique pour empêcher deux entrées simultanées.
- [x] Ajouter une protection CSRF ou un token dédié au endpoint de scan.
- [ ] Vérifier qu’un billet non payé ou annulé ne peut jamais être validé.
- [ ] Décider entre envoi par e-mail et affichage uniquement dans l’espace utilisateur.
- [ ] Si l’e-mail n’est pas prêt, retirer la phrase indiquant que le billet a été envoyé par e-mail.

## Phase 5 — Sécurité minimale avant ouverture publique

- [ ] Passer le serveur en `APP_ENV=prod` et `APP_DEBUG=0`.
- [ ] Ajouter une limitation des tentatives de connexion.
- [ ] Ajouter des contraintes serveur sur les formulaires et les mots de passe.
- [x] Passer la déconnexion en `POST` avec CSRF.
- [x] Configurer explicitement les cookies `Secure`, `HttpOnly` et `SameSite`.
- [ ] Ajouter HSTS, CSP, `X-Content-Type-Options` et `Referrer-Policy`.
- [ ] Vérifier qu’un utilisateur ne peut pas lire la commande d’un autre utilisateur.
- [ ] Vérifier qu’un organisateur ne voit que les événements qui lui appartiennent, si plusieurs organisateurs sont prévus.

## Phase 6 — Corriger le déploiement

- [x] Adapter le pipeline pour conserver et valider le `.env` déjà présent sur le serveur, au lieu de le remplacer par des variables de base de données GitHub.
- [ ] Vérifier que `composer install --no-dev` termine correctement.
- [ ] Construire et déployer les assets avec `npm ci` puis `npm run build`, ou construire explicitement les assets sur le serveur.
- [ ] Vérifier que `public/build` contient les fichiers utilisés par Twig.
- [ ] Exécuter les migrations avant le cache Symfony.
- [ ] Exécuter `php bin/console cache:clear --env=prod` après le déploiement.
- [ ] Ajouter une URL de health check qui vérifie l’application et la base sans exposer de secret.
- [ ] Préparer une procédure de rollback du code et de restauration de base.

## Phase 7 — Tests de préproduction

- [ ] Ouvrir l’accueil et la liste des événements.
- [ ] Créer un compte utilisateur.
- [ ] Se connecter et se déconnecter.
- [ ] Créer une commande avec un billet payant.
- [ ] Créer une commande gratuite si ce cas est conservé.
- [ ] Effectuer un paiement Papi en sandbox.
- [ ] Vérifier la réception du webhook.
- [ ] Vérifier qu’un webhook répété ne crée pas de billet supplémentaire.
- [ ] Vérifier qu’un montant ou une référence incorrecte est refusé.
- [ ] Vérifier l’échec du paiement et la libération des places.
- [ ] Vérifier l’expiration d’une commande abandonnée.
- [ ] Afficher le billet et scanner son QR code.
- [ ] Vérifier qu’un billet déjà scanné est refusé.
- [ ] Vérifier les accès non autorisés à une autre commande.
- [ ] Vérifier le tableau de bord organisateur.
- [ ] Vérifier les logs sans secret ni donnée bancaire sensible.

## Phase 8 — Mise en production

- [ ] Valider la préproduction par écrit.
- [ ] Faire une sauvegarde de production juste avant le déploiement.
- [ ] Déployer depuis une branche ou un tag identifié.
- [ ] Appliquer les migrations validées.
- [ ] Vider/reconstruire le cache Symfony.
- [ ] Faire un achat réel de faible montant ou un test contrôlé convenu avec Papi.
- [ ] Vérifier le webhook et la création du billet.
- [ ] Surveiller les logs pendant les premières heures.
- [ ] Préparer un contact support et une procédure de remboursement manuel.

## Définition de terminé

Le lancement est considéré comme prêt uniquement lorsque :

- la base de production contient toutes les tables attendues ;
- les migrations sont enregistrées et vérifiées ;
- les secrets ne sont plus dans Git ;
- un paiement Papi réel ou sandbox complet a été validé ;
- un webhook répété ne crée pas de doublon ;
- le stock ne peut pas devenir négatif ou dépasser la capacité ;
- un billet réel peut être présenté et refusé après son premier scan ;
- les logs et la sauvegarde sont disponibles ;
- aucune erreur critique n’apparaît avec `APP_DEBUG=0`.
