# Ticketika — Billetterie Olympia (Symfony 7)

Application Symfony 7 : entités Doctrine, contrôleurs, formulaires, Twig, Tailwind CSS et JavaScript compilés par Webpack Encore.

## Installation

Prérequis : PHP 8.2+, Composer, Node.js 18+.

```bash
composer install
npm install

php bin/console doctrine:database:create
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load

npm run build                  # compile Tailwind et JS vers public/build
php -S 127.0.0.1:8000 -t public   # ou : symfony serve
```

En développement, lancez `npm run watch` dans un second terminal.

Le journal fonctionnel et technique du projet se trouve dans `docs/PROJECT_LOG.md`. Il doit être mis à jour après chaque évolution importante.

## Structure

```
src/
  Controller/   Accueil, événements, commandes, billets, organisateur, scan
  Entity/       Event, TicketType, Order, Ticket
  Repository/   EventRepository (requêtes des événements à venir)
  Service/      OrderPlacer : réservation des places et génération des billets
  Form/         EventType (création d'événement), OrderType (acheteur)
  DataFixtures/ Données de démonstration
templates/      Twig : base, partials, pages par parcours
assets/         app.js et styles/app.css (Tailwind)
config/         Configuration Symfony (Doctrine, Twig, Encore, routes)
```

## Routes principales

| Route | URL | Rôle |
|---|---|---|
| `home` | `/` | Accueil « Ce soir à Olympia » |
| `events` | `/evenements` | Liste, filtre par catégorie et recherche |
| `event_show` | `/evenements/{slug}` | Fiche et choix des billets |
| `checkout` | `/evenements/{slug}/billets` | Informations acheteur et paiement |
| `order_show` | `/commandes/{id}` | Confirmation et billets |
| `tickets` | `/mes-billets` | Billets de la session |
| `organizer_dashboard` | `/organisateur` | Tableau de bord |
| `organizer_event_new` | `/organisateur/evenements/nouveau` | Création d'événement |
| `organizer_scan` | `/organisateur/scan` | Scan des billets |
| `organizer_scan_validate` | `/organisateur/scan/valider` | API de validation (POST) |

## À faire avant la mise en production

- **Paiement** : brancher le prestataire mobile money et carte dans `OrderPlacer` ou un service dédié.
- **Authentification** : protéger `/organisateur/*` (security bundle, rôle `ROLE_ORGANIZER`). Aujourd'hui, les routes sont publiques.
- **Comptes acheteurs** : `/mes-billets` s'appuie sur la session. Ajouter un compte client pour retrouver les billets.
- **QR code** : `order/confirmation.html.twig` affiche une maquette. Générer un QR réel à partir du code du billet (bibliothèque `endroid/qr-code`).
- **Places en direct** : `app.js` simule la baisse des places sur l'accueil. Exposer une route JSON et l'appeler périodiquement.
- **Base de données** : SQLite par défaut (`.env`). Pour MySQL ou PostgreSQL, modifier `DATABASE_URL`.
