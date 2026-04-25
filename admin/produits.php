<?php
// ============================================================
//   NOVASTORE - admin/produits.php
//   Gestion des produits (CRUD complet)
// ============================================================

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

$message = '';
$type_msg = '';

// ---- SUPPRIMER un produit ----
if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    $pdo->prepare('UPDATE produits SET actif = 0 WHERE id = ?')->execute([$_GET['supprimer']]);
    $message = 'Produit désactivé avec succès.';
    $type_msg = 'success';
}

// ---- AJOUTER / MODIFIER un produit ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = $_POST['id'] ?? null;
    $categorie   = $_POST['categorie_id'];
    $marque      = trim($_POST['marque']);
    $nom         = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $modele      = trim($_POST['modele']);
    $prix        = floatval($_POST['prix']);
    $stock       = intval($_POST['stock']);
    $badge       = trim($_POST['badge']);
    $image       = trim($_POST['image']);

    if ($id) {
        // Modification
        $stmt = $pdo->prepare('
            UPDATE produits SET categorie_id=?, marque=?, nom=?, description=?,
            modele=?, prix=?, stock=?, badge=?, image=?
            WHERE id=?
        ');
        $stmt->execute([$categorie, $marque, $nom, $description, $modele, $prix, $stock, $badge, $image, $id]);
        $message = 'Produit modifié avec succès.';
    } else {
        // Ajout
        $stmt = $pdo->prepare('
            INSERT INTO produits (categorie_id, marque, nom, description, modele, prix, stock, badge, image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$categorie, $marque, $nom, $description, $modele, $prix, $stock, $badge, $image]);
        $message = 'Produit ajouté avec succès.';
    }
    $type_msg = 'success';
}

// ---- Récupérer les produits ----
$search = trim($_GET['search'] ?? '');
$cat_filter = $_GET['cat'] ?? '';

$sql = 'SELECT p.*, c.nom AS categorie FROM produits p JOIN categories c ON c.id = p.categorie_id WHERE p.actif = 1';
$params = [];

if ($search) {
    $sql .= ' AND (p.nom LIKE ? OR p.marque LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($cat_filter) {
    $sql .= ' AND p.categorie_id = ?';
    $params[] = $cat_filter;
}
$sql .= ' ORDER BY p.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll();

// Catégories pour le filtre et le formulaire
$categories = $pdo->query('SELECT * FROM categories WHERE active = 1 ORDER BY ordre')->fetchAll();

// Produit à éditer
$produit_edit = null;
if (isset($_GET['editer']) && is_numeric($_GET['editer'])) {
    $stmt = $pdo->prepare('SELECT * FROM produits WHERE id = ?');
    $stmt->execute([$_GET['editer']]);
    $produit_edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits – Admin NovaStore</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f1f5f9; display: flex; min-height: 100vh; }
        /* Sidebar identique au dashboard */
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

        /* Barre de filtres */
        .filters-bar {
            background:white; border-radius:12px; padding:20px 24px;
            display:flex; gap:16px; align-items:center; margin-bottom:24px;
            box-shadow:0 2px 8px rgba(0,0,0,0.06); flex-wrap:wrap;
        }
        .filters-bar input, .filters-bar select {
            padding:10px 16px; border:2px solid #e9ecef; border-radius:8px;
            font-family:'DM Sans',sans-serif; font-size:0.9rem; outline:none; transition:0.2s;
        }
        .filters-bar input:focus, .filters-bar select:focus { border-color:#E63946; }
        .filters-bar input { flex:1; min-width:200px; }
        .btn-add-produit {
            background:#E63946; color:white; border:none; padding:10px 20px;
            border-radius:8px; cursor:pointer; font-family:'DM Sans',sans-serif;
            font-weight:600; display:flex; align-items:center; gap:8px; white-space:nowrap;
            text-decoration:none; font-size:0.9rem;
        }
        .btn-add-produit:hover { background:#c1121f; }

        /* Table */
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; }
        .data-table { width:100%; border-collapse:collapse; }
        .data-table th { background:#f8f9fa; padding:12px 16px; text-align:left; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.5px; color:#6c757d; font-weight:600; }
        .data-table td { padding:14px 16px; border-bottom:1px solid #f1f5f9; font-size:0.9rem; color:#374151; }
        .data-table tr:last-child td { border-bottom:none; }
        .data-table tr:hover td { background:#f8f9fa; }
        .product-img-thumb { width:50px; height:50px; object-fit:contain; border-radius:8px; background:#f8f9fa; }
        .stock-badge { padding:3px 10px; border-radius:20px; font-size:0.8rem; font-weight:600; }
        .btn-action { padding:6px 12px; border-radius:6px; border:none; cursor:pointer; font-size:0.8rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
        .btn-edit { background:#dbeafe; color:#3b82f6; }
        .btn-edit:hover { background:#bfdbfe; }
        .btn-delete { background:#fee2e2; color:#ef4444; }
        .btn-delete:hover { background:#fecaca; }

        /* Modal formulaire */
        .modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:20px; }
        .modal-bg.open { display:flex; }
        .modal-form { background:white; border-radius:16px; padding:40px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; }
        .modal-form h2 { font-size:1.3rem; color:#1D3557; margin-bottom:24px; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-weight:600; font-size:0.85rem; color:#374151; margin-bottom:6px; }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:10px 14px; border:2px solid #e9ecef; border-radius:8px; font-family:'DM Sans',sans-serif; font-size:0.9rem; outline:none; transition:0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:#E63946; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .btn-submit { background:#E63946; color:white; border:none; padding:12px 24px; border-radius:8px; font-family:'DM Sans',sans-serif; font-weight:700; cursor:pointer; font-size:0.95rem; width:100%; margin-top:8px; }
        .btn-cancel { background:#f1f5f9; color:#374151; border:none; padding:12px 24px; border-radius:8px; font-family:'DM Sans',sans-serif; font-weight:600; cursor:pointer; font-size:0.95rem; width:100%; margin-top:8px; }
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
        <a href="produits.php" class="active"><i class="fas fa-tags"></i> Produits</a>
        <a href="#"><i class="fas fa-list"></i> Catégories</a>
        <div class="nav-section-title">Utilisateurs</div>
        <a href="utilisateurs.php"><i class="fas fa-users"></i> Clients</a>
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
        <h1><i class="fas fa-tags" style="color:#E63946; margin-right:8px;"></i> Gestion des produits</h1>
        <button class="btn-add-produit" onclick="ouvrirModal()">
            <i class="fas fa-plus"></i> Ajouter un produit
        </button>
    </div>

    <div class="page">

        <?php if ($message): ?>
        <div class="alert alert-<?= $type_msg ?>" style="margin-bottom:20px;">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Filtres -->
        <form method="GET" class="filters-bar">
            <input type="text" name="search" placeholder="🔍 Rechercher un produit..." value="<?= htmlspecialchars($search) ?>">
            <select name="cat">
                <option value="">Toutes les catégories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat_filter == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['icone'] . ' ' . $cat['nom']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-add-produit">Filtrer</button>
        </form>

        <!-- Table produits -->
        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($produits)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:40px; color:#6c757d;">Aucun produit trouvé</td></tr>
                <?php else: ?>
                    <?php foreach ($produits as $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['image']): ?>
                            <img src="../<?= htmlspecialchars($p['image']) ?>" alt="" class="product-img-thumb">
                            <?php else: ?>
                            <div class="product-img-thumb" style="display:flex;align-items:center;justify-content:center;color:#ccc;"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:700;"><?= htmlspecialchars($p['nom']) ?></div>
                            <div style="font-size:0.8rem; color:#E63946; font-weight:600;"><?= htmlspecialchars($p['marque'] ?? '') ?></div>
                            <div style="font-size:0.8rem; color:#6c757d;"><?= htmlspecialchars($p['modele'] ?? '') ?></div>
                        </td>
                        <td><?= htmlspecialchars($p['categorie']) ?></td>
                        <td style="font-weight:700; color:#1D3557;"><?= number_format($p['prix'], 3) ?> DT</td>
                        <td>
                            <span class="stock-badge" style="background:<?= $p['stock'] == 0 ? '#fee2e2' : ($p['stock'] < 5 ? '#fef3c7' : '#dcfce7') ?>; color:<?= $p['stock'] == 0 ? '#ef4444' : ($p['stock'] < 5 ? '#f59e0b' : '#10b981') ?>;">
                                <?= $p['stock'] == 0 ? 'Épuisé' : $p['stock'] . ' unités' ?>
                            </span>
                        </td>
                        <td>
                            <a href="?editer=<?= $p['id'] ?>" class="btn-action btn-edit" onclick="ouvrirModalEdit(<?= htmlspecialchars(json_encode($p)) ?>); return false;">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <a href="?supprimer=<?= $p['id'] ?>" class="btn-action btn-delete"
                               onclick="return confirm('Désactiver ce produit ?')">
                                <i class="fas fa-trash"></i> Supprimer
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

<!-- ===== MODAL FORMULAIRE ===== -->
<div class="modal-bg" id="modalProduit">
    <div class="modal-form">
        <h2 id="modal-title"><i class="fas fa-plus" style="color:#E63946;"></i> Ajouter un produit</h2>
        <form method="POST" action="">
            <input type="hidden" name="id" id="form-id">

            <div class="form-row">
                <div class="form-group">
                    <label>Marque</label>
                    <input type="text" name="marque" id="form-marque" placeholder="ex: BRAUN">
                </div>
                <div class="form-group">
                    <label>Catégorie *</label>
                    <select name="categorie_id" id="form-categorie" required>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['icone'] . ' ' . $cat['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Nom du produit *</label>
                <input type="text" name="nom" id="form-nom" placeholder="ex: Cafetière électrique" required>
            </div>

            <div class="form-group">
                <label>Modèle / Détails</label>
                <input type="text" name="modele" id="form-modele" placeholder="ex: Puissance 1000W - 10 tasses">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="form-description" rows="3" placeholder="Description du produit..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Prix (DT) *</label>
                    <input type="number" name="prix" id="form-prix" step="0.001" min="0" placeholder="0.000" required>
                </div>
                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" name="stock" id="form-stock" min="0" placeholder="0" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Image (chemin)</label>
                    <input type="text" name="image" id="form-image" placeholder="images/produit.jpg">
                </div>
                <div class="form-group">
                    <label>Badge</label>
                    <input type="text" name="badge" id="form-badge" placeholder="ex: Promo, Nouveau">
                </div>
            </div>

            <div class="form-row" style="margin-top:8px;">
                <button type="button" class="btn-cancel" onclick="fermerModal()">Annuler</button>
                <button type="submit" class="btn-submit">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function ouvrirModal() {
    document.getElementById('modal-title').innerHTML = '<i class="fas fa-plus" style="color:#E63946;"></i> Ajouter un produit';
    document.getElementById('form-id').value = '';
    document.getElementById('form-marque').value = '';
    document.getElementById('form-nom').value = '';
    document.getElementById('form-modele').value = '';
    document.getElementById('form-description').value = '';
    document.getElementById('form-prix').value = '';
    document.getElementById('form-stock').value = '';
    document.getElementById('form-image').value = '';
    document.getElementById('form-badge').value = '';
    document.getElementById('modalProduit').classList.add('open');
}

function ouvrirModalEdit(p) {
    document.getElementById('modal-title').innerHTML = '<i class="fas fa-edit" style="color:#3b82f6;"></i> Modifier le produit';
    document.getElementById('form-id').value         = p.id;
    document.getElementById('form-marque').value     = p.marque || '';
    document.getElementById('form-categorie').value  = p.categorie_id;
    document.getElementById('form-nom').value        = p.nom;
    document.getElementById('form-modele').value     = p.modele || '';
    document.getElementById('form-description').value= p.description || '';
    document.getElementById('form-prix').value       = p.prix;
    document.getElementById('form-stock').value      = p.stock;
    document.getElementById('form-image').value      = p.image || '';
    document.getElementById('form-badge').value      = p.badge || '';
    document.getElementById('modalProduit').classList.add('open');
}

function fermerModal() {
    document.getElementById('modalProduit').classList.remove('open');
}

// Fermer en cliquant dehors
document.getElementById('modalProduit').addEventListener('click', function(e) {
    if (e.target === this) fermerModal();
});
</script>

</body>
</html>