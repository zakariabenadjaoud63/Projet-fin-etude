

CREATE DATABASE IF NOT EXISTS meca_speed
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE meca_speed;


CREATE TABLE IF NOT EXISTS utilisateurs (
  id           INT          AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(100) NOT NULL,
  email        VARCHAR(150) NOT NULL UNIQUE,
  telephone    VARCHAR(20)  DEFAULT NULL,
  role         ENUM('client','proprietaire','admin') NOT NULL DEFAULT 'client',
  mot_de_passe VARCHAR(255) NOT NULL,
  cree_le      DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS categories_produits (
  id   INT         AUTO_INCREMENT PRIMARY KEY,
  nom  VARCHAR(50) NOT NULL,
  slug VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS produits (
  id           INT            AUTO_INCREMENT PRIMARY KEY,
  categorie_id INT            NOT NULL,
  nom          VARCHAR(200)   NOT NULL,
  description  TEXT           DEFAULT NULL,
  prix         DECIMAL(10,2)  NOT NULL,
  emoji        VARCHAR(10)    DEFAULT '📦',
  badge        VARCHAR(50)    DEFAULT NULL,
  en_stock     TINYINT(1)     DEFAULT 1,
  cree_le      DATETIME       DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categorie_id) REFERENCES categories_produits(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS rendez_vous (
  id             INT          AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id INT          NOT NULL,
  vehicule       VARCHAR(150) NOT NULL,
  service        VARCHAR(150) NOT NULL,
  date_rdv       DATE         NOT NULL,
  heure_rdv      TIME         NOT NULL,
  statut         ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
  notes          TEXT         DEFAULT NULL,
  cree_le        DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS commandes (
  id             INT           AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id INT           NOT NULL,
  total          DECIMAL(10,2) NOT NULL,
  statut         ENUM('en_attente','validee','livree','annulee') DEFAULT 'en_attente',
  cree_le        DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS commande_lignes (
  id            INT           AUTO_INCREMENT PRIMARY KEY,
  commande_id   INT           NOT NULL,
  produit_id    INT           NOT NULL,
  quantite      INT           NOT NULL DEFAULT 1,
  prix_unitaire DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  FOREIGN KEY (produit_id)  REFERENCES produits(id)  ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS suivi_vehicules (
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
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS suivi_taches (
  id          INT          AUTO_INCREMENT PRIMARY KEY,
  suivi_id    INT          NOT NULL,
  nom         VARCHAR(150) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  faite       TINYINT(1)   DEFAULT 0,
  active      TINYINT(1)   DEFAULT 0,
  ordre       INT          DEFAULT 0,
  FOREIGN KEY (suivi_id) REFERENCES suivi_vehicules(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS factures (
  id             INT           AUTO_INCREMENT PRIMARY KEY,
  commande_id    INT           DEFAULT NULL,
  rdv_id         INT           DEFAULT NULL,
  utilisateur_id INT           NOT NULL,
  montant        DECIMAL(10,2) NOT NULL,
  statut         ENUM('impayee','payee','annulee') DEFAULT 'impayee',
  date_facture   DATE          NOT NULL,
  cree_le        DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (commande_id)    REFERENCES commandes(id)     ON DELETE SET NULL,
  FOREIGN KEY (rdv_id)         REFERENCES rendez_vous(id)   ON DELETE SET NULL,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

