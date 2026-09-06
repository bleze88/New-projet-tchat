# Tchat

Réécriture moderne et sécurisée d'un vieux projet de tchat PHP/MySQL. Salons
multiples, messagerie privée, mises à jour en quasi temps réel via
Server-Sent Events, PHP 8+ avec PDO/requêtes préparées.

## Prérequis

- PHP >= 8.1 avec les extensions `pdo_mysql`, `fileinfo`, `gd` (ou `mbstring`
  fourni par le polyfill Composer)
- MySQL ou MariaDB
- Composer

## Installation

```bash
composer install
cp .env.example .env
# éditer .env avec les identifiants de la base de données locale

mysql -u root -p -e "CREATE DATABASE projet_tchat CHARACTER SET utf8mb4;"
mysql -u root -p projet_tchat < database/schema.sql
mysql -u root -p projet_tchat < database/seed.sql

php -S localhost:8000 -t public
```

Puis ouvrir http://localhost:8000.

## Structure

- `public/` — seul dossier exposé au serveur web ; une page par fichier
  (`register.php`, `login.php`, `rooms.php`, `room.php`, `dm.php`,
  `profile.php`, `stream.php` pour le flux SSE)
- `src/Support/` — utilitaires (connexion PDO, session, CSRF, auth,
  anti-flood, upload d'avatar, rendu BBCode sécurisé)
- `src/Models/` — accès aux données (`User`, `Room`, `Message`,
  `DirectMessage`)
- `src/templates/` — vues PHP simples
- `database/schema.sql` / `seed.sql` — structure et données de départ

## Sécurité

Points corrigés par rapport à l'ancienne version du projet :

- PDO + requêtes préparées partout (plus de `mysql_*`, plus de SQL concaténé)
- Mots de passe hashés avec `password_hash()` (bcrypt/argon2)
- Jeton CSRF vérifié sur chaque formulaire de type POST
- Sortie systématiquement échappée (`htmlspecialchars`) ; le contenu des
  messages passe par un formatteur BBCode qui échappe d'abord le texte puis
  n'autorise qu'une liste réduite de balises, avec validation stricte du
  schéma des URL (http/https uniquement) avant insertion dans `href`/`src`
- Session régénérée à la connexion, cookies `httponly`/`samesite`
- Upload d'avatar : type MIME vérifié par contenu (`finfo`), dimensions et
  taille contrôlées avec la bonne logique, nom de fichier généré
  aléatoirement côté serveur, dossier de destination protégé par `.htaccess`
- Anti-flood sur l'envoi de messages et les tentatives de connexion
- Aucun secret commité : les identifiants de base de données vivent dans
  `.env` (ignoré par git)

## Temps réel

`public/stream.php` est un flux Server-Sent Events unique par utilisateur :
le navigateur ouvre une connexion `EventSource`, le serveur interroge la base
toutes les secondes et pousse les nouveaux messages tant que la connexion est
ouverte (limitée à 60s, le navigateur se reconnecte automatiquement). Cela
fonctionne sans process persistant, donc sur n'importe quel hébergement PHP
classique. Derrière nginx/PHP-FPM, pensez à désactiver le buffering
(`X-Accel-Buffering: no`, déjà envoyé) et à augmenter `fastcgi_read_timeout`
au-delà de 60s.
