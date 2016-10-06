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
-- Table structure for table `interadmin_teste_registros`
--

CREATE TABLE `interadmin_teste_registros` (
  `id` mediumint(8) UNSIGNED NOT NULL,
  `id_string` varchar(255) NOT NULL,
  `id_slug` varchar(255) NOT NULL,
  `id_tipo` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `parent_id` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `parent_id_tipo` smallint(5) UNSIGNED NOT NULL,
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
  `char_key` char(1) NOT NULL DEFAULT '',
  `char_1` char(1) NOT NULL DEFAULT '',
  `char_2` char(1) NOT NULL DEFAULT '',
  `char_3` char(1) NOT NULL DEFAULT '',
  `char_4` char(1) NOT NULL DEFAULT '',
  `char_5` char(1) NOT NULL DEFAULT '',
  `char_6` char(1) NOT NULL,
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
-- Table structure for table `interadmin_teste_en_registros`
--

CREATE TABLE `interadmin_teste_en_registros` (
  `id` mediumint(8) UNSIGNED NOT NULL,
  `id_string` varchar(255) NOT NULL,
  `id_slug` varchar(255) NOT NULL,
  `id_tipo` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `parent_id` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `parent_id_tipo` smallint(5) UNSIGNED NOT NULL,
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
  `char_key` char(1) NOT NULL DEFAULT '',
  `char_1` char(1) NOT NULL DEFAULT '',
  `char_2` char(1) NOT NULL DEFAULT '',
  `char_3` char(1) NOT NULL DEFAULT '',
  `char_4` char(1) NOT NULL DEFAULT '',
  `char_5` char(1) NOT NULL DEFAULT '',
  `char_6` char(1) NOT NULL,
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
  `id_tipo` smallint(5) UNSIGNED NOT NULL,
  `id` mediumint(8) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `interadmin_teste_tipos`
--

CREATE TABLE `interadmin_teste_tipos` (
  `id_tipo` smallint(5) UNSIGNED NOT NULL,
  `id_tipo_string` varchar(255) NOT NULL,
  `id_slug` varchar(255) NOT NULL,
  `date_modify` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `model_id_tipo` varchar(100) NOT NULL DEFAULT '0',
  `parent_id_tipo` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `redirect_id_tipo` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `nome` varchar(100) NOT NULL DEFAULT '',
  `nome_en` varchar(100) NOT NULL DEFAULT '',
  `texto` text NOT NULL,
  `class` varchar(255) NOT NULL DEFAULT '',
  `class_tipo` varchar(255) NOT NULL DEFAULT '',
  `icone` varchar(255) NOT NULL,
  `template` varchar(255) NOT NULL DEFAULT '',
  `editpage` varchar(255) NOT NULL DEFAULT '',
  `template_inserir` varchar(255) NOT NULL DEFAULT '',
  `tabela` varchar(255) NOT NULL DEFAULT '',
  `disparo` varchar(255) NOT NULL DEFAULT '',
  `campos` text NOT NULL,
  `arquivos` varchar(50) NOT NULL DEFAULT '',
  `arquivos_ajuda` varchar(255) NOT NULL DEFAULT '',
  `arquivos_2` varchar(50) NOT NULL DEFAULT '',
  `arquivos_2_ajuda` varchar(255) NOT NULL DEFAULT '',
  `arquivos_3` varchar(50) NOT NULL DEFAULT '',
  `arquivos_3_ajuda` varchar(255) NOT NULL DEFAULT '',
  `links` varchar(50) NOT NULL DEFAULT '',
  `links_ajuda` varchar(255) NOT NULL DEFAULT '',
  `children` text NOT NULL,
  `mostrar` char(1) NOT NULL DEFAULT '',
  `language` char(1) NOT NULL DEFAULT '',
  `menu` char(1) NOT NULL DEFAULT '',
  `busca` char(1) NOT NULL DEFAULT '',
  `restrito` char(1) NOT NULL DEFAULT '',
  `admin` char(1) NOT NULL DEFAULT '',
  `editar` char(1) NOT NULL DEFAULT '',
  `unico` char(1) NOT NULL DEFAULT '',
  `versoes` char(1) NOT NULL DEFAULT '',
  `hits` char(1) NOT NULL,
  `tags` char(1) NOT NULL,
  `tags_list` char(1) NOT NULL,
  `tags_tipo` char(1) NOT NULL,
  `tags_registros` char(1) NOT NULL,
  `publish_tipo` char(1) NOT NULL DEFAULT '',
  `visualizar` char(1) NOT NULL DEFAULT '',
  `layout` tinyint(1) UNSIGNED NOT NULL,
  `layout_registros` tinyint(1) UNSIGNED NOT NULL,
  `ordem` tinyint(4) NOT NULL DEFAULT '0',
  `log` text NOT NULL,
  `deleted_tipo` char(1) NOT NULL DEFAULT '',
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
ALTER TABLE `interadmin_teste_registros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_tipo` (`id_tipo`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `select_key` (`select_key`);
ALTER TABLE `interadmin_teste_registros` ADD FULLTEXT KEY `interadmin_search` (`varchar_key`,`varchar_1`,`varchar_2`,`varchar_3`,`text_1`,`text_2`,`text_3`);

--
-- Indexes for table `interadmin_teste_en_registros`
--
ALTER TABLE `interadmin_teste_en_registros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_tipo` (`id_tipo`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `select_key` (`select_key`);

--
-- Indexes for table `interadmin_teste_tags`
--
ALTER TABLE `interadmin_teste_tags`
  ADD PRIMARY KEY (`id_tag`);

--
-- Indexes for table `interadmin_teste_tipos`
--
ALTER TABLE `interadmin_teste_tipos`
  ADD PRIMARY KEY (`id_tipo`);
ALTER TABLE `interadmin_teste_tipos` ADD FULLTEXT KEY `interadmin_search` (`nome`,`nome_en`,`texto`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `interadmin_teste_registros`
--
ALTER TABLE `interadmin_teste_registros`
  MODIFY `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134911;
--
-- AUTO_INCREMENT for table `interadmin_teste_en_registros`
--
ALTER TABLE `interadmin_teste_en_registros`
  MODIFY `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5983;
--
-- AUTO_INCREMENT for table `interadmin_teste_tags`
--
ALTER TABLE `interadmin_teste_tags`
  MODIFY `id_tag` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `interadmin_teste_tipos`
--
ALTER TABLE `interadmin_teste_tipos`
  MODIFY `id_tipo` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=528;
