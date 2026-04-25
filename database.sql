CREATE DATABASE IF NOT EXISTS novastore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE novastore;


CREATE TABLE utilisateurs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(100)        NOT NULL,
    prenom        VARCHAR(100)        NOT NULL,
    email         VARCHAR(150)        NOT NULL UNIQUE,
    mot_de_passe  VARCHAR(255)        NOT NULL,          -- bcrypt hash
    telephone     VARCHAR(20)         DEFAULT NULL,
    role          ENUM('client','admin') NOT NULL DEFAULT 'client',
    actif         TINYINT(1)          NOT NULL DEFAULT 1,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE categories (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(100)        NOT NULL,
    icone         VARCHAR(10)         DEFAULT NULL,       
    slug          VARCHAR(120)        NOT NULL UNIQUE,
    ordre         INT                 NOT NULL DEFAULT 0,
    active        TINYINT(1)          NOT NULL DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO categories (nom, icone, slug, ordre) VALUES
('Alimentaire',    '🥗', 'alimentaire',    1),
('Électroménager', '⚡', 'electromenager', 2),
('Cosmétiques',    '✨', 'cosmetiques',    3),
('Vêtements',      '👗', 'vetements',      4),
('Jeux',           '🎮', 'jeux',           5),
('Ustensiles',     '🍴', 'ustensiles',     6),
('Nettoyage',      '🧹', 'nettoyage',      7);


CREATE TABLE produits (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id  INT                 NOT NULL,
    marque        VARCHAR(100)        DEFAULT NULL,
    nom           VARCHAR(200)        NOT NULL,
    description   TEXT                DEFAULT NULL,
    modele        VARCHAR(200)        DEFAULT NULL,       
    prix          DECIMAL(10,3)       NOT NULL,           
    stock         INT                 NOT NULL DEFAULT 0,
    image         VARCHAR(255)        DEFAULT NULL,       
    badge         VARCHAR(80)         DEFAULT NULL,       
    note_moyenne  DECIMAL(2,1)        NOT NULL DEFAULT 0.0,
    nb_avis       INT                 NOT NULL DEFAULT 0,
    actif         TINYINT(1)          NOT NULL DEFAULT 1,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_produit_categorie FOREIGN KEY (categorie_id)
        REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;


INSERT INTO produits (categorie_id, marque, nom, modele, prix, stock, image, note_moyenne, nb_avis) VALUES
-- Électroménager / Ustensiles
(2, 'BRAUN',    'Cafetière électrique',          'Puissance 1000W - 10 tasses',          165.000, 20, 'images/machine-à-café.jpg', 4.0, 12),
(2, 'TECHWOOD', 'Airfryer Essential XL 4.1L',    'Capacité 4.1L - 1400W',                235.900, 15, 'images/friteuse.jpg',        5.0, 34),
(2, 'MOULINEX', 'Masterchef Essential 4.8L',     'Bol inox - 800W',                      189.000, 8,  'images/robot.jpg',           4.0, 9),
(6, 'KENWOOD',  'Mixeur Plongeant Triblade',      'Moteur puissant - Accessoires inclus', 120.000, 25, 'images/mixeur.jpg',          4.0, 17),
-- Alimentaire
(1, 'EL MAZRAA','Plateau d\'œufs frais',          'Le plateau de 30 pièces',              13.500, 100,'images/plateau.jpg',         5.0, 88),
(1, 'FRUITS',   'Bananes Importées (1kg)',         'Origine : Équateur',                   20.000, 50, 'images/banan.jpg',           4.0, 42),
(1, 'DELICE',   'Lait',                            '100ml',                                2.990,  200,'images/lait.jpg',            0.0, 0),
(1, 'BOULANGERIE','Pain de mie complet (Toast)',  'Sachet de 500g',                        2.350, 120, 'images/toast.jpg',           4.0, 23),
-- Nettoyage
(7, 'JUDY',     'Eau de Javel Parfumée (2L)',     'Parfum Citron ou Frais',               4.200, 80,  'images/javel.jpg',           5.0, 55),
(7, 'DIPTOX',   'Insecticide parfum citron (160mL)','Flacon aérosol',                     5.600, 60,  'images/fitox.jpg',           4.0, 18),
(7, 'PRIL',     'Liquide Vaisselle Power (650ml)','Efficacité dégraissante',              3.150, 150, 'images/pril.jpg',            5.0, 71),
(7, 'HARPIC',   'WC Gel Désinfectant (750ml)',    'Nettoie et détartre',                  7.900, 45,  'images/gel.jpg',             4.0, 29);

-- ============================================================
-- 4. TABLE : adresses (adresses de livraison des clients)
-- ============================================================
CREATE TABLE adresses (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT               NOT NULL,
    adresse       VARCHAR(255)        NOT NULL,
    ville         VARCHAR(100)        NOT NULL,
    code_postal   VARCHAR(10)         DEFAULT NULL,
    gouvernorat   VARCHAR(100)        DEFAULT NULL,
    par_defaut    TINYINT(1)          NOT NULL DEFAULT 0,

    CONSTRAINT fk_adresse_user FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 5. TABLE : panier (panier temporaire par session/utilisateur)
-- ============================================================
CREATE TABLE panier (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT               NOT NULL,
    produit_id    INT                 NOT NULL,
    quantite      INT                 NOT NULL DEFAULT 1,
    added_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_panier (utilisateur_id, produit_id),

    CONSTRAINT fk_panier_user    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    CONSTRAINT fk_panier_produit FOREIGN KEY (produit_id)     REFERENCES produits(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 6. TABLE : wishlist (liste de souhaits)
-- ============================================================
CREATE TABLE wishlist (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT               NOT NULL,
    produit_id    INT                 NOT NULL,
    added_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_wishlist (utilisateur_id, produit_id),

    CONSTRAINT fk_wishlist_user    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    CONSTRAINT fk_wishlist_produit FOREIGN KEY (produit_id)     REFERENCES produits(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7. TABLE : commandes
-- ============================================================
CREATE TABLE commandes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT                   NOT NULL,
    adresse_id      INT                   DEFAULT NULL,
    total           DECIMAL(10,3)         NOT NULL,
    statut          ENUM(
                        'en_attente',
                        'confirmee',
                        'en_preparation',
                        'expediee',
                        'livree',
                        'annulee'
                    )                     NOT NULL DEFAULT 'en_attente',
    mode_paiement   ENUM('especes','carte','virement') NOT NULL DEFAULT 'especes',
    notes           TEXT                  DEFAULT NULL,
    created_at      TIMESTAMP             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_commande_user    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE RESTRICT,
    CONSTRAINT fk_commande_adresse FOREIGN KEY (adresse_id)     REFERENCES adresses(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 8. TABLE : lignes_commande (détail de chaque commande)
-- ============================================================
CREATE TABLE lignes_commande (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    commande_id   INT                 NOT NULL,
    produit_id    INT                 NOT NULL,
    quantite      INT                 NOT NULL,
    prix_unitaire DECIMAL(10,3)       NOT NULL,   -- prix au moment de l'achat (snapshot)
    sous_total    DECIMAL(10,3)       NOT NULL,

    CONSTRAINT fk_ligne_commande FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ligne_produit  FOREIGN KEY (produit_id)  REFERENCES produits(id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 9. TABLE : avis (avis / étoiles des clients)
-- ============================================================
CREATE TABLE avis (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    produit_id    INT                 NOT NULL,
    utilisateur_id INT                NOT NULL,
    note          TINYINT             NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire   TEXT                DEFAULT NULL,
    valide        TINYINT(1)          NOT NULL DEFAULT 0, 
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_avis (produit_id, utilisateur_id),

    CONSTRAINT fk_avis_produit FOREIGN KEY (produit_id)     REFERENCES produits(id)      ON DELETE CASCADE,
    CONSTRAINT fk_avis_user    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 10. TABLE : tokens (réinitialisation mot de passe)
-- ============================================================
CREATE TABLE tokens_reset (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT               NOT NULL,
    token         VARCHAR(255)        NOT NULL UNIQUE,
    expire_at     DATETIME            NOT NULL,
    utilise       TINYINT(1)          NOT NULL DEFAULT 0,
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_token_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ADMIN PAR DÉFAUT
-- mot de passe : Admin1234! (à changer immédiatement)
-- hash bcrypt généré avec password_hash('Admin1234!', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('Admin', 'NovaStore', 'admin@novastore.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ============================================================
-- FIN DU SCRIPT
-- ============================================================