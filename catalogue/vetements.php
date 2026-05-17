<?php


session_start();
require_once '../config/db.php';

$pdo = getDB();

$nb_panier = 0;

if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'client') {
    $stmt2 = $pdo->prepare('SELECT COALESCE(SUM(quantite),0) FROM panier WHERE utilisateur_id=?');
    $stmt2->execute([$_SESSION['user_id']]);
    $nb_panier = intval($stmt2->fetchColumn());
}

$produits_femme = [
    [
        'nom' => 'Ensemble Simple',
        'marque' => 'NOVASTORE',
        'modele' => '100% Coton – Confort & Style',
        'prix' => 49.900,
        'image' => '../images/ensemble.jpg',
        'note' => 4.5,
        'avis' => 38,
        'tailles' => ['XS', 'S', 'M', 'L'],
        'couleurs' => [
            ['nom' => 'Gris', 'hex' => '#9ca3af'],
            ['nom' => 'Blanc', 'hex' => '#f9fafb'],
            ['nom' => 'Noir', 'hex' => '#111827'],
        ],
        'lien' => 'vetements/femme.php',
    ],
    [
        'nom' => 'Ensemble Rayé',
        'marque' => 'NOVASTORE',
        'modele' => '100% Coton – Rayures tendance',
        'prix' => 54.900,
        'image' => '../images/ensemble2.jpg',
        'note' => 4.0,
        'avis' => 24,
        'tailles' => ['XS', 'S', 'M', 'L'],
        'couleurs' => [
            ['nom' => 'Marron', 'hex' => '#78350f'],
            ['nom' => 'Beige', 'hex' => '#d4b483'],
            ['nom' => 'Blanc', 'hex' => '#f9fafb'],
            ['nom' => 'Noir', 'hex' => '#111827'],
        ],
        'lien' => 'vetements/femme.php',
    ],
    [
        'nom' => 'Pyjama / Ensemble Nuit',
        'marque' => 'NOVASTORE',
        'modele' => 'Coton doux – Confort nuit',
        'prix' => 39.900,
        'image' => '../images/ensemble.jpg',
        'note' => 4.5,
        'avis' => 51,
        'tailles' => ['XS', 'S', 'M', 'L'],
        'couleurs' => [
            ['nom' => 'Jaune + Rose', 'hex' => '#fbbf24', 'hex2' => '#f9a8d4'],
            ['nom' => 'Bleu + Rose', 'hex' => '#60a5fa', 'hex2' => '#f9a8d4'],
        ],
        'lien' => 'vetements/femme.php',
    ],
];

$produits_homme = [
    [
        'nom' => 'Jacket en Coton',
        'marque' => 'NOVASTORE',
        'modele' => '100% Coton – Coupe Regular',
        'prix' => 59.900,
        'image' => '../images/chemise.jpg',
        'note' => 4.5,
        'avis' => 33,
        'tailles' => ['M', 'L', 'XL'],
        'couleurs' => [
            ['nom' => 'Gris', 'hex' => '#9ca3af'],
            ['nom' => 'Noir', 'hex' => '#111827'],
            ['nom' => 'Beige', 'hex' => '#d4b483'],
        ],
        'lien' => 'vetements/homme.php',
    ],
    [
        'nom' => 'Shirt Rayé',
        'marque' => 'NOVASTORE',
        'modele' => 'Coton – Rayures tendance',
        'prix' => 44.900,
        'image' => '../images/shirt.jpg',
        'note' => 4.0,
        'avis' => 27,
        'tailles' => ['M', 'L', 'XL'],
        'couleurs' => [
            ['nom' => 'Blanc + Bleu', 'hex' => '#f9fafb', 'hex2' => '#3b82f6'],
            ['nom' => 'Blanc + Noir', 'hex' => '#f9fafb', 'hex2' => '#111827'],
            ['nom' => 'Blanc + Vert', 'hex' => '#f9fafb', 'hex2' => '#16a34a'],
        ],
        'lien' => 'vetements/homme.php',
    ],
    [
        'nom' => 'Polo en Coton',
        'marque' => 'NOVASTORE',
        'modele' => '100% Coton – Coupe Slim',
        'prix' => 34.900,
        'image' => '../images/polo.jpg',
        'note' => 4.5,
        'avis' => 45,
        'tailles' => ['M', 'L', 'XL'],
        'couleurs' => [
            ['nom' => 'Gris', 'hex' => '#9ca3af'],
            ['nom' => 'Blanc', 'hex' => '#f9fafb'],
            ['nom' => 'Noir', 'hex' => '#111827'],
        ],
        'lien' => 'vetements/homme.php',
    ],
];

function etoiles($note) {
    $html = '';

    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= round(floatval($note))
            ? '<i class="fas fa-star"></i>'
            : '<i class="far fa-star"></i>';
    }

    return $html;
}

function carteVetement($p) {
    $prix_parts = explode('.', number_format(floatval($p['prix']), 3, '.', ''));

    ob_start();
    ?>
    <div class="vetement-card">

        <div class="vetement-img-box">
            <img src="<?= htmlspecialchars($p['image']) ?>"
                 alt="<?= htmlspecialchars($p['nom']) ?>"
                 class="vetement-img">
        </div>

        <div class="vetement-body">

            <div class="vetement-header">
                <div>
                    <span class="brand-tag"><?= htmlspecialchars($p['marque']) ?></span>
                    <div class="vetement-name"><?= htmlspecialchars($p['nom']) ?></div>
                    <div class="vetement-modele"><?= htmlspecialchars($p['modele']) ?></div>
                </div>

                <div class="vetement-price">
                    <?= htmlspecialchars($prix_parts[0]) ?>.<span style="font-size:0.8rem;"><?= htmlspecialchars($prix_parts[1] ?? '000') ?></span> DT
                </div>
            </div>

            <div class="vetement-rating">
                <?= etoiles($p['note']) ?>
                <span style="font-size:0.8rem;color:#6c757d;margin-left:4px;">
                    (<?= intval($p['avis']) ?>)
                </span>
            </div>

            <div class="options-label">Tailles disponibles</div>
            <div class="tailles-row">
                <?php foreach ($p['tailles'] as $t): ?>
                    <span class="taille-pill"><?= htmlspecialchars($t) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="options-label" style="margin-top:10px;">Couleurs disponibles</div>
            <div class="couleurs-row">
                <?php foreach ($p['couleurs'] as $c): ?>
                    <?php if (isset($c['hex2'])): ?>
                        <div class="couleur-dot bicolor"
                             style="--c1:<?= htmlspecialchars($c['hex']) ?>;--c2:<?= htmlspecialchars($c['hex2']) ?>;"
                             title="<?= htmlspecialchars($c['nom']) ?>">
                        </div>
                    <?php else: ?>
                        <div class="couleur-dot"
                             style="background:<?= htmlspecialchars($c['hex']) ?>;<?= $c['hex'] === '#f9fafb' ? 'border-color:#dee2e6;' : '' ?>"
                             title="<?= htmlspecialchars($c['nom']) ?>">
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <a href="<?= htmlspecialchars($p['lien']) ?>" class="btn-commander">
                <i class="fas fa-shopping-bag"></i>
                Choisir & Commander
            </a>

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
    <title>Vêtements – NovaStore</title>

    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .breadcrumb {
            background:white;
            border-bottom:1px solid #e9ecef;
            padding:12px 0;
        }

        .breadcrumb-inner {
            display:flex;
            align-items:center;
            gap:8px;
            font-size:0.9rem;
            color:#6c757d;
        }

        .breadcrumb-inner a {
            color:#E63946;
            text-decoration:none;
        }

        .page-banner {
            background:linear-gradient(135deg,#1D3557,#2d4a6b);
            border-radius:16px;
            padding:32px;
            margin-bottom:48px;
            color:white;
            text-align:center;
        }

        .page-banner h3 {
            font-family:'Playfair Display',serif;
            font-size:1.6rem;
            margin-bottom:8px;
        }

        .page-banner p {
            color:rgba(255,255,255,0.85);
        }

        .section-label {
            display:flex;
            align-items:center;
            gap:12px;
            margin:0 0 28px;
            padding-bottom:12px;
            border-bottom:3px solid #f1f5f9;
        }

        .section-label-icon {
            width:42px;
            height:42px;
            border-radius:10px;
            display:flex;
            align-items:center;
            justify-content:center;
            color:white;
            font-size:1.1rem;
            flex-shrink:0;
        }

        .section-label h3 {
            font-family:'Playfair Display',serif;
            font-size:1.4rem;
            color:#1D3557;
            margin:0;
        }

        .section-label a {
            margin-left:auto;
            font-size:0.88rem;
            color:#E63946;
            text-decoration:none;
            font-weight:600;
            display:flex;
            align-items:center;
            gap:4px;
        }

        .vetement-grid {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
            gap:28px;
            margin-bottom:60px;
        }

        .vetement-card {
            background:white;
            border-radius:14px;
            overflow:hidden;
            box-shadow:0 4px 16px rgba(0,0,0,0.08);
            transition:transform 0.3s, box-shadow 0.3s;
            border:1px solid #f0f0f0;
        }

        .vetement-card:hover {
            transform:translateY(-6px);
            box-shadow:0 10px 30px rgba(0,0,0,0.12);
        }

        .vetement-img-box {
            width:100%;
            height:260px;
            background:#f8f9fa;
            display:flex;
            align-items:center;
            justify-content:center;
            overflow:hidden;
        }

        .vetement-img {
            width:100%;
            height:100%;
            object-fit:contain;
            object-position:center center;
            padding:10px;
            display:block;
        }

        .vetement-body {
            padding:18px;
        }

        .vetement-header {
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:12px;
            margin-bottom:10px;
        }

        .vetement-name {
            font-size:1rem;
            font-weight:700;
            color:#1D3557;
        }

        .vetement-modele {
            font-size:0.82rem;
            color:#6c757d;
            margin-top:2px;
        }

        .vetement-price {
            font-size:1.2rem;
            font-weight:700;
            color:#007bff;
            white-space:nowrap;
        }

        .vetement-rating {
            color:#ffc107;
            font-size:0.82rem;
            display:flex;
            gap:2px;
            margin:10px 0;
        }

        .options-label {
            font-size:0.78rem;
            font-weight:700;
            color:#374151;
            text-transform:uppercase;
            letter-spacing:0.5px;
            margin:12px 0 7px;
        }

        .tailles-row {
            display:flex;
            gap:6px;
            flex-wrap:wrap;
        }

        .taille-pill {
            padding:4px 10px;
            border-radius:6px;
            border:1.5px solid #e9ecef;
            font-size:0.78rem;
            font-weight:700;
            color:#374151;
            background:white;
        }

        .couleurs-row {
            display:flex;
            gap:7px;
            flex-wrap:wrap;
            margin-top:2px;
        }

        .couleur-dot {
            width:24px;
            height:24px;
            border-radius:50%;
            border:2.5px solid #e9ecef;
            flex-shrink:0;
            position:relative;
        }

        .couleur-dot.bicolor {
            background:linear-gradient(135deg,var(--c1) 50%,var(--c2) 50%);
        }

        .couleur-dot[title]:hover::after {
            content:attr(title);
            position:absolute;
            bottom:130%;
            left:50%;
            transform:translateX(-50%);
            background:#1D3557;
            color:white;
            padding:3px 7px;
            border-radius:5px;
            font-size:0.72rem;
            white-space:nowrap;
            z-index:10;
        }

        .btn-commander {
            width:100%;
            padding:11px;
            background:#1D3557;
            color:white;
            border:none;
            border-radius:8px;
            font-family:'DM Sans',sans-serif;
            font-weight:700;
            font-size:0.9rem;
            cursor:pointer;
            transition:0.2s;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:7px;
            text-decoration:none;
            margin-top:14px;
        }

        .btn-commander:hover {
            background:#E63946;
        }

        @media(max-width:768px) {
            .vetement-grid {
                grid-template-columns:1fr;
            }

            .vetement-img-box {
                height:240px;
            }
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

        <a href="../index.php" class="logo" style="text-decoration:none;">
            <img src="../images/logo.png" alt="NovaStore" class="logo-img">
            Nova<strong>Store</strong>
        </a>

        <div class="nav-search">
            <input type="text" placeholder="Rechercher un produit..." id="search-input">
            <button type="button" onclick="lancerRecherche()">Rechercher</button>
        </div>

        <nav class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>

                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>

                    <a href="../admin/dashboard.php" class="btn-nav">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>

                    <a href="../auth/logout.php" class="btn-nav" style="color:#E63946;">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>

                <?php else: ?>

                    <a href="../client/profil.php" class="btn-nav">
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($_SESSION['prenom'] ?? 'Profil') ?>
                    </a>

                    <a href="../client/panier.php" class="btn-nav btn-primary">
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

                <a href="../auth/login.php" class="btn-nav">Connexion</a>
                <a href="../auth/register.php" class="btn-nav btn-primary">S'inscrire</a>

            <?php endif; ?>

            <button id="theme-toggle"
                    type="button"
                    onclick="toggleTheme()"
                    style="background:white;border:2px solid #e9ecef;border-radius:50%;width:40px;height:40px;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;transition:0.3s;flex-shrink:0;">
                🌙
            </button>
        </nav>

    </div>
</header>

<div class="breadcrumb">
    <div class="container">
        <div class="breadcrumb-inner">
            <a href="../index.php">Accueil</a>
            <i class="fas fa-chevron-right" style="font-size:0.7rem;"></i>
            <span style="color:#1D3557;font-weight:600;">Vêtements</span>
        </div>
    </div>
</div>

<section style="padding:50px 0;">
        <div class="section-label">
            <div class="section-label-icon" style="background:#f472b6;">
                <i class="fas fa-female"></i>
            </div>

            <h3>Collection Femme</h3>

            <a href="vetements/femme.php">
                Voir tout <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="vetement-grid">
            <?php foreach ($produits_femme as $p): ?>
                <?= carteVetement($p) ?>
            <?php endforeach; ?>
        </div>

        <div class="section-label">
            <div class="section-label-icon" style="background:#3b82f6;">
                <i class="fas fa-male"></i>
            </div>

            <h3>Collection Homme</h3>

            <a href="vetements/homme.php">
                Voir tout <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="vetement-grid">
            <?php foreach ($produits_homme as $p): ?>
                <?= carteVetement($p) ?>
            <?php endforeach; ?>
        </div>

    </div>
</section>

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
    window.BASE_URL = '../';

    function lancerRecherche() {
        const q = document.getElementById('search-input').value.trim();

        if (q) {
            window.location.href = `../search.php?q=${encodeURIComponent(q)}`;
        }
    }

    document.getElementById('search-input')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            lancerRecherche();
        }
    });

    function toggleTheme() {
        const body = document.body;
        const btn = document.getElementById('theme-toggle');

        body.classList.toggle('dark');

        if (body.classList.contains('dark')) {
            if (btn) btn.textContent = '☀️';
            localStorage.setItem('theme','dark');
        } else {
            if (btn) btn.textContent = '🌙';
            localStorage.setItem('theme','light');
        }
    }

    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');

        const btn = document.getElementById('theme-toggle');
        if (btn) btn.textContent = '☀️';
    }
</script>

<script src="../main.js"></script>

</body>
</html>

