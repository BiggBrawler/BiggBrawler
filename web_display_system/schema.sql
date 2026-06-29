-- ============================================================
-- DISPLAY SYSTEM – Full Database Schema v3
-- Run this file once via phpMyAdmin or MySQL CLI.
-- ============================================================

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(100)  NOT NULL UNIQUE,
  `email`         VARCHAR(255)  NOT NULL UNIQUE,
  `password_hash` VARCHAR(255)  NOT NULL,
  `role`          ENUM('admin','basic') NOT NULL DEFAULT 'basic',
  `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT      NOT NULL,
  `passcode`   CHAR(6)  NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used`       TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Brand-standard CSS per typed block (admin-controlled)
CREATE TABLE IF NOT EXISTS `block_styles` (
  `block_type`  VARCHAR(50)      NOT NULL PRIMARY KEY,
  `font_family` VARCHAR(100)     DEFAULT 'Arial',
  `font_size`   INT              DEFAULT 16,
  `font_color`  VARCHAR(50)      DEFAULT '#000000',
  `font_weight` VARCHAR(20)      DEFAULT 'normal',
  `font_style`  VARCHAR(20)      DEFAULT 'normal',
  `line_height` DECIMAL(4,2)     DEFAULT 1.40,
  `updated_at`  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asset pool (reusable text / images / video references)
CREATE TABLE IF NOT EXISTS `assets` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `type`       ENUM('text','image','video') NOT NULL,
  `content`    TEXT        NOT NULL,
  `label`      VARCHAR(255) NULL,
  `created_at` TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Canvas global settings (single row, id=1)
CREATE TABLE IF NOT EXISTS `canvas_settings` (
  `id`      INT AUTO_INCREMENT PRIMARY KEY,
  `bg_type` ENUM('color','image') DEFAULT 'color',
  `bg_val`  VARCHAR(255)          DEFAULT '#1a1a2e'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Canvas elements: sections + all block types
-- section_id NULL  → element is at root canvas level
-- section_id SET   → element lives inside that section (coords are section-relative)
CREATE TABLE IF NOT EXISTS `canvas_elements` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `section_id`     INT   NULL     COMMENT 'Parent section ID; NULL = root level',
  `type`           ENUM('section','text','image','video','carousel','marquee','table') NOT NULL,
  `block_subtype`  ENUM('free','section_header','item_title','price','description') DEFAULT 'free',
  `x_pos`          INT          NOT NULL DEFAULT 0,
  `y_pos`          INT          NOT NULL DEFAULT 0,
  `width`          INT          NOT NULL DEFAULT 200,
  `height`         INT          NOT NULL DEFAULT 100,
  `manual_content` TEXT         NULL,
  `asset_id`       INT          NULL,
  `section_bg`     VARCHAR(255) NULL  COMMENT 'Background image path for section blocks',
  `font_family`    VARCHAR(100) DEFAULT 'Arial',
  `font_size`      INT          DEFAULT 16,
  `font_color`     VARCHAR(50)  DEFAULT '#000000',
  `font_weight`    VARCHAR(20)  DEFAULT 'normal',
  `font_style`     VARCHAR(20)  DEFAULT 'normal',
  `line_height`    DECIMAL(4,2) DEFAULT 1.40,
  `locked`         TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`     INT          NOT NULL DEFAULT 0,
  FOREIGN KEY (`asset_id`)   REFERENCES `assets`(`id`)          ON DELETE SET NULL,
  FOREIGN KEY (`section_id`) REFERENCES `canvas_elements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Seed data ----

INSERT INTO `canvas_settings` (`id`, `bg_type`, `bg_val`)
VALUES (1, 'color', '#1a1a2e')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO `block_styles` (`block_type`, `font_family`, `font_size`, `font_color`, `font_weight`, `font_style`, `line_height`) VALUES
  ('section_header', 'Arial', 36, '#ffffff', 'bold',   'normal', 1.20),
  ('item_title',     'Arial', 24, '#f0f0f0', 'bold',   'normal', 1.30),
  ('price',          'Arial', 30, '#f39c12', 'bold',   'normal', 1.20),
  ('description',    'Arial', 14, '#cccccc', 'normal', 'normal', 1.60)
ON DUPLICATE KEY UPDATE block_type = block_type;

-- ============================================================
-- After running this schema, visit setup.php in your browser
-- to create your first admin account.
-- ============================================================
-- Upgrading from v2? Run this ALTER on an existing database:
-- ALTER TABLE `canvas_elements`
--   MODIFY COLUMN `type`
--   ENUM('section','text','image','video','carousel','marquee') NOT NULL;
-- ============================================================
