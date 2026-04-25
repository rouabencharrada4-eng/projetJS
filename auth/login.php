<?php
// ============================================================
//   NOVASTORE - auth/login.php
//   Connexion utilisateur (client + admin)
// ============================================================

session_start();

// Si déjà connecté, rediriger selon le rôle
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

require_once '../config/db.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($mdp)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($mdp, $user['mot_de_passe'])) {
            // Créer la session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['prenom']  = $user['prenom'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            // Redirection selon rôle
            if ($user['role'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../index.php');
            }
            exit;
        } else {
            $erreur = 'E-mail ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion – NovaStore</title>
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
            max-width: 440px;
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
        .forgot-link {
            text-align: right;
            margin-top: -12px;
            margin-bottom: 16px;
        }
        .forgot-link a {
            font-size: 0.85rem;
            color: #E63946;
        }
        .auth-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .auth-link a { color: #E63946; font-weight: 600; }
        .divider {
            text-align: center;
            color: #adb5bd;
            margin: 20px 0;
            font-size: 0.85rem;
            position: relative;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #dee2e6;
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }
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
            <p class="auth-subtitle">Connectez-vous à votre compte</p>

            <?php if ($erreur): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="exemple@email.com" required autofocus>
                </div>

                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe"
                           placeholder="Votre mot de passe" required>
                </div>

                <div class="forgot-link">
                    <a href="#">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>

            <div class="divider">ou</div>

            <div class="auth-link">
                Pas encore de compte ? <a href="register.php">S'inscrire gratuitement</a>
            </div>
        </div>
    </div>
</body>
</html>