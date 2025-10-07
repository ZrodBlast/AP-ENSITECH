<?php
include("_conf.php"); // Connexion à la BDD

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
    $email = isset($_POST['email']) ? trim(htmlspecialchars($_POST['email'])) : '';
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';

    try {
        // Préparer la requête pour récupérer l'utilisateur
        $sql = "SELECT * FROM utilisateurs WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
            // Tu peux démarrer une session ici si besoin
             session_start();
             // Dans signin.php (ou script de connexion)
            $_SESSION['nom'] = $user['nom']; // Gardez le nom pour l'affichage si vous le souhaitez
            $_SESSION['email'] = $user['email']; // Stockez l'email ici

            // Rediriger vers index.php après connexion réussie
            header('Location: index.php');
            exit();
        } else {
            echo "❌ Identifiants incorrects.";
        }
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
} else {
    echo "Méthode de requête non autorisée.";
}
?>
