# Youngbrighthing

Boutique e-commerce de mode en pièces uniques ("capsules"/drops), construite avec Symfony 7.1, Postgres et Stripe Checkout.

## Prérequis

- Docker et Docker Compose

## Démarrage

```bash
docker compose up -d
```

L'application est ensuite disponible sur http://localhost:8080. Les logs sont visibles avec `docker compose logs -f app`.

## Variables d'environnement

Les valeurs par défaut/non sensibles sont dans `.env` (versionné). Les secrets réels (Stripe, mailer) doivent être placés dans `.env.local` (non versionné, jamais commité) :

```
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
MAILER_DSN="smtp://user:pass@host:port"
```

Pour tester les webhooks Stripe en local (nécessite le [Stripe CLI](https://stripe.com/docs/stripe-cli)) :

```bash
stripe listen --forward-to localhost:8080/stripe/webhook
```

Copier le `whsec_...` affiché dans `STRIPE_WEBHOOK_SECRET` de `.env.local`.

## Base de données

Le schéma est géré par les migrations Doctrine (`migrations/`). Pour appliquer les migrations sur une base neuve :

```bash
docker compose exec app php bin/console doctrine:migrations:migrate
```

## Données de démonstration

```bash
docker compose exec app php bin/console app:seed
```

Crée deux comptes (`admin@youngbrighthing.com` / `admin`, `user@youngbrighthing.com` / `user`), 3 catégories et 6 produits. **Attention : cette commande vide entièrement les tables produits/commandes/utilisateurs avant de les recréer.**

## Tests

Les tests tournent sur une base Postgres séparée (`<nom_de_la_base>_test`, suffixe automatique via `config/packages/doctrine.yaml`).

```bash
# Une seule fois, pour créer/migrer la base de test
docker compose exec -e DATABASE_URL='postgresql://app:!ChangeMe!@database:5432/app?serverVersion=16&charset=utf8' -e APP_ENV=test app php bin/console doctrine:migrations:migrate --no-interaction

# Lancer la suite
docker compose exec -e DATABASE_URL='postgresql://app:!ChangeMe!@database:5432/app?serverVersion=16&charset=utf8' -e APP_ENV=test app php bin/phpunit
```

Les tests n'envoient jamais de vrais emails (`MAILER_DSN=null://null` dans `.env.test`) ni n'appellent l'API Stripe.

## Administration

`/admin/dashboard` (rôle `ROLE_ADMIN`) : gestion des produits, catégories, utilisateurs, consultation des commandes et statistiques de vente.
