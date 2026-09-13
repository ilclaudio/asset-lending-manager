/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.23-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: local
-- ------------------------------------------------------
-- Server version	10.6.23-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `wp_actionscheduler_actions`
--

DROP TABLE IF EXISTS `wp_actionscheduler_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_actionscheduler_actions` (
  `action_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hook` varchar(191) NOT NULL,
  `status` varchar(20) NOT NULL,
  `scheduled_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `scheduled_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `args` varchar(191) DEFAULT NULL,
  `schedule` longtext DEFAULT NULL,
  `group_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `last_attempt_local` datetime DEFAULT '0000-00-00 00:00:00',
  `claim_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `extended_args` varchar(8000) DEFAULT NULL,
  PRIMARY KEY (`action_id`),
  KEY `hook_status_scheduled_date_gmt` (`hook`(163),`status`,`scheduled_date_gmt`),
  KEY `status_scheduled_date_gmt` (`status`,`scheduled_date_gmt`),
  KEY `scheduled_date_gmt` (`scheduled_date_gmt`),
  KEY `args` (`args`),
  KEY `group_id` (`group_id`),
  KEY `last_attempt_gmt` (`last_attempt_gmt`),
  KEY `claim_id_status_priority_scheduled_date_gmt` (`claim_id`,`status`,`priority`,`scheduled_date_gmt`),
  KEY `status_last_attempt_gmt` (`status`,`last_attempt_gmt`),
  KEY `status_claim_id` (`status`,`claim_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_actions`
--

LOCK TABLES `wp_actionscheduler_actions` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_actionscheduler_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_claims`
--

DROP TABLE IF EXISTS `wp_actionscheduler_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_actionscheduler_claims` (
  `claim_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date_created_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`claim_id`),
  KEY `date_created_gmt` (`date_created_gmt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_claims`
--

LOCK TABLES `wp_actionscheduler_claims` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_actionscheduler_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_groups`
--

DROP TABLE IF EXISTS `wp_actionscheduler_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_actionscheduler_groups` (
  `group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `slug` (`slug`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_groups`
--

LOCK TABLES `wp_actionscheduler_groups` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_actionscheduler_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_logs`
--

DROP TABLE IF EXISTS `wp_actionscheduler_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_actionscheduler_logs` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action_id` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `log_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `log_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`log_id`),
  KEY `action_id` (`action_id`),
  KEY `log_date_gmt` (`log_date_gmt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_logs`
--

LOCK TABLES `wp_actionscheduler_logs` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_actionscheduler_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_almgr_loan_requests`
--

DROP TABLE IF EXISTS `wp_almgr_loan_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_almgr_loan_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `requester_id` bigint(20) unsigned NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `request_date` datetime NOT NULL,
  `request_message` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `response_date` datetime DEFAULT NULL,
  `response_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `requester_id` (`requester_id`),
  KEY `owner_id` (`owner_id`),
  KEY `status` (`status`),
  KEY `asset_status_request_date` (`asset_id`,`status`,`request_date`),
  KEY `requester_request_date` (`requester_id`,`request_date`),
  KEY `requester_status_request_date` (`requester_id`,`status`,`request_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_almgr_loan_requests`
--

LOCK TABLES `wp_almgr_loan_requests` WRITE;
/*!40000 ALTER TABLE `wp_almgr_loan_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_almgr_loan_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_almgr_loan_requests_history`
--

DROP TABLE IF EXISTS `wp_almgr_loan_requests_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_almgr_loan_requests_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_request_id` bigint(20) unsigned NOT NULL,
  `asset_id` bigint(20) unsigned NOT NULL,
  `requester_id` bigint(20) unsigned NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL,
  `message` text DEFAULT NULL,
  `changed_at` datetime NOT NULL,
  `changed_by` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `loan_request_id` (`loan_request_id`),
  KEY `asset_id` (`asset_id`),
  KEY `requester_id` (`requester_id`),
  KEY `owner_id` (`owner_id`),
  KEY `changed_by` (`changed_by`),
  KEY `status` (`status`),
  KEY `asset_changed_at` (`asset_id`,`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_almgr_loan_requests_history`
--

LOCK TABLES `wp_almgr_loan_requests_history` WRITE;
/*!40000 ALTER TABLE `wp_almgr_loan_requests_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_almgr_loan_requests_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_commentmeta`
--

DROP TABLE IF EXISTS `wp_commentmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_commentmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_commentmeta`
--

LOCK TABLES `wp_commentmeta` WRITE;
/*!40000 ALTER TABLE `wp_commentmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_commentmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_comments`
--

DROP TABLE IF EXISTS `wp_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT 0,
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT 0,
  `comment_approved` varchar(20) NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) NOT NULL DEFAULT '',
  `comment_type` varchar(20) NOT NULL DEFAULT 'comment',
  `comment_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_comments`
--

LOCK TABLES `wp_comments` WRITE;
/*!40000 ALTER TABLE `wp_comments` DISABLE KEYS */;
INSERT INTO `wp_comments` VALUES (1,1,'Un commentatore di WordPress','wapuu@wordpress.example','https://it.wordpress.org/','','2025-12-15 15:49:18','2025-12-15 14:49:18','Ciao, questo è un commento.\nPer iniziare a moderare, modificare ed eliminare commenti, vai alla schermata dei commenti nella bacheca.\nGli avatar di chi lascia un commento sono forniti da <a href=\"https://it.gravatar.com/\">Gravatar</a>.',0,'1','','comment',0,0);
/*!40000 ALTER TABLE `wp_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_links`
--

DROP TABLE IF EXISTS `wp_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_links` (
  `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `link_name` varchar(255) NOT NULL DEFAULT '',
  `link_image` varchar(255) NOT NULL DEFAULT '',
  `link_target` varchar(25) NOT NULL DEFAULT '',
  `link_description` varchar(255) NOT NULL DEFAULT '',
  `link_visible` varchar(20) NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) unsigned NOT NULL DEFAULT 1,
  `link_rating` int(11) NOT NULL DEFAULT 0,
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) NOT NULL DEFAULT '',
  `link_notes` mediumtext NOT NULL,
  `link_rss` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_links`
--

LOCK TABLES `wp_links` WRITE;
/*!40000 ALTER TABLE `wp_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_options`
--

DROP TABLE IF EXISTS `wp_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`),
  KEY `autoload` (`autoload`)
) ENGINE=InnoDB AUTO_INCREMENT=5640 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_options`
--

LOCK TABLES `wp_options` WRITE;
/*!40000 ALTER TABLE `wp_options` DISABLE KEYS */;
INSERT INTO `wp_options` VALUES (1,'cron','a:16:{i:1788107520;a:1:{s:26:\"action_scheduler_run_queue\";a:1:{s:32:\"0d04ed39571b55704c122d726248bbac\";a:3:{s:8:\"schedule\";s:12:\"every_minute\";s:4:\"args\";a:1:{i:0;s:7:\"WP Cron\";}s:8:\"interval\";i:60;}}}i:1788108000;a:1:{s:30:\"wp_delete_temp_updater_backups\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:2:{s:8:\"schedule\";b:0;s:4:\"args\";a:0:{}}}}i:1788108558;a:1:{s:16:\"wp_update_themes\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1788108559;a:1:{s:34:\"wp_privacy_delete_old_export_files\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1788127200;a:2:{s:28:\"wpforms_email_summaries_cron\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:2:{s:8:\"schedule\";b:0;s:4:\"args\";a:0:{}}}s:33:\"wpforms_weekly_entries_count_cron\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:2:{s:8:\"schedule\";b:0;s:4:\"args\";a:0:{}}}}i:1788144572;a:1:{s:21:\"wp_update_user_counts\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1788148158;a:1:{s:16:\"wp_version_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1788149958;a:1:{s:17:\"wp_update_plugins\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1788184795;a:1:{s:27:\"acf_update_site_health_data\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1788187759;a:1:{s:32:\"recovery_mode_clean_expired_keys\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1788187772;a:2:{s:19:\"wp_scheduled_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:25:\"delete_expired_transients\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1788187775;a:1:{s:30:\"wp_scheduled_auto_draft_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1788192442;a:1:{s:41:\"wp_privacy_personal_data_cleanup_requests\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1788274159;a:1:{s:30:\"wp_site_health_scheduled_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"weekly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:604800;}}}i:1788427399;a:1:{s:30:\"wp_delete_temp_updater_backups\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"weekly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:604800;}}}s:7:\"version\";i:2;}','on'),(2,'siteurl','https://alm-e2e.local','on'),(3,'home','https://alm-e2e.local','on'),(4,'blogname','ALM DEMO','on'),(5,'blogdescription','','on'),(6,'users_can_register','0','on'),(7,'admin_email','admin@example.test','on'),(8,'start_of_week','1','on'),(9,'use_balanceTags','0','on'),(10,'use_smilies','1','on'),(11,'require_name_email','1','on'),(12,'comments_notify','1','on'),(13,'posts_per_rss','10','on'),(14,'rss_use_excerpt','0','on'),(15,'mailserver_url','mail.example.com','on'),(16,'mailserver_login','login@example.com','on'),(17,'mailserver_pass','','on'),(18,'mailserver_port','110','on'),(19,'default_category','1','on'),(20,'default_comment_status','','on'),(21,'default_ping_status','','on'),(22,'default_pingback_flag','','on'),(23,'posts_per_page','10','on'),(24,'date_format','j F Y','on'),(25,'time_format','G:i','on'),(26,'links_updated_date_format','j F Y G:i','on'),(27,'comment_moderation','','on'),(28,'moderation_notify','1','on'),(29,'permalink_structure','/%postname%/','on'),(30,'rewrite_rules','a:138:{s:11:\"^wp-json/?$\";s:22:\"index.php?rest_route=/\";s:14:\"^wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:21:\"^index.php/wp-json/?$\";s:22:\"index.php?rest_route=/\";s:24:\"^index.php/wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:17:\"^wp-sitemap\\.xml$\";s:23:\"index.php?sitemap=index\";s:17:\"^wp-sitemap\\.xsl$\";s:36:\"index.php?sitemap-stylesheet=sitemap\";s:23:\"^wp-sitemap-index\\.xsl$\";s:34:\"index.php?sitemap-stylesheet=index\";s:48:\"^wp-sitemap-([a-z]+?)-([a-z\\d_-]+?)-(\\d+?)\\.xml$\";s:75:\"index.php?sitemap=$matches[1]&sitemap-subtype=$matches[2]&paged=$matches[3]\";s:34:\"^wp-sitemap-([a-z]+?)-(\\d+?)\\.xml$\";s:47:\"index.php?sitemap=$matches[1]&paged=$matches[2]\";s:8:\"asset/?$\";s:31:\"index.php?post_type=almgr_asset\";s:38:\"asset/feed/(feed|rdf|rss|rss2|atom)/?$\";s:48:\"index.php?post_type=almgr_asset&feed=$matches[1]\";s:33:\"asset/(feed|rdf|rss|rss2|atom)/?$\";s:48:\"index.php?post_type=almgr_asset&feed=$matches[1]\";s:25:\"asset/page/([0-9]{1,})/?$\";s:49:\"index.php?post_type=almgr_asset&paged=$matches[1]\";s:47:\"category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:42:\"category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:23:\"category/(.+?)/embed/?$\";s:46:\"index.php?category_name=$matches[1]&embed=true\";s:35:\"category/(.+?)/page/?([0-9]{1,})/?$\";s:53:\"index.php?category_name=$matches[1]&paged=$matches[2]\";s:17:\"category/(.+?)/?$\";s:35:\"index.php?category_name=$matches[1]\";s:44:\"tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:39:\"tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:20:\"tag/([^/]+)/embed/?$\";s:36:\"index.php?tag=$matches[1]&embed=true\";s:32:\"tag/([^/]+)/page/?([0-9]{1,})/?$\";s:43:\"index.php?tag=$matches[1]&paged=$matches[2]\";s:14:\"tag/([^/]+)/?$\";s:25:\"index.php?tag=$matches[1]\";s:45:\"type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:40:\"type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:21:\"type/([^/]+)/embed/?$\";s:44:\"index.php?post_format=$matches[1]&embed=true\";s:33:\"type/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?post_format=$matches[1]&paged=$matches[2]\";s:15:\"type/([^/]+)/?$\";s:33:\"index.php?post_format=$matches[1]\";s:33:\"asset/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:43:\"asset/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:63:\"asset/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"asset/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"asset/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:39:\"asset/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:22:\"asset/([^/]+)/embed/?$\";s:44:\"index.php?almgr_asset=$matches[1]&embed=true\";s:26:\"asset/([^/]+)/trackback/?$\";s:38:\"index.php?almgr_asset=$matches[1]&tb=1\";s:46:\"asset/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_asset=$matches[1]&feed=$matches[2]\";s:41:\"asset/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_asset=$matches[1]&feed=$matches[2]\";s:34:\"asset/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?almgr_asset=$matches[1]&paged=$matches[2]\";s:41:\"asset/([^/]+)/comment-page-([0-9]{1,})/?$\";s:51:\"index.php?almgr_asset=$matches[1]&cpage=$matches[2]\";s:30:\"asset/([^/]+)(?:/([0-9]+))?/?$\";s:50:\"index.php?almgr_asset=$matches[1]&page=$matches[2]\";s:22:\"asset/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:32:\"asset/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:52:\"asset/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:47:\"asset/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:47:\"asset/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:28:\"asset/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:56:\"almgr_structure/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:54:\"index.php?almgr_structure=$matches[1]&feed=$matches[2]\";s:51:\"almgr_structure/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:54:\"index.php?almgr_structure=$matches[1]&feed=$matches[2]\";s:32:\"almgr_structure/([^/]+)/embed/?$\";s:48:\"index.php?almgr_structure=$matches[1]&embed=true\";s:44:\"almgr_structure/([^/]+)/page/?([0-9]{1,})/?$\";s:55:\"index.php?almgr_structure=$matches[1]&paged=$matches[2]\";s:26:\"almgr_structure/([^/]+)/?$\";s:37:\"index.php?almgr_structure=$matches[1]\";s:51:\"almgr_type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?almgr_type=$matches[1]&feed=$matches[2]\";s:46:\"almgr_type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?almgr_type=$matches[1]&feed=$matches[2]\";s:27:\"almgr_type/([^/]+)/embed/?$\";s:43:\"index.php?almgr_type=$matches[1]&embed=true\";s:39:\"almgr_type/([^/]+)/page/?([0-9]{1,})/?$\";s:50:\"index.php?almgr_type=$matches[1]&paged=$matches[2]\";s:21:\"almgr_type/([^/]+)/?$\";s:32:\"index.php?almgr_type=$matches[1]\";s:52:\"almgr_state/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_state=$matches[1]&feed=$matches[2]\";s:47:\"almgr_state/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_state=$matches[1]&feed=$matches[2]\";s:28:\"almgr_state/([^/]+)/embed/?$\";s:44:\"index.php?almgr_state=$matches[1]&embed=true\";s:40:\"almgr_state/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?almgr_state=$matches[1]&paged=$matches[2]\";s:22:\"almgr_state/([^/]+)/?$\";s:33:\"index.php?almgr_state=$matches[1]\";s:52:\"almgr_level/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_level=$matches[1]&feed=$matches[2]\";s:47:\"almgr_level/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_level=$matches[1]&feed=$matches[2]\";s:28:\"almgr_level/([^/]+)/embed/?$\";s:44:\"index.php?almgr_level=$matches[1]&embed=true\";s:40:\"almgr_level/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?almgr_level=$matches[1]&paged=$matches[2]\";s:22:\"almgr_level/([^/]+)/?$\";s:33:\"index.php?almgr_level=$matches[1]\";s:12:\"robots\\.txt$\";s:18:\"index.php?robots=1\";s:13:\"favicon\\.ico$\";s:19:\"index.php?favicon=1\";s:12:\"sitemap\\.xml\";s:23:\"index.php?sitemap=index\";s:48:\".*wp-(atom|rdf|rss|rss2|feed|commentsrss2)\\.php$\";s:18:\"index.php?feed=old\";s:20:\".*wp-app\\.php(/.*)?$\";s:19:\"index.php?error=403\";s:18:\".*wp-register.php$\";s:23:\"index.php?register=true\";s:32:\"feed/(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:27:\"(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:8:\"embed/?$\";s:21:\"index.php?&embed=true\";s:20:\"page/?([0-9]{1,})/?$\";s:28:\"index.php?&paged=$matches[1]\";s:27:\"comment-page-([0-9]{1,})/?$\";s:38:\"index.php?&page_id=2&cpage=$matches[1]\";s:41:\"comments/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:36:\"comments/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:17:\"comments/embed/?$\";s:21:\"index.php?&embed=true\";s:44:\"search/(.+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:39:\"search/(.+)/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:20:\"search/(.+)/embed/?$\";s:34:\"index.php?s=$matches[1]&embed=true\";s:32:\"search/(.+)/page/?([0-9]{1,})/?$\";s:41:\"index.php?s=$matches[1]&paged=$matches[2]\";s:14:\"search/(.+)/?$\";s:23:\"index.php?s=$matches[1]\";s:47:\"author/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:42:\"author/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:23:\"author/([^/]+)/embed/?$\";s:44:\"index.php?author_name=$matches[1]&embed=true\";s:35:\"author/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?author_name=$matches[1]&paged=$matches[2]\";s:17:\"author/([^/]+)/?$\";s:33:\"index.php?author_name=$matches[1]\";s:69:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:64:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:45:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/embed/?$\";s:74:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&embed=true\";s:57:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:81:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]\";s:39:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$\";s:63:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]\";s:56:\"([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:51:\"([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:32:\"([0-9]{4})/([0-9]{1,2})/embed/?$\";s:58:\"index.php?year=$matches[1]&monthnum=$matches[2]&embed=true\";s:44:\"([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:65:\"index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]\";s:26:\"([0-9]{4})/([0-9]{1,2})/?$\";s:47:\"index.php?year=$matches[1]&monthnum=$matches[2]\";s:43:\"([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:38:\"([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:19:\"([0-9]{4})/embed/?$\";s:37:\"index.php?year=$matches[1]&embed=true\";s:31:\"([0-9]{4})/page/?([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&paged=$matches[2]\";s:13:\"([0-9]{4})/?$\";s:26:\"index.php?year=$matches[1]\";s:27:\".?.+?/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\".?.+?/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\".?.+?/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\".?.+?/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"(.?.+?)/embed/?$\";s:41:\"index.php?pagename=$matches[1]&embed=true\";s:20:\"(.?.+?)/trackback/?$\";s:35:\"index.php?pagename=$matches[1]&tb=1\";s:40:\"(.?.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:35:\"(.?.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:28:\"(.?.+?)/page/?([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&paged=$matches[2]\";s:35:\"(.?.+?)/comment-page-([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&cpage=$matches[2]\";s:24:\"(.?.+?)(?:/([0-9]+))?/?$\";s:47:\"index.php?pagename=$matches[1]&page=$matches[2]\";s:27:\"[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\"[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\"[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\"[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"([^/]+)/embed/?$\";s:37:\"index.php?name=$matches[1]&embed=true\";s:20:\"([^/]+)/trackback/?$\";s:31:\"index.php?name=$matches[1]&tb=1\";s:40:\"([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:35:\"([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:28:\"([^/]+)/page/?([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&paged=$matches[2]\";s:35:\"([^/]+)/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&cpage=$matches[2]\";s:24:\"([^/]+)(?:/([0-9]+))?/?$\";s:43:\"index.php?name=$matches[1]&page=$matches[2]\";s:16:\"[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:26:\"[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:46:\"[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:22:\"[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";}','on'),(31,'hack_file','0','on'),(32,'blog_charset','UTF-8','on'),(33,'moderation_keys','','off'),(34,'active_plugins','a:9:{i:0;s:30:\"advanced-custom-fields/acf.php\";i:1;s:47:\"asset-lending-manager/asset-lending-manager.php\";i:2;s:23:\"loco-translate/loco.php\";i:3;s:19:\"members/members.php\";i:4;s:23:\"plugin-check/plugin.php\";i:5;s:40:\"wordpress-beta-tester/wp-beta-tester.php\";i:6;s:29:\"wp-mail-smtp/wp_mail_smtp.php\";i:7;s:31:\"wp-migrate-db/wp-migrate-db.php\";i:8;s:24:\"wpforms-lite/wpforms.php\";}','on'),(35,'category_base','','on'),(36,'ping_sites','https://rpc.pingomatic.com/','on'),(37,'comment_max_links','2','on'),(38,'gmt_offset','','on'),(39,'default_email_category','1','on'),(40,'recently_edited','a:2:{i:0;s:99:\"C:\\Users\\Claudio\\Local Sites\\alm-site\\app\\public/wp-content/plugins/asset-lending-manager/AGENTS.md\";i:1;s:0:\"\";}','off'),(41,'template','twentytwentyone','on'),(42,'stylesheet','twentytwentyone','on'),(43,'comment_registration','','on'),(44,'html_type','text/html','on'),(45,'use_trackback','0','on'),(46,'default_role','subscriber','on'),(47,'db_version','61833','on'),(48,'uploads_use_yearmonth_folders','1','on'),(49,'upload_path','','on'),(50,'blog_public','0','on'),(51,'default_link_category','2','on'),(52,'show_on_front','page','on'),(53,'tag_base','','on'),(54,'show_avatars','1','on'),(55,'avatar_rating','G','on'),(56,'upload_url_path','','on'),(57,'thumbnail_size_w','150','on'),(58,'thumbnail_size_h','150','on'),(59,'thumbnail_crop','1','on'),(60,'medium_size_w','300','on'),(61,'medium_size_h','300','on'),(62,'avatar_default','mystery','on'),(63,'large_size_w','1024','on'),(64,'large_size_h','1024','on'),(65,'image_default_link_type','none','on'),(66,'image_default_size','','on'),(67,'image_default_align','','on'),(68,'close_comments_for_old_posts','','on'),(69,'close_comments_days_old','14','on'),(70,'thread_comments','','on'),(71,'thread_comments_depth','5','on'),(72,'page_comments','','on'),(73,'comments_per_page','50','on'),(74,'default_comments_page','newest','on'),(75,'comment_order','asc','on'),(76,'sticky_posts','a:0:{}','on'),(77,'widget_categories','a:2:{i:1;a:0:{}s:12:\"_multiwidget\";i:1;}','auto'),(78,'widget_text','a:2:{i:1;a:0:{}s:12:\"_multiwidget\";i:1;}','auto'),(79,'widget_rss','a:2:{i:1;a:0:{}s:12:\"_multiwidget\";i:1;}','auto'),(80,'uninstall_plugins','a:1:{s:37:\"user-role-editor/user-role-editor.php\";a:2:{i:0;s:16:\"User_Role_Editor\";i:1;s:9:\"uninstall\";}}','off'),(81,'timezone_string','Europe/Rome','on'),(82,'page_for_posts','0','on'),(83,'page_on_front','2','on'),(84,'default_post_format','0','on'),(85,'link_manager_enabled','0','on'),(86,'finished_splitting_shared_terms','1','on'),(87,'site_icon','0','on'),(88,'medium_large_size_w','768','on'),(89,'medium_large_size_h','0','on'),(90,'wp_page_for_privacy_policy','3','on'),(91,'show_comments_cookies_opt_in','','on'),(92,'admin_email_lifespan','1797356473','on'),(93,'disallowed_keys','','off'),(94,'comment_previously_approved','1','on'),(95,'auto_plugin_theme_update_emails','a:0:{}','off'),(96,'auto_update_core_dev','enabled','on'),(97,'auto_update_core_minor','enabled','on'),(98,'auto_update_core_major','enabled','on'),(99,'wp_force_deactivated_plugins','a:0:{}','on'),(100,'wp_attachment_pages_enabled','0','on'),(101,'wp_notes_notify','1','on'),(102,'initial_db_version','60717','on'),(103,'wp_user_roles','a:10:{s:13:\"administrator\";a:2:{s:4:\"name\";s:13:\"Administrator\";s:12:\"capabilities\";a:104:{s:13:\"switch_themes\";b:1;s:11:\"edit_themes\";b:1;s:16:\"activate_plugins\";b:1;s:12:\"edit_plugins\";b:1;s:10:\"edit_users\";b:1;s:10:\"edit_files\";b:1;s:14:\"manage_options\";b:1;s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:6:\"import\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:8:\"level_10\";b:1;s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:12:\"delete_users\";b:1;s:12:\"create_users\";b:1;s:17:\"unfiltered_upload\";b:1;s:14:\"edit_dashboard\";b:1;s:14:\"update_plugins\";b:1;s:14:\"delete_plugins\";b:1;s:15:\"install_plugins\";b:1;s:13:\"update_themes\";b:1;s:14:\"install_themes\";b:1;s:11:\"update_core\";b:1;s:10:\"list_users\";b:1;s:12:\"remove_users\";b:1;s:13:\"promote_users\";b:1;s:18:\"edit_theme_options\";b:1;s:13:\"delete_themes\";b:1;s:6:\"export\";b:1;s:16:\"restrict_content\";b:1;s:10:\"list_roles\";b:1;s:12:\"create_roles\";b:1;s:12:\"delete_roles\";b:1;s:10:\"edit_roles\";b:1;s:10:\"loco_admin\";b:1;s:12:\"create_posts\";b:1;s:17:\"install_languages\";b:1;s:14:\"resume_plugins\";b:1;s:13:\"resume_themes\";b:1;s:23:\"view_site_health_checks\";b:1;s:14:\"read_alm_asset\";b:1;s:23:\"read_private_alm_assets\";b:1;s:14:\"edit_alm_asset\";b:1;s:15:\"edit_alm_assets\";b:1;s:22:\"edit_others_alm_assets\";b:1;s:25:\"edit_published_alm_assets\";b:1;s:23:\"edit_private_alm_assets\";b:1;s:16:\"delete_alm_asset\";b:1;s:17:\"delete_alm_assets\";b:1;s:24:\"delete_others_alm_assets\";b:1;s:27:\"delete_published_alm_assets\";b:1;s:25:\"delete_private_alm_assets\";b:1;s:18:\"publish_alm_assets\";b:1;s:15:\"alm_view_assets\";b:1;s:14:\"alm_view_asset\";b:1;s:14:\"alm_edit_asset\";b:1;s:17:\"almgr_view_assets\";b:1;s:16:\"almgr_view_asset\";b:1;s:16:\"almgr_edit_asset\";b:1;s:16:\"read_almgr_asset\";b:1;s:25:\"read_private_almgr_assets\";b:1;s:16:\"edit_almgr_asset\";b:1;s:17:\"edit_almgr_assets\";b:1;s:24:\"edit_others_almgr_assets\";b:1;s:27:\"edit_published_almgr_assets\";b:1;s:25:\"edit_private_almgr_assets\";b:1;s:18:\"delete_almgr_asset\";b:1;s:19:\"delete_almgr_assets\";b:1;s:26:\"delete_others_almgr_assets\";b:1;s:29:\"delete_published_almgr_assets\";b:1;s:27:\"delete_private_almgr_assets\";b:1;s:20:\"publish_almgr_assets\";b:1;}}s:6:\"editor\";a:2:{s:4:\"name\";s:6:\"Editor\";s:12:\"capabilities\";a:34:{s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;}}s:6:\"author\";a:2:{s:4:\"name\";s:6:\"Author\";s:12:\"capabilities\";a:10:{s:12:\"upload_files\";b:1;s:10:\"edit_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:22:\"delete_published_posts\";b:1;}}s:11:\"contributor\";a:2:{s:4:\"name\";s:11:\"Contributor\";s:12:\"capabilities\";a:5:{s:10:\"edit_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;}}s:10:\"subscriber\";a:2:{s:4:\"name\";s:10:\"Subscriber\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:7:\"level_0\";b:1;}}s:10:\"translator\";a:2:{s:4:\"name\";s:10:\"Translator\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:10:\"loco_admin\";b:1;}}s:10:\"alm_member\";a:2:{s:4:\"name\";s:5:\"Socio\";s:12:\"capabilities\";a:3:{s:4:\"read\";b:1;s:15:\"alm_view_assets\";b:1;s:14:\"alm_view_asset\";b:1;}}s:12:\"alm_operator\";a:2:{s:4:\"name\";s:9:\"Operatore\";s:12:\"capabilities\";a:17:{s:4:\"read\";b:1;s:14:\"read_alm_asset\";b:1;s:23:\"read_private_alm_assets\";b:1;s:14:\"edit_alm_asset\";b:1;s:15:\"edit_alm_assets\";b:1;s:22:\"edit_others_alm_assets\";b:1;s:25:\"edit_published_alm_assets\";b:1;s:23:\"edit_private_alm_assets\";b:1;s:16:\"delete_alm_asset\";b:1;s:17:\"delete_alm_assets\";b:1;s:24:\"delete_others_alm_assets\";b:1;s:27:\"delete_published_alm_assets\";b:1;s:25:\"delete_private_alm_assets\";b:1;s:18:\"publish_alm_assets\";b:1;s:15:\"alm_view_assets\";b:1;s:14:\"alm_view_asset\";b:1;s:14:\"alm_edit_asset\";b:1;}}s:12:\"almgr_member\";a:2:{s:4:\"name\";s:6:\"Member\";s:12:\"capabilities\";a:3:{s:4:\"read\";b:1;s:17:\"almgr_view_assets\";b:1;s:16:\"almgr_view_asset\";b:1;}}s:14:\"almgr_operator\";a:2:{s:4:\"name\";s:8:\"Operator\";s:12:\"capabilities\";a:31:{s:4:\"read\";b:1;s:14:\"read_alm_asset\";b:1;s:23:\"read_private_alm_assets\";b:1;s:14:\"edit_alm_asset\";b:1;s:15:\"edit_alm_assets\";b:1;s:22:\"edit_others_alm_assets\";b:1;s:25:\"edit_published_alm_assets\";b:1;s:23:\"edit_private_alm_assets\";b:1;s:16:\"delete_alm_asset\";b:1;s:17:\"delete_alm_assets\";b:1;s:24:\"delete_others_alm_assets\";b:1;s:27:\"delete_published_alm_assets\";b:1;s:25:\"delete_private_alm_assets\";b:1;s:18:\"publish_alm_assets\";b:1;s:17:\"almgr_view_assets\";b:1;s:16:\"almgr_view_asset\";b:1;s:16:\"almgr_edit_asset\";b:1;s:16:\"read_almgr_asset\";b:1;s:25:\"read_private_almgr_assets\";b:1;s:16:\"edit_almgr_asset\";b:1;s:17:\"edit_almgr_assets\";b:1;s:24:\"edit_others_almgr_assets\";b:1;s:27:\"edit_published_almgr_assets\";b:1;s:25:\"edit_private_almgr_assets\";b:1;s:18:\"delete_almgr_asset\";b:1;s:19:\"delete_almgr_assets\";b:1;s:26:\"delete_others_almgr_assets\";b:1;s:29:\"delete_published_almgr_assets\";b:1;s:27:\"delete_private_almgr_assets\";b:1;s:20:\"publish_almgr_assets\";b:1;s:12:\"upload_files\";b:1;}}}','on'),(104,'fresh_site','0','off'),(105,'WPLANG','it_IT','auto'),(106,'user_count','5','off'),(107,'widget_block','a:6:{i:2;a:1:{s:7:\"content\";s:19:\"<!-- wp:search /-->\";}i:3;a:1:{s:7:\"content\";s:158:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Articoli recenti</h2><!-- /wp:heading --><!-- wp:latest-posts /--></div><!-- /wp:group -->\";}i:4;a:1:{s:7:\"content\";s:105:\"<!-- wp:group {\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group\"></div>\n<!-- /wp:group -->\";}i:5;a:1:{s:7:\"content\";s:145:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Archivi</h2><!-- /wp:heading --><!-- wp:archives /--></div><!-- /wp:group -->\";}i:6;a:1:{s:7:\"content\";s:149:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Categorie</h2><!-- /wp:heading --><!-- wp:categories /--></div><!-- /wp:group -->\";}s:12:\"_multiwidget\";i:1;}','on'),(108,'sidebars_widgets','a:3:{s:19:\"wp_inactive_widgets\";a:4:{i:0;s:7:\"block-2\";i:1;s:7:\"block-3\";i:2;s:7:\"block-5\";i:3;s:7:\"block-6\";}s:9:\"sidebar-1\";a:1:{i:0;s:7:\"block-4\";}s:13:\"array_version\";i:3;}','on'),(109,'widget_pages','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(110,'widget_calendar','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(111,'widget_archives','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(112,'widget_media_audio','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(113,'widget_media_image','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(114,'widget_media_gallery','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(115,'widget_media_video','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(116,'widget_meta','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(117,'widget_search','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(118,'widget_recent-posts','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(119,'widget_recent-comments','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(120,'widget_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(121,'widget_nav_menu','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(122,'widget_custom_html','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(126,'theme_mods_twentytwentyfive','a:4:{s:18:\"custom_css_post_id\";i:-1;s:16:\"sidebars_widgets\";a:2:{s:4:\"time\";i:1777736514;s:4:\"data\";a:2:{s:19:\"wp_inactive_widgets\";a:4:{i:0;s:7:\"block-2\";i:1;s:7:\"block-3\";i:2;s:7:\"block-5\";i:3;s:7:\"block-6\";}s:9:\"sidebar-1\";a:1:{i:0;s:7:\"block-4\";}}}s:19:\"wp_classic_sidebars\";a:1:{s:9:\"sidebar-1\";a:11:{s:4:\"name\";s:6:\"Footer\";s:2:\"id\";s:9:\"sidebar-1\";s:11:\"description\";s:56:\"Aggiungi i widget qui per farli apparire nel tuo footer.\";s:5:\"class\";s:0:\"\";s:13:\"before_widget\";s:39:\"<section id=\"%1$s\" class=\"widget %2$s\">\";s:12:\"after_widget\";s:10:\"</section>\";s:12:\"before_title\";s:25:\"<h2 class=\"widget-title\">\";s:11:\"after_title\";s:5:\"</h2>\";s:14:\"before_sidebar\";s:0:\"\";s:13:\"after_sidebar\";s:0:\"\";s:12:\"show_in_rest\";b:0;}}s:18:\"nav_menu_locations\";a:0:{}}','off'),(129,'recovery_keys','a:0:{}','off'),(156,'recently_activated','a:0:{}','off'),(159,'finished_updating_comment_type','1','auto'),(661,'alm_structure_children','a:0:{}','auto'),(675,'alm_state_children','a:0:{}','auto'),(678,'alm_level_children','a:0:{}','auto'),(681,'members_activated','1767260630','auto'),(682,'members_addons_migrated','1','auto'),(683,'widget_members-widget-login','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(684,'widget_members-widget-users','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(686,'members_notifications','a:4:{s:6:\"update\";i:1782323273;s:4:\"feed\";a:0:{}s:6:\"events\";a:0:{}s:9:\"dismissed\";a:0:{}}','auto'),(693,'acf_first_activated_version','6.7.0','on'),(694,'acf_site_health','{\"version\":\"6.8.4\",\"plugin_type\":\"Free\",\"update_source\":\"wordpress.org\",\"wp_version\":\"7.1\",\"mysql_version\":\"10.6.23-MariaDB\",\"is_multisite\":false,\"active_theme\":{\"name\":\"Twenty Twenty-One\",\"version\":\"2.8\",\"theme_uri\":\"https:\\/\\/wordpress.org\\/themes\\/twentytwentyone\\/\",\"stylesheet\":false},\"active_plugins\":{\"advanced-custom-fields\\/acf.php\":{\"name\":\"Advanced Custom Fields\",\"version\":\"6.8.4\",\"plugin_uri\":\"https:\\/\\/www.advancedcustomfields.com\"},\"asset-lending-manager\\/asset-lending-manager.php\":{\"name\":\"Asset Lending Manager\",\"version\":\"0.4.0\",\"plugin_uri\":\"https:\\/\\/github.com\\/ilclaudio\\/asset-lending-manager\"},\"loco-translate\\/loco.php\":{\"name\":\"Loco Translate\",\"version\":\"2.8.5\",\"plugin_uri\":\"https:\\/\\/wordpress.org\\/plugins\\/loco-translate\\/\"},\"members\\/members.php\":{\"name\":\"Members\",\"version\":\"3.2.22\",\"plugin_uri\":\"https:\\/\\/members-plugin.com\\/\"},\"plugin-check\\/plugin.php\":{\"name\":\"Plugin Check (PCP)\",\"version\":\"2.0.0\",\"plugin_uri\":\"https:\\/\\/github.com\\/WordPress\\/plugin-check\"},\"wordpress-beta-tester\\/wp-beta-tester.php\":{\"name\":\"WordPress Beta Tester\",\"version\":\"4.0.0\",\"plugin_uri\":\"https:\\/\\/wordpress.org\\/plugins\\/wordpress-beta-tester\\/\"},\"wpforms-lite\\/wpforms.php\":{\"name\":\"WPForms Lite\",\"version\":\"1.10.2\",\"plugin_uri\":\"https:\\/\\/wpforms.com\"},\"wp-mail-smtp\\/wp_mail_smtp.php\":{\"name\":\"WP Mail SMTP\",\"version\":\"4.8.0\",\"plugin_uri\":\"https:\\/\\/wpmailsmtp.com\\/\"}},\"ui_field_groups\":\"0\",\"php_field_groups\":\"0\",\"json_field_groups\":\"0\",\"rest_field_groups\":\"0\",\"all_location_rules\":[\"post_type==almgr_asset\"],\"number_of_fields_by_type\":{\"text\":7,\"date_picker\":1,\"number\":1,\"post_object\":1,\"file\":2,\"textarea\":1},\"number_of_third_party_fields_by_type\":[],\"post_types_enabled\":true,\"ui_post_types\":\"0\",\"json_post_types\":\"0\",\"ui_taxonomies\":\"0\",\"json_taxonomies\":\"0\",\"rest_api_format\":\"light\",\"admin_ui_enabled\":true,\"field_type-modal_enabled\":true,\"field_settings_tabs_enabled\":false,\"shortcode_enabled\":false,\"registered_acf_forms\":\"0\",\"json_save_paths\":1,\"json_load_paths\":1,\"ai_enabled\":false,\"schema_support\":false,\"schema_ready_objects\":{\"blocks\":0,\"post_types\":0},\"event_first_activated\":1767260672,\"event_first_created_field_group\":1767354073,\"last_updated\":1788104404}','off'),(696,'acf_version','6.8.4','auto'),(703,'current_theme','Twenty Twenty-One','auto'),(704,'theme_mods_twentytwentyone','a:4:{i:0;b:0;s:18:\"nav_menu_locations\";a:0:{}s:18:\"custom_css_post_id\";i:-1;s:16:\"sidebars_widgets\";a:2:{s:4:\"time\";i:1777735786;s:4:\"data\";a:2:{s:19:\"wp_inactive_widgets\";a:0:{}s:9:\"sidebar-1\";a:1:{i:0;s:7:\"block-4\";}}}}','on'),(705,'theme_switched','','auto'),(770,'wpmdb_usage','a:2:{s:6:\"action\";s:8:\"savefile\";s:4:\"time\";i:1782323307;}','off'),(802,'members_review_prompt_delay','a:1:{s:13:\"delayed_until\";i:1782409393;}','auto'),(804,'alm_type_children','a:0:{}','auto'),(832,'loco_recent','a:4:{s:1:\"c\";s:21:\"Loco_data_RecentItems\";s:1:\"v\";i:0;s:1:\"d\";a:1:{s:6:\"bundle\";a:1:{s:54:\"plugin.asset-lending-manager/asset-lending-manager.php\";i:1782227856;}}s:1:\"t\";i:1782227856;}','off'),(853,'category_children','a:0:{}','auto'),(917,'recovery_mode_email_last_sent','1775658074','auto'),(975,'ure_tasks_queue','a:0:{}','auto'),(1334,'loco_settings','a:4:{s:1:\"c\";s:18:\"Loco_data_Settings\";s:1:\"v\";i:0;s:1:\"d\";a:28:{s:7:\"version\";s:5:\"2.8.5\";s:8:\"gen_hash\";b:0;s:9:\"use_fuzzy\";b:1;s:9:\"fuzziness\";i:20;s:11:\"num_backups\";i:5;s:9:\"pot_alias\";a:3:{i:0;s:10:\"default.po\";i:1;s:8:\"en_US.po\";i:2;s:5:\"en.po\";}s:9:\"php_alias\";a:2:{i:0;s:3:\"php\";i:1;s:4:\"twig\";}s:9:\"jsx_alias\";a:1:{i:0;s:4:\"*.js\";}s:10:\"fs_persist\";b:0;s:10:\"fs_protect\";i:1;s:11:\"pot_protect\";i:1;s:12:\"pot_expected\";i:1;s:12:\"max_php_size\";s:4:\"100K\";s:11:\"po_utf8_bom\";b:0;s:8:\"po_width\";s:2:\"79\";s:10:\"jed_pretty\";b:0;s:9:\"jed_clean\";b:0;s:10:\"ajax_files\";b:1;s:13:\"deepl_api_key\";s:0:\"\";s:14:\"google_api_key\";s:0:\"\";s:17:\"microsoft_api_key\";s:0:\"\";s:20:\"microsoft_api_region\";s:6:\"global\";s:13:\"lecto_api_key\";s:0:\"\";s:14:\"openai_api_key\";s:0:\"\";s:16:\"openai_api_model\";s:0:\"\";s:17:\"openai_api_prompt\";s:0:\"\";s:9:\"code_view\";i:1;s:10:\"fs_basedir\";s:10:\"wp-content\";}s:1:\"t\";i:1782226707;}','auto'),(1375,'action_scheduler_hybrid_store_demarkation','80','auto'),(1376,'schema-ActionScheduler_StoreSchema','8.0.1771539148','auto'),(1377,'schema-ActionScheduler_LoggerSchema','3.0.1771539148','auto'),(1378,'wp_mail_smtp_initial_version','4.7.1','off'),(1379,'wp_mail_smtp_version','4.7.1','off'),(1381,'wp_mail_smtp_activated_time','1771539148','off'),(1382,'wp_mail_smtp_activated','a:1:{s:4:\"lite\";i:1771539148;}','auto'),(1388,'action_scheduler_lock_async-request-runner','6a3c2195688001.18847220|1782325713','no'),(1391,'wp_mail_smtp_migration_version','5','on'),(1392,'wp_mail_smtp_debug_events_db_version','1','on'),(1393,'wp_mail_smtp_activation_prevent_redirect','1','auto'),(1394,'wp_mail_smtp_setup_wizard_stats','a:3:{s:13:\"launched_time\";i:1771539160;s:14:\"completed_time\";i:1771539847;s:14:\"was_successful\";b:0;}','off'),(1395,'wp_mail_smtp_mail_key','XXDeh8qVKHLt58nqWuxgXRDr0+ySsx/kj+0r58Nn3q0=','auto'),(1398,'as_has_wp_comment_logs','no','on'),(1401,'wpforms_activation_redirect','1','auto'),(1402,'wpforms_installation_source','wp-mail-smtp-setup-wizard','auto'),(1403,'wpforms_version','1.9.9.2','auto'),(1404,'wpforms_version_lite','1.9.9.2','auto'),(1405,'wpforms_activated','a:1:{s:4:\"lite\";i:1771539832;}','auto'),(1410,'wpforms_versions_lite','a:26:{s:5:\"1.5.9\";i:0;s:7:\"1.6.7.2\";i:0;s:5:\"1.6.8\";i:0;s:5:\"1.7.5\";i:0;s:7:\"1.7.5.1\";i:0;s:5:\"1.7.7\";i:0;s:5:\"1.8.2\";i:0;s:5:\"1.8.3\";i:0;s:5:\"1.8.4\";i:0;s:5:\"1.8.6\";i:0;s:5:\"1.8.7\";i:0;s:5:\"1.9.1\";i:0;s:5:\"1.9.2\";i:0;s:5:\"1.9.7\";i:0;s:7:\"1.9.8.6\";i:0;s:7:\"1.9.9.2\";i:1771539844;s:7:\"1.9.9.3\";i:1771967796;s:7:\"1.9.9.4\";i:1772662123;s:8:\"1.10.0.1\";i:1774107763;s:8:\"1.10.0.2\";i:1774965614;s:8:\"1.10.0.3\";i:1775681431;s:8:\"1.10.0.4\";i:1775923670;s:8:\"1.10.0.5\";i:1779291868;s:6:\"1.10.1\";i:1780320125;s:8:\"1.10.1.1\";i:1781804624;s:6:\"1.10.2\";i:1782204072;}','auto'),(1411,'wpforms_constant_contact_version','3','auto'),(1412,'widget_wpforms-widget','a:1:{s:12:\"_multiwidget\";i:1;}','auto'),(1413,'wpforms_settings','a:3:{s:13:\"modern-markup\";s:1:\"1\";s:20:\"modern-markup-is-set\";b:1;s:26:\"modern-markup-hide-setting\";b:1;}','auto'),(1414,'wpforms_admin_notices','a:1:{s:14:\"review_request\";a:2:{s:4:\"time\";i:1771539844;s:9:\"dismissed\";b:0;}}','auto'),(1415,'wp_mail_smtp_debug','a:2:{i:0;i:9;i:1;i:10;}','off'),(1417,'wpforms_splash_version','1.8.6','auto'),(1420,'wp_mail_smtp_review_notice','a:2:{s:4:\"time\";i:1771539855;s:9:\"dismissed\";b:0;}','auto'),(1422,'_wpforms_transient_upload_htaccess_file','a:3:{s:4:\"size\";i:737;s:5:\"mtime\";i:1782324972;s:5:\"ctime\";i:1782324972;}','on'),(1423,'wpforms_email_summaries_fetch_info_blocks_last_run','1787926410','auto'),(1424,'wpforms_process_forms_locator_status','completed','auto'),(1425,'wpforms_notifications','a:4:{s:4:\"feed\";a:0:{}s:6:\"events\";a:0:{}s:9:\"dismissed\";a:0:{}s:6:\"update\";i:1782323266;}','auto'),(1426,'wp_mail_smtp_notifications','a:4:{s:6:\"update\";i:1788104400;s:4:\"feed\";a:0:{}s:6:\"events\";a:0:{}s:9:\"dismissed\";a:0:{}}','auto'),(1439,'wp_mail_smtp_lite_sent_email_counter','115','on'),(1440,'wp_mail_smtp_lite_weekly_sent_email_counter','a:12:{s:2:\"08\";i:13;s:2:\"09\";i:3;i:10;i:10;i:11;i:17;i:12;i:3;i:15;i:22;i:16;i:2;i:18;i:21;i:21;i:3;i:23;i:4;i:25;i:15;i:35;i:2;}','on'),(1477,'_wpforms_transient_wpforms_C:/Users/Claudio/Local Sites/alm-site/app/public/wp-content/uploads/wpforms/cache/.htaccess_file','a:3:{s:4:\"size\";i:446;s:5:\"mtime\";i:1782204073;s:5:\"ctime\";i:1771689463;}','on'),(1902,'wpforms_version_previous','1.10.1.1','auto'),(2881,'action_scheduler_migration_status','complete','auto'),(2921,'alm_settings','a:11:{s:5:\"email\";a:3:{s:9:\"from_name\";s:0:\"\";s:12:\"from_address\";s:0:\"\";s:12:\"system_email\";s:0:\"\";}s:13:\"notifications\";a:4:{s:7:\"enabled\";b:1;s:12:\"loan_request\";b:1;s:13:\"loan_decision\";b:1;s:17:\"loan_confirmation\";b:1;}s:8:\"template\";a:2:{s:7:\"subject\";a:8:{s:20:\"request_to_requester\";s:50:\"[ALM] Richiesta di prestito inviata: {ASSET_TITLE}\";s:16:\"request_to_owner\";s:57:\"[ALM] Nuova richiesta di prestito ricevuta: {ASSET_TITLE}\";s:8:\"approved\";s:52:\"[ALM] Richiesta di prestito approvata: {ASSET_TITLE}\";s:8:\"rejected\";s:52:\"[ALM] Richiesta di prestito rifiutata: {ASSET_TITLE}\";s:8:\"canceled\";s:52:\"[ALM] Richiesta di prestito annullata: {ASSET_TITLE}\";s:13:\"direct_assign\";s:43:\"[ALM] Risorsa assegnata a te: {ASSET_TITLE}\";s:27:\"direct_assign_to_prev_owner\";s:40:\"[ALM] Risorsa riassegnata: {ASSET_TITLE}\";s:12:\"force_return\";s:35:\"[ALM] Asset returned: {ASSET_TITLE}\";}s:4:\"body\";a:8:{s:20:\"request_to_requester\";s:163:\"Ciao {REQUESTER_NAME},\n\nLa tua richiesta di prestito per \"{ASSET_TITLE}\" è stata inviata ed è in attesa di approvazione.\n\nVisualizza risorsa: {ASSET_URL}\n\n-- ALM\";s:16:\"request_to_owner\";s:137:\"Salve,\n\n{REQUESTER_NAME} ha richiesto in prestito \"{ASSET_TITLE}\".\n\nMessaggio: {REQUEST_MESSAGE}\n\nVisualizza risorsa: {ASSET_URL}\n\n-- ALM\";s:8:\"approved\";s:133:\"Ciao {REQUESTER_NAME},\n\nLa tua richiesta di prestito per \"{ASSET_TITLE}\" è stata approvata.\n\nVisualizza risorsa: {ASSET_URL}\n\n-- ALM\";s:8:\"rejected\";s:162:\"Ciao {REQUESTER_NAME},\n\nLa tua richiesta di prestito per \"{ASSET_TITLE}\" è stata rifiutata.\n\nMotivo: {REJECTION_MESSAGE}\n\nVisualizza risorsa: {ASSET_URL}\n\n-- ALM\";s:8:\"canceled\";s:205:\"Ciao {REQUESTER_NAME},\n\nLa tua richiesta di prestito per \"{ASSET_TITLE}\" è stata annullata automaticamente perché la risorsa è stata assegnata a un altro utente.\n\nVisualizza risorsa: {ASSET_URL}\n\n-- ALM\";s:13:\"direct_assign\";s:147:\"Ciao {ASSIGNEE_NAME},\n\nLa risorsa \"{ASSET_TITLE}\" ti è stata assegnata da {ACTOR_NAME}.\n\nMotivo: {REASON}\n\nVisualizza risorsa: {ASSET_URL}\n\n-- ALM\";s:27:\"direct_assign_to_prev_owner\";s:166:\"Ciao {PREV_OWNER_NAME},\n\nLa risorsa \"{ASSET_TITLE}\" è stata riassegnata a {ASSIGNEE_NAME} da {ACTOR_NAME}.\n\nMotivo: {REASON}\n\nVisualizza risorsa: {ASSET_URL}\n\n-- ALM\";s:12:\"force_return\";s:147:\"Hello {BORROWER_NAME},\n\nThe loan for \"{ASSET_TITLE}\" has been closed by the operator {ACTOR_NAME}.\n\nNotes: {NOTES}\n\nView asset: {ASSET_URL}\n\n-- ALM\";}}s:5:\"loans\";a:7:{s:21:\"loan_requests_enabled\";b:1;s:19:\"max_active_per_user\";i:0;s:23:\"allow_multiple_requests\";b:1;s:26:\"request_message_max_length\";i:500;s:28:\"rejection_message_max_length\";i:500;s:31:\"direct_assign_reason_max_length\";i:500;s:29:\"change_state_notes_max_length\";i:500;}s:13:\"direct_assign\";a:2:{s:7:\"enabled\";b:1;s:20:\"allowed_target_roles\";a:2:{i:0;s:10:\"alm_member\";i:1;s:12:\"alm_operator\";}}s:8:\"workflow\";a:3:{s:36:\"cancel_concurrent_requests_on_assign\";b:1;s:43:\"cancel_component_requests_when_kit_assigned\";b:1;s:34:\"automatic_operations_actor_user_id\";i:1;}s:8:\"frontend\";a:5:{s:14:\"assets_page_id\";i:0;s:22:\"login_redirect_page_id\";i:0;s:23:\"logout_redirect_page_id\";i:2;s:19:\"asset_list_per_page\";i:12;s:20:\"default_filters_open\";b:0;}s:12:\"autocomplete\";a:5:{s:9:\"min_chars\";i:3;s:11:\"max_results\";i:5;s:18:\"description_length\";i:20;s:30:\"public_assets_endpoint_enabled\";b:1;s:15:\"qr_scan_enabled\";b:1;}s:7:\"logging\";a:4:{s:7:\"enabled\";b:0;s:5:\"level\";s:5:\"error\";s:18:\"mask_personal_data\";b:0;s:16:\"log_email_events\";b:0;}s:5:\"asset\";a:1:{s:11:\"code_prefix\";s:3:\"ALM\";}s:8:\"rest_api\";a:1:{s:7:\"enabled\";b:1;}}','auto'),(3265,'using_application_passwords','1','off'),(3343,'almgr_settings','a:11:{s:5:\"email\";a:3:{s:9:\"from_name\";s:7:\"ALM E2E\";s:12:\"from_address\";s:20:\"noreply@example.test\";s:12:\"system_email\";s:19:\"system@example.test\";}s:13:\"notifications\";a:5:{s:7:\"enabled\";b:1;s:12:\"loan_request\";b:1;s:26:\"loan_request_operator_mode\";s:5:\"never\";s:13:\"loan_decision\";b:1;s:13:\"direct_assign\";b:1;}s:8:\"template\";a:2:{s:7:\"subject\";a:8:{s:20:\"request_to_requester\";s:50:\"[ALM] Richiesta di prestito inviata: {ASSET_TITLE}\";s:16:\"request_to_owner\";s:57:\"[ALM] Nuova richiesta di prestito ricevuta: {ASSET_TITLE}\";s:8:\"approved\";s:52:\"[ALM] Richiesta di prestito approvata: {ASSET_TITLE}\";s:8:\"rejected\";s:52:\"[ALM] Richiesta di prestito rifiutata: {ASSET_TITLE}\";s:8:\"canceled\";s:52:\"[ALM] Richiesta di prestito annullata: {ASSET_TITLE}\";s:13:\"direct_assign\";s:43:\"[ALM] Risorsa assegnata a te: {ASSET_TITLE}\";s:27:\"direct_assign_to_prev_owner\";s:40:\"[ALM] Risorsa riassegnata: {ASSET_TITLE}\";s:12:\"force_return\";s:35:\"[ALM] Asset returned: {ASSET_TITLE}\";}s:4:\"body\";a:8:{s:20:\"request_to_requester\";s:169:\"Ciao {REQUESTER_NAME},\r\n\r\nLa tua richiesta di prestito per \"{ASSET_TITLE}\" è stata inviata ed è in attesa di approvazione.\r\n\r\nVisualizza risorsa: {ASSET_URL}\r\n\r\n-- ALM\";s:16:\"request_to_owner\";s:145:\"Salve,\r\n\r\n{REQUESTER_NAME} ha richiesto in prestito \"{ASSET_TITLE}\".\r\n\r\nMessaggio: {REQUEST_MESSAGE}\r\n\r\nVisualizza risorsa: {ASSET_URL}\r\n\r\n-- ALM\";s:8:\"approved\";s:139:\"Ciao {REQUESTER_NAME},\r\n\r\nLa tua richiesta di prestito per \"{ASSET_TITLE}\" è stata approvata.\r\n\r\nVisualizza risorsa: {ASSET_URL}\r\n\r\n-- ALM\";s:8:\"rejected\";s:170:\"Ciao {REQUESTER_NAME},\r\n\r\nLa tua richiesta di prestito per \"{ASSET_TITLE}\" è stata rifiutata.\r\n\r\nMotivo: {REJECTION_MESSAGE}\r\n\r\nVisualizza risorsa: {ASSET_URL}\r\n\r\n-- ALM\";s:8:\"canceled\";s:211:\"Ciao {REQUESTER_NAME},\r\n\r\nLa tua richiesta di prestito per \"{ASSET_TITLE}\" è stata annullata automaticamente perché la risorsa è stata assegnata a un altro utente.\r\n\r\nVisualizza risorsa: {ASSET_URL}\r\n\r\n-- ALM\";s:13:\"direct_assign\";s:155:\"Ciao {ASSIGNEE_NAME},\r\n\r\nLa risorsa \"{ASSET_TITLE}\" ti è stata assegnata da {ACTOR_NAME}.\r\n\r\nMotivo: {REASON}\r\n\r\nVisualizza risorsa: {ASSET_URL}\r\n\r\n-- ALM\";s:27:\"direct_assign_to_prev_owner\";s:174:\"Ciao {PREV_OWNER_NAME},\r\n\r\nLa risorsa \"{ASSET_TITLE}\" è stata riassegnata a {ASSIGNEE_NAME} da {ACTOR_NAME}.\r\n\r\nMotivo: {REASON}\r\n\r\nVisualizza risorsa: {ASSET_URL}\r\n\r\n-- ALM\";s:12:\"force_return\";s:147:\"Hello {BORROWER_NAME},\n\nThe loan for \"{ASSET_TITLE}\" has been closed by the operator {ACTOR_NAME}.\n\nNotes: {NOTES}\n\nView asset: {ASSET_URL}\n\n-- ALM\";}}s:5:\"loans\";a:7:{s:21:\"loan_requests_enabled\";b:1;s:19:\"max_active_per_user\";i:0;s:23:\"allow_multiple_requests\";b:1;s:26:\"request_message_max_length\";i:500;s:28:\"rejection_message_max_length\";i:500;s:31:\"direct_assign_reason_max_length\";i:500;s:29:\"change_state_notes_max_length\";i:500;}s:13:\"direct_assign\";a:2:{s:7:\"enabled\";b:1;s:20:\"allowed_target_roles\";a:2:{i:0;s:12:\"almgr_member\";i:1;s:14:\"almgr_operator\";}}s:8:\"workflow\";a:3:{s:36:\"cancel_concurrent_requests_on_assign\";b:1;s:43:\"cancel_component_requests_when_kit_assigned\";b:1;s:34:\"automatic_operations_actor_user_id\";i:1;}s:8:\"frontend\";a:7:{s:14:\"assets_page_id\";i:0;s:18:\"asset_view_page_id\";i:0;s:21:\"asset_history_page_id\";i:125;s:22:\"login_redirect_page_id\";i:0;s:23:\"logout_redirect_page_id\";i:0;s:19:\"asset_list_per_page\";i:12;s:20:\"default_filters_open\";b:0;}s:12:\"autocomplete\";a:5:{s:9:\"min_chars\";i:3;s:11:\"max_results\";i:5;s:18:\"description_length\";i:20;s:30:\"public_assets_endpoint_enabled\";b:1;s:15:\"qr_scan_enabled\";b:1;}s:7:\"logging\";a:4:{s:7:\"enabled\";b:1;s:5:\"level\";s:5:\"debug\";s:18:\"mask_personal_data\";b:0;s:16:\"log_email_events\";b:0;}s:5:\"asset\";a:1:{s:11:\"code_prefix\";s:3:\"ALM\";}s:8:\"rest_api\";a:1:{s:7:\"enabled\";b:1;}}','auto'),(3375,'almgr_type_children','a:0:{}','auto'),(3505,'almgr_structure_children','a:0:{}','auto'),(3507,'almgr_state_children','a:0:{}','auto'),(3849,'almgr_level_children','a:0:{}','auto'),(4100,'almgr_role_capabilities_version','20260425_media_permissions','off'),(4779,'wp_beta_tester','a:2:{s:7:\"channel\";s:11:\"development\";s:13:\"stream-option\";s:2:\"rc\";}','off'),(4814,'db_upgraded','','on'),(4816,'can_compress_scripts','1','on'),(5375,'_transient_wp_styles_for_blocks','a:2:{s:4:\"hash\";s:32:\"e1dc145c21f962e6b5155c9683f57168\";s:6:\"blocks\";a:9:{s:32:\"832dc2d864d79097d8b8b493ad93453b\";s:0:\"\";s:32:\"45d3e0c4afcbd8cf25cb1ba51abfb3d7\";s:46:\":root :where(.wp-block-icon svg){width: 24px;}\";s:32:\"feca6e996f694be2d29599793228e0d7\";s:0:\"\";s:32:\"5eef131663eddaf830554df656fc2968\";s:324:\":where(.wp-block-gallery.is-layout-flex){gap: var( --wp--style--gallery-gap-default, var( --gallery-block--gutter-size, var( --wp--style--block-gap, 0.5em ) ) );}:where(.wp-block-gallery.is-layout-grid){gap: var( --wp--style--gallery-gap-default, var( --gallery-block--gutter-size, var( --wp--style--block-gap, 0.5em ) ) );}\";s:32:\"c99c05932c6685777ec5b856698fcc7d\";s:118:\":where(.wp-block-latest-posts.is-layout-flex){gap: 1.25em;}:where(.wp-block-latest-posts.is-layout-grid){gap: 1.25em;}\";s:32:\"dec8d648f30b13caec8e61374591787d\";s:120:\":where(.wp-block-post-template.is-layout-flex){gap: 1.25em;}:where(.wp-block-post-template.is-layout-grid){gap: 1.25em;}\";s:32:\"6c35533f7a92cce94808323603db9fc8\";s:120:\":where(.wp-block-term-template.is-layout-flex){gap: 1.25em;}:where(.wp-block-term-template.is-layout-grid){gap: 1.25em;}\";s:32:\"6a0505cd5c78a87ed77570cda43c1132\";s:102:\":where(.wp-block-columns.is-layout-flex){gap: 2em;}:where(.wp-block-columns.is-layout-grid){gap: 2em;}\";s:32:\"25a66f156386551185570f72a9f7d44e\";s:69:\":root :where(.wp-block-pullquote){font-size: 1.5em;line-height: 1.6;}\";}}','on'),(5389,'_wpforms_transient_wpforms_C:/Users/Claudio/Local Sites/alm-e2e/app/public/wp-content/uploads/wpforms/cache/.htaccess_file','a:3:{s:4:\"size\";i:446;s:5:\"mtime\";i:1782324972;s:5:\"ctime\";i:1782324972;}','on'),(5451,'_transient_health-check-site-status-result','{\"good\":16,\"recommended\":5,\"critical\":2}','on'),(5530,'core_updater.lock','1787414760','off'),(5541,'_site_transient_timeout_php_check_f97435f29f75964c6616760b2555a445','1788531212','off'),(5542,'_site_transient_php_check_f97435f29f75964c6616760b2555a445','a:5:{s:19:\"recommended_version\";s:3:\"8.3\";s:15:\"minimum_version\";s:3:\"7.4\";s:12:\"is_supported\";b:1;s:9:\"is_secure\";b:1;s:13:\"is_acceptable\";b:1;}','off'),(5566,'_site_transient_update_core','O:8:\"stdClass\":4:{s:7:\"updates\";a:1:{i:0;O:8:\"stdClass\":10:{s:8:\"response\";s:6:\"latest\";s:8:\"download\";s:57:\"https://downloads.wordpress.org/release/wordpress-7.1.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:57:\"https://downloads.wordpress.org/release/wordpress-7.1.zip\";s:10:\"no_content\";s:68:\"https://downloads.wordpress.org/release/wordpress-7.1-no-content.zip\";s:11:\"new_bundled\";s:69:\"https://downloads.wordpress.org/release/wordpress-7.1-new-bundled.zip\";s:7:\"partial\";s:0:\"\";s:8:\"rollback\";s:0:\"\";}s:7:\"current\";s:3:\"7.1\";s:7:\"version\";s:3:\"7.1\";s:11:\"php_version\";s:3:\"7.4\";s:13:\"mysql_version\";s:5:\"5.5.5\";s:11:\"new_bundled\";s:3:\"6.7\";s:15:\"partial_version\";s:0:\"\";}}s:12:\"last_checked\";i:1788104962;s:15:\"version_checked\";s:3:\"7.1\";s:12:\"translations\";a:0:{}}','off'),(5611,'_site_transient_timeout_theme_roots','1788106202','off'),(5612,'_site_transient_theme_roots','a:2:{s:16:\"twentytwentyfive\";s:7:\"/themes\";s:15:\"twentytwentyone\";s:7:\"/themes\";}','off'),(5622,'_site_transient_update_themes','O:8:\"stdClass\":5:{s:12:\"last_checked\";i:1788104963;s:7:\"checked\";a:2:{s:16:\"twentytwentyfive\";s:3:\"1.5\";s:15:\"twentytwentyone\";s:3:\"2.8\";}s:8:\"response\";a:1:{s:15:\"twentytwentyone\";a:6:{s:5:\"theme\";s:15:\"twentytwentyone\";s:11:\"new_version\";s:3:\"2.9\";s:3:\"url\";s:45:\"https://wordpress.org/themes/twentytwentyone/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/theme/twentytwentyone.2.9.zip\";s:8:\"requires\";s:3:\"5.3\";s:12:\"requires_php\";s:3:\"5.6\";}}s:9:\"no_update\";a:1:{s:16:\"twentytwentyfive\";a:6:{s:5:\"theme\";s:16:\"twentytwentyfive\";s:11:\"new_version\";s:3:\"1.5\";s:3:\"url\";s:46:\"https://wordpress.org/themes/twentytwentyfive/\";s:7:\"package\";s:62:\"https://downloads.wordpress.org/theme/twentytwentyfive.1.5.zip\";s:8:\"requires\";s:3:\"6.7\";s:12:\"requires_php\";s:3:\"7.2\";}}s:12:\"translations\";a:0:{}}','off'),(5624,'_site_transient_update_plugins','O:8:\"stdClass\":5:{s:12:\"last_checked\";i:1788104977;s:8:\"response\";a:8:{s:30:\"advanced-custom-fields/acf.php\";O:8:\"stdClass\":13:{s:2:\"id\";s:36:\"w.org/plugins/advanced-custom-fields\";s:4:\"slug\";s:22:\"advanced-custom-fields\";s:6:\"plugin\";s:30:\"advanced-custom-fields/acf.php\";s:11:\"new_version\";s:5:\"6.8.9\";s:3:\"url\";s:53:\"https://wordpress.org/plugins/advanced-custom-fields/\";s:7:\"package\";s:70:\"http://downloads.wordpress.org/plugin/advanced-custom-fields.6.8.9.zip\";s:5:\"icons\";a:2:{s:2:\"1x\";s:67:\"https://ps.w.org/advanced-custom-fields/assets/icon.svg?rev=3207824\";s:3:\"svg\";s:67:\"https://ps.w.org/advanced-custom-fields/assets/icon.svg?rev=3207824\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:78:\"https://ps.w.org/advanced-custom-fields/assets/banner-1544x500.jpg?rev=3374528\";s:2:\"1x\";s:77:\"https://ps.w.org/advanced-custom-fields/assets/banner-772x250.jpg?rev=3374528\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"6.2\";s:6:\"tested\";s:3:\"7.1\";s:12:\"requires_php\";s:3:\"7.4\";s:16:\"requires_plugins\";a:0:{}}s:60:\"asset-lending-manager.stale-backup/asset-lending-manager.php\";O:8:\"stdClass\":14:{s:2:\"id\";s:35:\"w.org/plugins/asset-lending-manager\";s:4:\"slug\";s:21:\"asset-lending-manager\";s:6:\"plugin\";s:60:\"asset-lending-manager.stale-backup/asset-lending-manager.php\";s:11:\"new_version\";s:5:\"0.3.2\";s:3:\"url\";s:52:\"https://wordpress.org/plugins/asset-lending-manager/\";s:7:\"package\";s:69:\"http://downloads.wordpress.org/plugin/asset-lending-manager.0.3.2.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:74:\"https://ps.w.org/asset-lending-manager/assets/icon-256x256.png?rev=3531387\";s:2:\"1x\";s:74:\"https://ps.w.org/asset-lending-manager/assets/icon-128x128.png?rev=3531387\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:77:\"https://ps.w.org/asset-lending-manager/assets/banner-1544x500.png?rev=3531387\";s:2:\"1x\";s:76:\"https://ps.w.org/asset-lending-manager/assets/banner-772x250.png?rev=3531387\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"6.2\";s:6:\"tested\";s:3:\"7.1\";s:12:\"requires_php\";s:3:\"7.4\";s:16:\"requires_plugins\";a:1:{i:0;s:22:\"advanced-custom-fields\";}s:14:\"upgrade_notice\";s:83:\"<p>Documentation-only release. No code or database changes; no action required.</p>\";}s:23:\"loco-translate/loco.php\";O:8:\"stdClass\":14:{s:2:\"id\";s:28:\"w.org/plugins/loco-translate\";s:4:\"slug\";s:14:\"loco-translate\";s:6:\"plugin\";s:23:\"loco-translate/loco.php\";s:11:\"new_version\";s:5:\"2.8.8\";s:3:\"url\";s:45:\"https://wordpress.org/plugins/loco-translate/\";s:7:\"package\";s:62:\"http://downloads.wordpress.org/plugin/loco-translate.2.8.8.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/loco-translate/assets/icon-256x256.png?rev=1000676\";s:2:\"1x\";s:67:\"https://ps.w.org/loco-translate/assets/icon-128x128.png?rev=1000676\";}s:7:\"banners\";a:1:{s:2:\"1x\";s:68:\"https://ps.w.org/loco-translate/assets/banner-772x250.jpg?rev=745046\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"6.6\";s:6:\"tested\";s:5:\"7.0.4\";s:12:\"requires_php\";s:3:\"7.4\";s:16:\"requires_plugins\";a:0:{}s:14:\"upgrade_notice\";s:54:\"<ul>\n<li>Various improvements and bug fixes</li>\n</ul>\";}s:19:\"members/members.php\";O:8:\"stdClass\":13:{s:2:\"id\";s:21:\"w.org/plugins/members\";s:4:\"slug\";s:7:\"members\";s:6:\"plugin\";s:19:\"members/members.php\";s:11:\"new_version\";s:6:\"3.2.26\";s:3:\"url\";s:38:\"https://wordpress.org/plugins/members/\";s:7:\"package\";s:56:\"http://downloads.wordpress.org/plugin/members.3.2.26.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:60:\"https://ps.w.org/members/assets/icon-256x256.png?rev=3508404\";s:2:\"1x\";s:60:\"https://ps.w.org/members/assets/icon-128x128.png?rev=3508404\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:63:\"https://ps.w.org/members/assets/banner-1544x500.png?rev=3508404\";s:2:\"1x\";s:62:\"https://ps.w.org/members/assets/banner-772x250.png?rev=3508404\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"6.0\";s:6:\"tested\";s:3:\"7.1\";s:12:\"requires_php\";s:3:\"7.4\";s:16:\"requires_plugins\";a:0:{}}s:23:\"plugin-check/plugin.php\";O:8:\"stdClass\":13:{s:2:\"id\";s:26:\"w.org/plugins/plugin-check\";s:4:\"slug\";s:12:\"plugin-check\";s:6:\"plugin\";s:23:\"plugin-check/plugin.php\";s:11:\"new_version\";s:5:\"2.1.0\";s:3:\"url\";s:43:\"https://wordpress.org/plugins/plugin-check/\";s:7:\"package\";s:60:\"http://downloads.wordpress.org/plugin/plugin-check.2.1.0.zip\";s:5:\"icons\";a:2:{s:2:\"1x\";s:57:\"https://ps.w.org/plugin-check/assets/icon.svg?rev=3166100\";s:3:\"svg\";s:57:\"https://ps.w.org/plugin-check/assets/icon.svg?rev=3166100\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:68:\"https://ps.w.org/plugin-check/assets/banner-1544x500.png?rev=3166100\";s:2:\"1x\";s:67:\"https://ps.w.org/plugin-check/assets/banner-772x250.png?rev=3166100\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"6.3\";s:6:\"tested\";s:5:\"7.0.4\";s:12:\"requires_php\";s:3:\"7.4\";s:16:\"requires_plugins\";a:0:{}}s:40:\"wordpress-beta-tester/wp-beta-tester.php\";O:8:\"stdClass\":13:{s:2:\"id\";s:35:\"w.org/plugins/wordpress-beta-tester\";s:4:\"slug\";s:21:\"wordpress-beta-tester\";s:6:\"plugin\";s:40:\"wordpress-beta-tester/wp-beta-tester.php\";s:11:\"new_version\";s:5:\"4.0.1\";s:3:\"url\";s:52:\"https://wordpress.org/plugins/wordpress-beta-tester/\";s:7:\"package\";s:69:\"http://downloads.wordpress.org/plugin/wordpress-beta-tester.4.0.1.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:74:\"https://ps.w.org/wordpress-beta-tester/assets/icon-256x256.png?rev=2562317\";s:2:\"1x\";s:74:\"https://ps.w.org/wordpress-beta-tester/assets/icon-128x128.png?rev=2562317\";}s:7:\"banners\";a:0:{}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"4.9\";s:6:\"tested\";s:3:\"7.1\";s:12:\"requires_php\";s:3:\"5.6\";s:16:\"requires_plugins\";a:0:{}}s:24:\"wpforms-lite/wpforms.php\";O:8:\"stdClass\":13:{s:2:\"id\";s:26:\"w.org/plugins/wpforms-lite\";s:4:\"slug\";s:12:\"wpforms-lite\";s:6:\"plugin\";s:24:\"wpforms-lite/wpforms.php\";s:11:\"new_version\";s:7:\"2.0.1.1\";s:3:\"url\";s:43:\"https://wordpress.org/plugins/wpforms-lite/\";s:7:\"package\";s:62:\"http://downloads.wordpress.org/plugin/wpforms-lite.2.0.1.1.zip\";s:5:\"icons\";a:2:{s:2:\"1x\";s:57:\"https://ps.w.org/wpforms-lite/assets/icon.svg?rev=3254748\";s:3:\"svg\";s:57:\"https://ps.w.org/wpforms-lite/assets/icon.svg?rev=3254748\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:68:\"https://ps.w.org/wpforms-lite/assets/banner-1544x500.png?rev=3091364\";s:2:\"1x\";s:67:\"https://ps.w.org/wpforms-lite/assets/banner-772x250.png?rev=3091364\";}s:11:\"banners_rtl\";a:2:{s:2:\"2x\";s:72:\"https://ps.w.org/wpforms-lite/assets/banner-1544x500-rtl.png?rev=3254748\";s:2:\"1x\";s:71:\"https://ps.w.org/wpforms-lite/assets/banner-772x250-rtl.png?rev=3254748\";}s:8:\"requires\";s:3:\"5.5\";s:6:\"tested\";s:5:\"7.0.4\";s:12:\"requires_php\";s:3:\"7.2\";s:16:\"requires_plugins\";a:0:{}}s:29:\"wp-mail-smtp/wp_mail_smtp.php\";O:8:\"stdClass\":13:{s:2:\"id\";s:26:\"w.org/plugins/wp-mail-smtp\";s:4:\"slug\";s:12:\"wp-mail-smtp\";s:6:\"plugin\";s:29:\"wp-mail-smtp/wp_mail_smtp.php\";s:11:\"new_version\";s:5:\"4.9.0\";s:3:\"url\";s:43:\"https://wordpress.org/plugins/wp-mail-smtp/\";s:7:\"package\";s:60:\"http://downloads.wordpress.org/plugin/wp-mail-smtp.4.9.0.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:65:\"https://ps.w.org/wp-mail-smtp/assets/icon-256x256.png?rev=1755440\";s:2:\"1x\";s:65:\"https://ps.w.org/wp-mail-smtp/assets/icon-128x128.png?rev=1755440\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:68:\"https://ps.w.org/wp-mail-smtp/assets/banner-1544x500.png?rev=3206423\";s:2:\"1x\";s:67:\"https://ps.w.org/wp-mail-smtp/assets/banner-772x250.png?rev=3206423\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"5.5\";s:6:\"tested\";s:5:\"7.0.4\";s:12:\"requires_php\";s:3:\"7.4\";s:16:\"requires_plugins\";a:0:{}}}s:12:\"translations\";a:0:{}s:9:\"no_update\";a:1:{s:47:\"asset-lending-manager/asset-lending-manager.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:35:\"w.org/plugins/asset-lending-manager\";s:4:\"slug\";s:21:\"asset-lending-manager\";s:6:\"plugin\";s:47:\"asset-lending-manager/asset-lending-manager.php\";s:11:\"new_version\";s:5:\"0.3.2\";s:3:\"url\";s:52:\"https://wordpress.org/plugins/asset-lending-manager/\";s:7:\"package\";s:69:\"http://downloads.wordpress.org/plugin/asset-lending-manager.0.3.2.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:74:\"https://ps.w.org/asset-lending-manager/assets/icon-256x256.png?rev=3531387\";s:2:\"1x\";s:74:\"https://ps.w.org/asset-lending-manager/assets/icon-128x128.png?rev=3531387\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:77:\"https://ps.w.org/asset-lending-manager/assets/banner-1544x500.png?rev=3531387\";s:2:\"1x\";s:76:\"https://ps.w.org/asset-lending-manager/assets/banner-772x250.png?rev=3531387\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"6.2\";}}s:7:\"checked\";a:9:{s:30:\"advanced-custom-fields/acf.php\";s:5:\"6.8.4\";s:47:\"asset-lending-manager/asset-lending-manager.php\";s:5:\"0.4.0\";s:60:\"asset-lending-manager.stale-backup/asset-lending-manager.php\";s:5:\"0.2.4\";s:23:\"loco-translate/loco.php\";s:5:\"2.8.5\";s:19:\"members/members.php\";s:6:\"3.2.22\";s:23:\"plugin-check/plugin.php\";s:5:\"2.0.0\";s:40:\"wordpress-beta-tester/wp-beta-tester.php\";s:5:\"4.0.0\";s:24:\"wpforms-lite/wpforms.php\";s:6:\"1.10.2\";s:29:\"wp-mail-smtp/wp_mail_smtp.php\";s:5:\"4.8.0\";}}','off'),(5626,'_site_transient_timeout_wp_theme_files_patterns-75f9974894adb0be97561957ca4f759a','1788107856','off'),(5627,'_site_transient_wp_theme_files_patterns-75f9974894adb0be97561957ca4f759a','a:2:{s:7:\"version\";s:3:\"2.8\";s:8:\"patterns\";a:0:{}}','off'),(5629,'_site_transient_timeout_wp_theme_files_patterns-6a5780627f2681ffe2619c3f6260b529','1788108789','off'),(5630,'_site_transient_wp_theme_files_patterns-6a5780627f2681ffe2619c3f6260b529','a:2:{s:7:\"version\";s:3:\"2.8\";s:8:\"patterns\";a:0:{}}','off');
/*!40000 ALTER TABLE `wp_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_commentmeta`
--

DROP TABLE IF EXISTS `wp_pc_commentmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_commentmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_commentmeta`
--

LOCK TABLES `wp_pc_commentmeta` WRITE;
/*!40000 ALTER TABLE `wp_pc_commentmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_pc_commentmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_comments`
--

DROP TABLE IF EXISTS `wp_pc_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT 0,
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT 0,
  `comment_approved` varchar(20) NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) NOT NULL DEFAULT '',
  `comment_type` varchar(20) NOT NULL DEFAULT 'comment',
  `comment_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_comments`
--

LOCK TABLES `wp_pc_comments` WRITE;
/*!40000 ALTER TABLE `wp_pc_comments` DISABLE KEYS */;
INSERT INTO `wp_pc_comments` VALUES (1,1,'Un commentatore di WordPress','wapuu@wordpress.example','https://it.wordpress.org/','','2026-04-12 22:59:43','2026-04-12 20:59:43','Ciao, questo è un commento.\nPer iniziare a moderare, modificare ed eliminare commenti, vai alla schermata dei commenti nella bacheca.\nGli avatar di chi lascia un commento sono forniti da <a href=\"https://it.gravatar.com/\">Gravatar</a>.',0,'1','','comment',0,0);
/*!40000 ALTER TABLE `wp_pc_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_links`
--

DROP TABLE IF EXISTS `wp_pc_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_links` (
  `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `link_name` varchar(255) NOT NULL DEFAULT '',
  `link_image` varchar(255) NOT NULL DEFAULT '',
  `link_target` varchar(25) NOT NULL DEFAULT '',
  `link_description` varchar(255) NOT NULL DEFAULT '',
  `link_visible` varchar(20) NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) unsigned NOT NULL DEFAULT 1,
  `link_rating` int(11) NOT NULL DEFAULT 0,
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) NOT NULL DEFAULT '',
  `link_notes` mediumtext NOT NULL,
  `link_rss` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_links`
--

LOCK TABLES `wp_pc_links` WRITE;
/*!40000 ALTER TABLE `wp_pc_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_pc_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_options`
--

DROP TABLE IF EXISTS `wp_pc_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`),
  KEY `autoload` (`autoload`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_options`
--

LOCK TABLES `wp_pc_options` WRITE;
/*!40000 ALTER TABLE `wp_pc_options` DISABLE KEYS */;
INSERT INTO `wp_pc_options` VALUES (1,'cron','a:4:{i:1776031183;a:1:{s:16:\"wp_version_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1776032983;a:1:{s:17:\"wp_update_plugins\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1776034783;a:1:{s:16:\"wp_update_themes\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}s:7:\"version\";i:2;}','on'),(2,'permalink_structure','/%postname%/','auto'),(3,'siteurl','https://alm-e2e.local','on'),(4,'home','https://alm-e2e.local','on'),(5,'blogname','Plugin Check','on'),(6,'blogdescription','','on'),(7,'users_can_register','0','on'),(8,'admin_email','demo@plugincheck.test','on'),(9,'start_of_week','1','on'),(10,'use_balanceTags','0','on'),(11,'use_smilies','1','on'),(12,'require_name_email','1','on'),(13,'comments_notify','1','on'),(14,'posts_per_rss','10','on'),(15,'rss_use_excerpt','0','on'),(16,'mailserver_url','mail.example.com','on'),(17,'mailserver_login','login@example.com','on'),(18,'mailserver_pass','','on'),(19,'mailserver_port','110','on'),(20,'default_category','1','on'),(21,'default_comment_status','open','on'),(22,'default_ping_status','open','on'),(23,'default_pingback_flag','0','on'),(24,'posts_per_page','10','on'),(25,'date_format','j F Y','on'),(26,'time_format','G:i','on'),(27,'links_updated_date_format','j F Y G:i','on'),(28,'comment_moderation','0','on'),(29,'moderation_notify','1','on'),(30,'rewrite_rules','a:141:{s:11:\"^wp-json/?$\";s:22:\"index.php?rest_route=/\";s:14:\"^wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:21:\"^index.php/wp-json/?$\";s:22:\"index.php?rest_route=/\";s:24:\"^index.php/wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:17:\"^wp-sitemap\\.xml$\";s:23:\"index.php?sitemap=index\";s:17:\"^wp-sitemap\\.xsl$\";s:36:\"index.php?sitemap-stylesheet=sitemap\";s:23:\"^wp-sitemap-index\\.xsl$\";s:34:\"index.php?sitemap-stylesheet=index\";s:48:\"^wp-sitemap-([a-z]+?)-([a-z\\d_-]+?)-(\\d+?)\\.xml$\";s:75:\"index.php?sitemap=$matches[1]&sitemap-subtype=$matches[2]&paged=$matches[3]\";s:34:\"^wp-sitemap-([a-z]+?)-(\\d+?)\\.xml$\";s:47:\"index.php?sitemap=$matches[1]&paged=$matches[2]\";s:8:\"asset/?$\";s:31:\"index.php?post_type=almgr_asset\";s:38:\"asset/feed/(feed|rdf|rss|rss2|atom)/?$\";s:48:\"index.php?post_type=almgr_asset&feed=$matches[1]\";s:33:\"asset/(feed|rdf|rss|rss2|atom)/?$\";s:48:\"index.php?post_type=almgr_asset&feed=$matches[1]\";s:25:\"asset/page/([0-9]{1,})/?$\";s:49:\"index.php?post_type=almgr_asset&paged=$matches[1]\";s:28:\"^almgr/v1/assets/([0-9]+)/?$\";s:56:\"index.php?almgr_api_route=asset&almgr_api_id=$matches[1]\";s:19:\"^almgr/v1/assets/?$\";s:32:\"index.php?almgr_api_route=assets\";s:20:\"^almgr/v1/members/?$\";s:33:\"index.php?almgr_api_route=members\";s:36:\"^almgr/v1/members/([0-9]+)/assets/?$\";s:71:\"index.php?almgr_api_route=member_assets&almgr_api_member_id=$matches[1]\";s:47:\"category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:42:\"category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:23:\"category/(.+?)/embed/?$\";s:46:\"index.php?category_name=$matches[1]&embed=true\";s:35:\"category/(.+?)/page/?([0-9]{1,})/?$\";s:53:\"index.php?category_name=$matches[1]&paged=$matches[2]\";s:17:\"category/(.+?)/?$\";s:35:\"index.php?category_name=$matches[1]\";s:44:\"tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:39:\"tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:20:\"tag/([^/]+)/embed/?$\";s:36:\"index.php?tag=$matches[1]&embed=true\";s:32:\"tag/([^/]+)/page/?([0-9]{1,})/?$\";s:43:\"index.php?tag=$matches[1]&paged=$matches[2]\";s:14:\"tag/([^/]+)/?$\";s:25:\"index.php?tag=$matches[1]\";s:45:\"type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:40:\"type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:21:\"type/([^/]+)/embed/?$\";s:44:\"index.php?post_format=$matches[1]&embed=true\";s:33:\"type/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?post_format=$matches[1]&paged=$matches[2]\";s:15:\"type/([^/]+)/?$\";s:33:\"index.php?post_format=$matches[1]\";s:33:\"asset/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:43:\"asset/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:63:\"asset/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"asset/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"asset/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:39:\"asset/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:22:\"asset/([^/]+)/embed/?$\";s:44:\"index.php?almgr_asset=$matches[1]&embed=true\";s:26:\"asset/([^/]+)/trackback/?$\";s:38:\"index.php?almgr_asset=$matches[1]&tb=1\";s:46:\"asset/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_asset=$matches[1]&feed=$matches[2]\";s:41:\"asset/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_asset=$matches[1]&feed=$matches[2]\";s:34:\"asset/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?almgr_asset=$matches[1]&paged=$matches[2]\";s:41:\"asset/([^/]+)/comment-page-([0-9]{1,})/?$\";s:51:\"index.php?almgr_asset=$matches[1]&cpage=$matches[2]\";s:30:\"asset/([^/]+)(?:/([0-9]+))?/?$\";s:50:\"index.php?almgr_asset=$matches[1]&page=$matches[2]\";s:22:\"asset/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:32:\"asset/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:52:\"asset/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:47:\"asset/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:47:\"asset/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:28:\"asset/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:56:\"almgr_structure/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:54:\"index.php?almgr_structure=$matches[1]&feed=$matches[2]\";s:51:\"almgr_structure/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:54:\"index.php?almgr_structure=$matches[1]&feed=$matches[2]\";s:32:\"almgr_structure/([^/]+)/embed/?$\";s:48:\"index.php?almgr_structure=$matches[1]&embed=true\";s:44:\"almgr_structure/([^/]+)/page/?([0-9]{1,})/?$\";s:55:\"index.php?almgr_structure=$matches[1]&paged=$matches[2]\";s:26:\"almgr_structure/([^/]+)/?$\";s:37:\"index.php?almgr_structure=$matches[1]\";s:51:\"almgr_type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?almgr_type=$matches[1]&feed=$matches[2]\";s:46:\"almgr_type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?almgr_type=$matches[1]&feed=$matches[2]\";s:27:\"almgr_type/([^/]+)/embed/?$\";s:43:\"index.php?almgr_type=$matches[1]&embed=true\";s:39:\"almgr_type/([^/]+)/page/?([0-9]{1,})/?$\";s:50:\"index.php?almgr_type=$matches[1]&paged=$matches[2]\";s:21:\"almgr_type/([^/]+)/?$\";s:32:\"index.php?almgr_type=$matches[1]\";s:52:\"almgr_state/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_state=$matches[1]&feed=$matches[2]\";s:47:\"almgr_state/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_state=$matches[1]&feed=$matches[2]\";s:28:\"almgr_state/([^/]+)/embed/?$\";s:44:\"index.php?almgr_state=$matches[1]&embed=true\";s:40:\"almgr_state/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?almgr_state=$matches[1]&paged=$matches[2]\";s:22:\"almgr_state/([^/]+)/?$\";s:33:\"index.php?almgr_state=$matches[1]\";s:52:\"almgr_level/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_level=$matches[1]&feed=$matches[2]\";s:47:\"almgr_level/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?almgr_level=$matches[1]&feed=$matches[2]\";s:28:\"almgr_level/([^/]+)/embed/?$\";s:44:\"index.php?almgr_level=$matches[1]&embed=true\";s:40:\"almgr_level/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?almgr_level=$matches[1]&paged=$matches[2]\";s:22:\"almgr_level/([^/]+)/?$\";s:33:\"index.php?almgr_level=$matches[1]\";s:12:\"robots\\.txt$\";s:18:\"index.php?robots=1\";s:13:\"favicon\\.ico$\";s:19:\"index.php?favicon=1\";s:12:\"sitemap\\.xml\";s:23:\"index.php?sitemap=index\";s:48:\".*wp-(atom|rdf|rss|rss2|feed|commentsrss2)\\.php$\";s:18:\"index.php?feed=old\";s:20:\".*wp-app\\.php(/.*)?$\";s:19:\"index.php?error=403\";s:18:\".*wp-register.php$\";s:23:\"index.php?register=true\";s:32:\"feed/(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:27:\"(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:8:\"embed/?$\";s:21:\"index.php?&embed=true\";s:20:\"page/?([0-9]{1,})/?$\";s:28:\"index.php?&paged=$matches[1]\";s:41:\"comments/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:36:\"comments/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:17:\"comments/embed/?$\";s:21:\"index.php?&embed=true\";s:44:\"search/(.+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:39:\"search/(.+)/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:20:\"search/(.+)/embed/?$\";s:34:\"index.php?s=$matches[1]&embed=true\";s:32:\"search/(.+)/page/?([0-9]{1,})/?$\";s:41:\"index.php?s=$matches[1]&paged=$matches[2]\";s:14:\"search/(.+)/?$\";s:23:\"index.php?s=$matches[1]\";s:47:\"author/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:42:\"author/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:23:\"author/([^/]+)/embed/?$\";s:44:\"index.php?author_name=$matches[1]&embed=true\";s:35:\"author/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?author_name=$matches[1]&paged=$matches[2]\";s:17:\"author/([^/]+)/?$\";s:33:\"index.php?author_name=$matches[1]\";s:69:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:64:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:45:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/embed/?$\";s:74:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&embed=true\";s:57:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:81:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]\";s:39:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$\";s:63:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]\";s:56:\"([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:51:\"([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:32:\"([0-9]{4})/([0-9]{1,2})/embed/?$\";s:58:\"index.php?year=$matches[1]&monthnum=$matches[2]&embed=true\";s:44:\"([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:65:\"index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]\";s:26:\"([0-9]{4})/([0-9]{1,2})/?$\";s:47:\"index.php?year=$matches[1]&monthnum=$matches[2]\";s:43:\"([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:38:\"([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:19:\"([0-9]{4})/embed/?$\";s:37:\"index.php?year=$matches[1]&embed=true\";s:31:\"([0-9]{4})/page/?([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&paged=$matches[2]\";s:13:\"([0-9]{4})/?$\";s:26:\"index.php?year=$matches[1]\";s:27:\".?.+?/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\".?.+?/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\".?.+?/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\".?.+?/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"(.?.+?)/embed/?$\";s:41:\"index.php?pagename=$matches[1]&embed=true\";s:20:\"(.?.+?)/trackback/?$\";s:35:\"index.php?pagename=$matches[1]&tb=1\";s:40:\"(.?.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:35:\"(.?.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:28:\"(.?.+?)/page/?([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&paged=$matches[2]\";s:35:\"(.?.+?)/comment-page-([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&cpage=$matches[2]\";s:24:\"(.?.+?)(?:/([0-9]+))?/?$\";s:47:\"index.php?pagename=$matches[1]&page=$matches[2]\";s:27:\"[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\"[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\"[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\"[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"([^/]+)/embed/?$\";s:37:\"index.php?name=$matches[1]&embed=true\";s:20:\"([^/]+)/trackback/?$\";s:31:\"index.php?name=$matches[1]&tb=1\";s:40:\"([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:35:\"([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:28:\"([^/]+)/page/?([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&paged=$matches[2]\";s:35:\"([^/]+)/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&cpage=$matches[2]\";s:24:\"([^/]+)(?:/([0-9]+))?/?$\";s:43:\"index.php?name=$matches[1]&page=$matches[2]\";s:16:\"[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:26:\"[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:46:\"[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:22:\"[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";}','on'),(31,'hack_file','0','on'),(32,'blog_charset','UTF-8','on'),(33,'moderation_keys','','off'),(34,'active_plugins','a:7:{i:0;s:30:\"advanced-custom-fields/acf.php\";i:1;s:47:\"asset-lending-manager/asset-lending-manager.php\";i:2;s:23:\"loco-translate/loco.php\";i:3;s:19:\"members/members.php\";i:4;s:23:\"plugin-check/plugin.php\";i:5;s:29:\"wp-mail-smtp/wp_mail_smtp.php\";i:6;s:24:\"wpforms-lite/wpforms.php\";}','on'),(35,'category_base','','on'),(36,'ping_sites','https://rpc.pingomatic.com/','on'),(37,'comment_max_links','2','on'),(38,'gmt_offset','0','on'),(39,'default_email_category','1','on'),(40,'recently_edited','','off'),(41,'template','twentytwentyfive','on'),(42,'stylesheet','twentytwentyfive','on'),(43,'comment_registration','0','on'),(44,'html_type','text/html','on'),(45,'use_trackback','0','on'),(46,'default_role','subscriber','on'),(47,'db_version','60717','on'),(48,'uploads_use_yearmonth_folders','1','on'),(49,'upload_path','','on'),(50,'blog_public','0','on'),(51,'default_link_category','2','on'),(52,'show_on_front','posts','on'),(53,'tag_base','','on'),(54,'show_avatars','1','on'),(55,'avatar_rating','G','on'),(56,'upload_url_path','','on'),(57,'thumbnail_size_w','150','on'),(58,'thumbnail_size_h','150','on'),(59,'thumbnail_crop','1','on'),(60,'medium_size_w','300','on'),(61,'medium_size_h','300','on'),(62,'avatar_default','mystery','on'),(63,'large_size_w','1024','on'),(64,'large_size_h','1024','on'),(65,'image_default_link_type','none','on'),(66,'image_default_size','','on'),(67,'image_default_align','','on'),(68,'close_comments_for_old_posts','0','on'),(69,'close_comments_days_old','14','on'),(70,'thread_comments','1','on'),(71,'thread_comments_depth','5','on'),(72,'page_comments','0','on'),(73,'comments_per_page','50','on'),(74,'default_comments_page','newest','on'),(75,'comment_order','asc','on'),(76,'sticky_posts','a:0:{}','on'),(77,'widget_categories','a:0:{}','on'),(78,'widget_text','a:0:{}','on'),(79,'widget_rss','a:0:{}','on'),(80,'uninstall_plugins','a:0:{}','off'),(81,'timezone_string','Europe/Rome','on'),(82,'page_for_posts','0','on'),(83,'page_on_front','0','on'),(84,'default_post_format','0','on'),(85,'link_manager_enabled','0','on'),(86,'finished_splitting_shared_terms','1','on'),(87,'site_icon','0','on'),(88,'medium_large_size_w','768','on'),(89,'medium_large_size_h','0','on'),(90,'wp_page_for_privacy_policy','3','on'),(91,'show_comments_cookies_opt_in','1','on'),(92,'admin_email_lifespan','1791579583','on'),(93,'disallowed_keys','','off'),(94,'comment_previously_approved','1','on'),(95,'auto_plugin_theme_update_emails','a:0:{}','off'),(96,'auto_update_core_dev','enabled','on'),(97,'auto_update_core_minor','enabled','on'),(98,'auto_update_core_major','enabled','on'),(99,'wp_force_deactivated_plugins','a:0:{}','on'),(100,'wp_attachment_pages_enabled','0','on'),(101,'wp_notes_notify','1','on'),(102,'initial_db_version','60717','on'),(103,'wp_user_roles','a:10:{s:13:\"administrator\";a:2:{s:4:\"name\";s:13:\"Administrator\";s:12:\"capabilities\";a:104:{s:13:\"switch_themes\";b:1;s:11:\"edit_themes\";b:1;s:16:\"activate_plugins\";b:1;s:12:\"edit_plugins\";b:1;s:10:\"edit_users\";b:1;s:10:\"edit_files\";b:1;s:14:\"manage_options\";b:1;s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:6:\"import\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:8:\"level_10\";b:1;s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:12:\"delete_users\";b:1;s:12:\"create_users\";b:1;s:17:\"unfiltered_upload\";b:1;s:14:\"edit_dashboard\";b:1;s:14:\"update_plugins\";b:1;s:14:\"delete_plugins\";b:1;s:15:\"install_plugins\";b:1;s:13:\"update_themes\";b:1;s:14:\"install_themes\";b:1;s:11:\"update_core\";b:1;s:10:\"list_users\";b:1;s:12:\"remove_users\";b:1;s:13:\"promote_users\";b:1;s:18:\"edit_theme_options\";b:1;s:13:\"delete_themes\";b:1;s:6:\"export\";b:1;s:16:\"restrict_content\";b:1;s:10:\"list_roles\";b:1;s:12:\"create_roles\";b:1;s:12:\"delete_roles\";b:1;s:10:\"edit_roles\";b:1;s:10:\"loco_admin\";b:1;s:12:\"create_posts\";b:1;s:17:\"install_languages\";b:1;s:14:\"resume_plugins\";b:1;s:13:\"resume_themes\";b:1;s:23:\"view_site_health_checks\";b:1;s:14:\"read_alm_asset\";b:1;s:23:\"read_private_alm_assets\";b:1;s:14:\"edit_alm_asset\";b:1;s:15:\"edit_alm_assets\";b:1;s:22:\"edit_others_alm_assets\";b:1;s:25:\"edit_published_alm_assets\";b:1;s:23:\"edit_private_alm_assets\";b:1;s:16:\"delete_alm_asset\";b:1;s:17:\"delete_alm_assets\";b:1;s:24:\"delete_others_alm_assets\";b:1;s:27:\"delete_published_alm_assets\";b:1;s:25:\"delete_private_alm_assets\";b:1;s:18:\"publish_alm_assets\";b:1;s:15:\"alm_view_assets\";b:1;s:14:\"alm_view_asset\";b:1;s:14:\"alm_edit_asset\";b:1;s:17:\"almgr_view_assets\";b:1;s:16:\"almgr_view_asset\";b:1;s:16:\"almgr_edit_asset\";b:1;s:16:\"read_almgr_asset\";b:1;s:25:\"read_private_almgr_assets\";b:1;s:16:\"edit_almgr_asset\";b:1;s:17:\"edit_almgr_assets\";b:1;s:24:\"edit_others_almgr_assets\";b:1;s:27:\"edit_published_almgr_assets\";b:1;s:25:\"edit_private_almgr_assets\";b:1;s:18:\"delete_almgr_asset\";b:1;s:19:\"delete_almgr_assets\";b:1;s:26:\"delete_others_almgr_assets\";b:1;s:29:\"delete_published_almgr_assets\";b:1;s:27:\"delete_private_almgr_assets\";b:1;s:20:\"publish_almgr_assets\";b:1;}}s:6:\"editor\";a:2:{s:4:\"name\";s:6:\"Editor\";s:12:\"capabilities\";a:34:{s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;}}s:6:\"author\";a:2:{s:4:\"name\";s:6:\"Author\";s:12:\"capabilities\";a:10:{s:12:\"upload_files\";b:1;s:10:\"edit_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:22:\"delete_published_posts\";b:1;}}s:11:\"contributor\";a:2:{s:4:\"name\";s:11:\"Contributor\";s:12:\"capabilities\";a:5:{s:10:\"edit_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;}}s:10:\"subscriber\";a:2:{s:4:\"name\";s:10:\"Subscriber\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:7:\"level_0\";b:1;}}s:10:\"translator\";a:2:{s:4:\"name\";s:10:\"Translator\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:10:\"loco_admin\";b:1;}}s:10:\"alm_member\";a:2:{s:4:\"name\";s:5:\"Socio\";s:12:\"capabilities\";a:3:{s:4:\"read\";b:1;s:15:\"alm_view_assets\";b:1;s:14:\"alm_view_asset\";b:1;}}s:12:\"alm_operator\";a:2:{s:4:\"name\";s:9:\"Operatore\";s:12:\"capabilities\";a:17:{s:4:\"read\";b:1;s:14:\"read_alm_asset\";b:1;s:23:\"read_private_alm_assets\";b:1;s:14:\"edit_alm_asset\";b:1;s:15:\"edit_alm_assets\";b:1;s:22:\"edit_others_alm_assets\";b:1;s:25:\"edit_published_alm_assets\";b:1;s:23:\"edit_private_alm_assets\";b:1;s:16:\"delete_alm_asset\";b:1;s:17:\"delete_alm_assets\";b:1;s:24:\"delete_others_alm_assets\";b:1;s:27:\"delete_published_alm_assets\";b:1;s:25:\"delete_private_alm_assets\";b:1;s:18:\"publish_alm_assets\";b:1;s:15:\"alm_view_assets\";b:1;s:14:\"alm_view_asset\";b:1;s:14:\"alm_edit_asset\";b:1;}}s:12:\"almgr_member\";a:2:{s:4:\"name\";s:6:\"Member\";s:12:\"capabilities\";a:3:{s:4:\"read\";b:1;s:17:\"almgr_view_assets\";b:1;s:16:\"almgr_view_asset\";b:1;}}s:14:\"almgr_operator\";a:2:{s:4:\"name\";s:8:\"Operator\";s:12:\"capabilities\";a:30:{s:4:\"read\";b:1;s:14:\"read_alm_asset\";b:1;s:23:\"read_private_alm_assets\";b:1;s:14:\"edit_alm_asset\";b:1;s:15:\"edit_alm_assets\";b:1;s:22:\"edit_others_alm_assets\";b:1;s:25:\"edit_published_alm_assets\";b:1;s:23:\"edit_private_alm_assets\";b:1;s:16:\"delete_alm_asset\";b:1;s:17:\"delete_alm_assets\";b:1;s:24:\"delete_others_alm_assets\";b:1;s:27:\"delete_published_alm_assets\";b:1;s:25:\"delete_private_alm_assets\";b:1;s:18:\"publish_alm_assets\";b:1;s:17:\"almgr_view_assets\";b:1;s:16:\"almgr_view_asset\";b:1;s:16:\"almgr_edit_asset\";b:1;s:16:\"read_almgr_asset\";b:1;s:25:\"read_private_almgr_assets\";b:1;s:16:\"edit_almgr_asset\";b:1;s:17:\"edit_almgr_assets\";b:1;s:24:\"edit_others_almgr_assets\";b:1;s:27:\"edit_published_almgr_assets\";b:1;s:25:\"edit_private_almgr_assets\";b:1;s:18:\"delete_almgr_asset\";b:1;s:19:\"delete_almgr_assets\";b:1;s:26:\"delete_others_almgr_assets\";b:1;s:29:\"delete_published_almgr_assets\";b:1;s:27:\"delete_private_almgr_assets\";b:1;s:20:\"publish_almgr_assets\";b:1;}}}','on'),(104,'fresh_site','1','off'),(105,'user_count','1','off'),(106,'widget_block','a:6:{i:2;a:1:{s:7:\"content\";s:19:\"<!-- wp:search /-->\";}i:3;a:1:{s:7:\"content\";s:158:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Articoli recenti</h2><!-- /wp:heading --><!-- wp:latest-posts /--></div><!-- /wp:group -->\";}i:4;a:1:{s:7:\"content\";s:228:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Commenti recenti</h2><!-- /wp:heading --><!-- wp:latest-comments {\"displayAvatar\":false,\"displayDate\":false,\"displayExcerpt\":false} /--></div><!-- /wp:group -->\";}i:5;a:1:{s:7:\"content\";s:145:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Archivi</h2><!-- /wp:heading --><!-- wp:archives /--></div><!-- /wp:group -->\";}i:6;a:1:{s:7:\"content\";s:149:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Categorie</h2><!-- /wp:heading --><!-- wp:categories /--></div><!-- /wp:group -->\";}s:12:\"_multiwidget\";i:1;}','auto'),(107,'sidebars_widgets','a:4:{s:19:\"wp_inactive_widgets\";a:0:{}s:9:\"sidebar-1\";a:3:{i:0;s:7:\"block-2\";i:1;s:7:\"block-3\";i:2;s:7:\"block-4\";}s:9:\"sidebar-2\";a:2:{i:0;s:7:\"block-5\";i:1;s:7:\"block-6\";}s:13:\"array_version\";i:3;}','auto');
/*!40000 ALTER TABLE `wp_pc_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_postmeta`
--

DROP TABLE IF EXISTS `wp_pc_postmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_postmeta`
--

LOCK TABLES `wp_pc_postmeta` WRITE;
/*!40000 ALTER TABLE `wp_pc_postmeta` DISABLE KEYS */;
INSERT INTO `wp_pc_postmeta` VALUES (1,2,'_wp_page_template','default'),(2,3,'_wp_page_template','default');
/*!40000 ALTER TABLE `wp_pc_postmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_posts`
--

DROP TABLE IF EXISTS `wp_pc_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT 0,
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) NOT NULL DEFAULT 'open',
  `post_password` varchar(255) NOT NULL DEFAULT '',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `guid` varchar(255) NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT 0,
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`),
  KEY `type_status_author` (`post_type`,`post_status`,`post_author`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_posts`
--

LOCK TABLES `wp_pc_posts` WRITE;
/*!40000 ALTER TABLE `wp_pc_posts` DISABLE KEYS */;
INSERT INTO `wp_pc_posts` VALUES (1,1,'2026-04-12 22:59:43','2026-04-12 20:59:43','<!-- wp:paragraph -->\n<p>Ti diamo il benvenuto in WordPress. Questo è il tuo primo articolo. Modificalo o eliminalo e quindi inizia a scrivere!</p>\n<!-- /wp:paragraph -->','Ciao mondo!','','publish','open','open','','ciao-mondo','','','2026-04-12 22:59:43','2026-04-12 20:59:43','',0,'https://alm-site.local/?p=1',0,'post','',1),(2,1,'2026-04-12 22:59:43','2026-04-12 20:59:43','<!-- wp:paragraph -->\n<p>Questa è una pagina di esempio. Differisce da un articolo di un blog perché rimane sempre allo stesso posto e (in molti temi) appare nel menu di navigazione. Molte persone iniziano con una pagina di Informazioni che li presentano ai visitatori del sito. Potrebbe apparire una presentazione del tipo:</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\">\n<!-- wp:paragraph -->\n<p>Ciao! Sono un fattorino ciclista di giorno, aspirante attore di notte e questo è il mio sito web. Vivo a Los Angeles, ho un grande cane di nome Jack e mi piace la piña colada. (E prendere la pioggia)</p>\n<!-- /wp:paragraph -->\n</blockquote>\n<!-- /wp:quote -->\n\n<!-- wp:paragraph -->\n<p>...o qualcosa di simile:</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\">\n<!-- wp:paragraph -->\n<p>La Società XYZ Aggeggi è stata fondata nel 1971 e da allora fornisce al pubblico aggeggi di ottima qualità. Situata a Fantasilandia, XYZ impiega oltre 2.000 persone e realizza ogni sorta di aggeggio fantastico per la comunità di Fantasilandia.</p>\n<!-- /wp:paragraph -->\n</blockquote>\n<!-- /wp:quote -->\n\n<!-- wp:paragraph -->\n<p>Come nuovo utente di WordPress, dovresti andare nella tua <a href=\"https://alm-e2e.local/wp-admin/\">Bacheca</a> per eliminare questa pagina, e crearne delle nuove per i tuoi contenuti. Divertiti!</p>\n<!-- /wp:paragraph -->','Pagina di esempio','','publish','closed','open','','pagina-di-esempio','','','2026-04-12 22:59:43','2026-04-12 20:59:43','',0,'https://alm-site.local/?page_id=2',0,'page','',0),(3,1,'2026-04-12 22:59:43','2026-04-12 20:59:43','<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Chi siamo</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p><strong class=\"privacy-policy-tutorial\">Testo suggerito: </strong>L\'indirizzo del nostro sito web è: https://alm-e2e.local.</p>\n<!-- /wp:paragraph -->\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Commenti</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p><strong class=\"privacy-policy-tutorial\">Testo suggerito: </strong>Quando i visitatori lasciano commenti sul sito, raccogliamo i dati mostrati nel modulo dei commenti oltre all\'indirizzo IP del visitatore e la stringa dello user agent del browser per facilitare il rilevamento dello spam.</p>\n<!-- /wp:paragraph -->\n<!-- wp:paragraph -->\n<p>Una stringa anonimizzata creata a partire dal tuo indirizzo email (altrimenti detta hash) può essere fornita al servizio Gravatar per vedere se lo stai usando. La privacy policy del servizio Gravatar è disponibile qui: https://automattic.com/privacy/. Dopo l\'approvazione del tuo commento, la tua immagine del profilo è visibile al pubblico nel contesto del tuo commento.</p>\n<!-- /wp:paragraph -->\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Media</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p><strong class=\"privacy-policy-tutorial\">Testo suggerito: </strong>Se carichi immagini sul sito web, dovresti evitare di caricare immagini che includono i dati di posizione incorporati (EXIF GPS). I visitatori del sito web possono scaricare ed estrarre qualsiasi dato sulla posizione dalle immagini sul sito web.</p>\n<!-- /wp:paragraph -->\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Cookie</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p><strong class=\"privacy-policy-tutorial\">Testo suggerito: </strong>Se lasci un commento sul nostro sito, puoi scegliere di salvare il tuo nome, indirizzo email e sito web nei cookie. Sono usati per la tua comodità in modo che tu non debba inserire nuovamente i tuoi dati quando lasci un altro commento. Questi cookie dureranno per un anno.</p>\n<!-- /wp:paragraph -->\n<!-- wp:paragraph -->\n<p>Se visiti la pagina di login, verrà impostato un cookie temporaneo per determinare se il tuo browser accetta i cookie. Questo cookie non contiene dati personali e viene eliminato quando chiudi il browser.</p>\n<!-- /wp:paragraph -->\n<!-- wp:paragraph -->\n<p>Quando effettui l\'accesso, verranno impostati diversi cookie per salvare le tue informazioni di accesso e le tue opzioni di visualizzazione dello schermo. I cookie di accesso durano due giorni mentre i cookie per le opzioni dello schermo durano un anno. Se selezioni &quot;Ricordami&quot;, il tuo accesso persisterà per due settimane. Se esci dal tuo account, i cookie di accesso verranno rimossi.</p>\n<!-- /wp:paragraph -->\n<!-- wp:paragraph -->\n<p>Se modifichi o pubblichi un articolo, un cookie aggiuntivo verrà salvato nel tuo browser. Questo cookie non include dati personali, ma indica semplicemente l\'ID dell\'articolo appena modificato. Scade dopo 1 giorno.</p>\n<!-- /wp:paragraph -->\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Contenuto incorporato da altri siti web</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p><strong class=\"privacy-policy-tutorial\">Testo suggerito: </strong>Gli articoli su questo sito possono includere contenuti incorporati (ad esempio video, immagini, articoli, ecc.). I contenuti incorporati da altri siti web si comportano esattamente allo stesso modo come se il visitatore avesse visitato l\'altro sito web.</p>\n<!-- /wp:paragraph -->\n<!-- wp:paragraph -->\n<p>Questi siti web possono raccogliere dati su di te, usare cookie, integrare ulteriori tracciamenti di terze parti e monitorare l\'interazione con essi, incluso il tracciamento della tua interazione con il contenuto incorporato se hai un account e sei connesso a quei siti web.</p>\n<!-- /wp:paragraph -->\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Con chi condividiamo i tuoi dati</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p><strong class=\"privacy-policy-tutorial\">Testo suggerito: </strong>Se richiedi una reimpostazione della password, il tuo indirizzo IP verrà incluso nell\'email di reimpostazione.</p>\n<!-- /wp:paragraph -->\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Per quanto tempo conserviamo i tuoi dati</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p><strong class=\"privacy-policy-tutorial\">Testo suggerito: </strong>Se lasci un commento, il commento e i relativi metadati vengono conservati a tempo indeterminato. È così che possiamo riconoscere e approvare automaticamente eventuali commenti successivi invece di tenerli in una coda di moderazione.</p>\n<!-- /wp:paragraph -->\n<!-- wp:paragraph -->\n<p>Per gli utenti che si registrano sul nostro sito web (se presenti), memorizziamo anche le informazioni personali che forniscono nel loro profilo utente. Tutti gli utenti possono vedere, modificare o eliminare le loro informazioni personali in qualsiasi momento (eccetto il loro nome utente che non possono cambiare). Gli amministratori del sito web possono anche vedere e modificare queste informazioni.</p>\n<!-- /wp:paragraph -->\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Quali diritti hai sui tuoi dati</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p><strong class=\"privacy-policy-tutorial\">Testo suggerito: </strong>Se hai un account su questo sito, o hai lasciato commenti, puoi richiedere di ricevere un file esportato dal sito con i dati personali che abbiamo su di te, compresi i dati che ci hai fornito. Puoi anche richiedere che cancelliamo tutti i dati personali che ti riguardano. Questo non include i dati che siamo obbligati a conservare per scopi amministrativi, legali o di sicurezza.</p>\n<!-- /wp:paragraph -->\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Dove i tuoi dati sono inviati</h2>\n<!-- /wp:heading -->\n<!-- wp:paragraph -->\n<p><strong class=\"privacy-policy-tutorial\">Testo suggerito: </strong>I commenti dei visitatori possono essere controllati attraverso un servizio di rilevamento automatico dello spam.</p>\n<!-- /wp:paragraph -->\n','Privacy Policy','','draft','closed','open','','privacy-policy','','','2026-04-12 22:59:43','2026-04-12 20:59:43','',0,'https://alm-site.local/?page_id=3',0,'page','',0);
/*!40000 ALTER TABLE `wp_pc_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_term_relationships`
--

DROP TABLE IF EXISTS `wp_pc_term_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_term_relationships`
--

LOCK TABLES `wp_pc_term_relationships` WRITE;
/*!40000 ALTER TABLE `wp_pc_term_relationships` DISABLE KEYS */;
INSERT INTO `wp_pc_term_relationships` VALUES (1,1,0);
/*!40000 ALTER TABLE `wp_pc_term_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_term_taxonomy`
--

DROP TABLE IF EXISTS `wp_pc_term_taxonomy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_term_taxonomy`
--

LOCK TABLES `wp_pc_term_taxonomy` WRITE;
/*!40000 ALTER TABLE `wp_pc_term_taxonomy` DISABLE KEYS */;
INSERT INTO `wp_pc_term_taxonomy` VALUES (1,1,'category','',0,1);
/*!40000 ALTER TABLE `wp_pc_term_taxonomy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_termmeta`
--

DROP TABLE IF EXISTS `wp_pc_termmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_termmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_termmeta`
--

LOCK TABLES `wp_pc_termmeta` WRITE;
/*!40000 ALTER TABLE `wp_pc_termmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_pc_termmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_terms`
--

DROP TABLE IF EXISTS `wp_pc_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_terms`
--

LOCK TABLES `wp_pc_terms` WRITE;
/*!40000 ALTER TABLE `wp_pc_terms` DISABLE KEYS */;
INSERT INTO `wp_pc_terms` VALUES (1,'Senza categoria','senza-categoria',0);
/*!40000 ALTER TABLE `wp_pc_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_usermeta`
--

DROP TABLE IF EXISTS `wp_pc_usermeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_usermeta`
--

LOCK TABLES `wp_pc_usermeta` WRITE;
/*!40000 ALTER TABLE `wp_pc_usermeta` DISABLE KEYS */;
INSERT INTO `wp_pc_usermeta` VALUES (1,1,'nickname','plugincheck'),(2,1,'first_name',''),(3,1,'last_name',''),(4,1,'description',''),(5,1,'rich_editing','true'),(6,1,'syntax_highlighting','true'),(7,1,'comment_shortcuts','false'),(8,1,'admin_color','fresh'),(9,1,'use_ssl','0'),(10,1,'show_admin_bar_front','true'),(11,1,'locale',''),(12,1,'wp_pc_capabilities','a:1:{s:13:\"administrator\";b:1;}'),(13,1,'wp_pc_user_level','10'),(14,1,'dismissed_wp_pointers',''),(15,1,'default_password_nag','1'),(16,1,'show_welcome_panel','1');
/*!40000 ALTER TABLE `wp_pc_usermeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_pc_users`
--

DROP TABLE IF EXISTS `wp_pc_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_pc_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `user_nicename` varchar(50) NOT NULL DEFAULT '',
  `user_email` varchar(100) NOT NULL DEFAULT '',
  `user_url` varchar(100) NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT 0,
  `display_name` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_pc_users`
--

LOCK TABLES `wp_pc_users` WRITE;
/*!40000 ALTER TABLE `wp_pc_users` DISABLE KEYS */;
INSERT INTO `wp_pc_users` VALUES (1,'plugincheck','$wp$2y$12$LrVGb.xzUscITJ2JvfQKf.6DOFyKPrSI90TSE8iiE6ZdGyOgDXtlm','plugincheck','demo@plugincheck.test','https://alm-e2e.local','2026-04-12 20:59:43','',0,'plugincheck');
/*!40000 ALTER TABLE `wp_pc_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_postmeta`
--

DROP TABLE IF EXISTS `wp_postmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=1192 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_postmeta`
--

LOCK TABLES `wp_postmeta` WRITE;
/*!40000 ALTER TABLE `wp_postmeta` DISABLE KEYS */;
INSERT INTO `wp_postmeta` VALUES (1,2,'_wp_page_template','default'),(3,1,'_edit_last','1'),(5,1,'_edit_lock','1767262800:1'),(14,2,'_edit_lock','1771751070:1'),(15,2,'_edit_last','1'),(24,18,'_wp_attached_file','2026/01/Manuale-Utente.pdf'),(25,18,'_wp_attachment_metadata','a:1:{s:8:\"filesize\";i:22233;}'),(26,19,'_wp_attached_file','2026/01/Specifiche-Tecniche.pdf'),(27,19,'_wp_attachment_metadata','a:1:{s:8:\"filesize\";i:22233;}'),(28,20,'_wp_attached_file','2026/01/rifrattore40.jpg'),(29,20,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:450;s:6:\"height\";i:253;s:4:\"file\";s:24:\"2026/01/rifrattore40.jpg\";s:8:\"filesize\";i:25898;s:5:\"sizes\";a:2:{s:6:\"medium\";a:5:{s:4:\"file\";s:24:\"rifrattore40-300x169.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:169;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:6629;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:24:\"rifrattore40-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3378;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"1\";s:8:\"keywords\";a:0:{}}}'),(115,24,'_wp_attached_file','2026/01/Vixen-Oculare-SLV-6mm-1-25-.jpg'),(116,24,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:800;s:4:\"file\";s:39:\"2026/01/Vixen-Oculare-SLV-6mm-1-25-.jpg\";s:8:\"filesize\";i:58679;s:5:\"sizes\";a:3:{s:6:\"medium\";a:5:{s:4:\"file\";s:39:\"Vixen-Oculare-SLV-6mm-1-25--300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:9675;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:39:\"Vixen-Oculare-SLV-6mm-1-25--150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3676;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:39:\"Vixen-Oculare-SLV-6mm-1-25--768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:38457;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),(121,27,'_wp_attached_file','2026/01/ath1-annuncio_161372_359219_IMG_20221103_154121.jpg'),(122,27,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:405;s:6:\"height\";i:540;s:4:\"file\";s:59:\"2026/01/ath1-annuncio_161372_359219_IMG_20221103_154121.jpg\";s:8:\"filesize\";i:76951;s:5:\"sizes\";a:2:{s:6:\"medium\";a:5:{s:4:\"file\";s:59:\"ath1-annuncio_161372_359219_IMG_20221103_154121-225x300.jpg\";s:5:\"width\";i:225;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:16258;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:59:\"ath1-annuncio_161372_359219_IMG_20221103_154121-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:5030;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),(264,33,'_wp_attached_file','2026/01/Skywatcher-90-900-450x300-1.jpg'),(265,33,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:450;s:6:\"height\";i:300;s:4:\"file\";s:39:\"2026/01/Skywatcher-90-900-450x300-1.jpg\";s:8:\"filesize\";i:14561;s:5:\"sizes\";a:2:{s:6:\"medium\";a:5:{s:4:\"file\";s:39:\"Skywatcher-90-900-450x300-1-300x200.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:200;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7940;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:39:\"Skywatcher-90-900-450x300-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:5102;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),(267,34,'_wp_attached_file','2026/01/binocoloAAGG.jpg'),(268,34,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:325;s:6:\"height\";i:217;s:4:\"file\";s:24:\"2026/01/binocoloAAGG.jpg\";s:8:\"filesize\";i:14572;s:5:\"sizes\";a:2:{s:6:\"medium\";a:5:{s:4:\"file\";s:24:\"binocoloAAGG-300x200.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:200;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:12400;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:24:\"binocoloAAGG-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7053;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),(275,37,'_wp_attached_file','2026/01/s-l1600.webp'),(276,37,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:1196;s:6:\"height\";i:1600;s:4:\"file\";s:20:\"2026/01/s-l1600.webp\";s:8:\"filesize\";i:349612;s:5:\"sizes\";a:5:{s:6:\"medium\";a:5:{s:4:\"file\";s:20:\"s-l1600-224x300.webp\";s:5:\"width\";i:224;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/webp\";s:8:\"filesize\";i:11986;}s:5:\"large\";a:5:{s:4:\"file\";s:21:\"s-l1600-765x1024.webp\";s:5:\"width\";i:765;s:6:\"height\";i:1024;s:9:\"mime-type\";s:10:\"image/webp\";s:8:\"filesize\";i:135324;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:20:\"s-l1600-150x150.webp\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/webp\";s:8:\"filesize\";i:4252;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:21:\"s-l1600-768x1027.webp\";s:5:\"width\";i:768;s:6:\"height\";i:1027;s:9:\"mime-type\";s:10:\"image/webp\";s:8:\"filesize\";i:135582;}s:9:\"1536x1536\";a:5:{s:4:\"file\";s:22:\"s-l1600-1148x1536.webp\";s:5:\"width\";i:1148;s:6:\"height\";i:1536;s:9:\"mime-type\";s:10:\"image/webp\";s:8:\"filesize\";i:258656;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),(423,44,'_wp_attached_file','2026/01/newtoniano.jpg'),(424,44,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:448;s:6:\"height\";i:299;s:4:\"file\";s:22:\"2026/01/newtoniano.jpg\";s:8:\"filesize\";i:42921;s:5:\"sizes\";a:2:{s:6:\"medium\";a:5:{s:4:\"file\";s:22:\"newtoniano-300x200.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:200;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:19922;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:22:\"newtoniano-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:8741;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),(454,46,'_wp_attached_file','2026/01/dobson-1-450x300-1.jpg'),(455,46,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:450;s:6:\"height\";i:300;s:4:\"file\";s:30:\"2026/01/dobson-1-450x300-1.jpg\";s:8:\"filesize\";i:24664;s:5:\"sizes\";a:2:{s:6:\"medium\";a:5:{s:4:\"file\";s:30:\"dobson-1-450x300-1-300x200.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:200;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:12198;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:30:\"dobson-1-450x300-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:6204;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),(456,47,'_wp_attached_file','2026/01/Manuale-Utente-1.pdf'),(457,47,'_wp_attachment_metadata','a:1:{s:8:\"filesize\";i:22233;}'),(458,48,'_wp_attached_file','2026/01/Specifiche-Tecniche-1.pdf'),(459,48,'_wp_attachment_metadata','a:1:{s:8:\"filesize\";i:22233;}'),(517,51,'_wp_attached_file','2026/01/atlas_moon.jpg'),(518,51,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:194;s:6:\"height\";i:259;s:4:\"file\";s:22:\"2026/01/atlas_moon.jpg\";s:8:\"filesize\";i:11262;s:5:\"sizes\";a:1:{s:9:\"thumbnail\";a:5:{s:4:\"file\";s:22:\"atlas_moon-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:6985;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),(548,53,'_wp_attached_file','2026/01/13d0c316-0a7f-45e5-b204-ac296572ceeb_JWT2.avif'),(549,54,'_wp_attached_file','2026/01/13d0c316-0a7f-45e5-b204-ac296572ceeb_JWT2-1.avif'),(550,55,'_wp_attached_file','2026/01/JWST.png'),(551,55,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:543;s:4:\"file\";s:16:\"2026/01/JWST.png\";s:8:\"filesize\";i:546595;s:5:\"sizes\";a:3:{s:6:\"medium\";a:5:{s:4:\"file\";s:16:\"JWST-300x204.png\";s:5:\"width\";i:300;s:6:\"height\";i:204;s:9:\"mime-type\";s:9:\"image/png\";s:8:\"filesize\";i:95783;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:16:\"JWST-150x150.png\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:9:\"image/png\";s:8:\"filesize\";i:41577;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:16:\"JWST-768x521.png\";s:5:\"width\";i:768;s:6:\"height\";i:521;s:9:\"mime-type\";s:9:\"image/png\";s:8:\"filesize\";i:469625;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),(1118,99,'_edit_lock','1776013005:2'),(1173,55,'_wp_attachment_image_alt','dfsdf'),(1174,55,'_wp_old_slug','jwst'),(1175,24,'_wp_attachment_image_alt','vvvv'),(1176,118,'_edit_lock','1777735382:2'),(1177,118,'_edit_last','2'),(1178,120,'_edit_lock','1777736367:2'),(1179,120,'_edit_last','2'),(1184,125,'_edit_lock','1782216493:2'),(1185,125,'_edit_last','2'),(1189,133,'almgr_components','a:2:{i:0;s:3:\"131\";i:1;s:3:\"132\";}'),(1190,133,'_almgr_components','field_694a66df43fd1'),(1191,134,'_almgr_current_owner','19');
/*!40000 ALTER TABLE `wp_postmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_posts`
--

DROP TABLE IF EXISTS `wp_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT 0,
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) NOT NULL DEFAULT 'open',
  `post_password` varchar(255) NOT NULL DEFAULT '',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `guid` varchar(255) NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT 0,
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`),
  KEY `type_status_author` (`post_type`,`post_status`,`post_author`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_posts`
--

LOCK TABLES `wp_posts` WRITE;
/*!40000 ALTER TABLE `wp_posts` DISABLE KEYS */;
INSERT INTO `wp_posts` VALUES (1,1,'2025-12-15 15:49:18','2025-12-15 14:49:18','<!-- wp:paragraph -->\n<p>Ti diamo il benvenuto al sito demo del plugin: <a href=\"https://github.com/ilclaudio/asset-lending-manager\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager\" target=\"_blank\" rel=\"noreferrer noopener\">Assets Lending Manager</a>. Per visualizzare l\'elenco dei dispositivi clicca <a href=\"/device\">QUI</a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->','Ciao mondo','','publish','open','open','','ciao-mondo','','','2026-01-01 11:11:40','2026-01-01 10:11:40','',0,'https://alm-e2e.local/?p=1',0,'post','',1),(2,1,'2025-12-15 15:49:18','2025-12-15 14:49:18','<!-- wp:paragraph -->\n<p>Ti diamo il benvenuto nel sito demo del plugin: <a href=\"https://github.com/ilclaudio/asset-lending-manager\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager\" target=\"_blank\" rel=\"noreferrer noopener\">Assets Lending Manager</a>. </p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Per autenticarti clicca <a href=\"/wp-admin\">QUI</a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p><strong>Per visualizzare l\'elenco delle risorse clicca <a href=\"/asset\">QUI</a>.</strong></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Documentazione: </p>\n<!-- /wp:paragraph -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li><strong><a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/README.md\" target=\"_blank\" rel=\"noreferrer noopener\">descrizione del plugin</a></strong></li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li><strong><a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaAzioniUtente.md\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaAzioniUtente.md\" target=\"_blank\" rel=\"noreferrer noopener\">schema permessi per ruolo</a></strong></li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li> <a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaNotificheEmail.md\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaNotificheEmail.md\" target=\"_blank\" rel=\"noreferrer noopener\"><strong>schema notifiche email</strong></a></li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->','ALM DEMO','','publish','closed','open','','pagina-di-esempio','','','2026-02-22 09:54:19','2026-02-22 08:54:19','',0,'https://alm-e2e.local/?page_id=2',0,'page','',0),(4,0,'2025-12-15 15:49:19','2025-12-15 14:49:19','<!-- wp:page-list /-->','Navigazione','','publish','closed','closed','','navigation','','','2025-12-15 15:49:19','2025-12-15 14:49:19','',0,'https://alm-e2e.local/2025/12/15/navigation/',0,'wp_navigation','',0),(7,1,'2026-01-01 10:44:56','2026-01-01 09:44:56','{\"version\":3,\"isGlobalStylesUserThemeJSON\":true}','Custom Styles','','publish','closed','closed','','wp-global-styles-twentytwentyfive','','','2026-01-01 10:44:56','2026-01-01 09:44:56','',0,'https://alm-e2e.local/2026/01/01/wp-global-styles-twentytwentyfive/',0,'wp_global_styles','',0),(8,1,'2026-01-01 11:09:02','2026-01-01 10:09:02','<!-- wp:paragraph -->\n<p>Ti diamo il benvenuto in WordPress. Questo è il tuo primo articolo. Modificalo o eliminalo e quindi inizia a scrivere!</p>\n<!-- /wp:paragraph -->','Ciao mondo','','inherit','closed','closed','','1-revision-v1','','','2026-01-01 11:09:02','2026-01-01 10:09:02','',1,'https://alm-e2e.local/?p=8',0,'revision','',0),(10,1,'2026-01-01 11:11:09','2026-01-01 10:11:09','<!-- wp:paragraph -->\n<p>Ti diamo il benvenuto al sito demo del plugin: <a href=\"https://github.com/ilclaudio/asset-lending-manager\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager\" target=\"_blank\" rel=\"noreferrer noopener\">Assets Lending Manager</a>. Per visualizzare l\'elenco dei dispositivi clicca <a href=\"/device\">QUI</a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->','Ciao mondo','','inherit','closed','closed','','1-revision-v1','','','2026-01-01 11:11:09','2026-01-01 10:11:09','',1,'https://alm-e2e.local/?p=10',0,'revision','',0),(12,1,'2026-01-01 11:16:21','2026-01-01 10:16:21','<!-- wp:paragraph -->\n<p>Ti diamo il benvenuto al sito demo del plugin: <a href=\"https://github.com/ilclaudio/asset-lending-manager\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager\" target=\"_blank\" rel=\"noreferrer noopener\">Assets Lending Manager</a>. Per visualizzare l\'elenco dei dispositivi clicca <a href=\"/device\">QUI</a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->','ALM DEMO','','inherit','closed','closed','','2-revision-v1','','','2026-01-01 11:16:21','2026-01-01 10:16:21','',2,'https://alm-e2e.local/?p=12',0,'revision','',0),(15,1,'2026-01-01 11:21:40','2026-01-01 10:21:40','<!-- wp:paragraph -->\n<p>Ti diamo il benvenuto nel sito demo del plugin: <a href=\"https://github.com/ilclaudio/asset-lending-manager\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager\" target=\"_blank\" rel=\"noreferrer noopener\">Assets Lending Manager</a>. </p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Per visualizzare l\'elenco dei dispositivi clicca <a href=\"/device\">QUI</a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Per autenticarti clicca <a href=\"/wp-admin\">QUI</a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->','ALM DEMO','','inherit','closed','closed','','2-revision-v1','','','2026-01-01 11:21:40','2026-01-01 10:21:40','',2,'https://alm-e2e.local/?p=15',0,'revision','',0),(18,1,'2026-01-01 11:34:08','2026-01-01 10:34:08','','Manuale Utente','','inherit','','closed','','manuale-utente','','','2026-01-01 11:34:08','2026-01-01 10:34:08','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/Manuale-Utente.pdf',0,'attachment','application/pdf',0),(19,1,'2026-01-01 11:34:25','2026-01-01 10:34:25','','Specifiche Tecniche','','inherit','','closed','','specifiche-tecniche','','','2026-01-01 11:34:25','2026-01-01 10:34:25','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/Specifiche-Tecniche.pdf',0,'attachment','application/pdf',0),(20,1,'2026-01-01 11:34:41','2026-01-01 10:34:41','','rifrattore40','','inherit','','closed','','rifrattore40','','','2026-01-01 11:34:41','2026-01-01 10:34:41','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/rifrattore40.jpg',0,'attachment','image/jpeg',0),(24,1,'2026-01-01 11:42:02','2026-01-01 10:42:02','vvv','Vixen-Oculare-SLV-6mm-1-25-','vvvv','inherit','closed','closed','','vixen-oculare-slv-6mm-1-25','','','2026-04-25 18:29:07','2026-04-25 16:29:07','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/Vixen-Oculare-SLV-6mm-1-25-.jpg',0,'attachment','image/jpeg',0),(27,1,'2026-01-01 11:45:15','2026-01-01 10:45:15','','ath1-annuncio_161372_359219_IMG_20221103_154121','','inherit','','closed','','ath1-annuncio_161372_359219_img_20221103_154121','','','2026-01-01 11:45:15','2026-01-01 10:45:15','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/ath1-annuncio_161372_359219_IMG_20221103_154121.jpg',0,'attachment','image/jpeg',0),(33,1,'2026-01-01 11:59:03','2026-01-01 10:59:03','','Skywatcher-90-900-450x300','','inherit','','closed','','skywatcher-90-900-450x300','','','2026-01-01 11:59:03','2026-01-01 10:59:03','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/Skywatcher-90-900-450x300-1.jpg',0,'attachment','image/jpeg',0),(34,1,'2026-01-01 12:00:26','2026-01-01 11:00:26','','binocoloAAGG','','inherit','','closed','','binocoloaagg','','','2026-01-01 12:00:26','2026-01-01 11:00:26','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/binocoloAAGG.jpg',0,'attachment','image/jpeg',0),(37,1,'2026-01-01 12:08:31','2026-01-01 11:08:31','','s-l1600','','inherit','','closed','','s-l1600','','','2026-01-01 12:08:31','2026-01-01 11:08:31','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/s-l1600.webp',0,'attachment','image/webp',0),(44,1,'2026-01-01 12:32:22','2026-01-01 11:32:22','','newtoniano','','inherit','','closed','','newtoniano','','','2026-01-01 12:32:22','2026-01-01 11:32:22','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/newtoniano.jpg',0,'attachment','image/jpeg',0),(46,1,'2026-01-01 12:34:58','2026-01-01 11:34:58','','dobson-1-450x300','','inherit','','closed','','dobson-1-450x300','','','2026-01-01 12:34:58','2026-01-01 11:34:58','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/dobson-1-450x300-1.jpg',0,'attachment','image/jpeg',0),(47,1,'2026-01-01 12:36:46','2026-01-01 11:36:46','','Manuale Utente','','inherit','','closed','','manuale-utente-2','','','2026-01-01 12:36:46','2026-01-01 11:36:46','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/Manuale-Utente-1.pdf',0,'attachment','application/pdf',0),(48,1,'2026-01-01 12:36:58','2026-01-01 11:36:58','','Specifiche Tecniche','','inherit','','closed','','specifiche-tecniche-2','','','2026-01-01 12:36:58','2026-01-01 11:36:58','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/Specifiche-Tecniche-1.pdf',0,'attachment','application/pdf',0),(51,1,'2026-01-01 12:43:48','2026-01-01 11:43:48','','atlas_moon','','inherit','','closed','','atlas_moon','','','2026-01-01 12:43:48','2026-01-01 11:43:48','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/atlas_moon.jpg',0,'attachment','image/jpeg',0),(53,1,'2026-01-01 12:44:40','2026-01-01 11:44:40','','13d0c316-0a7f-45e5-b204-ac296572ceeb_JWT+2','','inherit','','closed','','13d0c316-0a7f-45e5-b204-ac296572ceeb_jwt2','','','2026-01-01 12:44:40','2026-01-01 11:44:40','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/13d0c316-0a7f-45e5-b204-ac296572ceeb_JWT2.avif',0,'attachment','image/avif',0),(54,1,'2026-01-01 12:45:14','2026-01-01 11:45:14','','13d0c316-0a7f-45e5-b204-ac296572ceeb_JWT+2','','inherit','','closed','','13d0c316-0a7f-45e5-b204-ac296572ceeb_jwt2-2','','','2026-01-01 12:45:14','2026-01-01 11:45:14','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/13d0c316-0a7f-45e5-b204-ac296572ceeb_JWT2-1.avif',0,'attachment','image/avif',0),(55,1,'2026-01-01 12:46:52','2026-01-01 11:46:52','','JWST','sdfdsfsdf','inherit','closed','closed','','jwst-2','','','2026-04-25 18:27:58','2026-04-25 16:27:58','',0,'https://alm-e2e.local/wp-content/uploads/2026/01/JWST.png',0,'attachment','image/png',0),(75,1,'2026-01-09 19:19:57','2026-01-09 18:19:57','<!-- wp:paragraph -->\n<p>Ti diamo il benvenuto nel sito demo del plugin: <a href=\"https://github.com/ilclaudio/asset-lending-manager\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager\" target=\"_blank\" rel=\"noreferrer noopener\">Assets Lending Manager</a>. </p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Per visualizzare l\'elenco delle risorse clicca <a href=\"/asset\">QUI</a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Per autenticarti clicca <a href=\"/wp-admin\">QUI</a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->','ALM DEMO','','inherit','closed','closed','','2-revision-v1','','','2026-01-09 19:19:57','2026-01-09 18:19:57','',2,'https://alm-e2e.local/?p=75',0,'revision','',0),(82,1,'2026-02-22 09:46:54','2026-02-22 08:46:54','<!-- wp:paragraph -->\n<p>Ti diamo il benvenuto nel sito demo del plugin: <a href=\"https://github.com/ilclaudio/asset-lending-manager\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager\" target=\"_blank\" rel=\"noreferrer noopener\">Assets Lending Manager</a>. </p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p><strong>Per visualizzare l\'elenco delle risorse clicca <a href=\"/asset\">QUI</a>.</strong></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Per autenticarti clicca <a href=\"/wp-admin\">QUI</a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Documentazione: <strong><a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/README.md\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/README.md\" target=\"_blank\" rel=\"noreferrer noopener\">descrizione del plugin</a></strong>, <strong><a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaAzioniUtente.md\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaAzioniUtente.md\" target=\"_blank\" rel=\"noreferrer noopener\">schema azioni per ruolo</a></strong> e <a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaNotificheEmail.md\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaNotificheEmail.md\" target=\"_blank\" rel=\"noreferrer noopener\"><strong>schema notifiche email</strong></a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->','ALM DEMO','','inherit','closed','closed','','2-revision-v1','','','2026-02-22 09:46:54','2026-02-22 08:46:54','',2,'https://alm-e2e.local/?p=82',0,'revision','',0),(84,1,'2026-02-22 09:50:17','2026-02-22 08:50:17','<!-- wp:paragraph -->\n<p>Ti diamo il benvenuto nel sito demo del plugin: <a href=\"https://github.com/ilclaudio/asset-lending-manager\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager\" target=\"_blank\" rel=\"noreferrer noopener\">Assets Lending Manager</a>. </p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p><strong>Per visualizzare l\'elenco delle risorse clicca <a href=\"/asset\">QUI</a>.</strong></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Per autenticarti clicca <a href=\"/wp-admin\">QUI</a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Documentazione: </p>\n<!-- /wp:paragraph -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li><strong><a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/README.md\" target=\"_blank\" rel=\"noreferrer noopener\">descrizione del plugin</a></strong></li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li><strong><a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaAzioniUtente.md\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaAzioniUtente.md\" target=\"_blank\" rel=\"noreferrer noopener\">schema permessi per ruolo</a></strong></li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li> <a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaNotificheEmail.md\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaNotificheEmail.md\" target=\"_blank\" rel=\"noreferrer noopener\"><strong>schema notifiche email</strong></a></li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->','ALM DEMO','','inherit','closed','closed','','2-revision-v1','','','2026-02-22 09:50:17','2026-02-22 08:50:17','',2,'https://alm-e2e.local/?p=84',0,'revision','',0),(86,1,'2026-02-22 09:54:15','2026-02-22 08:54:15','<!-- wp:paragraph -->\n<p>Ti diamo il benvenuto nel sito demo del plugin: <a href=\"https://github.com/ilclaudio/asset-lending-manager\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager\" target=\"_blank\" rel=\"noreferrer noopener\">Assets Lending Manager</a>. </p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Per autenticarti clicca <a href=\"/wp-admin\">QUI</a>.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p><strong>Per visualizzare l\'elenco delle risorse clicca <a href=\"/asset\">QUI</a>.</strong></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Documentazione: </p>\n<!-- /wp:paragraph -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li><strong><a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/README.md\" target=\"_blank\" rel=\"noreferrer noopener\">descrizione del plugin</a></strong></li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li><strong><a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaAzioniUtente.md\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaAzioniUtente.md\" target=\"_blank\" rel=\"noreferrer noopener\">schema permessi per ruolo</a></strong></li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li> <a href=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaNotificheEmail.md\" data-type=\"link\" data-id=\"https://github.com/ilclaudio/asset-lending-manager/blob/dev/DOC/SchemaNotificheEmail.md\" target=\"_blank\" rel=\"noreferrer noopener\"><strong>schema notifiche email</strong></a></li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->','ALM DEMO','','inherit','closed','closed','','2-revision-v1','','','2026-02-22 09:54:15','2026-02-22 08:54:15','',2,'https://alm-e2e.local/?p=86',0,'revision','',0),(99,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:11:{s:8:\"location\";a:1:{i:0;a:1:{i:0;a:3:{s:5:\"param\";s:9:\"post_type\";s:8:\"operator\";s:2:\"==\";s:5:\"value\";s:11:\"almgr_asset\";}}}s:8:\"position\";s:6:\"normal\";s:5:\"style\";s:7:\"default\";s:15:\"label_placement\";s:3:\"top\";s:21:\"instruction_placement\";s:5:\"label\";s:14:\"hide_on_screen\";s:0:\"\";s:11:\"description\";s:0:\"\";s:12:\"show_in_rest\";i:0;s:13:\"display_title\";s:0:\"\";s:15:\"allow_ai_access\";b:0;s:14:\"ai_description\";s:0:\"\";}','ALM Asset Fields','alm-asset-fields','publish','closed','closed','','group_694a655eddefc','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',0,'https://alm-e2e.local/?post_type=acf-field-group&#038;p=99',0,'acf-field-group','',0),(100,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:12:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:4:\"text\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:1;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"default_value\";s:0:\"\";s:9:\"maxlength\";s:0:\"\";s:17:\"allow_in_bindings\";i:0;s:11:\"placeholder\";s:0:\"\";s:7:\"prepend\";s:0:\"\";s:6:\"append\";s:0:\"\";}','Manufacturer','almgr_manufacturer','publish','closed','closed','','field_694a656243fca','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=100',0,'acf-field','',0),(101,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:12:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:4:\"text\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:1;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"default_value\";s:0:\"\";s:9:\"maxlength\";s:0:\"\";s:17:\"allow_in_bindings\";i:0;s:11:\"placeholder\";s:0:\"\";s:7:\"prepend\";s:0:\"\";s:6:\"append\";s:0:\"\";}','Model','almgr_model','publish','closed','closed','','field_694a65e643fcb','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=101',1,'acf-field','',0),(102,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:11:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:11:\"date_picker\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:0;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:14:\"display_format\";s:5:\"d/m/Y\";s:13:\"return_format\";s:5:\"d/m/Y\";s:9:\"first_day\";i:1;s:23:\"default_to_current_date\";i:0;s:17:\"allow_in_bindings\";i:0;}','Purchase date','almgr_data_acquisto','publish','closed','closed','','field_694a65fa43fcc','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=102',2,'acf-field','',0),(103,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:14:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:6:\"number\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:0;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"default_value\";s:0:\"\";s:3:\"min\";s:0:\"\";s:3:\"max\";s:0:\"\";s:17:\"allow_in_bindings\";i:0;s:11:\"placeholder\";s:0:\"\";s:4:\"step\";s:0:\"\";s:7:\"prepend\";s:0:\"\";s:6:\"append\";s:0:\"\";}','Cost','almgr_cost','publish','closed','closed','','field_694a665243fcd','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=103',3,'acf-field','',0),(104,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:12:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:4:\"text\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:0;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"default_value\";s:0:\"\";s:9:\"maxlength\";s:0:\"\";s:17:\"allow_in_bindings\";i:0;s:11:\"placeholder\";s:0:\"\";s:7:\"prepend\";s:0:\"\";s:6:\"append\";s:0:\"\";}','Dimensions','almgr_dimensions','publish','closed','closed','','field_694a668743fce','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=104',4,'acf-field','',0),(105,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:12:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:4:\"text\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:0;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"default_value\";s:0:\"\";s:9:\"maxlength\";s:0:\"\";s:17:\"allow_in_bindings\";i:0;s:11:\"placeholder\";s:0:\"\";s:7:\"prepend\";s:0:\"\";s:6:\"append\";s:0:\"\";}','Weight','almgr_weight','publish','closed','closed','','field_694a66a443fcf','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=105',5,'acf-field','',0),(106,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:12:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:4:\"text\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:0;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"default_value\";s:0:\"\";s:9:\"maxlength\";s:0:\"\";s:17:\"allow_in_bindings\";i:0;s:11:\"placeholder\";s:0:\"\";s:7:\"prepend\";s:0:\"\";s:6:\"append\";s:0:\"\";}','Location','almgr_location','publish','closed','closed','','field_694a66be43fd0','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=106',6,'acf-field','',0),(107,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:16:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:11:\"post_object\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:0;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:9:\"post_type\";a:1:{i:0;s:11:\"almgr_asset\";}s:11:\"post_status\";s:0:\"\";s:8:\"taxonomy\";s:0:\"\";s:13:\"return_format\";s:6:\"object\";s:8:\"multiple\";i:1;s:10:\"allow_null\";i:0;s:17:\"allow_in_bindings\";i:0;s:13:\"bidirectional\";i:0;s:2:\"ui\";i:1;s:20:\"bidirectional_target\";a:0:{}}','Components','almgr_components','publish','closed','closed','','field_694a66df43fd1','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=107',7,'acf-field','',0),(108,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:12:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:4:\"file\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:0;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"return_format\";s:5:\"array\";s:7:\"library\";s:3:\"all\";s:8:\"min_size\";s:0:\"\";s:8:\"max_size\";s:0:\"\";s:10:\"mime_types\";s:0:\"\";s:17:\"allow_in_bindings\";i:0;}','User manual','almgr_user_manual','publish','closed','closed','','field_694a672043fd2','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=108',8,'acf-field','',0),(109,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:12:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:4:\"file\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:0;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"return_format\";s:5:\"array\";s:7:\"library\";s:3:\"all\";s:8:\"min_size\";s:0:\"\";s:8:\"max_size\";s:0:\"\";s:10:\"mime_types\";s:0:\"\";s:17:\"allow_in_bindings\";i:0;}','Technical data sheet','almgr_technical_data_sheet','publish','closed','closed','','field_694a675043fd3','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=109',9,'acf-field','',0),(110,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:12:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:4:\"text\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:0;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"default_value\";s:0:\"\";s:9:\"maxlength\";s:0:\"\";s:17:\"allow_in_bindings\";i:0;s:11:\"placeholder\";s:0:\"\";s:7:\"prepend\";s:0:\"\";s:6:\"append\";s:0:\"\";}','Serial number','almgr_serial_number','publish','closed','closed','','field_694a677143fd4','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=110',10,'acf-field','',0),(111,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:12:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:4:\"text\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:0;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"default_value\";s:0:\"\";s:9:\"maxlength\";s:0:\"\";s:17:\"allow_in_bindings\";i:0;s:11:\"placeholder\";s:0:\"\";s:7:\"prepend\";s:0:\"\";s:6:\"append\";s:0:\"\";}','External code','almgr_external_code','publish','closed','closed','','field_694a677b43fd5','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=111',11,'acf-field','',0),(112,2,'2026-04-12 17:48:51','2026-04-12 15:48:51','a:12:{s:10:\"aria-label\";s:0:\"\";s:4:\"type\";s:8:\"textarea\";s:12:\"instructions\";s:0:\"\";s:8:\"required\";i:0;s:17:\"conditional_logic\";i:0;s:7:\"wrapper\";a:3:{s:5:\"width\";s:0:\"\";s:5:\"class\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"default_value\";s:0:\"\";s:9:\"maxlength\";s:0:\"\";s:17:\"allow_in_bindings\";i:0;s:4:\"rows\";s:0:\"\";s:11:\"placeholder\";s:0:\"\";s:9:\"new_lines\";s:0:\"\";}','Notes','almgr_notes','publish','closed','closed','','field_694a679543fd6','','','2026-04-12 17:48:51','2026-04-12 15:48:51','',99,'https://alm-e2e.local/?post_type=acf-field&p=112',12,'acf-field','',0),(118,2,'2026-05-02 17:21:53','2026-05-02 15:21:53','<!-- wp:html -->\n [almgr_asset_list]\n<!-- /wp:html -->\n\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->','Elenco Risorse','','publish','closed','closed','','elenco-risorse','','','2026-05-02 17:21:56','2026-05-02 15:21:56','',0,'https://alm-e2e.local/?page_id=118',0,'page','',0),(119,2,'2026-05-02 17:21:53','2026-05-02 15:21:53','<!-- wp:html -->\n [almgr_asset_list]\n<!-- /wp:html -->\n\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->','Elenco Risorse','','inherit','closed','closed','','118-revision-v1','','','2026-05-02 17:21:53','2026-05-02 15:21:53','',118,'https://alm-e2e.local/?p=119',0,'revision','',0),(120,2,'2026-05-02 17:22:59','2026-05-02 15:22:59','<!-- wp:html -->\n[almgr_asset_view]\n<!-- /wp:html -->','Dettaglio Risorsa','','publish','closed','closed','','dettaglio-risorsa','','','2026-05-02 17:23:02','2026-05-02 15:23:02','',0,'https://alm-e2e.local/?page_id=120',0,'page','',0),(121,2,'2026-05-02 17:22:59','2026-05-02 15:22:59','<!-- wp:html -->\n[almgr_asset_view]\n<!-- /wp:html -->','Dettaglio Risorsa','','inherit','closed','closed','','120-revision-v1','','','2026-05-02 17:22:59','2026-05-02 15:22:59','',120,'https://alm-e2e.local/?p=121',0,'revision','',0),(125,2,'2026-06-18 21:53:52','2026-06-18 19:53:52','<!-- wp:shortcode -->\n[almgr_asset_history]\n<!-- /wp:shortcode -->','Storia risorsa','','publish','closed','closed','','storia-risorsa','','','2026-06-23 14:07:19','2026-06-23 12:07:19','',0,'https://alm-e2e.local/?page_id=125',0,'page','',0),(126,2,'2026-06-18 21:53:09','2026-06-18 19:53:09','{\"version\":3,\"isGlobalStylesUserThemeJSON\":true}','Custom Styles','','publish','closed','closed','','wp-global-styles-twentytwentyone','','','2026-06-18 21:53:09','2026-06-18 19:53:09','',0,'https://alm-e2e.local/wp-global-styles-twentytwentyone/',0,'wp_global_styles','',0),(127,2,'2026-06-18 21:53:52','2026-06-18 19:53:52','<!-- wp:shortcode -->\n[almgr_asset_history]\n<!-- /wp:shortcode -->','Storia risorsa','','inherit','closed','closed','','125-revision-v1','','','2026-06-18 21:53:52','2026-06-18 19:53:52','',125,'https://alm-e2e.local/?p=127',0,'revision','',0),(128,2,'2026-06-18 23:51:32','2026-06-18 21:51:32','<!-- wp:paragraph -->\n<p>aDS</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[almgr_asset_history]\n<!-- /wp:shortcode -->\n\n<!-- wp:paragraph -->\n<p>dadasdas</p>\n<!-- /wp:paragraph -->','Storia risorsa','','inherit','closed','closed','','125-revision-v1','','','2026-06-18 23:51:32','2026-06-18 21:51:32','',125,'https://alm-e2e.local/?p=128',0,'revision','',0),(129,2,'2026-06-23 14:07:14','2026-06-23 12:07:14','<!-- wp:shortcode -->\n[almgr_asset_history]\n<!-- /wp:shortcode -->','Storia risorsa','','inherit','closed','closed','','125-revision-v1','','','2026-06-23 14:07:14','2026-06-23 12:07:14','',125,'https://alm-e2e.local/?p=129',0,'revision','',0),(130,0,'2026-08-30 18:24:39','2026-08-30 16:24:39','','E2E Available Component','','publish','closed','closed','','e2e-available-component','','','2026-08-30 18:24:39','2026-08-30 16:24:39','',0,'https://alm-e2e.local/asset/e2e-available-component/',0,'almgr_asset','',0),(131,0,'2026-08-30 18:24:39','2026-08-30 16:24:39','','E2E Kit Component 1','','publish','closed','closed','','e2e-kit-component-1','','','2026-08-30 18:24:39','2026-08-30 16:24:39','',0,'https://alm-e2e.local/asset/e2e-kit-component-1/',0,'almgr_asset','',0),(132,0,'2026-08-30 18:24:39','2026-08-30 16:24:39','','E2E Kit Component 2','','publish','closed','closed','','e2e-kit-component-2','','','2026-08-30 18:24:39','2026-08-30 16:24:39','',0,'https://alm-e2e.local/asset/e2e-kit-component-2/',0,'almgr_asset','',0),(133,0,'2026-08-30 18:24:39','2026-08-30 16:24:39','','E2E Kit','','publish','closed','closed','','e2e-kit','','','2026-08-30 18:24:39','2026-08-30 16:24:39','',0,'https://alm-e2e.local/asset/e2e-kit/',0,'almgr_asset','',0),(134,0,'2026-08-30 18:24:39','2026-08-30 16:24:39','','E2E On Loan Asset','','publish','closed','closed','','e2e-on-loan-asset','','','2026-08-30 18:24:39','2026-08-30 16:24:39','',0,'https://alm-e2e.local/asset/e2e-on-loan-asset/',0,'almgr_asset','',0);
/*!40000 ALTER TABLE `wp_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_relationships`
--

DROP TABLE IF EXISTS `wp_term_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_relationships`
--

LOCK TABLES `wp_term_relationships` WRITE;
/*!40000 ALTER TABLE `wp_term_relationships` DISABLE KEYS */;
INSERT INTO `wp_term_relationships` VALUES (1,1,0),(7,21,0),(126,24,0),(130,2,0),(130,4,0),(130,15,0),(130,18,0),(131,2,0),(131,4,0),(131,15,0),(131,18,0),(132,2,0),(132,5,0),(132,15,0),(132,18,0),(133,3,0),(133,15,0),(133,18,0),(133,23,0),(134,2,0),(134,4,0),(134,14,0),(134,18,0);
/*!40000 ALTER TABLE `wp_term_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_taxonomy`
--

DROP TABLE IF EXISTS `wp_term_taxonomy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_taxonomy`
--

LOCK TABLES `wp_term_taxonomy` WRITE;
/*!40000 ALTER TABLE `wp_term_taxonomy` DISABLE KEYS */;
INSERT INTO `wp_term_taxonomy` VALUES (1,1,'category','',0,1),(2,2,'almgr_structure','',0,4),(3,3,'almgr_structure','',0,1),(4,4,'almgr_type','',0,3),(5,5,'almgr_type','',0,1),(6,6,'almgr_type','',0,0),(7,7,'almgr_type','',0,0),(8,8,'almgr_type','',0,0),(9,9,'almgr_type','',0,0),(10,10,'almgr_type','',0,0),(11,11,'almgr_type','',0,0),(12,12,'almgr_type','',0,0),(13,13,'almgr_type','',0,0),(14,14,'almgr_state','',0,1),(15,15,'almgr_state','',0,4),(16,16,'almgr_state','',0,0),(17,17,'almgr_state','',0,0),(18,18,'almgr_level','',0,5),(19,19,'almgr_level','',0,0),(20,20,'almgr_level','',0,0),(21,21,'wp_theme','',0,1),(22,22,'almgr_type','',0,0),(23,23,'almgr_type','',0,1),(24,24,'wp_theme','',0,1);
/*!40000 ALTER TABLE `wp_term_taxonomy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_termmeta`
--

DROP TABLE IF EXISTS `wp_termmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_termmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_termmeta`
--

LOCK TABLES `wp_termmeta` WRITE;
/*!40000 ALTER TABLE `wp_termmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_termmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_terms`
--

DROP TABLE IF EXISTS `wp_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_terms`
--

LOCK TABLES `wp_terms` WRITE;
/*!40000 ALTER TABLE `wp_terms` DISABLE KEYS */;
INSERT INTO `wp_terms` VALUES (1,'Senza categoria','senza-categoria',0),(2,'Componente','component',0),(3,'Kit','kit',0),(4,'Telescopio','telescope',0),(5,'Oculare','ocular',0),(6,'Rifrattore','refractor',0),(7,'Tubo ottico','optical-tube',0),(8,'Binocolo','binoculars',0),(9,'Treppiede','tripod',0),(10,'Filtro','filter',0),(11,'Accessorio','accessory',0),(12,'Libro','book',0),(13,'Rivista','magazine',0),(14,'In prestito','on-loan',0),(15,'Disponibile','available',0),(16,'In manutenzione','maintenance',0),(17,'Ritirato','retired',0),(18,'Base','basic',0),(19,'Intermedio','intermediate',0),(20,'Avanzato','advanced',0),(21,'twentytwentyfive','twentytwentyfive',0),(22,'Mount','mount',0),(23,'Generic','generic',0),(24,'twentytwentyone','twentytwentyone',0);
/*!40000 ALTER TABLE `wp_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_usermeta`
--

DROP TABLE IF EXISTS `wp_usermeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=368 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_usermeta`
--

LOCK TABLES `wp_usermeta` WRITE;
/*!40000 ALTER TABLE `wp_usermeta` DISABLE KEYS */;
INSERT INTO `wp_usermeta` VALUES (1,1,'nickname','claudio'),(2,1,'first_name','Una'),(3,1,'last_name','Prova'),(4,1,'description',''),(5,1,'rich_editing','true'),(6,1,'syntax_highlighting','true'),(7,1,'comment_shortcuts','false'),(8,1,'admin_color','modern'),(9,1,'use_ssl','0'),(10,1,'show_admin_bar_front','true'),(11,1,'locale',''),(12,1,'wp_capabilities','a:2:{s:13:\"administrator\";b:1;s:12:\"almgr_member\";b:1;}'),(13,1,'wp_user_level','10'),(14,1,'dismissed_wp_pointers',''),(15,1,'show_welcome_panel','0'),(17,1,'wp_dashboard_quick_press_last_post_id','95'),(18,1,'community-events-location','a:1:{s:2:\"ip\";s:11:\"94.34.117.0\";}'),(19,1,'wp_persisted_preferences','a:5:{s:4:\"core\";a:2:{s:26:\"isComplementaryAreaVisible\";b:1;s:10:\"openPanels\";a:5:{i:0;s:11:\"post-status\";i:1;s:28:\"taxonomy-panel-alm_structure\";i:2;s:24:\"taxonomy-panel-alm_level\";i:3;s:23:\"taxonomy-panel-alm_type\";i:4;s:24:\"taxonomy-panel-alm_state\";}}s:9:\"_modified\";s:24:\"2026-02-22T08:51:48.289Z\";s:14:\"core/edit-post\";a:3:{s:12:\"welcomeGuide\";b:0;s:19:\"metaBoxesMainIsOpen\";b:1;s:23:\"metaBoxesMainOpenHeight\";i:216;}s:17:\"core/block-editor\";a:1:{s:25:\"linkControlSettingsDrawer\";b:1;}s:22:\"core/customize-widgets\";a:1:{s:12:\"welcomeGuide\";b:0;}}'),(20,2,'nickname','amministratore'),(21,2,'first_name','Amministra'),(22,2,'last_name','Tore'),(23,2,'description',''),(24,2,'rich_editing','true'),(25,2,'syntax_highlighting','true'),(26,2,'comment_shortcuts','false'),(27,2,'admin_color','modern'),(28,2,'use_ssl','0'),(29,2,'show_admin_bar_front','true'),(30,2,'locale','it_IT'),(31,2,'wp_capabilities','a:1:{s:13:\"administrator\";b:1;}'),(32,2,'wp_user_level','10'),(33,2,'dismissed_wp_pointers',''),(91,1,'wp_user-settings','libraryContent=browse'),(92,1,'wp_user-settings-time','1767263681'),(94,2,'wp_dashboard_quick_press_last_post_id','124'),(102,2,'closedpostboxes_dashboard','a:6:{i:0;s:32:\"wp_mail_smtp_reports_widget_lite\";i:1;s:21:\"dashboard_site_health\";i:2;s:19:\"dashboard_right_now\";i:3;s:18:\"dashboard_activity\";i:4;s:21:\"dashboard_quick_press\";i:5;s:17:\"dashboard_primary\";}'),(103,2,'metaboxhidden_dashboard','a:0:{}'),(114,1,'session_tokens','a:1:{s:64:\"6d5cbe487e78223e7a491ead57bca3eab00ea065f8b188d68c79f80a0e2888a1\";a:4:{s:10:\"expiration\";i:1775816423;s:2:\"ip\";s:9:\"127.0.0.1\";s:2:\"ua\";s:111:\"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36\";s:5:\"login\";i:1775643623;}}'),(135,2,'wp_persisted_preferences','a:4:{s:4:\"core\";a:2:{s:26:\"isComplementaryAreaVisible\";b:1;s:10:\"openPanels\";a:5:{i:0;s:11:\"post-status\";i:1;s:26:\"taxonomy-panel-almgr_level\";i:2;s:26:\"taxonomy-panel-almgr_state\";i:3;s:25:\"taxonomy-panel-almgr_type\";i:4;s:30:\"taxonomy-panel-almgr_structure\";}}s:14:\"core/edit-post\";a:3:{s:12:\"welcomeGuide\";b:0;s:19:\"metaBoxesMainIsOpen\";b:1;s:23:\"metaBoxesMainOpenHeight\";i:219;}s:9:\"_modified\";s:24:\"2026-05-02T15:40:05.010Z\";s:14:\"core/edit-site\";a:2:{s:12:\"welcomeGuide\";b:0;s:16:\"welcomeGuidePage\";b:0;}}'),(137,2,'wp_user-settings','plugin_check_category_preferences=general__plugin_repo__security__performance__accessibility'),(138,2,'wp_user-settings-time','1773610071'),(140,1,'_application_passwords','a:0:{}'),(249,2,'manageedit-acf-post-typecolumnshidden','a:1:{i:0;s:7:\"acf-key\";}'),(250,2,'acf_user_settings','a:1:{s:19:\"post-type-first-run\";b:1;}'),(321,2,'session_tokens','a:5:{s:64:\"43ed2456e197b8c28f9d2fe64c04861a1186beebaaa6ce189132017a6e99d866\";a:4:{s:10:\"expiration\";i:1782376817;s:2:\"ip\";s:9:\"127.0.0.1\";s:2:\"ua\";s:111:\"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36\";s:5:\"login\";i:1782204017;}s:64:\"76e3d4c9ca7237bfdd38d006499450f97492bbef753d4db4099fbb5dffdee25b\";a:4:{s:10:\"expiration\";i:1782388842;s:2:\"ip\";s:9:\"127.0.0.1\";s:2:\"ua\";s:111:\"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36\";s:5:\"login\";i:1782216042;}s:64:\"e660f0ac8f0321f01fe98a43c704b537f927f68b13939bc7dbeaf68ea3be730b\";a:4:{s:10:\"expiration\";i:1782399397;s:2:\"ip\";s:9:\"127.0.0.1\";s:2:\"ua\";s:111:\"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36\";s:5:\"login\";i:1782226597;}s:64:\"db47ce187b4af1c4f43a94dd3e210e434f010f7d698dd93d41f126ae7b138e30\";a:4:{s:10:\"expiration\";i:1782461739;s:2:\"ip\";s:9:\"127.0.0.1\";s:2:\"ua\";s:111:\"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36\";s:5:\"login\";i:1782288939;}s:64:\"124764c247120403cf2baa6ebea2386de1456729d4a6297274cf5f26c7a3af31\";a:4:{s:10:\"expiration\";i:1782496053;s:2:\"ip\";s:9:\"127.0.0.1\";s:2:\"ua\";s:111:\"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36\";s:5:\"login\";i:1782323253;}}'),(322,18,'nickname','e2e-operator'),(323,18,'first_name',''),(324,18,'last_name',''),(325,18,'description',''),(326,18,'rich_editing','true'),(327,18,'syntax_highlighting','true'),(328,18,'infinite_scrolling','true'),(329,18,'comment_shortcuts','false'),(330,18,'admin_color','modern'),(331,18,'use_ssl','0'),(332,18,'show_admin_bar_front','true'),(333,18,'locale',''),(334,18,'wp_capabilities','a:1:{s:14:\"almgr_operator\";b:1;}'),(335,18,'wp_user_level','0'),(336,18,'dismissed_wp_pointers',''),(337,19,'nickname','e2e-member-1'),(338,19,'first_name',''),(339,19,'last_name',''),(340,19,'description',''),(341,19,'rich_editing','true'),(342,19,'syntax_highlighting','true'),(343,19,'infinite_scrolling','true'),(344,19,'comment_shortcuts','false'),(345,19,'admin_color','modern'),(346,19,'use_ssl','0'),(347,19,'show_admin_bar_front','true'),(348,19,'locale',''),(349,19,'wp_capabilities','a:1:{s:12:\"almgr_member\";b:1;}'),(350,19,'wp_user_level','0'),(351,19,'dismissed_wp_pointers',''),(352,20,'nickname','e2e-member-2'),(353,20,'first_name',''),(354,20,'last_name',''),(355,20,'description',''),(356,20,'rich_editing','true'),(357,20,'syntax_highlighting','true'),(358,20,'infinite_scrolling','true'),(359,20,'comment_shortcuts','false'),(360,20,'admin_color','modern'),(361,20,'use_ssl','0'),(362,20,'show_admin_bar_front','true'),(363,20,'locale',''),(364,20,'wp_capabilities','a:1:{s:12:\"almgr_member\";b:1;}'),(365,20,'wp_user_level','0'),(366,20,'dismissed_wp_pointers',''),(367,1,'infinite_scrolling','true');
/*!40000 ALTER TABLE `wp_usermeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_users`
--

DROP TABLE IF EXISTS `wp_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `user_nicename` varchar(50) NOT NULL DEFAULT '',
  `user_email` varchar(100) NOT NULL DEFAULT '',
  `user_url` varchar(100) NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT 0,
  `display_name` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_users`
--

LOCK TABLES `wp_users` WRITE;
/*!40000 ALTER TABLE `wp_users` DISABLE KEYS */;
INSERT INTO `wp_users` VALUES (1,'claudio','$wp$2y$12$JpPI84OlnUcZQ9wuYWo4X.s80e8Xd0tM0id/LP7UpVOA4hHtUQAPe','claudio','claudio@example.test','https://alm-e2e.local','2025-12-15 14:49:18','',0,'Una Prova'),(2,'amministratore','$wp$2y$12$7cl79O3bTRdoOIUdNkqIB.yX2uNzQralJpi9rHq7IDkqST7UB7/ye','amministratore','aaa@kk.it','','2026-01-01 10:03:18','',0,'Amministra Tore'),(18,'e2e-operator','$wp$2y$10$bhYDKLgoZUOeN69o4HO2gOZemv.IPpkE65vUYc1buiIkCPJbxueUS','e2e-operator','e2e-operator@example.test','','2026-08-30 16:24:38','',0,'e2e-operator'),(19,'e2e-member-1','$wp$2y$10$WNsrL9HfIyvNIBMrrGtI2O1/2c2Pio7Q9lh5..Go9.XK9ghdiuwPm','e2e-member-1','e2e-member-1@example.test','','2026-08-30 16:24:38','',0,'e2e-member-1'),(20,'e2e-member-2','$wp$2y$10$LyrIdp6SbMDMYg/geYLxGe4u/2crR/7xHgIHnfTAHsjtBEGVv4Ab.','e2e-member-2','e2e-member-2@example.test','','2026-08-30 16:24:38','',0,'e2e-member-2');
/*!40000 ALTER TABLE `wp_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wpforms_logs`
--

DROP TABLE IF EXISTS `wp_wpforms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_wpforms_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `types` varchar(255) NOT NULL,
  `create_at` datetime NOT NULL,
  `form_id` bigint(20) DEFAULT NULL,
  `entry_id` bigint(20) DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wpforms_logs`
--

LOCK TABLES `wp_wpforms_logs` WRITE;
/*!40000 ALTER TABLE `wp_wpforms_logs` DISABLE KEYS */;
INSERT INTO `wp_wpforms_logs` VALUES (1,'Migration','Migration of WPForms to 1.9.9.3 is fully completed.','log','2026-02-24 21:16:36',0,0,2),(2,'Migration','Migration of WPForms to 1.9.9.4 is fully completed.','log','2026-03-04 22:08:43',0,0,2),(3,'Migration','Migration of WPForms to 1.10.0.1 is fully completed.','log','2026-03-21 15:42:43',0,0,0),(4,'Migration','Migration of WPForms to 1.10.0.2 is fully completed.','log','2026-03-31 14:00:14',0,0,0),(5,'Migration','Migration of WPForms to 1.10.0.3 is fully completed.','log','2026-04-08 20:50:31',0,0,0),(6,'Migration','Migration of WPForms to 1.10.0.4 is fully completed.','log','2026-04-11 16:07:50',0,0,0),(7,'Migration','Migration of WPForms to 1.10.0.5 is fully completed.','log','2026-05-20 15:44:28',0,0,2),(8,'Migration','Migration of WPForms to 1.10.1 is fully completed.','log','2026-06-01 13:22:05',0,0,2),(9,'Migration','Migration of WPForms to 1.10.1.1 is fully completed.','log','2026-06-18 17:43:44',0,0,0),(10,'Migration','Migration of WPForms to 1.10.2 is fully completed.','log','2026-06-23 08:41:12',0,0,0);
/*!40000 ALTER TABLE `wp_wpforms_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wpforms_payment_meta`
--

DROP TABLE IF EXISTS `wp_wpforms_payment_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_wpforms_payment_meta` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) NOT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_id` (`payment_id`),
  KEY `meta_key` (`meta_key`(191)),
  KEY `meta_value` (`meta_value`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wpforms_payment_meta`
--

LOCK TABLES `wp_wpforms_payment_meta` WRITE;
/*!40000 ALTER TABLE `wp_wpforms_payment_meta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wpforms_payment_meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wpforms_payments`
--

DROP TABLE IF EXISTS `wp_wpforms_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_wpforms_payments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT '',
  `subtotal_amount` decimal(26,8) NOT NULL DEFAULT 0.00000000,
  `discount_amount` decimal(26,8) NOT NULL DEFAULT 0.00000000,
  `total_amount` decimal(26,8) NOT NULL DEFAULT 0.00000000,
  `currency` varchar(3) NOT NULL DEFAULT '',
  `entry_id` bigint(20) NOT NULL DEFAULT 0,
  `gateway` varchar(20) NOT NULL DEFAULT '',
  `type` varchar(12) NOT NULL DEFAULT '',
  `mode` varchar(4) NOT NULL DEFAULT '',
  `transaction_id` varchar(40) NOT NULL DEFAULT '',
  `customer_id` varchar(40) NOT NULL DEFAULT '',
  `subscription_id` varchar(40) NOT NULL DEFAULT '',
  `subscription_status` varchar(10) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `date_created_gmt` datetime NOT NULL,
  `date_updated_gmt` datetime NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  KEY `status` (`status`(8)),
  KEY `total_amount` (`total_amount`),
  KEY `type` (`type`(8)),
  KEY `transaction_id` (`transaction_id`(32)),
  KEY `customer_id` (`customer_id`(32)),
  KEY `subscription_id` (`subscription_id`(32)),
  KEY `subscription_status` (`subscription_status`(8)),
  KEY `title` (`title`(64))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wpforms_payments`
--

LOCK TABLES `wp_wpforms_payments` WRITE;
/*!40000 ALTER TABLE `wp_wpforms_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wpforms_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wpforms_tasks_meta`
--

DROP TABLE IF EXISTS `wp_wpforms_tasks_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_wpforms_tasks_meta` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `action` varchar(255) NOT NULL,
  `data` longtext NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wpforms_tasks_meta`
--

LOCK TABLES `wp_wpforms_tasks_meta` WRITE;
/*!40000 ALTER TABLE `wp_wpforms_tasks_meta` DISABLE KEYS */;
INSERT INTO `wp_wpforms_tasks_meta` VALUES (1,'wpforms_process_forms_locator_scan','W10=','2026-02-19 22:24:04'),(2,'wpforms_process_purge_spam','W10=','2026-02-19 22:24:04');
/*!40000 ALTER TABLE `wp_wpforms_tasks_meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wpmailsmtp_debug_events`
--

DROP TABLE IF EXISTS `wp_wpmailsmtp_debug_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_wpmailsmtp_debug_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content` text DEFAULT NULL,
  `initiator` text DEFAULT NULL,
  `event_type` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wpmailsmtp_debug_events`
--

LOCK TABLES `wp_wpmailsmtp_debug_events` WRITE;
/*!40000 ALTER TABLE `wp_wpmailsmtp_debug_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wpmailsmtp_debug_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wpmailsmtp_tasks_meta`
--

DROP TABLE IF EXISTS `wp_wpmailsmtp_tasks_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_wpmailsmtp_tasks_meta` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `action` varchar(255) NOT NULL,
  `data` longtext NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wpmailsmtp_tasks_meta`
--

LOCK TABLES `wp_wpmailsmtp_tasks_meta` WRITE;
/*!40000 ALTER TABLE `wp_wpmailsmtp_tasks_meta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wpmailsmtp_tasks_meta` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-30 18:32:33
