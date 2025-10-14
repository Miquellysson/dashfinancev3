<?php

class CollectionBoardModel {
    private PDO $pdo;

    private const BOARD_COLUMNS = ['a_vencer','vencendo','vencido','em_cobranca','perdido'];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function fetchBoard(array $filters = []): array {
        $rows = $this->fetchPaymentsWithCard($filters);
        $today = new DateTimeImmutable('today');

        $columns = array_fill_keys(self::BOARD_COLUMNS, [
            'cards' => [],
            'totals' => [
                'amount' => 0.0,
                'clients' => 0,
                'cards' => 0,
            ],
            'client_ids' => [],
        ]);
        $overdueByClient = [];
        $orderBy = $filters['order_by'] ?? 'due_date';
        $orderDir = strtolower($filters['order_dir'] ?? 'asc');
        if (!in_array($orderDir, ['asc','desc'], true)) {
            $orderDir = 'asc';
        }

        $summary = [
            'total_amount' => 0.0,
            'total_cards' => 0,
            'due_today' => 0,
            'due_today_amount' => 0.0,
            'overdue_amount' => 0.0,
        ];

        foreach ($rows as $row) {
            $cardStatus = $this->resolveEffectiveStatus($row, $today);
            if ($cardStatus === null) {
                continue;
            }

            if ($filters['column'] ?? null) {
                if ($filters['column'] !== $cardStatus) {
                    continue;
                }
            }

            $card = $this->buildCardPayload($row, $cardStatus, $today);
            $clientId = $row['client_id'] ?? null;

            $columns[$cardStatus]['cards'][] = $card;
            $columns[$cardStatus]['totals']['amount'] += $card['amount'];
            $columns[$cardStatus]['totals']['cards']++;
            if ($clientId) {
                $columns[$cardStatus]['client_ids'][$clientId] = true;
                if ($card['days_overdue'] > 0 || $cardStatus === 'em_cobranca') {
                    $overdueByClient[$clientId] = ($overdueByClient[$clientId] ?? 0) + 1;
                }
            }

            if ($cardStatus !== 'perdido') {
                $summary['total_amount'] += $card['amount'];
                $summary['total_cards']++;
                if ($card['days_until_due'] === 0) {
                    $summary['due_today']++;
                    $summary['due_today_amount'] += $card['amount'];
                }
                if ($card['days_overdue'] > 0) {
                    $summary['overdue_amount'] += $card['amount'];
                }
            }
        }

        foreach ($columns as $status => &$data) {
            $data['totals']['amount'] = round($data['totals']['amount'], 2);
            $data['totals']['clients'] = count($data['client_ids']);
            unset($data['client_ids']);

            usort($data['cards'], function ($a, $b) use ($status, $orderBy, $orderDir) {
                if ($status === 'perdido') {
                    $aTime = $a['lost_at'] ?? $a['moved_at'] ?? '';
                    $bTime = $b['lost_at'] ?? $b['moved_at'] ?? '';
                    return strcmp($bTime, $aTime);
                }

                $multiplier = $orderDir === 'desc' ? -1 : 1;

                switch ($orderBy) {
                    case 'amount':
                        return $multiplier * ($a['amount'] <=> $b['amount']);
                    case 'client_name':
                        $cmp = strcmp(mb_strtolower($a['client_name']), mb_strtolower($b['client_name']));
                        if ($cmp === 0) {
                            return $multiplier * ($a['amount'] <=> $b['amount']);
                        }
                        return $multiplier * $cmp;
                    case 'days_overdue':
                        $cmpOverdue = $a['days_overdue'] <=> $b['days_overdue'];
                        if ($cmpOverdue === 0) {
                            return $multiplier * ($a['amount'] <=> $b['amount']);
                        }
                        return $multiplier * $cmpOverdue;
                    case 'due_date':
                    default:
                        $aDue = $a['due_date'] ? strtotime($a['due_date']) : null;
                        $bDue = $b['due_date'] ? strtotime($b['due_date']) : null;
                        if ($aDue === $bDue) {
                            $cmpOverdue = $a['days_overdue'] <=> $b['days_overdue'];
                            if ($cmpOverdue === 0) {
                                return $multiplier * ($a['amount'] <=> $b['amount']);
                            }
                            return $multiplier * $cmpOverdue;
                        }
                        if ($aDue === null) {
                            return 1;
                        }
                        if ($bDue === null) {
                            return -1;
                        }
                        return $multiplier * ($aDue <=> $bDue);
                }
            });

            foreach ($data['cards'] as &$card) {
                $clientId = $card['client_id'] ?? null;
                if ($clientId && ($overdueByClient[$clientId] ?? 0) > 1) {
                    if (!in_array('atencao', $card['badges'], true)) {
                        $card['badges'][] = 'atencao';
                    }
                }
            }
            unset($card);
        }
        unset($data);

        $summary['total_amount'] = round($summary['total_amount'], 2);
        $summary['due_today_amount'] = round($summary['due_today_amount'], 2);
        $summary['overdue_amount'] = round($summary['overdue_amount'], 2);

        return [
            'columns' => $columns,
            'summary' => $summary,
            'filters_used' => $filters,
        ];
    }

    public function ensureCard(int $paymentId, ?int $userId = null): array {
        $card = $this->getCardByPayment($paymentId);
        if ($card) {
            return $card;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO collection_cards (payment_id, status_since, created_by, updated_by)
            VALUES (:payment_id, NOW(), :created_by, :updated_by)
        ");
        $stmt->execute([
            ':payment_id' => $paymentId,
            ':created_by' => $userId,
            ':updated_by' => $userId,
        ]);

        return $this->getCardByPayment($paymentId);
    }

    public function getCardByPayment(int $paymentId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM collection_cards
            WHERE payment_id = :payment_id
            LIMIT 1
        ");
        $stmt->execute([':payment_id' => $paymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getPaymentWithCard(int $paymentId): ?array {
        $stmt = $this->pdo->prepare($this->baseBoardQuery() . " AND p.id = :payment_id LIMIT 1");
        $stmt->execute([':payment_id' => $paymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateManualStatus(int $paymentId, string $targetStatus, array $options, int $userId): array {
        if (!in_array($targetStatus, self::BOARD_COLUMNS, true)) {
            throw new InvalidArgumentException('Status inválido.');
        }

        $paymentRow = $this->getPaymentWithCard($paymentId);
        if (!$paymentRow) {
            throw new RuntimeException('Pagamento não encontrado.');
        }

        $today = new DateTimeImmutable('today');
        $cardRow = $this->ensureCard($paymentId, $userId);
        $currentStatus = $this->resolveEffectiveStatus(array_merge($paymentRow, $cardRow), $today);

        $this->applyManualStatusChange($cardRow, $paymentId, $targetStatus, $options, $userId);

        $this->recordMovement(
            (int)$cardRow['id'],
            $paymentId,
            $currentStatus,
            $targetStatus,
            $options['reason_code'] ?? null,
            $options['notes'] ?? null,
            $userId
        );

        $updated = $this->getPaymentWithCard($paymentId);
        return [
            'card' => $this->getCardByPayment($paymentId),
            'payment' => $updated,
            'status' => $this->resolveEffectiveStatus($updated, $today),
        ];
    }

    public function recordMovement(int $cardId, int $paymentId, ?string $fromStatus, string $toStatus, ?string $reasonCode, ?string $notes, ?int $userId): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO collection_movements (card_id, payment_id, from_status, to_status, reason_code, notes, created_by, created_at)
            VALUES (:card_id, :payment_id, :from_status, :to_status, :reason_code, :notes, :created_by, NOW())
        ");
        $stmt->execute([
            ':card_id' => $cardId,
            ':payment_id' => $paymentId,
            ':from_status' => $fromStatus,
            ':to_status' => $toStatus,
            ':reason_code' => $reasonCode,
            ':notes' => $notes,
            ':created_by' => $userId,
        ]);
    }

    public function addContact(int $paymentId, array $payload, int $userId): array {
        $paymentRow = $this->getPaymentWithCard($paymentId);
        if (!$paymentRow) {
            throw new RuntimeException('Pagamento não encontrado.');
        }

        $cardRow = $this->ensureCard($paymentId, $userId);
        $contactDate = $payload['contacted_at'] ?? (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare("
            INSERT INTO collection_contacts (
                card_id, payment_id, contact_type, contacted_at,
                client_response, expected_payment_at, notes, is_reminder, created_by, created_at
            ) VALUES (
                :card_id, :payment_id, :contact_type, :contacted_at,
                :client_response, :expected_payment_at, :notes, :is_reminder, :created_by, NOW()
            )
        ");
        $stmt->execute([
            ':card_id' => $cardRow['id'],
            ':payment_id' => $paymentId,
            ':contact_type' => $payload['contact_type'],
            ':contacted_at' => $contactDate,
            ':client_response' => $payload['client_response'] ?? null,
            ':expected_payment_at' => $payload['expected_payment_at'] ?? null,
            ':notes' => $payload['notes'] ?? null,
            ':is_reminder' => !empty($payload['is_reminder']) ? 1 : 0,
            ':created_by' => $userId,
        ]);

        $this->touchLastContact(
            (int)$cardRow['id'],
            $contactDate,
            $payload['contact_type'],
            $payload['notes'] ?? null,
            $userId
        );

        $contactId = (int)$this->pdo->lastInsertId();
        $contact = $this->getContactById($contactId);

        if (!empty($payload['auto_move_to']) && $payload['auto_move_to'] === 'em_cobranca') {
            $this->updateManualStatus($paymentId, 'em_cobranca', [
                'reason_code' => 'auto_contact',
                'notes' => 'Movido automaticamente após registrar contato.',
            ], $userId);
        }

        return $contact;
    }

    public function listContacts(int $paymentId): array {
        $stmt = $this->pdo->prepare("
            SELECT cc.*, u.nome_completo AS autor_nome
            FROM collection_contacts cc
            LEFT JOIN users u ON u.id = cc.created_by
            WHERE cc.payment_id = :payment_id
            ORDER BY cc.contacted_at DESC
        ");
        $stmt->execute([':payment_id' => $paymentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCardDetails(int $paymentId): ?array {
        $row = $this->getPaymentWithCard($paymentId);
        if (!$row) {
            return null;
        }
        $today = new DateTimeImmutable('today');
        $status = $this->resolveEffectiveStatus($row, $today);
        if ($status === null) {
            return null;
        }
        $card = $this->buildCardPayload($row, $status, $today);
        return $card;
    }

    public function listMovements(int $paymentId): array {
        $stmt = $this->pdo->prepare("
            SELECT cm.*, u.nome_completo AS autor_nome
            FROM collection_movements cm
            LEFT JOIN users u ON u.id = cm.created_by
            WHERE cm.payment_id = :payment_id
            ORDER BY cm.created_at DESC
        ");
        $stmt->execute([':payment_id' => $paymentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchPaymentsWithCard(array $filters): array {
        $sql = $this->baseBoardQuery();
        $conditions = [];
        $params = [];

        if (!empty($filters['client'])) {
            $conditions[] = 'c.name LIKE :client';
            $params[':client'] = '%' . $filters['client'] . '%';
        }

        if (!empty($filters['project'])) {
            $conditions[] = 'pr.name LIKE :project';
            $params[':project'] = '%' . $filters['project'] . '%';
        }

        if (!empty($filters['responsavel_id'])) {
            $conditions[] = 'pr.usuario_responsavel_id = :responsavel';
            $params[':responsavel'] = (int)$filters['responsavel_id'];
        }

        if (!empty($filters['value_min'])) {
            $conditions[] = 'p.amount >= :value_min';
            $params[':value_min'] = (float)$filters['value_min'];
        }

        if (!empty($filters['value_max'])) {
            $conditions[] = 'p.amount <= :value_max';
            $params[':value_max'] = (float)$filters['value_max'];
        }

        if (!empty($filters['due_from'])) {
            $conditions[] = 'p.due_date >= :due_from';
            $params[':due_from'] = $filters['due_from'];
        }

        if (!empty($filters['due_to'])) {
            $conditions[] = 'p.due_date <= :due_to';
            $params[':due_to'] = $filters['due_to'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(p.description LIKE :search OR pr.name LIKE :search OR c.name LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if ($conditions) {
            $sql .= ' AND ' . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function baseBoardQuery(): string {
        return "
            SELECT
                p.id AS payment_id,
                p.project_id,
                p.kind,
                COALESCE(p.transaction_type, 'receita') AS transaction_type,
                p.description,
                p.category,
                p.amount,
                p.currency,
                p.due_date,
                p.paid_at,
                p.status_id,
                p.created_at AS payment_created_at,
                p.updated_at AS payment_updated_at,
                s.name AS status_name,
                pr.name AS project_name,
                pr.usuario_responsavel_id,
                pr.client_id,
                c.name AS client_name,
                c.email AS client_email,
                c.phone AS client_phone,
                card.id AS card_id,
                card.manual_status,
                card.status_since,
                card.last_contact_at,
                card.last_contact_channel,
                card.last_contact_notes,
                card.lost_reason,
                card.lost_details,
                card.lost_at,
                card.updated_at AS card_updated_at
            FROM payments p
            LEFT JOIN projects pr ON pr.id = p.project_id
            LEFT JOIN clients c ON c.id = pr.client_id
            LEFT JOIN status_catalog s ON s.id = p.status_id
            LEFT JOIN collection_cards card ON card.payment_id = p.id
            WHERE COALESCE(p.transaction_type, 'receita') = 'receita'
        ";
    }

    private function resolveEffectiveStatus(array $row, DateTimeImmutable $today): ?string {
        $manualStatus = $row['manual_status'] ?? null;
        if ($manualStatus) {
            return $manualStatus;
        }

        if (!empty($row['paid_at'])) {
            return null;
        }

        $statusName = strtolower(trim($row['status_name'] ?? ''));
        if (in_array($statusName, ['recebido', 'pago', 'cancelado', 'paid', 'cancelled'], true)) {
            return null;
        }

        $dueDateRaw = $row['due_date'] ?? null;
        $dueDate = $dueDateRaw ? DateTimeImmutable::createFromFormat('Y-m-d', $dueDateRaw) : null;

        if (!$dueDate) {
            return 'a_vencer';
        }

        $diffDays = (int)$today->diff($dueDate)->format('%r%a');
        if ($diffDays > 7) {
            return 'a_vencer';
        }
        if ($diffDays >= 0) {
            return 'vencendo';
        }
        return 'vencido';
    }

    private function buildCardPayload(array $row, string $status, DateTimeImmutable $today): array {
        $amount = (float)($row['amount'] ?? 0);
        $dueDateRaw = $row['due_date'] ?? null;
        $dueDate = $dueDateRaw ? DateTimeImmutable::createFromFormat('Y-m-d', $dueDateRaw) : null;
        $daysUntilDue = $dueDate ? (int)$today->diff($dueDate)->format('%r%a') : null;
        $daysOverdue = $daysUntilDue !== null ? max(0, -$daysUntilDue) : 0;

        $phoneDigits = Utils::normalizePhone($row['client_phone'] ?? null);
        $whatsAppLink = $phoneDigits ? 'https://wa.me/55' . $phoneDigits : null;

        $badges = [];
        if ($amount > 1000) {
            $badges[] = 'alto_valor';
        }
        if (($row['kind'] ?? 'one_time') === 'recurring') {
            $badges[] = 'parcelado';
        }

        return [
            'payment_id' => (int)$row['payment_id'],
            'project_id' => (int)($row['project_id'] ?? 0),
            'client_id' => (int)($row['client_id'] ?? 0),
            'status' => $status,
            'amount' => $amount,
            'amount_formatted' => Utils::formatMoney($amount),
            'currency' => strtoupper($row['currency'] ?? 'BRL'),
            'client_name' => $row['client_name'] ?? 'Cliente sem nome',
            'client_email' => $row['client_email'] ?? null,
            'client_phone' => Utils::formatPhone($phoneDigits),
            'whatsapp_link' => $whatsAppLink,
            'project_name' => $row['project_name'] ?? null,
            'description' => $row['description'] ?? null,
            'due_date' => $dueDateRaw,
            'due_date_formatted' => $dueDate ? $dueDate->format('d/m/Y') : null,
            'days_until_due' => $daysUntilDue,
            'days_overdue' => $daysOverdue,
            'last_contact_at' => $row['last_contact_at'] ?? null,
            'last_contact_channel' => $row['last_contact_channel'] ?? null,
            'last_contact_notes' => $row['last_contact_notes'] ?? null,
            'lost_reason' => $row['lost_reason'] ?? null,
            'lost_details' => $row['lost_details'] ?? null,
            'lost_at' => $row['lost_at'] ?? null,
            'badges' => $badges,
            'moved_at' => $row['card_updated_at'] ?? $row['payment_updated_at'] ?? null,
        ];
    }

    private function applyManualStatusChange(array $cardRow, int $paymentId, string $targetStatus, array $options, int $userId): void {
        $fields = [
            'manual_status' => $targetStatus,
            'status_since' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'updated_by' => $userId,
            'updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        if ($targetStatus === 'perdido') {
            $fields['lost_reason'] = $options['lost_reason'] ?? null;
            $fields['lost_details'] = $options['lost_details'] ?? null;
            $fields['lost_at'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        } else {
            $fields['lost_reason'] = null;
            $fields['lost_details'] = null;
            $fields['lost_at'] = null;
        }

        $this->upsertCardFields((int)$cardRow['id'], $paymentId, $fields);
    }

    private function touchLastContact(int $cardId, string $contactDate, string $channel, ?string $notes, int $userId): void {
        $stmt = $this->pdo->prepare("
            UPDATE collection_cards
            SET last_contact_at = :contacted_at,
                last_contact_channel = :channel,
                last_contact_notes = :notes,
                updated_by = :user_id,
                updated_at = NOW()
            WHERE id = :card_id
        ");
        $stmt->execute([
            ':contacted_at' => $contactDate,
            ':channel' => $channel,
            ':notes' => $notes,
            ':user_id' => $userId,
            ':card_id' => $cardId,
        ]);
    }

    private function upsertCardFields(int $cardId, int $paymentId, array $fields): void {
        $setParts = [];
        $params = [];
        foreach ($fields as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[':' . $key] = $value;
        }
        $params[':card_id'] = $cardId;
        $params[':payment_id'] = $paymentId;

        $sql = "
            UPDATE collection_cards
            SET " . implode(', ', $setParts) . "
            WHERE id = :card_id AND payment_id = :payment_id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    private function getContactById(int $contactId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT cc.*, u.nome_completo AS autor_nome
            FROM collection_contacts cc
            LEFT JOIN users u ON u.id = cc.created_by
            WHERE cc.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $contactId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
