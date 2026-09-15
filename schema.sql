/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: retrogames
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-5ubuntu0.1 from Ubuntu

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `game_sessions`
--

DROP TABLE IF EXISTS `game_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `game_id` int(10) unsigned NOT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ended_at` datetime DEFAULT NULL,
  `duration` int(10) unsigned DEFAULT NULL COMMENT 'seconds actually played',
  PRIMARY KEY (`id`),
  KEY `idx_gs_user` (`user_id`),
  KEY `idx_gs_duration` (`duration`),
  KEY `idx_gs_game` (`game_id`),
  CONSTRAINT `fk_gs_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `game_sessions` WRITE;
/*!40000 ALTER TABLE `game_sessions` DISABLE KEYS */;
INSERT INTO `game_sessions` VALUES
(1,3,4,'2026-09-14 01:15:26','2026-09-14 01:15:35',6),
(2,3,2,'2026-09-14 01:16:10','2026-09-14 01:19:18',13),
(3,3,4,'2026-09-14 01:19:18','2026-09-14 01:20:00',33),
(4,2,4,'2026-09-14 20:43:16','2026-09-14 20:43:57',32),
(5,1,4,'2026-09-14 20:45:45','2026-09-14 20:46:30',26);
/*!40000 ALTER TABLE `game_sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `games`
--

DROP TABLE IF EXISTS `games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `games` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'arcade',
  `thumbnail` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL COMMENT 'soft delete / recycle bin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_games_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `games` WRITE;
/*!40000 ALTER TABLE `games` DISABLE KEYS */;
INSERT INTO `games` VALUES
(1,'snake','Snake','Classic snake game. Eat food, grow longer, avoid walls and yourself!','Arrow keys or WASD to move. Eat the food to grow. Avoid hitting walls or your tail.','arcade',NULL,1,1,'2026-09-14 08:04:19','2026-09-14 08:04:19',NULL),
(2,'pacman','Pac-Man','Guide Pac-Man through the maze, eat all dots while avoiding ghosts!','Arrow keys to move. Eat all dots to win. Power pellets let you eat ghosts!','arcade',NULL,1,2,'2026-09-14 08:04:19','2026-09-14 08:04:19',NULL),
(3,'car','Car Racing','Dodge incoming traffic and survive as long as possible at high speed!','Arrow keys or A/D to move left/right. Avoid other cars. Speed increases over time.','racing',NULL,1,3,'2026-09-14 08:04:19','2026-09-14 08:04:19',NULL),
(4,'flappybird','Flappy Bird','Tap to flap through pipes. How far can you go?','Press Space or click to flap. Avoid the pipes. Each pipe passed = 1 point.','arcade',NULL,1,4,'2026-09-14 08:04:19','2026-09-14 08:04:19',NULL),
(5,'pingpong','Ping Pong','Classic table tennis. Play against the computer or a friend!','Player 1: W/S keys. Player 2: Up/Down arrows. First to 7 wins!','sports',NULL,1,5,'2026-09-14 08:04:19','2026-09-14 08:04:19',NULL),
(6,'tetris','Tetris','Arrange falling blocks to complete lines. Classic puzzle action!','Arrow keys to move/rotate. Down to drop faster. Space for hard drop.','puzzle',NULL,1,6,'2026-09-14 08:04:19','2026-09-14 08:04:19',NULL);
/*!40000 ALTER TABLE `games` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Temporary table structure for view `leaderboard`
--

DROP TABLE IF EXISTS `leaderboard`;
/*!50001 DROP VIEW IF EXISTS `leaderboard`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `leaderboard` AS SELECT
 1 AS `game_id`,
  1 AS `user_id`,
  1 AS `best_score`,
  1 AS `username`,
  1 AS `game_name`,
  1 AS `game_slug` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `scores`
--

DROP TABLE IF EXISTS `scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `scores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `game_id` int(10) unsigned NOT NULL,
  `score` int(10) unsigned NOT NULL DEFAULT 0,
  `level` int(10) unsigned DEFAULT NULL,
  `duration` int(10) unsigned DEFAULT NULL COMMENT 'seconds',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL COMMENT 'soft delete / recycle bin',
  PRIMARY KEY (`id`),
  KEY `idx_user_game` (`user_id`,`game_id`),
  KEY `idx_game_score` (`game_id`,`score` DESC),
  KEY `idx_scores_deleted` (`deleted_at`),
  CONSTRAINT `fk_scores_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`),
  CONSTRAINT `fk_scores_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scores`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `scores` WRITE;
/*!40000 ALTER TABLE `scores` DISABLE KEYS */;
INSERT INTO `scores` VALUES
(1,3,4,2,1,10,'2026-09-14 01:15:33',NULL),
(2,3,4,21,1,42,'2026-09-14 01:19:58',NULL),
(3,2,4,20,1,40,'2026-09-14 20:43:54',NULL),
(4,1,4,14,1,30,'2026-09-14 20:46:28',NULL);
/*!40000 ALTER TABLE `scores` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `total_playtime` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'lifetime seconds played',
  `deleted_at` datetime DEFAULT NULL COMMENT 'soft delete / recycle bin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'admin','admin@retrogames.com','$2y$10$Ta.3t4mxnDzf03De6culF.uU/Ho1JfA0/eApvVoDit7h9JslK/zRe','admin',NULL,1,'2026-09-14 08:04:19','2026-09-14 20:46:30','2026-09-14 20:44:45',26,NULL),
(2,'pandu','pandu@gmail.com','$2y$12$oJWtBoPrCW7TCnFNkoNUsOiBcQUobdQzHokv30l4lt5ZRm6kh/kq.','user',NULL,1,'2026-09-14 01:10:27','2026-09-14 20:43:57','2026-09-14 20:43:05',32,NULL),
(3,'admin2','admin2@yoi.com','$2a$12$/cQFkr3f8HheR7WuA27qfuqzhkUdCCCLHGvDIH/NO7CtbTWYkxeFG','admin',NULL,1,'2026-09-14 01:13:20','2026-09-14 01:20:00','2026-09-14 01:15:10',52,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Final view structure for view `leaderboard`
--

/*!50001 DROP VIEW IF EXISTS `leaderboard`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `leaderboard` AS select `s`.`game_id` AS `game_id`,`s`.`user_id` AS `user_id`,max(`s`.`score`) AS `best_score`,`u`.`username` AS `username`,`g`.`name` AS `game_name`,`g`.`slug` AS `game_slug` from ((`scores` `s` join `users` `u` on(`s`.`user_id` = `u`.`id`)) join `games` `g` on(`s`.`game_id` = `g`.`id`)) where `s`.`deleted_at` is null and `u`.`deleted_at` is null and `g`.`deleted_at` is null group by `s`.`game_id`,`s`.`user_id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-14 20:50:12
