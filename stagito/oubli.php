<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mot de passe oublié</title>
  <link rel="stylesheet" type="text/css" href="style.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f9;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .container {
      max-width: 400px;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      text-align: center;
    }
    h1 {
      color: #333;
      margin-bottom: 20px;
    }
    .message {
      margin: 15px 0;
      padding: 10px;
      border-radius: 5px;
    }
    .success {
      background-color: #d4edda;
      color: #155724;
    }
    .error {
      background-color: #f8d7da;
      color: #721c24;
    }
    label {
      display: block;
      margin-bottom: 5px;
      color: #555;
      text-align: left;
    }
    input[type="email"], input[type="submit"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }
    input[type="submit"] {
      background-color: #007bff;
      color: #fff;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    input[type="submit"]:hover {
      background-color: #0056b3;
    }
    .form-title {
      font-size: 18px;
      margin-bottom: 15px;
      color: #666;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Mot de passe oublié</h1>
    <p class="form-title">Récupération de mot de passe</p>
    <?php
    include "_conf.php";
    if (isset($_POST['email'])) {
      $lemail = $_POST['email'];
      echo "<p>Le formulaire a été envoyé avec comme email la valeur : $lemail</p>";

      if ($connexion = mysqli_connect($host, $user, $password, $bdd)) {
        $requete = "SELECT * FROM utilisateurs WHERE email='$lemail'";
        $resultat = mysqli_query($connexion, $requete);
        $login = 0;
        while ($donnees = mysqli_fetch_assoc($resultat)) {
          $login = $donnees['email'];
          $mot_de_passe = $donnees['mot_de_passe'];
        }
        if ($login != 0) {
      // Génération d'un mot de passe provisoire
      $mot_de_passe_provisoire = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);

      // Mise à jour du mot de passe provisoire dans la base de données
      $update_requete = "UPDATE utilisateurs SET mot_de_passe='$mot_de_passe_provisoire' WHERE email='$lemail'";
      if (mysqli_query($connexion, $update_requete)) {
        echo "<p class='success'>Un mot de passe provisoire a été généré : $mot_de_passe_provisoire</p>";
      } else {
        echo "<p class='error'>ERREUR : Impossible de mettre à jour le mot de passe provisoire</p>";
      }

      $message = "Bonjour, voici votre mot de passe provisoire pour vous connecter : $mot_de_passe_provisoire";
      echo "<p class='success'>Un email vous a été envoyé avec votre mot de passe provisoire.</p>";
      mail($lemail, 'Mot de passe oublié sur le site TI', $message);
    } else {
      echo "<p class='error'>ERREUR : Email introuvable</p>";
        }
        mysqli_close($connexion);
      } else {
        echo "<p class='error'>ERREUR : Connexion à la base de données échouée</p>";
      }
    }
    ?>
    <form method="POST">
      <label for="email">Email</label>
      <input type="email" name="email" required>
      <input type="submit" name="submit" value="Valider">
    </form>
  </div>
</body>
</html>
