<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stagito | Accueil</title>
    <link rel="stylesheet" href="global.css" data-cache-bust="true">
    <link rel="stylesheet" href="home.css" data-cache-bust="true">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 25px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<?php
session_start();

// Inclure les informations de connexion à la base de données
include '_conf.php';

// Connexion à la base de données
$connexion = mysqli_connect($host, $user, $password, $bdd);
if (!$connexion) {
    die('Erreur de connexion : ' . mysqli_connect_error());
}

// Récupérer les informations de l'utilisateur connecté
$login = $_SESSION['nom'] ?? '';
if ($login) {
    $stmt = $connexion->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
} else {
    $user = null;
}

// Gestion des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_cr'])) {
        $titre = $_POST['titre'] ?? '';
        $date = $_POST['date'] ?? '';
        $contenu = $_POST['contenu'] ?? '';
        $classe = $_POST['classe'] ?? '';
        if ($titre && $date && $contenu && $classe) {
            $stmt = $connexion->prepare("INSERT INTO compte_rendu (date_cr, titre, contenu,classe) VALUES (?, ?, ?, ? )");
            $stmt->bind_param("ssss", $date, $titre, $contenu, $classe);
            $stmt->execute();
            header("Location: index.php?success=1");
            exit;
        } else {
            $erreur = "Tous les champs sont obligatoires.";
        }
    }

    if (isset($_POST['delete_cr'])) {
        $id = $_POST['id'];
        $stmt = $connexion->prepare("DELETE FROM compte_rendu WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: index.php?deleted=1");
        exit;
    }

    if (isset($_POST['edit_cr'])) {
        $id = $_POST['id'];
        $stmt = $connexion->prepare("SELECT * FROM compte_rendu WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $modif_cr = $result->fetch_assoc();
    }

    if (isset($_POST['update_cr'])) {
        $id = $_POST['id'];
        $titre = $_POST['titre'];
        $date = $_POST['date'];
        $contenu = $_POST['contenu'];
        $classe = $_POST['classe'];
        $stmt = $connexion->prepare("UPDATE compte_rendu SET titre = ?, date_cr = ?, contenu = ?, classe = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $titre, $date, $contenu, $classe, $id);
        $stmt->execute();
        header("Location: index.php?updated=1");
        exit;
    }
}

// Récupérer les comptes rendus
$classe_filter = $_POST['libelle_classes'] ?? '';
$comptes_rendus = [];
if ($classe_filter) {
    $stmt = $connexion->prepare("SELECT * FROM compte_rendu WHERE classe = ?");
    $stmt->bind_param("s", $classe_filter);
} else {
    $stmt = $connexion->prepare("SELECT * FROM compte_rendu");
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $comptes_rendus[] = $row;
}
?>
<header>
    <div class="title">
        <h1>Stagito.fr</h1>
    </div>
    <div class="type">
        <h2>Bienvenue <?= htmlspecialchars($user['nom'] ?? '') ?> !</h2>
    </div>
    <div class="leave">
        <form method="POST">
            <button class="submit-button" type="submit" name="user-param"><i class='bx bx-cog'></i></button>
            <button class="submit-button" type="submit" name="logout" onclick="if(confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) { window.location.href='signin.php'; return false; }">Deconnexion</button>
        </form>
    </div>
</header>
<main>
    <div class="title">
        <form method="POST" class="form">
            <div class="cr-param">
                <select name="libelle_classes" class="libelle_classes">

                    <option value="SIO CIEL 1" <?= $classe_filter == 'SIO CIEL 1' ? 'selected' : '' ?>>SIO CIEL 1</option>
                    <option value="SIO CIEL 2" <?= $classe_filter == 'SIO CIEL 2' ? 'selected' : '' ?>>SIO CIEL 2</option>
                    <option value="SIO SLAM 1" <?= $classe_filter == 'SIO SLAM 1' ? 'selected' : '' ?>>SIO SLAM 1</option>
                    <option value="SIO SLAM 2" <?= $classe_filter == 'SIO SLAM 2' ? 'selected' : '' ?>>SIO SLAM 2</option>
                </select>
                <input type="date" name="selectDate" class="selectDate" value="2025-05-06" min="2025-01-01" max="2025-12-21" />
            </div>
            <button type="submit" name="refresh" class="refresh" onclick="location.reload(); return false;">Actualiser</button>
        </form>
        <p></p>
    </div>

    <div id="message-container">
        <?php if (isset($_GET['success'])) echo "<p style='color:green;'>✅ Compte rendu ajouté.</p>"; ?>
        <?php if (isset($_GET['deleted'])) echo "<p style='color:red;'>🗑️ Compte rendu supprimé.</p>"; ?>
        <?php if (isset($_GET['updated'])) echo "<p style='color:blue;'>✏️ Compte rendu modifié.</p>"; ?>
        <?php if (isset($erreur)) echo "<p style='color:red;'>$erreur</p>"; ?>
    </div>
    <script>
        setTimeout(() => {
            const messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                messageContainer.style.display = 'none';
            }
        }, 3000);
    </script>

    <div class="container">
        <?php foreach ($comptes_rendus as $cr): ?>
            <div class="card">
                <h4><?= htmlspecialchars($cr['titre']) ?> — <small><?= htmlspecialchars($cr['date_cr']) ?></small></h4>
                <p><strong>Classe : </strong><?= htmlspecialchars($cr['classe'] ?? 'Non spécifiée') ?></p>
                <p><?= nl2br(htmlspecialchars($cr['contenu'])) ?></p>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $cr['id'] ?>">
                    <button type="submit" name="edit_cr">✏️ Modifier</button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce compte rendu ?');">
                    <input type="hidden" name="id" value="<?= $cr['id'] ?>">
                    <button type="submit" name="delete_cr">🗑️ Supprimer</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="footer">
        <button class="submit-button" id="showModal">+ Faire un nouveau compte rendu</button>
    </div>

    <div id="crModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3><?= isset($modif_cr) ? "Modifier le compte rendu" : "Nouveau compte rendu" ?></h3>
            <form method="POST" class="form">
                <input type="hidden" name="id" value="<?= $modif_cr['id'] ?? '' ?>">
                <label>Titre :</label><br>
                <input type="text" name="titre" value="<?= htmlspecialchars($modif_cr['titre'] ?? '') ?>" required><br><br>
                <label>Date :</label><br>
                <input type="date" name="date" value="<?= htmlspecialchars($modif_cr['date_cr'] ?? '') ?>" required><br><br>
                <label>Classe :</label><br>
                <select name="classe" required>
                    <option value="">-- Sélectionner --</option>
                    <option value="SIO CIEL 1" <?= (isset($modif_cr) && $modif_cr['classe'] == 'SIO CIEL 1') ? 'selected' : '' ?>>SIO CIEL 1</option>
                    <option value="SIO CIEL 2" <?= (isset($modif_cr) && $modif_cr['classe'] == 'SIO CIEL 2') ? 'selected' : '' ?>>SIO CIEL 2</option>
                    <option value="SIO SLAM 1" <?= (isset($modif_cr) && $modif_cr['classe'] == 'SIO SLAM 1') ? 'selected' : '' ?>>SIO SLAM 1</option>
                    <option value="SIO SLAM 2" <?= (isset($modif_cr) && $modif_cr['classe'] == 'SIO SLAM 2') ? 'selected' : '' ?>>SIO SLAM 2</option>
                </select><br><br>
                <label>Contenu :</label><br>
                <textarea name="contenu" rows="6" cols="50" required><?= htmlspecialchars($modif_cr['contenu'] ?? '') ?></textarea><br><br>
                <button type="submit" name="<?= isset($modif_cr) ? 'update_cr' : 'submit_cr' ?>">
                    <?= isset($modif_cr) ? 'Mettre à jour' : 'Enregistrer' ?>
                </button>
            </form>
        </div>
    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const modal = document.getElementById("crModal");
        const showBtn = document.getElementById("showModal");
        const closeBtn = document.querySelector(".modal .close");

        if (showBtn) {
            showBtn.addEventListener("click", function (e) {
                e.preventDefault();
                modal.style.display = "block";
            });
        }

        if (closeBtn) {
            closeBtn.onclick = function () {
                modal.style.display = "none";
            };
        }

        window.onclick = function (event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }

        <?php if (isset($modif_cr)) { ?>
            modal.style.display = "block";
        <?php } ?>
    });
</script>
</body>
</html>