<?php
// ============================================================
//   NOVASTORE - auth/reset_password.php
//   Réinitialisation du mot de passe via token
// ============================================================

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$token    = trim($_GET['token'] ?? '');
$message  = '';
$type_msg = '';
$token_valide = false;
$user = null;

// ---- Vérifier le token ----
if ($token) {
    $stmt = $pdo->prepare('
        SELECT t.*, u.id AS user_id, u.prenom, u.email
        FROM tokens_reset t
        JOIN utilisateurs u ON u.id = t.utilisateur_id
        WHERE t.token = ?
          AND t.utilise = 0
          AND t.expire_at > NOW()
    ');
    $stmt->execute([$token]);
    $token_data = $stmt->fetch();

    if ($token_data) {
        $token_valide = true;
        $user = $token_data;
    } else {
        $message  = 'Ce lien est invalide ou a expiré. Veuillez faire une nouvelle demande.';
        $type_msg = 'error';
    }
}

// ---- Traiter le nouveau mot de passe ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valide) {
    $nouveau = $_POST['nouveau_mdp']  ?? '';
    $confirm = $_POST['confirmer_mdp'] ?? '';

    if (strlen($nouveau) < 8) {
        $message  = 'Le mot de passe doit contenir au moins 8 caractères.';
        $type_msg = 'error';
    } elseif ($nouveau !== $confirm) {
        $message  = 'Les mots de passe ne correspondent pas.';
        $type_msg = 'error';
    } else {
        // Mettre à jour le mot de passe
        $hash = password_hash($nouveau, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?')
            ->execute([$hash, $user['user_id']]);

        // Marquer le token comme utilisé
        $pdo->prepare('UPDATE tokens_reset SET utilise = 1 WHERE token = ?')
            ->execute([$token]);

        $message      = '✅ Mot de passe modifié avec succès ! Vous pouvez maintenant vous connecter.';
        $type_msg     = 'success';
        $token_valide = false; // Cacher le formulaire
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe – NovaStore</title>
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
            font-size: 1.6rem;
            color: #1D3557;
            margin-bottom: 8px;
        }
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
            font-size: 2.5rem;
            color: #10b981;
            background: #dcfce7;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .icon-box i.error {
            color: #ef4444;
            background: #fee2e2;
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

        /* Indicateur force mot de passe */
        .mdp-strength { margin-top: 6px; height: 4px; border-radius: 2px; background: #e9ecef; overflow: hidden; }
        .mdp-strength-bar { height: 100%; border-radius: 2px; transition: 0.3s; width: 0; }
        .mdp-hint { font-size: 0.78rem; color: #6c757d; margin-top: 4px; }
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

        <?php if (!$token): ?>
        <!-- Pas de token dans l'URL -->
        <div class="icon-box"><i class="error fas fa-times-circle"></i></div>
        <div class="auth-logo">Lien invalide</div>
        <p class="auth-subtitle">Aucun token fourni.</p>
        <div class="auth-link">
            <a href="forgot_password.php"><i class="fas fa-redo"></i> Nouvelle demande</a>
        </div>

        <?php elseif ($type_msg === 'success'): ?>
        <!-- Succès -->
        <div class="icon-box"><i class="fas fa-check-circle"></i></div>
        <div class="auth-logo">Mot de passe modifié !</div>
        <div class="alert alert-success" style="margin:20px 0;">
            <?= $message ?>
        </div>
        <a href="login.php" class="btn-submit" style="text-decoration:none; margin-top:8px;">
            <i class="fas fa-sign-in-alt"></i> Se connecter
        </a>

        <?php elseif (!$token_valide): ?>
        <!-- Token invalide ou expiré -->
        <div class="icon-box"><i class="error fas fa-clock"></i></div>
        <div class="auth-logo">Lien expiré</div>
        <div class="alert alert-error" style="margin:20px 0;">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
        <div class="auth-link">
            <a href="forgot_password.php"><i class="fas fa-redo"></i> Faire une nouvelle demande</a>
        </div>

        <?php else: ?>
        <!-- Formulaire nouveau mot de passe -->
        <div class="icon-box"><i class="fas fa-key" style="color:#E63946; background:#fce7f3;"></i></div>
        <div class="auth-logo">Nouveau mot de passe</div>
        <p class="auth-subtitle">
            Bonjour <strong><?= htmlspecialchars($user['prenom']) ?></strong>,
            choisissez un nouveau mot de passe.
        </p>

        <?php if ($message && $type_msg === 'error'): ?>
        <div class="alert alert-error" style="margin-bottom:20px;">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
                <label for="nouveau_mdp"><i class="fas fa-lock"></i> Nouveau mot de passe</label>
                <input type="password" id="nouveau_mdp" name="nouveau_mdp"
                       placeholder="Minimum 8 caractères" required
                       oninput="verifierForce(this.value)">
                <div class="mdp-strength"><div class="mdp-strength-bar" id="strength-bar"></div></div>
                <div class="mdp-hint" id="strength-hint">Entrez un mot de passe</div>
            </div>

            <div class="form-group">
                <label for="confirmer_mdp"><i class="fas fa-lock"></i> Confirmer le mot de passe</label>
                <input type="password" id="confirmer_mdp" name="confirmer_mdp"
                       placeholder="Répéter le mot de passe" required>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Enregistrer le nouveau mot de passe
            </button>
        </form>

        <div class="auth-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
function verifierForce(mdp) {
    const bar  = document.getElementById('strength-bar');
    const hint = document.getElementById('strength-hint');

    let score = 0;
    if (mdp.length >= 8)              score++;
    if (/[A-Z]/.test(mdp))           score++;
    if (/[0-9]/.test(mdp))           score++;
    if (/[^A-Za-z0-9]/.test(mdp))   score++;

    const niveaux = [
        { pct: '0%',   color: '#e9ecef', label: 'Entrez un mot de passe' },
        { pct: '25%',  color: '#ef4444', label: '😟 Très faible' },
        { pct: '50%',  color: '#f59e0b', label: '😐 Faible' },
        { pct: '75%',  color: '#3b82f6', label: '😊 Moyen' },
        { pct: '100%', color: '#10b981', label: '💪 Fort' },
    ];

    const n = niveaux[score] || niveaux[0];
    bar.style.width       = n.pct;
    bar.style.background  = n.color;
    hint.textContent      = n.label;
    hint.style.color      = n.color;
}
</script>

</body>
</html>