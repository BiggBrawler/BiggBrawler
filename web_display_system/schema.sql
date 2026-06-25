-- ============================================================
-- DISPLAY SYSTEM – Database Schema
-- Run this once to set up your tables.
-- ============================================================

CREATE TABLE IF NOT EXISTS `assets` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `type`       ENUM('text', 'image') NOT NULL,
  `content`    TEXT NOT NULL,
  `label`      VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `canvas_settings` (
  `id`      INT AUTO_INCREMENT PRIMARY KEY,
  `bg_type` ENUM('color', 'image') DEFAULT 'color',
  `bg_val`  VARCHAR(255)           DEFAULT '#1a1a2e'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `canvas_elements` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `type`           ENUM('text', 'image') NOT NULL,
  `x_pos`          INT    NOT NULL DEFAULT 0,
  `y_pos`          INT    NOT NULL DEFAULT 0,
  `width`          INT    NOT NULL DEFAULT 200,
  `height`         INT    NOT NULL DEFAULT 100,
  `manual_content` TEXT   NULL,
  `asset_id`       INT    NULL,
  `font_family`    VARCHAR(100) DEFAULT 'Arial',
  `font_size`      INT          DEFAULT 16,
  `font_color`     VARCHAR(50)  DEFAULT '#000000',
  FOREIGN KEY (`asset_id`) REFERENCES `assets`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed the single canvas settings row
INSERT INTO `canvas_settings` (`id`, `bg_type`, `bg_val`)
VALUES (1, 'color', '#1a1a2e')
ON DUPLICATE KEY UPDATE id = id;
