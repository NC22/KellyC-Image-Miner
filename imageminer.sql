-- phpMyAdmin SQL Dump
-- version 3.3.7deb7
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Дек 18 2016 г., 14:54
-- Версия сервера: 5.1.73
-- Версия PHP: 5.3.3-7+squeeze19

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `joydump`
--

-- --------------------------------------------------------

--
-- Структура таблицы `controll_users`
--

CREATE TABLE IF NOT EXISTS `controll_users` (
  `user_id` int(10) NOT NULL AUTO_INCREMENT,
  `user_login` char(64) NOT NULL,
  `user_password` char(64) NOT NULL,
  `user_role` char(12) NOT NULL DEFAULT 'admin',
  `user_cookie` char(32) NOT NULL DEFAULT '',
  `user_permissions` int(10) NOT NULL DEFAULT '0',
  `user_ip` char(16) NOT NULL,
  `user_try` int(11) NOT NULL DEFAULT '0',
  `user_create` datetime NOT NULL,
  `user_last_auth` datetime NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Структура таблицы `job_sessions`
--

CREATE TABLE IF NOT EXISTS `job_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `session_active` tinyint(1) NOT NULL DEFAULT '0',
  `session_user` char(32) NOT NULL,
  `session_password` char(32) NOT NULL,
  `session_cookies` char(255) NOT NULL,
  `session_last_update` datetime NOT NULL,
  `session_last_active` datetime NOT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_id` (`session_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Структура таблицы `parsed_authors`
--

CREATE TABLE IF NOT EXISTS `parsed_authors` (
  `author_id` int(10) NOT NULL AUTO_INCREMENT,
  `author_loaded` tinyint(1) NOT NULL DEFAULT '0',
  `author_name` char(32) NOT NULL,
  `author_raiting` int(10) NOT NULL,
  `author_last_update` datetime NOT NULL,
  PRIMARY KEY (`author_id`),
  UNIQUE KEY `author_id` (`author_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=45529 ;

-- --------------------------------------------------------

--
-- Структура таблицы `parsed_images`
--

CREATE TABLE IF NOT EXISTS `parsed_images` (
  `image_id` int(10) NOT NULL AUTO_INCREMENT,
  `image_dhash` bigint(20) unsigned NOT NULL,
  `image_loaded` tinyint(1) NOT NULL DEFAULT '0',
  `image_material_id` int(10) NOT NULL,
  `image_link` varchar(1024) NOT NULL,
  `image_load_fail` tinyint(1) NOT NULL DEFAULT '0',
  `image_preview` char(32) NOT NULL DEFAULT '',
  `image_palete` char(42) NOT NULL DEFAULT '',
  `image_color` char(6) NOT NULL DEFAULT '',
  `image_ban_search` tinyint(1) NOT NULL DEFAULT '0',
  `image_w` smallint(6) NOT NULL DEFAULT '0',
  `image_h` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`image_id`),
  KEY `HASH` (`image_dhash`),
  KEY `IMAGE_SIZE` (`image_w`,`image_h`),
  KEY `image_material` (`image_material_id`),
  FULLTEXT KEY `image_paletesearch` (`image_palete`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2078620 ;

-- --------------------------------------------------------

--
-- Структура таблицы `parsed_locations`
--

CREATE TABLE IF NOT EXISTS `parsed_locations` (
  `location_id` int(11) NOT NULL AUTO_INCREMENT,
  `location_name` varchar(16) NOT NULL,
  `location_pages` int(11) NOT NULL DEFAULT '0',
  `location_value` varchar(255) NOT NULL DEFAULT '',
  `location_last_update` datetime NOT NULL,
  PRIMARY KEY (`location_id`),
  UNIQUE KEY `location` (`location_name`,`location_value`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Структура таблицы `parsed_materials`
--

CREATE TABLE IF NOT EXISTS `parsed_materials` (
  `material_id` int(10) NOT NULL,
  `material_images_count` smallint(5) NOT NULL,
  `material_images_loaded` tinyint(4) NOT NULL DEFAULT '0',
  `material_author_id` int(10) NOT NULL,
  `material_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `material_censored` tinyint(1) NOT NULL DEFAULT '0',
  `material_auth_required` tinyint(1) NOT NULL DEFAULT '0',
  `material_title` char(64) NOT NULL,
  `material_rating` float NOT NULL,
  `material_tags` char(255) NOT NULL,
  `material_gifs` char(255) NOT NULL,
  `material_loaded_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`material_id`),
  KEY `material_priority` (`material_rating`),
  KEY `material_images_num` (`material_images_count`),
  FULLTEXT KEY `material_search` (`material_tags`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `parsed_pages`
--

CREATE TABLE IF NOT EXISTS `parsed_pages` (
  `page_id` int(10) NOT NULL AUTO_INCREMENT,
  `page_result` int(10) NOT NULL,
  `page_site_id` int(10) NOT NULL,
  `page_number` int(10) NOT NULL,
  `page_loaded` tinyint(1) NOT NULL DEFAULT '0',
  `page_route` char(32) NOT NULL DEFAULT '',
  UNIQUE KEY `page_id` (`page_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=221047 ;

-- --------------------------------------------------------

--
-- Структура таблицы `search_cache`
--

CREATE TABLE IF NOT EXISTS `search_cache` (
  `search_id` int(11) NOT NULL AUTO_INCREMENT,
  `search_dhash` bigint(20) unsigned NOT NULL DEFAULT '0',
  `search_result` varchar(255) NOT NULL DEFAULT '',
  `search_rating` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`search_id`),
  UNIQUE KEY `search_dhash` (`search_dhash`),
  KEY `search_rating` (`search_rating`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=73 ;

-- --------------------------------------------------------

--
-- Структура таблицы `search_sessions`
--

CREATE TABLE IF NOT EXISTS `search_sessions` (
  `ssession_id` int(10) NOT NULL AUTO_INCREMENT,
  `ssession_ip` varchar(16) NOT NULL DEFAULT '',
  `ssession_last_active` datetime NOT NULL,
  `ssession_actions` int(8) NOT NULL DEFAULT '0',
  `ssession_action` varchar(6) NOT NULL,
  PRIMARY KEY (`ssession_id`),
  UNIQUE KEY `ssession_ip` (`ssession_ip`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=187 ;

-- --------------------------------------------------------

--
-- Структура таблицы `site_actions`
--

CREATE TABLE IF NOT EXISTS `site_actions` (
  `action_id` int(11) NOT NULL AUTO_INCREMENT,
  `action_type` char(32) NOT NULL,
  `action_value` char(255) NOT NULL,
  PRIMARY KEY (`action_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2782 ;

-- --------------------------------------------------------

--
-- Структура таблицы `site_visitors`
--

CREATE TABLE IF NOT EXISTS `site_visitors` (
  `visitor_id` int(10) NOT NULL AUTO_INCREMENT,
  `visitor_ip` varchar(16) NOT NULL,
  `visitor_ref` varchar(120) NOT NULL DEFAULT '',
  `visitor_visits` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`visitor_id`),
  UNIQUE KEY `visitor_ip` (`visitor_ip`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=116 ;
