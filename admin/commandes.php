<?php
// ============================================================
//   NOVASTORE - admin/commandes.php
//   Gestion des commandes
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$message = '';

// ---- Changer le statut d'une commande ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commande_id'], $_POST['statut'])) {
    $statuts_valides = ['en_attente','confirmee','en_preparation','expediee','livree','annulee'];
    if (in_array($_POST['statut'], $statuts_valides)) {
        $pdo->prepare('UPDATE commandes SET statut = ? WHERE id = ?')
            ->execute([$_POST['statut'], $_POST['commande_id']]);
        $message = 'Statut mis à jour avec succès.';
    }
}

// ---- Filtres ----
$filtre_statut = $_GET['statut'] ?? '';
$search        = trim($_GET['search'] ?? '');

$sql = '
    SELECT c.*, u.nom, u.prenom, u.email, u.telephone,
           COUNT(lc.id) AS nb_articles
    FROM commandes c
    JOIN utilisateurs u ON u.id = c.utilisateur_id
    LEFT JOIN lignes_commande lc ON lc.commande_id = c.id
    WHERE 1=1
';
$params = [];

if ($filtre_statut) {
    $sql .= ' AND c.statut = ?';
    $params[] = $filtre_statut;
}
if ($search) {
    $sql .= ' AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR c.id = ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = $search;
}
$sql .= ' GROUP BY c.id ORDER BY c.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

$statut_labels = [
    'en_attente'     => ['label' => 'En attente',      'color' => '#f59e0b'],
    'confirmee'      => ['label' => 'Confirmée',        'color' => '#3b82f6'],
    'en_preparation' => ['label' => 'En préparation',   'color' => '#8b5cf6'],
    'expediee'       => ['label' => 'Expédiée',         'color' => '#06b6d4'],
    'livree'         => ['label' => 'Livrée',           'color' => '#10b981'],
    'annulee'        => ['label' => 'Annulée',          'color' => '#ef4444'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes – Admin NovaStore</title>
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

        .filters-bar { background:white; border-radius:12px; padding:20px 24px; display:flex; gap:12px; align-items:center; margin-bottom:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06); flex-wrap:wrap; }
        .filters-bar input, .filters-bar select { padding:10px 16px; border:2px solid #e9ecef; border-radius:8px; font-family:'DM Sans',sans-serif; font-size:0.9rem; outline:none; transition:0.2s; }
        .filters-bar input:focus, .filters-bar select:focus { border-color:#E63946; }
        .filters-bar input { flex:1; min-width:200px; }
        .btn-filter { background:#1D3557; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-family:'DM Sans',sans-serif; font-weight:600; }

        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; }
        .data-table { width:100%; border-collapse:collapse; }
        .data-table th { background:#f8f9fa; padding:12px 16px; text-align:left; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.5px; color:#6c757d; font-weight:600; }
        .data-table td { padding:14px 16px; border-bottom:1px solid #f1f5f9; font-size:0.9rem; color:#374151; vertical-align:middle; }
        .data-table tr:last-child td { border-bottom:none; }
        .data-table tr:hover td { background:#f8f9fa; }

        .status-badge { padding:4px 10px; border-radius:20px; font-size:0.78rem; font-weight:600; display:inline-block; }
        .status-select { padding:6px 10px; border-radius:8px; border:2px solid #e9ecef; font-family:'DM Sans',sans-serif; font-size:0.82rem; cursor:pointer; background:white; }
        .btn-save-status { background:#10b981; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:0.82rem; font-weight:600; }
        .btn-save-status:hover { background:#059669; }

        .tab-filters { display:flex; gap:8px; flex-wrap:wrap; }
        .tab-btn { padding:8px 16px; border-radius:20px; border:2px solid #e9ecef; background:white; cursor:pointer; font-family:'DM Sans',sans-serif; font-size:0.85rem; font-weight:600; text-decoration:none; color:#374151; transition:0.2s; }
        .tab-btn:hover, .tab-btn.active { border-color:#E63946; color:#E63946; background:#fce7f3; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">Nova<strong>Store</strong><span>Espace Administrateur</span></div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">Principal</div>
        <a href="dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="commandes.php" class="active"><i class="fas fa-box"></i> Commandes</a>
        <div class="nav-section-title">Catalogue</div>
        <a href="produits.php"><i class="fas fa-tags"></i> Produits</a>
        <a href="#"><i class="fas fa-list"></i> Catégories</a>
        <div class="nav-section-title">Utilisateurs</div>
        <a href="utilisateurs.php"><i class="fas fa-users"></i> Clients</a>
        <div class="nav-section-title">Paramètres</div>
        <a href="../index.html" target="_blank"><i class="fas fa-store"></i> Voir le site</a>
    </nav>
    <div class="sidebar-footer">
        <div><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></div>
        <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</aside>

<div class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-box" style="color:#E63946; margin-right:8px;"></i> Gestion des commandes</h1>
        <span style="color:#6c757d; font-size:0.9rem;"><?= count($commandes) ?> commande(s)</span>
    </div>

    <div class="page">

        <?php if ($message): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Filtres par statut -->
        <div class="filters-bar">
            <div class="tab-filters">
                <a href="commandes.php" class="tab-btn <?= !$filtre_statut ? 'active' : '' ?>">Toutes</a>
                <?php foreach ($statut_labels as $key => $s): ?>
                <a href="?statut=<?= $key ?>" class="tab-btn <?= $filtre_statut === $key ? 'active' : '' ?>"><?= $s['label'] ?></a>
                <?php endforeach; ?>
            </div>
            <form method="GET" style="display:flex; gap:8px; margin-left:auto;">
                <input type="hidden" name="statut" value="<?= htmlspecialchars($filtre_statut) ?>">
                <input type="text" name="search" placeholder="🔍 N° commande, client..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-filter">Chercher</button>
            </form>
        </div>

        <!-- Table commandes -->
        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Articles</th>
                        <th>Total</th>
                        <th>Paiement</th>
                        <th>Statut actuel</th>
                        <th>Changer statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($commandes)): ?>
                    <tr><td colspan="8" style="text-align:center; padding:40px; color:#6c757d;">Aucune commande trouvée</td></tr>
                <?php else: ?>
                    <?php foreach ($commandes as $cmd): ?>
                    <?php $s = $statut_labels[$cmd['statut']]; ?>
                    <tr>
                        <td style="font-weight:700; color:#E63946;">#<?= $cmd['id'] ?></td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']) ?></div>
                            <div style="font-size:0.8rem; color:#6c757d;"><?= htmlspecialchars($cmd['email']) ?></div>
                            <?php if ($cmd['telephone']): ?>
                            <div style="font-size:0.8rem; color:#6c757d;"><?= htmlspecialchars($cmd['telephone']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center; font-weight:600;"><?= $cmd['nb_articles'] ?></td>
                        <td style="font-weight:700; color:#1D3557;"><?= number_format($cmd['total'], 3) ?> DT</td>
                        <td style="text-transform:capitalize;"><?= htmlspecialchars($cmd['mode_paiement']) ?></td>
                        <td>
                            <span class="status-badge" style="background:<?= $s['color'] ?>22; color:<?= $s['color'] ?>;">
                                <?= $s['label'] ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:flex; gap:6px; align-items:center;">
                                <input type="hidden" name="commande_id" value="<?= $cmd['id'] ?>">
                                <select name="statut" class="status-select">
                                    <?php foreach ($statut_labels as $key => $sl): ?>
                                    <option value="<?= $key ?>" <?= $cmd['statut'] === $key ? 'selected' : '' ?>>
                                        <?= $sl['label'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-save-status"><i class="fas fa-check"></i></button>
                            </form>
                        </td>
                        <td style="color:#6c757d; white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($cmd['created_at'])) ?></td>
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