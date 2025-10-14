-- Migration: cobrança kanban support tables

CREATE TABLE IF NOT EXISTS collection_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    manual_status ENUM('a_vencer','vencendo','vencido','em_cobranca','perdido') DEFAULT NULL,
    status_since DATETIME DEFAULT NULL,
    last_contact_at DATETIME DEFAULT NULL,
    last_contact_channel ENUM('email','whatsapp','sms','ligacao','outro') DEFAULT NULL,
    last_contact_notes VARCHAR(255) DEFAULT NULL,
    lost_reason ENUM('cliente_nao_responde','cliente_recusa','empresa_fechou','valor_nao_compensa','outros') DEFAULT NULL,
    lost_details TEXT DEFAULT NULL,
    lost_at DATETIME DEFAULT NULL,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_collection_cards_payment (payment_id),
    CONSTRAINT fk_collection_card_payment
        FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_collection_card_created_by
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_collection_card_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS collection_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    payment_id INT NOT NULL,
    from_status ENUM('a_vencer','vencendo','vencido','em_cobranca','perdido') DEFAULT NULL,
    to_status ENUM('a_vencer','vencendo','vencido','em_cobranca','perdido') NOT NULL,
    reason_code VARCHAR(60) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_collection_move_card
        FOREIGN KEY (card_id) REFERENCES collection_cards(id) ON DELETE CASCADE,
    CONSTRAINT fk_collection_move_payment
        FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_collection_move_user
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_collection_movements_payment (payment_id),
    INDEX idx_collection_movements_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS collection_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    payment_id INT NOT NULL,
    contact_type ENUM('email','whatsapp','sms','ligacao','outro') NOT NULL,
    contacted_at DATETIME NOT NULL,
    client_response VARCHAR(255) DEFAULT NULL,
    expected_payment_at DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    is_reminder TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_collection_contact_card
        FOREIGN KEY (card_id) REFERENCES collection_cards(id) ON DELETE CASCADE,
    CONSTRAINT fk_collection_contact_payment
        FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_collection_contact_user
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_collection_contacts_payment (payment_id),
    INDEX idx_collection_contacts_contacted_at (contacted_at)
);
