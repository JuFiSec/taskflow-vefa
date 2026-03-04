-- ================================================
-- TaskFlow - Base de données VEFA
-- Initialisation des tables
-- ================================================

USE taskflow;
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET character_set_connection = utf8mb4;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'notaire', 'promoteur') DEFAULT 'promoteur',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des dossiers VEFA
CREATE TABLE IF NOT EXISTS dossiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) NOT NULL UNIQUE,
    titre VARCHAR(200) NOT NULL,
    promoteur VARCHAR(150) NOT NULL,
    notaire VARCHAR(150) NOT NULL,
    reservataire VARCHAR(150) NOT NULL,
    bien_description TEXT,
    prix_vente DECIMAL(15,2),
    statut ENUM('en_cours', 'signe', 'archive', 'suspendu') DEFAULT 'en_cours',
    date_signature DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES utilisateurs(id)
);

-- Table des commentaires/suivi de dossier
CREATE TABLE IF NOT EXISTS suivi_dossiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dossier_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    commentaire TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dossier_id) REFERENCES dossiers(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

-- ================================================
-- Donnees de test (mot de passe : "password" pour tous)
-- ================================================
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('Admin', 'TaskFlow', 'admin@taskflow.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Schaaf', 'Ithiel', 'ithiel.schaaf@notaire.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'notaire'),
('Dannie', 'Fieni', 'dannie.fieni@promoteur.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'promoteur');

INSERT INTO dossiers (reference, titre, promoteur, notaire, reservataire, bien_description, prix_vente, statut, created_by) VALUES
('VEFA-2026-001', 'Residence Les Oliviers - Apt 3B', 'Nexity Grand Paris', 'Me Schaaf Ithiel', 'Wilfried-Luc Kolo', 'Appartement T3, 68m2, 3eme etage, parking inclus', 285000.00, 'en_cours', 1),
('VEFA-2026-002', 'Le Domaine de Vincennes - Villa 12', 'Bouygues Immobilier', 'Me Aicha Sangafowa', 'Sophie Leclerc', 'Villa 4 pieces, 110m2, jardin 200m2', 495000.00, 'signe', 1),
('VEFA-2026-003', 'Tour Horizon - Apt 8A', 'Kaufman & Broad', 'Me Schaaf Ithiel', 'Aruna Winner', 'Studio 28m2, vue panoramique, 8eme etage', 175000.00, 'en_cours', 2),
('VEFA-2026-004', 'Les Terrasses du Lac - Apt 2C', 'Vinci Immobilier', 'Me Laurent Claire', 'Isabelle Moreau', 'T2, 45m2, terrasse 15m2, vue lac', 220000.00, 'archive', 1);

-- ================================================
-- Droits monitoring pour mysql_exporter
-- ================================================
GRANT REPLICATION CLIENT ON *.* TO 'taskflow_user'@'%';
GRANT PROCESS ON *.* TO 'taskflow_user'@'%';
FLUSH PRIVILEGES;