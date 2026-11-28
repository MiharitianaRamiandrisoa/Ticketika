# Suivi des corrections et améliorations Ticketika

Dernière mise à jour : 27 novembre 2026

Ce fichier sert de tableau de suivi. Une tâche ne doit être cochée qu’après vérification dans l’environnement concerné.

Pour le chemin complet vers la mise en production, voir [LAUNCH_TODO.md](LAUNCH_TODO.md).

## Légende

- **P0** : bloquant avant tout paiement réel ou mise en production.
- **P1** : important pour la fiabilité et la sécurité.
- **P2** : amélioration fonctionnelle ou confort.

## P0 — Sécurité et paiement

- [ ] **P0 — Production : réparer le schéma manquant**. La table `olympiam_ticketika.events` est absente. Vérifier d’abord `doctrine_migration_versions`, `events`, `orders`, `ticket_types` et `tickets` avant toute correction. **Bloqué actuellement : accès/identifiants de production non disponibles.**
- [ ] **P0 — Révoquer les secrets Papi exposés** dans `.env`, recréer `PAPI_API_KEY` et `PAPI_WEBHOOK_SECRET`, puis retirer les anciennes valeurs de l’historique Git.
- [ ] **P0 — Configurer les variables Papi dans le `.env` du serveur** : `PAYMENT_MODE`, `PAPI_API_KEY`, `PAPI_BASE_URL`, `PAPI_PUBLIC_URL` et `PAPI_WEBHOOK_SECRET`. Le pipeline les valide désormais sans remplacer le fichier.
- [ ] **P0 — Utiliser `APP_ENV=prod` et `APP_DEBUG=0`** sur le serveur avec un `APP_SECRET` aléatoire et permanent.
- [ ] **P0 — Corriger et valider `DATABASE_URL`**. `doctrine:schema:validate` échoue actuellement avec une erreur d’URL de connexion.
- [ ] **P0 — Vérifier le montant et la devise du webhook** par rapport à la commande avant de la marquer comme payée.
- [ ] **P0 — Rendre le webhook idempotent** avec identifiant d’événement fournisseur, transaction et verrouillage de la commande.
- [ ] **P0 — Empêcher les doubles billets** lors de deux callbacks ou paiements concurrents.
- [ ] **P0 — Ne finaliser le paiement que depuis le webhook signé**. Le retour navigateur doit seulement afficher l’état du paiement.
- [ ] **P0 — Empêcher le mode fake en production** si `PAYMENT_MODE` est absent ou incorrect.

## P1 — Migration et fiabilité des commandes

- [x] **P1 — Préparer une migration locale** : `migrations/Version20261126120000.php`.
  Elle ajoute une expiration de réservation, une référence de paiement unique et une table de suivi des événements webhook.
- [ ] **P1 — Revoir la migration sur une copie de la base serveur** avant application.
- [ ] **P1 — Sauvegarder la base serveur** avant toute migration.
- [ ] **P1 — Appliquer la migration en préproduction**, puis vérifier les commandes, paiements et scans.
- [ ] **P1 — Appliquer la migration en production via le pipeline** après validation.
- [x] **P1 — Ajouter le champ d’expiration dans `Order`** et le renseigner lors de la réservation.
- [x] **P1 — Implémenter la libération automatique** via la commande `app:orders:expire` ; il reste à planifier son exécution sur le serveur.
- [x] **P1 — Utiliser la table `payment_webhook_events`** dans le contrôleur webhook.
- [ ] **P1 — Ajouter une vraie machine d’état** : pending, paid, failed, expired, cancelled, refunded.
- [x] **P1 — Ajouter une limite serveur** sur la quantité de billets par commande.
- [x] **P1 — Corriger les courses concurrentes sur le stock** avec transaction et verrouillage.
- [x] **P1 — Vérifier la capacité globale de l’événement**, pas seulement le stock de chaque type de billet.
- [ ] **P1 — Sauvegarder les lignes de commande** avec nom et prix du billet au moment de l’achat.

## P1 — Sécurité applicative

- [ ] Remplacer progressivement l’authentification maison par Symfony Security, ou ajouter une politique équivalente.
- [ ] Ajouter une limitation des tentatives de connexion et des créations de commandes.
- [ ] Ajouter des contraintes serveur sur les noms, e-mails, mots de passe, prix, capacités et quantités.
- [ ] Passer la déconnexion en `POST` avec protection CSRF.
- [ ] Ajouter une protection CSRF ou un mécanisme d’authentification dédié au scan des billets.
- [ ] Rendre le scan atomique afin qu’un même billet ne soit pas accepté deux fois simultanément.
- [ ] Ajouter la propriété de l’événement et limiter les organisateurs à leurs propres données.
- [ ] Ajouter les en-têtes HTTPS, HSTS, CSP, `X-Content-Type-Options` et `Referrer-Policy`.
- [ ] Vérifier les cookies de session : `Secure`, `HttpOnly`, `SameSite` et expiration d’inactivité.

## P2 — Fonctionnalités restantes

- [ ] Générer un vrai QR code pour chaque billet.
- [ ] Remplacer le bouton de simulation par un vrai lecteur QR ou une saisie sécurisée.
- [ ] Ajouter l’envoi réel des billets par e-mail.
- [ ] Ajouter vérification d’e-mail, mot de passe oublié et changement de mot de passe.
- [ ] Ajouter modification et suppression d’événements.
- [ ] Ajouter gestion complète des types de billets depuis le back-office.
- [ ] Ajouter annulation, remboursement et rapprochement des paiements.
- [ ] Remplacer les places simulées en JavaScript par une disponibilité issue du serveur.
- [ ] Connecter le formulaire newsletter à un service réel.

## Vérifications réalisées

- [x] Syntaxe Twig validée.
- [x] Syntaxe YAML validée.
- [x] Routes Symfony inspectées.
- [x] Migration `Version20261126120000.php` validée avec `php -l`.
- [ ] `doctrine:schema:validate` à refaire après correction de `DATABASE_URL`.
- [ ] Audit Composer à réaliser dans un environnement avec accès réseau.
- [ ] Tests automatisés à créer : paiement, webhook, stock, accès utilisateur et scan.

## Procédure d’application serveur

1. Faire une sauvegarde de la base.
2. Tester la migration sur une copie ou la préproduction.
3. Vérifier le SQL généré et l’état des migrations.
4. Déployer le code et les variables d’environnement nécessaires.
5. Exécuter `php bin/console doctrine:migrations:migrate --env=prod --no-interaction`.
6. Vérifier les paiements Papi, la réception du webhook et la création d’un billet de test.
7. Surveiller les logs et conserver le résultat de la migration.

La migration n’a pas été appliquée à une base distante pendant cette analyse.

## Blocage actuel

Les identifiants de la base et/ou l’accès SSH de production ne sont pas encore disponibles. Ne pas les mettre dans ce dépôt ni dans la conversation. Les ajouter uniquement dans les secrets du serveur ou du pipeline CI/CD, puis reprendre la procédure de diagnostic et de migration ci-dessus.
