<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stagito | Accueil</title>
    <link rel="stylesheet" href="global.css" data-cache-bust="true">
    <link rel="stylesheet" href="login.css" data-cache-bust="true">
</head>
<body>
    <main>
        <div class="title-box">
            <div class="title">
                <h1>Stagito.fr</h1>
            </div>
        </div>
        <div class="container">
            <div class="card">
                <div class="card-title">
                    <h3>Inscription</h3>
                </div>
                <form action="signup_verif.php" class="card-form" method="POST">
                    <div class="card-form-double">
                        <input type="text" name="nom" placeholder="Nom" value="" required>
                        <input type="text" name="prenom" placeholder="Prenom" value="" required>
                    </div>
                    <select name="classe" id="classe" required>
                        <option value="1">SIO SLAM 1</option>
                        <option value="2">SIO SLAM 2</option>
                        <option value="3">SIO CIEL 1</option>
                        <option value="4">SIO CIEL 2</option>
                        <option value="5">Autre</option>
                    </select>
                    <select name="role" id="role" required>
                        <option value="eleve">Élève</option>
                        <option value="prof">Professeur</option>
                        <option value="secretariat">Secrétariat</option>
                        <option value="admin">Administrateur</option>
                    </select>
                    <input type="text" name="email" placeholder="email" value="" required>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <div class="card-bottom-form">
                        <div class="card-bottom-left-form">
                            <button>S'inscrire</button>
                        </div>
                        <div class="card-bottom-right-form">
                            <p>Déjà inscrit ? </p>
                            <a href="signin.php">Se connecter</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
    <script>
        function bustCache() {
          const scripts = document.querySelectorAll('script[data-cache-bust="true"]');
          const links = document.querySelectorAll('link[data-cache-bust="true"]');
          
          scripts.forEach(script => {
            const src = script.getAttribute('src');
            if (src) {
              const newSrc = src.split('?')[0] + '?v=' + new Date().getTime();
              script.setAttribute('src', newSrc);
            }
          });
    
          links.forEach(link => {
            const href = link.getAttribute('href');
            if (href) {
              const newHref = href.split('?')[0] + '?v=' + new Date().getTime();
              link.setAttribute('href', newHref);
            }
          });
        }
    
        window.addEventListener('load', bustCache);
    </script>
</html>