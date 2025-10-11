-- Align schema with current PHP models (status catalog, clients, goals).
-- Execute in MySQL (5.7+). Statements are idempotent and skip if already applied.

/* =====================
   status_catalog
   ===================== */
CREATE TABLE IF NOT EXISTS status_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    color_hex CHAR(7) NOT NULL DEFAULT '#6b7280',
    sort_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_status_catalog_name (name)
);

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

/* =====================
   clients: entry_date / notes
   ===================== */
SET @missing_entry_date := (
    SELECT COUNT(*) = 0
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'clients'
      AND COLUMN_NAME = 'entry_date'
);

SET @sql := IF(@missing_entry_date, 'ALTER TABLE clients ADD COLUMN entry_date DATE NULL AFTER address;', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @missing_notes := (
    SELECT COUNT(*) = 0
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'clients'
      AND COLUMN_NAME = 'notes'
);

SET @sql := IF(@missing_notes, 'ALTER TABLE clients ADD COLUMN notes TEXT NULL AFTER entry_date;', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

/* =====================
   goals: new period fields
   ===================== */
SET @missing_period_type := (
    SELECT COUNT(*) = 0
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'goals'
      AND COLUMN_NAME = 'period_type'
);

SET @sql := IF(
    @missing_period_type,
    'ALTER TABLE goals
        ADD COLUMN period_type ENUM(''daily'',''weekly'',''biweekly'',''monthly'',''quarterly'') NOT NULL DEFAULT ''monthly'' AFTER id,
        ADD COLUMN period_start DATE NOT NULL DEFAULT ''1970-01-01'' AFTER period_type,
        ADD COLUMN period_end DATE NOT NULL DEFAULT ''1970-01-01'' AFTER period_start,
        ADD COLUMN target_value DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER period_end;',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


/* =====================
   payments: ensure status_id exists & FK ready
   ===================== */
SET @missing_status_id := (
    SELECT COUNT(*) = 0
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'payments'
      AND COLUMN_NAME = 'status_id'
);

SET @sql := IF(@missing_status_id,
    'ALTER TABLE payments ADD COLUMN status_id INT NULL AFTER paid_at;',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @missing_fk := (
    SELECT COUNT(*) = 0
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'payments'
      AND CONSTRAINT_NAME = 'fk_payments_status_catalog'
);

SET @sql := IF(@missing_fk,
    'ALTER TABLE payments ADD CONSTRAINT fk_payments_status_catalog FOREIGN KEY (status_id) REFERENCES status_catalog(id) ON DELETE SET NULL;',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
