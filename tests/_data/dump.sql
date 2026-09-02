# noinspection SqlNoDataSourceInspectionForFile
-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 06, 2016 at 11:28 AM
-- Server version: 5.6.32
-- PHP Version: 7.0.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `teste`
--

-- --------------------------------------------------------

--
-- Table structure for table `interadmin_teste_records`
--

CREATE TABLE `interadmin_teste_records` (
  `id` mediumint(8) UNSIGNED NOT NULL,
  `id_string` varchar(255) NOT NULL,
  `id_slug` varchar(255) NOT NULL,
  `type_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `parent_id` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `parent_type_id` smallint(5) UNSIGNED NOT NULL,
  `date_key` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_1` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_2` datetime NOT NULL,
  `date_3` datetime NOT NULL,
  `date_4` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_5` datetime NOT NULL,
  `date_6` datetime NOT NULL,
  `date_insert` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modify` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_publish` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_expire` datetime NOT NULL,
  `date_hit` datetime NOT NULL,
  `varchar_key` varchar(255) NOT NULL DEFAULT '',
  `varchar_1` varchar(255) NOT NULL DEFAULT '',
  `varchar_2` varchar(255) NOT NULL DEFAULT '',
  `varchar_3` varchar(255) NOT NULL DEFAULT '',
  `varchar_4` varchar(255) NOT NULL DEFAULT '',
  `varchar_5` varchar(255) NOT NULL DEFAULT '',
  `varchar_6` varchar(255) NOT NULL DEFAULT '',
  `varchar_7` varchar(255) NOT NULL,
  `varchar_8` varchar(255) NOT NULL,
  `varchar_9` varchar(255) NOT NULL,
  `varchar_10` varchar(255) NOT NULL,
  `varchar_11` varchar(255) NOT NULL,
  `varchar_12` varchar(64) NOT NULL,
  `varchar_13` varchar(64) NOT NULL,
  `varchar_14` varchar(64) NOT NULL,
  `varchar_15` varchar(64) NOT NULL,
  `varchar_16` varchar(64) NOT NULL,
  `varchar_17` varchar(64) NOT NULL,
  `varchar_18` varchar(64) NOT NULL,
  `varchar_19` varchar(64) NOT NULL,
  `varchar_20` varchar(64) NOT NULL,
  `password_key` varchar(50) NOT NULL DEFAULT '',
  `text_1` text NOT NULL,
  `text_2` text NOT NULL,
  `text_3` text NOT NULL,
  `text_4` text NOT NULL,
  `file_1` varchar(255) NOT NULL DEFAULT '',
  `file_1_text` varchar(255) NOT NULL DEFAULT '',
  `file_2` varchar(255) NOT NULL DEFAULT '',
  `file_2_text` varchar(255) NOT NULL DEFAULT '',
  `file_3` varchar(255) NOT NULL DEFAULT '',
  `file_3_text` varchar(255) NOT NULL DEFAULT '',
  `file_4` varchar(255) NOT NULL DEFAULT '',
  `file_4_text` varchar(255) NOT NULL DEFAULT '',
  `file_5` varchar(255) NOT NULL DEFAULT '',
  `file_5_text` varchar(255) NOT NULL DEFAULT '',
  `bool_key` tinyint(1) NOT NULL DEFAULT 0,
  `bool_1` tinyint(1) NOT NULL DEFAULT 0,
  `bool_2` tinyint(1) NOT NULL DEFAULT 0,
  `bool_3` tinyint(1) NOT NULL DEFAULT 0,
  `bool_4` tinyint(1) NOT NULL DEFAULT 0,
  `bool_5` tinyint(1) NOT NULL DEFAULT 0,
  `bool_6` tinyint(1) NOT NULL DEFAULT 0,
  `select_key` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_1` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_2` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_3` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_4` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_5` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_6` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_multi_1` text NOT NULL,
  `select_multi_2` text NOT NULL,
  `special_1` text NOT NULL,
  `int_key` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `hits` mediumint(9) UNSIGNED NOT NULL,
  `tags` varchar(255) NOT NULL,
  `int_1` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `int_2` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `float_1` float NOT NULL DEFAULT '0',
  `log` text NOT NULL,
  `log_user` varchar(50) NOT NULL DEFAULT '',
  `publish` char(1) NOT NULL DEFAULT '',
  `deleted` char(1) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `interadmin_teste_en_records`
--

CREATE TABLE `interadmin_teste_en_records` (
  `id` mediumint(8) UNSIGNED NOT NULL,
  `id_string` varchar(255) NOT NULL,
  `id_slug` varchar(255) NOT NULL,
  `type_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `parent_id` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `parent_type_id` smallint(5) UNSIGNED NOT NULL,
  `date_key` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_1` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_2` datetime NOT NULL,
  `date_3` datetime NOT NULL,
  `date_4` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_5` datetime NOT NULL,
  `date_6` datetime NOT NULL,
  `date_insert` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modify` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_publish` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_expire` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_hit` datetime NOT NULL,
  `varchar_key` varchar(255) NOT NULL DEFAULT '',
  `varchar_1` varchar(255) NOT NULL DEFAULT '',
  `varchar_2` varchar(255) NOT NULL DEFAULT '',
  `varchar_3` varchar(255) NOT NULL DEFAULT '',
  `varchar_4` varchar(255) NOT NULL DEFAULT '',
  `varchar_5` varchar(255) NOT NULL DEFAULT '',
  `varchar_6` varchar(255) NOT NULL DEFAULT '',
  `varchar_7` varchar(255) NOT NULL,
  `varchar_8` varchar(255) NOT NULL,
  `varchar_9` varchar(255) NOT NULL,
  `varchar_10` varchar(255) NOT NULL,
  `varchar_11` varchar(255) NOT NULL,
  `varchar_12` varchar(64) NOT NULL,
  `varchar_13` varchar(64) NOT NULL,
  `varchar_14` varchar(64) NOT NULL,
  `varchar_15` varchar(64) NOT NULL,
  `varchar_16` varchar(64) NOT NULL,
  `varchar_17` varchar(64) NOT NULL,
  `varchar_18` varchar(64) NOT NULL,
  `varchar_19` varchar(64) NOT NULL,
  `varchar_20` varchar(64) NOT NULL,
  `password_key` varchar(50) NOT NULL DEFAULT '',
  `text_1` text NOT NULL,
  `text_2` text NOT NULL,
  `text_3` text NOT NULL,
  `text_4` text NOT NULL,
  `file_1` varchar(255) NOT NULL DEFAULT '',
  `file_1_text` varchar(255) NOT NULL DEFAULT '',
  `file_2` varchar(255) NOT NULL DEFAULT '',
  `file_2_text` varchar(255) NOT NULL DEFAULT '',
  `file_3` varchar(255) NOT NULL DEFAULT '',
  `file_3_text` varchar(255) NOT NULL DEFAULT '',
  `file_4` varchar(255) NOT NULL DEFAULT '',
  `file_4_text` varchar(255) NOT NULL DEFAULT '',
  `file_5` varchar(255) NOT NULL DEFAULT '',
  `file_5_text` varchar(255) NOT NULL DEFAULT '',
  `bool_key` tinyint(1) NOT NULL DEFAULT 0,
  `bool_1` tinyint(1) NOT NULL DEFAULT 0,
  `bool_2` tinyint(1) NOT NULL DEFAULT 0,
  `bool_3` tinyint(1) NOT NULL DEFAULT 0,
  `bool_4` tinyint(1) NOT NULL DEFAULT 0,
  `bool_5` tinyint(1) NOT NULL DEFAULT 0,
  `bool_6` tinyint(1) NOT NULL DEFAULT 0,
  `select_key` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_1` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_2` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_3` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_4` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_5` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_6` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `select_multi_1` text NOT NULL,
  `select_multi_2` text NOT NULL,
  `special_1` text NOT NULL,
  `int_key` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `hits` mediumint(9) UNSIGNED NOT NULL,
  `int_1` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `int_2` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `float_1` float NOT NULL DEFAULT '0',
  `log` text NOT NULL,
  `log_user` varchar(50) NOT NULL DEFAULT '',
  `publish` char(1) NOT NULL DEFAULT '',
  `deleted` char(1) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `interadmin_teste_tags`
--

CREATE TABLE `interadmin_teste_tags` (
  `id_tag` mediumint(8) UNSIGNED NOT NULL,
  `parent_id` mediumint(8) UNSIGNED NOT NULL,
  `type_id` smallint(5) UNSIGNED NOT NULL,
  `id` mediumint(8) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `interadmin_teste_files`
--

CREATE TABLE `interadmin_teste_files` (
  `file_id` int(10) UNSIGNED NOT NULL,
  `type_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `part` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `url_thumb` varchar(255) NOT NULL DEFAULT '',
  `url_zoom` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `caption` varchar(255) NOT NULL DEFAULT '',
  `credits` varchar(255) NOT NULL DEFAULT '',
  `link` varchar(255) NOT NULL DEFAULT '',
  `link_blank` char(1) NOT NULL DEFAULT '',
  `visible` char(1) NOT NULL DEFAULT '',
  `featured` char(1) NOT NULL DEFAULT '',
  `position` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `deleted` char(1) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `interadmin_teste_types`
--

CREATE TABLE `interadmin_teste_types` (
  `type_id` smallint(5) UNSIGNED NOT NULL,
  `type_id_string` varchar(255) NOT NULL,
  `id_slug` varchar(255) NOT NULL,
  `date_modify` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `model_type_id` varchar(100) NOT NULL DEFAULT '0',
  `parent_type_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `redirect_type_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `name_en` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `class` varchar(255) NOT NULL DEFAULT '',
  `class_type` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(255) NOT NULL,
  `template` varchar(255) NOT NULL DEFAULT '',
  `editpage` varchar(255) NOT NULL DEFAULT '',
  `template_insert` varchar(255) NOT NULL DEFAULT '',
  `table_name` varchar(255) NOT NULL DEFAULT '',
  `trigger_function` varchar(255) NOT NULL DEFAULT '',
  `fields` text NOT NULL,
  `files_1` varchar(50) NOT NULL DEFAULT '',
  `files_1_help` varchar(255) NOT NULL DEFAULT '',
  `files_2` varchar(50) NOT NULL DEFAULT '',
  `files_2_help` varchar(255) NOT NULL DEFAULT '',
  `files_3` varchar(50) NOT NULL DEFAULT '',
  `files_3_help` varchar(255) NOT NULL DEFAULT '',
  `links` varchar(50) NOT NULL DEFAULT '',
  `links_help` varchar(255) NOT NULL DEFAULT '',
  `children` text NOT NULL,
  `visible` char(1) NOT NULL DEFAULT '',
  `language` char(1) NOT NULL DEFAULT '',
  `menu` char(1) NOT NULL DEFAULT '',
  `search` char(1) NOT NULL DEFAULT '',
  `restricted` char(1) NOT NULL DEFAULT '',
  `admin` char(1) NOT NULL DEFAULT '',
  `edit` char(1) NOT NULL DEFAULT '',
  `single` char(1) NOT NULL DEFAULT '',
  `versions` char(1) NOT NULL DEFAULT '',
  `hits` char(1) NOT NULL,
  `tags` char(1) NOT NULL,
  `tags_list` char(1) NOT NULL,
  `tags_type` char(1) NOT NULL,
  `tags_records` char(1) NOT NULL,
  `publish_type` char(1) NOT NULL DEFAULT '',
  `template_view` char(1) NOT NULL DEFAULT '',
  `layout` tinyint(1) UNSIGNED NOT NULL,
  `layout_records` tinyint(1) UNSIGNED NOT NULL,
  `position` tinyint(4) NOT NULL DEFAULT '0',
  `log` text NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `inherited` varchar(255) NOT NULL,
  `xtra_disabledfields` text NOT NULL,
  `xtra_disabledchildren` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `interadmin_teste`
--
ALTER TABLE `interadmin_teste_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `select_key` (`select_key`);
ALTER TABLE `interadmin_teste_records` ADD FULLTEXT KEY `interadmin_search` (`varchar_key`,`varchar_1`,`varchar_2`,`varchar_3`,`text_1`,`text_2`,`text_3`);

--
-- Indexes for table `interadmin_teste_en_records`
--
ALTER TABLE `interadmin_teste_en_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `select_key` (`select_key`);

--
-- Indexes for table `interadmin_teste_tags`
--
ALTER TABLE `interadmin_teste_tags`
  ADD PRIMARY KEY (`id_tag`);

--
-- Indexes for table `interadmin_teste_files`
--
ALTER TABLE `interadmin_teste_files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `interadmin_teste_types`
--
ALTER TABLE `interadmin_teste_types`
  ADD PRIMARY KEY (`type_id`);
ALTER TABLE `interadmin_teste_types` ADD FULLTEXT KEY `interadmin_search` (`name`,`name_en`,`description`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `interadmin_teste_records`
--
ALTER TABLE `interadmin_teste_records`
  MODIFY `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134911;
--
-- AUTO_INCREMENT for table `interadmin_teste_en_records`
--
ALTER TABLE `interadmin_teste_en_records`
  MODIFY `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5983;
--
-- AUTO_INCREMENT for table `interadmin_teste_tags`
--
ALTER TABLE `interadmin_teste_tags`
  MODIFY `id_tag` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `interadmin_teste_files`
--
ALTER TABLE `interadmin_teste_files`
  MODIFY `file_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `interadmin_teste_types`
--
ALTER TABLE `interadmin_teste_types`
  MODIFY `type_id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=528;
