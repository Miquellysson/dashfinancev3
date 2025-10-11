-- Reserva Financeira - estrutura inicial
-- Executar em MySQL 5.7+ / MariaDB 10+

CREATE TABLE IF NOT EXISTS financial_reserve_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_type ENUM('deposit','withdraw') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reference_date DATE NOT NULL,
    description VARCHAR(180) NULL,
    category VARCHAR(120) NULL,
    notes TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_financial_reserve_date (reference_date),
    INDEX idx_financial_reserve_type (operation_type),
    CONSTRAINT fk_financial_reserve_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

