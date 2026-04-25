<?php
// ============================================================
//   NOVASTORE - client/profil.php
//   Profil du client connecté
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$message = '';
$type_msg = '';

// ---- Modifier les infos personnelles ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profil') {
        $nom      = trim($_POST['nom']);
        $prenom   = trim($_POST['prenom']);
        $tel      = trim($_POST['telephone']);

        if (empty($nom) || empty($prenom)) {
            $message = 'Le nom et prénom sont obligatoires.';
            $type_msg = 'error';
        } else {
            $pdo->prepare('UPDATE utilisateurs SET nom=?, prenom=?, telephone=? WHERE id=?')
                ->execute([$nom, $prenom, $tel, $_SESSION['user_id']]);
            $_SESSION['nom']    = $nom;
            $_SESSION['prenom'] = $prenom;
            $message  = 'Profil mis à jour avec succès !';
            $type_msg = 'success';
        }
    }

    if ($_POST['action'] === 'update_mdp') {
        $ancien  = $_POST['ancien_mdp'];
        $nouveau = $_POST['nouveau_mdp'];
        $confirm = $_POST['confirmer_mdp'];

        $stmt = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id=?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($ancien, $user['mot_de_passe'])) {
            $message = 'Ancien mot de passe incorrect.';
            $type_msg = 'error';
        } elseif (strlen($nouveau) < 8) {
            $message = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
            $type_msg = 'error';
        } elseif ($nouveau !== $confirm) {
            $message = 'Les mots de passe ne correspondent pas.';
            $type_msg = 'error';
        } else {
            $hash = password_hash($nouveau, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE utilisateurs SET mot_de_passe=? WHERE id=?')
                ->execute([$hash, $_SESSION['user_id']]);
            $message  = 'Mot de passe modifié avec succès !';
            $type_msg = 'success';
        }
    }

    if ($_POST['action'] === 'add_adresse') {
        $adresse    = trim($_POST['adresse']);
        $ville      = trim($_POST['ville']);
        $gouvernorat = trim($_POST['gouvernorat']);
        $code_postal = trim($_POST['code_postal']);
        $par_defaut  = isset($_POST['par_defaut']) ? 1 : 0;

        if ($par_defaut) {
            $pdo->prepare('UPDATE adresses SET par_defaut=0 WHERE utilisateur_id=?')
                ->execute([$_SESSION['user_id']]);
        }

        $pdo->prepare('INSERT INTO adresses (utilisateur_id, adresse, ville, gouvernorat, code_postal, par_defaut) VALUES (?,?,?,?,?,?)')
            ->execute([$_SESSION['user_id'], $adresse, $ville, $gouvernorat, $code_postal, $par_defaut]);
        $message  = 'Adresse ajoutée avec succès !';
        $type_msg = 'success';
    }
}

// ---- Supprimer une adresse ----
if (isset($_GET['supprimer_adresse']) && is_numeric($_GET['supprimer_adresse'])) {
    $pdo->prepare('DELETE FROM adresses WHERE id=? AND utilisateur_id=?')
        ->execute([$_GET['supprimer_adresse'], $_SESSION['user_id']]);
    $message  = 'Adresse supprimée.';
    $type_msg = 'success';
}

// ---- Récupérer les données ----
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id=?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$adresses = $pdo->prepare('SELECT * FROM adresses WHERE utilisateur_id=? ORDER BY par_defaut DESC');
$adresses->execute([$_SESSION['user_id']]);
$adresses = $adresses->fetchAll();

// Stats rapides
$nb_commandes = $pdo->prepare('SELECT COUNT(*) FROM commandes WHERE utilisateur_id=?');
$nb_commandes->execute([$_SESSION['user_id']]);
$nb_commandes = $nb_commandes->fetchColumn();

$total_depense = $pdo->prepare('SELECT COALESCE(SUM(total),0) FROM commandes WHERE utilisateur_id=? AND statut != "annulee"');
$total_depense->execute([$_SESSION['user_id']]);
$total_depense = $total_depense->fetchColumn();

$nb_wishlist = $pdo->prepare('SELECT COUNT(*) FROM wishlist WHERE utilisateur_id=?');
$nb_wishlist->execute([$_SESSION['user_id']]);
$nb_wishlist = $nb_wishlist->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil – NovaStore</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f1f5f9; }
        .client-page { max-width: 900px; margin: 40px auto; padding: 0 20px; }

        /* Header profil */
        .profil-header {
            background: linear-gradient(135deg, #1D3557, #2d4a6b);
            border-radius: 16px;
            padding: 32px;
            color: white;
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 32px;
        }
        .profil-avatar {
            width: 80px; height: 80px;
            background: #E63946;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 700; color: white;
            flex-shrink: 0;
        }
        .profil-header h2 { font-size: 1.5rem; margin-bottom: 4px; }
        .profil-header p  { color: rgba(255,255,255,0.7); font-size: 0.9rem; }

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        .stat-mini {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .stat-mini .val { font-size: 1.6rem; font-weight: 700; color: #1D3557; }
        .stat-mini .lbl { font-size: 0.85rem; color: #6c757d; margin-top: 4px; }

        /* Tabs */
        .tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
        .tab-btn {
            padding: 10px 20px; border-radius: 8px; border: 2px solid #e9ecef;
            background: white; cursor: pointer; font-family: 'DM Sans', sans-serif;
            font-weight: 600; font-size: 0.9rem; color: #374151; transition: 0.2s;
        }
        .tab-btn.active, .tab-btn:hover { border-color: #E63946; color: #E63946; background: #fce7f3; }

        /* Cards */
        .card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 28px; margin-bottom: 24px;
        }
        .card h3 { font-size: 1.1rem; color: #1D3557; margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px; }

        /* Formulaires */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.85rem; color: #374151; margin-bottom: 6px; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px 14px; border: 2px solid #e9ecef;
            border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 0.9rem;
            outline: none; transition: 0.2s;
        }
        .form-group input:focus { border-color: #E63946; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn-save {
            background: #E63946; color: white; border: none; padding: 11px 28px;
            border-radius: 8px; font-family: 'DM Sans', sans-serif; font-weight: 700;
            cursor: pointer; font-size: 0.95rem; transition: 0.2s;
        }
        .btn-save:hover { background: #c1121f; }

        /* Adresses */
        .adresse-card {
            border: 2px solid #e9ecef; border-radius: 10px; padding: 16px;
            margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-start;
        }
        .adresse-card.default { border-color: #E63946; background: #fef9f9; }
        .badge-default {
            background: #E63946; color: white; font-size: 0.75rem;
            padding: 2px 8px; border-radius: 20px; font-weight: 600;
        }
        .btn-delete-addr {
            background: #fee2e2; color: #ef4444; border: none; padding: 6px 12px;
            border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600;
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: 1fr; }
            .profil-header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<header class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="logo">Nova<strong>Store</strong></a>
        <nav class="nav-actions">
            <a href="commandes.php" class="btn-nav"><i class="fas fa-box"></i> Mes commandes</a>
            <a href="panier.php" class="btn-nav"><i class="fas fa-shopping-cart"></i> Panier</a>
            <a href="../auth/logout.php" class="btn-nav" style="color:#E63946;">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </nav>
    </div>
</header>

<div class="client-page">

    <?php if ($message): ?>
    <div class="alert alert-<?= $type_msg ?>" style="margin-bottom:20px;">
        <i class="fas fa-<?= $type_msg === 'success' ? 'check' : 'exclamation' ?>-circle"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- Header profil -->
    <div class="profil-header">
        <div class="profil-avatar">
            <?= strtoupper(substr($user['prenom'], 0, 1)) ?>
        </div>
        <div>
            <h2><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h2>
            <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
            <p><i class="fas fa-calendar"></i> Membre depuis <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="val"><?= $nb_commandes ?></div>
            <div class="lbl">Commandes</div>
        </div>
        <div class="stat-mini">
            <div class="val"><?= number_format($total_depense, 3) ?></div>
            <div class="lbl">DT dépensés</div>
        </div>
        <div class="stat-mini">
            <div class="val"><?= $nb_wishlist ?></div>
            <div class="lbl">Favoris</div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('infos')"><i class="fas fa-user"></i> Mes informations</button>
        <button class="tab-btn" onclick="showTab('mdp')"><i class="fas fa-lock"></i> Mot de passe</button>
        <button class="tab-btn" onclick="showTab('adresses')"><i class="fas fa-map-marker-alt"></i> Mes adresses</button>
    </div>

    <!-- Tab : Infos personnelles -->
    <div class="tab-content active" id="tab-infos">
        <div class="card">
            <h3><i class="fas fa-user" style="color:#E63946;"></i> Informations personnelles</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_profil">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Adresse e-mail</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:#f8f9fa; color:#6c757d;">
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>" placeholder="+216 XX XXX XXX">
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Enregistrer</button>
            </form>
        </div>
    </div>

    <!-- Tab : Mot de passe -->
    <div class="tab-content" id="tab-mdp">
        <div class="card">
            <h3><i class="fas fa-lock" style="color:#E63946;"></i> Changer le mot de passe</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_mdp">
                <div class="form-group">
                    <label>Ancien mot de passe</label>
                    <input type="password" name="ancien_mdp" required placeholder="Votre mot de passe actuel">
                </div>
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="nouveau_mdp" required placeholder="Minimum 8 caractères">
                </div>
                <div class="form-group">
                    <label>Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirmer_mdp" required placeholder="Répéter le mot de passe">
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-key"></i> Changer le mot de passe</button>
            </form>
        </div>
    </div>

    <!-- Tab : Adresses -->
    <div class="tab-content" id="tab-adresses">
        <div class="card">
            <h3><i class="fas fa-map-marker-alt" style="color:#E63946;"></i> Mes adresses de livraison</h3>

            <?php if (empty($adresses)): ?>
                <p style="color:#6c757d; text-align:center; padding:20px;">Aucune adresse enregistrée.</p>
            <?php else: ?>
                <?php foreach ($adresses as $addr): ?>
                <div class="adresse-card <?= $addr['par_defaut'] ? 'default' : '' ?>">
                    <div>
                        <?php if ($addr['par_defaut']): ?>
                        <span class="badge-default">Par défaut</span><br><br>
                        <?php endif; ?>
                        <strong><?= htmlspecialchars($addr['adresse']) ?></strong><br>
                        <span style="color:#6c757d;">
                            <?= htmlspecialchars($addr['ville']) ?>
                            <?= $addr['gouvernorat'] ? ', ' . htmlspecialchars($addr['gouvernorat']) : '' ?>
                            <?= $addr['code_postal'] ? ' ' . htmlspecialchars($addr['code_postal']) : '' ?>
                        </span>
                    </div>
                    <a href="?supprimer_adresse=<?= $addr['id'] ?>" class="btn-delete-addr"
                       onclick="return confirm('Supprimer cette adresse ?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <hr style="margin: 24px 0; border-color: #f1f5f9;">
            <h4 style="margin-bottom:16px; color:#1D3557;">Ajouter une adresse</h4>
            <form method="POST">
                <input type="hidden" name="action" value="add_adresse">
                <div class="form-group">
                    <label>Adresse complète</label>
                    <input type="text" name="adresse" placeholder="Rue, numéro, appartement..." required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ville</label>
                        <input type="text" name="ville" placeholder="Tunis" required>
                    </div>
                    <div class="form-group">
                        <label>Gouvernorat</label>
                        <input type="text" name="gouvernorat" placeholder="Ariana">
                    </div>
                </div>
                <div class="form-group">
                    <label>Code postal</label>
                    <input type="text" name="code_postal" placeholder="1000">
                </div>
                <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="par_defaut" id="par_defaut" style="width:auto;">
                    <label for="par_defaut" style="margin:0; cursor:pointer;">Définir comme adresse par défaut</label>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-plus"></i> Ajouter l'adresse</button>
            </form>
        </div>
    </div>

</div>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.classList.add('active');
}
</script>

</body>
</html>