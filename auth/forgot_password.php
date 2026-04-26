<?php
// ============================================================
//   NOVASTORE - auth/forgot_password.php
//   Demande de réinitialisation du mot de passe
// ============================================================

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$message  = '';
$type_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message  = 'Veuillez entrer une adresse e-mail valide.';
        $type_msg = 'error';
    } else {
        // Vérifier si l'email existe
        $stmt = $pdo->prepare('SELECT id, prenom FROM utilisateurs WHERE email = ? AND actif = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Générer un token unique
            $token     = bin2hex(random_bytes(32));
            $expire_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Supprimer les anciens tokens de cet utilisateur
            $pdo->prepare('DELETE FROM tokens_reset WHERE utilisateur_id = ?')
                ->execute([$user['id']]);

            // Enregistrer le nouveau token
            $pdo->prepare('INSERT INTO tokens_reset (utilisateur_id, token, expire_at) VALUES (?, ?, ?)')
                ->execute([$user['id'], $token, $expire_at]);

            // Lien de réinitialisation
            $lien = 'http://localhost/projetjs/auth/reset_password.php?token=' . $token;

            // ⚠️ En production : envoyer par email (mail() ou PHPMailer)
            // Pour le dev, on affiche le lien directement
           $lien_reset = $lien;
$message    = '';
$type_msg   = 'success';
        } else {
            // Sécurité : même message si email inexistant (évite l'énumération)
            $message  = 'Si cet email existe, un lien de réinitialisation a été envoyé.';
            $type_msg = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié – NovaStore</title>
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
        .auth-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .auth-link a { color: #E63946; font-weight: 600; }
        .icon-box {
            text-align: center;
            margin-bottom: 24px;
        }
        .icon-box i {
            font-size: 3rem;
            color: #E63946;
            background: #fce7f3;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.85rem; color: #374151; margin-bottom: 6px; }
        .form-group input {
            width: 100%; padding: 12px 16px; border: 2px solid #e9ecef;
            border-radius: 8px; font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem; outline: none; transition: 0.2s;
        }
        .form-group input:focus { border-color: #E63946; box-shadow: 0 0 0 3px rgba(230,57,70,0.1); }
        .btn-submit {
            width: 100%; padding: 14px; background: #E63946; color: white;
            border: none; border-radius: 8px; font-size: 1rem; font-weight: 600;
            cursor: pointer; font-family: 'DM Sans', sans-serif;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: 0.2s;
        }
        .btn-submit:hover { background: #c1121f; }
        .alert {
            word-break: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="logo">Nova<strong>Store</strong></a>
    </div>
</header>

<div class="auth-page">
    <div class="auth-card">

        <div class="icon-box">
            <i class="fas fa-lock"></i>
        </div>

        <div class="auth-logo">Mot de passe oublié ?</div>
        <p class="auth-subtitle">Entrez votre email pour recevoir un lien de réinitialisation.</p>

        <?php if ($type_msg === 'success'): ?>
<div style="text-align:center; padding:20px 0;">
    <p style="color:#10b981; font-weight:700; font-size:1rem; margin-bottom:16px;">
        Lien généré avec succès !
    </p>
    <a href="<?= $lien_reset ?>"
       style="display:inline-block; background:#1D3557; color:white;
              padding:12px 24px; border-radius:8px; text-decoration:none;
              font-weight:600; font-size:0.9rem;">
        Réinitialiser mon mot de passe
    </a>
    <p style="color:#adb5bd; font-size:0.75rem; margin-top:16px;">
        Ce lien est valable 1 heure.
    </p>
</div>
<?php endif; ?>

        <?php if ($type_msg !== 'success'): ?>
        <form method="POST">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Adresse e-mail</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="exemple@email.com" required autofocus>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Envoyer le lien
            </button>
        </form>
        <?php endif; ?>

        <div class="auth-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
        </div>
    </div>
</div>

</body>
</html>