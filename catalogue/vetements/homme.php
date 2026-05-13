<?php
// ============================================================
//   NOVASTORE - catalogue/vetements/homme.php
// ============================================================

session_start();
require_once '../../config/db.php';
$pdo = getDB();

$nb_panier = 0;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client') {
    $stmt2 = $pdo->prepare('SELECT COALESCE(SUM(quantite),0) FROM panier WHERE utilisateur_id=?');
    $stmt2->execute([$_SESSION['user_id']]);
    $nb_panier = intval($stmt2->fetchColumn());
}

$produits = [
    [
        'id'      => 'homme-1',
        'nom'     => 'Jacket en Coton',
        'marque'  => 'NOVASTORE',
        'modele'  => '100% Coton – Coupe Regular',
        'prix'    => 59.900,
        'image'   => '../../images/chemise.jpg',
        'note'    => 4.5,
        'avis'    => 33,
        'tailles' => ['M', 'L', 'XL'],
        'couleurs' => [
            ['nom' => 'Gris',  'hex' => '#9ca3af'],
            ['nom' => 'Noir',  'hex' => '#111827'],
            ['nom' => 'Beige', 'hex' => '#d4b483'],
        ],
    ],
    [
        'id'      => 'homme-2',
        'nom'     => 'Shirt Rayé',
        'marque'  => 'NOVASTORE',
        'modele'  => 'Coton – Rayures tendance',
        'prix'    => 44.900,
        'image'   => '../../images/shirt.jpg',
        'note'    => 4.0,
        'avis'    => 27,
        'tailles' => ['M', 'L', 'XL'],
        'couleurs' => [
            ['nom' => 'Blanc + Bleu',  'hex' => '#f9fafb', 'hex2' => '#3b82f6'],
            ['nom' => 'Blanc + Noir',  'hex' => '#f9fafb', 'hex2' => '#111827'],
            ['nom' => 'Blanc + Vert',  'hex' => '#f9fafb', 'hex2' => '#16a34a'],
        ],
    ],
    [
        'id'      => 'homme-3',
        'nom'     => 'Polo en Coton',
        'marque'  => 'NOVASTORE',
        'modele'  => '100% Coton – Coupe Slim',
        'prix'    => 34.900,
        'image'   => '../../images/polo.jpg',
        'note'    => 4.5,
        'avis'    => 45,
        'tailles' => ['M', 'L', 'XL'],
        'couleurs' => [
            ['nom' => 'Gris',  'hex' => '#9ca3af'],
            ['nom' => 'Blanc', 'hex' => '#f9fafb'],
            ['nom' => 'Noir',  'hex' => '#111827'],
        ],
    ],
];
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
            background: linear-gradient(135deg, #1D3557, #2d4a6b);
            border-radius:16px; padding:32px;
            margin-bottom:48px; color:white; text-align:center;
        }
        .page-banner h3 { font-family:'Playfair Display',serif; font-size:1.6rem; margin-bottom:8px; }
        .page-banner p { color:rgba(255,255,255,0.85); }

        .vetement-grid {
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(300px,1fr));
            gap:32px; max-width:1100px; margin:0 auto;
        }
        .vetement-card {
            background:white; border-radius:16px; overflow:hidden;
            box-shadow:0 4px 20px rgba(0,0,0,0.08);
            transition:transform 0.3s, box-shadow 0.3s;
            border:1px solid #f0f0f0;
        }
        .vetement-card:hover { transform:translateY(-6px); box-shadow:0 10px 30px rgba(0,0,0,0.12); }

        .vetement-img { width:100%; height:280px; object-fit:cover; background:#f8f9fa; }
        .vetement-body { padding:20px; }
        .vetement-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; }
        .vetement-name { font-size:1.1rem; font-weight:700; color:#1D3557; }
        .vetement-modele { font-size:0.85rem; color:#6c757d; margin-top:2px; }
        .vetement-price { font-size:1.3rem; font-weight:700; color:#007bff; white-space:nowrap; }

        .options-label { font-size:0.82rem; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px; margin:14px 0 8px; }

        .tailles-row { display:flex; gap:8px; flex-wrap:wrap; }
        .taille-btn {
            width:46px; height:42px; border-radius:8px;
            border:2px solid #e9ecef; background:white;
            cursor:pointer; font-family:'DM Sans',sans-serif;
            font-weight:700; font-size:0.85rem; color:#374151;
            transition:0.2s; display:flex; align-items:center; justify-content:center;
        }
        .taille-btn:hover, .taille-btn.selected {
            border-color:#E63946; background:#E63946; color:white;
        }

        .couleurs-row { display:flex; gap:10px; flex-wrap:wrap; }
        .couleur-wrapper { position:relative; display:flex; align-items:center; }
        .couleur-btn {
            width:32px; height:32px; border-radius:50%;
            border:3px solid #e9ecef; cursor:pointer;
            transition:0.2s; flex-shrink:0;
        }
        .couleur-btn.bicolor { background:linear-gradient(135deg, var(--c1) 50%, var(--c2) 50%); }
        .couleur-btn:hover { border-color:#E63946; transform:scale(1.15); }

        .couleur-tooltip {
            display:none; position:absolute; bottom:120%; left:50%;
            transform:translateX(-50%); background:#1D3557; color:white;
            padding:4px 8px; border-radius:6px; font-size:0.75rem;
            white-space:nowrap; z-index:10;
        }
        .couleur-wrapper:hover .couleur-tooltip { display:block; }

        .vetement-rating { color:#ffc107; font-size:0.85rem; display:flex; gap:2px; margin:12px 0; }

        .selection-info { font-size:0.8rem; color:#6c757d; margin-top:4px; min-height:18px; }

        .btn-ajouter {
            width:100%; padding:12px; background:#1D3557; color:white;
            border:none; border-radius:10px; font-family:'DM Sans',sans-serif;
            font-weight:700; font-size:0.95rem; cursor:pointer; transition:0.2s;
            display:flex; align-items:center; justify-content:center; gap:8px; margin-top:16px;
        }
        .btn-ajouter:hover { background:#E63946; }
    </style>
</head>
<body>

<!-- TOP BAR -->
<div class="top-bar">
    <div class="container-top-bar"><p>Livraison gratuite depuis 200 DT !</p></div>
</div>

<!-- NAVBAR -->
<header class="navbar">
    <div class="nav-container">
        <a href="../../index.php" class="logo" style="text-decoration:none;">
            <img src="../../images/logo.png" alt="NovaStore" class="logo-img">
            Nova<strong>Store</strong>
        </a>
        <div class="nav-search">
            <input type="text" placeholder="Rechercher un produit..." id="search-input">
            <button onclick="lancerRecherche()">Rechercher</button>
        </div>
        <nav class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="../../admin/dashboard.php" class="btn-nav"><i class="fas fa-chart-pie"></i> Dashboard</a>
                    <a href="../../auth/logout.php" class="btn-nav" style="color:#E63946;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                <?php else: ?>
                    <a href="../../client/profil.php" class="btn-nav"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['prenom']) ?></a>
                    <a href="../../client/panier.php" class="btn-nav btn-primary">
                        <i class="fas fa-shopping-cart"></i> Panier
                        <?php if ($nb_panier > 0): ?>
                        <span style="background:white;color:#E63946;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;margin-left:4px;"><?= $nb_panier ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="../../auth/login.php" class="btn-nav">Connexion</a>
                <a href="../../auth/register.php" class="btn-nav btn-primary">S'inscrire</a>
            <?php endif; ?>
            <button id="theme-toggle" onclick="toggleTheme()"
                style="background:white;border:2px solid #e9ecef;border-radius:50%;width:40px;height:40px;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;transition:0.3s;flex-shrink:0;">🌙</button>
        </nav>
    </div>
</header>

<!-- BREADCRUMB -->
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

<!-- CONTENU -->
<section style="padding:50px 0;">
    <div class="container">

        <div class="page-banner">
            <h3>Collection Homme</h3>
            <p>Jackets, shirts rayés et polos — Tailles M, L, XL disponibles</p>
        </div>

        <div class="vetement-grid">
            <?php foreach ($produits as $p): ?>
            <div class="vetement-card" id="card-<?= $p['id'] ?>">
                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>" class="vetement-img">
                <div class="vetement-body">
                    <div class="vetement-header">
                        <div>
                            <span class="brand-tag"><?= htmlspecialchars($p['marque']) ?></span>
                            <div class="vetement-name"><?= htmlspecialchars($p['nom']) ?></div>
                            <div class="vetement-modele"><?= htmlspecialchars($p['modele']) ?></div>
                        </div>
                        <div class="vetement-price"><?= number_format($p['prix'], 3) ?> DT</div>
                    </div>

                    <div class="vetement-rating">
                        <?php for ($j = 1; $j <= 5; $j++): ?>
                            <i class="<?= $j <= round($p['note']) ? 'fas' : 'far' ?> fa-star"></i>
                        <?php endfor; ?>
                        <span style="font-size:0.8rem;color:#6c757d;margin-left:4px;">(<?= $p['avis'] ?>)</span>
                    </div>

                    <!-- Tailles -->
                    <div class="options-label">Taille</div>
                    <div class="tailles-row" id="tailles-<?= $p['id'] ?>">
                        <?php foreach ($p['tailles'] as $t): ?>
                        <button class="taille-btn" onclick="selectionnerTaille('<?= $p['id'] ?>', '<?= $t ?>', this)">
                            <?= $t ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="selection-info" id="taille-selected-<?= $p['id'] ?>"></div>

                    <!-- Couleurs -->
                    <div class="options-label" style="margin-top:14px;">Couleur</div>
                    <div class="couleurs-row" id="couleurs-<?= $p['id'] ?>">
                        <?php foreach ($p['couleurs'] as $c): ?>
                        <div class="couleur-wrapper">
                            <?php if (isset($c['hex2'])): ?>
                            <button class="couleur-btn bicolor"
                                style="--c1:<?= $c['hex'] ?>; --c2:<?= $c['hex2'] ?>;"
                                onclick="selectionnerCouleur('<?= $p['id'] ?>', '<?= addslashes($c['nom']) ?>', this)"
                                title="<?= $c['nom'] ?>">
                            </button>
                            <?php else: ?>
                            <button class="couleur-btn"
                                style="background:<?= $c['hex'] ?>;<?= $c['hex']==='#f9fafb'?'border-color:#dee2e6;':'' ?>"
                                onclick="selectionnerCouleur('<?= $p['id'] ?>', '<?= $c['nom'] ?>', this)"
                                title="<?= $c['nom'] ?>">
                            </button>
                            <?php endif; ?>
                            <span class="couleur-tooltip"><?= $c['nom'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="selection-info" id="couleur-selected-<?= $p['id'] ?>"></div>

                    <button class="btn-ajouter" onclick="ajouterPanier('<?= $p['id'] ?>', '<?= addslashes($p['nom']) ?>')">
                        <i class="fas fa-shopping-cart"></i> Ajouter au panier
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="logo-footer"><img src="../../images/logo.png" alt="NovaStore" class="logo-img-footer"></div>
                <p>La qualité professionnelle au service de votre quotidien.</p>
            </div>
            <div>
                <h4>Aide & Service</h4>
                <a href="#">Livraison</a><a href="#">Retours</a><a href="#">Conditions générales</a>
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
        <div class="footer-bottom"><p>&copy; <?= date('Y') ?> NovaStore. Tous droits réservés.</p></div>
    </div>
</footer>

<script>
    const selections = {};

    function selectionnerTaille(produitId, taille, btn) {
        document.querySelectorAll(`#tailles-${produitId} .taille-btn`).forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        if (!selections[produitId]) selections[produitId] = {};
        selections[produitId].taille = taille;
        document.getElementById(`taille-selected-${produitId}`).textContent = 'Taille sélectionnée : ' + taille;
    }

    function selectionnerCouleur(produitId, couleur, btn) {
        document.querySelectorAll(`#couleurs-${produitId} .couleur-btn`).forEach(b => {
            b.style.boxShadow = 'none';
            b.style.transform = 'scale(1)';
        });
        btn.style.boxShadow = '0 0 0 3px #E63946';
        btn.style.transform = 'scale(1.15)';
        if (!selections[produitId]) selections[produitId] = {};
        selections[produitId].couleur = couleur;
        document.getElementById(`couleur-selected-${produitId}`).textContent = 'Couleur sélectionnée : ' + couleur;
    }

    function ajouterPanier(produitId, nom) {
        const sel = selections[produitId] || {};
        if (!sel.taille) { alert('Veuillez sélectionner une taille !'); return; }
        if (!sel.couleur) { alert('Veuillez sélectionner une couleur !'); return; }

        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client'): ?>
        const toast = document.createElement('div');
        toast.style.cssText = `position:fixed;bottom:30px;right:30px;background:#10b981;color:white;padding:14px 24px;border-radius:50px;font-weight:600;font-family:'DM Sans',sans-serif;font-size:0.95rem;z-index:99999;box-shadow:0 4px 20px rgba(16,185,129,0.4);max-width:360px;`;
        toast.innerHTML = `✅ ${nom} — Taille ${sel.taille} / ${sel.couleur} ajouté au panier !`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
        <?php else: ?>
        window.location.href = '../../auth/login.php';
        <?php endif; ?>
    }

    function lancerRecherche() {
        const q = document.getElementById('search-input').value.trim();
        if (q) window.location.href = `../../search.php?q=${encodeURIComponent(q)}`;
    }
    document.getElementById('search-input')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') lancerRecherche();
    });

    function toggleTheme() {
        const body = document.body;
        const btn  = document.getElementById('theme-toggle');
        body.classList.toggle('dark');
        if (body.classList.contains('dark')) { btn.textContent='☀️'; localStorage.setItem('theme','dark'); }
        else { btn.textContent='🌙'; localStorage.setItem('theme','light'); }
    }
    (function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            const btn = document.getElementById('theme-toggle');
            if (btn) btn.textContent = '☀️';
        }
    })();
</script>
</body>
</html>