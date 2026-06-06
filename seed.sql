-- ============================================================
--  SETUP COMPLET — meca_speed
--  Créer les tables + insérer les données de test
-- ============================================================

USE meca_speed;

SET FOREIGN_KEY_CHECKS = 0;

-- ── TABLES ──────────────────────────────────────────────────

CREATE TABLE garages (
  id      INT          AUTO_INCREMENT PRIMARY KEY,
  nom     VARCHAR(100) NOT NULL,
  adresse VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE utilisateurs (
  id           INT          AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(100) NOT NULL,
  email        VARCHAR(150) NOT NULL UNIQUE,
  telephone    VARCHAR(20)  DEFAULT NULL,
  garages_id   INT          DEFAULT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  role         VARCHAR(20)  DEFAULT 'client',
  cree_le      DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE vehicules (
  id_v            INT         AUTO_INCREMENT PRIMARY KEY,
  id_proprietaire INT         NOT NULL,
  immatriculation VARCHAR(20) NOT NULL UNIQUE,
  marque          VARCHAR(50) NOT NULL,
  modele          VARCHAR(50) NOT NULL,
  annee           INT         DEFAULT NULL,
  FOREIGN KEY (id_proprietaire) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories_produits (
  id   INT         AUTO_INCREMENT PRIMARY KEY,
  nom  VARCHAR(50) NOT NULL,
  slug VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE produits (
  id           INT           AUTO_INCREMENT PRIMARY KEY,
  categorie_id INT           NOT NULL,
  garage_id    INT           NOT NULL DEFAULT 1,
  nom          VARCHAR(200)  NOT NULL,
  description  TEXT          DEFAULT NULL,
  prix         DECIMAL(10,2) NOT NULL,
  image        VARCHAR(255)  DEFAULT 'default_produit.jpg',
  emoji        VARCHAR(10)   DEFAULT '📦',
  badge        VARCHAR(50)   DEFAULT NULL,
  en_stock     TINYINT(1)    DEFAULT 1,
  cree_le      DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categorie_id) REFERENCES categories_produits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rendez_vous (
  id             INT          AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id INT          NOT NULL,
  id_vl          INT          DEFAULT NULL,
  garage_id      INT          NOT NULL DEFAULT 1,
  mecano_id      INT          DEFAULT NULL,
  vehicule       VARCHAR(150) NOT NULL,
  service        VARCHAR(150) NOT NULL,
  date_rdv       DATE         NOT NULL,
  heure_rdv      TIME         NOT NULL,
  statut         ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
  notes          TEXT         DEFAULT NULL,
  cree_le        DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  FOREIGN KEY (id_vl)          REFERENCES vehicules(id_v)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE commandes (
  id             INT           AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id INT           NOT NULL,
  total          DECIMAL(10,2) NOT NULL,
  statut         ENUM('en_attente','validee','livree','annulee') DEFAULT 'en_attente',
  cree_le        DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE commande_lignes (
  id            INT           AUTO_INCREMENT PRIMARY KEY,
  commande_id   INT           NOT NULL,
  produit_id    INT           NOT NULL,
  quantite      INT           NOT NULL DEFAULT 1,
  prix_unitaire DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  FOREIGN KEY (produit_id)  REFERENCES produits(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE suivi_vehicules (
  id              INT          AUTO_INCREMENT PRIMARY KEY,
  reference       VARCHAR(50)  NOT NULL UNIQUE,
  utilisateur_id  INT          NOT NULL,
  immatriculation VARCHAR(30)  NOT NULL,
  modele          VARCHAR(150) NOT NULL,
  progression     INT          DEFAULT 0,
  statut          VARCHAR(80)  DEFAULT 'En attente',
  eta             VARCHAR(100) DEFAULT NULL,
  mecanicien      VARCHAR(100) DEFAULT NULL,
  note_mecanicien TEXT         DEFAULT NULL,
  cree_le         DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE factures (
  id             INT           AUTO_INCREMENT PRIMARY KEY,
  commande_id    INT           DEFAULT NULL,
  rdv_id         INT           DEFAULT NULL,
  utilisateur_id INT           NOT NULL,
  montant        DECIMAL(10,2) NOT NULL,
  statut         ENUM('impayee','payee','annulee') DEFAULT 'impayee',
  date_facture   DATE          NOT NULL,
  cree_le        DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (commande_id)    REFERENCES commandes(id)    ON DELETE SET NULL,
  FOREIGN KEY (rdv_id)         REFERENCES rendez_vous(id)  ON DELETE SET NULL,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── VIDER LES DONNÉES ────────────────────────────────────────

DROP TABLE IF EXISTS factures;
DROP TABLE IF EXISTS commande_lignes;
DROP TABLE IF EXISTS commandes;
DROP TABLE IF EXISTS suivi_vehicules;
DROP TABLE IF EXISTS rendez_vous;
DROP TABLE IF EXISTS vehicules;
DROP TABLE IF EXISTS produits;
DROP TABLE IF EXISTS categories_produits;
DROP TABLE IF EXISTS utilisateurs;
DROP TABLE IF EXISTS garages;

-- ── DONNÉES ──────────────────────────────────────────────────

INSERT INTO garages (id, nom, adresse) VALUES
(1, 'MécaSpeed - Draa Ben Khedda', 'Cité nouvelle'),
(2, 'MécaSpeed - Tizi Ouzou',      'Boulevard du 1er Novembre');

INSERT INTO utilisateurs (nom, email, telephone, garages_id, mot_de_passe, role) VALUES
('Admin',   'admin@mecaspeed.com',  '0770000000', 1, '$2y$10$k.kgqrpV48H9s74FIzxiweDltB81y4OrgSxnfpoG8x2Oxvg40jd1S', 'admin'),
('Rachid',  'client@mecaspeed.com', '0660000000', 1, '$2y$10$8H8Mq3Z56ag0uj5ovsSCxOK4/zcPexzNIW7lXdZ2SdJk/wQ8xcCte', 'client'),
('Sofiane', 'mecano@mecaspeed.com', '0550000000', 1, '$2y$10$eA8WxcF1p6cEVfkT3klhI.fvW522TdV1eGWeo9mAMXYiGXzw/XqAy', 'mecano'),
('Mourad',  'mourad@mecaspeed.com', '0775595856', 2, '$2y$10$0/FAuuPHVLGZ6hOjEpIAm./ZQE/kaudQmrhAoCnLe.LZKsuumXxB2', 'mecano');

INSERT INTO categories_produits (id, nom, slug) VALUES
(1, 'Huiles & Lubrifiants', 'huiles-lubrifiants'),
(2, 'Freinage & Pièces',    'freinage-pieces'),
(3, 'Pneus',                'pneus'),
(4, 'Filtres',              'filtres'),
(5, 'Batteries',            'batteries');

INSERT INTO produits (categorie_id, garage_id, nom, description, prix, image, en_stock) VALUES
(1, 1, 'Huile Moteur Castrol 5W40',    'Huile synthétique haute performance.',    950.00,  'castrol.jpg',      15),
(2, 1, 'Plaquettes de frein Brembo',   'Kit 4 plaquettes avant haute endurance.', 4500.00, 'brembo.jpg',        8),
(3, 1, 'Pneu Continental 195/65 R15',  'Pneu été haute qualité.',                13000.00, 'pneu.jpg',         26),
(4, 1, 'Filtre à huile Purflux',       'Filtre moteur qualité OEM.',              1200.00, 'filtre_huile.jpg', 20),
(5, 1, 'Batterie Varta 60Ah',          'Batterie fiable, démarrage rapide.',     20000.00, 'batterie.jpg',     10);

INSERT INTO vehicules (id_proprietaire, immatriculation, marque, modele, annee) VALUES
(2, '16600-111-16', 'Chevrolet', 'Aveo', 2011);

INSERT INTO rendez_vous (utilisateur_id, garage_id, mecano_id, vehicule, service, date_rdv, heure_rdv, statut) VALUES
(2, 1, 3, '16600-111-16', 'Vidange',  '2026-06-10', '09:00:00', 'pending'),
(2, 1, 3, '16600-111-16', 'Freinage', '2026-06-12', '10:30:00', 'confirmed');

INSERT INTO suivi_vehicules (reference, utilisateur_id, immatriculation, modele, progression, statut, eta, mecanicien, note_mecanicien) VALUES
('REF-001', 2, '16600-111-16', 'Chevrolet Aveo', 60, 'Changement plaquettes en cours', 'Ce soir à 17h', 'Sofiane', 'Pièces reçues, montage en cours.');

SET FOREIGN_KEY_CHECKS = 1;
