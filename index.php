<?php
session_start();
require_once 'config/db.php';
$pdo = getDB();

function getProduits($pdo, $categorie_slug, $limite = 4) {
    $stmt = $pdo->prepare('
        SELECT p.*, c.nom AS categorie, c.slug
        FROM produits p
        JOIN categories c ON c.id = p.categorie_id
        WHERE c.slug = ? AND p.actif = 1 AND p.stock > 0
        ORDER BY p.note_moyenne DESC
        LIMIT ?
    ');
    $stmt->execute([$categorie_slug, $limite]);
    return $stmt->fetchAll();
}

$categories              = $pdo->query('SELECT * FROM categories WHERE active = 1 ORDER BY ordre')->fetchAll();
$produits_electromenager = getProduits($pdo, 'electromenager');
$produits_ustensiles     = getProduits($pdo, 'ustensiles');
$produits_alimentaire    = getProduits($pdo, 'alimentaire');
$produits_nettoyage      = getProduits($pdo, 'nettoyage');

$wishlist_ids = [];
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client') {
    $stmt = $pdo->prepare('SELECT produit_id FROM wishlist WHERE utilisateur_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_ids = array_column($stmt->fetchAll(), 'produit_id');
}

function etoiles($note) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= round($note) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
    }
    return $html;
}

function carteProuit($p, $wishlist_ids) {
    $in_wishlist = in_array($p['id'], $wishlist_ids);
    $prix_parts  = explode('.', number_format($p['prix'], 3, '.', ''));
    $prix_entier = $prix_parts[0];
    $prix_cents  = $prix_parts[1] ?? '000';
    ob_start();
    ?>
    <div class="product-card">
        <?php if ($p['badge']): ?>
        <div class="product-badge" style="position:absolute;top:10px;left:10px;background:#E63946;color:white;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:700;">
            <?= htmlspecialchars($p['badge']) ?>
        </div>
        <?php endif; ?>
        <button class="wishlist-btn <?= $in_wishlist ? 'active' : '' ?>" data-id="<?= $p['id'] ?>">
            <i class="<?= $in_wishlist ? 'fas' : 'far' ?> fa-heart"></i>
        </button>
        <div class="product-img-box">
            <img src="<?= htmlspecialchars($p['image'] ?? 'images/placeholder.jpg') ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
        </div>
        <div class="product-info">
            <div class="product-footer-price">
                <div class="price-container">
                    <span class="price-main"><?= $prix_entier ?></span>
                    <span class="price-currency">DT</span>
                    <span class="price-cents"><?= $prix_cents ?></span>
                </div>
                <button class="btn-cart-icon" data-id="<?= $p['id'] ?>" <?= $p['stock'] <= 0 ? 'disabled title="Rupture de stock"' : '' ?>>
                    <i class="fas fa-shopping-cart"></i>
                </button>
            </div>
            <?php if ($p['marque']): ?>
            <span class="brand-tag"><?= htmlspecialchars($p['marque']) ?></span>
            <?php endif; ?>
            <h3 class="product-name"><?= htmlspecialchars($p['nom']) ?></h3>
            <p class="product-model"><?= htmlspecialchars($p['modele'] ?? '') ?></p>
            <div class="rating">
                <?= etoiles($p['note_moyenne']) ?>
                <?php if ($p['nb_avis'] > 0): ?>
                <span style="font-size:0.8rem; color:#6c757d; margin-left:4px;">(<?= $p['nb_avis'] ?>)</span>
                <?php endif; ?>
            </div>
            <?php if ($p['stock'] <= 5 && $p['stock'] > 0): ?>
            <p style="font-size:0.75rem; color:#f59e0b; margin-top:6px;">
                <i class="fas fa-exclamation-triangle"></i> Plus que <?= $p['stock'] ?> en stock !
            </p>
            <?php elseif ($p['stock'] <= 0): ?>
            <p style="font-size:0.75rem; color:#ef4444; margin-top:6px;">
                <i class="fas fa-times-circle"></i> Rupture de stock
            </p>
            <?php endif; ?>
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
    <title>NovaStore | Votre supermarché en ligne</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ===== MEGA MENU ===== */
        .dropdown-content { overflow: visible !important; }

        .mega-menu-item { position: relative; }

        .mega-menu-item .has-submenu {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            color: #1a1a2e;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
        }
        .mega-menu-item .has-submenu:hover {
            background: #f8f9fa;
            color: #E63946;
            padding-left: 24px;
        }

        /* Niveau 2 */
        .submenu-lvl2 {
            display: none;
            position: absolute;
            top: 0;
            left: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border-top: 3px solid #E63946;
            z-index: 9999;
            min-width: 210px;
            padding: 6px 0;
            overflow: visible;
        }
        .mega-menu-item:hover .submenu-lvl2 { display: block; }

        /* Lien direct niveau 2 */
        .submenu-lvl2-link {
            display: flex;
            align-items: center;
            padding: 11px 16px;
            font-size: 0.87rem;
            font-weight: 600;
            color: #374151;
            text-decoration: none;
            transition: 0.2s;
            border-left: 3px solid transparent;
            white-space: nowrap;
        }
        .submenu-lvl2-link:hover {
            background: #f8f9fa;
            color: #E63946;
            border-left-color: #E63946;
            padding-left: 20px;
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
        <div class="logo">
            <img src="images/logo.png" alt="Logo de NovaStore" class="logo-img">
            Nova<strong>Store</strong>
        </div>

        <!-- Rayons -->
        <div class="dropdown">
            <button class="btn-nav">Rayons <i class="fa-solid fa-chevron-down" style="font-size:0.8rem; margin-left:5px;"></i></button>
            <div class="dropdown-content">

                <!-- ===== ALIMENTAIRE ===== -->
                <div class="mega-menu-item">
                    <a href="catalogue/alimentaire.php" class="has-submenu">
                        Alimentaire
                        <i class="fas fa-chevron-right" style="font-size:0.75rem; margin-left:12px;"></i>
                    </a>
                    <div class="submenu-lvl2">
                        <a href="catalogue/alimentaire/fruits.php" class="submenu-lvl2-link">Fruits</a>
                        <a href="catalogue/alimentaire/legumes.php" class="submenu-lvl2-link">Légumes</a>
                        <a href="catalogue/alimentaire/produits-laitiers.php" class="submenu-lvl2-link">Produits laitiers</a>
                        <a href="catalogue/alimentaire/boissons.php" class="submenu-lvl2-link">Boissons</a>
                        <a href="catalogue/alimentaire/oeufs.php" class="submenu-lvl2-link">Oeufs</a>
                        <a href="catalogue/alimentaire/petit-dejeuner.php" class="submenu-lvl2-link">Petit déjeuner</a>
                    </div>
                </div>

                <!-- ===== ÉLECTROMÉNAGER ===== -->
                <div class="mega-menu-item">
                    <a href="catalogue/electromenager.php" class="has-submenu">
                        Électroménager
                        <i class="fas fa-chevron-right" style="font-size:0.75rem; margin-left:12px;"></i>
                    </a>
                </div>

                <!-- ===== COSMÉTIQUES ===== -->
                <div class="mega-menu-item">
                    <a href="catalogue/cosmetiques.php" class="has-submenu">
                        Cosmétiques
                        <i class="fas fa-chevron-right" style="font-size:0.75rem; margin-left:12px;"></i>
                    </a>
                    <div class="submenu-lvl2">
                        <a href="catalogue/cosmetiques/parfums.php" class="submenu-lvl2-link">Parfums</a>
                        <a href="catalogue/cosmetiques/maquillage.php" class="submenu-lvl2-link">Maquillage</a>
                        <a href="catalogue/cosmetiques/soins.php" class="submenu-lvl2-link">Soins</a>
                    </div>
                </div>

                <!-- ===== VÊTEMENTS ===== -->
                <div class="mega-menu-item">
                    <a href="catalogue/vetements.php" class="has-submenu">
                        Vêtements
                        <i class="fas fa-chevron-right" style="font-size:0.75rem; margin-left:12px;"></i>
                    </a>
                    <div class="submenu-lvl2">
                        <a href="catalogue/vetements/homme.php" class="submenu-lvl2-link">Homme</a>
                        <a href="catalogue/vetements/femme.php" class="submenu-lvl2-link">Femme</a>
                    </div>
                </div>

                <!-- ===== JEUX ===== -->
                <div class="mega-menu-item">
                    <a href="catalogue/jeux.php" class="has-submenu">
                        Jeux
                        <i class="fas fa-chevron-right" style="font-size:0.75rem; margin-left:12px;"></i>
                    </a>
                </div>

                <!-- ===== USTENSILES ===== -->
                <div class="mega-menu-item">
                    <a href="catalogue/ustensiles.php" class="has-submenu">
                        Ustensiles
                        <i class="fas fa-chevron-right" style="font-size:0.75rem; margin-left:12px;"></i>
                    </a>
                </div>

                <!-- ===== NETTOYAGE ===== -->
                <div class="mega-menu-item">
                    <a href="catalogue/nettoyage.php" class="has-submenu">
                        Nettoyage
                        <i class="fas fa-chevron-right" style="font-size:0.75rem; margin-left:12px;"></i>
                    </a>
                </div>

            </div>
        </div>

        <!-- Recherche -->
        <div class="nav-search">
            <input type="text" placeholder="Rechercher un produit..." id="search-input"
                value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button onclick="lancerRecherche()">Rechercher</button>
        </div>

        <!-- Actions navbar -->
        <nav class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin/dashboard.php" class="btn-nav">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                    <a href="auth/logout.php" class="btn-nav" style="color:#E63946;">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                <?php else: ?>
                    <a href="client/profil.php" class="btn-nav">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['prenom']) ?>
                    </a>
                    <a href="client/panier.php" class="btn-nav btn-primary" id="btn-panier">
                        <i class="fas fa-shopping-cart"></i> Panier
                        <?php
                        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantite),0) FROM panier WHERE utilisateur_id=?');
                        $stmt->execute([$_SESSION['user_id']]);
                        $nb_panier = intval($stmt->fetchColumn());
                        if ($nb_panier > 0): ?>
                        <span style="background:white;color:#E63946;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;margin-left:4px;">
                            <?= $nb_panier ?>
                        </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="auth/login.php" class="btn-nav">Connexion</a>
                <a href="auth/register.php" class="btn-nav btn-primary">S'inscrire</a>
            <?php endif; ?>

            <button id="theme-toggle" onclick="toggleTheme()"
                style="background:white; border:2px solid #e9ecef; border-radius:50%;
                       width:40px; height:40px; cursor:pointer; font-size:1.2rem;
                       display:flex; align-items:center; justify-content:center;
                       transition:0.3s; flex-shrink:0; line-height:1;">
                🌙
            </button>
        </nav>
    </div>
</header>

<!-- HERO SLIDER -->
<main class="hero-slider">
    <div class="hero-content"></div>
</main>

<!-- CATALOGUE BANNER -->
<section class="catalogue-banner">
    <div class="container">
        <a href="#produits" class="btn-catalogue">Découvrir notre catalogue complet</a>
    </div>
</section>

<!-- SECTION : ÉLECTROMÉNAGER -->
<?php
$section_electromenager = array_merge($produits_electromenager, $produits_ustensiles);
if (!empty($section_electromenager)): ?>
<section class="product-section" id="produits">
    <div class="container">
        <h2 class="section-title">Équipez votre cuisine</h2>
        <div class="products-grid">
            <?php foreach (array_slice($section_electromenager, 0, 4) as $p): ?>
                <?= carteProuit($p, $wishlist_ids) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- SECTION : ALIMENTAIRE -->
<?php if (!empty($produits_alimentaire)): ?>
<section class="product-section bg-light">
    <div class="container">
        <h2 class="section-title">Les indispensables du quotidien</h2>
        <div class="products-grid">
            <?php foreach ($produits_alimentaire as $p): ?>
                <?= carteProuit($p, $wishlist_ids) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- SECTION : NETTOYAGE -->
<?php if (!empty($produits_nettoyage)): ?>
<section class="product-section">
    <div class="container">
        <h2 class="section-title">L'essentiel du ménage</h2>
        <div class="products-grid">
            <?php foreach ($produits_nettoyage as $p): ?>
                <?= carteProuit($p, $wishlist_ids) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- SECTION : RÉSULTATS RECHERCHE / FILTRE CATÉGORIE -->
<?php
$recherche  = trim($_GET['q']   ?? '');
$cat_filtre = trim($_GET['cat'] ?? '');

if ($recherche || $cat_filtre):
    $sql    = 'SELECT p.*, c.nom AS categorie FROM produits p JOIN categories c ON c.id = p.categorie_id WHERE p.actif = 1';
    $params = [];
    if ($recherche) {
        $sql     .= ' AND (p.nom LIKE ? OR p.marque LIKE ? OR p.modele LIKE ?)';
        $params[] = "%$recherche%";
        $params[] = "%$recherche%";
        $params[] = "%$recherche%";
    }
    if ($cat_filtre) {
        $sql     .= ' AND c.slug = ?';
        $params[] = $cat_filtre;
    }
    $sql .= ' ORDER BY p.note_moyenne DESC LIMIT 12';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultats = $stmt->fetchAll();
?>
<section class="product-section bg-light">
    <div class="container">
        <h2 class="section-title">
            <?= $recherche
                ? 'Résultats pour "' . htmlspecialchars($recherche) . '"'
                : htmlspecialchars($cat_filtre) ?>
            <span style="font-size:1rem; color:#6c757d; font-weight:400;">
                (<?= count($resultats) ?> produit<?= count($resultats) > 1 ? 's' : '' ?>)
            </span>
        </h2>
        <?php if (empty($resultats)): ?>
            <p style="text-align:center; color:#6c757d; padding:40px;">
                Aucun produit trouvé. <a href="index.php" style="color:#E63946;">Retour à l'accueil</a>
            </p>
        <?php else: ?>
        <div class="products-grid">
            <?php foreach ($resultats as $p): ?>
                <?= carteProuit($p, $wishlist_ids) ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="logo-footer">
                    <img src="images/logo.png" alt="NovaStore" class="logo-img-footer">
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

<!-- SCRIPTS -->
<script>
    const images = ['images/hero-1.png', 'images/hero-2.png', 'images/hero-3.png'];
    let currentIndex = 0;
    const slider = document.querySelector('.hero-slider');
    images.forEach((imageSrc, index) => {
        const slide = document.createElement('div');
        slide.classList.add('slide');
        slide.style.backgroundImage = `url('${imageSrc}')`;
        if (index === 0) slide.classList.add('active');
        slider.appendChild(slide);
    });
    const slides = document.querySelectorAll('.slide');
    function nextSlide() {
        if (slides.length === 0) return;
        slides[currentIndex].classList.remove('active');
        currentIndex = (currentIndex + 1) % slides.length;
        slides[currentIndex].classList.add('active');
    }
    setInterval(nextSlide, 4000);

    function lancerRecherche() {
        const q = document.getElementById('search-input').value.trim();
        if (q) window.location.href = `index.php?q=${encodeURIComponent(q)}`;
    }
    document.getElementById('search-input')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') lancerRecherche();
    });

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
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark');
            const btn = document.getElementById('theme-toggle');
            if (btn) btn.textContent = '☀️';
        }
    })();
</script>
<script src="main.js"></script>

</body>
</html>