-- Migration: Add currency to payments

ALTER TABLE payments
    ADD COLUMN currency CHAR(3) NOT NULL DEFAULT 'BRL' AFTER amount;

UPDATE payments
SET currency = 'BRL'
WHERE currency IS NULL OR currency = '';
