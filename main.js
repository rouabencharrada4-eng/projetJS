
document.addEventListener('DOMContentLoaded', function () {
    if (typeof fetch === 'undefined') return;


    const BASE = window.BASE_URL || '';

    chargerStatut();

    function chargerStatut() {
        fetch(BASE + 'api/cart_status.php')
            .then(r => r.json())
            .then(data => {
                if (data.connecte) {
                    mettreAJourCompteurPanier(data.total_panier);

                    if (data.wishlist_ids && data.wishlist_ids.length > 0) {
                        data.wishlist_ids.forEach(id => {
                            const btn = document.querySelector(`.wishlist-btn[data-id="${id}"]`);

                            if (btn) {
                                btn.classList.add('active');

                                const icon = btn.querySelector('i');
                                if (icon) icon.classList.replace('far', 'fas');
                            }
                        });
                    }

                    mettreAJourNavbar(data.nom, data.total_panier);
                }
            })
            .catch(err => console.log('Statut non disponible:', err));
    }


    document.querySelectorAll('.btn-cart-icon').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const produitId = this.dataset.id;

            if (!produitId || isNaN(parseInt(produitId)) || parseInt(produitId) <= 0) {
                afficherToast('Produit non disponible.', 'warning');
                return;
            }

            const btnOriginal = this;
            const ancienHTML = btnOriginal.innerHTML;

            btnOriginal.disabled = true;
            btnOriginal.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(BASE + 'api/add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    produit_id: parseInt(produitId),
                    quantite: 1
                })
            })
                .then(r => {
                    if (!r.ok) {
                        throw new Error('Erreur HTTP ' + r.status);
                    }

                    return r.json();
                })
                .then(data => {
                    if (data.redirect) {
                        window.location.href = BASE + data.redirect.replace('../', '');
                        return;
                    }

                    if (data.success) {
                        afficherToast(data.message || 'Produit ajouté au panier !', 'success');
                        mettreAJourCompteurPanier(data.total_panier || 0);

                        btnOriginal.innerHTML = '<i class="fas fa-check"></i>';

                        setTimeout(() => {
                            btnOriginal.innerHTML = ancienHTML || '<i class="fas fa-shopping-cart"></i>';
                            btnOriginal.disabled = false;
                        }, 1500);
                    } else {
                        afficherToast(data.message || 'Erreur ajout au panier.', 'error');
                        btnOriginal.innerHTML = ancienHTML || '<i class="fas fa-shopping-cart"></i>';
                        btnOriginal.disabled = false;
                    }
                })
                .catch(err => {
                    console.error('Erreur ajout panier:', err);
                    afficherToast('Erreur de connexion avec le panier.', 'error');

                    btnOriginal.innerHTML = ancienHTML || '<i class="fas fa-shopping-cart"></i>';
                    btnOriginal.disabled = false;
                });
        });
    });


    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const produitId = this.dataset.id;

            if (!produitId || isNaN(parseInt(produitId)) || parseInt(produitId) <= 0) {
                afficherToast('Produit non configuré.', 'warning');
                return;
            }

            fetch(BASE + 'api/wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    produit_id: parseInt(produitId)
                })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.redirect) {
                        window.location.href = BASE + data.redirect.replace('../', '');
                        return;
                    }

                    if (data.success) {
                        const icon = this.querySelector('i');

                        if (data.active) {
                            this.classList.add('active');
                            if (icon) icon.classList.replace('far', 'fas');
                        } else {
                            this.classList.remove('active');
                            if (icon) icon.classList.replace('fas', 'far');
                        }

                        afficherToast(data.message || 'Wishlist mise à jour.', 'success');
                    } else {
                        afficherToast(data.message || 'Erreur wishlist.', 'error');
                    }
                })
                .catch(() => afficherToast('Erreur de connexion.', 'error'));
        });
    });

    const searchInput = document.querySelector('.nav-search input');
    const searchBtn = document.querySelector('.nav-search button');

    if (searchInput) {
        let timeout;

        searchInput.addEventListener('input', function () {
            clearTimeout(timeout);

            const q = this.value.trim();

            if (q.length < 2) {
                fermerResultats();
                return;
            }

            timeout = setTimeout(() => rechercherProduits(q), 300);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                const q = this.value.trim();

                if (q) {
                    window.location.href = BASE + 'search.php?q=' + encodeURIComponent(q);
                }
            }

            if (e.key === 'Escape') {
                fermerResultats();
            }
        });

        document.addEventListener('click', function (e) {
            const box = searchInput.closest('.nav-search');

            if (box && !box.contains(e.target)) {
                fermerResultats();
            }
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            const q = searchInput?.value.trim();

            if (q) {
                window.location.href = BASE + 'search.php?q=' + encodeURIComponent(q);
            }
        });
    }

    function rechercherProduits(q) {
        fetch(BASE + 'api/search.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => afficherResultats(data.produits, q))
            .catch(() => { });
    }

    function afficherResultats(produits, q) {
        let dropdown = document.getElementById('search-dropdown');

        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.id = 'search-dropdown';

            dropdown.style.cssText = `
                position: absolute;
                top: calc(100% + 10px);
                left: 0;
                right: 0;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 18px;
                max-height: 360px;
                overflow-y: auto;
                z-index: 99999;
                box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
                padding: 8px;
                font-family: 'DM Sans', sans-serif;
            `;

            const searchBox = document.querySelector('.nav-search');

            if (searchBox) {
                searchBox.style.position = 'relative';
                searchBox.appendChild(dropdown);
            }
        }

        if (!produits || produits.length === 0) {
            dropdown.innerHTML = `
                <div style="
                    padding: 22px 18px;
                    text-align: center;
                    color: #64748b;
                    font-size: 0.9rem;
                ">
                    <i class="fas fa-search" style="
                        display:block;
                        font-size:1.45rem;
                        margin-bottom:8px;
                        color:#cbd5e1;
                    "></i>
                    Aucun produit trouvé pour <strong>"${escapeHtml(q)}"</strong>
                </div>
            `;
            return;
        }

        dropdown.innerHTML = produits.slice(0, 6).map(p => {
            const image = BASE + (p.image || 'images/placeholder.jpg');
            const prix = parseFloat(p.prix || 0).toFixed(3);
            const marque = p.marque || 'NovaStore';
            const categorie = p.categorie || '';
            const nom = p.nom || '';

            return `
                <div class="search-result-item"
                     onclick="window.location.href='${BASE}search.php?q=${encodeURIComponent(nom)}'"
                     style="
                        display:flex;
                        align-items:center;
                        gap:12px;
                        padding:10px 12px;
                        border-radius:14px;
                        cursor:pointer;
                        transition:background 0.2s ease, transform 0.2s ease;
                     "
                     onmouseover="this.style.background='#f8fafc'; this.style.transform='translateX(2px)'"
                     onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">

                    <div style="width:45px;height:45px;border-radius:8px;background:#f8fafc;flex-shrink:0;overflow:hidden;">
                        <img src="${image}" alt=""
                             style="width:100%;height:100%;object-fit:contain;display:block;">
                    </div>

                    <div style="flex:1; min-width:0;">
                        <div style="
                            font-size:0.72rem;
                            color:#E63946;
                            font-weight:900;
                            text-transform:uppercase;
                            letter-spacing:0.35px;
                            white-space:nowrap;
                            overflow:hidden;
                            text-overflow:ellipsis;
                            margin-bottom:2px;
                        ">
                            ${escapeHtml(marque)}
                        </div>

                        <div style="
                            font-size:0.92rem;
                            color:#1D3557;
                            font-weight:900;
                            white-space:nowrap;
                            overflow:hidden;
                            text-overflow:ellipsis;
                        ">
                            ${escapeHtml(nom)}
                        </div>

                        <div style="
                            font-size:0.76rem;
                            color:#64748b;
                            white-space:nowrap;
                            overflow:hidden;
                            text-overflow:ellipsis;
                            margin-top:2px;
                        ">
                            ${escapeHtml(categorie)}
                        </div>
                    </div>

                    <div style="
                        font-weight:900;
                        color:#007bff;
                        font-size:0.88rem;
                        white-space:nowrap;
                    ">
                        ${prix} DT
                    </div>
                </div>
            `;
        }).join('');

        dropdown.innerHTML += `
            <div style="
                border-top:1px solid #f1f5f9;
                margin-top:6px;
                padding-top:8px;
            ">
                <button type="button"
                    onclick="window.location.href='${BASE}search.php?q=${encodeURIComponent(q)}'"
                    style="
                        width:100%;
                        border:none;
                        background:#1D3557;
                        color:white;
                        padding:10px 14px;
                        border-radius:13px;
                        font-weight:900;
                        cursor:pointer;
                        font-family:'DM Sans', sans-serif;
                        font-size:0.88rem;
                        transition:0.2s;
                    "
                    onmouseover="this.style.background='#E63946'"
                    onmouseout="this.style.background='#1D3557'">
                    Voir tous les résultats
                </button>
            </div>
        `;
    }

    function fermerResultats() {
        const dropdown = document.getElementById('search-dropdown');
        if (dropdown) dropdown.remove();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }


    function mettreAJourCompteurPanier(total) {
        total = parseInt(total) || 0;

        let badge = document.getElementById('panier-badge');

        if (!badge) {
            const lienPanier = document.querySelector('a[href*="panier"]');

            if (lienPanier) {
                badge = document.createElement('span');
                badge.id = 'panier-badge';

                badge.style.cssText = `
                    background: #E63946;
                    color: white;
                    border-radius: 50%;
                    width: 20px;
                    height: 20px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 0.72rem;
                    font-weight: 700;
                    margin-left: 4px;
                `;

                lienPanier.appendChild(badge);
            }
        }

        if (badge) {
            badge.textContent = total > 0 ? total : '';
            badge.style.display = total > 0 ? 'inline-flex' : 'none';
        }
    }

    function mettreAJourNavbar(prenom, totalPanier) {
        const btnConnexion = document.querySelector('a[href*="login"]');

        if (btnConnexion && prenom) {
            btnConnexion.innerHTML = '<i class="fas fa-user"></i> ' + prenom;
            btnConnexion.href = BASE + 'client/profil.php';
        }

        const btnInscription = document.querySelector('a[href*="register"]');

        if (btnInscription && prenom) {
            btnInscription.innerHTML = '<i class="fas fa-shopping-cart"></i> Panier';
            btnInscription.href = BASE + 'client/panier.php';
            mettreAJourCompteurPanier(totalPanier);
        }
    }


    function afficherToast(message, type) {
        type = type || 'success';

        const ancien = document.getElementById('nova-toast');

        if (ancien) {
            ancien.remove();
        }

        const colors = {
            success: { bg: '#10b981', shadow: 'rgba(16,185,129,0.4)' },
            error: { bg: '#ef4444', shadow: 'rgba(239,68,68,0.4)' },
            warning: { bg: '#f59e0b', shadow: 'rgba(245,158,11,0.4)' }
        };

        const c = colors[type] || colors.success;

        const toast = document.createElement('div');
        toast.id = 'nova-toast';

        toast.style.cssText = `
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: ${c.bg};
            color: white;
            padding: 14px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            box-shadow: 0 4px 20px ${c.shadow};
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: 360px;
        `;

        toast.innerHTML = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';

            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }


    window.toggleTheme = function () {
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
    };

    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');

        const btn = document.getElementById('theme-toggle');

        if (btn) {
            btn.textContent = '☀️';
        }
    }

    window.showSub = function (section) {
        document.querySelectorAll('.submenu-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.submenu-left-item').forEach(i => i.classList.remove('active'));

        const el = document.getElementById('sub-' + section);

        if (el) {
            el.classList.add('active');
        }

        if (window.event && window.event.currentTarget) {
            window.event.currentTarget.classList.add('active');
        }
    };

    if (window.location.pathname.endsWith('/search.php') || window.location.pathname.endsWith('search.php')) {
        document.body.classList.add('search-page-compact');

        const style = document.createElement('style');
        style.id = 'search-page-compact-style';

        style.textContent = `
            body.search-page-compact {
                background: #f8fafc;
            }

            body.search-page-compact .product-section,
            body.search-page-compact section {
                padding-top: 34px !important;
            }

            body.search-page-compact .container {
                max-width: none !important;
                width: 100% !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding-left: 32px !important;
                padding-right: 24px !important;
            }

            body.search-page-compact .products-grid {
                display: grid !important;
                grid-template-columns: repeat(auto-fill, minmax(230px, 270px)) !important;
                gap: 24px !important;
                justify-content: flex-start !important;
                align-items: start !important;
                max-width: none !important;
                width: 100% !important;
                margin-left: 0 !important;
                margin-right: auto !important;
                padding: 0 0 50px 0 !important;
            }

            body.search-page-compact .product-card {
                width: 100% !important;
                max-width: 270px !important;
                min-height: auto !important;
                border-radius: 18px !important;
                overflow: hidden !important;
                background: #ffffff !important;
                box-shadow: 0 8px 26px rgba(15, 23, 42, 0.08) !important;
                border: 1px solid #eef2f7 !important;
                transition: transform 0.22s ease, box-shadow 0.22s ease !important;
            }

            body.search-page-compact .product-card:hover {
                transform: translateY(-5px) !important;
                box-shadow: 0 16px 38px rgba(15, 23, 42, 0.13) !important;
            }

            body.search-page-compact .product-img-box {
                height: 155px !important;
                width: 100% !important;
                background: linear-gradient(180deg, #ffffff, #f8fafc) !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                overflow: hidden !important;
                padding: 12px !important;
                border-bottom: 1px solid #f1f5f9 !important;
            }

            body.search-page-compact .product-img-box img {
                width: 100% !important;
                height: 100% !important;
                max-width: 135px !important;
                max-height: 125px !important;
                object-fit: contain !important;
                object-position: center !important;
                display: block !important;
            }

            body.search-page-compact .product-info {
                padding: 16px !important;
            }

            body.search-page-compact .product-footer-price {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 12px !important;
                margin-bottom: 12px !important;
            }

            body.search-page-compact .price-container {
                display: flex !important;
                align-items: baseline !important;
                gap: 3px !important;
                color: #007bff !important;
            }

            body.search-page-compact .price-main {
                font-size: 1.45rem !important;
                font-weight: 900 !important;
                line-height: 1 !important;
            }

            body.search-page-compact .price-currency,
            body.search-page-compact .price-cents {
                font-size: 0.78rem !important;
                font-weight: 800 !important;
            }

            body.search-page-compact .btn-cart-icon {
                width: 42px !important;
                height: 42px !important;
                border-radius: 12px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                flex-shrink: 0 !important;
                background: #1D3557 !important;
                color: white !important;
                border: none !important;
            }

            body.search-page-compact .btn-cart-icon:hover {
                background: #E63946 !important;
            }

            body.search-page-compact .brand-tag,
            body.search-page-compact .product-badge {
                font-size: 0.72rem !important;
                padding: 4px 9px !important;
                border-radius: 7px !important;
            }

            body.search-page-compact .product-name {
                font-size: 0.98rem !important;
                line-height: 1.35 !important;
                margin: 8px 0 4px !important;
                color: #1D3557 !important;
                font-weight: 800 !important;
            }

            body.search-page-compact .product-model {
                font-size: 0.8rem !important;
                color: #64748b !important;
                line-height: 1.4 !important;
                min-height: 18px !important;
            }

            body.search-page-compact .rating {
                font-size: 0.82rem !important;
                margin-top: 8px !important;
            }

            body.search-page-compact h1,
            body.search-page-compact .page-title {
                font-size: 2rem !important;
                margin-bottom: 10px !important;
            }

            @media (max-width: 768px) {
                body.search-page-compact .container {
                    padding-left: 14px !important;
                    padding-right: 14px !important;
                }

                body.search-page-compact .products-grid {
                    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)) !important;
                    justify-content: flex-start !important;
                }

                body.search-page-compact .product-card {
                    max-width: none !important;
                }

                body.search-page-compact .product-img-box {
                    height: 145px !important;
                }
            }
        `;

        document.head.appendChild(style);
    }
});