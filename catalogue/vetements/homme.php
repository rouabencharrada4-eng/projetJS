<?php

session_start();
require_once '../../config/db.php';

$pdo = getDB();

$wishlist_ids = [];
$nb_panier = 0;

if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'client') {
    $stmt = $pdo->prepare('SELECT produit_id FROM wishlist WHERE utilisateur_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_ids = array_column($stmt->fetchAll(), 'produit_id');

    $stmt2 = $pdo->prepare('SELECT COALESCE(SUM(quantite),0) FROM panier WHERE utilisateur_id=?');
    $stmt2->execute([$_SESSION['user_id']]);
    $nb_panier = intval($stmt2->fetchColumn());
}

$produits = [
    [
        'nom' => 'Jacket en Coton',
        'marque' => 'NOVASTORE',
        'modele' => '100% Coton – Coupe Regular',
        'prix' => 59.900,
        'stock' => 20,
        'image' => 'images/chemise.jpg',
        'image_page' => '../../images/chemise.jpg',
        'note' => 4.5,
        'avis' => 33,
        'tailles' => ['M', 'L', 'XL'],
        'couleurs' => [
            ['nom' => 'Gris', 'hex' => '#9ca3af'],
            ['nom' => 'Noir', 'hex' => '#111827'],
            ['nom' => 'Beige', 'hex' => '#d4b483'],
        ],
    ],
    [
        'nom' => 'Shirt Rayé',
        'marque' => 'NOVASTORE',
        'modele' => 'Coton – Rayures tendance',
        'prix' => 44.900,
        'stock' => 20,
        'image' => 'images/shirt.jpg',
        'image_page' => '../../images/shirt.jpg',
        'note' => 4.0,
        'avis' => 27,
        'tailles' => ['M', 'L', 'XL'],
        'couleurs' => [
            ['nom' => 'Blanc + Bleu', 'hex' => '#f9fafb', 'hex2' => '#3b82f6'],
            ['nom' => 'Blanc + Noir', 'hex' => '#f9fafb', 'hex2' => '#111827'],
            ['nom' => 'Blanc + Vert', 'hex' => '#f9fafb', 'hex2' => '#16a34a'],
        ],
    ],
    [
        'nom' => 'Polo en Coton',
        'marque' => 'NOVASTORE',
        'modele' => '100% Coton – Coupe Slim',
        'prix' => 34.900,
        'stock' => 20,
        'image' => 'images/polo.jpg',
        'image_page' => '../../images/polo.jpg',
        'note' => 4.5,
        'avis' => 45,
        'tailles' => ['M', 'L', 'XL'],
        'couleurs' => [
            ['nom' => 'Gris', 'hex' => '#9ca3af'],
            ['nom' => 'Blanc', 'hex' => '#f9fafb'],
            ['nom' => 'Noir', 'hex' => '#111827'],
        ],
    ],
];

function getCategorieVetementsId($pdo) {
    $stmt = $pdo->prepare("
        SELECT id FROM categories
        WHERE slug IN ('vetements', 'vêtements')
           OR LOWER(nom) LIKE '%vetement%'
           OR LOWER(nom) LIKE '%vêtement%'
        LIMIT 1
    ");
    $stmt->execute();
    $id = $stmt->fetchColumn();

    return $id ? intval($id) : 1;
}

function assurerProduitExiste($pdo, $p) {
    $stmt = $pdo->prepare('SELECT * FROM produits WHERE nom = ? LIMIT 1');
    $stmt->execute([$p['nom']]);
    $db = $stmt->fetch();

    if ($db) {
        return $db;
    }

    $categorie_id = getCategorieVetementsId($pdo);

    $stmt = $pdo->prepare('
        INSERT INTO produits
        (categorie_id, marque, nom, description, modele, prix, stock, image, badge, note_moyenne, nb_avis, actif)
        VALUES (?, ?, ?, NULL, ?, ?, ?, ?, NULL, ?, ?, 1)
    ');

    $stmt->execute([
        $categorie_id,
        $p['marque'],
        $p['nom'],
        $p['modele'],
        $p['prix'],
        $p['stock'],
        $p['image'],
        $p['note'],
        $p['avis']
    ]);

    $id = $pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT * FROM produits WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

foreach ($produits as $k => $p) {
    $db = assurerProduitExiste($pdo, $p);

    $produits[$k]['id'] = intval($db['id']);
    $produits[$k]['stock'] = intval($db['stock']);
    $produits[$k]['prix'] = floatval($db['prix']);
    $produits[$k]['note'] = floatval($db['note_moyenne'] ?? $p['note']);
    $produits[$k]['avis'] = intval($db['nb_avis'] ?? $p['avis']);
    $produits[$k]['marque'] = $db['marque'] ?: $p['marque'];
    $produits[$k]['modele'] = $db['modele'] ?: $p['modele'];
}

function etoiles($note) {
    $html = '';

    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= round(floatval($note))
            ? '<i class="fas fa-star"></i>'
            : '<i class="far fa-star"></i>';
    }

    return $html;
}

function carteVetement($p, $wishlist_ids) {
    $id = intval($p['id']);
    $stock = intval($p['stock']);
    $prix_parts = explode('.', number_format(floatval($p['prix']), 3, '.', ''));

    $wishlist_ids_int = array_map('intval', $wishlist_ids);
    $in_wishlist = in_array($id, $wishlist_ids_int);

    ob_start();
    ?>
    <div class="clothes-card">
        <div class="clothes-img-box">
            <img src="<?= htmlspecialchars($p['image_page']) ?>"
                 alt="<?= htmlspecialchars($p['nom']) ?>"
                 class="clothes-img">
        </div>

        <div class="clothes-body">
            <div class="top-row">
                <span class="brand-tag"><?= htmlspecialchars($p['marque']) ?></span>

                <button class="wishlist-btn <?= $in_wishlist ? 'active' : '' ?>"
                        type="button"
                        data-id="<?= $id ?>">
                    <i class="<?= $in_wishlist ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
            </div>

            <div class="clothes-price">
                <?= htmlspecialchars($prix_parts[0]) ?><small>.<?= htmlspecialchars($prix_parts[1] ?? '000') ?></small> DT
            </div>

            <h3 class="clothes-name"><?= htmlspecialchars($p['nom']) ?></h3>
            <p class="clothes-model"><?= htmlspecialchars($p['modele']) ?></p>

            <div class="rating">
                <?= etoiles($p['note']) ?>
                <span style="font-size:0.8rem;color:#6c757d;margin-left:4px;">
                    (<?= intval($p['avis']) ?>)
                </span>
            </div>

            <div class="options-label">Tailles disponibles</div>
            <div class="tailles-row">
                <?php foreach ($p['tailles'] as $i => $t): ?>
                    <button type="button" class="taille-pill <?= $i === 0 ? 'selected' : '' ?>">
                        <?= htmlspecialchars($t) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="options-label">Couleurs disponibles</div>
            <div class="couleurs-row">
                <?php foreach ($p['couleurs'] as $i => $c): ?>
                    <?php if (isset($c['hex2'])): ?>
                        <button type="button"
                                class="couleur-dot bicolor <?= $i === 0 ? 'selected' : '' ?>"
                                style="--c1:<?= htmlspecialchars($c['hex']) ?>;--c2:<?= htmlspecialchars($c['hex2']) ?>;"
                                title="<?= htmlspecialchars($c['nom']) ?>">
                        </button>
                    <?php else: ?>
                        <button type="button"
                                class="couleur-dot <?= $i === 0 ? 'selected' : '' ?>"
                                style="background:<?= htmlspecialchars($c['hex']) ?>;<?= $c['hex'] === '#f9fafb' ? 'border-color:#cbd5e1;' : '' ?>"
                                title="<?= htmlspecialchars($c['nom']) ?>">
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($stock > 0): ?>
                <button class="btn-add-clothes btn-cart-icon"
                        type="button"
                        data-id="<?= $id ?>">
                    <i class="fas fa-shopping-cart"></i>
                    Ajouter au panier
                </button>
            <?php else: ?>
                <button class="btn-add-clothes"
                        type="button"
                        disabled>
                    <i class="fas fa-ban"></i>
                    Rupture de stock
                </button>
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
    <title>Vêtements Homme – NovaStore</title>

    <link rel="stylesheet" href="../../style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .breadcrumb { background:white; border-bottom:1px solid #e9ecef; padding:12px 0; }
        .breadcrumb-inner { display:flex; align-items:center; gap:8px; font-size:0.9rem; color:#6c757d; }
        .breadcrumb-inner a { color:#E63946; text-decoration:none; }

        .page-banner {
            background:linear-gradient(135deg,#3b82f6,#1D3557);
            border-radius:16px;
            padding:32px;
            margin-bottom:42px;
            color:white;
            text-align:center;
        }

        .page-banner h3 {
            font-family:'Playfair Display',serif;
            font-size:1.7rem;
            margin-bottom:8px;
        }

        .page-banner p { color:rgba(255,255,255,0.85); }

        .clothes-grid {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
            gap:28px;
        }

        .clothes-card {
            background:white;
            border:1px solid #eef2f7;
            border-radius:16px;
            overflow:hidden;
            box-shadow:0 4px 16px rgba(0,0,0,0.06);
        }

        .clothes-img-box {
            width:100%;
            height:290px;
            background:#f8fafc;
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
            border-bottom:1px solid #f1f5f9;
        }

        .clothes-img {
            width:100%;
            height:100%;
            object-fit:contain;
            object-position:center center;
            padding:12px;
            display:block;
        }

        .clothes-body { padding:18px; }

        .top-row {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:12px;
        }

        .clothes-price {
            font-size:1.7rem;
            font-weight:800;
            color:#007bff;
            margin-bottom:10px;
        }

        .clothes-price small { font-size:0.95rem; }

        .clothes-name {
            font-size:1.05rem;
            color:#0f172a;
            font-weight:800;
            margin-bottom:6px;
        }

        .clothes-model {
            color:#64748b;
            font-size:0.9rem;
            margin-bottom:10px;
        }

        .rating {
            color:#ffc107;
            font-size:0.86rem;
            display:flex;
            gap:2px;
            align-items:center;
            margin:10px 0 16px;
        }

        .options-label {
            font-size:0.78rem;
            font-weight:800;
            color:#334155;
            text-transform:uppercase;
            letter-spacing:0.4px;
            margin:12px 0 8px;
        }

        .tailles-row,
        .couleurs-row {
            display:flex;
            gap:8px;
            flex-wrap:wrap;
        }

        .taille-pill {
            padding:6px 12px;
            border-radius:8px;
            border:1.5px solid #e2e8f0;
            background:white;
            color:#0f172a;
            font-weight:700;
            cursor:pointer;
        }

        .taille-pill.selected,
        .taille-pill:hover {
            border-color:#1D3557;
            background:#1D3557;
            color:white;
        }

        .couleur-dot {
            width:28px;
            height:28px;
            border-radius:50%;
            border:2px solid #e2e8f0;
            cursor:pointer;
        }

        .couleur-dot.bicolor {
            background:linear-gradient(135deg,var(--c1) 50%,var(--c2) 50%);
        }

        .couleur-dot.selected,
        .couleur-dot:hover {
            outline:3px solid rgba(29,53,87,0.18);
            border-color:#1D3557;
            transform:scale(1.08);
        }

        .btn-add-clothes {
            width:100%;
            margin-top:18px;
            padding:13px 16px;
            border-radius:10px;
            border:none;
            background:#1D3557;
            color:white;
            font-family:'DM Sans',sans-serif;
            font-weight:800;
            font-size:0.95rem;
            cursor:pointer;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:8px;
        }

        .btn-add-clothes:hover { background:#E63946; }
        .btn-add-clothes:disabled { opacity:0.45; cursor:not-allowed; }

        @media(max-width:768px) {
            .clothes-grid { grid-template-columns:1fr; }
            .clothes-img-box { height:260px; }
        }
    </style>
</head>

<body>

<div class="top-bar">
    <div class="container-top-bar">
        <p>Livraison gratuite depuis 200 DT !</p>
    </div>
</div>

<header class="navbar">
    <div class="nav-container">
        <a href="../../index.php" class="logo" style="text-decoration:none;">
            <img src="../../images/logo.png" alt="NovaStore" class="logo-img">
            Nova<strong>Store</strong>
        </a>

        <div class="nav-search">
            <input type="text" placeholder="Rechercher un produit..." id="search-input">
            <button type="button" onclick="lancerRecherche()">Rechercher</button>
        </div>

        <nav class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="../../admin/dashboard.php" class="btn-nav">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                    <a href="../../auth/logout.php" class="btn-nav" style="color:#E63946;">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                <?php else: ?>
                    <a href="../../client/profil.php" class="btn-nav">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['prenom'] ?? 'Profil') ?>
                    </a>
                    <a href="../../client/panier.php" class="btn-nav btn-primary">
                        <i class="fas fa-shopping-cart"></i> Panier
                        <?php if ($nb_panier > 0): ?>
                            <span id="panier-badge" style="background:white;color:#E63946;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;margin-left:4px;">
                                <?= $nb_panier ?>
                            </span>
                        <?php else: ?>
                            <span id="panier-badge" style="display:none;"></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="../../auth/login.php" class="btn-nav">Connexion</a>
                <a href="../../auth/register.php" class="btn-nav btn-primary">S'inscrire</a>
            <?php endif; ?>

            <button id="theme-toggle" type="button" onclick="toggleTheme()"
                    style="background:white;border:2px solid #e9ecef;border-radius:50%;width:40px;height:40px;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;">
                🌙
            </button>
        </nav>
    </div>
</header>

<div class="breadcrumb">
    <div class="container">
        <div class="breadcrumb-inner">
            <a href="../../index.php">Accueil</a>
            <i class="fas fa-chevron-right" style="font-size:0.7rem;"></i>
            <a href="../vetements.php">Vêtements</a>
            <i class="fas fa-chevron-right" style="font-size:0.7rem;"></i>
            <span style="color:#1D3557;font-weight:600;">Homme</span>
        </div>
    </div>
</div>

<section style="padding:50px 0;">

        <div class="clothes-grid">
            <?php foreach ($produits as $p): ?>
                <?= carteVetement($p, $wishlist_ids) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="logo-footer">
                    <img src="../../images/logo.png" alt="NovaStore" class="logo-img-footer">
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
                    <a href="https://instagram.com" target="_blank">
                        <i class="fab fa-instagram"></i> Instagram
                    </a>
                    <a href="https://facebook.com" target="_blank">
                        <i class="fab fa-facebook"></i> Facebook
                    </a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> NovaStore. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<script>
    window.BASE_URL = '../../';

    function lancerRecherche() {
        const q = document.getElementById('search-input').value.trim();
        if (q) {
            window.location.href = `../../search.php?q=${encodeURIComponent(q)}`;
        }
    }

    document.getElementById('search-input')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') lancerRecherche();
    });

    document.querySelectorAll('.clothes-card').forEach(card => {
        card.querySelectorAll('.taille-pill').forEach(btn => {
            btn.addEventListener('click', function () {
                card.querySelectorAll('.taille-pill').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
            });
        });

        card.querySelectorAll('.couleur-dot').forEach(btn => {
            btn.addEventListener('click', function () {
                card.querySelectorAll('.couleur-dot').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
            });
        });
    });

    function toggleTheme() {
        const body = document.body;
        const btn = document.getElementById('theme-toggle');
        body.classList.toggle('dark');

        if (body.classList.contains('dark')) {
            if (btn) btn.textContent = '☀️';
            localStorage.setItem('theme', 'dark');
        } else {
            if (btn) btn.textContent = '🌙';
            localStorage.setItem('theme', 'light');
        }
    }

    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        const btn = document.getElementById('theme-toggle');
        if (btn) btn.textContent = '☀️';
    }
</script>

<script src="../../main.js"></script>

</body>
</html>