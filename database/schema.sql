-- ═══════════════════════════════════════════════════════════
-- IntelliHire — Schéma MySQL complet
-- Base : intellihire
-- Importer via phpMyAdmin › Onglet SQL
-- ═══════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Tables (ordre inverse des dépendances) ────────────────
DROP TABLE IF EXISTS `notification`;
DROP TABLE IF EXISTS `entretien`;
DROP TABLE IF EXISTS `candidature_historique`;
DROP TABLE IF EXISTS `candidature`;
DROP TABLE IF EXISTS `competence_candidat`;
DROP TABLE IF EXISTS `profil_candidat`;
DROP TABLE IF EXISTS `competence_offre`;
DROP TABLE IF EXISTS `offre_emploi`;
DROP TABLE IF EXISTS `competence`;
DROP TABLE IF EXISTS `utilisateur`;
DROP TABLE IF EXISTS `role`;

-- ── Rôles ────────────────────────────────────────────────
CREATE TABLE `role` (
  `id_role`  INT AUTO_INCREMENT PRIMARY KEY,
  `nom_role` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `role` (`nom_role`) VALUES
  ('RECRUTEUR'),  -- id_role = 1
  ('CANDIDAT');   -- id_role = 2

-- ── Utilisateurs ─────────────────────────────────────────
CREATE TABLE `utilisateur` (
  `id_user`      INT AUTO_INCREMENT PRIMARY KEY,
  `nom`          VARCHAR(100) NOT NULL,
  `prenom`       VARCHAR(100) NOT NULL,
  `email`        VARCHAR(191) NOT NULL UNIQUE,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `id_role`      INT NOT NULL,
  `actif`        TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_role`) REFERENCES `role`(`id_role`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Compétences ──────────────────────────────────────────
CREATE TABLE `competence` (
  `id_competence` INT AUTO_INCREMENT PRIMARY KEY,
  `nom`           VARCHAR(100) NOT NULL UNIQUE,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Offres d'emploi ──────────────────────────────────────
CREATE TABLE `offre_emploi` (
  `id_offre`     INT AUTO_INCREMENT PRIMARY KEY,
  `titre`        VARCHAR(255) NOT NULL,
  `description`  TEXT NOT NULL,
  `type_contrat` ENUM('CDI','CDD','Stage','Freelance') NOT NULL DEFAULT 'CDI',
  `localisation` VARCHAR(255) DEFAULT '',
  `statut`       ENUM('Brouillon','Publiee','Cloturee','Archivee') NOT NULL DEFAULT 'Brouillon',
  `id_recruteur` INT DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_recruteur`) REFERENCES `utilisateur`(`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Compétences ↔ Offres ─────────────────────────────────
CREATE TABLE `competence_offre` (
  `id_offre`      INT NOT NULL,
  `id_competence` INT NOT NULL,
  PRIMARY KEY (`id_offre`, `id_competence`),
  FOREIGN KEY (`id_offre`)      REFERENCES `offre_emploi`(`id_offre`) ON DELETE CASCADE,
  FOREIGN KEY (`id_competence`) REFERENCES `competence`(`id_competence`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Profils candidats ────────────────────────────────────
CREATE TABLE `profil_candidat` (
  `id_profil`  INT AUTO_INCREMENT PRIMARY KEY,
  `id_user`    INT NOT NULL UNIQUE,
  `cv_path`    VARCHAR(255) DEFAULT '',
  `experience` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `utilisateur`(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Compétences ↔ Candidats ──────────────────────────────
CREATE TABLE `competence_candidat` (
  `id_profil`     INT NOT NULL,
  `id_competence` INT NOT NULL,
  PRIMARY KEY (`id_profil`, `id_competence`),
  FOREIGN KEY (`id_profil`)     REFERENCES `profil_candidat`(`id_profil`) ON DELETE CASCADE,
  FOREIGN KEY (`id_competence`) REFERENCES `competence`(`id_competence`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Candidatures ─────────────────────────────────────────
CREATE TABLE `candidature` (
  `id_candidature` INT AUTO_INCREMENT PRIMARY KEY,
  `id_offre`       INT NOT NULL,
  `id_profil`      INT NOT NULL,
  `lettre_motiv`   TEXT DEFAULT '',
  `score_matching` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `statut`         ENUM('Recue','En_cours','Entretien','Acceptee','Refusee') NOT NULL DEFAULT 'Recue',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_candidature` (`id_offre`, `id_profil`),
  FOREIGN KEY (`id_offre`)  REFERENCES `offre_emploi`(`id_offre`) ON DELETE CASCADE,
  FOREIGN KEY (`id_profil`) REFERENCES `profil_candidat`(`id_profil`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Historique des statuts ───────────────────────────────
CREATE TABLE `candidature_historique` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `id_candidature` INT NOT NULL,
  `ancien_statut`  VARCHAR(50) NOT NULL,
  `nouveau_statut` VARCHAR(50) NOT NULL,
  `note`           TEXT DEFAULT '',
  `id_user`        INT DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_candidature`) REFERENCES `candidature`(`id_candidature`) ON DELETE CASCADE,
  FOREIGN KEY (`id_user`)        REFERENCES `utilisateur`(`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Entretiens ───────────────────────────────────────────
CREATE TABLE `entretien` (
  `id_entretien`   INT AUTO_INCREMENT PRIMARY KEY,
  `id_candidature` INT NOT NULL UNIQUE,
  `date_entretien` DATETIME NOT NULL,
  `type_entretien` ENUM('Presentiel','Visio','Telephonique') NOT NULL DEFAULT 'Presentiel',
  `lieu_ou_lien`   VARCHAR(255) DEFAULT '',
  `decision`       ENUM('En_attente','Valide','Refuse') NOT NULL DEFAULT 'En_attente',
  `compte_rendu`   TEXT DEFAULT '',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_candidature`) REFERENCES `candidature`(`id_candidature`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Notifications ────────────────────────────────────────
CREATE TABLE `notification` (
  `id_notif`   INT AUTO_INCREMENT PRIMARY KEY,
  `id_user`    INT NOT NULL,
  `message`    TEXT NOT NULL,
  `lien`       VARCHAR(255) DEFAULT NULL,
  `lu`         TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `utilisateur`(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ═══════════════════════════════════════════════════════════
-- DONNÉES DE DÉMONSTRATION
-- Mot de passe : password (bcrypt)
-- ═══════════════════════════════════════════════════════════

-- Compétences (id 1–20)
INSERT INTO `competence` (`nom`) VALUES
  ('PHP'),('JavaScript'),('MySQL'),('React'),('Vue.js'),
  ('Laravel'),('Node.js'),('Python'),('Docker'),('Git'),
  ('HTML/CSS'),('TypeScript'),('PostgreSQL'),('Redis'),('AWS'),
  ('Figma'),('UI/UX'),('Agile/Scrum'),('Communication'),('Leadership');

-- Utilisateurs
-- id_user=1 : recruteur  (id_role=1)
-- id_user=2 : Yassine    (id_role=2)
-- id_user=3 : Sara       (id_role=2)
-- id_user=4 : Omar       (id_role=2)
INSERT INTO `utilisateur` (`nom`,`prenom`,`email`,`mot_de_passe`,`id_role`) VALUES
  ('Alami',    'Karim',   'recruteur@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
  ('Rachidi',  'Yassine', 'candidat@test.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
  ('Ennaji',   'Sara',    'sara@test.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
  ('Moussaoui','Omar',    'omar@test.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);

-- Offres (id_recruteur=1 = Karim)
INSERT INTO `offre_emploi` (`titre`,`description`,`type_contrat`,`localisation`,`statut`,`id_recruteur`) VALUES
  ('Développeur Full-Stack PHP',
   'Nous recherchons un développeur Full-Stack expérimenté maîtrisant PHP et Vue.js.

Responsabilités :
- Développer et maintenir des applications web robustes
- Collaborer avec l\'équipe design pour intégrer les interfaces
- Participer aux revues de code et aux sprints Agile

Profil recherché :
- 3+ ans d\'expérience en développement web
- Maîtrise de PHP, Vue.js et MySQL
- Connaissance de Git et Docker souhaitée',
   'CDI', 'Casablanca, Maroc', 'Publiee', 1),

  ('Développeur Front-End React',
   'Rejoignez notre équipe produit pour concevoir des interfaces modernes et performantes.

Vos missions :
- Créer des composants React réutilisables et maintenables
- Optimiser les performances front-end
- Travailler en collaboration étroite avec les équipes UX et Back-End

Profil :
- 2+ ans d\'expérience React/TypeScript
- Maîtrise de HTML5, CSS3, JavaScript ES6+
- Expérience avec les outils de design (Figma)',
   'CDI', 'Rabat, Maroc', 'Publiee', 1),

  ('Développeur Back-End Node.js',
   'Nous cherchons un développeur Node.js pour renforcer notre équipe technique.

Missions :
- Concevoir et développer des APIs RESTful performantes
- Gérer les bases de données PostgreSQL et Redis
- Mettre en place des pipelines CI/CD

Profil :
- Expérience Node.js + Express ou NestJS
- Connaissance de PostgreSQL, Redis
- Sensibilité sécurité et performances',
   'CDI', 'Casablanca, Maroc', 'Publiee', 1),

  ('Stage Développeur Python / Data',
   'Stage de 6 mois dans notre équipe Data Engineering.

Missions :
- Participer au développement de pipelines de données
- Analyser et visualiser des données métier
- Contribuer à des projets Machine Learning

Profil :
- Étudiant en informatique ou data science
- Bases en Python et SQL
- Curiosité et esprit d\'analyse',
   'Stage', 'Marrakech, Maroc', 'Publiee', 1),

  ('Chef de Projet Digital',
   'Pilotez des projets digitaux ambitieux au sein d\'une équipe dynamique.

Missions :
- Cadrer et planifier les projets de A à Z
- Animer les rituels Agile (sprint planning, daily, rétro)
- Assurer le lien entre équipes techniques et métiers

Profil :
- 5+ ans d\'expérience en gestion de projet IT
- Certification PMP ou équivalent appréciée
- Excellent leadership et communication',
   'CDI', 'Casablanca, Maroc', 'Publiee', 1),

  ('Designer UI/UX Senior',
   'Créez des expériences utilisateur mémorables pour nos produits.

Missions :
- Concevoir des maquettes et prototypes interactifs sur Figma
- Réaliser des tests utilisateurs et itérer rapidement
- Définir et faire évoluer le design system de l\'entreprise

Profil :
- 4+ ans en design UI/UX
- Maîtrise de Figma et des outils de prototypage
- Portfolio à présenter',
   'CDI', 'Casablanca, Maroc', 'Brouillon', 1);

-- Compétences ↔ Offres
INSERT INTO `competence_offre` (`id_offre`,`id_competence`) VALUES
  (1,1),(1,6),(1,3),(1,5),(1,10),(1,9),   -- offre 1 : PHP, Laravel, MySQL, Vue.js, Git, Docker
  (2,4),(2,12),(2,11),(2,16),             -- offre 2 : React, TypeScript, HTML/CSS, Figma
  (3,7),(3,14),(3,13),(3,10),             -- offre 3 : Node.js, Redis, PostgreSQL, Git
  (4,8),(4,3),(4,2),                      -- offre 4 : Python, MySQL, JavaScript
  (5,18),(5,19),(5,20),                   -- offre 5 : Agile, Communication, Leadership
  (6,16),(6,17),(6,19);                   -- offre 6 : Figma, UI/UX, Communication

-- Profils candidats
-- id_profil=1 : Yassine (id_user=2)
-- id_profil=2 : Sara    (id_user=3)
-- id_profil=3 : Omar    (id_user=4)
INSERT INTO `profil_candidat` (`id_user`,`cv_path`,`experience`) VALUES
  (2, 'uploads/cv_demo_yassine.pdf', 3),
  (3, 'uploads/cv_demo_sara.pdf',    2),
  (4, 'uploads/cv_demo_omar.pdf',    5);

-- Compétences ↔ Candidats
INSERT INTO `competence_candidat` (`id_profil`,`id_competence`) VALUES
  (1,1),(1,3),(1,5),(1,10),(1,11),   -- Yassine : PHP, MySQL, Vue.js, Git, HTML/CSS
  (2,4),(2,12),(2,11),(2,2),         -- Sara    : React, TypeScript, HTML/CSS, JS
  (3,7),(3,13),(3,10),(3,8);         -- Omar    : Node.js, PostgreSQL, Git, Python

-- Candidatures
INSERT INTO `candidature` (`id_offre`,`id_profil`,`lettre_motiv`,`score_matching`,`statut`) VALUES
  (1, 1,
   'Passionné par le développement web depuis plus de 3 ans, je maîtrise la stack PHP et Vue.js. J\'ai développé plusieurs applications en production et suis convaincu de pouvoir apporter une réelle valeur ajoutée à votre équipe.',
   83.33, 'En_cours'),
  (2, 2,
   'Développeuse front-end spécialisée React, j\'ai travaillé sur des projets SaaS à fort trafic. Je maîtrise TypeScript, les bonnes pratiques de performance et j\'adore créer des interfaces intuitives.',
   100.00, 'Entretien'),
  (3, 3,
   'Expert Node.js avec 5 ans d\'expérience dans la conception d\'APIs robustes. J\'ai une solide expérience avec PostgreSQL et Redis en environnement de production.',
   75.00, 'Acceptee'),
  (1, 2,
   'Même si mon expertise principale est React, je maîtrise les bases de PHP et suis très motivée pour apprendre. Je m\'adapte rapidement aux nouvelles technologies.',
   33.33, 'Recue'),
  (4, 1,
   'Bien que mon profil soit orienté web, je dispose de bonnes bases en Python et SQL, et je suis très intéressé par la data science.',
   66.67, 'Refusee');

-- Historique des statuts (id_user=1 = Karim le recruteur)
INSERT INTO `candidature_historique` (`id_candidature`,`ancien_statut`,`nouveau_statut`,`note`,`id_user`) VALUES
  (1, 'Recue', 'En_cours',  'Profil intéressant, passage en revue approfondie.', 1),
  (2, 'Recue', 'En_cours',  'Excellent score de matching, à convoquer rapidement.', 1),
  (2, 'En_cours', 'Entretien', 'Entretien planifié pour la semaine prochaine.', 1),
  (3, 'Recue', 'En_cours',  'Candidature solide.', 1),
  (3, 'En_cours', 'Acceptee', 'Candidat retenu après entretien. Très bon profil.', 1),
  (5, 'Recue', 'Refusee',   'Profil ne correspond pas aux attentes pour ce poste.', 1);

-- Entretiens
INSERT INTO `entretien` (`id_candidature`,`date_entretien`,`type_entretien`,`lieu_ou_lien`,`decision`,`compte_rendu`) VALUES
  (2, DATE_ADD(NOW(), INTERVAL 3 DAY),  'Visio',      'https://meet.google.com/abc-def-ghi', 'En_attente', ''),
  (3, DATE_SUB(NOW(), INTERVAL 5 DAY),  'Presentiel', 'Bureau RH — 3ème étage, Casablanca',  'Valide',
   'Excellente prestation technique. Candidat très à l\'aise avec Node.js et les systèmes distribués. Culture fit parfait. Recommandé pour recrutement.');

-- Notifications
INSERT INTO `notification` (`id_user`,`message`,`lien`,`lu`) VALUES
  (1, 'Nouvelle candidature de Yassine Rachidi pour "Développeur Full-Stack PHP"',    'candidatures/detail/1', 0),
  (1, 'Nouvelle candidature de Sara Ennaji pour "Développeur Front-End React"',        'candidatures/detail/2', 0),
  (1, 'Nouvelle candidature de Omar Moussaoui pour "Développeur Back-End Node.js"',   'candidatures/detail/3', 1),
  (2, 'Votre candidature est en cours d\'examen pour "Développeur Full-Stack PHP"',   'candidatures/detail/1', 0),
  (3, 'Vous êtes convoqué(e) à un entretien pour "Développeur Front-End React"',      'candidatures/detail/2', 0),
  (4, 'Félicitations — votre candidature pour "Développeur Back-End Node.js" est acceptée', 'candidatures/detail/3', 1);

