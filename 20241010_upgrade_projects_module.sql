-- Migration: Upgrade Projects Module and Users
-- Executar em MySQL 5.7+

SET FOREIGN_KEY_CHECKS = 0;

/* =====================
   Ajustes tabela projects
   ===================== */
ALTER TABLE projects
    ADD COLUMN nome_cliente VARCHAR(255) NOT NULL DEFAULT '' AFTER client_id,
    ADD COLUMN data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER nome_cliente,
    ADD COLUMN tipo_servico ENUM('Desenvolvimento Web','Design','Consultoria','Manutenção','SEO','Marketing Digital','Outro') NOT NULL DEFAULT 'Desenvolvimento Web' AFTER data_entrada,
    ADD COLUMN status_satisfacao ENUM('Satisfeito','Parcialmente Satisfeito','Insatisfeito','Aguardando Feedback') NOT NULL DEFAULT 'Aguardando Feedback' AFTER tipo_servico,
    ADD COLUMN valor_projeto DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER status,
    ADD COLUMN status_pagamento ENUM('Pago','Pendente','Parcial','Cancelado') NOT NULL DEFAULT 'Pendente' AFTER valor_projeto,
    ADD COLUMN valor_pago DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER status_pagamento,
    ADD COLUMN valor_pendente DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER valor_pago,
    ADD COLUMN observacoes TEXT NULL AFTER valor_pendente,
    ADD COLUMN usuario_responsavel_id INT NULL AFTER observacoes,
    ADD COLUMN deleted_at DATETIME NULL AFTER updated_at;

UPDATE projects p
LEFT JOIN clients c ON c.id = p.client_id
SET p.nome_cliente = COALESCE(c.name, p.nome_cliente, 'Cliente não informado')
WHERE p.nome_cliente = '' OR p.nome_cliente IS NULL;

UPDATE projects
SET valor_projeto = budget
WHERE valor_projeto = 0 AND budget IS NOT NULL;

UPDATE projects
SET valor_pendente = GREATEST(0, valor_projeto - valor_pago);

ALTER TABLE projects
    ADD CONSTRAINT fk_projects_usuario_responsavel
        FOREIGN KEY (usuario_responsavel_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX idx_projects_nome_cliente ON projects (nome_cliente);
CREATE INDEX idx_projects_status_pagamento ON projects (status_pagamento);
CREATE INDEX idx_projects_tipo_servico ON projects (tipo_servico);
CREATE INDEX idx_projects_data_entrada ON projects (data_entrada);
CREATE INDEX idx_projects_usuario ON projects (usuario_responsavel_id);

/* =====================
   Tabela de atividades
   ===================== */
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
    FOREIGN KEY (responsavel_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_activities_projeto (projeto_id),
    INDEX idx_project_activities_status (status_atividade),
    INDEX idx_project_activities_prioridade (prioridade)
);

/* =====================
   Ajustes tabela users
   ===================== */
ALTER TABLE users
    CHANGE COLUMN name nome_completo VARCHAR(255) NOT NULL,
    CHANGE COLUMN role tipo_usuario ENUM('Admin','Gerente','Colaborador','Cliente') DEFAULT 'Colaborador',
    ADD COLUMN telefone VARCHAR(30) NULL AFTER email,
    ADD COLUMN cargo VARCHAR(120) NULL AFTER telefone,
    ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER cargo,
    ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER foto_perfil,
    ADD COLUMN data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER ativo,
    ADD COLUMN ultimo_acesso TIMESTAMP NULL AFTER data_cadastro,
    ADD COLUMN senha_atualizada_em TIMESTAMP NULL AFTER ultimo_acesso,
    ADD COLUMN deleted_at DATETIME NULL AFTER senha_atualizada_em;

ALTER TABLE users
    ADD COLUMN password_reset_token VARCHAR(191) NULL AFTER password,
    ADD COLUMN password_reset_expires DATETIME NULL AFTER password_reset_token;

/* =====================
   Logs e sessões
   ===================== */
CREATE TABLE IF NOT EXISTS user_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    acao ENUM('create','update','delete','login','logout','password_reset','status_change','role_change') NOT NULL,
    detalhes JSON NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_audit_usuario (usuario_id),
    INDEX idx_user_audit_acao (acao)
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

SET FOREIGN_KEY_CHECKS = 1;
