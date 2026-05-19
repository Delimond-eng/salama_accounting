-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 19 mai 2026 à 19:52
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `salama_compta`
--

-- --------------------------------------------------------

--
-- Structure de la table `agents`
--

CREATE TABLE `agents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `matricule` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'agent',
  `site_id` bigint(20) UNSIGNED DEFAULT NULL,
  `groupe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `horaire_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(80) DEFAULT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reference` varchar(120) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `societe_id`, `user_id`, `action`, `entity_type`, `entity_id`, `reference`, `description`, `metadata`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'creation', 'facture', 1, 'FAC-2026-0001', 'Création facture FAC-2026-0001', '{\"type\":\"vente_client\",\"montant_ttc\":\"130000.00\"}', '127.0.0.1', '2026-05-19 17:36:10', '2026-05-19 17:36:10'),
(2, 1, 1, 'creation', 'ecriture', 9, 'VT-2026-00002', 'creation — VT-2026-00002 CAMERAS', '{\"statut\":\"validee\",\"journal_id\":2,\"total_debit\":130000,\"total_credit\":130000}', '127.0.0.1', '2026-05-19 17:36:58', '2026-05-19 17:36:58'),
(3, 1, 1, 'validation', 'ecriture', 9, 'VT-2026-00002', 'validation — VT-2026-00002 CAMERAS', '{\"statut\":\"validee\",\"journal_id\":2,\"total_debit\":130000,\"total_credit\":130000}', '127.0.0.1', '2026-05-19 17:36:58', '2026-05-19 17:36:58'),
(4, 1, 1, 'validation_comptable', 'facture', 1, 'FAC-2026-0001', 'Écriture de validation — FAC-2026-0001', '{\"ecriture_id\":9}', '127.0.0.1', '2026-05-19 17:36:58', '2026-05-19 17:36:58'),
(5, 1, 1, 'validation', 'facture', 1, 'FAC-2026-0001', 'Validation facture FAC-2026-0001', NULL, '127.0.0.1', '2026-05-19 17:36:58', '2026-05-19 17:36:58');

-- --------------------------------------------------------

--
-- Structure de la table `axes_analytiques`
--

CREATE TABLE `axes_analytiques` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `budgets`
--

CREATE TABLE `budgets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `exercice_id` bigint(20) UNSIGNED NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `type` enum('general','analytique','tresorerie') NOT NULL DEFAULT 'general',
  `statut` enum('brouillon','valide','archive') NOT NULL DEFAULT 'brouillon',
  `valide_par` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `declarations_fiscales`
--

CREATE TABLE `declarations_fiscales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `exercice_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('tva_mensuelle','tva_trimestrielle','is','dsf','ircm','patente','cnps_mensuel','other') NOT NULL,
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `date_limite_depot` date NOT NULL,
  `date_depot_effectif` date DEFAULT NULL,
  `base_imposable` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tva_collectee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tva_deductible` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tva_nette` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_impot` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit_reporte` decimal(15,2) NOT NULL DEFAULT 0.00,
  `statut` enum('a_declarer','brouillon','deposee','payee','en_contentieux') NOT NULL DEFAULT 'a_declarer',
  `num_quittance` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `etabli_par` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demandes_fonds`
--

CREATE TABLE `demandes_fonds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `workflow_definition_id` bigint(20) UNSIGNED NOT NULL,
  `workflow_etape_courante_id` bigint(20) UNSIGNED DEFAULT NULL,
  `numero` varchar(40) NOT NULL,
  `demandeur_id` bigint(20) UNSIGNED NOT NULL,
  `montant` decimal(18,2) NOT NULL,
  `devise` varchar(3) NOT NULL DEFAULT 'CDF',
  `motif` text NOT NULL,
  `journal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'en_attente',
  `compte_debit` varchar(20) DEFAULT NULL,
  `compte_credit` varchar(20) DEFAULT NULL,
  `ecriture_id` bigint(20) UNSIGNED DEFAULT NULL,
  `motif_rejet` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demande_fonds_historiques`
--

CREATE TABLE `demande_fonds_historiques` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `demande_fonds_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demande_fonds_validations`
--

CREATE TABLE `demande_fonds_validations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `demande_fonds_id` bigint(20) UNSIGNED NOT NULL,
  `workflow_etape_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `decision` varchar(20) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `devises`
--

CREATE TABLE `devises` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code_iso` varchar(3) NOT NULL,
  `libelle` varchar(100) NOT NULL,
  `symbole` varchar(10) DEFAULT NULL,
  `pays` varchar(100) DEFAULT NULL,
  `nb_decimales` int(11) NOT NULL DEFAULT 0,
  `est_devise_reference` tinyint(1) NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devises`
--

INSERT INTO `devises` (`id`, `code_iso`, `libelle`, `symbole`, `pays`, `nb_decimales`, `est_devise_reference`, `actif`, `created_at`, `updated_at`) VALUES
(1, 'XOF', 'Franc CFA BCEAO', 'FCFA', 'Afrique de l\'Ouest (UEMOA)', 0, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(2, 'XAF', 'Franc CFA BEAC', 'FCFA', 'Afrique Centrale (CEMAC)', 0, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(3, 'GNF', 'Franc guinéen', 'FG', 'Guinée', 0, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(4, 'CDF', 'Franc congolais', 'FC', 'République Démocratique du Congo', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(5, 'MGA', 'Ariary malgache', 'Ar', 'Madagascar', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(6, 'KMF', 'Franc comorien', 'CF', 'Comores', 0, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(7, 'NGN', 'Naira nigérian', '₦', 'Nigeria', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(8, 'GHS', 'Cédi ghanéen', 'GH₵', 'Ghana', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(9, 'MAD', 'Dirham marocain', 'DH', 'Maroc', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(10, 'TND', 'Dinar tunisien', 'DT', 'Tunisie', 3, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(11, 'DZD', 'Dinar algérien', 'DA', 'Algérie', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(12, 'EGP', 'Livre égyptienne', 'E£', 'Égypte', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(13, 'KES', 'Shilling kényan', 'KSh', 'Kenya', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(14, 'TZS', 'Shilling tanzanien', 'TSh', 'Tanzanie', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(15, 'UGX', 'Shilling ougandais', 'USh', 'Ouganda', 0, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(16, 'ZAR', 'Rand sud-africain', 'R', 'Afrique du Sud', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(17, 'ETB', 'Birr éthiopien', 'Br', 'Éthiopie', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(18, 'RWF', 'Franc rwandais', 'RF', 'Rwanda', 0, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(19, 'EUR', 'Euro', '€', 'Zone Euro', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(20, 'USD', 'Dollar américain', '$', 'États-Unis', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(21, 'GBP', 'Livre sterling', '£', 'Royaume-Uni', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(22, 'CHF', 'Franc suisse', 'CHF', 'Suisse', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(23, 'CNY', 'Yuan renminbi', '¥', 'Chine', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(24, 'JPY', 'Yen japonais', '¥', 'Japon', 0, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(25, 'CAD', 'Dollar canadien', 'CA$', 'Canada', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(26, 'AED', 'Dirham des Émirats', 'AED', 'Émirats Arabes Unis', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(27, 'SAR', 'Riyal saoudien', 'SR', 'Arabie Saoudite', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(28, 'INR', 'Roupie indienne', '₹', 'Inde', 2, 0, 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11');

-- --------------------------------------------------------

--
-- Structure de la table `echeanciers`
--

CREATE TABLE `echeanciers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `tiers_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date_echeance` date NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `sens` enum('debit','credit') NOT NULL,
  `statut` enum('en_attente','partiellement_regle','regle','contentieux','annule') NOT NULL DEFAULT 'en_attente',
  `montant_regle` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_restant` decimal(15,2) GENERATED ALWAYS AS (`montant` - `montant_regle`) VIRTUAL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ecritures`
--

CREATE TABLE `ecritures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `exercice_id` bigint(20) UNSIGNED NOT NULL,
  `journal_id` bigint(20) UNSIGNED NOT NULL,
  `num_piece` varchar(50) NOT NULL,
  `num_piece_interne` varchar(50) DEFAULT NULL,
  `date_ecriture` date NOT NULL,
  `date_piece` date DEFAULT NULL,
  `date_valeur` date DEFAULT NULL,
  `date_echeance` date DEFAULT NULL,
  `libelle` varchar(255) NOT NULL,
  `statut` enum('brouillon','validee','extournee','simulee') NOT NULL DEFAULT 'brouillon',
  `type_ecriture` enum('normale','ouverture','cloture','inventaire','extourne','simulation','budget') NOT NULL DEFAULT 'normale',
  `reference_externe` varchar(100) DEFAULT NULL,
  `reference_facture` varchar(100) DEFAULT NULL,
  `total_debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `devise` varchar(3) NOT NULL DEFAULT 'XOF',
  `taux_change` decimal(18,6) NOT NULL DEFAULT 1.000000,
  `cree_par` bigint(20) UNSIGNED DEFAULT NULL,
  `valide_par` bigint(20) UNSIGNED DEFAULT NULL,
  `valide_le` timestamp NULL DEFAULT NULL,
  `modifie_par` bigint(20) UNSIGNED DEFAULT NULL,
  `ecriture_origine_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date_extourne` date DEFAULT NULL,
  `est_import` tinyint(1) NOT NULL DEFAULT 0,
  `source_import` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ecritures`
--

INSERT INTO `ecritures` (`id`, `societe_id`, `exercice_id`, `journal_id`, `num_piece`, `num_piece_interne`, `date_ecriture`, `date_piece`, `date_valeur`, `date_echeance`, `libelle`, `statut`, `type_ecriture`, `reference_externe`, `reference_facture`, `total_debit`, `total_credit`, `devise`, `taux_change`, `cree_par`, `valide_par`, `valide_le`, `modifie_par`, `ecriture_origine_id`, `date_extourne`, `est_import`, `source_import`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 1, 'HA-2026-00001', NULL, '2026-05-17', NULL, NULL, NULL, 'Écriture Journal des achats', 'validee', 'normale', NULL, NULL, 1000.00, 1000.00, 'CDF', 1.000000, 1, 1, '2026-05-17 18:44:28', 1, NULL, NULL, 0, NULL, NULL, '2026-05-17 18:44:28', '2026-05-17 18:44:28', NULL),
(2, 1, 1, 2, 'VT-2026-00001', NULL, '2026-05-17', NULL, NULL, NULL, 'Écriture Journal des ventes', 'validee', 'normale', NULL, NULL, 4150.00, 4150.00, 'CDF', 1.000000, 1, 1, '2026-05-17 18:46:14', 1, NULL, NULL, 0, NULL, NULL, '2026-05-17 18:46:14', '2026-05-17 18:46:14', NULL),
(3, 1, 1, 3, 'BQ-2026-05-0001', NULL, '2026-05-17', NULL, NULL, NULL, 'Écriture Journal de banque', 'validee', 'normale', NULL, NULL, 1180.00, 1180.00, 'CDF', 1.000000, 1, 1, '2026-05-17 18:48:09', 1, NULL, NULL, 0, NULL, NULL, '2026-05-17 18:48:09', '2026-05-17 18:48:09', NULL),
(4, 1, 1, 4, 'CA-2026-05-0001', NULL, '2026-05-17', NULL, NULL, NULL, 'Écriture Journal de caisse', 'validee', 'normale', NULL, NULL, 8000.00, 8000.00, 'CDF', 1.000000, 1, 1, '2026-05-17 18:50:26', 1, NULL, NULL, 0, NULL, NULL, '2026-05-17 18:50:26', '2026-05-17 18:50:26', NULL),
(5, 1, 1, 5, 'OD-2026-00001', NULL, '2026-05-18', NULL, NULL, NULL, 'Écriture Opérations diverses', 'validee', 'normale', NULL, 'rdd', 2500.00, 2500.00, 'CDF', 1.000000, 1, 1, '2026-05-18 14:45:21', 1, NULL, NULL, 0, NULL, NULL, '2026-05-18 14:45:21', '2026-05-18 14:45:21', NULL),
(6, 1, 1, 5, 'OD-2026-00002', NULL, '2026-05-18', NULL, NULL, NULL, 'Écriture Opérations diverses', 'validee', 'normale', NULL, NULL, 1000000.00, 1000000.00, 'CDF', 1.000000, 1, 1, '2026-05-18 15:13:06', 1, NULL, NULL, 0, NULL, NULL, '2026-05-18 15:13:06', '2026-05-18 15:13:06', NULL),
(7, 1, 1, 5, 'OD-2026-00003', NULL, '2026-05-18', NULL, NULL, NULL, 'Écriture Opérations diverses', 'validee', 'normale', NULL, NULL, 680.00, 680.00, 'CDF', 1.000000, 1, 1, '2026-05-18 18:43:04', 1, NULL, NULL, 0, NULL, NULL, '2026-05-18 18:43:04', '2026-05-18 18:43:04', NULL),
(8, 1, 1, 5, 'OD-2026-00004', NULL, '2026-05-18', NULL, NULL, NULL, 'Écriture Opérations diverses', 'validee', 'normale', NULL, NULL, 1360.00, 1360.00, 'CDF', 1.000000, 1, 1, '2026-05-18 18:49:49', 1, NULL, NULL, 0, NULL, NULL, '2026-05-18 18:49:49', '2026-05-18 18:49:49', NULL),
(9, 1, 1, 2, 'VT-2026-00002', NULL, '2026-05-19', NULL, NULL, NULL, 'CAMERAS', 'validee', 'normale', 'FAC-2026-0001', 'FAC-2026-0001', 130000.00, 130000.00, 'CDF', 2200.000000, 1, 1, '2026-05-19 17:36:58', 1, NULL, NULL, 0, NULL, NULL, '2026-05-19 17:36:58', '2026-05-19 17:36:58', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `etats_financiers`
--

CREATE TABLE `etats_financiers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `exercice_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('bilan','compte_resultat','tafire','variation_capitaux_propres','balance_generale','balance_auxiliaire','grand_livre','balance_agee','autre') NOT NULL,
  `date_arrete` date NOT NULL,
  `donnees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`donnees`)),
  `fichier_path` varchar(500) DEFAULT NULL,
  `est_definitif` tinyint(1) NOT NULL DEFAULT 0,
  `genere_par` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `exercices`
--

CREATE TABLE `exercices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `libelle` varchar(100) NOT NULL,
  `annee` year(4) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `statut` enum('ouvert','pre_cloture','cloture','archive') NOT NULL DEFAULT 'ouvert',
  `est_courant` tinyint(1) NOT NULL DEFAULT 0,
  `date_ouverture` date DEFAULT NULL,
  `date_cloture` date DEFAULT NULL,
  `cloture_par` bigint(20) UNSIGNED DEFAULT NULL,
  `notes_cloture` text DEFAULT NULL,
  `report_a_nouveau_genere` tinyint(1) NOT NULL DEFAULT 0,
  `bilan_ouverture_genere` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `exercices`
--

INSERT INTO `exercices` (`id`, `societe_id`, `libelle`, `annee`, `date_debut`, `date_fin`, `statut`, `est_courant`, `date_ouverture`, `date_cloture`, `cloture_par`, `notes_cloture`, `report_a_nouveau_genere`, `bilan_ouverture_genere`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Exercice 2026', '2026', '2026-01-01', '2026-12-31', 'ouvert', 1, NULL, NULL, NULL, NULL, 0, 0, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `factures`
--

CREATE TABLE `factures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `exercice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type_document` varchar(30) NOT NULL,
  `numero` varchar(40) NOT NULL,
  `tiers_id` bigint(20) UNSIGNED NOT NULL,
  `facture_origine_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date_facture` date NOT NULL,
  `date_echeance` date DEFAULT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'brouillon',
  `objet` varchar(255) DEFAULT NULL,
  `montant_ht` decimal(18,2) NOT NULL DEFAULT 0.00,
  `montant_tva` decimal(18,2) NOT NULL DEFAULT 0.00,
  `montant_ttc` decimal(18,2) NOT NULL DEFAULT 0.00,
  `taux_tva` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tva_active` tinyint(1) NOT NULL DEFAULT 0,
  `devise` varchar(3) NOT NULL DEFAULT 'CDF',
  `ecriture_validation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cree_par` bigint(20) UNSIGNED DEFAULT NULL,
  `valide_par` bigint(20) UNSIGNED DEFAULT NULL,
  `valide_le` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `factures`
--

INSERT INTO `factures` (`id`, `societe_id`, `exercice_id`, `type_document`, `numero`, `tiers_id`, `facture_origine_id`, `date_facture`, `date_echeance`, `statut`, `objet`, `montant_ht`, `montant_tva`, `montant_ttc`, `taux_tva`, `tva_active`, `devise`, `ecriture_validation_id`, `notes`, `cree_par`, `valide_par`, `valide_le`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, 'vente_client', 'FAC-2026-0001', 2, NULL, '2026-05-19', NULL, 'validee', 'CAMERAS', 130000.00, 0.00, 130000.00, 16.00, 0, 'CDF', 9, NULL, 1, 1, '2026-05-19 17:36:58', '2026-05-19 17:36:09', '2026-05-19 17:36:58', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `facture_lignes`
--

CREATE TABLE `facture_lignes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `facture_id` bigint(20) UNSIGNED NOT NULL,
  `produit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ordre` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `libelle` varchar(255) NOT NULL,
  `quantite` decimal(18,4) NOT NULL DEFAULT 1.0000,
  `prix_unitaire` decimal(18,2) NOT NULL DEFAULT 0.00,
  `montant_ht` decimal(18,2) NOT NULL DEFAULT 0.00,
  `compte_comptable` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `facture_lignes`
--

INSERT INTO `facture_lignes` (`id`, `facture_id`, `produit_id`, `ordre`, `libelle`, `quantite`, `prix_unitaire`, `montant_ht`, `compte_comptable`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1, 'CAMERA HIVKISION4MP', 1.0000, 50000.00, 50000.00, NULL, '2026-05-19 17:36:10', '2026-05-19 17:36:10'),
(2, 1, NULL, 2, 'NVR', 1.0000, 80000.00, 80000.00, NULL, '2026-05-19 17:36:10', '2026-05-19 17:36:10');

-- --------------------------------------------------------

--
-- Structure de la table `imports_logs`
--

CREATE TABLE `imports_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type_import` enum('releve_bancaire','plan_comptable','tiers','ecritures_csv','factures') NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `total_lignes` int(11) NOT NULL DEFAULT 0,
  `lignes_importees` int(11) NOT NULL DEFAULT 0,
  `lignes_erreurs` int(11) NOT NULL DEFAULT 0,
  `erreurs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`erreurs`)),
  `statut` enum('en_cours','termine','echec') NOT NULL DEFAULT 'en_cours',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `journal_audit`
--

CREATE TABLE `journal_audit` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `module` varchar(50) NOT NULL,
  `entite_type` varchar(100) DEFAULT NULL,
  `entite_id` bigint(20) UNSIGNED DEFAULT NULL,
  `avant` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`avant`)),
  `apres` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`apres`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `journaux`
--

CREATE TABLE `journaux` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(10) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `type` enum('achats','ventes','banque','caisse','operations_diverses','salaires','stocks','effets','immobilisations','ouverture','cloture','simulation') NOT NULL,
  `compte_contrepartie` varchar(15) DEFAULT NULL,
  `prefixe_piece` varchar(10) DEFAULT NULL,
  `prochain_numero` int(11) NOT NULL DEFAULT 1,
  `format_numerotation` enum('annuel','mensuel','continu') NOT NULL DEFAULT 'annuel',
  `padding_numero` int(11) NOT NULL DEFAULT 4,
  `saisie_tiers_obligatoire` tinyint(1) NOT NULL DEFAULT 0,
  `saisie_lettrage_auto` tinyint(1) NOT NULL DEFAULT 0,
  `mode_brouillard` tinyint(1) NOT NULL DEFAULT 0,
  `devise_defaut` varchar(3) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `ordre_affichage` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `journaux`
--

INSERT INTO `journaux` (`id`, `societe_id`, `code`, `libelle`, `type`, `compte_contrepartie`, `prefixe_piece`, `prochain_numero`, `format_numerotation`, `padding_numero`, `saisie_tiers_obligatoire`, `saisie_lettrage_auto`, `mode_brouillard`, `devise_defaut`, `actif`, `ordre_affichage`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'HA', 'Journal des achats', 'achats', '401', 'HA-', 2, 'annuel', 5, 1, 0, 0, NULL, 1, 1, '2026-05-17 16:13:12', '2026-05-17 18:44:28', NULL),
(2, 1, 'VT', 'Journal des ventes', 'ventes', '411', 'VT-', 3, 'annuel', 5, 1, 0, 0, NULL, 1, 2, '2026-05-17 16:13:12', '2026-05-19 17:36:58', NULL),
(3, 1, 'BQ', 'Journal de banque', 'banque', '521', 'BQ-', 2, 'mensuel', 4, 0, 0, 0, NULL, 1, 3, '2026-05-17 16:13:12', '2026-05-17 18:48:09', NULL),
(4, 1, 'CA', 'Journal de caisse', 'caisse', '571', 'CA-', 2, 'mensuel', 4, 0, 0, 0, NULL, 1, 4, '2026-05-17 16:13:12', '2026-05-17 18:50:26', NULL),
(5, 1, 'OD', 'Opérations diverses', 'operations_diverses', NULL, 'OD-', 5, 'annuel', 5, 0, 0, 0, NULL, 1, 5, '2026-05-17 16:13:12', '2026-05-18 18:49:49', NULL),
(6, 1, 'SA', 'Journal des salaires', 'salaires', '422', 'SA-', 1, 'mensuel', 3, 0, 0, 0, NULL, 1, 6, '2026-05-17 16:13:12', '2026-05-17 16:13:12', NULL),
(7, 1, 'IM', 'Journal des immobilisations', 'immobilisations', NULL, 'IM-', 1, 'annuel', 4, 0, 0, 0, NULL, 1, 7, '2026-05-17 16:13:12', '2026-05-17 16:13:12', NULL),
(8, 1, 'EF', 'Journal des effets', 'effets', NULL, 'EF-', 1, 'annuel', 4, 1, 1, 0, NULL, 0, 8, '2026-05-17 16:13:12', '2026-05-17 16:13:12', NULL),
(9, 1, 'AN', 'Journal d\'à-nouveau', 'ouverture', NULL, 'AN-', 1, 'annuel', 3, 0, 0, 0, NULL, 1, 9, '2026-05-17 16:13:12', '2026-05-17 16:13:12', NULL),
(10, 1, 'CL', 'Journal de clôture', 'cloture', NULL, 'CL-', 1, 'annuel', 3, 0, 0, 0, NULL, 1, 10, '2026-05-17 16:13:12', '2026-05-17 16:13:12', NULL),
(11, 1, 'SI', 'Journal de simulation', 'simulation', NULL, 'SI-', 1, 'annuel', 4, 0, 0, 1, NULL, 0, 11, '2026-05-17 16:13:12', '2026-05-17 16:13:12', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `lettrage_groupes`
--

CREATE TABLE `lettrage_groupes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `num_compte` varchar(15) NOT NULL,
  `tiers_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lettre` varchar(10) NOT NULL,
  `total_debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `solde_lettre` decimal(15,2) NOT NULL DEFAULT 0.00,
  `statut` enum('partiel','complet') NOT NULL DEFAULT 'complet',
  `date_lettrage` date NOT NULL,
  `lettre_par` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lignes_budget`
--

CREATE TABLE `lignes_budget` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `budget_id` bigint(20) UNSIGNED NOT NULL,
  `num_compte` varchar(15) NOT NULL,
  `compte_id` bigint(20) UNSIGNED NOT NULL,
  `section_analytique_id` bigint(20) UNSIGNED DEFAULT NULL,
  `montant_janvier` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_fevrier` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_mars` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_avril` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_mai` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_juin` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_juillet` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_aout` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_septembre` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_octobre` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_novembre` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_decembre` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_annuel` decimal(15,2) GENERATED ALWAYS AS (`montant_janvier` + `montant_fevrier` + `montant_mars` + `montant_avril` + `montant_mai` + `montant_juin` + `montant_juillet` + `montant_aout` + `montant_septembre` + `montant_octobre` + `montant_novembre` + `montant_decembre`) VIRTUAL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lignes_ecritures`
--

CREATE TABLE `lignes_ecritures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ecriture_id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `exercice_id` bigint(20) UNSIGNED NOT NULL,
  `journal_id` bigint(20) UNSIGNED NOT NULL,
  `num_compte` varchar(15) NOT NULL,
  `compte_id` bigint(20) UNSIGNED NOT NULL,
  `tiers_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date_ecriture` date NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `devise` varchar(3) NOT NULL DEFAULT 'XOF',
  `montant_devise` decimal(15,2) DEFAULT NULL,
  `taux_change` decimal(18,6) NOT NULL DEFAULT 1.000000,
  `lettre` varchar(10) DEFAULT NULL,
  `date_lettrage` date DEFAULT NULL,
  `lettre_par` bigint(20) UNSIGNED DEFAULT NULL,
  `pointage` varchar(10) DEFAULT NULL,
  `date_pointage` date DEFAULT NULL,
  `axe_analytique_id` bigint(20) UNSIGNED DEFAULT NULL,
  `section_analytique_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ordre` int(11) NOT NULL DEFAULT 0,
  `reference_ligne` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `lignes_ecritures`
--

INSERT INTO `lignes_ecritures` (`id`, `ecriture_id`, `societe_id`, `exercice_id`, `journal_id`, `num_compte`, `compte_id`, `tiers_id`, `date_ecriture`, `libelle`, `debit`, `credit`, `devise`, `montant_devise`, `taux_change`, `lettre`, `date_lettrage`, `lettre_par`, `pointage`, `date_pointage`, `axe_analytique_id`, `section_analytique_id`, `ordre`, `reference_ligne`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '601100', 708, 1, '2026-05-17', 'Achat marchandises', 1000.00, 0.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-05-17 18:44:28', '2026-05-17 18:44:28'),
(2, 1, 1, 1, 1, '401100', 430, 1, '2026-05-17', 'Achat marchandises', 0.00, 1000.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, '2026-05-17 18:44:28', '2026-05-17 18:44:28'),
(3, 2, 1, 1, 2, '411100', 451, 2, '2026-05-17', 'Écriture Journal des ventes', 4150.00, 0.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-05-17 18:46:14', '2026-05-17 18:46:14'),
(4, 2, 1, 1, 2, '701100', 922, 2, '2026-05-17', 'Écriture Journal des ventes', 0.00, 4150.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, '2026-05-17 18:46:14', '2026-05-17 18:46:14'),
(5, 3, 1, 1, 3, '521001', 669, 1, '2026-05-17', 'Écriture Journal de banque', 0.00, 1180.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-05-17 18:48:09', '2026-05-17 18:48:09'),
(6, 3, 1, 1, 3, '401100', 430, 1, '2026-05-17', 'Écriture Journal de banque', 1180.00, 0.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, '2026-05-17 18:48:09', '2026-05-17 18:48:09'),
(7, 4, 1, 1, 4, '571100', 692, 2, '2026-05-17', 'Écriture Journal de caisse', 8000.00, 0.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-05-17 18:50:26', '2026-05-17 18:50:26'),
(8, 4, 1, 1, 4, '604100', 723, 2, '2026-05-17', 'Écriture Journal de caisse', 0.00, 8000.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, '2026-05-17 18:50:26', '2026-05-17 18:50:26'),
(9, 5, 1, 1, 5, '601100', 708, 1, '2026-05-18', 'Écriture Opérations diverses', 1000.00, 0.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-05-18 14:45:21', '2026-05-18 14:45:21'),
(10, 5, 1, 1, 5, '401100', 430, 1, '2026-05-18', 'Écriture Opérations diverses', 0.00, 1000.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, '2026-05-18 14:45:21', '2026-05-18 14:45:21'),
(11, 5, 1, 1, 5, '401100', 430, 1, '2026-05-18', 'Écriture Opérations diverses', 1500.00, 0.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, '2026-05-18 14:45:21', '2026-05-18 14:45:21'),
(12, 5, 1, 1, 5, '521001', 669, 1, '2026-05-18', 'Écriture Opérations diverses', 0.00, 1500.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, '2026-05-18 14:45:21', '2026-05-18 14:45:21'),
(13, 6, 1, 1, 5, '521001', 669, NULL, '2026-05-18', 'Écriture Opérations diverses', 1000000.00, 0.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-05-18 15:13:06', '2026-05-18 15:13:06'),
(14, 6, 1, 1, 5, '101100', 1, NULL, '2026-05-18', 'Écriture Opérations diverses', 0.00, 1000000.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, '2026-05-18 15:13:06', '2026-05-18 15:13:06'),
(15, 7, 1, 1, 5, '401100', 430, 1, '2026-05-18', 'Écriture Opérations diverses', 680.00, 0.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-05-18 18:43:04', '2026-05-18 18:43:04'),
(16, 7, 1, 1, 5, '521001', 669, 1, '2026-05-18', 'Écriture Opérations diverses', 0.00, 680.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, '2026-05-18 18:43:04', '2026-05-18 18:43:04'),
(17, 8, 1, 1, 5, '401100', 430, 1, '2026-05-18', 'Écriture Opérations diverses', 0.00, 1360.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-05-18 18:49:49', '2026-05-18 18:49:49'),
(18, 8, 1, 1, 5, '521001', 669, 1, '2026-05-18', 'Écriture Opérations diverses', 1360.00, 0.00, 'CDF', NULL, 1.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, '2026-05-18 18:49:49', '2026-05-18 18:49:49'),
(19, 9, 1, 1, 2, '411200', 452, 2, '2026-05-19', 'CAMERAS', 130000.00, 0.00, 'CDF', NULL, 2200.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-05-19 17:36:58', '2026-05-19 17:36:58'),
(20, 9, 1, 1, 2, '701100', 922, NULL, '2026-05-19', 'CAMERAS', 0.00, 130000.00, 'CDF', NULL, 2200.000000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, '2026-05-19 17:36:58', '2026-05-19 17:36:58');

-- --------------------------------------------------------

--
-- Structure de la table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(3, '2024_01_01_000001_create_societes_table', 1),
(4, '2024_01_01_000002_create_exercices_table', 1),
(5, '2024_01_01_000003_create_plan_comptable_table', 1),
(6, '2024_01_01_000004_create_journaux_table', 1),
(7, '2024_01_01_000005_create_tiers_table', 1),
(8, '2024_01_01_000006_create_devises_table', 1),
(9, '2024_01_01_000007_create_complementaires_table', 1),
(10, '2024_01_01_000008_create_ecritures_table', 1),
(11, '2024_01_01_000009_create_avancees_table', 1),
(12, '2024_09_27_004622_create_agents_table', 1),
(13, '2026_02_07_052355_create_permission_tables', 1),
(14, '2026_05_16_000001_add_type_compte_detail_to_plan_comptable_table', 1),
(15, '2026_05_20_100000_create_audit_logs_table', 2),
(16, '2026_05_21_100000_create_facturation_tables', 3);

-- --------------------------------------------------------

--
-- Structure de la table `modeles_ecritures`
--

CREATE TABLE `modeles_ecritures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `journal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `frequence` enum('ponctuel','quotidien','hebdomadaire','mensuel','trimestriel','annuel') NOT NULL DEFAULT 'ponctuel',
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `modeles_ecritures_lignes`
--

CREATE TABLE `modeles_ecritures_lignes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `modele_id` bigint(20) UNSIGNED NOT NULL,
  `num_compte` varchar(15) NOT NULL,
  `compte_id` bigint(20) UNSIGNED NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `sens` enum('debit','credit') NOT NULL,
  `montant` decimal(15,2) DEFAULT NULL,
  `pourcentage` decimal(5,2) DEFAULT NULL,
  `ordre` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(3, 'App\\Models\\User', 1),
(3, 'App\\Models\\User', 2),
(6, 'App\\Models\\User', 3);

-- --------------------------------------------------------

--
-- Structure de la table `notifications_compta`
--

CREATE TABLE `notifications_compta` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('echeance_proche','echeance_depassee','declaration_due','lettrage_ecart','rapprochement_ecart','exercice_non_cloture','depassement_budget','autre') NOT NULL,
  `titre` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `lien` varchar(500) DEFAULT NULL,
  `lue` tinyint(1) NOT NULL DEFAULT 0,
  `lue_le` timestamp NULL DEFAULT NULL,
  `priorite` enum('basse','normale','haute','critique') NOT NULL DEFAULT 'normale',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

CREATE TABLE `paiements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `type_paiement` varchar(30) NOT NULL,
  `facture_id` bigint(20) UNSIGNED DEFAULT NULL,
  `demande_fonds_id` bigint(20) UNSIGNED DEFAULT NULL,
  `numero` varchar(40) NOT NULL,
  `montant` decimal(18,2) NOT NULL,
  `devise` varchar(3) NOT NULL DEFAULT 'CDF',
  `methode` varchar(20) NOT NULL,
  `compte_tresorerie` varchar(20) NOT NULL,
  `date_paiement` date NOT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'brouillon',
  `ecriture_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametres_societe`
--

CREATE TABLE `parametres_societe` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `cle` varchar(100) NOT NULL,
  `valeur` text DEFAULT NULL,
  `type_valeur` varchar(30) NOT NULL DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametres_systeme`
--

CREATE TABLE `parametres_systeme` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cle` varchar(100) NOT NULL,
  `valeur` text DEFAULT NULL,
  `type_valeur` varchar(30) NOT NULL DEFAULT 'string',
  `groupe` varchar(50) DEFAULT NULL,
  `libelle` varchar(200) DEFAULT NULL,
  `modifiable` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres_systeme`
--

INSERT INTO `parametres_systeme` (`id`, `cle`, `valeur`, `type_valeur`, `groupe`, `libelle`, `modifiable`, `created_at`, `updated_at`) VALUES
(1, 'syscohada_version', 'Acte Uniforme révisé 2017', 'string', 'syscohada', 'Version SYSCOHADA', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(2, 'syscohada_date_application', '2018-01-01', 'date', 'syscohada', 'Date d\'application', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(3, 'syscohada_nb_classes', '9', 'int', 'syscohada', 'Nombre de classes', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(4, 'duree_exercice_mois', '12', 'int', 'syscohada', 'Durée exercice (mois)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(5, 'tva_taux_normal', '18', 'decimal', 'fiscalite', 'Taux TVA normal (%)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(6, 'tva_taux_reduit', '9', 'decimal', 'fiscalite', 'Taux TVA réduit (%)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(7, 'tva_compte_collectee', '443100', 'string', 'fiscalite', 'Compte TVA collectée', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(8, 'tva_compte_deductible', '445400', 'string', 'fiscalite', 'Compte TVA déductible', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(9, 'tva_periodicite', 'mensuelle', 'string', 'fiscalite', 'Périodicité déclaration TVA', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(10, 'devise_principale_defaut', 'XOF', 'string', 'devises', 'Devise principale par défaut', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(11, 'nb_decimales_montants', '0', 'int', 'devises', 'Nombre de décimales (XOF)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(12, 'compte_resultat_benefice', '131', 'string', 'comptes', 'Compte résultat net bénéfice', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(13, 'compte_resultat_perte', '139', 'string', 'comptes', 'Compte résultat net perte', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(14, 'compte_report_nouveau_B', '121', 'string', 'comptes', 'Compte report à nouveau (bénéfice)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(15, 'compte_report_nouveau_P', '129', 'string', 'comptes', 'Compte report à nouveau (perte)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(16, 'compte_ecart_conversion_A', '476', 'string', 'comptes', 'Compte écart conversion actif (476)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(17, 'compte_ecart_conversion_P', '477', 'string', 'comptes', 'Compte écart conversion passif (477)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(18, 'format_num_piece', '{PREFIX}{ANNEE}-{NUM}', 'string', 'numerotation', 'Format numérotation pièces', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(19, 'numerotation_continue', 'false', 'bool', 'numerotation', 'Numérotation continue (non annuelle)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(20, 'nb_lignes_par_page', '25', 'int', 'interface', 'Lignes par page (tableaux)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(21, 'date_format_affichage', 'd/m/Y', 'string', 'interface', 'Format date affichage', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(22, 'langue_defaut', 'fr', 'string', 'interface', 'Langue par défaut', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(23, 'alerte_echeance_jours', '7', 'int', 'alertes', 'Alerte échéance (jours avant)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11'),
(24, 'alerte_declaration_jours', '10', 'int', 'alertes', 'Alerte déclaration (jours avant)', 1, '2026-05-17 16:13:11', '2026-05-17 16:13:11');

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'dashboard_admin.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(2, 'accounting_journal.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(3, 'accounting_journal.create', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(4, 'accounting_journal.update', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(5, 'accounting_journal.delete', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(6, 'accounting_journal.export', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(7, 'accounting_journal.validate', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(8, 'accounting_ledger.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(9, 'accounting_ledger.export', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(10, 'accounting_trial_balance.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(11, 'accounting_trial_balance.export', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(12, 'accounting_subsidiary_balance.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(13, 'accounting_subsidiary_balance.export', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(14, 'accounting_cash_draft.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(15, 'accounting_cash_draft.create', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(16, 'accounting_cash_draft.update', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(17, 'accounting_cash_draft.delete', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(18, 'accounting_cash_draft.validate', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(19, 'accounting_reconciliation.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(20, 'accounting_reconciliation.process', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(21, 'accounting_closing.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(22, 'accounting_closing.process', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(23, 'accounting_reopening.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(24, 'accounting_reopening.process', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(25, 'accounting_exports.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(26, 'accounting_exports.export', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(27, 'users.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(28, 'users.create', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(29, 'users.update', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(30, 'users.delete', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(31, 'roles.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(32, 'roles.create', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(33, 'roles.update', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(34, 'roles.delete', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(35, 'logs.view', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(36, 'dashboard.view', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(37, 'saisie.view', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(38, 'saisie.create', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(39, 'saisie.update', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(40, 'saisie.validate', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(41, 'saisie.delete', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(42, 'livres.view', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(43, 'livres.export', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(44, 'tresorerie.view', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(45, 'tresorerie.create', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(46, 'tresorerie.update', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(47, 'tresorerie.export', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(48, 'etats.view', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(49, 'etats.export', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(50, 'exercices.view', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(51, 'exercices.create', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(52, 'exercices.update', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(53, 'exercices.process', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(54, 'parametres.view', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(55, 'parametres.create', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(56, 'parametres.update', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(57, 'parametres.delete', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(58, 'fiscalite.view', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(59, 'fiscalite.export', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(60, 'fiscalite.process', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(61, 'audit.view', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(62, 'facturation.view', 'web', '2026-05-19 14:22:09', '2026-05-19 14:22:09'),
(63, 'facturation.create', 'web', '2026-05-19 14:22:09', '2026-05-19 14:22:09'),
(64, 'facturation.update', 'web', '2026-05-19 14:22:09', '2026-05-19 14:22:09'),
(65, 'facturation.validate', 'web', '2026-05-19 14:22:09', '2026-05-19 14:22:09'),
(66, 'facturation.delete', 'web', '2026-05-19 14:22:09', '2026-05-19 14:22:09'),
(67, 'facturation.export', 'web', '2026-05-19 14:22:09', '2026-05-19 14:22:09'),
(68, 'facturation.process', 'web', '2026-05-19 14:22:09', '2026-05-19 14:22:09');

-- --------------------------------------------------------

--
-- Structure de la table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pieces_jointes`
--

CREATE TABLE `pieces_jointes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `pj_able_type` varchar(255) NOT NULL,
  `pj_able_id` bigint(20) UNSIGNED NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `nom_original` varchar(255) NOT NULL,
  `chemin` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `taille_octets` bigint(20) UNSIGNED NOT NULL,
  `type_document` enum('facture','bon_commande','releve_bancaire','contrat','justificatif','autre') NOT NULL DEFAULT 'autre',
  `uploade_par` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `plan_comptable`
--

CREATE TABLE `plan_comptable` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED DEFAULT NULL,
  `num_compte` varchar(15) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `libelle_abrege` varchar(60) DEFAULT NULL,
  `classe` tinyint(4) NOT NULL,
  `num_compte_parent` varchar(15) DEFAULT NULL,
  `niveau` tinyint(4) NOT NULL DEFAULT 1,
  `type_compte` enum('bilan','gestion','hors_bilan','analytique') NOT NULL,
  `type_compte_detail` varchar(100) DEFAULT NULL,
  `sens_normal` enum('debiteur','crediteur') NOT NULL,
  `categorie_bilan` enum('actif_immobilise','actif_circulant','tresorerie_actif','capitaux_propres','dettes_financieres','passif_circulant','tresorerie_passif','charges_ao','produits_ao','charges_hao','produits_hao','participation','impots','resultat','non_applicable') NOT NULL DEFAULT 'non_applicable',
  `est_compte_detail` tinyint(1) NOT NULL DEFAULT 1,
  `est_compte_tiers` tinyint(1) NOT NULL DEFAULT 0,
  `est_lettrable` tinyint(1) NOT NULL DEFAULT 0,
  `est_rapprochable` tinyint(1) NOT NULL DEFAULT 0,
  `est_budgetaire` tinyint(1) NOT NULL DEFAULT 0,
  `exige_piece_jointe` tinyint(1) NOT NULL DEFAULT 0,
  `multi_devises` tinyint(1) NOT NULL DEFAULT 0,
  `exige_analytique` tinyint(1) NOT NULL DEFAULT 0,
  `type_tva` enum('collectee','deductible','non_soumis','exonere') NOT NULL DEFAULT 'non_soumis',
  `taux_tva_defaut` decimal(5,2) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `est_systeme` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `plan_comptable`
--

INSERT INTO `plan_comptable` (`id`, `societe_id`, `num_compte`, `libelle`, `libelle_abrege`, `classe`, `num_compte_parent`, `niveau`, `type_compte`, `type_compte_detail`, `sens_normal`, `categorie_bilan`, `est_compte_detail`, `est_compte_tiers`, `est_lettrable`, `est_rapprochable`, `est_budgetaire`, `exige_piece_jointe`, `multi_devises`, `exige_analytique`, `type_tva`, `taux_tva_defaut`, `actif`, `est_systeme`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, '101100', 'Capital engagé non appelé', NULL, 1, '101100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(2, NULL, '101200', 'Capital souscrit, appelé, non versé', NULL, 1, '101200', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(3, NULL, '101300', 'Capital souscrit, appelé, versé, non amorti', NULL, 1, '101300', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(4, NULL, '101400', 'Capital souscrit, appelé, payé, amorti', NULL, 1, '101400', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(5, NULL, '101800', 'Capital souscrit soumis à des conditions spécifiques', NULL, 1, '101800', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(6, NULL, '102100', 'Dotation initiale', NULL, 1, '102100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(7, NULL, '102200', 'Allocations supplémentaires', NULL, 1, '102200', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(8, NULL, '102800', 'Autres dotations', NULL, 1, '102800', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(9, NULL, '103000', 'Capital personnel', NULL, 1, '103000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(10, NULL, '104100', 'Contributions temporaires', NULL, 1, '104100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(11, NULL, '104200', 'Opérations communes', NULL, 1, '104200', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(12, NULL, '104300', 'Rémunération, impôts et autres charges personnelles', NULL, 1, '104300', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(13, NULL, '104700', 'Retraits pour autoconsommation', NULL, 1, '104700', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(14, NULL, '104800', 'Autres prélèvements', NULL, 1, '104800', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(15, NULL, '105100', 'Primes d\'émission', NULL, 1, '105100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(16, NULL, '105200', 'Primes de cotisation', NULL, 1, '105200', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(17, NULL, '105300', 'Primes de fusion', NULL, 1, '105300', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(18, NULL, '105400', 'Primes de conversion', NULL, 1, '105400', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(19, NULL, '105800', 'Autres primes', NULL, 1, '105800', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(20, NULL, '106100', 'Différences de réévaluation légales', NULL, 1, '106100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(21, NULL, '106200', 'Écarts de réévaluation libres', NULL, 1, '106200', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(22, NULL, '109000', 'Contributors, subscribed capital, uncalled', NULL, 1, '109000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(23, NULL, '111000', 'Réserve légale', NULL, 1, '111000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(24, NULL, '112000', 'Réserves statutaires ou contractuelles', NULL, 1, '112000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(25, NULL, '113100', 'Réserves de plus-values nettes à long terme', NULL, 1, '113100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(26, NULL, '113200', 'Réserves pour l\'attribution gratuite d\'actions aux salariés et dirigeants', NULL, 1, '113200', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(27, NULL, '113300', 'Réserves résultant de l\'octroi de subventions d\'investissement', NULL, 1, '113300', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(28, NULL, '113400', 'Réserves de titres donnant accès au capital', NULL, 1, '113400', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(29, NULL, '113800', 'Autres réserves réglementées', NULL, 1, '113800', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(30, NULL, '118100', 'Réservations facultatives', NULL, 1, '118100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(31, NULL, '118800', 'Réserves diverses', NULL, 1, '118800', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(32, NULL, '121000', 'Créancier reporté', NULL, 1, '121000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(33, NULL, '129100', 'Perte nette à reporter', NULL, 1, '129100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(34, NULL, '129200', 'Perte - Amortissement réputé différé', NULL, 1, '129200', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(35, NULL, '130100', 'Résultat en attente d\'affectation : Bénéfice', NULL, 1, '130100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(36, NULL, '130900', 'Résultat en attente d\'affectation : Perte', NULL, 1, '130900', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(37, NULL, '131000', 'Revenu net : profit', NULL, 1, '131000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(38, NULL, '132000', 'Marge commerciale (tm)', NULL, 1, '132000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(39, NULL, '133000', 'Valeur ajoutee (v.a.)', NULL, 1, '133000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(40, NULL, '134000', 'Excédent brut d\'exploitation (ebe)', NULL, 1, '134000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(41, NULL, '135000', 'Résultat opérationnel (r.o.)', NULL, 1, '135000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(42, NULL, '136000', 'Résultat financier (r.f.)', NULL, 1, '136000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(43, NULL, '137000', 'Produits des activités ordinaires (r.i.a.)', NULL, 1, '137000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(44, NULL, '138100', 'Résultat de la fusion', NULL, 1, '138100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(45, NULL, '138200', 'Résultat de l\'apport partiel d\'actif', NULL, 1, '138200', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(46, NULL, '138300', 'Résultat du fractionnement', NULL, 1, '138300', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(47, NULL, '138400', 'Résultat de liquidation', NULL, 1, '138400', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(48, NULL, '139000', 'Revenu net : perte', NULL, 1, '139000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(49, NULL, '141100', 'Subvention d\'équipement : État', NULL, 1, '141100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(50, NULL, '141200', 'Subvention d\'équipement : Régions', NULL, 1, '141200', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(51, NULL, '141300', 'Subvention d\'équipement : Départements', NULL, 1, '141300', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(52, NULL, '141400', 'Subvention d\'équipement : Municipalités et autorités publiques décentralisées', NULL, 1, '141400', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(53, NULL, '141500', 'Subvention d\'équipement : Entités publiques ou mixtes', NULL, 1, '141500', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(54, NULL, '141600', 'Subvention d\'équipement : Entités et organisations privées', NULL, 1, '141600', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(55, NULL, '141700', 'Subvention d\'équipement : Organisations internationales', NULL, 1, '141700', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(56, NULL, '141800', 'Subvention d\'équipement : Autres', NULL, 1, '141800', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(57, NULL, '148000', 'D\'autres subventions d\'investissement', NULL, 1, '148000', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(58, NULL, '151000', 'Amortissement accéléré', NULL, 1, '151000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(59, NULL, '152000', 'Les plus-values à réinvestir', NULL, 1, '152000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(60, NULL, '153100', 'Fonds national', NULL, 1, '153100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(61, NULL, '153200', 'Prélèvement pour le budget', NULL, 1, '153200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(62, NULL, '154000', 'Provision spéciale de réévaluation', NULL, 1, '154000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(63, NULL, '155100', 'Reconstruction de gisements miniers et pétroliers', NULL, 1, '155100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(64, NULL, '156100', 'Provision réglementée relative aux stocks : Augmentation des prix', NULL, 1, '156100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(65, NULL, '156200', 'Disposition réglementée relative aux stocks : Fluctuation des prix', NULL, 1, '156200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(66, NULL, '157000', 'Provisions pour investissement', NULL, 1, '157000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(67, NULL, '158000', 'Autres dispositions réglementées et fonds', NULL, 1, '158000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(68, NULL, '161100', 'Emissions d\'obligations ordinaires', NULL, 1, '161100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(69, NULL, '161200', 'Obligations convertibles en actions', NULL, 1, '161200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(70, NULL, '161300', 'Obligations remboursables en actions', NULL, 1, '161300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(71, NULL, '161800', 'Autres émissions obligataires', NULL, 1, '161800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(72, NULL, '162000', 'Emprunts et dettes auprès des établissements de crédit', NULL, 1, '162000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(73, NULL, '163000', 'Avances reçues de l\'état', NULL, 1, '163000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(74, NULL, '164000', 'Avances reçues et comptes courants bloqués', NULL, 1, '164000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(75, NULL, '165100', 'Dépôts et garanties reçus : Dépôts', NULL, 1, '165100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(76, NULL, '165200', 'Dépôts et garanties reçus : Garanties', NULL, 1, '165200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(77, NULL, '166100', 'Intérêts courus sur les émissions obligataires', NULL, 1, '166100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(78, NULL, '166200', 'Intérêts courus sur emprunts et dettes auprès des établissements de crédit', NULL, 1, '166200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(79, NULL, '166300', 'Intérêts courus sur avances reçues de l\'Etat', NULL, 1, '166300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(80, NULL, '166400', 'Intérêts courus sur avances reçues et comptes courants bloqués', NULL, 1, '166400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(81, NULL, '166500', 'Intérêts courus sur cautions et cautionnements reçus', NULL, 1, '166500', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(82, NULL, '166700', 'Intérêts courus sur avances soumises à des conditions particulières', NULL, 1, '166700', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(83, NULL, '166800', 'Intérêts courus sur autres emprunts et dettes', NULL, 1, '166800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(84, NULL, '167100', 'Avances bloquées pour augmentation de capital', NULL, 1, '167100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(85, NULL, '167200', 'Avances conditionnelles de l\'État', NULL, 1, '167200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(86, NULL, '167300', 'Avances conditionnées aux autres agences africaines', NULL, 1, '167300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(87, NULL, '167400', 'Avances conditionnées aux organismes internationaux', NULL, 1, '167400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(88, NULL, '168100', 'Rentes viagères capitalisées pour les prêts et les dettes', NULL, 1, '168100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(89, NULL, '168200', 'Notes de fonds pour emprunts et dettes', NULL, 1, '168200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(90, NULL, '168300', 'Dettes résultant de titres empruntés', NULL, 1, '168300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(91, NULL, '168400', 'Prêts participatifs', NULL, 1, '168400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(92, NULL, '168500', 'Participation des salariés aux prêts et dettes', NULL, 1, '168500', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(93, NULL, '168600', 'Emprunts et dettes contractés avec d\'autres tiers', NULL, 1, '168600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(94, NULL, '172000', 'Dettes de location-achat / crédit-bail immobilier', NULL, 1, '172000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(95, NULL, '173000', 'Dettes de location-achat / crédit-bail mobilier', NULL, 1, '173000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(96, NULL, '174000', 'Dettes en location-achat / location-vente', NULL, 1, '174000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(97, NULL, '176200', 'Intérêts courus sur dettes de crédit-bail / crédit-bail immobilier', NULL, 1, '176200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(98, NULL, '176300', 'Intérêts courus sur les dettes de crédit-bail/crédit-bail', NULL, 1, '176300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(99, NULL, '176400', 'Intérêts courus sur les dettes de crédit-bail / location-vente', NULL, 1, '176400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(100, NULL, '176800', 'Intérêts courus sur autres dettes de location-acquisition', NULL, 1, '176800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(101, NULL, '178000', 'Autres dettes de location-achat', NULL, 1, '178000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(102, NULL, '181100', 'Dettes liées aux participations (groupe)', NULL, 1, '181100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(103, NULL, '181200', 'Dettes liées à des participations (hors groupe)', NULL, 1, '181200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(104, NULL, '182000', 'Coûts d\'obtention du contrat', NULL, 1, '182000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(105, NULL, '183000', 'Intérêts courus sur dettes rattachées à des participations', NULL, 1, '183000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(106, NULL, '184000', 'Comptes permanents bloqués des établissements et succursales', NULL, 1, '184000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(107, NULL, '185000', 'Comptes permanents non bloqués des établissements et succursales', NULL, 1, '185000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(108, NULL, '186000', 'Comptes de liaison chargés', NULL, 1, '186000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(109, NULL, '187000', 'Comptes de liaison de produits', NULL, 1, '187000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(110, NULL, '188000', 'Comptes de liaison des entreprises participantes', NULL, 1, '188000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(111, NULL, '191000', 'Les provisions pour litiges', NULL, 1, '191000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(112, NULL, '192000', 'Provision provision pour garantie pour le client', NULL, 1, '192000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(113, NULL, '193000', 'Provisions pour pertes sur les marchés d\'achèvement futurs', NULL, 1, '193000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(114, NULL, '194000', 'Provision pour perte de change', NULL, 1, '194000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(115, NULL, '195000', 'Provisions pour impôts', NULL, 1, '195000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(116, NULL, '196100', 'Provisions pour pensions et obligations similaires - engagement de retraite', NULL, 1, '196100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(117, NULL, '196200', 'Actifs du régime de retraite', NULL, 1, '196200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(118, NULL, '197000', 'Provisions pour restructuration', NULL, 1, '197000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(119, NULL, '198100', 'Provisions pour amendes et pénalités', NULL, 1, '198100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(120, NULL, '198300', 'Dispositions relatives aux auto-assureurs', NULL, 1, '198300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(121, NULL, '198400', 'Provisions pour le démantèlement et la remise en état', NULL, 1, '198400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(122, NULL, '198500', 'Dispositions relatives aux droits à réduction ou aux avantages en nature (chèques-cadeaux, cartes de fidélité, etc.)', NULL, 1, '198500', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(123, NULL, '198800', 'Provisions pour risques et charges divers', NULL, 1, '198800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(124, NULL, '211000', 'Frais de développement', NULL, 2, '211000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(125, NULL, '212100', 'Brevets', NULL, 2, '212100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(126, NULL, '212200', 'Licences', NULL, 2, '212200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(127, NULL, '212300', 'Concessions de services publics', NULL, 2, '212300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(128, NULL, '212800', 'Autres concessions et droits similaires', NULL, 2, '212800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(129, NULL, '213100', 'Logiciel', NULL, 2, '213100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(130, NULL, '213200', 'Sites web', NULL, 2, '213200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(131, NULL, '214000', 'Marques', NULL, 2, '214000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(132, NULL, '215000', 'Fonds commerciaux', NULL, 2, '215000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(133, NULL, '216000', 'Droit de louer', NULL, 2, '216000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(134, NULL, '217000', 'Investissements créatifs', NULL, 2, '217000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(135, NULL, '218100', 'Coûts d\'exploration et d\'évaluation des ressources minérales', NULL, 2, '218100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(136, NULL, '218200', 'Coûts d\'obtention du contrat', NULL, 2, '218200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(137, NULL, '218300', 'Fichiers clients, avis, titres de journaux et de magazines', NULL, 2, '218300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(138, NULL, '218400', 'Coûts de franchise', NULL, 2, '218400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(139, NULL, '218800', 'Divers droits et valeurs immatériels', NULL, 2, '218800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(140, NULL, '219100', 'Frais de développement', NULL, 2, '219100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(141, NULL, '219300', 'Logiciels et sites web', NULL, 2, '219300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(142, NULL, '219800', 'Autres droits et valeurs incorporels', NULL, 2, '219800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(143, NULL, '221100', 'Terre agricole', NULL, 2, '221100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(144, NULL, '221200', 'Terres forestières', NULL, 2, '221200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(145, NULL, '221800', 'Autres terres', NULL, 2, '221800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(146, NULL, '222100', 'Terrains à bâtir', NULL, 2, '222100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(147, NULL, '222800', 'Autres frais bancaires', NULL, 2, '222800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(148, NULL, '223100', 'Terrains bâtis pour les bâtiments industriels et agricoles', NULL, 2, '223100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(149, NULL, '223200', 'Terrains bâtis pour les bâtiments administratifs et commerciaux', NULL, 2, '223200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(150, NULL, '223400', 'Terrains bâtis pour les bâtiments affectés à d\'autres opérations professionnelles', NULL, 2, '223400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(151, NULL, '223500', 'Terrains bâtis pour les bâtiments affectés à d\'autres opérations non professionnelles', NULL, 2, '223500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(152, NULL, '223800', 'Autres terrains bâtis', NULL, 2, '223800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(153, NULL, '224100', 'Plantation d\'arbres et d\'arbustes', NULL, 2, '224100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(154, NULL, '224500', 'Améliorations du fonds', NULL, 2, '224500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(155, NULL, '224800', 'Autres travaux', NULL, 2, '224800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(156, NULL, '225100', 'Carrières', NULL, 2, '225100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(157, NULL, '226100', 'Parkings', NULL, 2, '226100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(158, NULL, '227000', 'Terres concédées', NULL, 2, '227000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(159, NULL, '228100', 'Terrain - immeubles de placement', NULL, 2, '228100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(160, NULL, '228500', 'Terrain pour le logement du personnel', NULL, 2, '228500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(161, NULL, '228600', 'Terrain à louer - acquisition', NULL, 2, '228600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(162, NULL, '228800', 'Terrains divers', NULL, 2, '228800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(163, NULL, '229100', 'Différents terrains', NULL, 2, '229100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(164, NULL, '229200', 'Terrain nu pour aménagement foncier en cours', NULL, 2, '229200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(165, NULL, '229500', 'Terrains de carrières - sous-sol pour l\'aménagement du territoire en cours', NULL, 2, '229500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(166, NULL, '229800', 'Autres terrains pour l\'aménagement en cours', NULL, 2, '229800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(167, NULL, '231100', 'Bâtiments industriels', NULL, 2, '231100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(168, NULL, '231200', 'Bâtiments agricoles', NULL, 2, '231200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(169, NULL, '231300', 'Bâtiments administratifs et commerciaux', NULL, 2, '231300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(170, NULL, '231400', 'Bâtiments utilisés pour le logement du personnel', NULL, 2, '231400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(171, NULL, '231500', 'Bâtiments - immeubles de placement', NULL, 2, '231500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(172, NULL, '231600', 'Immeubles locatifs - acquisition', NULL, 2, '231600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(173, NULL, '232100', 'Bâtiments industriels', NULL, 2, '232100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(174, NULL, '232200', 'Bâtiments agricoles', NULL, 2, '232200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(175, NULL, '232300', 'Bâtiments administratifs et commerciaux', NULL, 2, '232300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(176, NULL, '232400', 'Bâtiments affectés au logement du personnel', NULL, 2, '232400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(177, NULL, '232500', 'Bâtiments - immeubles de placement', NULL, 2, '232500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(178, NULL, '232600', 'Immeubles locatifs - acquisition', NULL, 2, '232600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(179, NULL, '233100', 'Sur le territoire', NULL, 2, '233100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(180, NULL, '233200', 'Voies ferrées', NULL, 2, '233200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(181, NULL, '233300', 'Voies navigables', NULL, 2, '233300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(182, NULL, '233400', 'Barrages, digues', NULL, 2, '233400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(183, NULL, '233500', 'Pistes d\'aérodrome', NULL, 2, '233500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(184, NULL, '233800', 'Autres travaux d\'infrastructure', NULL, 2, '233800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(185, NULL, '234100', 'Installations complexes spécialisées sur terrain propre', NULL, 2, '234100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(186, NULL, '234200', 'Installations complexes spécialisées sur terrain propre', NULL, 2, '234200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(187, NULL, '234300', 'Installations spécifiques sur terrain propre', NULL, 2, '234300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(188, NULL, '234400', 'Installations à caractère particulier sur sol tiers', NULL, 2, '234400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(189, NULL, '234500', 'Installations et équipements des bâtiments', NULL, 2, '234500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(190, NULL, '235100', 'Installations générales', NULL, 2, '235100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(191, NULL, '235800', 'Autres aménagements de bureaux', NULL, 2, '235800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(192, NULL, '237000', 'Bâtiments industriels, agricoles et commerciaux sous concession', NULL, 2, '237000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(193, NULL, '238000', 'D\'autres installations et arrangements', NULL, 2, '238000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(194, NULL, '239100', 'Bâtiments en cours de construction', NULL, 2, '239100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(195, NULL, '239200', 'Installations en cours', NULL, 2, '239200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(196, NULL, '239300', 'Travaux d\'infrastructures en cours', NULL, 2, '239300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL);
INSERT INTO `plan_comptable` (`id`, `societe_id`, `num_compte`, `libelle`, `libelle_abrege`, `classe`, `num_compte_parent`, `niveau`, `type_compte`, `type_compte_detail`, `sens_normal`, `categorie_bilan`, `est_compte_detail`, `est_compte_tiers`, `est_lettrable`, `est_rapprochable`, `est_budgetaire`, `exige_piece_jointe`, `multi_devises`, `exige_analytique`, `type_tva`, `taux_tva_defaut`, `actif`, `est_systeme`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(197, NULL, '239400', 'Equipements, aménagements et installations techniques en cours', NULL, 2, '239400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(198, NULL, '239500', 'Développements de bureaux en cours', NULL, 2, '239500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(199, NULL, '239800', 'Autres installations et aménagements en cours', NULL, 2, '239800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(200, NULL, '241100', 'Matériel industriel', NULL, 2, '241100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(201, NULL, '241200', 'Outils industriels', NULL, 2, '241200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(202, NULL, '241300', 'Matériel commercial', NULL, 2, '241300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(203, NULL, '241400', 'Outils commerciaux', NULL, 2, '241400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(204, NULL, '241600', 'Équipements et outillages industriels et commerciaux en location – acquisition', NULL, 2, '241600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(205, NULL, '242100', 'Matériel agricole', NULL, 2, '242100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(206, NULL, '242200', 'Outils agricoles', NULL, 2, '242200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(207, NULL, '242600', 'Location de matériel et d\'outils agricoles - acquisition', NULL, 2, '242600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(208, NULL, '243000', 'Matériel d\'emballage récupérable et identifiable', NULL, 2, '243000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(209, NULL, '244100', 'Fournitures de bureau', NULL, 2, '244100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(210, NULL, '244200', 'Matériel informatique', NULL, 2, '244200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(211, NULL, '244300', 'Matériel de bureau', NULL, 2, '244300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(212, NULL, '244400', 'Mobilier de bureau', NULL, 2, '244400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(213, NULL, '244500', 'Équipement et mobilier - immeubles de placement', NULL, 2, '244500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(214, NULL, '244600', 'Matériel et mobilier de location - acquisition', NULL, 2, '244600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(215, NULL, '244700', 'Équipement et mobilier pour le logement du personnel', NULL, 2, '244700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(216, NULL, '245100', 'équipement automobile', NULL, 2, '245100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(217, NULL, '245200', 'Matériel ferroviaire', NULL, 2, '245200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(218, NULL, '245300', 'Équipement pour les rivières et les lagons', NULL, 2, '245300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(219, NULL, '245400', 'Équipement naval', NULL, 2, '245400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(220, NULL, '245500', 'Matériel aérien', NULL, 2, '245500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(221, NULL, '245600', 'Location de matériel de transport - acquisition', NULL, 2, '245600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(222, NULL, '245700', 'Matériel tiré par des chevaux', NULL, 2, '245700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(223, NULL, '245800', 'Autres équipements de transport', NULL, 2, '245800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(224, NULL, '246100', 'Bétail, animaux de trait', NULL, 2, '246100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(225, NULL, '246200', 'Bétail, animaux reproducteurs', NULL, 2, '246200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(226, NULL, '246300', 'Animaux de garde', NULL, 2, '246300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(227, NULL, '246500', 'Plantations agricoles', NULL, 2, '246500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(228, NULL, '246800', 'Autres actifs biologiques', NULL, 2, '246800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(229, NULL, '247100', 'Disposition et équipement des équipements', NULL, 2, '247100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(230, NULL, '247200', 'Aménagements et aménagements des actifs biologiques', NULL, 2, '247200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(231, NULL, '247800', 'Autres équipements, équipements de matériels et actifs biologiques', NULL, 2, '247800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(232, NULL, '248100', 'Collections et œuvres d\'art', NULL, 2, '248100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(233, NULL, '248800', 'Équipements et mobilier divers', NULL, 2, '248800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(234, NULL, '249100', 'Équipements et outils industriels et commerciaux', NULL, 2, '249100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(235, NULL, '249200', 'Matériels et outillages agricoles', NULL, 2, '249200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(236, NULL, '249300', 'Matériaux d\'emballage récupérables et identifiables', NULL, 2, '249300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(237, NULL, '249400', 'Matériel et mobilier de bureau', NULL, 2, '249400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(238, NULL, '249500', 'Matériel de transport', NULL, 2, '249500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(239, NULL, '249600', 'Actifs biologiques', NULL, 2, '249600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(240, NULL, '249700', 'Agencements et installations pour les équipements et les actifs biologiques', NULL, 2, '249700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(241, NULL, '249800', 'Autres matériels et actifs biologiques', NULL, 2, '249800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(242, NULL, '251000', 'Avances et acomptes versés sur immobilisations incorporelles', NULL, 2, '251000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(243, NULL, '252000', 'avances et acomptes versés sur immobilisations corporelles', NULL, 2, '252000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(244, NULL, '261000', 'Participations dans les entités sous contrôle exclusif', NULL, 2, '261000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(245, NULL, '262000', 'Participations dans des entités contrôlées conjointement', NULL, 2, '262000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(246, NULL, '263000', 'Investissements dans des entités ayant une influence significative', NULL, 2, '263000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(247, NULL, '265000', 'Participations dans des organismes professionnels', NULL, 2, '265000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(248, NULL, '266000', 'Shares in economic interest groupings (eigs)', NULL, 2, '266000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(249, NULL, '268000', 'Autres investissements en actions', NULL, 2, '268000', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(250, NULL, '271100', 'Prêts participatifs', NULL, 2, '271100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(251, NULL, '271200', 'Prêts aux partenaires', NULL, 2, '271200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(252, NULL, '271300', 'Notes du Fonds', NULL, 2, '271300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(253, NULL, '271400', 'Créances de location-financement', NULL, 2, '271400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(254, NULL, '271500', 'Titres prêtés', NULL, 2, '271500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(255, NULL, '271800', 'Autres prêts et créances', NULL, 2, '271800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(256, NULL, '272100', 'Prêts immobiliers', NULL, 2, '272100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(257, NULL, '272200', 'Prêts mobiliers et prêts d\'installation', NULL, 2, '272200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(258, NULL, '272800', 'Autres prêts au personnel', NULL, 2, '272800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(259, NULL, '273100', 'Retenues', NULL, 2, '273100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(260, NULL, '273300', 'Fonds réglementé', NULL, 2, '273300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(261, NULL, '273400', 'Créances sur le concédant', NULL, 2, '273400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(262, NULL, '273800', 'Autres créances sur l\'État', NULL, 2, '273800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(263, NULL, '274100', 'Immobilisations de l\'activité du portefeuille (I.A.P)', NULL, 2, '274100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(264, NULL, '274200', 'Titres de participation', NULL, 2, '274200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(265, NULL, '274300', 'Certificats d\'investissement', NULL, 2, '274300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(266, NULL, '274400', 'Unités de fonds communs de placement (U.F.M.)', NULL, 2, '274400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(267, NULL, '274500', 'Obligations', NULL, 2, '274500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(268, NULL, '274600', 'Actions ou parts propres', NULL, 2, '274600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(269, NULL, '274800', 'Autres immobilisations', NULL, 2, '274800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(270, NULL, '275100', 'Cautions de loyer anticipées', NULL, 2, '275100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(271, NULL, '275200', 'Dépôts pour l\'électricité', NULL, 2, '275200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(272, NULL, '275300', 'Dépôts pour l\'eau', NULL, 2, '275300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(273, NULL, '275400', 'Dépôts pour le gaz', NULL, 2, '275400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(274, NULL, '275500', 'Dépôts pour téléphone, télex, fax', NULL, 2, '275500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(275, NULL, '275600', 'Obligations émises par l\'entité et remboursées par elle', NULL, 2, '275600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(276, NULL, '275700', 'Garanties sur d\'autres opérations', NULL, 2, '275700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(277, NULL, '275800', 'Autres dépôts et garanties', NULL, 2, '275800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(278, NULL, '276100', 'Prêts et créances non commerciales', NULL, 2, '276100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(279, NULL, '276200', 'Prêts au personnel', NULL, 2, '276200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(280, NULL, '276300', 'Créances sur l\'État', NULL, 2, '276300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(281, NULL, '276400', 'Actifs immobilisés', NULL, 2, '276400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(282, NULL, '276500', 'Dépôts et garanties versés', NULL, 2, '276500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(283, NULL, '276600', 'Créances de location-financement', NULL, 2, '276600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(284, NULL, '276700', 'Créances liées aux participations', NULL, 2, '276700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(285, NULL, '276800', 'Actifs financiers divers', NULL, 2, '276800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(286, NULL, '277100', 'Créances sur les participations (groupe)', NULL, 2, '277100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(287, NULL, '277200', 'Créances liées à des investissements (hors groupe)', NULL, 2, '277200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(288, NULL, '277300', 'Créances liées à des coentreprises', NULL, 2, '277300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(289, NULL, '277400', 'Avances aux Groupes d\'Intérêt Economique (G.I.E.)', NULL, 2, '277400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(290, NULL, '278100', 'Créances diverses du groupe', NULL, 2, '278100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(291, NULL, '278200', 'Créances diverses hors groupe', NULL, 2, '278200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(292, NULL, '278400', 'Dépôts à terme des banques', NULL, 2, '278400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(293, NULL, '278500', 'Or et métaux précieux', NULL, 2, '278500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(294, NULL, '278800', 'Autres actifs financiers', NULL, 2, '278800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(295, NULL, '281100', 'Amortissement des frais de développement', NULL, 2, '281100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(296, NULL, '281200', 'Amortissement des brevets, licences, concessions et droits similaires', NULL, 2, '281200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(297, NULL, '281300', 'Amortissement des logiciels et des sites Web', NULL, 2, '281300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(298, NULL, '281400', 'Amortissement des marques', NULL, 2, '281400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(299, NULL, '281500', 'Amortissement de l\'écart d\'acquisition', NULL, 2, '281500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(300, NULL, '281600', 'Amortissement des droits au bail', NULL, 2, '281600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(301, NULL, '281700', 'Amortissement des investissements de démarrage', NULL, 2, '281700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(302, NULL, '281800', 'Amortissement des autres droits et actifs incorporels', NULL, 2, '281800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(303, NULL, '282400', 'Amortissement des travaux de remise en état des terres', NULL, 2, '282400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(304, NULL, '283100', 'Amortissement des bâtiments industriels, agricoles, administratifs et commerciaux sur un terrain propre', NULL, 2, '283100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(305, NULL, '283200', 'Amortissement des bâtiments industriels, agricoles, administratifs et commerciaux sur des terrains non bâtis', NULL, 2, '283200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(306, NULL, '283300', 'Amortissement des travaux d\'infrastructure', NULL, 2, '283300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(307, NULL, '283400', 'Amortissement des agencements, agencements et installations techniques', NULL, 2, '283400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(308, NULL, '283500', 'Amortissement des équipements de bureau', NULL, 2, '283500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(309, NULL, '283700', 'Amortissement des bâtiments industriels, agricoles et commerciaux sous concession', NULL, 2, '283700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(310, NULL, '283800', 'Amortissement des autres installations et équipements', NULL, 2, '283800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(311, NULL, '284100', 'Amortissement des équipements et outils industriels et commerciaux', NULL, 2, '284100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(312, NULL, '284200', 'Amortissement des terres agricoles et forestières', NULL, 2, '284200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(313, NULL, '284300', 'Amortissement du matériel d\'emballage récupérable et identifiable', NULL, 2, '284300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(314, NULL, '284400', 'Amortissement des équipements et du mobilier', NULL, 2, '284400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(315, NULL, '284500', 'Amortissement du matériel de transport', NULL, 2, '284500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(316, NULL, '284600', 'Dépréciation des actifs biologiques', NULL, 2, '284600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(317, NULL, '284700', 'Amortissement des agencements, agencements, équipements et actifs biologiques', NULL, 2, '284700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(318, NULL, '284800', 'Amortissement des autres équipements', NULL, 2, '284800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(319, NULL, '291100', 'Dépréciation des frais de développement', NULL, 2, '291100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(320, NULL, '291200', 'Atteinte à des brevets, licences, concessions et droits similaires', NULL, 2, '291200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(321, NULL, '291300', 'Dépréciation de logiciels et de sites Web', NULL, 2, '291300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(322, NULL, '291400', 'Dépréciation de marques', NULL, 2, '291400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(323, NULL, '291500', 'Dépréciation de l\'écart d\'acquisition', NULL, 2, '291500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(324, NULL, '291600', 'Dépréciation des droits de location', NULL, 2, '291600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(325, NULL, '291700', 'Dépréciation des investissements de démarrage', NULL, 2, '291700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(326, NULL, '291800', 'Dépréciation d\'autres droits et actifs incorporels', NULL, 2, '291800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(327, NULL, '291900', 'Dépréciation des immobilisations incorporelles en cours', NULL, 2, '291900', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(328, NULL, '292100', 'Amortissement des terres agricoles et forestières', NULL, 2, '292100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(329, NULL, '292200', 'Amortissement des terrains nus', NULL, 2, '292200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(330, NULL, '292300', 'Dépréciation des terrains bâtis', NULL, 2, '292300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(331, NULL, '292400', 'Amortissement des travaux de remise en état des terres', NULL, 2, '292400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(332, NULL, '292500', 'Amortissement du terrain de la carrière', NULL, 2, '292500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(333, NULL, '292600', 'Dépréciation des terrains bâtis', NULL, 2, '292600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(334, NULL, '292700', 'Dépréciation des terrains en concession', NULL, 2, '292700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(335, NULL, '292800', 'Dépréciation d\'autres terrains', NULL, 2, '292800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(336, NULL, '292900', 'Dépréciation des terrains en cours', NULL, 2, '292900', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(337, NULL, '293100', 'Amortissement des bâtiments industriels, agricoles, administratifs et commerciaux sur un terrain propre', NULL, 2, '293100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(338, NULL, '293200', 'Amortissement des bâtiments industriels, agricoles, administratifs et commerciaux sur des terrains appartenant à des tiers', NULL, 2, '293200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(339, NULL, '293300', 'Dépréciation des travaux d\'infrastructure', NULL, 2, '293300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(340, NULL, '293400', 'Dépréciation des agencements et agencements', NULL, 2, '293400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(341, NULL, '293500', 'Dépréciation des agencements de bureaux', NULL, 2, '293500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(342, NULL, '293700', 'Amortissement des bâtiments industriels, agricoles et commerciaux sous concession', NULL, 2, '293700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(343, NULL, '293800', 'Dépréciation d\'autres agencements et installations', NULL, 2, '293800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(344, NULL, '293900', 'Amortissement des bâtiments et installations en cours', NULL, 2, '293900', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(345, NULL, '294100', 'Dépréciation des équipements et de l\'outillage industriels et commerciaux', NULL, 2, '294100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(346, NULL, '294200', 'Amortissement des machines et équipements agricoles', NULL, 2, '294200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(347, NULL, '294300', 'Amortissement du matériel d\'emballage récupérable et identifiable', NULL, 2, '294300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(348, NULL, '294400', 'Amortissement des équipements et du mobilier', NULL, 2, '294400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(349, NULL, '294500', 'Amortissement du matériel de transport', NULL, 2, '294500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(350, NULL, '294600', 'Dépréciation des actifs biologiques', NULL, 2, '294600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(351, NULL, '294700', 'Dépréciation des agencements, agencements, équipements et actifs biologiques', NULL, 2, '294700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(352, NULL, '294800', 'Dépréciation d\'autres équipements', NULL, 2, '294800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(353, NULL, '294900', 'Dépréciation des équipements en cours', NULL, 2, '294900', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(354, NULL, '295100', 'Dépréciation des avances versées sur immobilisations incorporelles', NULL, 2, '295100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(355, NULL, '295200', 'Dépréciation des avances versées sur immobilisations corporelles', NULL, 2, '295200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(356, NULL, '296100', 'Dépréciation des investissements dans les entités contrôlées exclusivement', NULL, 2, '296100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(357, NULL, '296200', 'Dépréciation des investissements dans les entités contrôlées conjointement', NULL, 2, '296200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(358, NULL, '296300', 'Dépréciation des investissements dans des entités sur lesquelles une influence significative est exercée', NULL, 2, '296300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(359, NULL, '296500', 'Dépréciation des investissements dans les organismes professionnels', NULL, 2, '296500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(360, NULL, '296600', 'Dépréciation des parts de GIE', NULL, 2, '296600', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(361, NULL, '296800', 'Dépréciation des autres investissements', NULL, 2, '296800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(362, NULL, '297100', 'Dépréciation des prêts et créances', NULL, 2, '297100', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(363, NULL, '297200', 'Dépréciation des prêts aux employés', NULL, 2, '297200', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(364, NULL, '297300', 'Dépréciation des créances sur l\'Etat', NULL, 2, '297300', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(365, NULL, '297400', 'Dépréciation des immobilisations', NULL, 2, '297400', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(366, NULL, '297500', 'Dépréciation des dépôts et cautionnements versés', NULL, 2, '297500', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(367, NULL, '297700', 'Dépréciation des créances rattachées à des participations et avances aux GIE', NULL, 2, '297700', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(368, NULL, '297800', 'Dépréciation des créances financières diverses', NULL, 2, '297800', 3, 'bilan', 'Actifs immobilisés', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(369, NULL, '311100', 'Biens A1', NULL, 3, '311100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(370, NULL, '311200', 'Marchandises A2', NULL, 3, '311200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(371, NULL, '312100', 'Marchandises B1', NULL, 3, '312100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(372, NULL, '312200', 'Biens B2', NULL, 3, '312200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(373, NULL, '313100', 'Actifs biologiques : Animaux', NULL, 3, '313100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(374, NULL, '313200', 'Actifs biologiques : Plantes', NULL, 3, '313200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(375, NULL, '318000', 'Biens hors activités ordinaires (h.a.o)', NULL, 3, '318000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(376, NULL, '321000', 'Matériaux a', NULL, 3, '321000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(377, NULL, '322000', 'Matériaux b', NULL, 3, '322000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(378, NULL, '323000', 'FOURNITURES (A,B)', NULL, 3, '323000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(379, NULL, '331000', 'Matières consommables', NULL, 3, '331000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(380, NULL, '332000', 'Fournitures d\'atelier et d\'usine', NULL, 3, '332000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(381, NULL, '333000', 'Fournitures de magasin', NULL, 3, '333000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(382, NULL, '334000', 'Fournitures de bureau', NULL, 3, '334000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(383, NULL, '335100', 'Emballage perdu', NULL, 3, '335100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(384, NULL, '335200', 'Emballage récupérable non identifiable', NULL, 3, '335200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(385, NULL, '335300', 'Emballage à usage mixte', NULL, 3, '335300', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(386, NULL, '335800', 'Autres emballages', NULL, 3, '335800', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(387, NULL, '338000', 'Autres matériaux', NULL, 3, '338000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(388, NULL, '341100', 'Produits en cours P1', NULL, 3, '341100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(389, NULL, '341200', 'Produits en cours P2', NULL, 3, '341200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(390, NULL, '342100', 'Travaux en cours T1', NULL, 3, '342100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL);
INSERT INTO `plan_comptable` (`id`, `societe_id`, `num_compte`, `libelle`, `libelle_abrege`, `classe`, `num_compte_parent`, `niveau`, `type_compte`, `type_compte_detail`, `sens_normal`, `categorie_bilan`, `est_compte_detail`, `est_compte_tiers`, `est_lettrable`, `est_rapprochable`, `est_budgetaire`, `exige_piece_jointe`, `multi_devises`, `exige_analytique`, `type_tva`, `taux_tva_defaut`, `actif`, `est_systeme`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(391, NULL, '342200', 'Travaux en cours T2', NULL, 3, '342200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(392, NULL, '343100', 'Produits intermédiaires A', NULL, 3, '343100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(393, NULL, '343200', 'Produits intermédiaires B', NULL, 3, '343200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(394, NULL, '344100', 'Produits résiduels A', NULL, 3, '344100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(395, NULL, '344200', 'Produits résiduels B', NULL, 3, '344200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(396, NULL, '345100', 'Actifs biologiques courants : Animaux', NULL, 3, '345100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(397, NULL, '345200', 'Actifs biologiques courants : Plantes', NULL, 3, '345200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(398, NULL, '351100', 'Études en cours E1', NULL, 3, '351100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(399, NULL, '351200', 'Études en cours E2', NULL, 3, '351200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(400, NULL, '352100', 'Services fournis S1', NULL, 3, '352100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(401, NULL, '352200', 'Services fournis S2', NULL, 3, '352200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(402, NULL, '361000', 'Produits finis a', NULL, 3, '361000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(403, NULL, '362000', 'Produits finis b', NULL, 3, '362000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(404, NULL, '363100', 'Produits finis : Animaux', NULL, 3, '363100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(405, NULL, '363200', 'Produits finis : Plantes', NULL, 3, '363200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(406, NULL, '363800', 'Autres stocks (activités connexes)', NULL, 3, '363800', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(407, NULL, '371100', 'Produits intermédiaires A', NULL, 3, '371100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(408, NULL, '371200', 'Produits intermédiaires B', NULL, 3, '371200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(409, NULL, '372100', 'Produits résiduels : Déchets', NULL, 3, '372100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(410, NULL, '372200', 'Produits résiduels : Ferraille', NULL, 3, '372200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(411, NULL, '372300', 'Produits résiduels : Matériaux de récupération', NULL, 3, '372300', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(412, NULL, '373100', 'Produits résiduels : Animaux', NULL, 3, '373100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(413, NULL, '373200', 'Produits résiduels : Plantes', NULL, 3, '373200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(414, NULL, '373800', 'Autres stocks (activités connexes)', NULL, 3, '373800', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(415, NULL, '381000', 'Marchandises en transit', NULL, 3, '381000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(416, NULL, '382000', 'Les matières premières et fournitures liées en cours de route', NULL, 3, '382000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(417, NULL, '383000', 'Autres fournitures en cours de route', NULL, 3, '383000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(418, NULL, '386000', 'Des produits finis en route', NULL, 3, '386000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(419, NULL, '387100', 'Stock en consignation', NULL, 3, '387100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(420, NULL, '387200', 'Stock en dépôt', NULL, 3, '387200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(421, NULL, '388000', 'Stock provenant d\'actifs mis hors service ou mis au rebut', NULL, 3, '388000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(422, NULL, '391000', 'Dépréciation des stocks de marchandises', NULL, 3, '391000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(423, NULL, '392000', 'L\'amortissement des matières premières et des fournitures connexes', NULL, 3, '392000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(424, NULL, '393000', 'Réductions de valeur sur les stocks d\'autres biens', NULL, 3, '393000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(425, NULL, '394000', 'L\'amortissement des travaux en cours', NULL, 3, '394000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(426, NULL, '395000', 'dépréciation des prestations en cours', NULL, 3, '395000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(427, NULL, '396000', 'Dépréciation du stock de produits finis', NULL, 3, '396000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(428, NULL, '397000', 'Dépréciation des stocks de produits intermédiaires et résiduels', NULL, 3, '397000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(429, NULL, '398000', 'La dépréciation des stocks en transit, en consignation ou en dépôt', NULL, 3, '398000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(430, NULL, '401100', 'Fournisseurs', NULL, 4, '401100', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(431, NULL, '401200', 'Fournisseurs, Groupe', NULL, 4, '401200', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(432, NULL, '401300', 'Sous-traitants', NULL, 4, '401300', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(433, NULL, '401600', 'Fournisseurs, réserve de propriété', NULL, 4, '401600', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(434, NULL, '401700', 'Fournisseurs, retenues', NULL, 4, '401700', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(435, NULL, '402100', 'Fournisseurs, effets à payer', NULL, 4, '402100', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(436, NULL, '402200', 'Fournisseurs - Groupe, effets à payer', NULL, 4, '402200', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(437, NULL, '402300', 'Sous-traitants, effets à payer', NULL, 4, '402300', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(438, NULL, '404100', 'Comptes créditeurs, immobilisations incorporelles', NULL, 4, '404100', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(439, NULL, '404200', 'Comptes fournisseurs, immobilisations', NULL, 4, '404200', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(440, NULL, '404600', 'Comptes créditeurs effets à payer, immobilisations incorporelles', NULL, 4, '404600', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(441, NULL, '404700', 'Comptes créditeurs effets à payer, immobilisations corporelles', NULL, 4, '404700', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(442, NULL, '408100', 'Fournisseurs', NULL, 4, '408100', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(443, NULL, '408200', 'Fournisseurs - Groupe', NULL, 4, '408200', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(444, NULL, '408300', 'Sous-traitants', NULL, 4, '408300', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(445, NULL, '408600', 'Fournisseurs, intérêts courus', NULL, 4, '408600', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(446, NULL, '409100', 'Avances et acomptes versés par les fournisseurs', NULL, 4, '409100', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(447, NULL, '409200', 'Fournisseurs - Groupe des paiements anticipés', NULL, 4, '409200', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(448, NULL, '409300', 'Fournisseurs et sous-traitants Avances et acomptes versés', NULL, 4, '409300', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(449, NULL, '409400', 'Créances des fournisseurs pour les emballages et les matériaux à retourner', NULL, 4, '409400', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(450, NULL, '409800', 'Fournisseurs, remises, rabais et autres crédits à obtenir', NULL, 4, '409800', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(451, NULL, '411100', 'Clients', NULL, 4, '411100', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(452, NULL, '411200', 'Clients - Groupe', NULL, 4, '411200', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(453, NULL, '411300', 'Clients (PoS)', NULL, 4, '411300', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(454, NULL, '411400', 'Clients, gouvernements et autorités publiques', NULL, 4, '411400', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(455, NULL, '411500', 'Clients, organisations internationales', NULL, 4, '411500', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(456, NULL, '411600', 'Clients, réserve de propriété', NULL, 4, '411600', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(457, NULL, '411700', 'Clients, retenues', NULL, 4, '411700', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(458, NULL, '411800', 'Clients, Remboursement de la taxe sur la valeur ajoutée (TVA)', NULL, 4, '411800', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(459, NULL, '412100', 'Comptes débiteurs, Effets à recevoir', NULL, 4, '412100', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(460, NULL, '412200', 'Clients - Groupe, Notes à recevoir', NULL, 4, '412200', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(461, NULL, '412400', 'Gouvernement et autorités publiques, Notes à recevoir', NULL, 4, '412400', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(462, NULL, '412500', 'Organisations internationales, Effets à recevoir', NULL, 4, '412500', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(463, NULL, '413100', 'Clients, chèques impayés', NULL, 4, '413100', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(464, NULL, '413200', 'Clients, Articles non payés', NULL, 4, '413200', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(465, NULL, '413300', 'Clients, cartes de crédit impayées', NULL, 4, '413300', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(466, NULL, '413800', 'Clients, autres articles non payés', NULL, 4, '413800', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(467, NULL, '414100', 'Débiteurs, immobilisations incorporelles', NULL, 4, '414100', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(468, NULL, '414200', 'Débiteurs, immobilisations corporelles', NULL, 4, '414200', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(469, NULL, '414600', 'Effets à recevoir, actifs incorporels', NULL, 4, '414600', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(470, NULL, '414700', 'Effets à recevoir, immobilisations corporelles', NULL, 4, '414700', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(471, NULL, '415000', 'Clients, factures impayées et escomptées', NULL, 4, '415000', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(472, NULL, '416100', 'Réclamations litigieuses', NULL, 4, '416100', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(473, NULL, '416200', 'Mauvaises dettes', NULL, 4, '416200', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(474, NULL, '418100', 'Clients, factures à émettre', NULL, 4, '418100', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(475, NULL, '418600', 'Clients, intérêts courus', NULL, 4, '418600', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(476, NULL, '419100', 'Clients, avances et dépôts reçus', NULL, 4, '419100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(477, NULL, '419200', 'Clients - Groupe, avances et dépôts reçus', NULL, 4, '419200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(478, NULL, '419400', 'Créances commerciales, dettes pour emballages et matériaux consignés', NULL, 4, '419400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(479, NULL, '419800', 'Clients, remises, rabais et autres crédits à accorder', NULL, 4, '419800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(480, NULL, '421100', 'Personnel, avances', NULL, 4, '421100', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(481, NULL, '421200', 'Personnel, acomptes', NULL, 4, '421200', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(482, NULL, '421300', 'Frais avancés et fournitures au personnel', NULL, 4, '421300', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(483, NULL, '422000', 'Personnel, rémunération due', NULL, 4, '422000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(484, NULL, '423100', 'Personnel, objections', NULL, 4, '423100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(485, NULL, '423200', 'Personnel, saisies-arrêts', NULL, 4, '423200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(486, NULL, '423300', 'Avis personnel, avis de tiers', NULL, 4, '423300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(487, NULL, '424100', 'Assistance médicale', NULL, 4, '424100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(488, NULL, '424200', 'Allocations familiales', NULL, 4, '424200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(489, NULL, '424500', 'Organisations sociales liées à l\'entité', NULL, 4, '424500', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(490, NULL, '424800', 'Autres œuvres sociales internes', NULL, 4, '424800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(491, NULL, '425100', 'Représentants du personnel', NULL, 4, '425100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(492, NULL, '425200', 'Syndicats et comités d\'entreprise', NULL, 4, '425200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(493, NULL, '425800', 'Autres représentants du personnel', NULL, 4, '425800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(494, NULL, '426100', 'Participation aux bénéfices', NULL, 4, '426100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(495, NULL, '426400', 'Participation au capital', NULL, 4, '426400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(496, NULL, '427000', 'Capital personnel', NULL, 4, '427000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(497, NULL, '428100', 'Dettes de vacances à payer', NULL, 4, '428100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(498, NULL, '428600', 'Autres charges à payer', NULL, 4, '428600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(499, NULL, '428700', 'Produits à recevoir', NULL, 4, '428700', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(500, NULL, '431100', 'Allocations familiales', NULL, 4, '431100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(501, NULL, '431200', 'Accidents du travail', NULL, 4, '431200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(502, NULL, '431300', 'Fonds de pension obligatoire', NULL, 4, '431300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(503, NULL, '431400', 'Fonds de pension facultatif', NULL, 4, '431400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(504, NULL, '431800', 'Autres cotisations sociales', NULL, 4, '431800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(505, NULL, '432000', 'Fonds de pension complémentaires', NULL, 4, '432000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(506, NULL, '433100', 'Assurance mutuelle', NULL, 4, '433100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(507, NULL, '433200', 'Assurance retraite', NULL, 4, '433200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(508, NULL, '433300', 'Assurances et organismes de santé', NULL, 4, '433300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(509, NULL, '438100', 'Charges sociales sur les bonus à payer', NULL, 4, '438100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(510, NULL, '438200', 'Charges sociales sur les congés à payer', NULL, 4, '438200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(511, NULL, '438600', 'Charges sociales sur les congés à payer', NULL, 4, '438600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(512, NULL, '438700', 'Produits à recevoir', NULL, 4, '438700', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(513, NULL, '441000', 'État, impôt sur le revenu', NULL, 4, '441000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(514, NULL, '442100', 'Taxes et redevances d\'État', NULL, 4, '442100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(515, NULL, '442200', 'Taxes pour les autorités publiques', NULL, 4, '442200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(516, NULL, '442300', 'Taxes récupérables auprès des détenteurs d\'obligations', NULL, 4, '442300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(517, NULL, '442400', 'Taxes récupérables auprès des associés', NULL, 4, '442400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(518, NULL, '442600', 'Droits de douane', NULL, 4, '442600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(519, NULL, '442800', 'Autres taxes', NULL, 4, '442800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(520, NULL, '443100', 'T.V.A. facturés sur les ventes', NULL, 4, '443100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(521, NULL, '443200', 'T.V.A. facturé sur les services fournis', NULL, 4, '443200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(522, NULL, '443300', 'T.V.A. facturé sur le travail', NULL, 4, '443300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(523, NULL, '443400', 'T.V.A. facturée sur la production livrée à domicile', NULL, 4, '443400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(524, NULL, '443500', 'T.V.A. sur les factures à émettre', NULL, 4, '443500', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(525, NULL, '444100', 'Etat, V.A.T. due', NULL, 4, '444100', 3, 'bilan', 'Fournisseur', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(526, NULL, '444500', 'Remboursement de l\'État, de la T.V.A.', NULL, 4, '444500', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(527, NULL, '444900', 'Report du crédit d\'impôt de l\'État et de la TVA', NULL, 4, '444900', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(528, NULL, '445100', 'TVA récupérable sur les immobilisations', NULL, 4, '445100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(529, NULL, '445200', 'T.V.A. recouvrable sur les achats', NULL, 4, '445200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(530, NULL, '445300', 'T.V.A. récupérable sur le transport', NULL, 4, '445300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(531, NULL, '445400', 'TVA récupérable sur les services externes et autres dépenses', NULL, 4, '445400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(532, NULL, '445500', 'T.V.A. récupérable sur les factures non reçues', NULL, 4, '445500', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(533, NULL, '445600', 'T.V.A. transférés par d\'autres entités', NULL, 4, '445600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(534, NULL, '446000', 'ÉTAT, AUTRES TAXES DE VENTE', NULL, 4, '446000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(535, NULL, '447100', 'Impôt général sur le revenu', NULL, 4, '447100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(536, NULL, '447200', 'Taxes sur les salaires', NULL, 4, '447200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(537, NULL, '447300', 'Contribution nationale', NULL, 4, '447300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(538, NULL, '447400', 'Contribution de solidarité nationale', NULL, 4, '447400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(539, NULL, '447800', 'Autres taxes et contributions', NULL, 4, '447800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(540, NULL, '448600', 'Dépenses accrues', NULL, 4, '448600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(541, NULL, '448700', 'Produits à recevoir', NULL, 4, '448700', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(542, NULL, '449100', 'État, cautionnement', NULL, 4, '449100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(543, NULL, '449200', 'Etat, avances et acomptes sur impôts', NULL, 4, '449200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(544, NULL, '449300', 'État, dotation à recevoir', NULL, 4, '449300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(545, NULL, '449400', 'État, subventions d\'investissement à recevoir', NULL, 4, '449400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(546, NULL, '449500', 'État, subventions de fonctionnement à recevoir', NULL, 4, '449500', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(547, NULL, '449600', 'Etat, subventions d\'équilibre à recevoir', NULL, 4, '449600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(548, NULL, '449700', 'État, avances sur subventions', NULL, 4, '449700', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(549, NULL, '449900', 'État, fonds réglementé provisionné', NULL, 4, '449900', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(550, NULL, '451000', 'Opérations avec les organisations africaine', NULL, 4, '451000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(551, NULL, '452000', 'Opérations avec d\'autres organisations internationales', NULL, 4, '452000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(552, NULL, '458100', 'Organisations internationales, dotations à recevoir', NULL, 4, '458100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(553, NULL, '458200', 'Organisations internationales, subventions à recevoir', NULL, 4, '458200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(554, NULL, '461100', 'Contributeurs, contributions en nature', NULL, 4, '461100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(555, NULL, '461200', 'Contributeurs, contributions en espèces', NULL, 4, '461200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(556, NULL, '461300', 'Apporteurs, appelés, capital non versé', NULL, 4, '461300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(557, NULL, '461400', 'Cotisants, compte de cotisations, opérations de restructuration (fusion...)', NULL, 4, '461400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(558, NULL, '461500', 'Apports, paiements reçus sur l\'augmentation de capital', NULL, 4, '461500', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(559, NULL, '461600', 'Contributeurs, paiements anticipés', NULL, 4, '461600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(560, NULL, '461700', 'Contributeurs défaillants', NULL, 4, '461700', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(561, NULL, '461800', 'Apporteurs, titres à échanger', NULL, 4, '461800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(562, NULL, '461900', 'Apporteurs, capital à rembourser', NULL, 4, '461900', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(563, NULL, '462100', 'Partenaires, comptes courants : Principal', NULL, 4, '462100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(564, NULL, '462600', 'Partenaires, comptes courants : Intérêts courus', NULL, 4, '462600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(565, NULL, '463100', 'Partenaires, opérations conjointes et gestion : Opérations en cours', NULL, 4, '463100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(566, NULL, '463600', 'Partenaires, coentreprises et gestion conjointe : Intérêts courus', NULL, 4, '463600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(567, NULL, '465000', 'Associates, dividends to be paid', NULL, 4, '465000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(568, NULL, '466000', 'Groupe, comptes courants', NULL, 4, '466000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(569, NULL, '467000', 'Les contributions en suspens sur le capital appelé', NULL, 4, '467000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(570, NULL, '469000', 'Entité, dividendes à recevoir', NULL, 4, '469000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(571, NULL, '471100', 'Débiteurs divers', NULL, 4, '471100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(572, NULL, '471200', 'Créanciers divers', NULL, 4, '471200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(573, NULL, '471300', 'Débiteurs et autres créanciers : Obligations', NULL, 4, '471300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(574, NULL, '471500', 'Rémunération des administrateurs non membres', NULL, 4, '471500', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(575, NULL, '471600', 'Compte d\'affacturage et de titrisation', NULL, 4, '471600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(576, NULL, '471700', 'Créances diverses - retenues', NULL, 4, '471700', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(577, NULL, '471800', 'Compte d\'apport, compte de fusion et opérations similaires', NULL, 4, '471800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(578, NULL, '471900', 'Bons de souscription d\'actions et d\'obligations', NULL, 4, '471900', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(579, NULL, '472100', 'Créances sur les ventes de titres de placement', NULL, 4, '472100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(580, NULL, '472600', 'Paiements en suspens sur les titres d\'investissement non libérés', NULL, 4, '472600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(581, NULL, '473100', 'Intermédiaires, opérations pour compte de tiers : Commettants', NULL, 4, '473100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(582, NULL, '473200', 'Intermédiaires, opérations pour compte de tiers : Mandataires', NULL, 4, '473200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(583, NULL, '473300', 'Intermédiaires, opérations pour compte de tiers : Commettants', NULL, 4, '473300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(584, NULL, '473400', 'Intermédiaires, opérations pour compte de tiers : Commissionnaires', NULL, 4, '473400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(585, NULL, '473900', 'État, collectivités locales, fonds d\'allocation globale', NULL, 4, '473900', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(586, NULL, '474600', 'Compte de répartition des charges périodiques', NULL, 4, '474600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(587, NULL, '474700', 'Compte d\'affectation des recettes périodiques', NULL, 4, '474700', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(588, NULL, '475100', 'Compte transitoire, ajustement spécial : Compte d\'actifs', NULL, 4, '475100', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL);
INSERT INTO `plan_comptable` (`id`, `societe_id`, `num_compte`, `libelle`, `libelle_abrege`, `classe`, `num_compte_parent`, `niveau`, `type_compte`, `type_compte_detail`, `sens_normal`, `categorie_bilan`, `est_compte_detail`, `est_compte_tiers`, `est_lettrable`, `est_rapprochable`, `est_budgetaire`, `exige_piece_jointe`, `multi_devises`, `exige_analytique`, `type_tva`, `taux_tva_defaut`, `actif`, `est_systeme`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(589, NULL, '475200', 'Compte transitoire, ajustement spécial : Compte de passif', NULL, 4, '475200', 3, 'bilan', 'Capitaux propres', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(590, NULL, '476000', 'Dépenses prépayées', NULL, 4, '476000', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(591, NULL, '477000', 'Revenu différé', NULL, 4, '477000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(592, NULL, '478110', 'Diminution des créances d\'exploitation', NULL, 4, '478100', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(593, NULL, '478180', 'Diminution des créances de la HAO', NULL, 4, '478100', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(594, NULL, '478200', 'Diminution des créances financières', NULL, 4, '478200', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(595, NULL, '478310', 'Augmentation des dettes d\'exploitation', NULL, 4, '478300', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(596, NULL, '478380', 'Augmentation des dettes de HAO', NULL, 4, '478300', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(597, NULL, '478400', 'Augmentation des passifs financiers', NULL, 4, '478400', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(598, NULL, '478600', 'Ecarts de valorisation des instruments de trésorerie', NULL, 4, '478600', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(599, NULL, '478800', 'Différences compensées par la couverture de change', NULL, 4, '478800', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(600, NULL, '479110', 'Augmentation des créances d\'exploitation', NULL, 4, '479100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(601, NULL, '479180', 'Augmentation des créances HAO', NULL, 4, '479100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(602, NULL, '479200', 'Augmentation des créances financières', NULL, 4, '479200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(603, NULL, '479310', 'Diminution des dettes d\'exploitation', NULL, 4, '479300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(604, NULL, '479380', 'Diminution des dettes de la HAO', NULL, 4, '479300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(605, NULL, '479400', 'Diminution des passifs financiers', NULL, 4, '479400', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(606, NULL, '479700', 'Ecarts de valorisation des instruments de trésorerie', NULL, 4, '479700', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(607, NULL, '479800', 'Différences compensées par la couverture de change', NULL, 4, '479800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(608, NULL, '481100', 'Immobilisations incorporelles', NULL, 4, '481100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(609, NULL, '481200', 'Immobilisations corporelles', NULL, 4, '481200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(610, NULL, '481300', 'Paiements en suspens sur les titres de participation et les titres non acquis', NULL, 4, '481300', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(611, NULL, '481610', 'Réserve de propriété - immobilisations incorporelles', NULL, 4, '481600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(612, NULL, '481620', 'Réserve de propriété - immobilisations corporelles', NULL, 4, '481600', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(613, NULL, '481710', 'Retenues de garantie - immobilisations incorporelles', NULL, 4, '481700', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(614, NULL, '481720', 'Retenues de garantie - immobilisations corporelles', NULL, 4, '481700', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(615, NULL, '481810', 'Factures impayées - immobilisations incorporelles', NULL, 4, '481800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(616, NULL, '481820', 'Factures impayées - immobilisations corporelles', NULL, 4, '481800', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(617, NULL, '482100', 'Immobilisations incorporelles', NULL, 4, '482100', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(618, NULL, '482200', 'Immobilisations corporelles', NULL, 4, '482200', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(619, NULL, '484000', 'Autres passifs non liés aux activités ordinaires (h.a.o.)', NULL, 4, '484000', 3, 'bilan', 'Dettes à court terme', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(620, NULL, '485100', 'En compte, immobilisations incorporelles', NULL, 4, '485100', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(621, NULL, '485200', 'En compte, les immobilisations corporelles', NULL, 4, '485200', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(622, NULL, '485300', 'Effets à recevoir, actifs incorporels', NULL, 4, '485300', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(623, NULL, '485400', 'Effets à recevoir, immobilisations corporelles', NULL, 4, '485400', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(624, NULL, '485500', 'Effets escomptés non encore échus', NULL, 4, '485500', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(625, NULL, '485600', 'Actifs financiers', NULL, 4, '485600', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(626, NULL, '485700', 'Retenues', NULL, 4, '485700', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(627, NULL, '485800', 'Factures à émettre', NULL, 4, '485800', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(628, NULL, '488000', 'Autres créances non liées aux activités ordinaires (h.a.o.)', NULL, 4, '488000', 3, 'bilan', 'Client', 'crediteur', 'non_applicable', 1, 1, 1, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(629, NULL, '490000', 'Dépréciation des comptes créditeurs', NULL, 4, '490000', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(630, NULL, '491100', 'Réclamations litigieuses', NULL, 4, '491100', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(631, NULL, '491200', 'Créances douteuses', NULL, 4, '491200', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(632, NULL, '492000', 'Dépréciation des comptes personnels', NULL, 4, '492000', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(633, NULL, '493000', 'Dépréciation des comptes de la sécurité sociale', NULL, 4, '493000', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(634, NULL, '494000', 'Dépréciation des comptes de l\'état et des collectivités locales', NULL, 4, '494000', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(635, NULL, '495000', 'Dépréciation des comptes des organisations internationales', NULL, 4, '495000', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(636, NULL, '496200', 'Associés, comptes courants', NULL, 4, '496200', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(637, NULL, '496300', 'Partenaires, opérations conjointes et GIE', NULL, 4, '496300', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(638, NULL, '496600', 'Groupe, comptes courants', NULL, 4, '496600', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(639, NULL, '497000', 'Dépréciation des comptes débiteurs divers', NULL, 4, '497000', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(640, NULL, '498500', 'Créances sur la cession d\'actifs immobilisés', NULL, 4, '498500', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(641, NULL, '498600', 'Créances sur les ventes de titres de placement', NULL, 4, '498600', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(642, NULL, '498800', 'Autres créances H.A.O.', NULL, 4, '498800', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(643, NULL, '499100', 'Provisions pour risques à court terme sur les activités opérationnelles', NULL, 4, '499100', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(644, NULL, '499700', 'Provisions pour risques à court terme sur opérations financières', NULL, 4, '499700', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(645, NULL, '499800', 'Provisions pour risques à court terme sur les opérations H.A.O.', NULL, 4, '499800', 3, 'bilan', 'Actifs circulants', 'crediteur', 'non_applicable', 1, 1, 1, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(646, NULL, '501100', 'Titres de trésorerie à court terme', NULL, 5, '501100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(647, NULL, '501200', 'Titres d\'institutions financières', NULL, 5, '501200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(648, NULL, '501300', 'Obligations de trésorerie à court terme', NULL, 5, '501300', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(649, NULL, '501600', 'Frais d\'acquisition des titres du Trésor et des lettres de change', NULL, 5, '501600', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(650, NULL, '502100', 'Actions ou parts propres', NULL, 5, '502100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(651, NULL, '502200', 'Actions cotées', NULL, 5, '502200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(652, NULL, '502300', 'Actions non cotées', NULL, 5, '502300', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(653, NULL, '502400', 'Actions démembrées (certificats d\'investissement ; droits de vote)', NULL, 5, '502400', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(654, NULL, '502500', 'Autres actions', NULL, 5, '502500', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(655, NULL, '502600', 'Coûts d\'acquisition d\'actions', NULL, 5, '502600', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(656, NULL, '503100', 'Obligations émises par l\'entité et remboursées par elle', NULL, 5, '503100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(657, NULL, '503200', 'Obligations cotées', NULL, 5, '503200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(658, NULL, '503300', 'Obligations non cotées', NULL, 5, '503300', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(659, NULL, '503500', 'Autres obligations', NULL, 5, '503500', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(660, NULL, '503600', 'Coûts d\'acquisition des obligations', NULL, 5, '503600', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(661, NULL, '504200', 'Bons de souscription d\'actions', NULL, 5, '504200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(662, NULL, '504300', 'Mandats d\'obligations', NULL, 5, '504300', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(663, NULL, '505000', 'Titres négociables en dehors de la région', NULL, 5, '505000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(664, NULL, '506100', 'Titres du Trésor et bons à court terme', NULL, 5, '506100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(665, NULL, '506200', 'Actions', NULL, 5, '506200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(666, NULL, '506300', 'Obligations', NULL, 5, '506300', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(667, NULL, '508000', 'Autres titres d\'investissement et créances similaires', NULL, 5, '508000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(668, NULL, '510000', 'Valeurs à encaisser', NULL, 5, '510000', 3, 'bilan', 'Banque et espèces', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(669, NULL, '521001', 'Banque', NULL, 5, '521000', 3, 'bilan', 'Banque et espèces', 'debiteur', 'non_applicable', 1, 0, 0, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(670, NULL, '521002', 'Compte d\'attente de la banque', NULL, 5, '521000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(671, NULL, '521003', 'Paiements entrants en suspens', NULL, 5, '521000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(672, NULL, '521004', 'Paiements sortants en suspens', NULL, 5, '521000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(673, NULL, '531000', 'Chèques postaux', NULL, 5, '531000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(674, NULL, '532000', 'Trésorerie', NULL, 5, '532000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(675, NULL, '533000', 'Sociétés de gestion et d\'intermédiation (s.g.i.)', NULL, 5, '533000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(676, NULL, '536000', 'Institutions financières, intérêts courus', NULL, 5, '536000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(677, NULL, '538000', 'Autres organismes financiers', NULL, 5, '538000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(678, NULL, '541000', 'Options de taux d\'intérêt', NULL, 5, '541000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(679, NULL, '542000', 'Options sur le taux de change', NULL, 5, '542000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(680, NULL, '543000', 'Options sur taux d\'actions', NULL, 5, '543000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(681, NULL, '544000', 'Instruments du marché à terme', NULL, 5, '544000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(682, NULL, '545000', 'Avoirs en or et autres métaux précieux', NULL, 5, '545000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(683, NULL, '551000', 'Monnaie électronique - carte de carburant', NULL, 5, '551000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(684, NULL, '552000', 'Monnaie électronique - téléphone portable', NULL, 5, '552000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(685, NULL, '553000', 'Monnaie électronique - carte peage', NULL, 5, '553000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(686, NULL, '554000', 'Porte-monnaie électronique', NULL, 5, '554000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(687, NULL, '558000', 'Autres instruments de monnaie électronique', NULL, 5, '558000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(688, NULL, '561000', 'Crédits d\'argent', NULL, 5, '561000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(689, NULL, '564000', 'Actualisation des crédits de campagne', NULL, 5, '564000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(690, NULL, '565000', 'Actualisation des crédits de campagne', NULL, 5, '565000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(691, NULL, '566000', 'Banques, crédits de trésorerie, intérêts courus', NULL, 5, '566000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(692, NULL, '571100', 'Argent liquide en monnaie nationale', NULL, 5, '571100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(693, NULL, '571200', 'Liquidités en devises étrangères', NULL, 5, '571200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(694, NULL, '572100', 'Fonds de la branche A en monnaie nationale', NULL, 5, '572100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(695, NULL, '572200', 'Branche A en monnaie étrangère', NULL, 5, '572200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(696, NULL, '573100', 'Succursale B en monnaie nationale', NULL, 5, '573100', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(697, NULL, '573200', 'Caissier de la succursale B en monnaie étrangère', NULL, 5, '573200', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(698, NULL, '581000', 'Comptes d\'avances', NULL, 5, '581000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(699, NULL, '582000', 'Accréditifs', NULL, 5, '582000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(700, NULL, '585000', 'Transferts de fonds', NULL, 5, '585000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(701, NULL, '585001', 'Fonds en transit', NULL, 5, '585000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 1, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(702, NULL, '590000', 'Dépréciation de valeur d\'un titre d\'investissement', NULL, 5, '590000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(703, NULL, '591000', 'Dépréciation des titres et créances', NULL, 5, '591000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(704, NULL, '592000', 'Dépréciation des comptes bancaires', NULL, 5, '592000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(705, NULL, '593000', 'Dé perte de valeur d\'institutions financières et d\'un compte similaire', NULL, 5, '593000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(706, NULL, '594000', 'Dépréciation des comptes d\'instruments de trésorerie', NULL, 5, '594000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(707, NULL, '599000', 'Provisions pour risques financiers à court terme', NULL, 5, '599000', 3, 'bilan', 'Actifs circulants', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(708, NULL, '601100', 'Achats de biens dans la région', NULL, 6, '601100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(709, NULL, '601200', 'Achats de biens en dehors de la région', NULL, 6, '601200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(710, NULL, '601300', 'Achats de biens auprès d\'entités du Groupe dans la Région', NULL, 6, '601300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(711, NULL, '601400', 'Achats de biens auprès d\'entités du Groupe en dehors de la Région', NULL, 6, '601400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(712, NULL, '601500', 'Frais d\'achat de marchandises sur les achats', NULL, 6, '601500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(713, NULL, '601900', 'Rabais et remises obtenus (non ventilés)', NULL, 6, '601900', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(714, NULL, '602100', 'Achats de matières premières et de fournitures connexes dans la région', NULL, 6, '602100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(715, NULL, '602200', 'Achats de matières premières et de fournitures connexes en dehors de la Région', NULL, 6, '602200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(716, NULL, '602300', 'Achats de matières premières et de fournitures liés aux entités du groupe dans la Région', NULL, 6, '602300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(717, NULL, '602400', 'Achats de matières premières et de fournitures liés aux entités du groupe en dehors de la Région', NULL, 6, '602400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(718, NULL, '602500', 'Achats de matières premières et fournitures connexes charges sur achats', NULL, 6, '602500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(719, NULL, '602900', 'Rabais, remises et ristournes obtenus (non ventilés)', NULL, 6, '602900', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(720, NULL, '603100', 'Variation des stocks de marchandises', NULL, 6, '603100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(721, NULL, '603200', 'Variation des stocks de matières premières et fournitures connexes', NULL, 6, '603200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(722, NULL, '603300', 'Variation des stocks d\'autres fournitures', NULL, 6, '603300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(723, NULL, '604100', 'Consommables', NULL, 6, '604100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(724, NULL, '604200', 'Matériaux combustibles', NULL, 6, '604200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(725, NULL, '604300', 'Produits de soins', NULL, 6, '604300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(726, NULL, '604400', 'Fournitures d\'atelier et d\'usine', NULL, 6, '604400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(727, NULL, '604500', 'Frais sur les achats', NULL, 6, '604500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(728, NULL, '604600', 'Fournitures de magasin', NULL, 6, '604600', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(729, NULL, '604700', 'Fournitures de bureau', NULL, 6, '604700', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(730, NULL, '604900', 'Rabais, remises et ristournes obtenus (non ventilés)', NULL, 6, '604900', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(731, NULL, '605100', 'Fournitures non stockables -Eau', NULL, 6, '605100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(732, NULL, '605200', 'Fournitures non stockables - Électricité', NULL, 6, '605200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(733, NULL, '605300', 'Fournitures non stockables - Autres énergies', NULL, 6, '605300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(734, NULL, '605400', 'Fournitures d\'entretien non stockables', NULL, 6, '605400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(735, NULL, '605500', 'Fournitures de bureau non stockables', NULL, 6, '605500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(736, NULL, '605600', 'Achats de petits équipements et d\'outils', NULL, 6, '605600', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(737, NULL, '605700', 'Achats d\'études et prestations de services', NULL, 6, '605700', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(738, NULL, '605800', 'Achats de travaux, de matériaux et d\'équipements', NULL, 6, '605800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(739, NULL, '605900', 'Rabais, remises et ristournes obtenus (non ventilés)', NULL, 6, '605900', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(740, NULL, '608100', 'Emballage à sens unique', NULL, 6, '608100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(741, NULL, '608200', 'Emballage consigné non identifiable', NULL, 6, '608200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(742, NULL, '608300', 'Emballage à usage mixte', NULL, 6, '608300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(743, NULL, '608500', 'Frais d\'achat', NULL, 6, '608500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(744, NULL, '608900', 'Rabais, remises et ristournes obtenus (non ventilés)', NULL, 6, '608900', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(745, NULL, '612000', 'Transport sur les ventes', NULL, 6, '612000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(746, NULL, '613000', 'Transport pour le compte de tiers', NULL, 6, '613000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(747, NULL, '614000', 'Transport du personnel', NULL, 6, '614000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(748, NULL, '616000', 'Transports de plis', NULL, 6, '616000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(749, NULL, '618100', 'Voyages et déplacements', NULL, 6, '618100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(750, NULL, '618200', 'Transport entre établissements ou chantiers', NULL, 6, '618200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(751, NULL, '618300', 'Transport administratif', NULL, 6, '618300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(752, NULL, '621000', 'Sous-traitance générale', NULL, 6, '621000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(753, NULL, '622100', 'Location de terrains', NULL, 6, '622100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(754, NULL, '622200', 'Location d\'immeubles', NULL, 6, '622200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(755, NULL, '622300', 'Location d\'équipements et d\'outils', NULL, 6, '622300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(756, NULL, '622400', 'Malus sur l\'emballage', NULL, 6, '622400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(757, NULL, '622500', 'Location d\'emballages', NULL, 6, '622500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(758, NULL, '622600', 'Loyers et rentes foncières', NULL, 6, '622600', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(759, NULL, '622800', 'Loyers divers et charges locatives', NULL, 6, '622800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(760, NULL, '623200', 'Location de biens immobiliers', NULL, 6, '623200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(761, NULL, '623300', 'Location de meubles', NULL, 6, '623300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(762, NULL, '623400', 'Location vente', NULL, 6, '623400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(763, NULL, '623800', 'Autres contrats de location-acquisition', NULL, 6, '623800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(764, NULL, '624100', 'Entretien et réparation des propriétés', NULL, 6, '624100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(765, NULL, '624200', 'Entretien et réparation des biens meubles', NULL, 6, '624200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(766, NULL, '624300', 'Entretien', NULL, 6, '624300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(767, NULL, '624400', 'Coûts de démantèlement et de restauration', NULL, 6, '624400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(768, NULL, '624800', 'Autres entretiens et réparations', NULL, 6, '624800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(769, NULL, '625100', 'Assurance multirisque', NULL, 6, '625100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(770, NULL, '625200', 'Assurance du matériel de transport', NULL, 6, '625200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(771, NULL, '625300', 'Assurance du risque d\'exploitation', NULL, 6, '625300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(772, NULL, '625400', 'Assurance responsabilité civile des producteurs', NULL, 6, '625400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(773, NULL, '625500', 'Assurance contre l\'insolvabilité des clients', NULL, 6, '625500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(774, NULL, '625700', 'Assurance transport sur les ventes', NULL, 6, '625700', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(775, NULL, '625800', 'Autres primes d\'assurance', NULL, 6, '625800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(776, NULL, '626100', 'Études et recherches', NULL, 6, '626100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(777, NULL, '626500', 'Documentation générale', NULL, 6, '626500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(778, NULL, '626600', 'Documentation technique', NULL, 6, '626600', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(779, NULL, '627100', 'Annonces, encarts', NULL, 6, '627100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(780, NULL, '627200', 'Catalogues, imprimés publicitaires', NULL, 6, '627200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(781, NULL, '627300', 'Échantillons', NULL, 6, '627300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(782, NULL, '627400', 'Foires et expositions', NULL, 6, '627400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(783, NULL, '627500', 'Publications', NULL, 6, '627500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(784, NULL, '627600', 'Cadeaux aux clients', NULL, 6, '627600', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(785, NULL, '627700', 'Coûts des symposiums, séminaires, conférences', NULL, 6, '627700', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(786, NULL, '627800', 'Autres dépenses de publicité et de relations publiques', NULL, 6, '627800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(787, NULL, '628100', 'Frais de téléphone', NULL, 6, '628100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(788, NULL, '628200', 'Frais de télex', NULL, 6, '628200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL);
INSERT INTO `plan_comptable` (`id`, `societe_id`, `num_compte`, `libelle`, `libelle_abrege`, `classe`, `num_compte_parent`, `niveau`, `type_compte`, `type_compte_detail`, `sens_normal`, `categorie_bilan`, `est_compte_detail`, `est_compte_tiers`, `est_lettrable`, `est_rapprochable`, `est_budgetaire`, `exige_piece_jointe`, `multi_devises`, `exige_analytique`, `type_tva`, `taux_tva_defaut`, `actif`, `est_systeme`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(789, NULL, '628300', 'Frais de télécopie', NULL, 6, '628300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(790, NULL, '628800', 'Autres frais de télécommunications', NULL, 6, '628800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(791, NULL, '631100', 'Frais sur titres (vente, garde)', NULL, 6, '631100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(792, NULL, '631200', 'Frais d\'effet de commerce', NULL, 6, '631200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(793, NULL, '631300', 'Location de coffres-forts', NULL, 6, '631300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(794, NULL, '631400', 'Commissions d\'affacturage et de titrisation', NULL, 6, '631400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(795, NULL, '631500', 'Frais de carte de crédit', NULL, 6, '631500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(796, NULL, '631600', 'Frais d\'émission d\'emprunt', NULL, 6, '631600', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(797, NULL, '631700', 'Taxes sur les instruments de monnaie électronique', NULL, 6, '631700', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(798, NULL, '631800', 'Autres frais bancaires', NULL, 6, '631800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(799, NULL, '632200', 'Commissions et courtage sur les ventes', NULL, 6, '632200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(800, NULL, '632400', 'Frais pour les professions réglementées', NULL, 6, '632400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(801, NULL, '632500', 'Frais juridiques et de contentieux', NULL, 6, '632500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(802, NULL, '632600', 'Commissions d\'affacturage et de sécurisation', NULL, 6, '632600', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(803, NULL, '632700', 'Rémunération des autres prestataires de services', NULL, 6, '632700', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(804, NULL, '632800', 'Frais divers', NULL, 6, '632800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(805, NULL, '633000', 'Les coûts de formation du personnel', NULL, 6, '633000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(806, NULL, '634200', 'Redevances pour brevets, licences', NULL, 6, '634200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(807, NULL, '634300', 'Redevances de logiciels', NULL, 6, '634300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(808, NULL, '634400', 'Redevances sur les marques', NULL, 6, '634400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(809, NULL, '634500', 'Redevances pour les sites web', NULL, 6, '634500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(810, NULL, '634600', 'Redevances pour concessions, droits et valeurs similaires', NULL, 6, '634600', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(811, NULL, '635100', 'Contributions', NULL, 6, '635100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(812, NULL, '635800', 'Diverses compétitions', NULL, 6, '635800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(813, NULL, '637100', 'Agents temporaires', NULL, 6, '637100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(814, NULL, '637200', 'Personnel détaché ou prêté à l\'entité', NULL, 6, '637200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(815, NULL, '638100', 'Frais de recrutement du personnel', NULL, 6, '638100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(816, NULL, '638200', 'Frais de déménagement', NULL, 6, '638200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(817, NULL, '638300', 'Receptions', NULL, 6, '638300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(818, NULL, '638400', 'Tâches', NULL, 6, '638400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(819, NULL, '638500', 'Charges de copropriété', NULL, 6, '638500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(820, NULL, '638800', 'Charges externes diverses', NULL, 6, '638800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(821, NULL, '641100', 'Impôts fonciers et taxes connexes', NULL, 6, '641100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(822, NULL, '641200', 'Brevets, licences et taxes connexes', NULL, 6, '641200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(823, NULL, '641300', 'Impôts sur les traitements et salaires', NULL, 6, '641300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(824, NULL, '641400', 'Taxes d\'apprentissage', NULL, 6, '641400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(825, NULL, '641500', 'La formation professionnelle se poursuit', NULL, 6, '641500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(826, NULL, '641800', 'Autres impôts et taxes directs', NULL, 6, '641800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(827, NULL, '645000', 'Taxes et taxes indirectes', NULL, 6, '645000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(828, NULL, '646100', 'Droits de mutation', NULL, 6, '646100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(829, NULL, '646200', 'Droits de timbre', NULL, 6, '646200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(830, NULL, '646300', 'Taxes sur les véhicules de société', NULL, 6, '646300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(831, NULL, '646400', 'Vignettes', NULL, 6, '646400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(832, NULL, '646800', 'Autres frais d\'inscription', NULL, 6, '646800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(833, NULL, '647100', 'Pénalités de base, impôts directs', NULL, 6, '647100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(834, NULL, '647200', 'Pénalités de base, impôts indirects', NULL, 6, '647200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(835, NULL, '647300', 'Pénalités de recouvrement, impôts directs', NULL, 6, '647300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(836, NULL, '647400', 'Pénalités de recouvrement, impôts indirects', NULL, 6, '647400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(837, NULL, '647800', 'Autres pénalités et amendes fiscales', NULL, 6, '647800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(838, NULL, '648000', 'Autres taxes et droits', NULL, 6, '648000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(839, NULL, '651100', 'Client', NULL, 6, '651100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(840, NULL, '651500', 'Autres débiteurs', NULL, 6, '651500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(841, NULL, '652100', 'Part des bénéfices transférée (comptabilité du gérant)', NULL, 6, '652100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(842, NULL, '652500', 'Pertes imputées par cession (comptabilité des associés non gérants)', NULL, 6, '652500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(843, NULL, '654100', 'Immobilisations incorporelles', NULL, 6, '654100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(844, NULL, '654200', 'Immobilisations corporelles', NULL, 6, '654200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(845, NULL, '656000', 'Perte de change sur les créances et dettes commerciales', NULL, 6, '656000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(846, NULL, '657000', 'Pénalités et amendes pénales', NULL, 6, '657000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(847, NULL, '658100', 'Indemnités de service et autres rémunérations des administrateurs', NULL, 6, '658100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(848, NULL, '658200', 'Dons', NULL, 6, '658200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(849, NULL, '658300', 'Mécénat', NULL, 6, '658300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(850, NULL, '658800', 'Autres charges diverses', NULL, 6, '658800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(851, NULL, '659100', 'Dotations aux dépréciations et provisions pour risques à court terme', NULL, 6, '659100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(852, NULL, '659300', 'Charges pour dépréciation et provisions pour risques à court terme sur les stocks', NULL, 6, '659300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(853, NULL, '659400', 'Dotations aux dépréciations et provisions pour risques à court terme sur créances', NULL, 6, '659400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(854, NULL, '659800', 'Autres charges pour dépréciation et provisions pour risques à court terme', NULL, 6, '659800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(855, NULL, '661100', 'Salaires et commissions', NULL, 6, '661100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(856, NULL, '661200', 'Primes et gratifications', NULL, 6, '661200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(857, NULL, '661300', 'Vacances payées', NULL, 6, '661300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(858, NULL, '661400', 'Indemnité de préavis, de licenciement et de recherche d\'emploi', NULL, 6, '661400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(859, NULL, '661500', 'Prestations de maladie versées aux travailleurs', NULL, 6, '661500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(860, NULL, '661600', 'Supplément familial', NULL, 6, '661600', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(861, NULL, '661700', 'Avantages en nature', NULL, 6, '661700', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(862, NULL, '661800', 'Autre rémunération directe', NULL, 6, '661800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(863, NULL, '662100', 'Salaires et commissions', NULL, 6, '662100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(864, NULL, '662200', 'Primes et gratifications', NULL, 6, '662200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(865, NULL, '662300', 'Vacances payées', NULL, 6, '662300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(866, NULL, '662400', 'Indemnité de préavis, de licenciement et de recherche d\'emploi', NULL, 6, '662400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(867, NULL, '662500', 'Prestations de maladie versées aux travailleurs', NULL, 6, '662500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(868, NULL, '662600', 'Supplément familial', NULL, 6, '662600', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(869, NULL, '662700', 'Avantages en nature', NULL, 6, '662700', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(870, NULL, '662800', 'Autre rémunération directe', NULL, 6, '662800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(871, NULL, '663100', 'Allocations de logement', NULL, 6, '663100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(872, NULL, '663200', 'Indemnités de représentation', NULL, 6, '663200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(873, NULL, '663300', 'Indemnités d\'expatriation', NULL, 6, '663300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(874, NULL, '663400', 'Indemnités de transport', NULL, 6, '663400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(875, NULL, '663800', 'Autres indemnités et prestations diverses', NULL, 6, '663800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(876, NULL, '664100', 'Charges sociales sur la rémunération du personnel national', NULL, 6, '664100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(877, NULL, '664200', 'Charges sociales sur la rémunération du personnel non national', NULL, 6, '664200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(878, NULL, '666100', 'Rémunération du travail de l\'opérateur', NULL, 6, '666100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(879, NULL, '666200', 'Charges sociales', NULL, 6, '666200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(880, NULL, '667100', 'Agents temporaires', NULL, 6, '667100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(881, NULL, '667200', 'Personnel détaché ou prêté à l\'entité', NULL, 6, '667200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(882, NULL, '668100', 'Paiements aux syndicats et aux comités d\'entreprise et d\'établissement', NULL, 6, '668100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(883, NULL, '668200', 'Paiements aux comités de santé et de sécurité', NULL, 6, '668200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(884, NULL, '668300', 'Paiements et contributions à d\'autres œuvres sociales', NULL, 6, '668300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(885, NULL, '668400', 'Médecine et pharmacie du travail', NULL, 6, '668400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(886, NULL, '668500', 'Assurances et organismes de santé', NULL, 6, '668500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(887, NULL, '668600', 'Assurance retraite et fonds de pension', NULL, 6, '668600', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(888, NULL, '668700', 'Majorations et pénalités sociales', NULL, 6, '668700', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(889, NULL, '668800', 'Charges sociales diverses', NULL, 6, '668800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(890, NULL, '671100', 'Emissions d\'obligations', NULL, 6, '671100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(891, NULL, '671200', 'Emprunts auprès des établissements de crédit', NULL, 6, '671200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(892, NULL, '671300', 'Dettes liées aux participations', NULL, 6, '671300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(893, NULL, '671400', 'Primes de remboursement des obligations', NULL, 6, '671400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(894, NULL, '672200', 'Intérêts sur les loyers provenant d\'achats d\'immobilisations/locations immobilières', NULL, 6, '672200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(895, NULL, '672300', 'Intérêts sur les loyers des contrats de location-acquisition/location d\'équipement', NULL, 6, '672300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(896, NULL, '672400', 'Intérêt pour la location-achat/location-vente', NULL, 6, '672400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(897, NULL, '672800', 'Intérêts sur les loyers d\'autres contrats de location-acquisition', NULL, 6, '672800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(898, NULL, '673000', 'Escomptes accordés', NULL, 6, '673000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(899, NULL, '674100', 'Avances reçues et acomptes à payer', NULL, 6, '674100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(900, NULL, '674200', 'Comptes courants bloqués', NULL, 6, '674200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(901, NULL, '674300', 'Intérêts sur les obligations garanties', NULL, 6, '674300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(902, NULL, '674400', 'Intérêts sur les dettes commerciales', NULL, 6, '674400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(903, NULL, '674500', 'Intérêts bancaires et de financement (escompte, etc.)', NULL, 6, '674500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(904, NULL, '674800', 'Intérêts sur dettes diverses', NULL, 6, '674800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(905, NULL, '675000', 'Les remises sur les factures commerciales', NULL, 6, '675000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(906, NULL, '676000', 'Pertes de change financières', NULL, 6, '676000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(907, NULL, '677100', 'Pertes sur la vente de titres de placement', NULL, 6, '677100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(908, NULL, '677200', 'Losses from the free allocation of shares to salaried staff and managers', NULL, 6, '677200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(909, NULL, '678100', 'Pertes et charges sur rentes viagères', NULL, 6, '678100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(910, NULL, '678200', 'Pertes et charges sur transactions financières', NULL, 6, '678200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(911, NULL, '678400', 'Pertes et charges sur instruments de trésorerie', NULL, 6, '678400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(912, NULL, '679100', 'Dotation aux amortissements et aux provisions pour risques financiers', NULL, 6, '679100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(913, NULL, '679500', 'Dotation aux amortissements et provisions sur titres de placement', NULL, 6, '679500', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(914, NULL, '679800', 'Autres charges pour dépréciation et provisions pour risques financiers à court terme', NULL, 6, '679800', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(915, NULL, '681200', 'Amortissement des immobilisations incorporelles', NULL, 6, '681200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(916, NULL, '681300', 'Amortissement des immobilisations corporelles', NULL, 6, '681300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(917, NULL, '691100', 'Dotations aux provisions pour risques et charges', NULL, 6, '691100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(918, NULL, '691300', 'Amortissement des immobilisations incorporelles', NULL, 6, '691300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(919, NULL, '691400', 'Amortissement des immobilisations corporelles', NULL, 6, '691400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(920, NULL, '697100', 'Dotations aux provisions pour risques et charges', NULL, 6, '697100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(921, NULL, '697200', 'Amortissement des immobilisations financières', NULL, 6, '697200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(922, NULL, '701100', 'Vente de marchandises dans la région', NULL, 7, '701100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(923, NULL, '701200', 'Vente de marchandises en dehors de la Région', NULL, 7, '701200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(924, NULL, '701300', 'Vente de biens à des entités du groupe dans la région', NULL, 7, '701300', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(925, NULL, '701400', 'Vente de biens à des entités du groupe en dehors de la Région', NULL, 7, '701400', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(926, NULL, '701500', 'Vente de marchandises sur Internet', NULL, 7, '701500', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(927, NULL, '701900', 'Rabais, remises, ristournes accordés (non ventilés) sur la vente de marchandises', NULL, 7, '701900', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(928, NULL, '702100', 'Ventes de produits finis dans la région', NULL, 7, '702100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(929, NULL, '702200', 'Ventes de produits finis en dehors de la région', NULL, 7, '702200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(930, NULL, '702300', 'Ventes de produits finis aux entités du groupe dans la Région', NULL, 7, '702300', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(931, NULL, '702400', 'Ventes de produits finis à des entités du groupe en dehors de la Région', NULL, 7, '702400', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(932, NULL, '702500', 'Vente en ligne de produits finis', NULL, 7, '702500', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(933, NULL, '702900', 'Rabais, remises, ristournes accordés (non ventilés) sur les ventes de produits finis', NULL, 7, '702900', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(934, NULL, '703100', 'Ventes de produits intermédiaires dans la région', NULL, 7, '703100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(935, NULL, '703200', 'Ventes de produits intermédiaires en dehors de la région', NULL, 7, '703200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(936, NULL, '703300', 'Ventes de produits intermédiaires aux entités du groupe dans la région', NULL, 7, '703300', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(937, NULL, '703400', 'Ventes de produits intermédiaires à des entités du groupe en dehors de la région', NULL, 7, '703400', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(938, NULL, '703500', 'Vente de produits intermédiaires sur Internet', NULL, 7, '703500', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(939, NULL, '703900', 'Rabais, remises, ristournes accordés (non ventilés) sur les ventes de produits intermédiaires', NULL, 7, '703900', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(940, NULL, '704100', 'Ventes de produits résiduels dans la région', NULL, 7, '704100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(941, NULL, '704200', 'Ventes de produits résiduels en dehors de la région', NULL, 7, '704200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(942, NULL, '704300', 'Ventes de produits résiduels aux entités du groupe dans la Région', NULL, 7, '704300', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(943, NULL, '704400', 'Ventes de produits résiduels à des entités du groupe en dehors de la Région', NULL, 7, '704400', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(944, NULL, '704500', 'Vente de produits résiduels sur Internet', NULL, 7, '704500', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(945, NULL, '704900', 'Rabais, remises, ristournes accordés (non ventilés) sur les ventes de produits résiduels', NULL, 7, '704900', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(946, NULL, '705100', 'Travaux facturés dans la Région', NULL, 7, '705100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(947, NULL, '705200', 'Travaux facturés en dehors de la région', NULL, 7, '705200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(948, NULL, '705300', 'Travaux facturés aux entités du groupe dans la région', NULL, 7, '705300', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(949, NULL, '705400', 'Travaux facturés aux entités du groupe en dehors de la région', NULL, 7, '705400', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(950, NULL, '705500', 'Travail facturé en ligne', NULL, 7, '705500', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(951, NULL, '705900', 'Rabais, remises, ristournes accordés (non ventilés) sur les travaux facturés', NULL, 7, '705900', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(952, NULL, '706100', 'Services vendus dans la région', NULL, 7, '706100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(953, NULL, '706200', 'Services vendus en dehors de la région', NULL, 7, '706200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(954, NULL, '706300', 'Services vendus aux entités du groupe dans la région', NULL, 7, '706300', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(955, NULL, '706400', 'Services vendus à des entités du groupe en dehors de la Région', NULL, 7, '706400', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(956, NULL, '706500', 'Services vendus sur Internet', NULL, 7, '706500', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(957, NULL, '706900', 'Rabais, remises, ristournes accordés (non ventilés) sur les services vendus', NULL, 7, '706900', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(958, NULL, '707100', 'Frais de port, perte d\'emballage et autres frais facturés', NULL, 7, '707100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(959, NULL, '707200', 'Commissions et courtage', NULL, 7, '707200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(960, NULL, '707300', 'Loyers et frais de location - financement', NULL, 7, '707300', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(961, NULL, '707400', 'Primes sur les reprises et cessions d\'emballages', NULL, 7, '707400', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(962, NULL, '707500', 'Mise à disposition de personnel', NULL, 7, '707500', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(963, NULL, '707600', 'Redevances pour brevets, logiciels, marques et droits similaires', NULL, 7, '707600', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(964, NULL, '707700', 'Services exploités dans l\'intérêt du personnel', NULL, 7, '707700', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(965, NULL, '707800', 'Autres produits accessoires', NULL, 7, '707800', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(966, NULL, '711000', 'Sur les produits d\'exportation', NULL, 7, '711000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(967, NULL, '712000', 'Sur les produits importés', NULL, 7, '712000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(968, NULL, '713000', 'Sur les produits de péréquation', NULL, 7, '713000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(969, NULL, '714000', 'Indemnites et subventions d\'exploitation (entité agricole)', NULL, 7, '714000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(970, NULL, '718100', 'Versés par l\'État et les collectivités publiques', NULL, 7, '718100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(971, NULL, '718200', 'Payé par les organisations internationales', NULL, 7, '718200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(972, NULL, '718300', 'Payé par des tiers', NULL, 7, '718300', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(973, NULL, '721000', 'Immobilisations incorporelles', NULL, 7, '721000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(974, NULL, '722100', 'Immobilisations corporelles (à l\'exclusion des actifs biologiques)', NULL, 7, '722100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(975, NULL, '722200', 'Immobilisations corporelles (actifs biologiques)', NULL, 7, '722200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(976, NULL, '724000', 'Production autoconsommée', NULL, 7, '724000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(977, NULL, '726000', 'Actifs financiers', NULL, 7, '726000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(978, NULL, '734100', 'Variation des stocks de produits en cours', NULL, 7, '734100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(979, NULL, '734200', 'Variations des stocks de travaux en cours', NULL, 7, '734200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(980, NULL, '735100', 'Changements dans les travaux en cours : Etudes en cours', NULL, 7, '735100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(981, NULL, '735200', 'Changements dans les services en cours : Services en cours', NULL, 7, '735200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(982, NULL, '736000', 'La variation des stocks de produits finis', NULL, 7, '736000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(983, NULL, '737100', 'Variation des stocks de produits intermédiaires', NULL, 7, '737100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(984, NULL, '737200', 'Variation du stock de produits résiduels', NULL, 7, '737200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(985, NULL, '751000', 'Profits on trade receivables and other debtors', NULL, 7, '751000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(986, NULL, '752100', 'Transfert de la part des pertes (comptabilité du gérant)', NULL, 7, '752100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(987, NULL, '752500', 'Bénéfices alloués par transfert (comptabilité des associés non gérants)', NULL, 7, '752500', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL);
INSERT INTO `plan_comptable` (`id`, `societe_id`, `num_compte`, `libelle`, `libelle_abrege`, `classe`, `num_compte_parent`, `niveau`, `type_compte`, `type_compte_detail`, `sens_normal`, `categorie_bilan`, `est_compte_detail`, `est_compte_tiers`, `est_lettrable`, `est_rapprochable`, `est_budgetaire`, `exige_piece_jointe`, `multi_devises`, `exige_analytique`, `type_tva`, `taux_tva_defaut`, `actif`, `est_systeme`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(988, NULL, '754100', 'Immobilisations incorporelles', NULL, 7, '754100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(989, NULL, '754200', 'Actifs immobilisés', NULL, 7, '754200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(990, NULL, '756000', 'Gains de change sur les créances et dettes commerciales', NULL, 7, '756000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(991, NULL, '758100', 'Indemnités de service et autres rémunérations des administrateurs', NULL, 7, '758100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(992, NULL, '758200', 'Indemnité d\'assurance reçue', NULL, 7, '758200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(993, NULL, '758800', 'Autres produits divers', NULL, 7, '758800', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(994, NULL, '759100', 'Reprise des dotations aux amortissements et aux provisions pour risques à court terme', NULL, 7, '759100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(995, NULL, '759300', 'Récupération des charges pour dépréciation et provisions sur stocks', NULL, 7, '759300', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(996, NULL, '759400', 'Récupération des charges pour dépréciation et provisions sur créances', NULL, 7, '759400', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(997, NULL, '759800', 'Reprise des dotations aux amortissements et provisions pour autres dotations aux amortissements et provisions pour risques d\'exploitation à court terme', NULL, 7, '759800', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(998, NULL, '771200', 'Intérêts d\'emprunt', NULL, 7, '771200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(999, NULL, '771300', 'Intérêts sur créances diverses', NULL, 7, '771300', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1000, NULL, '772100', 'Revenus des titres de participation', NULL, 7, '772100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1001, NULL, '772200', 'Revenus des autres titres immobilisés', NULL, 7, '772200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1002, NULL, '773000', 'Remises obtenues', NULL, 7, '773000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1003, NULL, '774500', 'Revenu des obligations', NULL, 7, '774500', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1004, NULL, '774600', 'Revenus des valeurs mobilières de placement', NULL, 7, '774600', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1005, NULL, '775000', 'Intérêt des loyers de location financement', NULL, 7, '775000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1006, NULL, '776000', 'Gains de change financiers', NULL, 7, '776000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1007, NULL, '777000', 'Les gains sur la cession de titres de placement', NULL, 7, '777000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1008, NULL, '778100', 'Gains sur les rentes viagères', NULL, 7, '778100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1009, NULL, '778200', 'Gains sur opérations financières', NULL, 7, '778200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1010, NULL, '778400', 'Gains sur les instruments de trésorerie', NULL, 7, '778400', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1011, NULL, '779100', 'Reprises de charges pour dépréciations et provisions à court terme sur risques financiers', NULL, 7, '779100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1012, NULL, '779500', 'Reprises de charges pour dépréciations et provisions à court terme sur titres de placement', NULL, 7, '779500', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1013, NULL, '779800', 'Reprises de charges pour dépréciations et provisions à court terme sur d\'autres charges pour dépréciations et provisions pour risques financiers à court terme', NULL, 7, '779800', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1014, NULL, '781000', 'Transferts de charges d\'exploitation', NULL, 7, '781000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1015, NULL, '787000', 'Transferts de charges financières', NULL, 7, '787000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1016, NULL, '791100', 'Reprises de provisions et amortissements d\'exploitation pour risques et charges', NULL, 7, '791100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1017, NULL, '791300', 'Reprises de provisions et dépréciation d\'exploitation des immobilisations incorporelles', NULL, 7, '791300', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1018, NULL, '791400', 'Reprises de provisions et amortissements d\'exploitation des immobilisations corporelles', NULL, 7, '791400', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1019, NULL, '797100', 'Reprises de provisions et dépréciations financières pour risques et charges', NULL, 7, '797100', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1020, NULL, '797200', 'Reprises de provisions et amortissements financiers d\'immobilisations financières', NULL, 7, '797200', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1021, NULL, '798000', 'Reprises d\'amortissements', NULL, 7, '798000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1022, NULL, '799000', 'Reversal of investment grants', NULL, 7, '799000', 3, 'gestion', 'Revenus', 'crediteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1023, NULL, '811000', 'Immobilisations incorporelles', NULL, 8, '811000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1024, NULL, '812000', 'Immobilisations corporelles', NULL, 8, '812000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1025, NULL, '816000', 'Actifs financiers', NULL, 8, '816000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1026, NULL, '821000', 'Immobilisations incorporelles', NULL, 8, '821000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1027, NULL, '822000', 'Immobilisations corporelles', NULL, 8, '822000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1028, NULL, '826000', 'Actifs financiers', NULL, 8, '826000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1029, NULL, '831000', 'H.A.O. reconnu', NULL, 8, '831000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1030, NULL, '833000', 'Les charges liées aux opérations de restructuration', NULL, 8, '833000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1031, NULL, '834000', 'Pertes sur h.a.o. créances', NULL, 8, '834000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1032, NULL, '835000', 'Dons et liberté accordés', NULL, 8, '835000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1033, NULL, '836000', 'Abandon de créances convenu', NULL, 8, '836000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1034, NULL, '837000', 'Les dépenses liées aux opérations de liquidation', NULL, 8, '837000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1035, NULL, '839000', 'Charges pour dépréciations et provisions pour risques à court terme h.a.o.', NULL, 8, '839000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1036, NULL, '841000', 'H.A.O products observed', NULL, 8, '841000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1037, NULL, '843000', 'Produits liés aux opérations de restructuration', NULL, 8, '843000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1038, NULL, '844000', 'Indemnites et subventions h.a.o. (entité agricole)', NULL, 8, '844000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1039, NULL, '845000', 'Dons et libéralités obtenus', NULL, 8, '845000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1040, NULL, '846000', 'Abandon de créances obtenu', NULL, 8, '846000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1041, NULL, '847000', 'Produits liés aux opérations de liquidation', NULL, 8, '847000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1042, NULL, '848000', 'Transferts de dépenses d\'h.a.o', NULL, 8, '848000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1043, NULL, '849000', 'Reversal of expenses for depreciations and provisions for short-term risks h.a.o.', NULL, 8, '849000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1044, NULL, '851000', 'Dotations aux provisions réglementées', NULL, 8, '851000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1045, NULL, '852000', 'Dépréciation et amortissement h.a.o.', NULL, 8, '852000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1046, NULL, '853000', 'Provisions pour dépréciation h.a.o', NULL, 8, '853000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1047, NULL, '854000', 'Dotations aux provisions pour risques et charges h.a.o.', NULL, 8, '854000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1048, NULL, '858000', 'Autres prix h.a.o.', NULL, 8, '858000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1049, NULL, '861000', 'Reprises de provisions réglementéesreprises de provisions réglementées', NULL, 8, '861000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1050, NULL, '862000', 'Reprise de l\'amortissement de l\'h.a.o.', NULL, 8, '862000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1051, NULL, '863000', 'Reprises de dépréciation h.a.o.', NULL, 8, '863000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1052, NULL, '864000', 'Récupération des provisions pour risques et charges de l\'h.a.o.', NULL, 8, '864000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1053, NULL, '868000', 'Autres h.a.o', NULL, 8, '868000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1054, NULL, '871000', 'Participation légale aux bénéfices', NULL, 8, '871000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1055, NULL, '874000', 'Participation contractuelle aux bénéfices', NULL, 8, '874000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1056, NULL, '878000', 'Autres participations', NULL, 8, '878000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1057, NULL, '881000', 'Etat', NULL, 8, '881000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1058, NULL, '884000', 'Collectivités publiques', NULL, 8, '884000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1059, NULL, '886000', 'Groupe', NULL, 8, '886000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1060, NULL, '888000', 'Autres', NULL, 8, '888000', 3, 'gestion', 'Revenus', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1061, NULL, '891100', 'Activités menées dans l\'État', NULL, 8, '891100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1062, NULL, '891200', 'Activités menées dans les autres États de la Région', NULL, 8, '891200', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1063, NULL, '891300', 'Activités réalisées en dehors de la Région', NULL, 8, '891300', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1064, NULL, '892000', 'Rappel fiscal sur les résultats précédents', NULL, 8, '892000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1065, NULL, '895000', 'Impôt minimum fixe (i.m.f.)', NULL, 8, '895000', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1066, NULL, '899100', 'Réductions', NULL, 8, '899100', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1067, NULL, '899400', 'CLIENTS, FACTURES IMPAYÉES ET ESCOMPTÉES', NULL, 8, '899400', 3, 'gestion', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1068, NULL, '901100', 'Crédits confirmés acquis', NULL, 9, '901100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1069, NULL, '901200', 'Des prêts encore à recouvrer', NULL, 9, '901200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1070, NULL, '901300', 'Facilités de financement renouvelables', NULL, 9, '901300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1071, NULL, '901400', 'Facilités d\'émission', NULL, 9, '901400', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1072, NULL, '901800', 'Autres engagements financiers obtenus', NULL, 9, '901800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1073, NULL, '902100', 'Approbations obtenues', NULL, 9, '902100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1074, NULL, '902200', 'Dépôts, garanties obtenues', NULL, 9, '902200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1075, NULL, '902300', 'Hypothèques obtenues', NULL, 9, '902300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1076, NULL, '902400', 'Articles approuvés par des tiers', NULL, 9, '902400', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1077, NULL, '902800', 'Autres garanties accordées', NULL, 9, '902800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1078, NULL, '903100', 'Achats à terme de produits de base', NULL, 9, '903100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1079, NULL, '903200', 'Achats à terme de devises', NULL, 9, '903200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1080, NULL, '903300', 'Commandes fermes des clients', NULL, 9, '903300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1081, NULL, '903800', 'Autres engagements réciproques', NULL, 9, '903800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1082, NULL, '904100', 'Abandons de créances conditionnels', NULL, 9, '904100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1083, NULL, '904300', 'Ventes avec clause de réserve de propriété', NULL, 9, '904300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1084, NULL, '904800', 'Divers engagements obtenus', NULL, 9, '904800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1085, NULL, '905100', 'Prêts accordés mais non décaissés', NULL, 9, '905100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1086, NULL, '905800', 'Autres engagements financiers accordés', NULL, 9, '905800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1087, NULL, '906100', 'Approbations accordées', NULL, 9, '906100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1088, NULL, '906200', 'Dépôts, garanties accordées', NULL, 9, '906200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1089, NULL, '906300', 'Hypothèques accordées', NULL, 9, '906300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1090, NULL, '906400', 'Éléments endossés par l\'entité', NULL, 9, '906400', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1091, NULL, '906800', 'Autres garanties accordées', NULL, 9, '906800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1092, NULL, '907100', 'Ventes à terme de produits de base', NULL, 9, '907100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1093, NULL, '907200', 'Ventes à terme de devises', NULL, 9, '907200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1094, NULL, '907300', 'Commandes fermes aux fournisseurs', NULL, 9, '907300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1095, NULL, '907800', 'Autres engagements réciproques', NULL, 9, '907800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1096, NULL, '908100', 'Annulations conditionnelles de dettes', NULL, 9, '908100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1097, NULL, '908200', 'Engagements de retraite', NULL, 9, '908200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1098, NULL, '908300', 'Achats avec clause de réserve de propriété', NULL, 9, '908300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1099, NULL, '908800', 'Engagements divers accordés', NULL, 9, '908800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1100, NULL, '911100', 'Contreparties - Crédits confirmés obtenus', NULL, 9, '911100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1101, NULL, '911200', 'Contreparties - Prêts restant à recouvrer', NULL, 9, '911200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1102, NULL, '911300', 'Contreparties - Facilités de financement renouvelables', NULL, 9, '911300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1103, NULL, '911400', 'Contreparties - Facilités d\'émission', NULL, 9, '911400', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1104, NULL, '911800', 'Contreparties - Autres engagements financiers obtenus', NULL, 9, '911800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1105, NULL, '912100', 'Contreparties - Avenants obtenus', NULL, 9, '912100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1106, NULL, '912200', 'Contreparties - Cautions, garanties obtenues', NULL, 9, '912200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1107, NULL, '912300', 'Considérations - Hypothèques obtenues', NULL, 9, '912300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1108, NULL, '912400', 'Contreparties - Articles avalisés par des tiers', NULL, 9, '912400', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1109, NULL, '912800', 'Contreparties - Autres garanties obtenues', NULL, 9, '912800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1110, NULL, '913100', 'Contreparties - Achats à terme de produits de base', NULL, 9, '913100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1111, NULL, '913200', 'Contreparties - Achats à terme de devises', NULL, 9, '913200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1112, NULL, '913300', 'Contreparties - Ordres fermes des clients', NULL, 9, '913300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1113, NULL, '913800', 'Considérations - Hypothèques obtenues', NULL, 9, '913800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1114, NULL, '914100', 'Contreparties - Abandons de créances conditionnels', NULL, 9, '914100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1115, NULL, '914300', 'Considérations - Ventes avec clause de réserve de propriété', NULL, 9, '914300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1116, NULL, '914800', 'Considérations - Divers engagements obtenus', NULL, 9, '914800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1117, NULL, '915100', 'Contreparties - Prêts accordés mais non déboursés', NULL, 9, '915100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1118, NULL, '915800', 'Contreparties - Autres engagements de financement accordés', NULL, 9, '915800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1119, NULL, '916100', 'Considérations - Garanties accordées', NULL, 9, '916100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1120, NULL, '916200', 'Contreparties - Cautions, garanties accordées', NULL, 9, '916200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1121, NULL, '916300', 'Considérations - Hypothèques accordées', NULL, 9, '916300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1122, NULL, '916400', 'Considérations - Éléments approuvés par l\'entité', NULL, 9, '916400', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1123, NULL, '916800', 'Contreparties - Autres garanties accordées', NULL, 9, '916800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1124, NULL, '917100', 'Contreparties - Ventes à terme de produits de base', NULL, 9, '917100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1125, NULL, '917200', 'Contreparties - Ventes à terme de devises', NULL, 9, '917200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1126, NULL, '917300', 'Considérations - Commandes fermes aux fournisseurs', NULL, 9, '917300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1127, NULL, '917800', 'Considérations - Hypothèques obtenues', NULL, 9, '917800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1128, NULL, '918100', 'Contreparties - Annulations conditionnelles de dettes', NULL, 9, '918100', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1129, NULL, '918200', 'Contreparties - Engagements de retraite', NULL, 9, '918200', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1130, NULL, '918300', 'Contreparties - Achats avec clause de réserve de propriété', NULL, 9, '918300', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1131, NULL, '918800', 'Considérations - Divers engagements accordés', NULL, 9, '918800', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1132, NULL, '920000', 'Comptes de réflexion', NULL, 9, '920000', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1133, NULL, '930000', 'Comptes de reclassement', NULL, 9, '930000', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1134, NULL, '940000', 'Contributeurs, capital souscrit, non appelé', NULL, 9, '940000', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1135, NULL, '950000', 'Comptes d\'actions', NULL, 9, '950000', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1136, NULL, '960000', 'Comptes d\'évolution des coûts préliminaires', NULL, 9, '960000', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1137, NULL, '970000', 'Comptes d\'écarts comptables', NULL, 9, '970000', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1138, NULL, '980000', 'Comptes de résultat', NULL, 9, '980000', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1139, NULL, '990000', 'Comptes de lien interne', NULL, 9, '990000', 3, 'hors_bilan', 'Hors bilan', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1140, NULL, '999001', 'Gain de change', NULL, 9, '999000', 3, 'hors_bilan', 'Autres produits', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1141, NULL, '999002', 'Perte de change', NULL, 9, '999000', 3, 'hors_bilan', 'Charges', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1142, NULL, '999999', 'Affectation du résultat', NULL, 9, '999900', 3, 'hors_bilan', 'Bénéfices de l\'exercice cours', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 1, NULL, '2026-05-17 16:13:11', '2026-05-17 16:13:11', NULL),
(1143, 1, '570050', 'CAISSE KAY', NULL, 5, '570000', 4, 'bilan', 'KK', 'debiteur', 'non_applicable', 1, 0, 0, 0, 0, 0, 0, 0, 'non_soumis', NULL, 1, 0, NULL, '2026-05-17 16:19:58', '2026-05-17 16:19:58', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(40) DEFAULT NULL,
  `libelle` varchar(255) NOT NULL,
  `prix_unitaire` decimal(18,2) NOT NULL DEFAULT 0.00,
  `compte_vente` varchar(20) NOT NULL DEFAULT '701100',
  `compte_achat` varchar(20) NOT NULL DEFAULT '601100',
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rapprochements_bancaires`
--

CREATE TABLE `rapprochements_bancaires` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `journal_id` bigint(20) UNSIGNED NOT NULL,
  `exercice_id` bigint(20) UNSIGNED NOT NULL,
  `date_releve` date NOT NULL,
  `solde_releve` decimal(15,2) NOT NULL,
  `solde_comptable` decimal(15,2) NOT NULL,
  `ecart` decimal(15,2) GENERATED ALWAYS AS (`solde_releve` - `solde_comptable`) VIRTUAL,
  `statut` enum('en_cours','valide','archive') NOT NULL DEFAULT 'en_cours',
  `valide_par` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'web', '2026-05-17 16:13:09', '2026-05-17 16:13:09'),
(2, 'manager', 'web', '2026-05-17 16:13:10', '2026-05-17 16:13:10'),
(3, 'super_admin', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(4, 'admin_comptable', 'web', '2026-05-19 12:17:29', '2026-05-19 12:17:29'),
(5, 'comptable', 'web', '2026-05-19 12:17:30', '2026-05-19 12:17:30'),
(6, 'caissier', 'web', '2026-05-19 12:17:30', '2026-05-19 12:17:30'),
(7, 'tresorier', 'web', '2026-05-19 12:17:30', '2026-05-19 12:17:30'),
(8, 'auditeur', 'web', '2026-05-19 12:17:30', '2026-05-19 12:17:30'),
(9, 'direction', 'web', '2026-05-19 12:17:30', '2026-05-19 12:17:30');

-- --------------------------------------------------------

--
-- Structure de la table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 2),
(3, 1),
(3, 2),
(4, 1),
(4, 2),
(5, 1),
(5, 2),
(6, 1),
(6, 2),
(7, 1),
(7, 2),
(8, 1),
(8, 2),
(9, 1),
(9, 2),
(10, 1),
(10, 2),
(11, 1),
(11, 2),
(12, 1),
(12, 2),
(13, 1),
(13, 2),
(14, 1),
(14, 2),
(15, 1),
(15, 2),
(16, 1),
(16, 2),
(17, 1),
(17, 2),
(18, 1),
(18, 2),
(19, 1),
(19, 2),
(20, 1),
(20, 2),
(21, 1),
(21, 2),
(22, 1),
(22, 2),
(23, 1),
(23, 2),
(24, 1),
(24, 2),
(25, 1),
(25, 2),
(26, 1),
(26, 2),
(27, 1),
(27, 2),
(27, 3),
(28, 1),
(28, 2),
(28, 3),
(29, 1),
(29, 2),
(29, 3),
(30, 1),
(30, 2),
(30, 3),
(31, 1),
(31, 2),
(31, 3),
(32, 1),
(32, 2),
(32, 3),
(33, 1),
(33, 2),
(33, 3),
(34, 1),
(34, 2),
(34, 3),
(35, 1),
(35, 2),
(36, 3),
(36, 4),
(36, 5),
(36, 6),
(36, 7),
(36, 8),
(36, 9),
(37, 3),
(37, 4),
(37, 5),
(37, 6),
(37, 7),
(37, 8),
(38, 3),
(38, 4),
(38, 5),
(38, 6),
(38, 7),
(39, 3),
(39, 4),
(39, 5),
(39, 6),
(39, 7),
(40, 3),
(40, 4),
(40, 5),
(40, 7),
(41, 3),
(41, 4),
(42, 3),
(42, 4),
(42, 5),
(42, 6),
(42, 7),
(42, 8),
(42, 9),
(43, 3),
(43, 4),
(43, 5),
(43, 7),
(44, 3),
(44, 4),
(44, 5),
(44, 6),
(44, 7),
(44, 8),
(44, 9),
(45, 3),
(45, 4),
(45, 6),
(45, 7),
(46, 3),
(46, 4),
(46, 7),
(47, 3),
(47, 4),
(47, 7),
(48, 3),
(48, 4),
(48, 5),
(48, 7),
(48, 8),
(48, 9),
(49, 3),
(49, 4),
(49, 5),
(49, 8),
(49, 9),
(50, 3),
(50, 4),
(50, 5),
(50, 8),
(50, 9),
(51, 3),
(51, 4),
(52, 3),
(52, 4),
(53, 3),
(53, 4),
(54, 3),
(54, 4),
(54, 5),
(54, 8),
(55, 3),
(55, 4),
(56, 3),
(56, 4),
(57, 3),
(58, 3),
(58, 4),
(58, 5),
(58, 8),
(59, 3),
(59, 4),
(60, 3),
(60, 4),
(61, 3),
(61, 4),
(61, 8),
(62, 3),
(62, 4),
(62, 5),
(62, 6),
(62, 7),
(62, 8),
(63, 3),
(63, 4),
(63, 5),
(63, 6),
(63, 7),
(64, 3),
(64, 4),
(64, 5),
(65, 3),
(65, 4),
(65, 5),
(65, 6),
(65, 7),
(66, 3),
(66, 4),
(67, 3),
(67, 4),
(67, 5),
(67, 7),
(68, 3),
(68, 4),
(68, 6),
(68, 7);

-- --------------------------------------------------------

--
-- Structure de la table `sections_analytiques`
--

CREATE TABLE `sections_analytiques` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `axe_analytique_id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `societes`
--

CREATE TABLE `societes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `raison_sociale` varchar(255) NOT NULL,
  `forme_juridique` varchar(50) DEFAULT NULL,
  `sigle` varchar(50) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) NOT NULL DEFAULT 'RDC',
  `telephone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `site_web` varchar(150) DEFAULT NULL,
  `rccm` varchar(100) DEFAULT NULL,
  `num_contribuable` varchar(100) DEFAULT NULL,
  `num_cnps` varchar(100) DEFAULT NULL,
  `regime_fiscal` varchar(50) DEFAULT NULL,
  `devise_principale` varchar(3) NOT NULL DEFAULT 'XOF',
  `logo_path` varchar(255) DEFAULT NULL,
  `statut` enum('active','inactive','suspendue') NOT NULL DEFAULT 'active',
  `parametres` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parametres`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `societes`
--

INSERT INTO `societes` (`id`, `code`, `raison_sociale`, `forme_juridique`, `sigle`, `adresse`, `ville`, `pays`, `telephone`, `email`, `site_web`, `rccm`, `num_contribuable`, `num_cnps`, `regime_fiscal`, `devise_principale`, `logo_path`, `statut`, `parametres`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '0001', 'SALAMA SARL', 'SARL', 'SALAMA', '12, Avenue de la Paix', 'Kinshasa', 'République Démocratique du Congo', '+243 000 000 000', 'demo@salama-accounting.local', NULL, 'RCCM', NULL, NULL, 'Réel normal', 'CDF', NULL, 'active', '{\"devise_affichage\":\"CDF\",\"mode_conversion\":\"origine\"}', '2026-05-17 16:13:11', '2026-05-19 11:24:07', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `taux_change`
--

CREATE TABLE `taux_change` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `devise_code` varchar(3) NOT NULL,
  `date_taux` date NOT NULL,
  `taux` decimal(18,6) NOT NULL,
  `taux_achat` decimal(18,6) DEFAULT NULL,
  `taux_vente` decimal(18,6) DEFAULT NULL,
  `source` enum('manuel','bceao','beac','banque_centrale','api_automatique') NOT NULL DEFAULT 'manuel',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `taux_change`
--

INSERT INTO `taux_change` (`id`, `societe_id`, `devise_code`, `date_taux`, `taux`, `taux_achat`, `taux_vente`, `source`, `created_at`, `updated_at`) VALUES
(1, 1, 'CDF', '2026-05-17', 2200.000000, NULL, NULL, 'manuel', '2026-05-17 17:39:28', '2026-05-17 17:39:28'),
(2, 1, 'USD', '2026-05-17', 2200.000000, NULL, NULL, 'manuel', '2026-05-17 19:33:27', '2026-05-17 19:33:27');

-- --------------------------------------------------------

--
-- Structure de la table `tiers`
--

CREATE TABLE `tiers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `nom_abrege` varchar(60) DEFAULT NULL,
  `type` enum('client','fournisseur','client_fournisseur','salarie','actionnaire','banque','organisme_social','administration','autre') NOT NULL,
  `num_compte_collectif` varchar(15) DEFAULT NULL,
  `forme_juridique` enum('personne_physique','sarl','sa','snc','sci','sas','ong','association','etat','autre') DEFAULT NULL,
  `rccm` varchar(100) DEFAULT NULL,
  `num_contribuable` varchar(100) DEFAULT NULL,
  `num_cnps` varchar(100) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `site_web` varchar(150) DEFAULT NULL,
  `contact_principal` varchar(150) DEFAULT NULL,
  `delai_paiement_jours` int(11) DEFAULT NULL,
  `mode_paiement_defaut` enum('virement','cheque','especes','mobile_money','effet','compensation','autre') DEFAULT NULL,
  `plafond_credit` decimal(15,2) DEFAULT NULL,
  `devise` varchar(3) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `bloque` tinyint(1) NOT NULL DEFAULT 0,
  `motif_blocage` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `tiers`
--

INSERT INTO `tiers` (`id`, `societe_id`, `code`, `nom`, `nom_abrege`, `type`, `num_compte_collectif`, `forme_juridique`, `rccm`, `num_contribuable`, `num_cnps`, `adresse`, `ville`, `pays`, `telephone`, `mobile`, `email`, `site_web`, `contact_principal`, `delai_paiement_jours`, `mode_paiement_defaut`, `plafond_credit`, `devise`, `actif`, `bloque`, `motif_blocage`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, '0010', 'FOURNISSEUR KAY', NULL, 'fournisseur', '401100', NULL, NULL, NULL, NULL, NULL, 'kin', NULL, '0825366520', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, '2026-05-17 18:41:15', '2026-05-17 18:41:15', NULL),
(2, 1, '0020', 'LIONNEL CLIENT', NULL, 'client', '411200', NULL, NULL, NULL, NULL, NULL, 'KIN', NULL, '0971464803', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, '2026-05-17 18:42:03', '2026-05-17 18:42:03', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'admin',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Compte Démo', 'demo@gmail.com', NULL, '$2y$10$3ONHdkAMqgcIDZmW4EPNgOi2LpMPmFOfOqoYkgKyC5l7gIU.Heg9q', 'super_admin', 'QQDrpC4GrcOMQDDQp9nUU5CTYzYiL0qZtE6p3bvbnhFV1jFiZhQBHEveTSR4', '2026-05-17 16:13:10', '2026-05-19 14:22:10'),
(2, 'Super Administrateur', 'admin@salama.cd', NULL, '$2y$10$lVPw1KgF.YDklGqERmqzlOmXlLvRJT1WCStER7vXLTIWdqH5qWr6y', 'super_admin', NULL, '2026-05-19 12:17:43', '2026-05-19 14:22:10'),
(3, 'lionnel', 'lionnelnawej11@gmail.com', NULL, '$2y$10$oFk3EScySZVKhogHU1O8pOD28PMbIn2/zsnaOX6AdqJa7HlJ41orm', 'caissier', 'tzN7vmzc8unlc3rd30KbVMeDev2aDGefOWN60A0pzrvAvRZp09SLg3YPZ7FJ', '2026-05-19 12:55:26', '2026-05-19 12:56:22');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur_societe`
--

CREATE TABLE `utilisateur_societe` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `workflow_definitions`
--

CREATE TABLE `workflow_definitions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `societe_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(40) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `type_workflow` varchar(30) NOT NULL DEFAULT 'demande_fonds',
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `est_defaut` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `workflow_definitions`
--

INSERT INTO `workflow_definitions` (`id`, `societe_id`, `code`, `libelle`, `type_workflow`, `actif`, `est_defaut`, `created_at`, `updated_at`) VALUES
(1, 1, 'df_standard', 'Demande de fonds — circuit standard', 'demande_fonds', 1, 1, '2026-05-19 14:22:08', '2026-05-19 14:22:08');

-- --------------------------------------------------------

--
-- Structure de la table `workflow_etapes`
--

CREATE TABLE `workflow_etapes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workflow_definition_id` bigint(20) UNSIGNED NOT NULL,
  `ordre` smallint(5) UNSIGNED NOT NULL,
  `code` varchar(40) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `type_etape` varchar(30) NOT NULL,
  `role_requis` varchar(255) DEFAULT NULL,
  `imputation_comptable` tinyint(1) NOT NULL DEFAULT 0,
  `execution_paiement` tinyint(1) NOT NULL DEFAULT 0,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `workflow_etapes`
--

INSERT INTO `workflow_etapes` (`id`, `workflow_definition_id`, `ordre`, `code`, `libelle`, `type_etape`, `role_requis`, `imputation_comptable`, `execution_paiement`, `actif`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'init', 'Initiateur', 'initiateur', NULL, 0, 0, 1, '2026-05-19 14:22:08', '2026-05-19 14:22:08'),
(2, 1, 2, 'compta', 'Imputation comptable', 'comptable', 'comptable', 1, 0, 1, '2026-05-19 14:22:08', '2026-05-19 14:22:08'),
(3, 1, 3, 'valid', 'Validation manager', 'validateur', 'manager', 0, 0, 1, '2026-05-19 14:22:08', '2026-05-19 14:22:08'),
(4, 1, 4, 'caisse', 'Exécution caisse', 'caissier', 'caissier', 0, 1, 1, '2026-05-19 14:22:08', '2026-05-19 14:22:08');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agents_matricule_unique` (`matricule`);

--
-- Index pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_societe_id_foreign` (`societe_id`),
  ADD KEY `audit_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  ADD KEY `audit_logs_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `audit_logs_action_index` (`action`);

--
-- Index pour la table `axes_analytiques`
--
ALTER TABLE `axes_analytiques`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `axes_analytiques_societe_id_code_unique` (`societe_id`,`code`);

--
-- Index pour la table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `budgets_societe_id_foreign` (`societe_id`),
  ADD KEY `budgets_exercice_id_foreign` (`exercice_id`),
  ADD KEY `budgets_valide_par_foreign` (`valide_par`);

--
-- Index pour la table `declarations_fiscales`
--
ALTER TABLE `declarations_fiscales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `declarations_fiscales_exercice_id_foreign` (`exercice_id`),
  ADD KEY `declarations_fiscales_etabli_par_foreign` (`etabli_par`),
  ADD KEY `declarations_fiscales_societe_id_type_periode_debut_index` (`societe_id`,`type`,`periode_debut`);

--
-- Index pour la table `demandes_fonds`
--
ALTER TABLE `demandes_fonds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `demandes_fonds_societe_id_numero_unique` (`societe_id`,`numero`),
  ADD KEY `demandes_fonds_workflow_definition_id_foreign` (`workflow_definition_id`),
  ADD KEY `demandes_fonds_workflow_etape_courante_id_foreign` (`workflow_etape_courante_id`),
  ADD KEY `demandes_fonds_demandeur_id_foreign` (`demandeur_id`),
  ADD KEY `demandes_fonds_journal_id_foreign` (`journal_id`),
  ADD KEY `demandes_fonds_ecriture_id_foreign` (`ecriture_id`);

--
-- Index pour la table `demande_fonds_historiques`
--
ALTER TABLE `demande_fonds_historiques`
  ADD PRIMARY KEY (`id`),
  ADD KEY `demande_fonds_historiques_demande_fonds_id_foreign` (`demande_fonds_id`),
  ADD KEY `demande_fonds_historiques_user_id_foreign` (`user_id`);

--
-- Index pour la table `demande_fonds_validations`
--
ALTER TABLE `demande_fonds_validations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `demande_fonds_validations_demande_fonds_id_foreign` (`demande_fonds_id`),
  ADD KEY `demande_fonds_validations_workflow_etape_id_foreign` (`workflow_etape_id`),
  ADD KEY `demande_fonds_validations_user_id_foreign` (`user_id`);

--
-- Index pour la table `devises`
--
ALTER TABLE `devises`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `devises_code_iso_unique` (`code_iso`);

--
-- Index pour la table `echeanciers`
--
ALTER TABLE `echeanciers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `echeanciers_societe_id_date_echeance_statut_index` (`societe_id`,`date_echeance`,`statut`),
  ADD KEY `echeanciers_tiers_id_statut_index` (`tiers_id`,`statut`);

--
-- Index pour la table `ecritures`
--
ALTER TABLE `ecritures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ecritures_exercice_id_foreign` (`exercice_id`),
  ADD KEY `ecritures_journal_id_foreign` (`journal_id`),
  ADD KEY `ecritures_cree_par_foreign` (`cree_par`),
  ADD KEY `ecritures_valide_par_foreign` (`valide_par`),
  ADD KEY `ecritures_modifie_par_foreign` (`modifie_par`),
  ADD KEY `ecritures_ecriture_origine_id_foreign` (`ecriture_origine_id`),
  ADD KEY `ecritures_societe_id_exercice_id_date_ecriture_index` (`societe_id`,`exercice_id`,`date_ecriture`),
  ADD KEY `ecritures_societe_id_journal_id_date_ecriture_index` (`societe_id`,`journal_id`,`date_ecriture`),
  ADD KEY `ecritures_societe_id_statut_index` (`societe_id`,`statut`),
  ADD KEY `ecritures_num_piece_index` (`num_piece`);

--
-- Index pour la table `etats_financiers`
--
ALTER TABLE `etats_financiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `etats_financiers_exercice_id_foreign` (`exercice_id`),
  ADD KEY `etats_financiers_genere_par_foreign` (`genere_par`),
  ADD KEY `etats_financiers_societe_id_exercice_id_type_index` (`societe_id`,`exercice_id`,`type`);

--
-- Index pour la table `exercices`
--
ALTER TABLE `exercices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exercices_societe_id_annee_unique` (`societe_id`,`annee`),
  ADD KEY `exercices_cloture_par_foreign` (`cloture_par`);

--
-- Index pour la table `factures`
--
ALTER TABLE `factures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `factures_societe_id_numero_unique` (`societe_id`,`numero`),
  ADD KEY `factures_exercice_id_foreign` (`exercice_id`),
  ADD KEY `factures_tiers_id_foreign` (`tiers_id`),
  ADD KEY `factures_facture_origine_id_foreign` (`facture_origine_id`),
  ADD KEY `factures_ecriture_validation_id_foreign` (`ecriture_validation_id`),
  ADD KEY `factures_cree_par_foreign` (`cree_par`),
  ADD KEY `factures_valide_par_foreign` (`valide_par`),
  ADD KEY `factures_societe_id_type_document_statut_index` (`societe_id`,`type_document`,`statut`),
  ADD KEY `factures_societe_id_date_echeance_statut_index` (`societe_id`,`date_echeance`,`statut`);

--
-- Index pour la table `facture_lignes`
--
ALTER TABLE `facture_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facture_lignes_facture_id_foreign` (`facture_id`),
  ADD KEY `facture_lignes_produit_id_foreign` (`produit_id`);

--
-- Index pour la table `imports_logs`
--
ALTER TABLE `imports_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `imports_logs_societe_id_foreign` (`societe_id`),
  ADD KEY `imports_logs_user_id_foreign` (`user_id`);

--
-- Index pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `journal_audit_societe_id_module_created_at_index` (`societe_id`,`module`,`created_at`),
  ADD KEY `journal_audit_entite_type_entite_id_index` (`entite_type`,`entite_id`),
  ADD KEY `journal_audit_user_id_index` (`user_id`);

--
-- Index pour la table `journaux`
--
ALTER TABLE `journaux`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `journaux_societe_id_code_unique` (`societe_id`,`code`),
  ADD KEY `journaux_societe_id_type_index` (`societe_id`,`type`);

--
-- Index pour la table `lettrage_groupes`
--
ALTER TABLE `lettrage_groupes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lettrage_groupes_societe_id_num_compte_lettre_unique` (`societe_id`,`num_compte`,`lettre`),
  ADD KEY `lettrage_groupes_tiers_id_foreign` (`tiers_id`),
  ADD KEY `lettrage_groupes_lettre_par_foreign` (`lettre_par`),
  ADD KEY `lettrage_groupes_lettre_index` (`lettre`);

--
-- Index pour la table `lignes_budget`
--
ALTER TABLE `lignes_budget`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lignes_budget_budget_id_foreign` (`budget_id`),
  ADD KEY `lignes_budget_compte_id_foreign` (`compte_id`),
  ADD KEY `lignes_budget_section_analytique_id_foreign` (`section_analytique_id`),
  ADD KEY `lignes_budget_num_compte_index` (`num_compte`);

--
-- Index pour la table `lignes_ecritures`
--
ALTER TABLE `lignes_ecritures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lignes_ecritures_ecriture_id_foreign` (`ecriture_id`),
  ADD KEY `lignes_ecritures_exercice_id_foreign` (`exercice_id`),
  ADD KEY `lignes_ecritures_journal_id_foreign` (`journal_id`),
  ADD KEY `lignes_ecritures_compte_id_foreign` (`compte_id`),
  ADD KEY `lignes_ecritures_lettre_par_foreign` (`lettre_par`),
  ADD KEY `lignes_ecritures_axe_analytique_id_foreign` (`axe_analytique_id`),
  ADD KEY `lignes_ecritures_section_analytique_id_foreign` (`section_analytique_id`),
  ADD KEY `lignes_ecritures_societe_id_num_compte_date_ecriture_index` (`societe_id`,`num_compte`,`date_ecriture`),
  ADD KEY `lignes_ecritures_societe_id_exercice_id_num_compte_index` (`societe_id`,`exercice_id`,`num_compte`),
  ADD KEY `lignes_ecritures_tiers_id_lettre_index` (`tiers_id`,`lettre`),
  ADD KEY `lignes_ecritures_num_compte_lettre_index` (`num_compte`,`lettre`),
  ADD KEY `lignes_ecritures_date_ecriture_index` (`date_ecriture`),
  ADD KEY `lignes_ecritures_num_compte_index` (`num_compte`),
  ADD KEY `lignes_ecritures_lettre_index` (`lettre`);

--
-- Index pour la table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `modeles_ecritures`
--
ALTER TABLE `modeles_ecritures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `modeles_ecritures_societe_id_code_unique` (`societe_id`,`code`),
  ADD KEY `modeles_ecritures_journal_id_foreign` (`journal_id`);

--
-- Index pour la table `modeles_ecritures_lignes`
--
ALTER TABLE `modeles_ecritures_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modeles_ecritures_lignes_modele_id_foreign` (`modele_id`),
  ADD KEY `modeles_ecritures_lignes_compte_id_foreign` (`compte_id`);

--
-- Index pour la table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Index pour la table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Index pour la table `notifications_compta`
--
ALTER TABLE `notifications_compta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_compta_societe_id_foreign` (`societe_id`),
  ADD KEY `notifications_compta_user_id_lue_created_at_index` (`user_id`,`lue`,`created_at`);

--
-- Index pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `paiements_societe_id_numero_unique` (`societe_id`,`numero`),
  ADD KEY `paiements_facture_id_foreign` (`facture_id`),
  ADD KEY `paiements_demande_fonds_id_foreign` (`demande_fonds_id`),
  ADD KEY `paiements_ecriture_id_foreign` (`ecriture_id`),
  ADD KEY `paiements_user_id_foreign` (`user_id`);

--
-- Index pour la table `parametres_societe`
--
ALTER TABLE `parametres_societe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parametres_societe_societe_id_cle_unique` (`societe_id`,`cle`);

--
-- Index pour la table `parametres_systeme`
--
ALTER TABLE `parametres_systeme`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parametres_systeme_cle_unique` (`cle`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Index pour la table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Index pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pieces_jointes_societe_id_foreign` (`societe_id`),
  ADD KEY `pieces_jointes_pj_able_type_pj_able_id_index` (`pj_able_type`,`pj_able_id`),
  ADD KEY `pieces_jointes_uploade_par_foreign` (`uploade_par`);

--
-- Index pour la table `plan_comptable`
--
ALTER TABLE `plan_comptable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_comptable_societe_id_num_compte_unique` (`societe_id`,`num_compte`),
  ADD KEY `plan_comptable_classe_type_compte_index` (`classe`,`type_compte`),
  ADD KEY `plan_comptable_num_compte_parent_index` (`num_compte_parent`),
  ADD KEY `plan_comptable_num_compte_index` (`num_compte`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `produits_societe_id_code_unique` (`societe_id`,`code`);

--
-- Index pour la table `rapprochements_bancaires`
--
ALTER TABLE `rapprochements_bancaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rapprochements_bancaires_societe_id_foreign` (`societe_id`),
  ADD KEY `rapprochements_bancaires_journal_id_foreign` (`journal_id`),
  ADD KEY `rapprochements_bancaires_exercice_id_foreign` (`exercice_id`),
  ADD KEY `rapprochements_bancaires_valide_par_foreign` (`valide_par`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Index pour la table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Index pour la table `sections_analytiques`
--
ALTER TABLE `sections_analytiques`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sections_analytiques_axe_analytique_id_code_unique` (`axe_analytique_id`,`code`),
  ADD KEY `sections_analytiques_societe_id_foreign` (`societe_id`),
  ADD KEY `sections_analytiques_parent_id_foreign` (`parent_id`);

--
-- Index pour la table `societes`
--
ALTER TABLE `societes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `societes_code_unique` (`code`);

--
-- Index pour la table `taux_change`
--
ALTER TABLE `taux_change`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `taux_change_societe_id_devise_code_date_taux_unique` (`societe_id`,`devise_code`,`date_taux`),
  ADD KEY `taux_change_devise_code_date_taux_index` (`devise_code`,`date_taux`),
  ADD KEY `taux_change_devise_code_index` (`devise_code`);

--
-- Index pour la table `tiers`
--
ALTER TABLE `tiers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tiers_societe_id_code_unique` (`societe_id`,`code`),
  ADD KEY `tiers_societe_id_type_index` (`societe_id`,`type`),
  ADD KEY `tiers_num_compte_collectif_index` (`num_compte_collectif`),
  ADD KEY `tiers_code_index` (`code`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Index pour la table `utilisateur_societe`
--
ALTER TABLE `utilisateur_societe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `utilisateur_societe_user_id_societe_id_unique` (`user_id`,`societe_id`),
  ADD KEY `utilisateur_societe_societe_id_foreign` (`societe_id`);

--
-- Index pour la table `workflow_definitions`
--
ALTER TABLE `workflow_definitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `workflow_definitions_societe_id_code_unique` (`societe_id`,`code`);

--
-- Index pour la table `workflow_etapes`
--
ALTER TABLE `workflow_etapes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `workflow_etapes_workflow_definition_id_ordre_unique` (`workflow_definition_id`,`ordre`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `axes_analytiques`
--
ALTER TABLE `axes_analytiques`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `declarations_fiscales`
--
ALTER TABLE `declarations_fiscales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `demandes_fonds`
--
ALTER TABLE `demandes_fonds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `demande_fonds_historiques`
--
ALTER TABLE `demande_fonds_historiques`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `demande_fonds_validations`
--
ALTER TABLE `demande_fonds_validations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `devises`
--
ALTER TABLE `devises`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `echeanciers`
--
ALTER TABLE `echeanciers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ecritures`
--
ALTER TABLE `ecritures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `etats_financiers`
--
ALTER TABLE `etats_financiers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `exercices`
--
ALTER TABLE `exercices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `factures`
--
ALTER TABLE `factures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `facture_lignes`
--
ALTER TABLE `facture_lignes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `imports_logs`
--
ALTER TABLE `imports_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `journaux`
--
ALTER TABLE `journaux`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `lettrage_groupes`
--
ALTER TABLE `lettrage_groupes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lignes_budget`
--
ALTER TABLE `lignes_budget`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lignes_ecritures`
--
ALTER TABLE `lignes_ecritures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `modeles_ecritures`
--
ALTER TABLE `modeles_ecritures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `modeles_ecritures_lignes`
--
ALTER TABLE `modeles_ecritures_lignes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications_compta`
--
ALTER TABLE `notifications_compta`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `parametres_societe`
--
ALTER TABLE `parametres_societe`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parametres_systeme`
--
ALTER TABLE `parametres_systeme`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT pour la table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `plan_comptable`
--
ALTER TABLE `plan_comptable`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1144;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rapprochements_bancaires`
--
ALTER TABLE `rapprochements_bancaires`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `sections_analytiques`
--
ALTER TABLE `sections_analytiques`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `societes`
--
ALTER TABLE `societes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `taux_change`
--
ALTER TABLE `taux_change`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `tiers`
--
ALTER TABLE `tiers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `utilisateur_societe`
--
ALTER TABLE `utilisateur_societe`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `workflow_definitions`
--
ALTER TABLE `workflow_definitions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `workflow_etapes`
--
ALTER TABLE `workflow_etapes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `axes_analytiques`
--
ALTER TABLE `axes_analytiques`
  ADD CONSTRAINT `axes_analytiques_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices` (`id`),
  ADD CONSTRAINT `budgets_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budgets_valide_par_foreign` FOREIGN KEY (`valide_par`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `declarations_fiscales`
--
ALTER TABLE `declarations_fiscales`
  ADD CONSTRAINT `declarations_fiscales_etabli_par_foreign` FOREIGN KEY (`etabli_par`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `declarations_fiscales_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices` (`id`),
  ADD CONSTRAINT `declarations_fiscales_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demandes_fonds`
--
ALTER TABLE `demandes_fonds`
  ADD CONSTRAINT `demandes_fonds_demandeur_id_foreign` FOREIGN KEY (`demandeur_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `demandes_fonds_ecriture_id_foreign` FOREIGN KEY (`ecriture_id`) REFERENCES `ecritures` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `demandes_fonds_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journaux` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `demandes_fonds_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demandes_fonds_workflow_definition_id_foreign` FOREIGN KEY (`workflow_definition_id`) REFERENCES `workflow_definitions` (`id`),
  ADD CONSTRAINT `demandes_fonds_workflow_etape_courante_id_foreign` FOREIGN KEY (`workflow_etape_courante_id`) REFERENCES `workflow_etapes` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `demande_fonds_historiques`
--
ALTER TABLE `demande_fonds_historiques`
  ADD CONSTRAINT `demande_fonds_historiques_demande_fonds_id_foreign` FOREIGN KEY (`demande_fonds_id`) REFERENCES `demandes_fonds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demande_fonds_historiques_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `demande_fonds_validations`
--
ALTER TABLE `demande_fonds_validations`
  ADD CONSTRAINT `demande_fonds_validations_demande_fonds_id_foreign` FOREIGN KEY (`demande_fonds_id`) REFERENCES `demandes_fonds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demande_fonds_validations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `demande_fonds_validations_workflow_etape_id_foreign` FOREIGN KEY (`workflow_etape_id`) REFERENCES `workflow_etapes` (`id`);

--
-- Contraintes pour la table `echeanciers`
--
ALTER TABLE `echeanciers`
  ADD CONSTRAINT `echeanciers_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `echeanciers_tiers_id_foreign` FOREIGN KEY (`tiers_id`) REFERENCES `tiers` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `ecritures`
--
ALTER TABLE `ecritures`
  ADD CONSTRAINT `ecritures_cree_par_foreign` FOREIGN KEY (`cree_par`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ecritures_ecriture_origine_id_foreign` FOREIGN KEY (`ecriture_origine_id`) REFERENCES `ecritures` (`id`),
  ADD CONSTRAINT `ecritures_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices` (`id`),
  ADD CONSTRAINT `ecritures_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journaux` (`id`),
  ADD CONSTRAINT `ecritures_modifie_par_foreign` FOREIGN KEY (`modifie_par`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ecritures_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ecritures_valide_par_foreign` FOREIGN KEY (`valide_par`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `etats_financiers`
--
ALTER TABLE `etats_financiers`
  ADD CONSTRAINT `etats_financiers_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices` (`id`),
  ADD CONSTRAINT `etats_financiers_genere_par_foreign` FOREIGN KEY (`genere_par`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `etats_financiers_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `exercices`
--
ALTER TABLE `exercices`
  ADD CONSTRAINT `exercices_cloture_par_foreign` FOREIGN KEY (`cloture_par`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `exercices_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `factures`
--
ALTER TABLE `factures`
  ADD CONSTRAINT `factures_cree_par_foreign` FOREIGN KEY (`cree_par`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `factures_ecriture_validation_id_foreign` FOREIGN KEY (`ecriture_validation_id`) REFERENCES `ecritures` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `factures_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `factures_facture_origine_id_foreign` FOREIGN KEY (`facture_origine_id`) REFERENCES `factures` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `factures_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `factures_tiers_id_foreign` FOREIGN KEY (`tiers_id`) REFERENCES `tiers` (`id`),
  ADD CONSTRAINT `factures_valide_par_foreign` FOREIGN KEY (`valide_par`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `facture_lignes`
--
ALTER TABLE `facture_lignes`
  ADD CONSTRAINT `facture_lignes_facture_id_foreign` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `facture_lignes_produit_id_foreign` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `imports_logs`
--
ALTER TABLE `imports_logs`
  ADD CONSTRAINT `imports_logs_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `imports_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  ADD CONSTRAINT `journal_audit_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `journal_audit_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `journaux`
--
ALTER TABLE `journaux`
  ADD CONSTRAINT `journaux_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lettrage_groupes`
--
ALTER TABLE `lettrage_groupes`
  ADD CONSTRAINT `lettrage_groupes_lettre_par_foreign` FOREIGN KEY (`lettre_par`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `lettrage_groupes_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lettrage_groupes_tiers_id_foreign` FOREIGN KEY (`tiers_id`) REFERENCES `tiers` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `lignes_budget`
--
ALTER TABLE `lignes_budget`
  ADD CONSTRAINT `lignes_budget_budget_id_foreign` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lignes_budget_compte_id_foreign` FOREIGN KEY (`compte_id`) REFERENCES `plan_comptable` (`id`),
  ADD CONSTRAINT `lignes_budget_section_analytique_id_foreign` FOREIGN KEY (`section_analytique_id`) REFERENCES `sections_analytiques` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `lignes_ecritures`
--
ALTER TABLE `lignes_ecritures`
  ADD CONSTRAINT `lignes_ecritures_axe_analytique_id_foreign` FOREIGN KEY (`axe_analytique_id`) REFERENCES `axes_analytiques` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lignes_ecritures_compte_id_foreign` FOREIGN KEY (`compte_id`) REFERENCES `plan_comptable` (`id`),
  ADD CONSTRAINT `lignes_ecritures_ecriture_id_foreign` FOREIGN KEY (`ecriture_id`) REFERENCES `ecritures` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lignes_ecritures_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices` (`id`),
  ADD CONSTRAINT `lignes_ecritures_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journaux` (`id`),
  ADD CONSTRAINT `lignes_ecritures_lettre_par_foreign` FOREIGN KEY (`lettre_par`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `lignes_ecritures_section_analytique_id_foreign` FOREIGN KEY (`section_analytique_id`) REFERENCES `sections_analytiques` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lignes_ecritures_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lignes_ecritures_tiers_id_foreign` FOREIGN KEY (`tiers_id`) REFERENCES `tiers` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `modeles_ecritures`
--
ALTER TABLE `modeles_ecritures`
  ADD CONSTRAINT `modeles_ecritures_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journaux` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `modeles_ecritures_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `modeles_ecritures_lignes`
--
ALTER TABLE `modeles_ecritures_lignes`
  ADD CONSTRAINT `modeles_ecritures_lignes_compte_id_foreign` FOREIGN KEY (`compte_id`) REFERENCES `plan_comptable` (`id`),
  ADD CONSTRAINT `modeles_ecritures_lignes_modele_id_foreign` FOREIGN KEY (`modele_id`) REFERENCES `modeles_ecritures` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications_compta`
--
ALTER TABLE `notifications_compta`
  ADD CONSTRAINT `notifications_compta_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_compta_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_demande_fonds_id_foreign` FOREIGN KEY (`demande_fonds_id`) REFERENCES `demandes_fonds` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `paiements_ecriture_id_foreign` FOREIGN KEY (`ecriture_id`) REFERENCES `ecritures` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `paiements_facture_id_foreign` FOREIGN KEY (`facture_id`) REFERENCES `factures` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `paiements_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paiements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `parametres_societe`
--
ALTER TABLE `parametres_societe`
  ADD CONSTRAINT `parametres_societe_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  ADD CONSTRAINT `pieces_jointes_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pieces_jointes_uploade_par_foreign` FOREIGN KEY (`uploade_par`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `plan_comptable`
--
ALTER TABLE `plan_comptable`
  ADD CONSTRAINT `plan_comptable_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `rapprochements_bancaires`
--
ALTER TABLE `rapprochements_bancaires`
  ADD CONSTRAINT `rapprochements_bancaires_exercice_id_foreign` FOREIGN KEY (`exercice_id`) REFERENCES `exercices` (`id`),
  ADD CONSTRAINT `rapprochements_bancaires_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journaux` (`id`),
  ADD CONSTRAINT `rapprochements_bancaires_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rapprochements_bancaires_valide_par_foreign` FOREIGN KEY (`valide_par`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sections_analytiques`
--
ALTER TABLE `sections_analytiques`
  ADD CONSTRAINT `sections_analytiques_axe_analytique_id_foreign` FOREIGN KEY (`axe_analytique_id`) REFERENCES `axes_analytiques` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sections_analytiques_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `sections_analytiques` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sections_analytiques_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `taux_change`
--
ALTER TABLE `taux_change`
  ADD CONSTRAINT `taux_change_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `tiers`
--
ALTER TABLE `tiers`
  ADD CONSTRAINT `tiers_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `utilisateur_societe`
--
ALTER TABLE `utilisateur_societe`
  ADD CONSTRAINT `utilisateur_societe_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `utilisateur_societe_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `workflow_definitions`
--
ALTER TABLE `workflow_definitions`
  ADD CONSTRAINT `workflow_definitions_societe_id_foreign` FOREIGN KEY (`societe_id`) REFERENCES `societes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `workflow_etapes`
--
ALTER TABLE `workflow_etapes`
  ADD CONSTRAINT `workflow_etapes_workflow_definition_id_foreign` FOREIGN KEY (`workflow_definition_id`) REFERENCES `workflow_definitions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
