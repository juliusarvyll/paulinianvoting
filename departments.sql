-- -------------------------------------------------------------
-- -------------------------------------------------------------
-- TablePlus 1.5.5
--
-- https://tableplus.com/
--
-- Database: bilangpaulinian
-- Generation Time: 2026-05-22 22:59:55.825369
-- -------------------------------------------------------------

-- Save current session settings and set optimal values for import
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0;
SET NAMES utf8mb4;

CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_name` varchar(255) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` (`id`, `department_name`, `logo_path`, `created_at`, `updated_at`) VALUES 
(1, 'SNAHS', NULL, '2026-05-22 00:43:51', '2026-05-22 00:43:51'),
(2, 'SITE', NULL, '2026-05-22 00:43:51', '2026-05-22 00:43:51'),
(3, 'SBAHM', NULL, '2026-05-22 00:43:51', '2026-05-22 00:43:51'),
(4, 'SASTE', NULL, '2026-05-22 00:43:51', '2026-05-22 00:43:51'),
(5, 'SOM', NULL, '2026-05-22 00:43:51', '2026-05-22 00:43:51'),
(6, 'GRADUATE SCHOOL', NULL, '2026-05-22 00:43:51', '2026-05-22 00:43:51'),
(7, 'ETEEAP', NULL, '2026-05-22 00:43:51', '2026-05-22 00:43:51');


-- Restore original session settings
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET SQL_MODE=@OLD_SQL_MODE;
SET SQL_NOTES=@OLD_SQL_NOTES;
