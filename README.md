# iTarot

Application Symfony pour gérer facilement ses parties de tarot par listes. Chaque liste regroupe un ensemble donné de joueurs et contient l'historique des parties jouées par ces mêmes joueurs. On crée donc une nouvelle liste dès que la composition des joueurs change, ce qui permet de suivre proprement chaque groupe.

## Fonctionnalités principales
- Création et gestion de listes de parties : une liste contient les joueurs concernés ainsi que leurs parties associées.
- Suivi du score cumulé d'un joueur sur l'ensemble des parties d'une même liste.
- Vue synthétique des performances d'un groupe de joueurs sur plusieurs parties successives.

## Démarrage rapide
1. Installer les dépendances PHP :
   ```bash
   composer install
   ```
2. Configurer les variables d'environnement (`.env`, `.env.local`) selon votre base de données.
3. Exécuter les migrations :
   ```bash
   php bin/console doctrine:migrations:migrate
   ```
4. Lancer le serveur de développement :
   ```bash
   symfony serve
   ```
