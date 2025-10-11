<?php
class GoalModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* -----------------------
       Helpers
       ----------------------- */

    /** Infere period_type a partir de um título PT/EN (compatibilidade). */
    private function inferPeriodTypeFromTitle(?string $title): ?string {
        if (!$title) return null;
        $t = mb_strtolower(trim($title), 'UTF-8');
        if (strpos($t, 'diári') !== false || strpos($t, 'diaria') !== false || strpos($t, 'daily') !== false) return 'daily';
        if (strpos($t, 'seman') !== false || strpos($t, 'weekly') !== false) return 'weekly';
        if (strpos($t, 'quinzen') !== false || strpos($t, 'biweekly') !== false) return 'biweekly';
        if (strpos($t, 'mens') !== false || strpos($t, 'mês') !== false || strpos($t, 'mes') !== false || strpos($t, 'monthly') !== false) return 'monthly';
        if (strpos($t, 'trimes') !== false || strpos($t, 'quarter') !== false) return 'quarterly';
        return null;
    }

    /** Calcula período (start/end) atual a partir de period_type. */
    private function currentRangeFor(string $periodType): array {
        $today = new DateTimeImmutable('today');
        switch ($periodType) {
            case 'daily':
                return [$today->format('Y-m-d'), $today->format('Y-m-d')];
            case 'weekly':
                $start = $today->modify('monday this week');
                $end   = $today->modify('sunday this week');
                return [$start->format('Y-m-d'), $end->format('Y-m-d')];
            case 'biweekly': {
                // 1ª ou 2ª quinzena do mês corrente
                $firstDay = $today->modify('first day of this month');
                $midDay   = $firstDay->modify('+14 days'); // dia 15
                if ($today <= $midDay) {
                    $start = $firstDay;
                    $end   = $midDay;
                } else {
                    $start = $midDay->modify('+1 day');
                    $end   = $today->modify('last day of this month');
                }
                return [$start->format('Y-m-d'), $end->format('Y-m-d')];
            }
            case 'monthly':
                $start = $today->modify('first day of this month');
                $end   = $today->modify('last day of this month');
                return [$start->format('Y-m-d'), $end->format('Y-m-d')];
            case 'quarterly': {
                $m = (int)$today->format('n');
                $q = intdiv($m - 1, 3); // 0..3
                $start = (new DateTimeImmutable($today->format('Y-01-01')))->modify('+' . ($q * 3) . ' months');
                $end   = $start->modify('+2 months')->modify('last day of this month');
                return [$start->format('Y-m-d'), $end->format('Y-m-d')];
            }
            default:
                return [$today->format('Y-m-d'), $today->format('Y-m-d')];
        }
    }

    /** Normaliza payload antigo -> novo schema. */
    private function normalizeData(array $data, bool $isUpdate = false): array {
        // period_type pode vir direto já correto:
        $periodType = $data['period_type'] ?? null;

        // Compat: tentar inferir por 'title'
        if (!$periodType && isset($data['title'])) {
            $periodType = $this->inferPeriodTypeFromTitle($data['title']);
        }

        // Compat: se vier algo tipo 'periodo_tipo' PT-BR
        if (!$periodType && isset($data['periodo_tipo'])) {
            $map = [
                'diaria' => 'daily',
                'semanal' => 'weekly',
                'quinzenal' => 'biweekly',
                'mensal' => 'monthly',
                'trimestral' => 'quarterly',
            ];
            $pt = mb_strtolower(trim($data['periodo_tipo']), 'UTF-8');
            $periodType = $map[$pt] ?? null;
        }

        // target_value
        $target = $data['target_value'] ?? ($data['target_amount'] ?? $data['valor_meta'] ?? 0);

        // period_start / period_end
        $periodStart = $data['period_start'] ?? ($data['periodo_inicio'] ?? null);
        $periodEnd   = $data['period_end']   ?? ($data['periodo_fim']    ?? null);

        // Compat: se só veio 'target_date', usa como period_end e deduz o start
        if (!$periodStart && !$periodEnd && isset($data['target_date']) && $data['target_date']) {
            $periodEnd = $data['target_date'];
        }

        // Se ainda faltou start/end, e temos period_type, usa intervalo atual
        if ($periodType && (!$periodStart || !$periodEnd)) {
            [$s, $e] = $this->currentRangeFor($periodType);
            $periodStart = $periodStart ?: $s;
            $periodEnd   = $periodEnd   ?: $e;
        }

        return [
            'period_type'  => $periodType ?? 'monthly', // default razoável
            'period_start' => $periodStart ?? $this->currentRangeFor('monthly')[0],
            'period_end'   => $periodEnd   ?? $this->currentRangeFor('monthly')[1],
            'target_value' => (float)$target,
        ];
    }

    /* -----------------------
       Consultas
       ----------------------- */

    public function all() {
        $stmt = $this->pdo->query("
            SELECT id, period_type, period_start, period_end, target_value, created_at, updated_at
            FROM goals
            ORDER BY period_start DESC, id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("
            SELECT id, period_type, period_start, period_end, target_value, created_at, updated_at
            FROM goals
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $d = $this->normalizeData($data);

        $sql = "INSERT INTO goals
                    (period_type, period_start, period_end, target_value, created_at, updated_at)
                VALUES
                    (:period_type, :period_start, :period_end, :target_value, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':period_type'  => $d['period_type'],
            ':period_start' => $d['period_start'],
            ':period_end'   => $d['period_end'],
            ':target_value' => $d['target_value'],
        ]);
    }

    public function update($id, $data) {
        $d = $this->normalizeData($data, true);

        $sql = "UPDATE goals SET
                    period_type  = :period_type,
                    period_start = :period_start,
                    period_end   = :period_end,
                    target_value = :target_value,
                    updated_at   = NOW()
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':period_type'  => $d['period_type'],
            ':period_start' => $d['period_start'],
            ':period_end'   => $d['period_end'],
            ':target_value' => $d['target_value'],
            ':id'           => (int)$id,
        ]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM goals WHERE id = :id");
        return $stmt->execute([':id' => (int)$id]);
    }
}
