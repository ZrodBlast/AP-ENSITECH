<?php
include("_conf.php"); // Connexion à la BDD (vérifie si $pdo est déjà défini ici)

try {
    // Créer une nouvelle connexion PDO si elle n’existe pas déjà
    if (!isset($pdo)) {
        $pdo = new PDO('mysql:host=localhost;dbname=projet_teixeira', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les informations du formulaire
    $nom = isset($_POST['nom']) ? $_POST['nom'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';

    // Vérifier si l'email est valide
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "L'email n'est pas valide.";
        exit;
    }

    // Hacher le mot de passe pour le rendre sécurisé
    $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

    // Préparer et exécuter la requête d'insertion dans la base de données
    try {
        $sql = "INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (:nom, :email, :mot_de_passe, :role)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':mot_de_passe', $mot_de_passe_hash);
        $stmt->bindParam(':role', $role);

        // Si l'insertion réussit, rediriger vers signin.php
        if ($stmt->execute()) {
            header("Location: signin.php");
            exit();
        } else {
            echo "Une erreur est survenue lors de l'inscription.";
        }
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire d'inscription</title>
</head>
<body>
    <h2>Inscription</h2>
    <form method="POST">
        <label for="nom">Nom :</label>
        <input type="text" name="nom" id="nom" required>
        <br><br>
        <label for="email">Email :</label>
        <input type="email" name="email" id="email" required>
        <br><br>
        <label for="mot_de_passe">Mot de passe :</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" required>
        <br><br>
        <label for="role">Rôle :</label>
        <select name="role" id="role" required>
            <option value="eleve">Élève</option>
            <option value="prof">Professeur</option>
        </select>
        <br><br>
        <button type="submit">S'inscrire</button>
    </form>
</body>
</html>
