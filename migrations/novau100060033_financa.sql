-- SQL dump alinhado ao schema atual da aplicação Finance
-- Ambiente alvo: MariaDB 10.6+ / MySQL 5.7+

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;
SET time_zone = '+00:00';

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `project_activities`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `goals`;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `user_audit_logs`;
DROP TABLE IF EXISTS `rate_limits`;
DROP TABLE IF EXISTS `templates_library`;
DROP TABLE IF EXISTS `clients`;
DROP TABLE IF EXISTS `status_catalog`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------
-- Estrutura da tabela `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `nome_completo`, `email`, `telefone`, `cargo`, `foto_perfil`, `ativo`, `data_cadastro`, `ultimo_acesso`, `senha_atualizada_em`, `deleted_at`, `password`, `password_reset_token`, `password_reset_expires`, `tipo_usuario`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'marketing@arkaleads.com', NULL, NULL, NULL, 1, '2025-10-10 14:17:22', '2025-10-11 02:18:02', NULL, NULL, '$2y$10$YBJQuY53lFqQACp0VvY10uqDlM7yV82D3uAYXUvdl5hQOnTSbT5FO', NULL, NULL, 'Admin', '2025-09-24 20:08:43', '2025-09-24 22:07:38'),
(2, 'tainon vargas', 'tainon@arkaleads.com', NULL, NULL, '/uploads/avatars/avatar_68e9871261bca8.50277516.png', 1, '2025-10-10 22:22:10', '2025-10-10 22:26:26', '2025-10-10 22:22:10', NULL, '$2y$10$hZhcFzAxqlcvJC8SnQM7/uYhyL5PwBnayGgNLZhqfY7dxm3h9d71m', NULL, NULL, 'Gerente', '2025-10-10 22:22:10', '2025-10-10 22:22:10');

-- --------------------------------------------------------
-- Estrutura da tabela `clients`
-- --------------------------------------------------------

CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_clients_entry_date` (`entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Estrutura da tabela `status_catalog`
-- --------------------------------------------------------

CREATE TABLE `status_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `color_hex` char(7) NOT NULL DEFAULT '#6b7280',
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_status_catalog_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(12, 'Dropped', '#858796', 12, '2025-09-24 19:50:20', NULL);

-- --------------------------------------------------------
-- Estrutura da tabela `projects`
-- --------------------------------------------------------

CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
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
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_projects_client` (`client_id`),
  KEY `idx_projects_status` (`status_id`),
  KEY `idx_projects_nome_cliente` (`nome_cliente`),
  KEY `idx_projects_status_pagamento` (`status_pagamento`),
  KEY `idx_projects_tipo_servico` (`tipo_servico`),
  KEY `idx_projects_data_entrada` (`data_entrada`),
  KEY `idx_projects_usuario` (`usuario_responsavel_id`),
  CONSTRAINT `fk_projects_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_projects_status` FOREIGN KEY (`status_id`) REFERENCES `status_catalog` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_projects_usuario_responsavel` FOREIGN KEY (`usuario_responsavel_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(19, 2, 'Voice', '2025-10-10 14:14:25', 'Rifa + IA', 'ativo', 767.00, 4, NULL, NULL, 0, NULL, NULL, NULL, '2025-09-25 13:51:25', '2025-09-25 13:51:25', 'Desenvolvimento Web', 'Aguardando Feedback', 767.00, 'Pendente', 0.00, 767.00, NULL, NULL, NULL);

-- --------------------------------------------------------
-- Estrutura da tabela `project_activities`
-- --------------------------------------------------------

CREATE TABLE `project_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_activities_projeto` (`projeto_id`),
  KEY `idx_project_activities_status` (`status_atividade`),
  KEY `idx_project_activities_prioridade` (`prioridade`),
  KEY `project_activities_responsavel_idx` (`responsavel_id`),
  CONSTRAINT `project_activities_ibfk_1` FOREIGN KEY (`projeto_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_activities_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `payments`
-- --------------------------------------------------------

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `kind` enum('one_time','recurring') NOT NULL DEFAULT 'one_time',
  `transaction_type` enum('receita','despesa') NOT NULL DEFAULT 'receita',
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(120) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'BRL',
  `due_date` date DEFAULT NULL,
  `paid_at` date DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payments_project` (`project_id`),
  KEY `idx_payments_status` (`status_id`),
  KEY `idx_payments_due` (`due_date`),
  CONSTRAINT `fk_payments_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_status` FOREIGN KEY (`status_id`) REFERENCES `status_catalog` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`id`, `project_id`, `kind`, `transaction_type`, `description`, `category`, `amount`, `currency`, `due_date`, `paid_at`, `status_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'one_time', 'receita', NULL, NULL, 1650.00, 'BRL', NULL, '2025-09-14', 1, '2025-09-25 00:26:29', '2025-09-25 13:34:15'),
(2, 10, 'one_time', 'receita', NULL, NULL, 700.00, 'BRL', NULL, '2025-09-23', 1, '2025-09-25 13:52:20', '2025-09-25 13:53:07'),
(3, 10, 'one_time', 'receita', NULL, NULL, 700.00, 'BRL', NULL, '2025-09-23', 1, '2025-09-25 13:54:43', '2025-09-25 13:54:43');

-- --------------------------------------------------------
-- Estrutura da tabela `goals`
-- --------------------------------------------------------

CREATE TABLE `goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_type` enum('daily','weekly','biweekly','monthly','quarterly') NOT NULL DEFAULT 'monthly',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `target_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_goals_period` (`period_start`,`period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `goals` (`id`, `period_type`, `period_start`, `period_end`, `target_value`, `created_at`, `updated_at`) VALUES
(1, 'monthly', '2025-09-01', '2025-09-27', 12333.00, '2025-09-24 21:57:50', '2025-09-24 21:57:50'),
(2, 'daily', '2025-09-25', '2025-09-25', 1688.00, '2025-09-24 22:08:06', '2025-09-24 22:08:06'),
(3, 'monthly', '2025-10-14', '2025-10-17', 342342.00, '2025-10-10 21:32:29', '2025-10-10 21:32:29'),
(4, 'monthly', '2025-10-11', '2025-10-11', 214.00, '2025-10-10 21:32:47', '2025-10-10 21:32:47'),
(5, 'weekly', '2025-10-13', '2025-10-18', 10.00, '2025-10-10 21:55:47', '2025-10-10 21:55:47');

-- --------------------------------------------------------
-- Estrutura da tabela `templates_library`
-- --------------------------------------------------------

CREATE TABLE `templates_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL,
  `template_type` varchar(50) NOT NULL DEFAULT 'general',
  `link` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `source_path` varchar(191) DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_templates_category` (`category`),
  KEY `idx_templates_type` (`template_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura da tabela `rate_limits`
-- --------------------------------------------------------

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `rota` varchar(120) NOT NULL,
  `tentativas` int(11) NOT NULL DEFAULT 1,
  `primeiro_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rate_limits_ip_rota` (`ip`,`rota`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rate_limits` (`id`, `ip`, `rota`, `tentativas`, `primeiro_registro`, `ultimo_registro`) VALUES
(1, '170.84.159.212', 'user_create', 1, '2025-10-10 22:22:10', '2025-10-10 22:22:10');

-- --------------------------------------------------------
-- Estrutura da tabela `user_audit_logs`
-- --------------------------------------------------------

CREATE TABLE `user_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `acao` enum('create','update','delete','login','logout','password_reset','status_change','role_change') NOT NULL,
  `detalhes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detalhes`)),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_audit_usuario` (`usuario_id`),
  KEY `idx_user_audit_acao` (`acao`),
  CONSTRAINT `user_audit_logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user_audit_logs` (`id`, `usuario_id`, `acao`, `detalhes`, `ip`, `user_agent`, `created_at`) VALUES
(1, 1, 'create', '{\"user_id\":2}', '170.84.159.212', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-10 22:22:10');

-- --------------------------------------------------------
-- Estrutura da tabela `user_sessions`
-- --------------------------------------------------------

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `session_id` varchar(191) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_sessions` (`session_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
