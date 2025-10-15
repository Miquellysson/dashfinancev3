-- Migration: align payments table with collection board requirements

-- Ensure payments.client_id exists
SET @missing_client_column := (
    SELECT COUNT(*) = 0
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'payments'
      AND COLUMN_NAME = 'client_id'
);

SET @sql := IF(
    @missing_client_column,
    'ALTER TABLE payments ADD COLUMN client_id INT NULL AFTER project_id;',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure payments.notes exists
SET @missing_notes_column := (
    SELECT COUNT(*) = 0
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'payments'
      AND COLUMN_NAME = 'notes'
);

SET @sql := IF(
    @missing_notes_column,
    'ALTER TABLE payments ADD COLUMN notes TEXT NULL AFTER category;',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure FK to clients exists
SET @missing_client_fk := (
    SELECT COUNT(*) = 0
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'payments'
      AND CONSTRAINT_NAME = 'fk_payments_clients'
);

SET @sql := IF(
    @missing_client_fk,
    'ALTER TABLE payments ADD CONSTRAINT fk_payments_clients FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL;',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure status_catalog has new statuses
INSERT INTO status_catalog (name, color_hex, sort_order)
VALUES
    ('Em Cobrança', '#f97316', 13),
    ('Perdido', '#6B7280', 14)
ON DUPLICATE KEY UPDATE color_hex = VALUES(color_hex), sort_order = VALUES(sort_order);

