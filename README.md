# Projet Symfony - E-Commerce

Projet réalisé dans le cadre du cours de Symfony.

## Membres du groupe

- Mustapha Niamane
- [Nom Prénom de votre binôme/trinôme]

**Rendu pour :** `yoanncoualan`  
**Date limite :** Dimanche 10 novembre 2026 à 23h59

---

## Fonctionnalités

### 1. Accueil
- Liste des produits avec photo, prix et disponibilité en stock.
- Clic sur un produit pour accéder à sa fiche.
- Bouton d'ajout de produit pour les utilisateurs avec le rôle `ROLE_ADMIN`.

### 2. Fiche produit
- Affichage des détails d'un produit (nom, description, photo, prix, stock).
- Bouton d'ajout au panier avec choix de la quantité pour les utilisateurs connectés.
- L'utilisateur connecté est automatiquement associé à la commande/au panier.
- Boutons de modification et suppression pour le `ROLE_ADMIN`.

### 3. Panier
- Affichage uniquement du panier en cours (non payé) de l'internaute connecté.
- Informations de chaque ligne : Nom, Quantité, Prix unitaire, Montant total de la ligne.
- Bouton de suppression d'un article du panier.
- Affichage du montant total du panier.
- Bouton pour acheter le panier (validation de la commande, passage de l'état à payé, date d'achat automatique et mise à jour du stock).

### 4. Mon compte
- Modification des informations du profil (nom, prénom, email, mot de passe).
- Historique des commandes payées :
  - Identifiant de la commande
  - Montant de la commande
  - Date de la commande (générée automatiquement en base)
- Clic sur une commande pour afficher son contenu détaillé.

### 5. Contenu de la commande
- Affichage du contenu de la commande sélectionnée sous forme de tableau (avec date d'achat et sans bouton d'achat/suppression).

### 6. Rôle SUPER_ADMIN
- Hérite des droits du `ROLE_ADMIN`.
- Liste des paniers non achetés avec :
  - Utilisateur à qui appartient le panier
  - Numéro du panier
  - Contenu du panier
- Liste des utilisateurs inscrits aujourd'hui, triés du plus récent au plus ancien.

---

## Contraintes techniques

- Menus et affichages adaptés selon le rôle (`ROLE_USER`, `ROLE_ADMIN`, `ROLE_SUPER_ADMIN`).
- Formulaires avec validation des données.
- Messages Flash pour le succès et l'échec des actions.
- Pages d'erreur personnalisées (404, 403, 500).
- Traduction en 2 langues : Français (`fr`) et Anglais (`en`).
- EasyAdmin bundle non utilisé.

---

## Comptes de test

| Rôle | Email | Mot de passe |
| :--- | :--- | :--- |
| **Super Admin** | `superadmin@shop.com` | `password` |
| **Admin** | `admin@shop.com` | `password` |
| **Utilisateur** | `user@shop.com` | `password` |

---

## Installation et lancement

1. Installer les dépendances :
```bash
composer install
```

2. Configurer la base de données dans `.env.local` :
```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/EEMI?serverVersion=8.4.3&charset=utf8mb4"
```

3. Mettre à jour la base de données et charger les données de test :
```bash
php bin/console doctrine:schema:update --force
php bin/console app:init-data
```

4. Lancer le serveur local :
```bash
symfony server:start
```

5. Lancer les tests :
```bash
php ./vendor/bin/phpunit
```
