# Projet - Formulaire d'Identification Sécurisé EN PHP (sans framework)

## Description
Ce projet est conçu pour être exécuté en **local** sur un environnement de développement, il consiste en un formulaire d'authentification sécurisé. Il permet de tester un identifiant et un mot de passe, avec la possibilité d'ajouter un nouveau compte. Le formulaire inclut des fonctionnalités comme la réinitialisation des champs, la validation de l'utilisateur et la gestion des tentatives de connexion avec un délai après plusieurs erreurs.

### Fonctionnalités principales :
- Formulaire avec champ identifiant et mot de passe
- 3 boutons : **Reset**, **Valider** (affichage de message de succès ou d'erreur), **Ajout Compte**
- Messages d'état : "Vous êtes connecté" ou "Erreur. Recommencez"
- Utilisation d'une base de données MySQL pour gérer les utilisateurs et les tentatives de connexion
- Sécurisation avec HTTPS (certificat auto-signé)
- Protection contre les attaques CSRF (via token unique)
- Limitation des tentatives de connexion et délai après plusieurs échecs (prévention des attaques par force brute notamment en utilisant l'IP pour bloquer un nombre de tentatives erronées trop grand)
- Hachage des mots de passe avec l'algorithme **bcrypt** pour renforcer la sécurité des données sensibles
- En-têtes de sécurité HTTP (Content-Security-Policy, X-Frame-Options, X-Content-Type-Options, Referrer-Policy)

## Technologies utilisées
- **PHP** : pour la gestion du serveur et des scripts de traitement
- **MySQL** : pour la gestion de la base de données des utilisateurs
- **HTML/CSS** : pour le design du formulaire
- **JavaScript** : pour certaines interactions côté client
- **XAMPP** : utilisé comme serveur local pour PHP et MySQL

## Installation et utilisation

1. **Cloner ou télécharger le dépôt** :
   - Cloner ce dépôt GitHub ou télécharger les fichiers sur votre machine

2. **Configurer la base de données** :
   - Créer une base de données nommée `bdd_mdp_securise` dans **phpMyAdmin** ou votre gestionnaire MySQL
   - Importer le fichier `bdd_mdp_securise.sql` pour initialiser la base de données

3. **Lancer un serveur local** :
   - Utilisez un serveur local comme **XAMPP** ou **WAMP** pour exécuter le projet en local
   - Assurez-vous que votre serveur MySQL est bien en cours d'exécution

> [!TIP]
> ### Configuration de HTTPS pour XAMPP (Environnement local)
>
> A. **Activer SSL dans XAMPP** :
> - Ouvrez le fichier `httpd.conf` dans `C:/xampp/apache/conf/` et assurez-vous que la ligne suivante est décommentée :
>     ```
>     LoadModule ssl_module modules/mod_ssl.so
>     ```
> - Créez un certificat SSL auto-signé à l'aide de la commande OpenSSL ou utilisez un certificat valide.
>     Si vous utilisez OpenSSL, voici la commande pour générer un certificat auto-signé :
>     ```bash
>     openssl req -x509 -newkey rsa:4096 -keyout server.key -out server.crt -days 365
>     ```
> - Ajoutez les lignes suivantes dans le fichier `httpd-ssl.conf` (`C:/xampp/apache/conf/extra/httpd-ssl.conf`) :
>     ```
>     SSLCertificateFile "C:/xampp/apache/conf/ssl.crt/server.crt"
>     SSLCertificateKeyFile "C:/xampp/apache/conf/ssl.key/server.key"
>     ```
> 
> B. **Configurer Apache pour rediriger HTTP vers HTTPS** :
> - Ajoutez ceci dans le fichier `httpd.conf` pour rediriger automatiquement les requêtes HTTP vers HTTPS :
>     ```
>     <VirtualHost *:80>
>         ServerName localhost
>         Redirect permanent / https://localhost/
>     </VirtualHost>
>     ```
> 
> C. **Redémarrer Apache** :
> - Redémarrez Apache dans le panneau de contrôle XAMPP pour appliquer les modifications.
>
> Une fois ces étapes effectuées on peut accéder au projet via `https://localhost/` au lieu de `http://localhost/`.

4. **Accéder au projet** :
   - Ouvrez un navigateur web et accédez à `https://localhost/nom_du_dossier` où `nom_du_dossier` est le répertoire où vous avez installé le projet.


## Identifiants de test
- **Identifiant** : `master`
- **Mot de passe** : `M@ster123`

## Sécurisation HTTPS
Ce projet utilise un **certificat auto-signé** pour activer HTTPS. 
Dans un contexte réel, il serait préférable d'utiliser un certificat valide, comme ceux fournis par **Let's Encrypt ou Cloudflare** ou un certificat payant.

## Mesures de Sécurité Implémentées :
1. **Redirection vers HTTPS** : Le projet impose l'utilisation de HTTPS pour garantir la sécurité des échanges entre le serveur et le client. Cela est effectué par la redirection automatique des requêtes HTTP vers HTTPS si nécessaire.
2. **Protection contre les attaques CSRF** : Chaque requête POST est protégée par un token CSRF, généré dynamiquement pour chaque session utilisateur, afin d'éviter toute manipulation malveillante de données par un attaquant.
3. **Limitation des tentatives de connexion** : Un mécanisme de sécurité limite le nombre de tentatives de connexion échouées en introduisant un délai entre chaque nouvelle tentative après un certain nombre d'échecs. Si l'utilisateur dépasse le nombre limite de tentatives (5), son adresse IP sera bloquée pour un certain temps. Le délai entre les tentatives échouées augmente exponentiellement après chaque échec.
4. **Hachage des mots de passe** : Les mots de passe des utilisateurs sont hachés avec l'algorithme **bcrypt**, ce qui garantit qu'ils ne sont jamais stockés en clair dans la base de données.
5. **En-têtes HTTP sécurisés** :
   - **Content-Security-Policy (CSP)** : Empêche le chargement de contenus non sécurisés à partir de sources non fiables.
   - **X-Frame-Options** : Empêche l'affichage du site dans des cadres (protection contre les attaques de type clickjacking).
   - **X-Content-Type-Options** : Empêche les attaques de type MIME-sniffing.
   - **Referrer-Policy** : Contrôle les informations envoyées dans l'en-tête HTTP `Referer` pour préserver la confidentialité de l'utilisateur.
6. **Protection contre XSS** : Utilisation de `htmlspecialchars()` pour assainir les données utilisateur avant de les afficher dans le HTML afin de prévenir les attaques Cross-Site Scripting (XSS).
7. **Validation des entrées utilisateur** : Toutes les données soumises par les utilisateurs sont validées côté serveur pour éviter les injections SQL et les entrées malveillantes.


## Fonctionnement et gestion des actions

### 1. Ajouter un compte
   - Pour ajouter un compte, utilisez le formulaire où vous pourrez entrer un **nouvel identifiant** et un **mot de passe**. Le mot de passe doit etre de 8 caractères avec une majuscule et un symbole spécial puis cliquez sur le bouton **Ajout Compte**. Si le nom utilisateur n'existe pas il sera ajouté et vous pourrez faire d'autre tests.
   - Le mot de passe sera automatiquement haché avec l'algorithme **bcrypt** avant d'être enregistré dans la base de données. 
   - Une fois l'ajout effectué, vous pouvez vous connecter avec ces identifiants.

### 2. Réinitialiser les champs
   - Si vous souhaitez vider les champs du formulaire (identifiant et mot de passe), cliquez simplement sur le bouton **Reset**. 
   - Cela réinitialisera les champs sans affecter les autres fonctionnalités du formulaire.

### 3. Se connecter
   - Entrez votre identifiant et votre mot de passe dans les champs appropriés, puis cliquez sur **Valider**.
   - Si les identifiants sont corrects, vous serez redirigé avec un message vous informant que vous êtes connecté. Sinon, un message d'erreur "Erreur. Recommencez" sera affiché.
   - Si vous avez fait une erreur dans vos tentatives, un délai sera ajouté avant que vous puissiez tenter une nouvelle connexion.
   - Si vous dépassez **5 tentatives échouées**, vous serez temporairement bloqué, et le délai d'attente entre les tentatives augmentera de manière exponentielle (par exemple, 5 secondes après la première erreur, 10 secondes après la deuxième, 30 secondes après la troisième, etc.).

### 4. Sécurité des tentatives de connexion
   - Un système de protection contre les attaques par force brute est mis en place. 
   - Après **5 tentatives échouées**, votre adresse IP sera temporairement bloquée pendant un certain temps. 
   - Ce mécanisme est conçu pour limiter les tentatives d'attaque par brute force. 
   - De plus, après chaque tentative échouée successive, le délai entre les tentatives sera doublé, rendant l'attaque plus difficile.

## Structure du projet
- **index.php** : Page principale contenant le formulaire d'authentification
- **bdd_mdp_securise.sql** : Script de base de données pour créer les tables nécessaires 
- **eaZyLogin.png** : Logo de la page web
- **README.md** : Le fichier que vous lisez actuellement

## Consignes du TP
- Le formulaire doit inclure :
  - 1 champ pour l'identifiant
  - 1 champ pour le mot de passe
  - 3 boutons : **Reset**, **Valider**, **Ajout Compte**
- Affichage des messages suivants :
  - "Vous êtes connecté" en cas de succès
  - "Erreur. Recommencez" en cas d'identifiant ou mot de passe incorrect
- **Base de données** : SQL (MySQL)
- **Déploiement** : GitHub, GitLab, ou Framagit

## Remarque
Dans ce projet, l'utilisation de HTTPS repose sur un certificat **auto-signé** uniquement pour cet exercice. Dans un projet réel, un certificat sécurisé et valide serait nécessaire.
Le TP est basé sur une seule page, car aucune consigne n'a été donnée pour l'ajout de plusieurs pages dans le projet.


## Images 

![image](https://github.com/user-attachments/assets/9e172ef9-5000-43a4-90b4-63299a189db4)

