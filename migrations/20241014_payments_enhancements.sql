-- Migration: Enhance payments table with classification fields

ALTER TABLE payments
    ADD COLUMN transaction_type ENUM('receita','despesa') NOT NULL DEFAULT 'receita' AFTER kind,
    ADD COLUMN description VARCHAR(255) NULL AFTER transaction_type,
    ADD COLUMN category VARCHAR(120) NULL AFTER description;

UPDATE payments
SET transaction_type = 'receita'
WHERE transaction_type IS NULL;
