<?php
// ============================================================
//   NOVASTORE - client/commandes.php
//   Historique des commandes du client
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

// ---- Annuler une commande ----
$message  = '';
$type_msg = '';

if (isset($_GET['annuler']) && is_numeric($_GET['annuler'])) {
    // Vérifier que la commande appartient au client et est encore annulable
    $stmt = $pdo->prepare('SELECT * FROM commandes WHERE id=? AND utilisateur_id=?');
    $stmt->execute([$_GET['annuler'], $_SESSION['user_id']]);
    $cmd = $stmt->fetch();

    if ($cmd && in_array($cmd['statut'], ['en_attente', 'confirmee'])) {
        // Remettre les stocks
        $lignes = $pdo->prepare('SELECT * FROM lignes_commande WHERE commande_id=?');
        $lignes->execute([$cmd['id']]);
        foreach ($lignes->fetchAll() as $ligne) {
            $pdo->prepare('UPDATE produits SET stock = stock + ? WHERE id = ?')
                ->execute([$ligne['quantite'], $ligne['produit_id']]);
        }
        $pdo->prepare('UPDATE commandes SET statut="annulee" WHERE id=?')
            ->execute([$cmd['id']]);
        $message  = 'Commande #' . $cmd['id'] . ' annulée avec succès.';
        $type_msg = 'success';
    } else {
        $message  = 'Cette commande ne peut plus être annulée.';
        $type_msg = 'error';
    }
}

// ---- Filtre statut ----
$filtre = $_GET['statut'] ?? '';

$sql = '
    SELECT c.*,
           COUNT(lc.id) AS nb_articles,
           a.adresse, a.ville, a.gouvernorat
    FROM commandes c
    LEFT JOIN lignes_commande lc ON lc.commande_id = c.id
    LEFT JOIN adresses a ON a.id = c.adresse_id
    WHERE c.utilisateur_id = ?
';
$params = [$_SESSION['user_id']];

if ($filtre) {
    $sql .= ' AND c.statut = ?';
    $params[] = $filtre;
}
$sql .= ' GROUP BY c.id ORDER BY c.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

// Détail d'une commande
$commande_detail = null;
$lignes_detail   = [];

if (isset($_GET['detail']) && is_numeric($_GET['detail'])) {
    $stmt = $pdo->prepare('SELECT * FROM commandes WHERE id=? AND utilisateur_id=?');
    $stmt->execute([$_GET['detail'], $_SESSION['user_id']]);
    $commande_detail = $stmt->fetch();

    if ($commande_detail) {
        $stmt = $pdo->prepare('
            SELECT lc.*, p.nom, p.marque, p.image, p.modele
            FROM lignes_commande lc
            JOIN produits p ON p.id = lc.produit_id
            WHERE lc.commande_id = ?
        ');
        $stmt->execute([$_GET['detail']]);
        $lignes_detail = $stmt->fetchAll();
    }
}

$statut_labels = [
    'en_attente'     => ['label' => 'En attente',      'color' => '#f59e0b', 'icon' => 'clock'],
    'confirmee'      => ['label' => 'Confirmée',        'color' => '#3b82f6', 'icon' => 'check'],
    'en_preparation' => ['label' => 'En préparation',   'color' => '#8b5cf6', 'icon' => 'box-open'],
    'expediee'       => ['label' => 'Expédiée',         'color' => '#06b6d4', 'icon' => 'shipping-fast'],
    'livree'         => ['label' => 'Livrée',           'color' => '#10b981', 'icon' => 'check-circle'],
    'annulee'        => ['label' => 'Annulée',          'color' => '#ef4444', 'icon' => 'times-circle'],
];

// Étapes du suivi
$etapes = ['en_attente', 'confirmee', 'en_preparation', 'expediee', 'livree'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Commandes – NovaStore</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f1f5f9; }
        .page { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.8rem; color: #1D3557; margin-bottom: 28px; }

        /* Filtres */
        .filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
        .filter-btn {
            padding: 8px 16px; border-radius: 20px; border: 2px solid #e9ecef;
            background: white; cursor: pointer; font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem; font-weight: 600; text-decoration: none;
            color: #374151; transition: 0.2s;
        }
        .filter-btn:hover, .filter-btn.active { border-color: #E63946; color: #E63946; background: #fce7f3; }

        /* Card commande */
        .commande-card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 16px; overflow: hidden;
            border-left: 4px solid #e9ecef; transition: 0.2s;
        }
        .commande-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
        .commande-header {
            padding: 20px 24px;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px;
        }
        .commande-id { font-size: 1.1rem; font-weight: 700; color: #1D3557; }
        .commande-date { font-size: 0.85rem; color: #6c757d; }
        .commande-total { font-size: 1.2rem; font-weight: 700; color: #007bff; }
        .status-badge {
            padding: 6px 14px; border-radius: 20px;
            font-size: 0.82rem; font-weight: 700; display: inline-flex;
            align-items: center; gap: 6px;
        }
        .commande-footer {
            padding: 12px 24px; background: #f8f9fa;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 8px;
        }
        .commande-info { font-size: 0.85rem; color: #6c757d; }

        /* Boutons */
        .btn-detail {
            padding: 7px 16px; border-radius: 8px; border: 2px solid #1D3557;
            color: #1D3557; background: white; cursor: pointer; font-family: 'DM Sans', sans-serif;
            font-weight: 600; font-size: 0.85rem; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;
        }
        .btn-detail:hover { background: #1D3557; color: white; }
        .btn-annuler {
            padding: 7px 16px; border-radius: 8px; border: 2px solid #ef4444;
            color: #ef4444; background: white; cursor: pointer; font-family: 'DM Sans', sans-serif;
            font-weight: 600; font-size: 0.85rem; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;
        }
        .btn-annuler:hover { background: #ef4444; color: white; }

        /* Suivi commande */
        .suivi {
            padding: 20px 24px;
            border-top: 1px solid #f1f5f9;
        }
        .suivi-steps {
            display: flex; align-items: center; justify-content: space-between;
            position: relative; margin: 16px 0;
        }
        .suivi-steps::before {
            content: ''; position: absolute; top: 20px; left: 0; right: 0;
            height: 3px; background: #e9ecef; z-index: 0;
        }
        .suivi-step {
            display: flex; flex-direction: column; align-items: center;
            gap: 8px; position: relative; z-index: 1; flex: 1;
        }
        .step-circle {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; transition: 0.3s; border: 3px solid #e9ecef;
            background: white;
        }
        .step-circle.done  { background: #10b981; border-color: #10b981; color: white; }
        .step-circle.current { background: #E63946; border-color: #E63946; color: white; }
        .step-label { font-size: 0.75rem; color: #6c757d; text-align: center; font-weight: 600; }
        .step-label.done { color: #10b981; }
        .step-label.current { color: #E63946; }

        /* Modal détail */
        .modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; align-items: center; justify-content: center; padding: 20px; }
        .modal-bg.open { display: flex; }
        .modal-box {
            background: white; border-radius: 16px; width: 100%;
            max-width: 650px; max-height: 90vh; overflow-y: auto;
        }
        .modal-header {
            padding: 24px 28px; border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; background: white; z-index: 1;
        }
        .modal-header h3 { font-size: 1.2rem; color: #1D3557; }
        .modal-close {
            background: #f1f5f9; border: none; width: 36px; height: 36px;
            border-radius: 50%; cursor: pointer; font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
        }
        .modal-body { padding: 24px 28px; }

        /* Lignes commande dans modal */
        .ligne-row {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 0; border-bottom: 1px solid #f1f5f9;
        }
        .ligne-row:last-child { border-bottom: none; }
        .ligne-img { width: 60px; height: 60px; object-fit: contain; border-radius: 8px; background: #f8f9fa; flex-shrink: 0; }
        .ligne-info { flex: 1; }
        .ligne-name { font-weight: 700; color: #1D3557; font-size: 0.95rem; }
        .ligne-brand { font-size: 0.8rem; color: #E63946; font-weight: 600; }
        .ligne-prix { font-weight: 700; color: #007bff; white-space: nowrap; }
        .ligne-qty { font-size: 0.8rem; color: #6c757d; }

        /* Vide */
        .vide { text-align: center; padding: 60px 20px; color: #6c757d; }
        .vide i { font-size: 4rem; color: #dee2e6; margin-bottom: 16px; display: block; }

        @media (max-width: 600px) {
            .commande-header { flex-direction: column; align-items: flex-start; }
            .suivi-steps { gap: 4px; }
            .step-label { font-size: 0.65rem; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<header class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="logo">Nova<strong>Store</strong></a>
        <nav class="nav-actions">
            <a href="profil.php" class="btn-nav"><i class="fas fa-user"></i> Mon profil</a>
            <a href="panier.php" class="btn-nav"><i class="fas fa-shopping-cart"></i> Panier</a>
            <a href="../auth/logout.php" class="btn-nav" style="color:#E63946;">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </nav>
    </div>
</header>

<div class="page">

    <?php if ($message): ?>
    <div class="alert alert-<?= $type_msg ?>" style="margin-bottom:20px;">
        <i class="fas fa-<?= $type_msg === 'success' ? 'check' : 'exclamation' ?>-circle"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <h1 class="page-title">
        <i class="fas fa-box" style="color:#E63946;"></i> Mes commandes
    </h1>

    <!-- Filtres -->
    <div class="filters">
        <a href="commandes.php" class="filter-btn <?= !$filtre ? 'active' : '' ?>">Toutes (<?= count($commandes) ?>)</a>
        <?php foreach ($statut_labels as $key => $s): ?>
        <a href="?statut=<?= $key ?>" class="filter-btn <?= $filtre === $key ? 'active' : '' ?>">
            <?= $s['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($commandes)): ?>
    <div class="vide">
        <i class="fas fa-box-open"></i>
        <h3>Aucune commande trouvée</h3>
        <p>Vous n'avez pas encore passé de commande.</p>
        <a href="../index.php" style="display:inline-flex; align-items:center; gap:8px; margin-top:20px; background:#1D3557; color:white; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:600;">
            <i class="fas fa-store"></i> Faire mes courses
        </a>
    </div>

    <?php else: ?>

    <?php foreach ($commandes as $cmd):
        $s = $statut_labels[$cmd['statut']];
        $etape_actuelle = array_search($cmd['statut'], $etapes);
    ?>
    <div class="commande-card" style="border-left-color: <?= $s['color'] ?>;">

        <!-- Header -->
        <div class="commande-header">
            <div>
                <div class="commande-id">
                    <i class="fas fa-<?= $s['icon'] ?>" style="color:<?= $s['color'] ?>;"></i>
                    Commande #<?= $cmd['id'] ?>
                </div>
                <div class="commande-date">
                    <i class="fas fa-calendar"></i>
                    <?= date('d/m/Y à H:i', strtotime($cmd['created_at'])) ?>
                </div>
            </div>

            <span class="status-badge" style="background:<?= $s['color'] ?>22; color:<?= $s['color'] ?>;">
                <i class="fas fa-<?= $s['icon'] ?>"></i> <?= $s['label'] ?>
            </span>

            <div class="commande-total"><?= number_format($cmd['total'], 3) ?> DT</div>
        </div>

        <!-- Suivi (sauf annulée) -->
        <?php if ($cmd['statut'] !== 'annulee'): ?>
        <div class="suivi">
            <div class="suivi-steps">
                <?php foreach ($etapes as $i => $etape):
                    $done    = $etape_actuelle !== false && $i < $etape_actuelle;
                    $current = $etape_actuelle !== false && $i === $etape_actuelle;
                    $icons   = ['clock', 'check', 'box-open', 'shipping-fast', 'check-circle'];
                ?>
                <div class="suivi-step">
                    <div class="step-circle <?= $done ? 'done' : ($current ? 'current' : '') ?>">
                        <i class="fas fa-<?= $icons[$i] ?>"></i>
                    </div>
                    <div class="step-label <?= $done ? 'done' : ($current ? 'current' : '') ?>">
                        <?= $statut_labels[$etape]['label'] ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="commande-footer">
            <div class="commande-info">
                <i class="fas fa-box"></i> <?= $cmd['nb_articles'] ?> article<?= $cmd['nb_articles'] > 1 ? 's' : '' ?>
                <?php if ($cmd['adresse']): ?>
                &nbsp;|&nbsp; <i class="fas fa-map-marker-alt"></i>
                <?= htmlspecialchars($cmd['adresse'] . ', ' . $cmd['ville']) ?>
                <?php endif; ?>
                &nbsp;|&nbsp; <i class="fas fa-credit-card"></i>
                <?= ucfirst($cmd['mode_paiement']) ?>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="?detail=<?= $cmd['id'] ?>" class="btn-detail" onclick="ouvrirDetail(<?= $cmd['id'] ?>); return false;">
                    <i class="fas fa-eye"></i> Voir le détail
                </a>
                <?php if (in_array($cmd['statut'], ['en_attente', 'confirmee'])): ?>
                <a href="?annuler=<?= $cmd['id'] ?>" class="btn-annuler"
                   onclick="return confirm('Annuler la commande #<?= $cmd['id'] ?> ?')">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ===== MODAL DÉTAIL ===== -->
<div class="modal-bg" id="modalDetail">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modal-title">Détail de la commande</h3>
            <button class="modal-close" onclick="fermerDetail()">✕</button>
        </div>
        <div class="modal-body" id="modal-body">
            <div style="text-align:center; padding:40px; color:#6c757d;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top:12px;">Chargement...</p>
            </div>
        </div>
    </div>
</div>

<script>
// Données des commandes pour le modal
const commandes = <?= json_encode(array_column($commandes, null, 'id')) ?>;

function ouvrirDetail(id) {
    document.getElementById('modalDetail').classList.add('open');

    // Charger le détail via fetch
    fetch('detail_commande.php?id=' + id)
        .then(r => r.text())
        .then(html => {
            document.getElementById('modal-title').innerHTML = 'Commande #' + id;
            document.getElementById('modal-body').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('modal-body').innerHTML =
                '<p style="text-align:center;color:#ef4444;">Erreur de chargement.</p>';
        });
}

function fermerDetail() {
    document.getElementById('modalDetail').classList.remove('open');
}

document.getElementById('modalDetail').addEventListener('click', function(e) {
    if (e.target === this) fermerDetail();
});
</script>

</body>
</html>