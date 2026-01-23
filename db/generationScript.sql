-- Nettoyage préalable (Attention, ça supprime tout si ça existe déjà)
DROP DATABASE IF EXISTS pass_php;
CREATE DATABASE pass_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pass_php;

-- -----------------------------------------------------------------------------
-- TABLE : PASS_SEMESTRES
-- Référence temporelle (ex: Partiels Blancs Octobre 2025)
-- -----------------------------------------------------------------------------
CREATE TABLE pass_semestres (
    id_semestre INT AUTO_INCREMENT PRIMARY KEY,
    code_semestre VARCHAR(50) NOT NULL UNIQUE, -- ex: 'PBI_2025'
    nom_semestre VARCHAR(100),
    annee INT,
    date_examen DATE
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- TABLE : PASS_ETUDIANTS
-- Données brutes issues du PDF (Anonymes)
-- -----------------------------------------------------------------------------
CREATE TABLE pass_etudiants (
    id_etudiant INT NOT NULL, -- Numéro candidat (ex: 22213290), pas d'auto-incrément car vient du PDF
    moyenne_mmok FLOAT DEFAULT NULL,
    moyenne_pharma FLOAT DEFAULT NULL,
    est_absent BOOLEAN DEFAULT FALSE,
    date_import DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_etudiant)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- TABLE : PASS_UTILISATEURS
-- Comptes réels des étudiants
-- -----------------------------------------------------------------------------
CREATE TABLE pass_utilisateurs (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- Assez long pour Bcrypt/Argon2
    pseudo VARCHAR(50),
    id_etudiant_claim INT UNIQUE DEFAULT NULL, -- Le lien vers le PDF
    is_verified BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Si on supprime l'étudiant du PDF, on garde le compte user mais on casse le lien (SET NULL)
    CONSTRAINT fk_user_etudiant 
        FOREIGN KEY (id_etudiant_claim) 
        REFERENCES pass_etudiants (id_etudiant) 
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- TABLE : PASS_PREFERENCES
-- Réglages du dashboard (1 pour 1 avec Utilisateur)
-- -----------------------------------------------------------------------------
CREATE TABLE pass_preferences (
    id_user INT NOT NULL,
    show_publicly BOOLEAN DEFAULT FALSE, -- Veut-il apparaître dans le classement public ?
    target_specialty VARCHAR(50) DEFAULT 'MMOK', -- Filtre par défaut
    notifications_enabled BOOLEAN DEFAULT TRUE,
    
    PRIMARY KEY (id_user),
    CONSTRAINT fk_pref_user 
        FOREIGN KEY (id_user) 
        REFERENCES pass_utilisateurs (id_user) 
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- TABLE : PASS_RESULTATS
-- Table centrale : Notes d'un étudiant pour un semestre donné
-- -----------------------------------------------------------------------------
CREATE TABLE pass_resultats (
    id_etudiant INT NOT NULL,
    id_semestre INT NOT NULL,
    
    -- Matières Principales
    ue1 FLOAT DEFAULT NULL,
    ue2 FLOAT DEFAULT NULL,
    ue3 FLOAT DEFAULT NULL,
    ue8 FLOAT DEFAULT NULL,
    ue10 FLOAT DEFAULT NULL,
    ue13 FLOAT DEFAULT NULL,
    
    -- Détails UE7
    ue7_ca FLOAT DEFAULT NULL,
    ue7_cg FLOAT DEFAULT NULL,
    ue7_co FLOAT DEFAULT NULL,
    ue7_total FLOAT DEFAULT NULL,
    
    -- Détails UE12
    ue12_hdm FLOAT DEFAULT NULL,
    ue12_anatomie FLOAT DEFAULT NULL,
    ue12_total FLOAT DEFAULT NULL,
    
    PRIMARY KEY (id_etudiant, id_semestre),
    
    CONSTRAINT fk_res_etudiant 
        FOREIGN KEY (id_etudiant) 
        REFERENCES pass_etudiants (id_etudiant) 
        ON DELETE CASCADE,
        
    CONSTRAINT fk_res_semestre 
        FOREIGN KEY (id_semestre) 
        REFERENCES pass_semestres (id_semestre) 
        ON DELETE CASCADE
) ENGINE=InnoDB;
