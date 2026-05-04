<?php
// ============================================================
//   NOVASTORE - catalogue/alimentaire.php
// ============================================================

session_start();
require_once '../config/db.php';
$pdo = getDB();

$wishlist_ids = [];
$nb_panier = 0;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client') {
    $stmt = $pdo->prepare('SELECT produit_id FROM wishlist WHERE utilisateur_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_ids = array_column($stmt->fetchAll(), 'produit_id');

    $stmt2 = $pdo->prepare('SELECT COALESCE(SUM(quantite),0) FROM panier WHERE utilisateur_id=?');
    $stmt2->execute([$_SESSION['user_id']]);
    $nb_panier = intval($stmt2->fetchColumn());
}

$sections = [
    [
        'titre' => 'Fruits',
        'icon'  => 'fas fa-apple-alt',
        'color' => '#10b981',
        'produits' => [
            ['nom' => 'Bananes', 'prix' => 20.000, 'image' => '../images/banan.jpg', 'note' => 4.0, 'avis' => 42],
            ['nom' => 'Kiwi',   'prix' => 18.500, 'image' => '../images/kiwi.jpg',  'note' => 4.5, 'avis' => 18],
            ['nom' => 'Pommes', 'prix' => 12.000, 'image' => '../images/pomme.jpg', 'note' => 4.0, 'avis' => 31],
        ]
    ],
    [
        'titre' => 'Légumes',
        'icon'  => 'fas fa-leaf',
        'color' => '#3b82f6',
        'produits' => [
            ['nom' => 'Pommes de terre', 'prix' => 3.500, 'image' => '../images/pot.jpg',   'note' => 4.0, 'avis' => 25],
            ['nom' => 'Carottes',        'prix' => 2.800, 'image' => '../images/carot.jpg', 'note' => 4.5, 'avis' => 19],
            ['nom' => 'Oignons',         'prix' => 2.500, 'image' => '../images/onion.jpg', 'note' => 4.0, 'avis' => 14],
        ]
    ],
    [
        'titre' => 'Produits laitiers',
        'icon'  => 'fas fa-glass-whiskey',
        'color' => '#8b5cf6',
        'produits' => [
            ['nom' => 'Lait',     'marque' => 'DELICE',    'modele' => '1L – Entier',        'prix' => 2.990, 'image' => '../images/lait.jpg', 'note' => 4.0, 'avis' => 55],
            ['nom' => 'Yaghourt', 'marque' => 'GRECOS',    'modele' => 'Nature – Pack de 4', 'prix' => 4.200, 'image' => '../images/yag.jpg',  'note' => 4.5, 'avis' => 33],
            ['nom' => 'Fromage',  'marque' => 'PRÉSIDENT', 'modele' => '8 Tranches',         'prix' => 8.900, 'image' => '../images/from.jpg', 'note' => 4.0, 'avis' => 27],
        ]
    ],
    [
        'titre' => 'Boissons',
        'icon'  => 'fas fa-cocktail',
        'color' => '#f59e0b',
        'produits' => [
            ['nom' => 'Eau gazeuse',  'marque' => 'BOGA',   'modele' => '1.5L – Pack de 4',   'prix' => 5.500, 'image' => '../images/gaz.jpg', 'note' => 4.0, 'avis' => 41],
            ['nom' => 'Jus de fruit', 'marque' => 'DELICE', 'modele' => '1L – Multivitaminé', 'prix' => 4.800, 'image' => '../images/jus.jpg', 'note' => 4.5, 'avis' => 38],
            ['nom' => 'Eau minérale', 'marque' => 'FOURAT', 'modele' => '1.5L – Pack de 6',   'prix' => 6.000, 'image' => '../images/eau.jpg', 'note' => 5.0, 'avis' => 62],
        ]
    ],
    [
        'titre' => 'Oeufs',
        'icon'  => 'fas fa-egg',
        'color' => '#E63946',
        'produits' => [
            ['nom' => "Plateau d'œufs frais", 'marque' => 'EL MAZRAA', 'modele' => 'Le plateau de 30 pièces', 'prix' => 13.500, 'image' => '../images/plateau.jpg', 'note' => 5.0, 'avis' => 88],
        ]
    ],
    [
        'titre' => 'Petit déjeuner',
        'icon'  => 'fas fa-bread-slice',
        'color' => '#06b6d4',
        'produits' => [
            ['nom' => 'Pain de mie (Toast)', 'marque' => 'BLEU DORE', 'modele' => 'Sachet de 500g', 'prix' => 2.350, 'image' => '../images/toast.jpg',     'note' => 4.0, 'avis' => 23],
            ['nom' => 'Confiture',           'marque' => 'EMMA',      'modele' => 'Fraise – 340g',  'prix' => 6.900, 'image' => '../images/confiture.jpg', 'note' => 4.5, 'avis' => 17],
            ['nom' => 'Beurre',              'marque' => 'JADIDA',    'modele' => 'Doux – 1kg',     'prix' => 5.200, 'image' => '../images/beurre.jpg',    'note' => 4.5, 'avis' => 29],
        ]
    ],
];

$section_ids = ['fruits', 'legumes', 'laitiers', 'boissons', 'oeufs', 'petitdej'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alimentaire – NovaStore</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .breadcrumb {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 12px 0;
        }
        .breadcrumb-inner {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.9rem; color: #6c757d;
        }
        .breadcrumb-inner a { color: #E63946; text-decoration: none; }

        .page-banner {
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 16px; padding: 32px;
            margin-bottom: 48px; color: white; text-align: center;
        }
        .page-banner h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem; margin-bottom: 8px;
        }

        .sections-nav {
            display: flex; gap: 10px; flex-wrap: wrap;
            justify-content: center; margin-bottom: 48px;
        }
        .section-nav-btn {
            padding: 10px 20px; border-radius: 25px;
            border: 2px solid #e9ecef; background: white;
            cursor: pointer; font-family: 'DM Sans', sans-serif;
            font-weight: 600; font-size: 0.88rem; color: #374151;
            transition: 0.2s; text-decoration: none;
            display: flex; align-items: center; gap: 6px;
        }
        .section-nav-btn:hover {
            border-color: #E63946; color: #E63946; background: #fce7f3;
        }

        .section-label {
            display: flex; align-items: center; gap: 12px;
            margin: 48px 0 24px; padding-bottom: 12px;
            border-bottom: 3px solid #f1f5f9;
        }
        .section-label-icon {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.1rem; flex-shrink: 0;
        }
        .section-label h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem; color: #1D3557; margin: 0;
        }
        .section-label span {
            font-size: 0.85rem; color: #6c757d; margin-left: auto;
        }
    </style>
</head>
<body>

<!-- TOP BAR -->
<div class="top-bar">
    <div class="container-top-bar">
        <p>Livraison gratuite depuis 200 DT !</p>
    </div>
</div>

<!-- NAVBAR -->
<header class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="logo" style="text-decoration:none;">
            <img src="../images/logo.png" alt="NovaStore" class="logo-img">
            Nova<strong>Store</strong>
        </a>
        <div class="nav-search">
            <input type="text" placeholder="Rechercher un produit..." id="search-input">
            <button onclick="lancerRecherche()">Rechercher</button>
        </div>
        <nav class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="../admin/dashboard.php" class="btn-nav"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <a href="../auth/logout.php" class="btn-nav" style="color:#E63946;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                <?php else: ?>
                    <a href="../client/profil.php" class="btn-nav"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['prenom']) ?></a>
                    <a href="../client/panier.php" class="btn-nav btn-primary">
                        <i class="fas fa-shopping-cart"></i> Panier
                        <?php if ($nb_panier > 0): ?>
                        <span style="background:white;color:#E63946;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;margin-left:4px;">
                            <?= $nb_panier ?>
                        </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="../auth/login.php" class="btn-nav">Connexion</a>
                <a href="../auth/register.php" class="btn-nav btn-primary">S'inscrire</a>
            <?php endif; ?>
            <button id="theme-toggle" onclick="toggleTheme()"
                style="background:white;border:2px solid #e9ecef;border-radius:50%;width:40px;height:40px;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;transition:0.3s;flex-shrink:0;">
                🌙
            </button>
        </nav>
    </div>
</header>

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <div class="container">
        <div class="breadcrumb-inner">
            <a href="../index.php">Accueil</a>
            <i class="fas fa-chevron-right" style="font-size:0.7rem;"></i>
            <span style="color:#1D3557;font-weight:600;">Alimentaire</span>
        </div>
    </div>
</div>

<!-- CONTENU -->
<section style="padding:50px 0;">
    <div class="container">

        <div class="page-banner">
            <h3>Rayon Alimentaire</h3>
        </div>

        <!-- Navigation rapide -->
        <div class="sections-nav">
            <a href="#fruits"   class="section-nav-btn"><i class="fas fa-apple-alt"></i> Fruits</a>
            <a href="#legumes"  class="section-nav-btn"><i class="fas fa-leaf"></i> Légumes</a>
            <a href="#laitiers" class="section-nav-btn"><i class="fas fa-glass-whiskey"></i> Produits laitiers</a>
            <a href="#boissons" class="section-nav-btn"><i class="fas fa-cocktail"></i> Boissons</a>
            <a href="#oeufs"    class="section-nav-btn"><i class="fas fa-egg"></i> Oeufs</a>
            <a href="#petitdej" class="section-nav-btn"><i class="fas fa-bread-slice"></i> Petit déjeuner</a>
        </div>

        <?php foreach ($sections as $i => $section): ?>
        <div id="<?= $section_ids[$i] ?>">
            <div class="section-label">
                <div class="section-label-icon" style="background:<?= $section['color'] ?>;">
                    <i class="<?= $section['icon'] ?>"></i>
                </div>
                <h3><?= $section['titre'] ?></h3>
                <span><?= count($section['produits']) ?> produit<?= count($section['produits']) > 1 ? 's' : '' ?></span>
            </div>

            <div class="products-grid">
                <?php foreach ($section['produits'] as $p):
                    $prix_parts = explode('.', number_format($p['prix'], 3, '.', ''));
                    $marque     = $p['marque'] ?? '';   // ← optionnel
                    $modele     = $p['modele'] ?? '';   // ← optionnel
                ?>
                <div class="product-card">
                    <button class="wishlist-btn">
                        <i class="far fa-heart"></i>
                    </button>
                    <div class="product-img-box">
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                    </div>
                    <div class="product-info">
                        <div class="product-footer-price">
                            <div class="price-container">
                                <span class="price-main"><?= $prix_parts[0] ?></span>
                                <span class="price-currency">DT</span>
                                <span class="price-cents"><?= $prix_parts[1] ?? '000' ?></span>
                            </div>
                            <button class="btn-cart-icon"
                                onclick="ajouterAuPanier('<?= htmlspecialchars(addslashes($p['nom'])) ?>')">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>

                        <?php if ($marque): ?>
                        <span class="brand-tag"><?= htmlspecialchars($marque) ?></span>
                        <?php endif; ?>

                        <h3 class="product-name"><?= htmlspecialchars($p['nom']) ?></h3>

                        <?php if ($modele): ?>
                        <p class="product-model"><?= htmlspecialchars($modele) ?></p>
                        <?php endif; ?>

                        <div class="rating">
                            <?php for ($j = 1; $j <= 5; $j++): ?>
                                <i class="<?= $j <= round($p['note']) ? 'fas' : 'far' ?> fa-star"></i>
                            <?php endfor; ?>
                            <?php if ($p['avis'] > 0): ?>
                            <span style="font-size:0.8rem; color:#6c757d; margin-left:4px;">(<?= $p['avis'] ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="logo-footer">
                    <img src="../images/logo.png" alt="NovaStore" class="logo-img-footer">
                </div>
                <p>La qualité professionnelle au service de votre quotidien.</p>
            </div>
            <div>
                <h4>Aide & Service</h4>
                <a href="#">Livraison</a>
                <a href="#">Retours</a>
                <a href="#">Conditions générales</a>
            </div>
            <div>
                <h4>Contactez-nous</h4>
                <a href="tel:+21672772779">+216 72 772 779</a>
                <a href="mailto:contact@novastore.com">contact@novastore.com</a>
                <p>123 Ghazela 2, Ariana</p>
            </div>
            <div>
                <h4>Suivez-nous</h4>
                <div class="social-links">
                    <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i> Instagram</a>
                    <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook"></i> Facebook</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> NovaStore. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<script>
    function lancerRecherche() {
        const q = document.getElementById('search-input').value.trim();
        if (q) window.location.href = `../index.php?q=${encodeURIComponent(q)}`;
    }
    document.getElementById('search-input')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') lancerRecherche();
    });

    function ajouterAuPanier(nom) {
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client'): ?>
        const toast = document.createElement('div');
        toast.style.cssText = `
            position:fixed; bottom:30px; right:30px;
            background:#10b981; color:white;
            padding:14px 24px; border-radius:50px;
            font-weight:600; font-family:'DM Sans',sans-serif;
            font-size:0.95rem; z-index:99999;
            box-shadow:0 4px 20px rgba(16,185,129,0.4);
        `;
        toast.innerHTML = '✅ ' + nom + ' ajouté au panier !';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
        <?php else: ?>
        window.location.href = '../auth/login.php';
        <?php endif; ?>
    }

    function toggleTheme() {
        const body = document.body;
        const btn  = document.getElementById('theme-toggle');
        body.classList.toggle('dark');
        if (body.classList.contains('dark')) {
            btn.textContent = '☀️';
            localStorage.setItem('theme', 'dark');
        } else {
            btn.textContent = '🌙';
            localStorage.setItem('theme', 'light');
        }
    }
    (function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            const btn = document.getElementById('theme-toggle');
            if (btn) btn.textContent = '☀️';
        }
    })();
</script>
<script src="../main.js"></script>

</body>
</html>