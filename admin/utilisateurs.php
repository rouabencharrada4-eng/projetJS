<?php
// ============================================================
//   NOVASTORE - admin/utilisateurs.php
//   Gestion des clients
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$message = '';

// ---- Activer / Désactiver un compte ----
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $stmt = $pdo->prepare('SELECT actif FROM utilisateurs WHERE id = ? AND role = "client"');
    $stmt->execute([$_GET['toggle']]);
    $u = $stmt->fetch();
    if ($u) {
        $nouveau = $u['actif'] ? 0 : 1;
        $pdo->prepare('UPDATE utilisateurs SET actif = ? WHERE id = ?')->execute([$nouveau, $_GET['toggle']]);
        $message = $nouveau ? 'Compte activé.' : 'Compte désactivé.';
    }
}

// ---- Recherche ----
$search = trim($_GET['search'] ?? '');
$sql = '
    SELECT u.*,
           COUNT(DISTINCT c.id)  AS nb_commandes,
           COALESCE(SUM(c.total), 0) AS total_depense
    FROM utilisateurs u
    LEFT JOIN commandes c ON c.utilisateur_id = u.id AND c.statut != "annulee"
    WHERE u.role = "client"
';
$params = [];
if ($search) {
    $sql .= ' AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= ' GROUP BY u.id ORDER BY u.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients – Admin NovaStore</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'DM Sans',sans-serif; background:#f1f5f9; display:flex; min-height:100vh; }
        .sidebar { width:260px; background:#1D3557; color:white; display:flex; flex-direction:column; position:fixed; top:0; left:0; height:100vh; z-index:100; }
        .sidebar-logo { padding:28px 24px; font-family:'Playfair Display',serif; font-size:1.5rem; border-bottom:1px solid rgba(255,255,255,0.1); }
        .sidebar-logo strong { color:#E63946; }
        .sidebar-logo span { display:block; font-size:0.75rem; color:rgba(255,255,255,0.5); font-family:'DM Sans',sans-serif; margin-top:4px; }
        .sidebar-nav { padding:20px 0; flex:1; }
        .nav-section-title { padding:8px 24px; font-size:0.7rem; text-transform:uppercase; letter-spacing:1px; color:rgba(255,255,255,0.4); margin-top:12px; }
        .sidebar-nav a { display:flex; align-items:center; gap:12px; padding:12px 24px; color:rgba(255,255,255,0.75); text-decoration:none; font-size:0.95rem; transition:0.2s; border-left:3px solid transparent; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background:rgba(255,255,255,0.08); color:white; border-left-color:#E63946; }
        .sidebar-nav a i { width:20px; text-align:center; }
        .sidebar-footer { padding:20px 24px; border-top:1px solid rgba(255,255,255,0.1); font-size:0.85rem; color:rgba(255,255,255,0.6); }
        .sidebar-footer a { color:#E63946; text-decoration:none; display:flex; align-items:center; gap:8px; margin-top:8px; }
        .main-content { margin-left:260px; flex:1; }
        .topbar { background:white; padding:16px 32px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 4px rgba(0,0,0,0.06); position:sticky; top:0; z-index:50; }
        .topbar h1 { font-size:1.3rem; color:#1D3557; }
        .page { padding:32px; }

        .filters-bar { background:white; border-radius:12px; padding:20px 24px; display:flex; gap:12px; align-items:center; margin-bottom:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .filters-bar input { flex:1; padding:10px 16px; border:2px solid #e9ecef; border-radius:8px; font-family:'DM Sans',sans-serif; font-size:0.9rem; outline:none; }
        .filters-bar input:focus { border-color:#E63946; }
        .btn-filter { background:#1D3557; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-family:'DM Sans',sans-serif; font-weight:600; }

        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; }
        .data-table { width:100%; border-collapse:collapse; }
        .data-table th { background:#f8f9fa; padding:12px 16px; text-align:left; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.5px; color:#6c757d; font-weight:600; }
        .data-table td { padding:14px 16px; border-bottom:1px solid #f1f5f9; font-size:0.9rem; color:#374151; }
        .data-table tr:last-child td { border-bottom:none; }
        .data-table tr:hover td { background:#f8f9fa; }
        .avatar-sm { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:white; font-size:0.9rem; flex-shrink:0; }
        .btn-action { padding:6px 12px; border-radius:6px; border:none; cursor:pointer; font-size:0.8rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
        .btn-disable { background:#fee2e2; color:#ef4444; }
        .btn-enable  { background:#dcfce7; color:#10b981; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">Nova<strong>Store</strong><span>Espace Administrateur</span></div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">Principal</div>
        <a href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="commandes.php"><i class="fas fa-box"></i> Commandes</a>
        <div class="nav-section-title">Catalogue</div>
        <a href="produits.php"><i class="fas fa-tags"></i> Produits</a>
        <a href="#"><i class="fas fa-list"></i> Catégories</a>
        <div class="nav-section-title">Utilisateurs</div>
        <a href="utilisateurs.php" class="active"><i class="fas fa-users"></i> Clients</a>
        <div class="nav-section-title">Paramètres</div>
        <a href="../index.php" target="_blank"><i class="fas fa-store"></i> Voir le site</a>
    </nav>
    <div class="sidebar-footer">
        <div><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></div>
        <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</aside>

<div class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-users" style="color:#E63946; margin-right:8px;"></i> Gestion des clients</h1>
        <span style="color:#6c757d; font-size:0.9rem;"><?= count($clients) ?> client(s)</span>
    </div>

    <div class="page">

        <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <form method="GET" class="filters-bar">
            <input type="text" name="search" placeholder="🔍 Rechercher par nom ou e-mail..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-filter">Chercher</button>
        </form>

        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Téléphone</th>
                        <th>Commandes</th>
                        <th>Total dépensé</th>
                        <th>Inscrit le</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($clients)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:40px; color:#6c757d;">Aucun client trouvé</td></tr>
                <?php else: ?>
                    <?php
                    $colors = ['#E63946','#3b82f6','#10b981','#f59e0b','#8b5cf6','#06b6d4'];
                    $i = 0;
                    foreach ($clients as $u):
                        $color = $colors[$i++ % count($colors)];
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div class="avatar-sm" style="background:<?= $color ?>;">
                                    <?= strtoupper(substr($u['prenom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></div>
                                    <div style="font-size:0.8rem; color:#6c757d;"><?= htmlspecialchars($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:#6c757d;"><?= htmlspecialchars($u['telephone'] ?? '—') ?></td>
                        <td style="text-align:center; font-weight:700;"><?= $u['nb_commandes'] ?></td>
                        <td style="font-weight:700; color:#1D3557;"><?= number_format($u['total_depense'], 3) ?> DT</td>
                        <td style="color:#6c757d;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?php if ($u['actif']): ?>
                            <span style="background:#dcfce7; color:#10b981; padding:4px 10px; border-radius:20px; font-size:0.8rem; font-weight:600;">Actif</span>
                            <?php else: ?>
                            <span style="background:#fee2e2; color:#ef4444; padding:4px 10px; border-radius:20px; font-size:0.8rem; font-weight:600;">Désactivé</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?toggle=<?= $u['id'] ?>&search=<?= urlencode($search) ?>"
                               class="btn-action <?= $u['actif'] ? 'btn-disable' : 'btn-enable' ?>"
                               onclick="return confirm('<?= $u['actif'] ? 'Désactiver' : 'Activer' ?> ce compte ?')">
                                <i class="fas fa-<?= $u['actif'] ? 'ban' : 'check' ?>"></i>
                                <?= $u['actif'] ? 'Désactiver' : 'Activer' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>