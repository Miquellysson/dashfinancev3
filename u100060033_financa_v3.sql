-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 15/10/2025 às 06:15
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u100060033_financa_v3`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `billing_cases`
--

CREATE TABLE IF NOT EXISTS `billing_cases` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `responsavel_id` int(11) DEFAULT NULL,
  `origem` enum('payment','manual') NOT NULL DEFAULT 'manual',
  `origem_id` int(11) DEFAULT NULL,
  `titulo` varchar(180) NOT NULL,
  `valor_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_pendente` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('aberto','negociando','pago','cancelado') NOT NULL DEFAULT 'aberto',
  `prioridade` enum('baixa','media','alta') NOT NULL DEFAULT 'media',
  `proxima_acao_em` datetime DEFAULT NULL,
  `encerrado_em` datetime DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `billing_cases`
--

INSERT INTO `billing_cases` (`id`, `client_id`, `responsavel_id`, `origem`, `origem_id`, `titulo`, `valor_total`, `valor_pendente`, `status`, `prioridade`, `proxima_acao_em`, `encerrado_em`, `observacoes`, `created_at`, `updated_at`) VALUES
(1, 18, 2, 'payment', NULL, 'cobrança', 0.00, 0.00, 'aberto', 'alta', '2026-12-12 18:59:00', NULL, '1212', '2025-10-11 07:54:32', '2025-10-11 07:54:32'),
(2, 14, 1, 'manual', NULL, 'ssdsd', 0.00, 0.00, 'aberto', 'baixa', '2025-10-08 06:22:00', '2025-10-23 06:23:00', 'dsdsd', '2025-10-11 09:23:04', '2025-10-11 09:23:04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `billing_tasks`
--

CREATE TABLE IF NOT EXISTS `billing_tasks` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `responsavel_id` int(11) DEFAULT NULL,
  `titulo` varchar(180) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('ligacao','whatsapp','email','reuniao','outro') NOT NULL DEFAULT 'outro',
  `status` enum('pendente','em_andamento','feito','cancelado') NOT NULL DEFAULT 'pendente',
  `due_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `lembrete_minutos` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clients`
--

CREATE TABLE IF NOT EXISTS `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `clients`
--

INSERT INTO `clients` (`id`, `name`, `email`, `phone`, `entry_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'VC Business', 'marketing@arkaleads.com', '', '2025-09-24', '', '2025-09-25 00:25:23', '2025-09-25 00:25:23'),
(2, 'Voice', '', '', '2025-09-25', '', '2025-09-25 13:34:58', '2025-09-25 13:34:58'),
(3, 'Victor - Farma Facil', '', '', '2025-09-25', '', '2025-09-25 13:35:16', '2025-09-25 13:35:16'),
(4, 'Ana', '', '', '2025-09-25', '', '2025-09-25 13:35:24', '2025-09-25 13:35:24'),
(5, 'Kami', '', '', '2025-09-25', '', '2025-09-25 13:35:29', '2025-09-25 13:35:29'),
(6, 'Get Power Research', '', '', '2025-09-25', '', '2025-09-25 13:35:40', '2025-09-25 13:35:40'),
(7, 'Flex Flooring', '', '', '2025-09-25', '', '2025-09-25 13:35:50', '2025-09-25 13:35:50'),
(8, 'Rancho', '', '', '2025-09-25', '', '2025-09-25 13:35:57', '2025-09-25 13:35:57'),
(9, 'Cleaning and Care - Michael', '', '', '2025-09-25', '', '2025-09-25 13:36:08', '2025-09-25 13:36:08'),
(10, 'Gi Rozendo Cleaning', '', '', '2025-09-25', '', '2025-09-25 13:36:20', '2025-09-25 13:36:20'),
(11, 'Juliana Interprete', '', '', '2025-09-25', '', '2025-09-25 13:36:26', '2025-09-25 13:36:26'),
(12, 'Loja Grace Glamour - Shopfy', '', '', '2025-09-25', '', '2025-09-25 13:36:40', '2025-09-25 13:36:40'),
(13, 'Cacau Forte Alimentos', '', '', '2025-09-25', '', '2025-09-25 13:36:53', '2025-09-25 13:36:53'),
(14, 'AJ Carpentry', '', '', '2025-09-25', '', '2025-09-25 13:37:01', '2025-09-25 13:37:01'),
(15, 'Bruno s Stone Care', '', '', '2025-09-25', '', '2025-09-25 13:37:08', '2025-09-25 13:37:08'),
(16, '2 The Point Cleaning', 'mike@mmlins.com.br', '(82) 99666-9740', '2025-10-10', 'tesasasdasasasd', '2025-09-25 13:37:19', '2025-10-10 21:51:28'),
(17, 'Ateliê Eryn Santos', '', '', '2025-09-25', '', '2025-09-25 13:37:30', '2025-09-25 13:37:30'),
(18, 'Boston Shine Painting', '', '', '2025-09-25', '', '2025-09-25 13:37:40', '2025-09-25 13:37:40'),
(19, 'Pro Trim Carpentry', '', '', '2025-09-25', '', '2025-09-25 13:37:49', '2025-09-25 13:37:49'),
(20, 'teste', 'mike@mmlins.com.br', '(82) 99666-9740', '2025-10-10', 'site com loja virutal', '2025-10-10 21:51:45', '2025-10-10 21:51:45'),
(21, 'Mk teste', 'mike@gmail.com', '(82) 99922-2020', '2025-10-10', 'sdasasdasdasdasddasda', '2025-10-10 21:52:51', '2025-10-10 21:52:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `collection_cards`
--

CREATE TABLE IF NOT EXISTS `collection_cards` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `manual_status` enum('a_vencer','vencendo','vencido','em_cobranca','perdido') DEFAULT NULL,
  `status_since` datetime DEFAULT NULL,
  `last_contact_at` datetime DEFAULT NULL,
  `last_contact_channel` enum('email','whatsapp','sms','ligacao','outro') DEFAULT NULL,
  `last_contact_notes` varchar(255) DEFAULT NULL,
  `lost_reason` enum('cliente_nao_responde','cliente_recusa','empresa_fechou','valor_nao_compensa','outros') DEFAULT NULL,
  `lost_details` text DEFAULT NULL,
  `lost_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `collection_cards`
--

INSERT INTO `collection_cards` (`id`, `payment_id`, `manual_status`, `status_since`, `last_contact_at`, `last_contact_channel`, `last_contact_notes`, `lost_reason`, `lost_details`, `lost_at`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 5, 'em_cobranca', '2025-10-15 00:23:51', '2025-10-15 00:23:51', 'email', 'POREM NADA\n', NULL, NULL, NULL, 1, 1, '2025-10-15 03:22:35', '2025-10-15 00:23:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `collection_contacts`
--

CREATE TABLE IF NOT EXISTS `collection_contacts` (
  `id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `contact_type` enum('email','whatsapp','sms','ligacao','outro') NOT NULL,
  `contacted_at` datetime NOT NULL,
  `client_response` varchar(255) DEFAULT NULL,
  `expected_payment_at` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_reminder` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `collection_contacts`
--

INSERT INTO `collection_contacts` (`id`, `card_id`, `payment_id`, `contact_type`, `contacted_at`, `client_response`, `expected_payment_at`, `notes`, `is_reminder`, `created_by`, `created_at`) VALUES
(1, 1, 5, 'whatsapp', '2025-10-15 00:23:31', 'TENTEI LIGAR\n', NULL, '', 0, 1, '2025-10-15 03:23:31'),
(2, 1, 5, 'whatsapp', '2025-10-15 00:23:32', 'TENTEI LIGAR\n', NULL, '', 0, 1, '2025-10-15 03:23:32'),
(3, 1, 5, 'whatsapp', '2025-10-15 00:23:36', 'TENTEI LIGAR\n', NULL, 'POREM NADA\n', 0, 1, '2025-10-15 03:23:36'),
(4, 1, 5, 'whatsapp', '2025-10-15 00:23:38', 'TENTEI LIGAR\n', NULL, 'POREM NADA\n', 0, 1, '2025-10-15 03:23:38'),
(5, 1, 5, 'whatsapp', '2025-10-15 00:23:43', 'TENTEI LIGAR\n', '2025-10-15', 'POREM NADA\n', 0, 1, '2025-10-15 03:23:43'),
(6, 1, 5, 'whatsapp', '2025-10-15 00:23:44', 'TENTEI LIGAR\n', '2025-10-15', 'POREM NADA\n', 0, 1, '2025-10-15 03:23:44'),
(7, 1, 5, 'whatsapp', '2025-10-15 00:23:44', 'TENTEI LIGAR\n', '2025-10-15', 'POREM NADA\n', 0, 1, '2025-10-15 03:23:44'),
(8, 1, 5, 'email', '2025-10-15 00:23:49', 'TENTEI LIGAR\n', '2025-10-15', 'POREM NADA\n', 0, 1, '2025-10-15 03:23:49'),
(9, 1, 5, 'email', '2025-10-15 00:23:51', 'TENTEI LIGAR\n', '2025-10-15', 'POREM NADA\n', 0, 1, '2025-10-15 03:23:51'),
(10, 1, 5, 'email', '2025-10-15 00:23:51', 'TENTEI LIGAR\n', '2025-10-15', 'POREM NADA\n', 0, 1, '2025-10-15 03:23:51'),
(11, 1, 5, 'email', '2025-10-15 00:23:51', 'TENTEI LIGAR\n', '2025-10-15', 'POREM NADA\n', 0, 1, '2025-10-15 03:23:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `collection_movements`
--

CREATE TABLE IF NOT EXISTS `collection_movements` (
  `id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `from_status` enum('a_vencer','vencendo','vencido','em_cobranca','perdido') DEFAULT NULL,
  `to_status` enum('a_vencer','vencendo','vencido','em_cobranca','perdido') NOT NULL,
  `reason_code` varchar(60) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `collection_movements`
--

INSERT INTO `collection_movements` (`id`, `card_id`, `payment_id`, `from_status`, `to_status`, `reason_code`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 5, 'vencido', 'a_vencer', 'create_manual', NULL, 1, '2025-10-15 03:22:35'),
(2, 1, 5, 'a_vencer', 'em_cobranca', 'manual_collection', 'PENSA QUE É BESTA? \n', 1, '2025-10-15 03:23:21'),
(3, 1, 5, 'em_cobranca', 'em_cobranca', 'auto_contact', 'Movido automaticamente após registrar contato.', 1, '2025-10-15 03:23:31'),
(4, 1, 5, 'em_cobranca', 'em_cobranca', 'auto_contact', 'Movido automaticamente após registrar contato.', 1, '2025-10-15 03:23:32'),
(5, 1, 5, 'em_cobranca', 'em_cobranca', 'auto_contact', 'Movido automaticamente após registrar contato.', 1, '2025-10-15 03:23:36'),
(6, 1, 5, 'em_cobranca', 'em_cobranca', 'auto_contact', 'Movido automaticamente após registrar contato.', 1, '2025-10-15 03:23:38'),
(7, 1, 5, 'em_cobranca', 'em_cobranca', 'auto_contact', 'Movido automaticamente após registrar contato.', 1, '2025-10-15 03:23:43'),
(8, 1, 5, 'em_cobranca', 'em_cobranca', 'auto_contact', 'Movido automaticamente após registrar contato.', 1, '2025-10-15 03:23:44'),
(9, 1, 5, 'em_cobranca', 'em_cobranca', 'auto_contact', 'Movido automaticamente após registrar contato.', 1, '2025-10-15 03:23:44'),
(10, 1, 5, 'em_cobranca', 'em_cobranca', 'auto_contact', 'Movido automaticamente após registrar contato.', 1, '2025-10-15 03:23:49'),
(11, 1, 5, 'em_cobranca', 'em_cobranca', 'auto_contact', 'Movido automaticamente após registrar contato.', 1, '2025-10-15 03:23:51'),
(12, 1, 5, 'em_cobranca', 'em_cobranca', 'auto_contact', 'Movido automaticamente após registrar contato.', 1, '2025-10-15 03:23:51'),
(13, 1, 5, 'em_cobranca', 'em_cobranca', 'auto_contact', 'Movido automaticamente após registrar contato.', 1, '2025-10-15 03:23:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `financial_reserve_entries`
--

CREATE TABLE IF NOT EXISTS `financial_reserve_entries` (
  `id` int(11) NOT NULL,
  `operation_type` enum('deposit','withdraw') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reference_date` date NOT NULL,
  `description` varchar(180) DEFAULT NULL,
  `category` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `goals`
--

CREATE TABLE IF NOT EXISTS `goals` (
  `id` int(11) NOT NULL,
  `period_type` enum('daily','weekly','biweekly','monthly','quarterly') NOT NULL DEFAULT 'monthly',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `target_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `goals`
--

INSERT INTO `goals` (`id`, `period_type`, `period_start`, `period_end`, `target_value`, `created_at`, `updated_at`) VALUES
(1, 'monthly', '2025-09-01', '2025-09-27', 12333.00, '2025-09-24 21:57:50', '2025-09-24 21:57:50'),
(2, 'daily', '2025-09-25', '2025-09-25', 1688.00, '2025-09-24 22:08:06', '2025-09-24 22:08:06'),
(3, 'monthly', '2025-10-14', '2025-10-17', 342342.00, '2025-10-10 21:32:29', '2025-10-10 21:32:29'),
(4, 'monthly', '2025-10-11', '2025-10-11', 214.00, '2025-10-10 21:32:47', '2025-10-10 21:32:47'),
(5, 'weekly', '2025-10-13', '2025-10-18', 10.00, '2025-10-10 21:55:47', '2025-10-10 21:55:47');

-- --------------------------------------------------------

--
-- Estrutura para tabela `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `resource_type` varchar(50) NOT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `message` text DEFAULT NULL,
  `trigger_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `sound` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `resource_type`, `resource_id`, `title`, `message`, `trigger_at`, `read_at`, `sound`, `created_at`, `updated_at`) VALUES
(1, 2, 'billing_case', 1, 'Follow-up de cobrança agendado', 'Há uma ação programada para o caso \"cobrança\".', '2026-12-12 18:59:00', NULL, 1, '2025-10-11 07:54:32', '2025-10-11 07:54:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `payments`
--

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `kind` enum('one_time','recurring') NOT NULL DEFAULT 'one_time',
  `transaction_type` enum('receita','despesa') NOT NULL DEFAULT 'receita',
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'BRL',
  `due_date` date DEFAULT NULL,
  `paid_at` date DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `payments`
--

INSERT INTO `payments` (`id`, `project_id`, `client_id`, `kind`, `transaction_type`, `description`, `category`, `notes`, `amount`, `currency`, `due_date`, `paid_at`, `status_id`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'one_time', 'receita', NULL, NULL, NULL, 1650.00, 'BRL', NULL, '2025-09-14', 1, '2025-09-25 00:26:29', '2025-09-25 13:34:15'),
(2, 10, NULL, 'one_time', 'receita', NULL, NULL, NULL, 700.00, 'BRL', NULL, '2025-09-23', 1, '2025-09-25 13:52:20', '2025-09-25 13:53:07'),
(3, 10, NULL, 'one_time', 'receita', NULL, NULL, NULL, 700.00, 'BRL', '2025-10-08', NULL, 1, '2025-09-25 13:54:43', '2025-10-15 01:29:57'),
(4, 16, NULL, 'one_time', 'receita', 'ssdsadasd', 'dasdsad', NULL, 124.00, 'BRL', '2025-10-16', NULL, 5, '2025-10-15 01:29:25', '2025-10-15 01:29:25'),
(5, 17, 16, 'one_time', 'receita', 'devedor', 'Site', NULL, 12.00, 'BRL', '2025-10-13', NULL, 13, '2025-10-15 03:22:35', '2025-10-15 03:23:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `projects`
--

CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `nome_cliente` varchar(255) NOT NULL DEFAULT '',
  `data_entrada` datetime NOT NULL DEFAULT current_timestamp(),
  `name` varchar(150) NOT NULL,
  `status` enum('ativo','pausado','concluido','cancelado') NOT NULL DEFAULT 'ativo',
  `project_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status_id` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `paid_at` date DEFAULT NULL,
  `recurrence_active` tinyint(1) NOT NULL DEFAULT 0,
  `recurrence_value` decimal(12,2) DEFAULT NULL,
  `recurrence_frequency` enum('daily','weekly','biweekly','monthly') DEFAULT NULL,
  `recurrence_next_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tipo_servico` enum('Desenvolvimento Web','Design','Consultoria','Manutenção','SEO','Marketing Digital','Outro') NOT NULL DEFAULT 'Desenvolvimento Web',
  `status_satisfacao` enum('Satisfeito','Parcialmente Satisfeito','Insatisfeito','Aguardando Feedback') NOT NULL DEFAULT 'Aguardando Feedback',
  `valor_projeto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status_pagamento` enum('Pago','Pendente','Parcial','Cancelado') NOT NULL DEFAULT 'Pendente',
  `valor_pago` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_pendente` decimal(12,2) NOT NULL DEFAULT 0.00,
  `observacoes` text DEFAULT NULL,
  `usuario_responsavel_id` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `projects`
--

INSERT INTO `projects` (`id`, `client_id`, `nome_cliente`, `data_entrada`, `name`, `status`, `project_value`, `status_id`, `due_date`, `paid_at`, `recurrence_active`, `recurrence_value`, `recurrence_frequency`, `recurrence_next_date`, `created_at`, `updated_at`, `tipo_servico`, `status_satisfacao`, `valor_projeto`, `status_pagamento`, `valor_pago`, `valor_pendente`, `observacoes`, `usuario_responsavel_id`, `deleted_at`) VALUES
(1, 1, 'VC Business', '2025-10-10 14:14:25', 'Trafego Pago', 'ativo', 1650.00, 4, '2025-09-14', '2025-10-14', 0, NULL, NULL, NULL, '2025-09-25 00:26:06', '2025-09-25 00:26:06', 'Desenvolvimento Web', 'Aguardando Feedback', 1650.00, 'Pendente', 0.00, 1650.00, NULL, NULL, NULL),
(2, 16, '2 The Point Cleaning', '2025-10-10 14:14:25', 'Site + GBP + Redes Socias', 'ativo', 1400.00, 4, '2025-09-22', '2025-09-29', 0, NULL, NULL, NULL, '2025-09-25 13:38:57', '2025-09-25 13:38:57', 'Desenvolvimento Web', 'Aguardando Feedback', 1400.00, 'Pendente', 0.00, 1400.00, NULL, NULL, NULL),
(3, 14, 'AJ Carpentry', '2025-10-10 14:14:25', 'Site + GBP + Redes Socias', 'ativo', 1400.00, 4, '2025-09-19', '2025-09-26', 0, NULL, NULL, NULL, '2025-09-25 13:39:42', '2025-09-25 13:39:42', 'Desenvolvimento Web', 'Aguardando Feedback', 1400.00, 'Pendente', 0.00, 1400.00, NULL, NULL, NULL),
(4, 4, 'Ana', '2025-10-10 14:14:25', 'Criação Ecommerce', 'ativo', 1400.00, 4, '2025-08-20', '2025-09-30', 0, NULL, NULL, NULL, '2025-09-25 13:40:19', '2025-09-25 13:40:19', 'Desenvolvimento Web', 'Aguardando Feedback', 1400.00, 'Pendente', 0.00, 1400.00, NULL, NULL, NULL),
(5, 17, 'Ateliê Eryn Santos', '2025-10-10 14:14:25', 'Site', 'ativo', 1400.00, 4, '2025-09-23', '2025-09-30', 0, NULL, NULL, NULL, '2025-09-25 13:40:54', '2025-09-25 13:40:54', 'Desenvolvimento Web', 'Aguardando Feedback', 1400.00, 'Pendente', 0.00, 1400.00, NULL, NULL, NULL),
(6, 18, 'Boston Shine Painting', '2025-10-10 14:14:25', 'Site + GBP + Redes Socias', 'ativo', 1400.00, 4, '2025-09-23', '2025-09-30', 0, NULL, NULL, NULL, '2025-09-25 13:41:33', '2025-09-25 13:41:33', 'Desenvolvimento Web', 'Aguardando Feedback', 1400.00, 'Pendente', 0.00, 1400.00, NULL, NULL, NULL),
(7, 15, 'Bruno s Stone Care', '2025-10-10 14:14:25', 'Site + GBP + Redes Socias', 'ativo', 1400.00, 4, '2025-09-22', '2025-09-29', 0, NULL, NULL, NULL, '2025-09-25 13:42:49', '2025-09-25 13:42:49', 'Desenvolvimento Web', 'Aguardando Feedback', 1400.00, 'Pendente', 0.00, 1400.00, NULL, NULL, NULL),
(8, 13, 'Cacau Forte Alimentos', '2025-10-10 14:14:25', 'Site Wordpress', 'ativo', 1400.00, 4, '2025-09-19', '2025-09-27', 0, NULL, NULL, NULL, '2025-09-25 13:43:25', '2025-09-25 13:43:25', 'Desenvolvimento Web', 'Aguardando Feedback', 1400.00, 'Pendente', 0.00, 1400.00, NULL, NULL, NULL),
(9, 9, 'Cleaning and Care - Michael', '2025-10-10 14:14:25', 'Site + GBP + Redes Socias', 'ativo', 1400.00, 4, '2025-09-06', '2025-09-13', 0, NULL, NULL, NULL, '2025-09-25 13:43:59', '2025-09-25 13:43:59', 'Desenvolvimento Web', 'Aguardando Feedback', 1400.00, 'Pendente', 0.00, 1400.00, NULL, NULL, NULL),
(10, 7, 'Flex Flooring', '2025-10-10 14:14:25', 'Site + GBP + Redes Socias', 'ativo', 1400.00, 4, '2025-09-03', '2025-09-10', 0, NULL, NULL, NULL, '2025-09-25 13:44:31', '2025-09-25 13:44:31', 'Desenvolvimento Web', 'Aguardando Feedback', 1400.00, 'Pendente', 0.00, 1400.00, NULL, NULL, NULL),
(11, 6, 'Get Power Research', '2025-10-10 14:14:25', 'Manutenção APP', 'ativo', 550.00, 4, '2025-10-08', NULL, 0, NULL, NULL, NULL, '2025-09-25 13:45:25', '2025-09-25 13:45:25', 'Desenvolvimento Web', 'Aguardando Feedback', 550.00, 'Pendente', 0.00, 550.00, NULL, NULL, NULL),
(12, 10, 'Gi Rozendo Cleaning', '2025-10-10 14:14:25', 'Site', 'ativo', 963.46, 4, '2025-09-11', '2025-09-19', 0, NULL, NULL, NULL, '2025-09-25 13:46:37', '2025-09-25 13:46:37', 'Desenvolvimento Web', 'Aguardando Feedback', 963.46, 'Pendente', 0.00, 963.46, NULL, NULL, NULL),
(13, 11, 'Juliana Interprete', '2025-10-10 14:14:25', 'Site', 'ativo', 1000.00, 4, '2025-09-18', '2025-09-25', 0, NULL, NULL, NULL, '2025-09-25 13:47:13', '2025-09-25 13:47:13', 'Desenvolvimento Web', 'Aguardando Feedback', 1000.00, 'Pendente', 0.00, 1000.00, NULL, NULL, NULL),
(14, 5, 'Kami', '2025-10-10 14:14:25', 'App', 'ativo', 1980.00, 4, '2025-08-21', '2025-09-30', 0, NULL, NULL, NULL, '2025-09-25 13:47:51', '2025-09-25 13:47:51', 'Desenvolvimento Web', 'Aguardando Feedback', 1980.00, 'Pendente', 0.00, 1980.00, NULL, NULL, NULL),
(15, 12, 'Loja Grace Glamour - Shopfy', '2025-10-10 14:14:25', 'Criação Loja Shopfy', 'ativo', 1500.00, 4, '2025-09-19', '2025-09-26', 0, NULL, NULL, NULL, '2025-09-25 13:48:32', '2025-09-25 13:48:32', 'Desenvolvimento Web', 'Aguardando Feedback', 1500.00, 'Pendente', 0.00, 1500.00, NULL, NULL, NULL),
(16, 19, 'Pro Trim Carpentry', '2025-10-10 14:14:25', 'Site + GBP + Redes Socias', 'ativo', 1400.00, 4, '2025-09-23', '2025-09-30', 0, NULL, NULL, NULL, '2025-09-25 13:48:57', '2025-09-25 13:48:57', 'Desenvolvimento Web', 'Aguardando Feedback', 1400.00, 'Pendente', 0.00, 1400.00, NULL, NULL, NULL),
(17, 8, 'Rancho', '2025-10-10 14:14:25', 'APP', 'ativo', 1925.00, 4, '2025-09-05', '2025-09-30', 0, NULL, NULL, NULL, '2025-09-25 13:49:25', '2025-09-25 13:49:25', 'Desenvolvimento Web', 'Aguardando Feedback', 1925.00, 'Pendente', 0.00, 1925.00, NULL, NULL, NULL),
(18, 3, 'Victor - Farma Facil', '2025-10-10 14:14:25', 'Manutenção APP', 'ativo', 385.00, 4, '2025-10-01', NULL, 0, NULL, NULL, NULL, '2025-09-25 13:50:27', '2025-09-25 13:50:27', 'Desenvolvimento Web', 'Aguardando Feedback', 385.00, 'Pendente', 0.00, 385.00, NULL, NULL, NULL),
(19, 2, 'Voice', '2025-10-10 14:14:25', 'Rifa + IA', 'ativo', 767.00, 4, NULL, NULL, 0, NULL, NULL, NULL, '2025-09-25 13:51:25', '2025-10-11 09:48:17', 'Desenvolvimento Web', 'Insatisfeito', 73000.00, 'Pendente', 0.00, 73000.00, '', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `project_activities`
--

CREATE TABLE IF NOT EXISTS `project_activities` (
  `id` int(11) NOT NULL,
  `projeto_id` int(11) NOT NULL,
  `titulo_atividade` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `data_inicio` datetime NOT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `status_atividade` enum('Não Iniciada','Em Andamento','Concluída','Bloqueada','Cancelada') NOT NULL DEFAULT 'Não Iniciada',
  `prioridade` enum('Baixa','Média','Alta','Urgente') NOT NULL DEFAULT 'Média',
  `responsavel_id` int(11) DEFAULT NULL,
  `horas_estimadas` decimal(7,2) DEFAULT NULL,
  `horas_reais` decimal(7,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `rate_limits`
--

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `rota` varchar(120) NOT NULL,
  `tentativas` int(11) NOT NULL DEFAULT 1,
  `primeiro_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `ip`, `rota`, `tentativas`, `primeiro_registro`, `ultimo_registro`) VALUES
(1, '170.84.159.212', 'user_create', 1, '2025-10-11 21:03:05', '2025-10-11 21:03:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `status_catalog`
--

CREATE TABLE IF NOT EXISTS `status_catalog` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `color_hex` char(7) NOT NULL DEFAULT '#6b7280',
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `status_catalog`
--

INSERT INTO `status_catalog` (`id`, `name`, `color_hex`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Recebido', '#1cc88a', 1, '2025-09-24 19:50:20', NULL),
(2, 'Pago', '#1cc88a', 2, '2025-09-24 19:50:20', NULL),
(3, 'A Receber', '#f6c23e', 3, '2025-09-24 19:50:20', NULL),
(4, 'Pendente', '#f6c23e', 4, '2025-09-24 19:50:20', NULL),
(5, 'Em Atraso', '#e74a3b', 5, '2025-09-24 19:50:20', NULL),
(6, 'Vencido', '#e74a3b', 6, '2025-09-24 19:50:20', NULL),
(7, 'Parcelado', '#4e73df', 7, '2025-09-24 19:50:20', NULL),
(8, 'Cancelado', '#858796', 8, '2025-09-24 19:50:20', NULL),
(9, 'Pending', '#f6c23e', 9, '2025-09-24 19:50:20', NULL),
(10, 'Paid', '#1cc88a', 10, '2025-09-24 19:50:20', NULL),
(11, 'Overdue', '#e74a3b', 11, '2025-09-24 19:50:20', NULL),
(12, 'Dropped', '#858796', 12, '2025-09-24 19:50:20', NULL),
(13, 'Em Cobrança', '#f97316', 13, '2025-10-15 03:18:16', '2025-10-15 03:18:16'),
(14, 'Perdido', '#6B7280', 14, '2025-10-15 03:18:16', '2025-10-15 03:18:16');

-- --------------------------------------------------------

--
-- Estrutura para tabela `templates_library`
--

CREATE TABLE IF NOT EXISTS `templates_library` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL,
  `template_type` varchar(50) NOT NULL DEFAULT 'general',
  `link` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `preview_html` mediumtext DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `source_path` varchar(191) DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `cargo` varchar(120) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `senha_atualizada_em` timestamp NULL DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `password_reset_token` varchar(191) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `tipo_usuario` enum('Admin','Gerente','Colaborador','Cliente') DEFAULT 'Colaborador',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `nome_completo`, `email`, `telefone`, `cargo`, `foto_perfil`, `ativo`, `data_cadastro`, `ultimo_acesso`, `senha_atualizada_em`, `deleted_at`, `password`, `password_reset_token`, `password_reset_expires`, `tipo_usuario`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'marketing@arkaleads.com', NULL, NULL, NULL, 1, '2025-10-10 14:17:22', '2025-10-15 03:21:37', '2025-10-11 14:07:55', NULL, '$2y$10$2eDMtIEFq50WX9C9uFHo1OkKULVPJz5htY5b6dJYaUFocSYyeCAQG', NULL, NULL, 'Admin', '2025-09-24 20:08:43', '2025-10-15 03:21:37'),
(2, 'tainon vargas', 'tainon@arkaleads.com', NULL, NULL, '/uploads/avatars/avatar_68e9871261bca8.50277516.png', 1, '2025-10-10 22:22:10', '2025-10-13 22:37:47', '2025-10-10 22:22:10', NULL, '$2y$10$hZhcFzAxqlcvJC8SnQM7/uYhyL5PwBnayGgNLZhqfY7dxm3h9d71m', NULL, NULL, 'Gerente', '2025-10-10 22:22:10', '2025-10-13 22:37:47'),
(3, 'MIke lins', 'mike@arkaleads.com', NULL, NULL, NULL, 1, '2025-10-11 21:03:06', '2025-10-15 01:28:56', '2025-10-11 21:03:06', NULL, '$2y$10$O46xMJzmQcqc1eKBllI6uO/DMZj6Rj9PvxbB0nIElQbRkmgnvamt.', NULL, NULL, 'Admin', '2025-10-11 21:03:06', '2025-10-15 01:28:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_audit_logs`
--

CREATE TABLE IF NOT EXISTS `user_audit_logs` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `acao` enum('create','update','delete','login','logout','password_reset','status_change','role_change') NOT NULL,
  `detalhes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detalhes`)),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `user_audit_logs`
--

INSERT INTO `user_audit_logs` (`id`, `usuario_id`, `acao`, `detalhes`, `ip`, `user_agent`, `created_at`) VALUES
(1, 1, 'create', '{\"user_id\":2}', '170.84.159.212', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-10 22:22:10'),
(2, 1, 'update', '{\"user_id\":1}', '170.84.159.212', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-11 09:54:55'),
(3, 1, 'update', '{\"user_id\":1}', '170.84.159.212', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-11 14:07:55'),
(4, 1, 'create', '{\"user_id\":3}', '170.84.159.212', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:143.0) Gecko/20100101 Firefox/143.0', '2025-10-11 21:03:06');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_sessions`
--

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `session_id` varchar(191) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `billing_cases`
--
ALTER TABLE `billing_cases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_billing_cases_client` (`client_id`),
  ADD KEY `fk_billing_cases_responsavel` (`responsavel_id`);

--
-- Índices de tabela `billing_tasks`
--
ALTER TABLE `billing_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `responsavel_id` (`responsavel_id`);

--
-- Índices de tabela `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clients_entry_date` (`entry_date`);

--
-- Índices de tabela `collection_cards`
--
ALTER TABLE `collection_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_collection_cards_payment` (`payment_id`),
  ADD KEY `fk_collection_card_created_by` (`created_by`),
  ADD KEY `fk_collection_card_updated_by` (`updated_by`);

--
-- Índices de tabela `collection_contacts`
--
ALTER TABLE `collection_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_collection_contact_card` (`card_id`),
  ADD KEY `fk_collection_contact_user` (`created_by`),
  ADD KEY `idx_collection_contacts_payment` (`payment_id`),
  ADD KEY `idx_collection_contacts_contacted_at` (`contacted_at`);

--
-- Índices de tabela `collection_movements`
--
ALTER TABLE `collection_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_collection_move_card` (`card_id`),
  ADD KEY `fk_collection_move_user` (`created_by`),
  ADD KEY `idx_collection_movements_payment` (`payment_id`),
  ADD KEY `idx_collection_movements_created_at` (`created_at`);

--
-- Índices de tabela `financial_reserve_entries`
--
ALTER TABLE `financial_reserve_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_financial_reserve_type` (`operation_type`),
  ADD KEY `idx_financial_reserve_date` (`reference_date`),
  ADD KEY `fk_financial_reserve_created_by` (`created_by`);

--
-- Índices de tabela `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_goals_period` (`period_start`,`period_end`);

--
-- Índices de tabela `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`read_at`),
  ADD KEY `idx_notifications_trigger` (`trigger_at`);

--
-- Índices de tabela `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_project` (`project_id`),
  ADD KEY `idx_payments_status` (`status_id`),
  ADD KEY `idx_payments_due` (`due_date`),
  ADD KEY `fk_payments_clients` (`client_id`);

--
-- Índices de tabela `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_projects_client` (`client_id`),
  ADD KEY `idx_projects_status` (`status_id`),
  ADD KEY `idx_projects_nome_cliente` (`nome_cliente`),
  ADD KEY `idx_projects_status_pagamento` (`status_pagamento`),
  ADD KEY `idx_projects_tipo_servico` (`tipo_servico`),
  ADD KEY `idx_projects_data_entrada` (`data_entrada`),
  ADD KEY `idx_projects_usuario` (`usuario_responsavel_id`);

--
-- Índices de tabela `project_activities`
--
ALTER TABLE `project_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_activities_projeto` (`projeto_id`),
  ADD KEY `idx_project_activities_status` (`status_atividade`),
  ADD KEY `idx_project_activities_prioridade` (`prioridade`),
  ADD KEY `project_activities_responsavel_idx` (`responsavel_id`);

--
-- Índices de tabela `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rate_limits_ip_rota` (`ip`,`rota`);

--
-- Índices de tabela `status_catalog`
--
ALTER TABLE `status_catalog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_status_catalog_name` (`name`);

--
-- Índices de tabela `templates_library`
--
ALTER TABLE `templates_library`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_templates_category` (`category`),
  ADD KEY `idx_templates_type` (`template_type`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `user_audit_logs`
--
ALTER TABLE `user_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_audit_usuario` (`usuario_id`),
  ADD KEY `idx_user_audit_acao` (`acao`);

--
-- Índices de tabela `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_sessions` (`session_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `billing_cases`
--
ALTER TABLE `billing_cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `billing_tasks`
--
ALTER TABLE `billing_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `collection_cards`
--
ALTER TABLE `collection_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `collection_contacts`
--
ALTER TABLE `collection_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `collection_movements`
--
ALTER TABLE `collection_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `financial_reserve_entries`
--
ALTER TABLE `financial_reserve_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `goals`
--
ALTER TABLE `goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `project_activities`
--
ALTER TABLE `project_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `status_catalog`
--
ALTER TABLE `status_catalog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `templates_library`
--
ALTER TABLE `templates_library`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `user_audit_logs`
--
ALTER TABLE `user_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `billing_cases`
--
ALTER TABLE `billing_cases`
  ADD CONSTRAINT `fk_billing_cases_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_billing_cases_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `billing_tasks`
--
ALTER TABLE `billing_tasks`
  ADD CONSTRAINT `billing_tasks_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `billing_cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `billing_tasks_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `collection_cards`
--
ALTER TABLE `collection_cards`
  ADD CONSTRAINT `fk_collection_card_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_collection_card_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_collection_card_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `collection_contacts`
--
ALTER TABLE `collection_contacts`
  ADD CONSTRAINT `fk_collection_contact_card` FOREIGN KEY (`card_id`) REFERENCES `collection_cards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_collection_contact_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_collection_contact_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `collection_movements`
--
ALTER TABLE `collection_movements`
  ADD CONSTRAINT `fk_collection_move_card` FOREIGN KEY (`card_id`) REFERENCES `collection_cards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_collection_move_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_collection_move_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `financial_reserve_entries`
--
ALTER TABLE `financial_reserve_entries`
  ADD CONSTRAINT `fk_financial_reserve_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_clients` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payments_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payments_status` FOREIGN KEY (`status_id`) REFERENCES `status_catalog` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_projects_status` FOREIGN KEY (`status_id`) REFERENCES `status_catalog` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_projects_usuario_responsavel` FOREIGN KEY (`usuario_responsavel_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `project_activities`
--
ALTER TABLE `project_activities`
  ADD CONSTRAINT `project_activities_ibfk_1` FOREIGN KEY (`projeto_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_activities_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `user_audit_logs`
--
ALTER TABLE `user_audit_logs`
  ADD CONSTRAINT `user_audit_logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
