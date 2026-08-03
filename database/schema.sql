CREATE TABLE IF NOT EXISTS settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  school_name VARCHAR(150) NOT NULL DEFAULT 'Titans Academy Futebol',
  slogan VARCHAR(190) DEFAULT 'TAF — Formando atletas, construindo campeões',
  hero_title VARCHAR(190) DEFAULT 'Treinamento, disciplina e evolução',
  hero_text TEXT,
  about_text LONGTEXT,
  logo_path VARCHAR(255) NULL,
  primary_color VARCHAR(20) NOT NULL DEFAULT '#25275e',
  secondary_color VARCHAR(20) NOT NULL DEFAULT '#27a9d6',
  address VARCHAR(255) NULL,
  phone VARCHAR(50) NULL,
  whatsapp VARCHAR(50) NULL,
  email VARCHAR(190) NULL,
  instagram VARCHAR(190) NULL,
  facebook VARCHAR(190) NULL,
  footer_text VARCHAR(255) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (
  id,
  hero_text,
  about_text,
  logo_path,
  instagram,
  footer_text
) VALUES (
  1,
  'Treinos por categoria, acompanhamento técnico e participação em campeonatos, torneios e amistosos.',
  'A Titans Academy Futebol, também conhecida como TAF, trabalha na formação esportiva e pessoal de seus atletas por meio do futebol, da disciplina e do trabalho em equipe.',
  'assets/images/logo-titans-v3.png',
  'https://www.instagram.com/titans_academia_futebol/',
  'Titans Academy Futebol — TAF | Formação esportiva, disciplina e evolução dentro e fora de campo.'
);

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','manager','editor','viewer') NOT NULL DEFAULT 'admin',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS age_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  min_age TINYINT UNSIGNED NULL,
  max_age TINYINT UNSIGNED NULL,
  description TEXT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_age_categories_active_order (active, sort_order, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO age_categories (name, max_age, sort_order) VALUES
('Sub-5', 5, 5),
('Sub-7', 7, 7),
('Sub-9', 9, 9),
('Sub-11', 11, 11),
('Sub-13', 13, 13),
('Sub-15', 15, 15),
('Sub-17', 17, 17),
('Sub-20', 20, 20);

CREATE TABLE IF NOT EXISTS locations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  unit_name VARCHAR(150) NOT NULL,
  address_line VARCHAR(255) NOT NULL,
  neighborhood VARCHAR(120) NULL,
  city VARCHAR(120) NULL,
  state VARCHAR(50) NULL,
  postal_code VARCHAR(20) NULL,
  phone VARCHAR(50) NULL,
  whatsapp VARCHAR(50) NULL,
  map_url VARCHAR(500) NULL,
  training_info TEXT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_locations_active_order (active, sort_order, unit_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coaches (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  role_title VARCHAR(150) NULL,
  bio TEXT NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(190) NULL,
  photo_path VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS athletes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  birth_date DATE NULL,
  category_id INT UNSIGNED NULL,
  category VARCHAR(100) NULL,
  position_name VARCHAR(100) NULL,
  shirt_number INT NULL,
  bio TEXT NULL,
  photo_path VARCHAR(255) NULL,
  guardian_name VARCHAR(150) NULL,
  guardian_phone VARCHAR(50) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_athletes_name (name),
  INDEX idx_athletes_category (category_id, active, name),
  CONSTRAINT fk_athletes_category FOREIGN KEY (category_id) REFERENCES age_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teams (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category_id INT UNSIGNED NULL,
  category VARCHAR(100) NULL,
  coach_name VARCHAR(150) NULL,
  badge_path VARCHAR(255) NULL,
  notes TEXT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_teams_category (category_id, active, name),
  CONSTRAINT fk_teams_category FOREIGN KEY (category_id) REFERENCES age_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS competitions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  competition_type ENUM('campeonato','torneio','amistoso') NOT NULL DEFAULT 'torneio',
  status ENUM('proximo','andamento','concluido') NOT NULL DEFAULT 'proximo',
  start_date DATE NULL,
  end_date DATE NULL,
  location VARCHAR(190) NULL,
  description TEXT NULL,
  is_achievement TINYINT(1) NOT NULL DEFAULT 0,
  placement VARCHAR(100) NULL,
  trophy_image VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS competition_teams (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  competition_id INT UNSIGNED NOT NULL,
  team_id INT UNSIGNED NOT NULL,
  group_name VARCHAR(80) NULL,
  registration_notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_comp_team (competition_id, team_id),
  CONSTRAINT fk_ct_competition FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
  CONSTRAINT fk_ct_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matches (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  competition_id INT UNSIGNED NULL,
  home_team VARCHAR(150) NOT NULL,
  away_team VARCHAR(150) NOT NULL,
  match_date DATETIME NULL,
  location VARCHAR(190) NULL,
  score_home INT NULL,
  score_away INT NULL,
  status ENUM('agendada','realizada','cancelada') NOT NULL DEFAULT 'agendada',
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_match_competition FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  message TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  moderated_at DATETIME NULL,
  moderated_by INT UNSIGNED NULL,
  INDEX idx_comments_status (status),
  CONSTRAINT fk_comments_user FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS news (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  summary VARCHAR(500) NOT NULL,
  content LONGTEXT NULL,
  image_path VARCHAR(255) NULL,
  published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  active TINYINT(1) NOT NULL DEFAULT 1,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_news_publication (active, featured, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO news (title, slug, summary, content, published_at, active, featured) VALUES
('Bem-vindo ao novo portal da TAF', 'bem-vindo-ao-novo-portal-da-taf', 'A Titans Academy Futebol está com uma página inicial mais moderna, organizada e preparada para aproximar atletas, famílias e comunidade.', 'O novo portal da Titans Academy Futebol reúne informações sobre atletas, professores, categorias, unidades, competições, jogos e conquistas. A página inicial também passa a destacar as notícias mais recentes da escolinha.', NOW(), 1, 1),
('Agenda esportiva em um só lugar', 'agenda-esportiva-em-um-so-lugar', 'Acompanhe no site os próximos jogos, amistosos, torneios e campeonatos das equipes Titans.', 'A área de agenda foi criada para facilitar o acompanhamento dos próximos compromissos da TAF. Datas, horários, adversários, locais e resultados podem ser atualizados diretamente pelo painel administrativo.', DATE_SUB(NOW(), INTERVAL 1 DAY), 1, 0),
('Categorias de base em destaque', 'categorias-de-base-em-destaque', 'Os atletas podem ser consultados por categoria Sub, com busca rápida por nome e posição.', 'A organização por categorias facilita a visualização dos atletas da Titans Academy Futebol. O visitante pode navegar entre os Subs e localizar atletas pelo nome ou pela posição em campo.', DATE_SUB(NOW(), INTERVAL 2 DAY), 1, 0);

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(100) PRIMARY KEY,
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES
('20260802233200_taf_locations_categories_filters'),
('20260802235500_home_news_carousel_v3');
