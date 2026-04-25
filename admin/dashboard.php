<?php
// ============================================================
//   NOVASTORE - admin/dashboard.php
//   Tableau de bord administrateur
// ============================================================

session_start();

// Protection : admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

// --- Statistiques ---
$stats = [];

// Total clients
$stats['clients'] = $pdo->query('SELECT COUNT(*) FROM utilisateurs WHERE role = "client"')->fetchColumn();

// Total produits
$stats['produits'] = $pdo->query('SELECT COUNT(*) FROM produits WHERE actif = 1')->fetchColumn();

// Total commandes
$stats['commandes'] = $pdo->query('SELECT COUNT(*) FROM commandes')->fetchColumn();

// Chiffre d'affaires total
$stats['ca'] = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut != "annulee"')->fetchColumn();

// Commandes en attente
$stats['en_attente'] = $pdo->query('SELECT COUNT(*) FROM commandes WHERE statut = "en_attente"')->fetchColumn();

// Produits stock faible (< 5)
$stats['stock_faible'] = $pdo->query('SELECT COUNT(*) FROM produits WHERE stock < 5 AND actif = 1')->fetchColumn();

// --- Dernières commandes ---
$dernieres_commandes = $pdo->query('
    SELECT c.id, c.total, c.statut, c.created_at,
           u.nom, u.prenom, u.email
    FROM commandes c
    JOIN utilisateurs u ON u.id = c.utilisateur_id
    ORDER BY c.created_at DESC
    LIMIT 8
')->fetchAll();

// --- Produits stock faible ---
$produits_stock_faible = $pdo->query('
    SELECT p.id, p.nom, p.marque, p.stock, cat.nom AS categorie
    FROM produits p
    JOIN categories cat ON cat.id = p.categorie_id
    WHERE p.stock < 5 AND p.actif = 1
    ORDER BY p.stock ASC
    LIMIT 6
')->fetchAll();

// Statuts avec couleurs
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
    <title>Dashboard Admin – NovaStore</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f1f5f9; display: flex; min-height: 100vh; }

        /* ---- SIDEBAR ---- */
        .sidebar {
            width: 260px;
            background: #1D3557;
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            transition: 0.3s;
        }
        .sidebar-logo {
            padding: 28px 24px;
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-logo strong { color: #E63946; }
        .sidebar-logo span { display: block; font-size: 0.75rem; color: rgba(255,255,255,0.5); font-family: 'DM Sans', sans-serif; margin-top: 4px; }
        .sidebar-nav { padding: 20px 0; flex: 1; overflow-y: auto; }
        .nav-section-title {
            padding: 8px 24px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.4);
            margin-top: 12px;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 0.95rem;
            transition: 0.2s;
            border-left: 3px solid transparent;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: rgba(255,255,255,0.08);
            color: white;
            border-left-color: #E63946;
        }
        .sidebar-nav a i { width: 20px; text-align: center; }
        .sidebar-footer {
            padding: 20px 24px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
        }
        .sidebar-footer a {
            color: #E63946;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        /* ---- MAIN ---- */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ---- TOP BAR ---- */
        .topbar {
            background: white;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar h1 { font-size: 1.3rem; color: #1D3557; }
        .topbar-user { display: flex; align-items: center; gap: 12px; }
        .topbar-user .avatar {
            width: 38px; height: 38px;
            background: #E63946;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 1rem;
        }

        /* ---- PAGE CONTENT ---- */
        .page { padding: 32px; }

        /* ---- STAT CARDS ---- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .stat-info { flex: 1; }
        .stat-value { font-size: 1.8rem; font-weight: 700; color: #1D3557; line-height: 1; }
        .stat-label { font-size: 0.85rem; color: #6c757d; margin-top: 4px; }
        .stat-badge {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* ---- SECTIONS ---- */
        .section-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header h3 { font-size: 1rem; color: #1D3557; font-weight: 700; }
        .card-header a { font-size: 0.85rem; color: #E63946; text-decoration: none; }

        /* ---- TABLE ---- */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th {
            background: #f8f9fa;
            padding: 12px 16px;
            text-align: left;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            font-weight: 600;
        }
        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #374151;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #f8f9fa; }

        /* ---- STATUS BADGE ---- */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            display: inline-block;
        }

        /* ---- STOCK LIST ---- */
        .stock-list { padding: 0; }
        .stock-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 24px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }
        .stock-item:last-child { border-bottom: none; }
        .stock-item-name { color: #374151; font-weight: 500; }
        .stock-item-brand { color: #6c757d; font-size: 0.8rem; }
        .stock-qty {
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .stock-zero { background: #fee2e2; color: #ef4444; }
        .stock-low  { background: #fef3c7; color: #f59e0b; }

        @media (max-width: 1024px) {
            .section-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { width: 0; overflow: hidden; }
            .main-content { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar">
    <div class="sidebar-logo">
        Nova<strong>Store</strong>
        <span>Espace Administrateur</span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">Principal</div>
        <a href="dashboard.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="commandes.php"><i class="fas fa-box"></i> Commandes
            <?php if ($stats['en_attente'] > 0): ?>
                <span style="margin-left:auto; background:#E63946; color:white; border-radius:20px; padding:2px 8px; font-size:0.75rem;">
                    <?= $stats['en_attente'] ?>
                </span>
            <?php endif; ?>
        </a>

        <div class="nav-section-title">Catalogue</div>
        <a href="produits.php"><i class="fas fa-tags"></i> Produits</a>
        <a href="#"><i class="fas fa-list"></i> Catégories</a>

        <div class="nav-section-title">Utilisateurs</div>
        <a href="utilisateurs.php"><i class="fas fa-users"></i> Clients</a>

        <div class="nav-section-title">Paramètres</div>
        <a href="../index.php" target="_blank"><i class="fas fa-store"></i> Voir le site</a>
    </nav>
    <div class="sidebar-footer">
        <div><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></div>
        <div style="font-size:0.8rem; color:rgba(255,255,255,0.4);"><?= htmlspecialchars($_SESSION['email']) ?></div>
        <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</aside>

<!-- ===== MAIN ===== -->
<div class="main-content">

    <!-- Top bar -->
    <div class="topbar">
        <h1><i class="fas fa-chart-pie" style="color:#E63946; margin-right:8px;"></i> Tableau de bord</h1>
        <div class="topbar-user">
            <span style="color:#6c757d; font-size:0.9rem;"><?= date('d/m/Y') ?></span>
            <div class="avatar"><?= strtoupper(substr($_SESSION['prenom'], 0, 1)) ?></div>
        </div>
    </div>

    <div class="page">

        <!-- ===== STAT CARDS ===== -->
        <div class="stats-grid">

            <div class="stat-card">
                <div class="stat-icon" style="background:#dbeafe;">
                    <i class="fas fa-users" style="color:#3b82f6;"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['clients'] ?></div>
                    <div class="stat-label">Clients inscrits</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:#dcfce7;">
                    <i class="fas fa-tags" style="color:#10b981;"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['produits'] ?></div>
                    <div class="stat-label">Produits actifs</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:#fef3c7;">
                    <i class="fas fa-box" style="color:#f59e0b;"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= $stats['commandes'] ?></div>
                    <div class="stat-label">Commandes totales</div>
                </div>
                <?php if ($stats['en_attente'] > 0): ?>
                <span class="stat-badge" style="background:#fef3c7; color:#f59e0b;">
                    <?= $stats['en_attente'] ?> en attente
                </span>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background:#fce7f3;">
                    <i class="fas fa-money-bill-wave" style="color:#E63946;"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?= number_format($stats['ca'], 3, '.', ' ') ?></div>
                    <div class="stat-label">Chiffre d'affaires (DT)</div>
                </div>
            </div>

            <?php if ($stats['stock_faible'] > 0): ?>
            <div class="stat-card" style="border: 2px solid #fef3c7;">
                <div class="stat-icon" style="background:#fef3c7;">
                    <i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value" style="color:#f59e0b;"><?= $stats['stock_faible'] ?></div>
                    <div class="stat-label">Produits stock faible</div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- ===== SECTION GRID ===== -->
        <div class="section-grid">

            <!-- Dernières commandes -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-box" style="color:#E63946; margin-right:8px;"></i>Dernières commandes</h3>
                    <a href="commandes.php">Voir tout →</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($dernieres_commandes)): ?>
                        <tr><td colspan="5" style="text-align:center; color:#6c757d; padding:30px;">Aucune commande pour l'instant</td></tr>
                    <?php else: ?>
                        <?php foreach ($dernieres_commandes as $cmd): ?>
                        <?php $s = $statut_labels[$cmd['statut']] ?? ['label'=>$cmd['statut'],'color'=>'#999']; ?>
                        <tr>
                            <td style="font-weight:700; color:#E63946;">#<?= $cmd['id'] ?></td>
                            <td>
                                <div style="font-weight:500;"><?= htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']) ?></div>
                                <div style="font-size:0.8rem; color:#6c757d;"><?= htmlspecialchars($cmd['email']) ?></div>
                            </td>
                            <td style="font-weight:700;"><?= number_format($cmd['total'], 3) ?> DT</td>
                            <td>
                                <span class="status-badge" style="background:<?= $s['color'] ?>22; color:<?= $s['color'] ?>;">
                                    <?= $s['label'] ?>
                                </span>
                            </td>
                            <td style="color:#6c757d;"><?= date('d/m/Y', strtotime($cmd['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Stock faible -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-exclamation-triangle" style="color:#f59e0b; margin-right:8px;"></i>Stock faible</h3>
                    <a href="produits.php">Gérer →</a>
                </div>
                <div class="stock-list">
                <?php if (empty($produits_stock_faible)): ?>
                    <div style="padding:30px; text-align:center; color:#10b981;">
                        <i class="fas fa-check-circle" style="font-size:2rem; margin-bottom:8px; display:block;"></i>
                        Tous les stocks sont OK !
                    </div>
                <?php else: ?>
                    <?php foreach ($produits_stock_faible as $p): ?>
                    <div class="stock-item">
                        <div>
                            <div class="stock-item-name"><?= htmlspecialchars($p['nom']) ?></div>
                            <div class="stock-item-brand"><?= htmlspecialchars($p['marque']) ?> · <?= htmlspecialchars($p['categorie']) ?></div>
                        </div>
                        <span class="stock-qty <?= $p['stock'] == 0 ? 'stock-zero' : 'stock-low' ?>">
                            <?= $p['stock'] == 0 ? 'Épuisé' : $p['stock'] . ' restants' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>