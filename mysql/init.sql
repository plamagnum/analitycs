-- Security Analytics Center Database Schema
-- Created for cybersecurity analytics platform

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS analitycs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE analitycs;

-- Table: hosts (sites/hosts from scans)
DROP TABLE IF EXISTS `hosts`;
CREATE TABLE `hosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `os` varchar(255) DEFAULT NULL,
  `status` enum('up','down','unknown') DEFAULT 'unknown',
  `first_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip` (`ip_address`),
  KEY `idx_status` (`status`),
  KEY `idx_hostname` (`hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ports (ports and services)
DROP TABLE IF EXISTS `ports`;
CREATE TABLE `ports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host_id` int(11) NOT NULL,
  `port_number` int(11) NOT NULL,
  `protocol` enum('tcp','udp') DEFAULT 'tcp',
  `service_name` varchar(100) DEFAULT NULL,
  `service_version` varchar(255) DEFAULT NULL,
  `state` enum('open','closed','filtered') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_ports_host` (`host_id`),
  KEY `idx_port_number` (`port_number`),
  KEY `idx_state` (`state`),
  CONSTRAINT `fk_ports_host` FOREIGN KEY (`host_id`) REFERENCES `hosts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: vulnerabilities
DROP TABLE IF EXISTS `vulnerabilities`;
CREATE TABLE `vulnerabilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `severity` enum('critical','high','medium','low','info') NOT NULL,
  `status` enum('new','in_progress','fixed','false_positive') DEFAULT 'new',
  `cve_id` varchar(50) DEFAULT NULL,
  `cvss_score` decimal(3,1) DEFAULT NULL,
  `solution` text,
  `references` text,
  `discovered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_vuln_host` (`host_id`),
  KEY `idx_severity` (`severity`),
  KEY `idx_status` (`status`),
  KEY `idx_cve` (`cve_id`),
  CONSTRAINT `fk_vuln_host` FOREIGN KEY (`host_id`) REFERENCES `hosts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: nmap_scans
DROP TABLE IF EXISTS `nmap_scans`;
CREATE TABLE `nmap_scans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `scan_type` varchar(100) DEFAULT NULL,
  `hosts_discovered` int(11) DEFAULT 0,
  `ports_discovered` int(11) DEFAULT 0,
  `scan_command` text,
  `scan_start` varchar(50) DEFAULT NULL,
  `scan_end` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uploaded` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: nuclei_scans
DROP TABLE IF EXISTS `nuclei_scans`;
CREATE TABLE `nuclei_scans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `vulnerabilities_found` int(11) DEFAULT 0,
  `scan_target` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uploaded` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ctf_competitions
DROP TABLE IF EXISTS `ctf_competitions`;
CREATE TABLE `ctf_competitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `team_name` varchar(100) DEFAULT NULL,
  `final_rank` int(11) DEFAULT NULL,
  `total_points` int(11) DEFAULT 0,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ctf_challenges
DROP TABLE IF EXISTS `ctf_challenges`;
CREATE TABLE `ctf_challenges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `competition_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('web','pwn','crypto','forensics','misc','reverse','osint') NOT NULL,
  `points` int(11) DEFAULT 0,
  `status` enum('solved','unsolved','in_progress') DEFAULT 'unsolved',
  `description` text,
  `flag` varchar(255) DEFAULT NULL,
  `writeup` text,
  `solution_files` text,
  `solved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_challenge_competition` (`competition_id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_challenge_competition` FOREIGN KEY (`competition_id`) REFERENCES `ctf_competitions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notes
DROP TABLE IF EXISTS `notes`;
CREATE TABLE `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text,
  `category` varchar(100) DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `related_type` enum('vulnerability','ctf','host','general') DEFAULT 'general',
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_related` (`related_type`, `related_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
