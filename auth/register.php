<?php
// ============================================================
//   NOVASTORE - auth/register.php
//   Inscription d'un nouveau client
// ============================================================

session_start();

// Si déjà connecté, rediriger
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';

$erreurs = [];
$succes  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom      = trim($_POST['nom']      ?? '');
    $prenom   = trim($_POST['prenom']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $tel      = trim($_POST['telephone'] ?? '');
    $mdp      = $_POST['mot_de_passe']  ?? '';
    $mdp2     = $_POST['confirmer_mdp'] ?? '';

    // --- Validation ---
    if (empty($nom))    $erreurs[] = 'Le nom est obligatoire.';
    if (empty($prenom)) $erreurs[] = 'Le prénom est obligatoire.';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $erreurs[] = 'Adresse e-mail invalide.';

    if (strlen($mdp) < 8)
        $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';

    if ($mdp !== $mdp2)
        $erreurs[] = 'Les mots de passe ne correspondent pas.';

    if (empty($erreurs)) {
        $pdo = getDB();

        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erreurs[] = 'Cette adresse e-mail est déjà utilisée.';
        } else {
            // Insérer l'utilisateur
            $hash = password_hash($mdp, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('
                INSERT INTO utilisateurs (nom, prenom, email, telephone, mot_de_passe, role)
                VALUES (?, ?, ?, ?, ?, "client")
            ');
            $stmt->execute([$nom, $prenom, $email, $tel, $hash]);

            $succes = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription – NovaStore</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 40px 20px;
        }
        .auth-card {
            background: white;
            border-radius: 16px;
            padding: 48px 40px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.10);
        }
        .auth-logo {
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: #1D3557;
            margin-bottom: 8px;
        }
        .auth-logo strong { color: #E63946; }
        .auth-subtitle {
            text-align: center;
            color: #6c757d;
            margin-bottom: 32px;
            font-size: 0.95rem;
        }
        .auth-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .auth-link a { color: #E63946; font-weight: 600; }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <header class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">
                Nova<strong>Store</strong>
            </a>
        </div>
    </header>

    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-logo">Nova<strong>Store</strong></div>
            <p class="auth-subtitle">Créer votre compte client</p>

            <?php if ($succes): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($succes) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($erreurs)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul style="margin:0; padding-left:16px;">
                        <?php foreach ($erreurs as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!$succes): ?>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom"
                               value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                               placeholder="Votre nom" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom"
                               value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                               placeholder="Votre prénom" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Adresse e-mail *</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="exemple@email.com" required>
                </div>

                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone"
                           value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                           placeholder="+216 XX XXX XXX">
                </div>

                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe *</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe"
                           placeholder="Minimum 8 caractères" required>
                </div>

                <div class="form-group">
                    <label for="confirmer_mdp">Confirmer le mot de passe *</label>
                    <input type="password" id="confirmer_mdp" name="confirmer_mdp"
                           placeholder="Répéter le mot de passe" required>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-plus"></i> Créer mon compte
                </button>
            </form>
            <?php else: ?>
                <div style="text-align:center; margin-top:20px;">
                    <a href="login.php" class="btn-submit" style="display:inline-flex; text-decoration:none;">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </a>
                </div>
            <?php endif; ?>

            <div class="auth-link">
                Déjà un compte ? <a href="login.php">Se connecter</a>
            </div>
        </div>
    </div>
</body>
</html>