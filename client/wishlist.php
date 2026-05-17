<?php


session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../config/db.php';
$pdo = getDB();

if (isset($_GET['vider'])) {
    $pdo->prepare('DELETE FROM wishlist WHERE utilisateur_id = ?')->execute([$_SESSION['user_id']]);
    header('Location: wishlist.php');
    exit;
}

if (isset($_GET['retirer']) && is_numeric($_GET['retirer'])) {
    $pdo->prepare('DELETE FROM wishlist WHERE utilisateur_id = ? AND produit_id = ?')
        ->execute([$_SESSION['user_id'], $_GET['retirer']]);
    header('Location: wishlist.php');
    exit;
}

if (isset($_GET['panier']) && is_numeric($_GET['panier'])) {
    $produit_id = intval($_GET['panier']);
    $stmt = $pdo->prepare('SELECT stock FROM produits WHERE id = ? AND actif = 1');
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch();

    if ($produit && $produit['stock'] > 0) {
        $stmt = $pdo->prepare('SELECT id FROM panier WHERE utilisateur_id = ? AND produit_id = ?');
        $stmt->execute([$_SESSION['user_id'], $produit_id]);
        if ($stmt->fetch()) {
            $pdo->prepare('UPDATE panier SET quantite = quantite + 1 WHERE utilisateur_id = ? AND produit_id = ?')
                ->execute([$_SESSION['user_id'], $produit_id]);
        } else {
            $pdo->prepare('INSERT INTO panier (utilisateur_id, produit_id, quantite) VALUES (?, ?, 1)')
                ->execute([$_SESSION['user_id'], $produit_id]);
        }
    }
    header('Location: wishlist.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT p.*, c.nom AS categorie
    FROM wishlist w
    JOIN produits p ON p.id = w.produit_id
    JOIN categories c ON c.id = p.categorie_id
    WHERE w.utilisateur_id = ?
    ORDER BY w.added_at DESC
');
$stmt->execute([$_SESSION['user_id']]);
$favoris = $stmt->fetchAll();

$stmt2 = $pdo->prepare('SELECT COALESCE(SUM(quantite),0) FROM panier WHERE utilisateur_id=?');
$stmt2->execute([$_SESSION['user_id']]);
$nb_panier = intval($stmt2->fetchColumn());
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Favoris – NovaStore</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700;6..12,800&family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f1f5f9; }
        .page { max-width: 1100px; margin: 40px auto; padding: 0 20px; }

        .wishlist-header {
            background: linear-gradient(135deg, #1D3557, #2d4a6b);
            border-radius: 16px; padding: 32px 40px;
            margin-bottom: 28px; color: white;
            display: flex; align-items: center; justify-content: space-between;
        }
        .wishlist-header h1 {
            font-family: 'Nunito', sans-serif;
            font-size: 1.8rem; font-weight: 900; margin-bottom: 6px;
        }
        .wishlist-header p { color: rgba(255,255,255,0.75); font-size: 0.9rem; }
        .wishlist-count {
            background: rgba(255,255,255,0.15);
            border-radius: 12px; padding: 16px 24px;
            text-align: center; flex-shrink: 0;
        }
        .wishlist-count .num { font-size: 2rem; font-weight: 900; font-family: 'Nunito', sans-serif; }
        .wishlist-count .lbl { font-size: 0.8rem; color: rgba(255,255,255,0.75); }

        .actions-bar {
            display: flex; align-items: center; justify-content: space-between;
            background: white; border-radius: 12px; padding: 14px 20px;
            margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .actions-bar span { font-weight: 600; color: #6c757d; font-size: 0.9rem; }
        .actions-bar strong { color: #1D3557; }

        .btn-vider {
            display: flex; align-items: center; gap: 6px;
            background: none; border: 2px solid #ef4444; color: #ef4444;
            padding: 7px 16px; border-radius: 8px; cursor: pointer;
            font-weight: 700; font-size: 0.85rem;
            font-family: 'Nunito Sans', sans-serif; transition: 0.2s;
            text-decoration: none;
        }
        .btn-vider:hover { background: #ef4444; color: white; }

        .favoris-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .favori-card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            overflow: hidden; position: relative;
            border: 1px solid #f0f0f0;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex; flex-direction: column;
        }
        .favori-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }

        .stock-badge {
            position: absolute; top: 10px; left: 10px; z-index: 5;
            background: #ef4444; color: white;
            font-size: 0.7rem; font-weight: 800;
            padding: 3px 8px; border-radius: 20px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        .btn-retirer-card {
            position: absolute; top: 10px; right: 10px; z-index: 5;
            width: 34px; height: 34px; border-radius: 50%;
            background: white; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: #E63946; font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: 0.2s; text-decoration: none;
        }
        .btn-retirer-card:hover { background: #E63946; color: white; transform: scale(1.1); }

        .favori-img {
            height: 180px; display: flex; align-items: center;
            justify-content: center; padding: 16px;
            background: #f8f9fa;
        }
        .favori-img img { max-width: 100%; max-height: 100%; object-fit: contain; }

        .favori-body { padding: 14px; flex: 1; display: flex; flex-direction: column; }

        .favori-cat {
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.8px; color: #6c757d; margin-bottom: 4px;
        }
        .favori-nom {
            font-weight: 700; font-size: 0.95rem; color: #1D3557;
            margin-bottom: 3px; line-height: 1.3;
        }
        .favori-modele { font-size: 0.8rem; color: #888; margin-bottom: 10px; flex: 1; }

        .favori-prix {
            font-family: 'Nunito', sans-serif;
            font-size: 1.3rem; font-weight: 900;
            color: #007bff; margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        .favori-prix small { font-size: 0.8rem; font-weight: 700; }

        .favori-actions { display: flex; gap: 8px; }

        .btn-panier {
            flex: 1; padding: 9px 12px;
            background: #1D3557; color: white;
            border: none; border-radius: 8px;
            font-family: 'Nunito Sans', sans-serif;
            font-weight: 700; font-size: 0.82rem;
            cursor: pointer; transition: 0.2s;
            display: flex; align-items: center;
            justify-content: center; gap: 6px;
            text-decoration: none;
        }
        .btn-panier:hover { background: #E63946; }
        .btn-panier.disabled {
            background: #adb5bd; cursor: not-allowed;
            pointer-events: none;
        }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 80px 20px;
            background: white; border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .empty-state i { font-size: 4rem; color: #dee2e6; display: block; margin-bottom: 20px; }
        .empty-state h3 { font-size: 1.3rem; color: #374151; margin-bottom: 8px; font-family: 'Nunito', sans-serif; font-weight: 800; }
        .empty-state p { color: #6c757d; margin-bottom: 24px; }
        .btn-shop {
            display: inline-flex; align-items: center; gap: 8px;
            background: #E63946; color: white; padding: 12px 28px;
            border-radius: 50px; text-decoration: none;
            font-weight: 700; font-size: 0.95rem; transition: 0.2s;
        }
        .btn-shop:hover { background: #c1121f; }
    </style>
</head>
<body>

<header class="navbar">
    <div class="nav-container">
        <a href="../index.php" class="logo" style="text-decoration:none;">
            <img src="../images/logo.png" alt="NovaStore" class="logo-img">
            Nova<strong>Store</strong>
        </a>
        <nav class="nav-actions">
            <a href="../index.php" class="btn-nav"><i class="fas fa-store"></i> Accueil</a>
            <a href="profil.php" class="btn-nav"><i class="fas fa-user"></i> Mon profil</a>
            <a href="commandes.php" class="btn-nav"><i class="fas fa-box"></i> Commandes</a>
            <a href="panier.php" class="btn-nav btn-primary">
                <i class="fas fa-shopping-cart"></i> Panier
                <?php if ($nb_panier > 0): ?>
                <span style="background:white;color:#E63946;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;margin-left:4px;">
                    <?= $nb_panier ?>
                </span>
                <?php endif; ?>
            </a>
            <a href="../auth/logout.php" class="btn-nav" style="color:#E63946;">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </nav>
    </div>
</header>

<div class="page">

    <div class="wishlist-header">
        <div>
            <h1><i class="fas fa-heart" style="margin-right:10px;color:#E63946;"></i>Mes Favoris</h1>
            <p>Retrouvez ici tous les produits que vous avez ajoutés à votre wishlist.</p>
        </div>
        <div class="wishlist-count">
            <div class="num"><?= count($favoris) ?></div>
            <div class="lbl">Favori<?= count($favoris) > 1 ? 's' : '' ?></div>
        </div>
    </div>

    <?php if (empty($favoris)): ?>

    <div class="empty-state">
        <i class="fas fa-heart-broken"></i>
        <h3>Votre liste de favoris est vide</h3>
        <p>Ajoutez des produits à vos favoris en cliquant sur le cœur ❤️ sur les fiches produits.</p>
        <a href="../index.php" class="btn-shop">
            <i class="fas fa-store"></i> Découvrir nos produits
        </a>
    </div>

    <?php else: ?>

    <div class="actions-bar">
        <span><strong><?= count($favoris) ?></strong> produit<?= count($favoris) > 1 ? 's' : '' ?> dans vos favoris</span>
        <a href="wishlist.php?vider=1" class="btn-vider"
           onclick="return confirm('Vider toute la liste de favoris ?')">
            <i class="fas fa-trash"></i> Tout vider
        </a>
    </div>

    <div class="favoris-grid">
        <?php foreach ($favoris as $p):
            $prix_parts = explode('.', number_format($p['prix'], 3, '.', ''));
        ?>
        <div class="favori-card">

            <?php if ($p['stock'] <= 0): ?>
            <div class="stock-badge">Épuisé</div>
            <?php endif; ?>

            <a href="wishlist.php?retirer=<?= $p['id'] ?>" class="btn-retirer-card"
               title="Retirer des favoris"
               onclick="return confirm('Retirer ce produit des favoris ?')">
                <i class="fas fa-heart-broken"></i>
            </a>

            <div class="favori-img">
                <img src="../<?= htmlspecialchars($p['image'] ?? 'images/placeholder.jpg') ?>"
                     alt="<?= htmlspecialchars($p['nom']) ?>">
            </div>

            <div class="favori-body">
                <div class="favori-cat"><?= htmlspecialchars($p['categorie']) ?></div>

                <?php if ($p['marque']): ?>
                <span class="brand-tag"><?= htmlspecialchars($p['marque']) ?></span>
                <?php endif; ?>

                <div class="favori-nom"><?= htmlspecialchars($p['nom']) ?></div>

                <?php if ($p['modele']): ?>
                <div class="favori-modele"><?= htmlspecialchars($p['modele']) ?></div>
                <?php endif; ?>

                <div class="favori-prix">
                    <?= $prix_parts[0] ?><small>.<?= $prix_parts[1] ?? '000' ?> DT</small>
                </div>

                <div class="favori-actions">
                    <?php if ($p['stock'] > 0): ?>
                    <a href="wishlist.php?panier=<?= $p['id'] ?>" class="btn-panier">
                        <i class="fas fa-shopping-cart"></i> Ajouter au panier
                    </a>
                    <?php else: ?>
                    <button class="btn-panier disabled" disabled>
                        <i class="fas fa-times-circle"></i> Épuisé
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<footer class="footer" style="margin-top:60px;">
    <div class="container">
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> NovaStore. Tous droits réservés.</p>
        </div>
    </div>
</footer>

</body>
</html>