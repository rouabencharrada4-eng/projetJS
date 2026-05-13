<?php
// ============================================================
//   NOVASTORE - search.php
//   Recherche globale de produits
// ============================================================

session_start();
require_once 'config/db.php';
$pdo = getDB();

$q = trim($_GET['q'] ?? '');

$nb_panier = 0;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client') {
    $stmt2 = $pdo->prepare('SELECT COALESCE(SUM(quantite),0) FROM panier WHERE utilisateur_id=?');
    $stmt2->execute([$_SESSION['user_id']]);
    $nb_panier = intval($stmt2->fetchColumn());
}

$resultats = [];
if ($q) {
    // Recherche dans le nom, la marque ou le modèle
    $stmt = $pdo->prepare('
        SELECT p.*, c.nom AS categorie_nom
        FROM produits p
        JOIN categories c ON c.id = p.categorie_id
        WHERE p.actif = 1 
          AND (p.nom LIKE ? OR p.marque LIKE ? OR p.modele LIKE ?)
        ORDER BY p.note_moyenne DESC
    ');
    $search_term = "%$q%";
    $stmt->execute([$search_term, $search_term, $search_term]);
    $resultats = $stmt->fetchAll();
}

function etoiles($note) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= round($note) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
    }
    return $html;
}

function carteProduit($p) {
    $prix_parts = explode('.', number_format($p['prix'], 3, '.', ''));
    ob_start();
    ?>
    <div class="product-card">
        <div class="product-img-box">
            <img src="<?= htmlspecialchars($p['image'] ?? 'images/placeholder.jpg') ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
        </div>
        <div class="product-info">
            <div class="product-footer-price">
                <div class="price-container">
                    <span class="price-main"><?= $prix_parts[0] ?></span>
                    <span class="price-currency">DT</span>
                    <span class="price-cents"><?= $prix_parts[1] ?? '000' ?></span>
                </div>
                <button class="btn-cart-icon" onclick="ajouterAuPanier(<?= $p['id'] ?>)">
                    <i class="fas fa-shopping-cart"></i>
                </button>
            </div>
            <?php if (!empty($p['marque'])): ?>
                <span class="brand-tag"><?= htmlspecialchars($p['marque']) ?></span>
            <?php endif; ?>
            <h3 class="product-name"><?= htmlspecialchars($p['nom']) ?></h3>
            <p class="product-model"><?= htmlspecialchars($p['modele'] ?? '') ?></p>
            <div class="rating">
                <?= etoiles($p['note_moyenne'] ?? 0) ?>
                <span style="font-size:0.8rem; color:#6c757d; margin-left:4px;">(<?= $p['nb_avis'] ?? 0 ?>)</span>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche : "<?= htmlspecialchars($q) ?>" – NovaStore</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .search-header {
            background: white;
            padding: 40px 0;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 40px;
        }
        .search-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: #1D3557;
            margin-bottom: 10px;
        }
        .search-meta {
            color: #6c757d;
            font-size: 1rem;
        }
        .no-results {
            text-align: center;
            padding: 80px 20px;
        }
        .no-results i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        .no-results h3 {
            font-size: 1.5rem;
            color: #1D3557;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<header class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo" style="text-decoration:none;">
            <img src="images/logo.png" alt="NovaStore" class="logo-img">
            Nova<strong>Store</strong>
        </a>
        <div class="nav-search">
            <input type="text" placeholder="Rechercher un produit..." id="search-input" value="<?= htmlspecialchars($q) ?>">
            <button onclick="lancerRecherche()">Rechercher</button>
        </div>
        <nav class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="client/profil.php" class="btn-nav"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['prenom']) ?></a>
                <a href="client/panier.php" class="btn-nav btn-primary">
                    <i class="fas fa-shopping-cart"></i> Panier
                    <?php if ($nb_panier > 0): ?>
                        <span class="panier-count"><?= $nb_panier ?></span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="auth/login.php" class="btn-nav">Connexion</a>
                <a href="auth/register.php" class="btn-nav btn-primary">S'inscrire</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="search-header">
    <div class="container">
        <h1 class="search-title">Résultats de recherche</h1>
        <p class="search-meta">
            <?php if ($q): ?>
                Il y a <strong><?= count($resultats) ?></strong> résultat(s) pour "<strong><?= htmlspecialchars($q) ?></strong>"
            <?php else: ?>
                Veuillez entrer un terme de recherche.
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="container" style="padding-bottom: 80px;">
    <?php if ($q && count($resultats) > 0): ?>
        <div class="products-grid">
            <?php foreach ($resultats as $p): ?>
                <?= carteProduit($p) ?>
            <?php endforeach; ?>
        </div>
    <?php elseif ($q): ?>
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>Aucun produit trouvé</h3>
            <p>Désolé, nous n'avons trouvé aucun article correspondant à votre recherche.</p>
            <a href="index.php" class="btn-primary" style="display:inline-block; margin-top:20px; text-decoration:none; padding:12px 24px; border-radius:8px;">Retour à l'accueil</a>
        </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> NovaStore. Tous droits réservés.</p>
    </div>
</footer>

<script>
    function lancerRecherche() {
        const q = document.getElementById('search-input').value.trim();
        if (q) window.location.href = `search.php?q=${encodeURIComponent(q)}`;
    }
    document.getElementById('search-input')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') lancerRecherche();
    });
</script>
<script src="main.js"></script>

</body>
</html>
