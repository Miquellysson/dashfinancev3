
-- Gestão Financeira - Schema Completo
-- Execute este script no seu banco MySQL

SET FOREIGN_KEY_CHECKS = 0;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome_completo VARCHAR(255) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  password_reset_token VARCHAR(191) NULL,
  password_reset_expires DATETIME NULL,
  tipo_usuario ENUM('Admin','Gerente','Colaborador','Cliente') DEFAULT 'Colaborador',
  telefone VARCHAR(30) NULL,
  cargo VARCHAR(120) NULL,
  foto_perfil VARCHAR(255) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ultimo_acesso TIMESTAMP NULL,
  senha_atualizada_em TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL
);

-- Tabela de clientes
CREATE TABLE IF NOT EXISTS clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(120),
  phone VARCHAR(20),
  address TEXT,
  entry_date DATE NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS status_catalog (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  color_hex CHAR(7) NOT NULL DEFAULT '#6b7280',
  sort_order INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_status_catalog_name (name)
);

CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NULL,
  nome_cliente VARCHAR(255) NOT NULL,
  name VARCHAR(180) NOT NULL,
  data_entrada DATETIME NOT NULL,
  tipo_servico ENUM('Desenvolvimento Web','Design','Consultoria','Manutenção','SEO','Marketing Digital','Outro') NOT NULL DEFAULT 'Desenvolvimento Web',
  status_satisfacao ENUM('Satisfeito','Parcialmente Satisfeito','Insatisfeito','Aguardando Feedback') NOT NULL DEFAULT 'Aguardando Feedback',
  status ENUM('ativo','pausado','concluido','cancelado') DEFAULT 'ativo',
  valor_projeto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status_pagamento ENUM('Pago','Pendente','Parcial','Cancelado') NOT NULL DEFAULT 'Pendente',
  valor_pago DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  valor_pendente DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  observacoes TEXT NULL,
  usuario_responsavel_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
  FOREIGN KEY (usuario_responsavel_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_projects_nome_cliente ON projects (nome_cliente);
CREATE INDEX idx_projects_status_pagamento ON projects (status_pagamento);
CREATE INDEX idx_projects_data_entrada ON projects (data_entrada);
CREATE INDEX idx_projects_tipo_servico ON projects (tipo_servico);

-- Tabela de pagamentos
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT,
  kind ENUM('one_time','recurring') DEFAULT 'one_time',
  amount DECIMAL(12,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'BRL',
  transaction_type ENUM('receita','despesa') NOT NULL DEFAULT 'receita',
  description VARCHAR(255) NULL,
  category VARCHAR(120) NULL,
  due_date DATE NULL,
  paid_at DATE NULL,
  status_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  FOREIGN KEY (status_id) REFERENCES status_catalog(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS project_activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT NOT NULL,
  titulo_atividade VARCHAR(255) NOT NULL,
  descricao TEXT NOT NULL,
  data_inicio DATETIME NOT NULL,
  data_conclusao DATETIME NULL,
  status_atividade ENUM('Não Iniciada','Em Andamento','Concluída','Bloqueada','Cancelada') NOT NULL DEFAULT 'Não Iniciada',
  prioridade ENUM('Baixa','Média','Alta','Urgente') NOT NULL DEFAULT 'Média',
  responsavel_id INT NULL,
  horas_estimadas DECIMAL(7,2) NULL,
  horas_reais DECIMAL(7,2) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  FOREIGN KEY (projeto_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (responsavel_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS user_audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  acao ENUM('create','update','delete','login','logout','password_reset','status_change','role_change') NOT NULL,
  detalhes JSON NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  session_id VARCHAR(191) NOT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uk_user_sessions (session_id)
);

CREATE TABLE IF NOT EXISTS rate_limits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  rota VARCHAR(120) NOT NULL,
  tentativas INT NOT NULL DEFAULT 1,
  primeiro_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rate_limits_ip_rota (ip, rota)
);

CREATE TABLE IF NOT EXISTS templates_library (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(50) NOT NULL,
  template_type VARCHAR(50) NOT NULL DEFAULT 'general',
  link VARCHAR(255) NULL,
  description TEXT NULL,
  source_path VARCHAR(191) NULL,
  keywords TEXT NULL,
  screenshot_path VARCHAR(255) NULL,
  file_path VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX idx_templates_category ON templates_library (category);
CREATE INDEX idx_templates_type ON templates_library (template_type);

CREATE TABLE IF NOT EXISTS goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  period_type ENUM('daily','weekly','biweekly','monthly','quarterly') NOT NULL DEFAULT 'monthly',
  period_start DATE NOT NULL DEFAULT '1970-01-01',
  period_end DATE NOT NULL DEFAULT '1970-01-01',
  target_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  title VARCHAR(150) NULL,
  description TEXT NULL,
  target_amount DECIMAL(12,2) DEFAULT 0.00,
  current_amount DECIMAL(12,2) DEFAULT 0.00,
  target_date DATE NULL,
  achieved TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

SET FOREIGN_KEY_CHECKS = 1;

-- Dados iniciais
INSERT INTO users (nome_completo, email, password, tipo_usuario)
VALUES
('Administrador', 'admin@arkaleads.com', '$2y$10$M0QJtDReuMXfslWGVdJ8O.OlM5qjeoN5NPgmK8p2upLir0EZJzvhO', 'Admin'),
('Gerente', 'gerente@arkaleads.com', '$2y$10$M0QJtDReuMXfslWGVdJ8O.OlM5qjeoN5NPgmK8p2upLir0EZJzvhO', 'Gerente')
ON DUPLICATE KEY UPDATE nome_completo=VALUES(nome_completo);

-- Dados de exemplo (opcional)
INSERT INTO clients (name, email, phone, address, entry_date, notes) VALUES
('Empresa ABC Ltda', 'contato@empresaabc.com', '(11) 99999-9999', 'Rua das Flores, 123 - São Paulo/SP', '2024-01-05', 'Cliente corporativo com contrato recorrente.'),
('João Silva', 'joao@email.com', '(11) 88888-8888', 'Av. Paulista, 456 - São Paulo/SP', '2024-02-02', 'Projeto de implantação de CRM.'),
('Maria Santos', 'maria@email.com', '(11) 77777-7777', 'Rua Augusta, 789 - São Paulo/SP', '2024-03-03', 'Aplicativo mobile com entregas em fases.')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  email = VALUES(email),
  phone = VALUES(phone),
  address = VALUES(address),
  entry_date = VALUES(entry_date),
  notes = VALUES(notes);

INSERT INTO status_catalog (name, color_hex, sort_order)
VALUES
('Recebido', '#1cc88a', 1),
('Pago', '#1cc88a', 2),
('A Receber', '#f6c23e', 3),
('Pendente', '#f6c23e', 4),
('Em Atraso', '#e74a3b', 5),
('Vencido', '#e74a3b', 6),
('Parcelado', '#4e73df', 7),
('Cancelado', '#858796', 8),
('Pending', '#f6c23e', 9),
('Paid', '#1cc88a', 10),
('Overdue', '#e74a3b', 11),
('Dropped', '#858796', 12)
ON DUPLICATE KEY UPDATE color_hex = VALUES(color_hex), sort_order = VALUES(sort_order);

INSERT INTO projects (
  client_id, nome_cliente, name, data_entrada, tipo_servico,
  status_satisfacao, status, valor_projeto, status_pagamento,
  valor_pago, valor_pendente, observacoes
) VALUES
(1, 'Empresa ABC Ltda', 'Website Institucional', '2024-01-15 09:00:00', 'Desenvolvimento Web', 'Aguardando Feedback', 'ativo', 5000.00, 'Parcial', 2500.00, 2500.00, 'Projeto institucional com escopo de 8 semanas'),
(2, 'João Silva', 'Sistema de Vendas', '2024-02-01 10:30:00', 'Consultoria', 'Satisfeito', 'ativo', 8000.00, 'Pendente', 0.00, 8000.00, 'Implantação de CRM e automações'),
(3, 'Maria Santos', 'App Mobile Delivery', '2024-03-01 14:00:00', 'Desenvolvimento Web', 'Parcialmente Satisfeito', 'pausado', 12000.00, 'Cancelado', 6000.00, 6000.00, 'Projeto pausado aguardando orçamento adicional')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO payments (
  id, project_id, kind, amount, currency, transaction_type,
  description, category, due_date, paid_at, status_id
) VALUES
(1, 1, 'one_time', 2500.00, 'BRL', 'receita',
 'Pagamento inicial - 50%', 'Entrada', '2024-01-20', '2024-01-20',
 (SELECT id FROM status_catalog WHERE name = 'Recebido')),
(2, 1, 'one_time', 2500.00, 'BRL', 'receita',
 'Pagamento final - 50%', 'Conclusão', '2024-02-15', NULL,
 (SELECT id FROM status_catalog WHERE name = 'A Receber')),
(3, 2, 'one_time', 4000.00, 'BRL', 'receita',
 'Primeira parcela', 'Consultoria', '2024-02-10', '2024-02-12',
 (SELECT id FROM status_catalog WHERE name = 'Recebido')),
(4, 2, 'one_time', 4000.00, 'BRL', 'receita',
 'Segunda parcela', 'Consultoria', '2024-03-10', NULL,
 (SELECT id FROM status_catalog WHERE name = 'Em Atraso')),
(5, 3, 'one_time', 6000.00, 'BRL', 'receita',
 'Entrada do projeto', 'App', '2024-03-15', '2024-03-18',
 (SELECT id FROM status_catalog WHERE name = 'Cancelado'))
ON DUPLICATE KEY UPDATE
  amount = VALUES(amount),
  currency = VALUES(currency),
  transaction_type = VALUES(transaction_type),
  description = VALUES(description),
  category = VALUES(category),
  due_date = VALUES(due_date),
  paid_at = VALUES(paid_at),
  status_id = VALUES(status_id);
