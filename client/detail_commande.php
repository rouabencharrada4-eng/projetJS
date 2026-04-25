<?php
// ============================================================
//   NOVASTORE - client/detail_commande.php
//   Détail d'une commande (chargé en AJAX pour le modal)
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    http_response_code(403);
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT c.*, a.adresse, a.ville, a.gouvernorat FROM commandes c LEFT JOIN adresses a ON a.id = c.adresse_id WHERE c.id=? AND c.utilisateur_id=?');
$stmt->execute([$id, $_SESSION['user_id']]);
$cmd = $stmt->fetch();

if (!$cmd) {
    echo '<p style="text-align:center;color:#ef4444;">Commande introuvable.</p>';
    exit;
}

$stmt = $pdo->prepare('
    SELECT lc.*, p.nom, p.marque, p.image, p.modele
    FROM lignes_commande lc
    JOIN produits p ON p.id = lc.produit_id
    WHERE lc.commande_id = ?
');
$stmt->execute([$id]);
$lignes = $stmt->fetchAll();

$statut_labels = [
    'en_attente'     => ['label' => 'En attente',    'color' => '#f59e0b'],
    'confirmee'      => ['label' => 'Confirmée',      'color' => '#3b82f6'],
    'en_preparation' => ['label' => 'En préparation', 'color' => '#8b5cf6'],
    'expediee'       => ['label' => 'Expédiée',       'color' => '#06b6d4'],
    'livree'         => ['label' => 'Livrée',         'color' => '#10b981'],
    'annulee'        => ['label' => 'Annulée',        'color' => '#ef4444'],
];
$s = $statut_labels[$cmd['statut']];
?>

<div style="margin-bottom:20px; padding:16px; background:#f8f9fa; border-radius:10px;">
    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px;">
        <div>
            <div style="font-size:0.85rem; color:#6c757d;">Date</div>
            <div style="font-weight:600;"><?= date('d/m/Y à H:i', strtotime($cmd['created_at'])) ?></div>
        </div>
        <div>
            <div style="font-size:0.85rem; color:#6c757d;">Statut</div>
            <span style="background:<?= $s['color'] ?>22; color:<?= $s['color'] ?>; padding:4px 12px; border-radius:20px; font-size:0.85rem; font-weight:700;">
                <?= $s['label'] ?>
            </span>
        </div>
        <div>
            <div style="font-size:0.85rem; color:#6c757d;">Paiement</div>
            <div style="font-weight:600;"><?= ucfirst($cmd['mode_paiement']) ?></div>
        </div>
        <div>
            <div style="font-size:0.85rem; color:#6c757d;">Total</div>
            <div style="font-weight:700; color:#007bff; font-size:1.1rem;"><?= number_format($cmd['total'], 3) ?> DT</div>
        </div>
    </div>
    <?php if ($cmd['adresse']): ?>
    <div style="margin-top:12px; font-size:0.85rem; color:#6c757d;">
        <i class="fas fa-map-marker-alt"></i>
        <?= htmlspecialchars($cmd['adresse'] . ', ' . $cmd['ville'] . ($cmd['gouvernorat'] ? ', ' . $cmd['gouvernorat'] : '')) ?>
    </div>
    <?php endif; ?>
    <?php if ($cmd['notes']): ?>
    <div style="margin-top:8px; font-size:0.85rem; color:#6c757d;">
        <i class="fas fa-comment"></i> <?= htmlspecialchars($cmd['notes']) ?>
    </div>
    <?php endif; ?>
</div>

<h4 style="color:#1D3557; margin-bottom:16px;">Articles commandés</h4>

<?php foreach ($lignes as $ligne): ?>
<div style="display:flex; align-items:center; gap:14px; padding:14px 0; border-bottom:1px solid #f1f5f9;">
    <?php if ($ligne['image']): ?>
    <img src="../<?= htmlspecialchars($ligne['image']) ?>" alt=""
         style="width:60px; height:60px; object-fit:contain; border-radius:8px; background:#f8f9fa; flex-shrink:0;">
    <?php else: ?>
    <div style="width:60px; height:60px; border-radius:8px; background:#f8f9fa; display:flex; align-items:center; justify-content:center; color:#ccc; flex-shrink:0;">
        <i class="fas fa-image fa-lg"></i>
    </div>
    <?php endif; ?>

    <div style="flex:1;">
        <div style="font-size:0.8rem; color:#E63946; font-weight:700;"><?= htmlspecialchars($ligne['marque'] ?? '') ?></div>
        <div style="font-weight:700; color:#1D3557;"><?= htmlspecialchars($ligne['nom']) ?></div>
        <div style="font-size:0.8rem; color:#6c757d;"><?= htmlspecialchars($ligne['modele'] ?? '') ?></div>
    </div>

    <div style="text-align:right; white-space:nowrap;">
        <div style="font-weight:700; color:#007bff;"><?= number_format($ligne['prix_unitaire'], 3) ?> DT</div>
        <div style="font-size:0.8rem; color:#6c757d;">x <?= $ligne['quantite'] ?></div>
        <div style="font-weight:700; color:#1D3557;"><?= number_format($ligne['sous_total'], 3) ?> DT</div>
    </div>
</div>
<?php endforeach; ?>

<div style="margin-top:20px; padding:16px; background:#f8f9fa; border-radius:10px; display:flex; justify-content:space-between; align-items:center;">
    <span style="font-size:1rem; font-weight:600; color:#374151;">Total de la commande</span>
    <span style="font-size:1.3rem; font-weight:700; color:#007bff;"><?= number_format($cmd['total'], 3) ?> DT</span>
</div>