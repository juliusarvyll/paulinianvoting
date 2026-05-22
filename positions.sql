-- -------------------------------------------------------------
-- -------------------------------------------------------------
-- TablePlus 1.5.5
--
-- https://tableplus.com/
--
-- Database: bilangpaulinian
-- Generation Time: 2026-05-22 23:00:09.084378
-- -------------------------------------------------------------

-- Save current session settings and set optimal values for import
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0;
SET NAMES utf8mb4;

CREATE TABLE `positions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `election_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `max_winners` int(11) NOT NULL,
  `level` enum('university','department','course','year_level','department_course_level','department_year_level') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `positions_election_id_level_index` (`election_id`,`level`),
  CONSTRAINT `positions_election_id_foreign` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `positions` (`id`, `election_id`, `name`, `max_winners`, `level`, `created_at`, `updated_at`) VALUES 
(1, 1, 'President', 1, 'university', '2026-05-22 01:06:06', '2026-05-22 01:06:06'),
(2, 1, 'Vice President', 1, 'university', '2026-05-22 01:06:41', '2026-05-22 01:06:41'),
(3, 1, 'Secretary', 1, 'university', '2026-05-22 01:07:04', '2026-05-22 01:07:04'),
(4, 1, 'Treasurer', 1, 'university', '2026-05-22 01:07:21', '2026-05-22 01:07:21'),
(5, 1, 'PRO', 1, 'university', '2026-05-22 01:07:37', '2026-05-22 01:07:37'),
(6, 1, 'Senator', 12, 'university', '2026-05-22 01:08:05', '2026-05-22 01:08:05'),
(7, 1, 'SNAHS Representative', 2, 'department', '2026-05-22 01:08:27', '2026-05-22 04:38:32'),
(8, 1, 'SBAHM Representative', 2, 'department', '2026-05-22 01:08:41', '2026-05-22 04:38:46'),
(9, 1, 'SASTE Representative', 2, 'department', '2026-05-22 01:09:25', '2026-05-22 05:05:43'),
(10, 1, 'SITE Representative', 1, 'department', '2026-05-22 01:09:39', '2026-05-22 04:38:57'),
(11, 1, 'Governor', 1, 'department', '2026-05-22 01:13:52', '2026-05-22 04:06:29'),
(12, 1, 'Vice Governor', 1, 'department', '2026-05-22 01:14:10', '2026-05-22 04:06:37'),
(13, 1, 'Secretary', 1, 'department', '2026-05-22 01:14:25', '2026-05-22 04:06:46'),
(14, 1, 'Treasurer', 1, 'department', '2026-05-22 01:14:51', '2026-05-22 04:06:53'),
(15, 1, 'PRO', 1, 'department', '2026-05-22 01:15:05', '2026-05-22 04:07:00'),
(16, 1, 'Councilor', 8, 'department', '2026-05-22 01:15:45', '2026-05-22 04:27:17');


-- Restore original session settings
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET SQL_MODE=@OLD_SQL_MODE;
SET SQL_NOTES=@OLD_SQL_NOTES;
