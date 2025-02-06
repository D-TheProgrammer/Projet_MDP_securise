<?php
session_start();

//demander a forcerr HTTPS
if (!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on") {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

//CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

//Connexion bdd
$nom_serveur = "localhost";
$nom_utilisateur = "root";
$mot_de_passe_bdd = "";
$nom_bdd = "bdd_mdp_securise";

$connexion = new mysqli($nom_serveur, $nom_utilisateur, $mot_de_passe_bdd, $nom_bdd);
if ($connexion->connect_error) {
    die("Connexion échouée : " . $connexion->connect_error);
}

//headers pour la securite
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' https://maxcdn.bootstrapcdn.com;");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");

//Adresse IP de l'utilisateur
$adresse_ip = $_SERVER["REMOTE_ADDR"];

//Fonction pour voir et controler le delai avant une nouvelle tentative
function verifier_delai($connexion, $adresse_ip) {
    $requete = $connexion->prepare("SELECT tentatives, UNIX_TIMESTAMP(derniere_tentative) FROM tentative_connexion WHERE adresse_ip = ?");
    $requete->bind_param("s", $adresse_ip);
    $requete->execute();
    $requete->store_result();
    $requete->bind_result($tentatives, $derniere_tentative);
    $requete->fetch();
    
    if ($tentatives > 5){
        if ($requete->num_rows > 0) {
            $temps_actuel = time();
            $delai = min(pow(2, $tentatives), 1800);
            if ($temps_actuel - $derniere_tentative < $delai) {
                return [$delai - ($temps_actuel - $derniere_tentative), $tentatives];
            }
        }
    }
    
    return [0, 0];
}

list($temps_restant, $tentatives) = verifier_delai($connexion, $adresse_ip);
if ($temps_restant > 0) {
    echo "<script>alert('Trop de tentatives ! Attendez encore " . ceil($temps_restant / 60) . " minutes. Tentatives: $tentatives.'); window.location.href='index.php';</script>";
    exit();
}

//GESTion  de la connexion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["valider"])) {
    if (!isset($_POST["csrf_token"]) || $_POST["csrf_token"] !== $_SESSION["csrf_token"]) {
        die("Requête invalide !");
    }

    $identifiant = trim($_POST["identifiant"]);
    $mot_de_passe = trim($_POST["mot_de_passe"]);

    $requete_utilisateur = $connexion->prepare("SELECT id, password FROM users WHERE username = ?");
    $requete_utilisateur->bind_param("s", $identifiant);
    $requete_utilisateur->execute();
    $requete_utilisateur->store_result();
    $requete_utilisateur->bind_result($id_utilisateur, $mot_de_passe_hache);
    $requete_utilisateur->fetch();
    
    if ($requete_utilisateur->num_rows > 0 && password_verify($mot_de_passe, $mot_de_passe_hache)) {
        $_SESSION["user_id"] = $id_utilisateur;
        $_SESSION["username"] = $identifiant;
        $connexion->query("DELETE FROM tentative_connexion WHERE adresse_ip = '$adresse_ip'");
        echo "<script>alert('Vous êtes connecté !'); window.location.href='index.php';</script>";
        exit();
    } else {
        $requete_verification = $connexion->prepare("SELECT tentatives FROM tentative_connexion WHERE adresse_ip = ?");
        $requete_verification->bind_param("s", $adresse_ip);
        $requete_verification->execute();
        $requete_verification->store_result();
        
        if ($requete_verification->num_rows > 0) {
            $connexion->query("UPDATE tentative_connexion SET tentatives = tentatives + 1, derniere_tentative = NOW() WHERE adresse_ip = '$adresse_ip'");
        } else {
            $connexion->query("INSERT INTO tentative_connexion (adresse_ip, tentatives) VALUES ('$adresse_ip', 1)");
        }
        
        echo "<script>alert('Identifiant ou mot de passe incorrect.'); window.location.href='index.php';</script>";
        exit();
    }
    $stmt->close();
}


//Pour ajouter un nouvel utilisateur
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ajout_compte"])) {
    if ($_POST["csrf_token"] !== $_SESSION["csrf_token"]) {
        die("Requête invalide !");
    }

    $nouveau_identifiant = trim($_POST["identifiant"]);
    $nouveau_mdp = trim($_POST["mot_de_passe"]);

    if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $nouveau_mdp)) {
        echo "<script>alert('Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
        exit();
    }

    //On verfiie si si l'identifiant existe déjà
    $requete_verification = $connexion->prepare("SELECT id FROM users WHERE username = ?");
    $requete_verification->bind_param("s", $nouveau_identifiant);
    $requete_verification->execute();
    $requete_verification->store_result();

    if ($requete_verification->num_rows > 0) {
        echo "<script>alert('Erreur : Ce nom d\'utilisateur existe déjà!');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
        exit();
    }
    $requete_verification->close();

    //Hachage du mot de passe et on le met dans la base de donnee
    $nouveau_mdp_hache = password_hash($nouveau_mdp, PASSWORD_BCRYPT);

    $stmt_insert = $connexion->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt_insert->bind_param("ss", $nouveau_identifiant, $nouveau_mdp_hache);

    if ($stmt_insert->execute()) {
        echo "<script>alert('Compte ajouté avec succès !');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
        exit();
    } else {
        echo "<script>alert('Erreur lors de l\'ajout du compte.');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
        exit();
    }

    $stmt_insert->close();
}



$connexion->close();
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Sécurisée</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #e3f2fd;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }
        .bloc_logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .bloc_logo img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
        }
        h4 {
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="bloc_logo">
            <img src="eaZyLogin.png" alt="Logo du site">
        </div>
        <h2 class="text-center">EaZyLogin - Connexion</h2>
        <form method="POST">
           <!--token csrf de securite --> 
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="identifiant">Identifiant</label>
                <input type="text" id="identifiant" class="form-control" name="identifiant" required>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" class="form-control" name="mot_de_passe" required autocomplete="new-password" onpaste="return false;" oncopy="return false;">
            </div>
            <button type="submit" class="btn btn-primary" name="valider">Valider</button>
            <button type="submit" class="btn btn-success" name="ajout_compte">Ajout Compte</button>
            <button type="reset" class="btn btn-danger">Réinitialiser</button>
        </form>
        <br/>
        <h4>Ps : L'utilisation de HTTPS dans cet exercice utilise un certificat auto-signé.
            Bien évidemment, dans un cas concret, j'utiliserais un véritable certificat payant ou Cloudflare pour un gratuit.
            De même, le TP n'a qu'une seule page car en cours et dans les consignes il n'était pas demandé de faire plusieurs pages comme une où l'utilisateur arrive après être connecté.
        </h4>
    </div>
</body>
</html>
