# Journal du projet Ticketika

Dernière mise à jour : 24 novembre 2026

Ce fichier est la référence de continuité du projet. Toute nouvelle fonctionnalité, règle métier, migration ou décision importante doit y être ajoutée avec sa date, les fichiers concernés et les vérifications effectuées.

## Suivi du 24 novembre 2026

- Implémentés : comptes utilisateurs, inscription/connexion, espace utilisateur, rattachement des commandes aux comptes, restriction des réservations et confirmations, contrôle organisateur, gestion des catégories, retrait de Sport, retrait du bandeau défilant, ajout de Accueil, indication de défilement des cartes et retrait des remboursements.
- Ajoutés : `User`, `Category`, `AuthService`, contrôleurs et formulaires associés, migration `Version20261124110000`.
- Vérifié : syntaxe PHP, syntaxe Twig, routes Symfony et compilation Webpack.
- Ajoutée ensuite : migration `Version20261124113000` pour aligner le nom de l'index `orders.user_id` avec Doctrine.
- Base locale réparée : l'ancien dossier corrompu a été conservé sous `C:\xampp\mysql\data\ticketika_corrupt_backup_20261124`, puis la base `ticketika` a été recréée, migrée et remplie avec les fixtures.
- État vérifié : schéma Doctrine synchronisé, 5 événements, 4 catégories, 2 utilisateurs de démonstration et 0 commande.
- Point technique restant : `cache:clear` est bloqué tant que les processus PHP actifs verrouillent `var/cache`; arrêter/restart le serveur PHP local avant de vider le cache.
- Navigation publique ajustée : toutes les catégories actives sont chargées dynamiquement dans la barre de navigation, puis Mon espace apparaît dans le même parcours public. Le lien Se connecter séparé a été retiré ; un visiteur arrive à la connexion depuis Mon espace.
- Séparation des espaces confirmée : aucun lien vers l'organisateur n'est rendu dans la navigation publique, l'accueil ou le pied de page. L'interface organisateur reste disponible uniquement par URL directe `/organisateur` et ses sous-routes, avec contrôle du rôle organisateur.
- Alignement de navigation corrigé : Accueil et toutes les catégories sont centrés, tandis que Mon espace est placé à droite.
- La hauteur, les espacements et la taille des liens ont été légèrement augmentés.
- Le conteneur horizontal de la barre a été élargi à `max-w-screen-2xl` pour mieux répartir les éléments.
- Connexion organisateur dédiée : l'accès direct à `/organisateur` redirige vers `/organisateur/connexion` si nécessaire. La page de connexion affiche le contexte back-office et refuse les comptes utilisateurs standards sans afficher la page 403 Symfony par défaut.

## Fonctionnalités et règles métier

### Séparation des espaces

- L'espace organisateur (`/organisateur`) est un back-office privé : il sert à gérer les événements, les catégories, les ventes et le scan des billets. Il est réservé aux comptes organisateurs.
- L'espace utilisateur (`/mon-espace`) est personnel à chaque compte connecté : il sert à consulter uniquement les billets et commandes de cet utilisateur.
- Ces deux espaces ne doivent jamais partager la même interface ni les mêmes droits d'accès.

### Comptes utilisateurs

- Un visiteur peut consulter l'accueil et les événements sans compte.
- La création d'un compte se fait via `/inscription`.
- La connexion se fait via `/connexion`.
- Le compte courant est conservé dans la session sous `ticketika_user_id`.
- `/mon-espace` est la page utilisateur principale.
- `/mes-billets` reste une URL compatible et redirige vers le même espace.
- Les billets affichés dans l'espace utilisateur proviennent uniquement des commandes rattachées à l'utilisateur connecté.
- La réservation et la page de confirmation exigent une connexion.
- Un utilisateur ne peut pas consulter la confirmation d'une commande appartenant à un autre utilisateur.
- Les mots de passe sont stockés avec `password_hash()` et vérifiés avec `password_verify()`.

### Organisateurs

- Aucun lien public vers l'espace organisateur n'est affiché dans la navigation, le pied de page ou l'accueil.
- Les routes `/organisateur/*` vérifient que l'utilisateur connecté possède le rôle organisateur.
- Un utilisateur standard est redirigé vers son espace ; un visiteur est redirigé vers la connexion.
- Les fixtures créent un compte de démonstration organisateur : `organisateur@ticketika.test` / `organisateur`.
- Les fixtures créent aussi un compte de démonstration utilisateur : `utilisateur@ticketika.test` / `utilisateur`.
- Ces mots de passe sont uniquement destinés au développement et doivent être remplacés en environnement réel.

### Catégories

- Les catégories sont stockées dans la table `categories` et gérées par `Category`.
- Seules les catégories actives sont proposées lors de la création d'un événement et dans les filtres publics.
- La gestion se fait dans `/organisateur/categories`.
- Une catégorie peut être activée ou désactivée ; sa désactivation ne supprime pas les événements existants.
- La catégorie Sport n'est pas créée dans les fixtures et n'est pas affichée dans l'interface.
- Les catégories initiales sont Concert, Spectacle, Famille et Atelier.
- Les événements référencent une catégorie par clé étrangère `events.category_id`.

### Accueil et interface

- La navigation contient Accueil, Événements et Mon espace.
- Le bandeau horizontal automatique de l'accueil a été supprimé.
- Le bloc de statistiques « Billet instantané / Paiement Mobile money » du hero a été supprimé.
- La section « À ne pas manquer » conserve le défilement horizontal manuel et affiche un indice visuel `↔` ainsi qu'un fondu sur le bord droit.
- La section Remboursement a été supprimée de la fiche événement, de l'accueil et du pied de page.
- Le bouton public « Espace organisateur » a été supprimé.

## Architecture importante

- `AuthService` centralise la lecture de l'utilisateur en session, la connexion, la déconnexion et le retour vers l'URL demandée.
- `UserController` gère inscription, connexion et déconnexion.
- `TicketController` gère Mon espace et l'affichage des billets.
- `CategoryController` gère la configuration des catégories côté organisateur.
- `Order.user` rattache chaque nouvelle commande au compte connecté.
- `Event.category` est une relation Doctrine vers `Category`, pas une chaîne libre.
- `EventRepository::findUpcoming()` filtre les catégories via leur slug et ignore les catégories désactivées.

## Base de données et migrations

- La migration initiale est `migrations/Version20261124064721.php`.
- La migration des comptes et catégories est `migrations/Version20261124110000.php`.
- Après toute modification d'entité, vérifier avec `php bin/console doctrine:schema:validate`.
- Appliquer les migrations avec `php bin/console doctrine:migrations:migrate --no-interaction`.
- Charger les données de démonstration avec `php bin/console doctrine:fixtures:load --no-interaction`.
- Ne jamais utiliser `doctrine:schema:update --force` sur une base contenant des données sans examiner le SQL généré.
- La base locale actuelle `ticketika` a rencontré une incohérence InnoDB de tablespaces après une migration interrompue ; ne pas supprimer la base sans vérifier les données ou obtenir confirmation.

## Vérifications usuelles

```powershell
php -l src/Controller/UserController.php
php bin/console lint:twig templates
php bin/console debug:router --show-controllers
php bin/console doctrine:schema:validate
npm run build
```

## Convention de mise à jour de ce journal

À chaque évolution :

1. ajouter la date et un résumé dans une section de suivi ;
2. noter les nouvelles règles d'accès ou règles métier ;
3. noter les fichiers et migrations ajoutés/modifiés ;
4. noter les commandes de migration, fixtures, build et tests exécutées ;
5. noter les blocages connus et les actions manuelles requises.
