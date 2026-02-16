<?php
session_start();

// Inclure les infos de connexion à la base
include '_conf.php';

// Connexion
$connexion = mysqli_connect($host, $user, $password, $bdd);
if (!$connexion) {
    die('Erreur de connexion : ' . mysqli_connect_error());
}

// Récupération utilisateur connecté
$login = $_SESSION['email'] ?? '';
if ($login) {
    $stmt = $connexion->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
} else {
    $user = null;
}

// ---------- Tables pour commentaires & notes (créées si non existantes) ----------
$connexion->query("
CREATE TABLE IF NOT EXISTS cr_commentaires (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cr_id INT NOT NULL,
  auteur_email VARCHAR(255) NOT NULL,
  contenu TEXT NOT NULL,
  date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (cr_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$connexion->query("
CREATE TABLE IF NOT EXISTS cr_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cr_id INT NOT NULL,
  prof_email VARCHAR(255) NOT NULL,
  note DECIMAL(5,2) NOT NULL,
  date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (cr_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Flags/messages
$showUserModal = false;

// Rôle utilisateur
$role = $user['role'] ?? null; // 'prof' | 'eleve'

// --------------------------------------------------------------------
// Gestion formulaires
// --------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Bouton paramètres -> ouvrir la modale utilisateur
    if (isset($_POST['user-param'])) {
        $showUserModal = true;
    }

    // Prof : ajouter un commentaire
    if (isset($_POST['ajouter_commentaire']) && $role === 'prof') {
        $cr_id = (int)($_POST['cr_id'] ?? 0);
        $contenu = trim($_POST['commentaire'] ?? '');
        if ($cr_id > 0 && $contenu !== '') {
            $stmt = $connexion->prepare("INSERT INTO cr_commentaires (cr_id, auteur_email, contenu) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $cr_id, $user['email'], $contenu);
            $stmt->execute();
            header("Location: index.php?comment_added=1#cr-$cr_id");
            exit;
        } else {
            header("Location: index.php?comment_error=1#cr-$cr_id");
            exit;
        }
    }

    // Prof : noter un CR (0–20)
    if (isset($_POST['noter_cr']) && $role === 'prof') {
        $cr_id = (int)($_POST['cr_id'] ?? 0);
        $note = trim($_POST['note'] ?? '');
        if ($cr_id > 0 && $note !== '' && is_numeric($note)) {
            $note_val = (float)$note;
            if ($note_val >= 0 && $note_val <= 20) {
                $stmt = $connexion->prepare("INSERT INTO cr_notes (cr_id, prof_email, note) VALUES (?, ?, ?)");
                $stmt->bind_param("isd", $cr_id, $user['email'], $note_val);
                $stmt->execute();
                header("Location: index.php?note_added=1#cr-$cr_id");
                exit;
            }
        }
        header("Location: index.php?note_error=1#cr-$cr_id");
        exit;
    }

    // Création CR (réservé aux élèves)
    if (isset($_POST['submit_cr'])) {
        if ($role !== 'eleve') { header("Location: index.php?forbidden=1"); exit; }

        $titre = $_POST['titre'] ?? '';
        $date = $_POST['date'] ?? '';
        $contenu = $_POST['contenu'] ?? '';
        $classe = $_POST['classe'] ?? '';
        $email = $login;

        if ($titre && $date && $contenu && $classe) {
            $stmt = $connexion->prepare("INSERT INTO compte_rendu (date_cr, titre, contenu, classe, utilisateur_email) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $date, $titre, $contenu, $classe, $email);
            $stmt->execute();
            header("Location: index.php?success=1");
            exit;
        } else {
            $erreur = "Tous les champs sont obligatoires.";
        }
    }

    // Suppression CR (élève propriétaire uniquement)
    if (isset($_POST['delete_cr'])) {
        if ($role !== 'eleve') { header("Location: index.php?forbidden=1"); exit; }

        $id = (int)$_POST['id'];
        $stmt = $connexion->prepare("DELETE FROM compte_rendu WHERE id = ? AND utilisateur_email = ?");
        $stmt->bind_param("is", $id, $login);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            header("Location: index.php?deleted=1");
        } else {
            header("Location: index.php?forbidden=1");
        }
        exit;
    }

    // Entrer en mode édition CR (élève propriétaire uniquement)
    if (isset($_POST['edit_cr'])) {
        if ($role !== 'eleve') { header("Location: index.php?forbidden=1"); exit; }

        $id = (int)$_POST['id'];
        $stmt = $connexion->prepare("SELECT * FROM compte_rendu WHERE id = ? AND utilisateur_email = ?");
        $stmt->bind_param("is", $id, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        $modif_cr = $result->fetch_assoc();
        if (!$modif_cr) {
            header("Location: index.php?forbidden=1");
            exit;
        }
    }

    // Mise à jour CR (élève propriétaire uniquement)
    if (isset($_POST['update_cr'])) {
        if ($role !== 'eleve') { header("Location: index.php?forbidden=1"); exit; }

        $id = (int)$_POST['id'];
        $titre = $_POST['titre'];
        $date = $_POST['date'];
        $contenu = $_POST['contenu'];
        $classe = $_POST['classe'];

        $stmt = $connexion->prepare("UPDATE compte_rendu SET titre = ?, date_cr = ?, contenu = ?, classe = ? WHERE id = ? AND utilisateur_email = ?");
        $stmt->bind_param("ssssis", $titre, $date, $contenu, $classe, $id, $login);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            header("Location: index.php?updated=1");
        } else {
            header("Location: index.php?forbidden=1");
        }
        exit;
    }
}

// Filtrage par classe
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

// Statistiques (affichées pour les profs)
$res = $connexion->query("SELECT COUNT(*) AS total_eleves FROM utilisateurs WHERE role = 'eleve'");
$nb_eleves = $res->fetch_assoc()['total_eleves'] ?? 0;

$res = $connexion->query("SELECT COUNT(*) AS total_cr FROM compte_rendu");
$nb_cr = $res->fetch_assoc()['total_cr'] ?? 0;

$res = $connexion->query("
    SELECT u.nom, COUNT(c.id) AS nb_cr
    FROM utilisateurs u
    LEFT JOIN compte_rendu c ON u.email = c.utilisateur_email
    WHERE u.role = 'eleve'
    GROUP BY u.nom
    ORDER BY u.nom
");
$liste = $res->fetch_all(MYSQLI_ASSOC);

// ---------- Récupérer commentaires & notes groupés par CR (méthode robuste) ----------
$commentaires_par_cr = [];
$notes_par_cr = [];
$stats_notes = []; // moyenne + nb

if (!empty($comptes_rendus)) {
    // on caste tous les IDs en int puis on construit un IN() sûr
    $idInts = array_map(fn($r) => (int)$r['id'], $comptes_rendus);
    $idInts = array_values(array_unique(array_filter($idInts, fn($v) => $v > 0)));
    if (!empty($idInts)) {
        $idsCsv = implode(',', $idInts);

        // Commentaires
        $sqlCom = "SELECT * FROM cr_commentaires WHERE cr_id IN ($idsCsv) ORDER BY date_ajout ASC";
        $resCom = $connexion->query($sqlCom);
        if ($resCom) {
            while ($c = $resCom->fetch_assoc()) {
                $commentaires_par_cr[(int)$c['cr_id']][] = $c;
            }
        }

        // Notes
        $sqlNote = "SELECT * FROM cr_notes WHERE cr_id IN ($idsCsv) ORDER BY date_ajout DESC";
        $resNote = $connexion->query($sqlNote);
        if ($resNote) {
            while ($n = $resNote->fetch_assoc()) {
                $notes_par_cr[(int)$n['cr_id']][] = $n;
            }
        }

        // Stats notes
        $sqlAgg = "
            SELECT cr_id, AVG(note) AS moyenne, COUNT(*) AS nb
            FROM cr_notes
            WHERE cr_id IN ($idsCsv)
            GROUP BY cr_id
        ";
        $resAgg = $connexion->query($sqlAgg);
        if ($resAgg) {
            while ($s = $resAgg->fetch_assoc()) {
                $cid = (int)$s['cr_id'];
                $stats_notes[$cid] = [
                    'moyenne' => (float)$s['moyenne'],
                    'nb' => (int)$s['nb']
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Stagito | Accueil</title>
    <link rel="stylesheet" href="global.css" data-cache-bust="true" />
    <link rel="stylesheet" href="home.css" data-cache-bust="true" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet' />
    <style>
        .modal {
            display: none; position: fixed; z-index: 9999; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff; margin: 10% auto; padding: 20px; border-radius: 10px;
            width: 80%; max-width: 500px; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .close { position: absolute; right: 15px; top: 10px; font-size: 25px; cursor: pointer; }
        .user-info-row { display:flex; justify-content:space-between; margin:8px 0; }
        .user-info-row b { margin-right:12px; }
        .note-badge { display:inline-block; padding:4px 8px; border-radius:999px; background:#eef; font-weight:600; margin-left:8px; }
        .comments { margin-top:10px; padding-left:10px; border-left:3px solid #eee; }
        .comment { margin:6px 0; font-size: 0.95rem; }
        .comment small { color:#666; }
        .grade-form, .comment-form { margin-top:10px; }
        .comment-form textarea { width:100%; min-height:70px; }
        .grade-input { width: 90px; padding:6px; }
        .btn { padding: 8px 12px; border-radius: 8px; border: none; background: #5b61ff; color:#fff; cursor:pointer; }
        .muted { color:#666; }
        .row-actions { margin-top: 8px; }
        .alert { padding:8px 10px; border-radius:8px; margin:8px 0; }
        .alert-ok { background:#e8f7ee; color:#096c3e; }
        .alert-err { background:#fde7e9; color:#9b1c1c; }
    </style>
</head>
<body>
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
<?php if ($user && $role === 'prof') { ?>
            <div class="type">
                <div style="margin: 30px auto; max-width: 800px; padding: 20px; border: 1px solid #ccc; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.1); background: #fff;">
                    <h2 style="text-align: center; margin-bottom: 20px;">📊 Statistiques</h2>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                        <thead>
                            <tr style="background-color: #f2f2f2;">
                                <th style="padding: 10px; border: 1px solid #ddd;">Nombre d'élèves</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Nombre de comptes rendus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><?= $nb_eleves ?></td>
                                <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><?= $nb_cr ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <h3 style="margin-top: 10px;">🧑‍🎓 Comptes rendus par élève</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f9f9f9;">
                                <th style="padding: 10px; border: 1px solid #ddd;">Nom de l'élève</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Nombre de comptes rendus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($liste as $ligne): ?>
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($ligne['nom']) ?></td>
                                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><?= $ligne['nb_cr'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
        <p></p>
    </div>
<?php } ?>
    <div id="message-container">
        <?php if (isset($_GET['success'])) echo "<p class='alert alert-ok'>✅ Compte rendu ajouté.</p>"; ?>
        <?php if (isset($_GET['deleted'])) echo "<p class='alert alert-ok'>🗑️ Compte rendu supprimé.</p>"; ?>
        <?php if (isset($_GET['updated'])) echo "<p class='alert alert-ok'>✏️ Compte rendu modifié.</p>"; ?>
        <?php if (isset($_GET['forbidden'])) echo "<p class='alert alert-err'>⛔ Action non autorisée.</p>"; ?>
        <?php if (isset($_GET['comment_added'])) echo "<p class='alert alert-ok'>💬 Commentaire ajouté.</p>"; ?>
        <?php if (isset($_GET['comment_error'])) echo "<p class='alert alert-err'>❌ Commentaire invalide.</p>"; ?>
        <?php if (isset($_GET['note_added'])) echo "<p class='alert alert-ok'>⭐ Note enregistrée.</p>"; ?>
        <?php if (isset($_GET['note_error'])) echo "<p class='alert alert-err'>❌ Note invalide (0 à 20).</p>"; ?>
        <?php if (isset($erreur)) echo "<p class='alert alert-err'>$erreur</p>"; ?>
    </div>
    <script>
        setTimeout(() => {
            const messageContainer = document.getElementById('message-container');
            if (messageContainer) { messageContainer.style.display = 'none'; }
        }, 3000);
    </script>

    <div class="container">
        <?php foreach ($comptes_rendus as $cr):
            $cr_id = (int)$cr['id'];
            $comms = $commentaires_par_cr[$cr_id] ?? [];
            $stat = $stats_notes[$cr_id] ?? null;
        ?>
            <div class="card" id="cr-<?= $cr_id ?>">
                <h4>
                    <?= htmlspecialchars($cr['titre']) ?>
                    — <small><?= htmlspecialchars($cr['date_cr']) ?></small>
                    <?php if ($stat): ?>
                        <span class="note-badge" title="Moyenne / Nombre de notes">
                            <?= number_format($stat['moyenne'], 2, ',', ' ') ?>/20 · <?= (int)$stat['nb'] ?> note<?= ((int)$stat['nb']>1?'s':'') ?>
                        </span>
                    <?php else: ?>
                        <span class="note-badge muted">Non noté</span>
                    <?php endif; ?>
                </h4>
                <p><strong>Classe : </strong><?= htmlspecialchars($cr['classe'] ?? 'Non spécifiée') ?></p>
                <p><?= nl2br(htmlspecialchars($cr['contenu'])) ?></p>

                <div class="row-actions">
                    <?php if ($role === 'eleve' && $login === $cr['utilisateur_email']): ?>
                        <!-- Eleve propriétaire : peut modifier/supprimer -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $cr_id ?>">
                            <button type="submit" name="edit_cr">✏️ Modifier</button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce compte rendu ?');">
                            <input type="hidden" name="id" value="<?= $cr_id ?>">
                            <button type="submit" name="delete_cr">🗑️ Supprimer</button>
                        </form>
                    <?php elseif ($role === 'prof'): ?>
                        <!-- Prof : lecture seule -->
                        <span class="muted">Lecture seule (prof)</span>
                    <?php endif; ?>
                </div>

                <!-- Commentaires -->
                <div class="comments">
                    <strong>Commentaires :</strong>
                    <?php if (empty($comms)): ?>
                        <div class="comment muted">Aucun commentaire pour l’instant.</div>
                    <?php else: ?>
                        <?php foreach ($comms as $cm): ?>
                            <div class="comment">
                                <small>Par <?= htmlspecialchars($cm['auteur_email']) ?> — <?= htmlspecialchars($cm['date_ajout']) ?></small>
                                <div><?= nl2br(htmlspecialchars($cm['contenu'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Formulaires prof : commenter + noter -->
                <?php if ($role === 'prof'): ?>
                    <form method="POST" class="comment-form">
                        <input type="hidden" name="cr_id" value="<?= $cr_id ?>">
                        <label>Ajouter un commentaire :</label>
                        <textarea name="commentaire" required></textarea>
                        <button type="submit" name="ajouter_commentaire" class="btn">Publier le commentaire</button>
                    </form>

                    <form method="POST" class="grade-form">
                        <input type="hidden" name="cr_id" value="<?= $cr_id ?>">
                        <label>Attribuer une note (0-20) :</label>
                        <input type="number" name="note" class="grade-input" step="0.5" min="0" max="20" required>
                        <button type="submit" name="noter_cr" class="btn">Enregistrer la note</button>
                    </form>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </div>

    <div class="footer">
        <?php if ($role === 'eleve'): ?>
            <button class="submit-button" id="showModal">+ Faire un nouveau compte rendu</button>
        <?php else: ?>
            <button class="submit-button" id="showModal" disabled title="Réservé aux élèves">+ Faire un nouveau compte rendu</button>
        <?php endif; ?>
    </div>

    <!-- Modale CR -->
    <div id="crModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3><?= isset($modif_cr) ? "Modifier le compte rendu" : "Nouveau compte rendu" ?></h3>
            <form method="POST" class="form">
                <input type="hidden" name="id" value="<?= $modif_cr['id'] ?? '' ?>">
                <label>Titre :</label><br>
                <input type="text" name="titre" value="<?= htmlspecialchars($modif_cr['titre'] ?? '') ?>" required <?= ($role==='eleve' ? '' : 'disabled') ?>><br><br>
                <label>Date :</label><br>
                <input type="date" name="date" value="<?= htmlspecialchars($modif_cr['date_cr'] ?? '') ?>" required <?= ($role==='eleve' ? '' : 'disabled') ?>><br><br>
                <label>Classe :</label><br>
                <select name="classe" required <?= ($role==='eleve' ? '' : 'disabled') ?>>
                    <option value="">-- Sélectionner --</option>
                    <option value="SIO CIEL 1" <?= (isset($modif_cr) && $modif_cr['classe'] == 'SIO CIEL 1') ? 'selected' : '' ?>>SIO CIEL 1</option>
                    <option value="SIO CIEL 2" <?= (isset($modif_cr) && $modif_cr['classe'] == 'SIO CIEL 2') ? 'selected' : '' ?>>SIO CIEL 2</option>
                    <option value="SIO SLAM 1" <?= (isset($modif_cr) && $modif_cr['classe'] == 'SIO SLAM 1') ? 'selected' : '' ?>>SIO SLAM 1</option>
                    <option value="SIO SLAM 2" <?= (isset($modif_cr) && $modif_cr['classe'] == 'SIO SLAM 2') ? 'selected' : '' ?>>SIO SLAM 2</option>
                </select><br><br>
                <label>Contenu :</label><br>
                <textarea name="contenu" rows="6" cols="50" required <?= ($role==='eleve' ? '' : 'disabled') ?>><?= htmlspecialchars($modif_cr['contenu'] ?? '') ?></textarea><br><br>
                <?php if (!isset($modif_cr)): ?>
                    <button type="submit" name="submit_cr" <?= ($role==='eleve' ? '' : 'disabled') ?>>Enregistrer</button>
                <?php else: ?>
                    <button type="submit" name="update_cr" <?= ($role==='eleve' ? '' : 'disabled') ?>>Mettre à jour</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Modale Infos utilisateur -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Informations utilisateur</h3>
            <?php if ($user): ?>
                <div class="user-info-row"><b>Nom</b><span><?= htmlspecialchars($user['nom']) ?></span></div>
                <div class="user-info-row"><b>Email</b><span><?= htmlspecialchars($user['email']) ?></span></div>
                <div class="user-info-row"><b>Rôle</b><span><?= htmlspecialchars($role) ?></span></div>
            <?php else: ?>
                <p>Aucun utilisateur connecté.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Modale CR
        const modal = document.getElementById("crModal");
        const showBtn = document.getElementById("showModal");
        const closeBtn = document.querySelector(".modal#crModal .close");

        if (showBtn) {
            showBtn.addEventListener("click", () => { modal.style.display = "block"; });
        }

        if (closeBtn) {
            closeBtn.addEventListener("click", () => {
                modal.style.display = "none";
                <?php if (isset($modif_cr)): ?> window.location.href = "index.php"; <?php endif; ?>
            });
        }

        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
                <?php if (isset($modif_cr)): ?> window.location.href = "index.php"; <?php endif; ?>
            }
        });

        // Modale utilisateur
        const userModal = document.getElementById("userModal");
        const userCloseBtn = document.querySelector(".modal#userModal .close");
        if (userCloseBtn) userCloseBtn.addEventListener("click", () => { userModal.style.display = "none"; });
        window.addEventListener('click', function(e) { if (e.target === userModal) userModal.style.display = "none"; });
        <?php if (!empty($showUserModal)): ?> userModal.style.display = "block"; <?php endif; ?>

        // Si on est en mode édition CR, ouvrir la modale CR directement
        <?php if (isset($modif_cr)): ?> modal.style.display = "block"; <?php endif; ?>
    });
</script>
</body>
</html>
