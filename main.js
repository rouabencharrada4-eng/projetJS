// ============================================================
//   NOVASTORE - main.js (VERSION CORRIGÉE)
//   Connecte les boutons panier + wishlist aux APIs PHP
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    if (typeof fetch === 'undefined') return;

    // =========================================================
    // 1. CHARGER LE STATUT DU PANIER AU CHARGEMENT
    // =========================================================
    chargerStatut();

    function chargerStatut() {
        fetch('api/cart_status.php')
            .then(r => r.json())
            .then(data => {
                if (data.connecte) {
                    mettreAJourCompteurPanier(data.total_panier);
                    if (data.wishlist_ids && data.wishlist_ids.length > 0) {
                        data.wishlist_ids.forEach(id => {
                            const btn = document.querySelector(`.wishlist-btn[data-id="${id}"]`);
                            if (btn) {
                                btn.classList.add('active');
                                btn.querySelector('i').classList.replace('far', 'fas');
                            }
                        });
                    }
                    mettreAJourNavbar(data.nom, data.total_panier);
                }
            })
            .catch(err => console.log('Statut non disponible:', err));
    }

    // =========================================================
    // 2. BOUTONS AJOUTER AU PANIER
    // =========================================================
    document.querySelectorAll('.btn-cart-icon').forEach(btn => {
        btn.addEventListener('click', function () {
            const produitId = this.dataset.id;

            if (!produitId) {
                afficherToast('⚠️ Produit non configuré.', 'warning');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            const btnOriginal = this;

            fetch('api/add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ produit_id: parseInt(produitId), quantite: 1 })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }
                    if (data.success) {
                        afficherToast(data.message, 'success');
                        mettreAJourCompteurPanier(data.total_panier);
                        btnOriginal.innerHTML = '<i class="fas fa-check"></i>';
                        setTimeout(() => {
                            btnOriginal.innerHTML = '<i class="fas fa-shopping-cart"></i>';
                            btnOriginal.disabled = false;
                        }, 1500);
                    } else {
                        afficherToast(data.message || 'Erreur.', 'error');
                        btnOriginal.innerHTML = '<i class="fas fa-shopping-cart"></i>';
                        btnOriginal.disabled = false;
                    }
                })
                .catch(() => {
                    afficherToast('Erreur de connexion.', 'error');
                    btnOriginal.innerHTML = '<i class="fas fa-shopping-cart"></i>';
                    btnOriginal.disabled = false;
                });
        });
    });

    // =========================================================
    // 3. BOUTONS WISHLIST (COEUR)
    // =========================================================
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const produitId = this.dataset.id;

            if (!produitId) {
                afficherToast('⚠️ Produit non configuré.', 'warning');
                return;
            }

            fetch('api/wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ produit_id: parseInt(produitId) })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }
                    if (data.success) {
                        const icon = this.querySelector('i');
                        if (data.active) {
                            this.classList.add('active');
                            icon.classList.replace('far', 'fas');
                        } else {
                            this.classList.remove('active');
                            icon.classList.replace('fas', 'far');
                        }
                        afficherToast(data.message, 'success');
                    } else {
                        afficherToast(data.message || 'Erreur.', 'error');
                    }
                })
                .catch(() => afficherToast('Erreur de connexion.', 'error'));
        });
    });

    // =========================================================
    // 4. RECHERCHE EN TEMPS RÉEL
    // =========================================================
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
            if (e.key === 'Enter') rechercherProduits(this.value.trim());
            if (e.key === 'Escape') fermerResultats();
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.closest('.nav-search').contains(e.target)) {
                fermerResultats();
            }
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            const q = searchInput?.value.trim();
            if (q) rechercherProduits(q);
        });
    }

    function rechercherProduits(q) {
        fetch(`api/search.php?q=${encodeURIComponent(q)}`)
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
                position: absolute; top: 100%; left: 0; right: 0;
                background: white; border: 2px solid #e9ecef;
                border-top: none; border-radius: 0 0 12px 12px;
                max-height: 400px; overflow-y: auto;
                z-index: 9999; box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            `;
            const searchBox = document.querySelector('.nav-search');
            if (searchBox) {
                searchBox.style.position = 'relative';
                searchBox.appendChild(dropdown);
            }
        }

        if (produits.length === 0) {
            dropdown.innerHTML = `
                <div style="padding:20px; text-align:center; color:#6c757d; font-size:0.9rem;">
                    <i class="fas fa-search" style="margin-right:8px;"></i>
                    Aucun produit trouvé pour "${q}"
                </div>`;
            return;
        }

        dropdown.innerHTML = produits.map(p => `
            <div style="display:flex; align-items:center; gap:12px; padding:12px 16px;
                        border-bottom:1px solid #f1f5f9; cursor:pointer;"
                 onmouseover="this.style.background='#f8f9fa'"
                 onmouseout="this.style.background='white'">
                <img src="${p.image || 'images/placeholder.jpg'}" alt=""
                     style="width:45px; height:45px; object-fit:contain; border-radius:6px; background:#f8f9fa; flex-shrink:0;">
                <div style="flex:1;">
                    <div style="font-size:0.75rem; color:#E63946; font-weight:700;">${p.marque || ''}</div>
                    <div style="font-weight:600; color:#1D3557; font-size:0.9rem;">${p.nom}</div>
                    <div style="font-size:0.75rem; color:#6c757d;">${p.categorie}</div>
                </div>
                <div style="font-weight:700; color:#007bff; white-space:nowrap;">
                    ${parseFloat(p.prix).toFixed(3)} DT
                </div>
            </div>
        `).join('');
    }

    function fermerResultats() {
        const dropdown = document.getElementById('search-dropdown');
        if (dropdown) dropdown.remove();
    }

    // =========================================================
    // 5. COMPTEUR PANIER DANS LA NAVBAR
    // =========================================================
    function mettreAJourCompteurPanier(total) {
        let badge = document.getElementById('panier-badge');
        if (!badge) {
            const lienPanier = document.querySelector('a[href*="panier"]');
            if (lienPanier) {
                badge = document.createElement('span');
                badge.id = 'panier-badge';
                badge.style.cssText = `
                    background: #E63946; color: white; border-radius: 50%;
                    width: 20px; height: 20px; display: inline-flex;
                    align-items: center; justify-content: center;
                    font-size: 0.72rem; font-weight: 700; margin-left: 4px;
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
            btnConnexion.innerHTML = `<i class="fas fa-user"></i> ${prenom}`;
            btnConnexion.href = 'client/profil.php';
        }
        const btnInscription = document.querySelector('a[href*="register"]');
        if (btnInscription && prenom) {
            btnInscription.innerHTML = `<i class="fas fa-shopping-cart"></i> Panier`;
            btnInscription.href = 'client/panier.php';
            mettreAJourCompteurPanier(totalPanier);
        }
    }

    // =========================================================
    // 6. TOAST NOTIFICATIONS
    // =========================================================
    function afficherToast(message, type = 'success') {
        const ancien = document.getElementById('nova-toast');
        if (ancien) ancien.remove();

        const colors = {
            success: { bg: '#10b981', shadow: 'rgba(16,185,129,0.4)' },
            error: { bg: '#ef4444', shadow: 'rgba(239,68,68,0.4)' },
            warning: { bg: '#f59e0b', shadow: 'rgba(245,158,11,0.4)' },
        };
        const c = colors[type] || colors.success;

        const toast = document.createElement('div');
        toast.id = 'nova-toast';
        toast.style.cssText = `
            position: fixed; bottom: 30px; right: 30px;
            background: ${c.bg}; color: white;
            padding: 14px 24px; border-radius: 50px;
            font-weight: 600; font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem; box-shadow: 0 4px 20px ${c.shadow};
            z-index: 99999; display: flex; align-items: center;
            gap: 8px; max-width: 360px;
        `;
        toast.innerHTML = message;
        document.body.appendChild(toast);

        if (!document.getElementById('toast-style')) {
            const style = document.createElement('style');
            style.id = 'toast-style';
            style.textContent = `
                @keyframes slideInToast {
                    from { transform: translateY(20px); opacity: 0; }
                    to   { transform: translateY(0);    opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        }

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

}); // FIN DOMContentLoaded