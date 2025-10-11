<?php
require_once __DIR__ . '/../Models/ClientModel.php';
require_once __DIR__ . '/../Models/ProjectModel.php';
require_once __DIR__ . '/../Models/PaymentModel.php';

class DashboardController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['user_id'])) { Utils::redirect('/auth/login'); }
    }

    /* -------- helpers de status -------- */
    private function seedStatusesIfMissing(): void {
        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM status_catalog")->fetchColumn();
        if ($count > 0) return;

        $ins = $this->pdo->prepare("
          INSERT INTO status_catalog (name, color_hex, sort_order, created_at)
          VALUES (?, ?, ?, NOW())
        ");
        $defs = [
          ['Pending', '#f6c23e'],
          ['Paid',    '#1cc88a'],
          ['Overdue', '#e74a3b'],
          ['Dropped', '#858796'],
        ];
        $ord = 1;
        foreach ($defs as [$name,$color]) {
            $ins->execute([$name, $color, $ord++]);
        }
    }

    private function statusDistribution(): array {
        // retorna labels, counts e cores para TODOS os status do catalogo (zeros inclusos)
        $sql = "
          SELECT
            s.name AS status_name,
            COALESCE(s.color_hex, '#D2EB17') AS color_hex,
            COALESCE(COUNT(p.id),0) AS c
          FROM status_catalog s
          LEFT JOIN projects p ON p.status_id = s.id
          GROUP BY s.id, s.name
          ORDER BY s.sort_order, s.name
        ";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $labels = []; $counts = []; $colors = [];
        foreach ($rows as $r) {
            $labels[] = $r['status_name'];
            $counts[] = (int)$r['c'];
            $colors[] = $r['color_hex'];
        }
        return [$labels, $counts, $colors];
    }

    /* -------- helpers de período/metas -------- */
    private function rangeFor(string $period): array {
        $today = new DateTimeImmutable('today');
        switch ($period) {
            case 'daily':     return [$today->format('Y-m-d'), $today->format('Y-m-d')];
            case 'weekly':    $s=$today->modify('monday this week'); $e=$today->modify('sunday this week'); return [$s->format('Y-m-d'), $e->format('Y-m-d')];
            case 'monthly':   $s=$today->modify('first day of this month'); $e=$today->modify('last day of this month'); return [$s->format('Y-m-d'), $e->format('Y-m-d')];
            case 'quarterly': $m=(int)$today->format('n'); $q=intdiv($m-1,3); $s=(new DateTimeImmutable($today->format('Y-01-01')))->modify('+'.($q*3).' months'); $e=$s->modify('+2 months')->modify('last day of this month'); return [$s->format('Y-m-d'), $e->format('Y-m-d')];
            default:          return [$today->format('Y-m-d'), $today->format('Y-m-d')];
        }
    }

    private function paidSumBetween(string $from, string $to): float {
        $st = $this->pdo->prepare("
            SELECT COALESCE(SUM(amount),0)
            FROM payments
            WHERE paid_at BETWEEN :a AND :b
        ");
        $st->execute([':a'=>$from, ':b'=>$to]);
        return (float)$st->fetchColumn();
    }

    private function paymentsSummary(): array {
        $sql = "
            SELECT
              SUM(CASE WHEN (COALESCE(p.transaction_type,'receita') = 'receita') AND LOWER(s.name) IN ('recebido','paid') AND (p.currency = 'BRL' OR p.currency IS NULL)
                       THEN p.amount ELSE 0 END) AS receita_recebida,
              SUM(CASE WHEN COALESCE(p.transaction_type,'receita') = 'despesa' AND LOWER(s.name) IN ('pago','paid') AND (p.currency = 'BRL' OR p.currency IS NULL)
                       THEN p.amount ELSE 0 END) AS despesa_paga,
              SUM(CASE WHEN LOWER(s.name) IN ('cancelado','dropped') AND (p.currency = 'BRL' OR p.currency IS NULL)
                       THEN p.amount ELSE 0 END) AS valor_perdido,
              SUM(CASE
                    WHEN (COALESCE(p.transaction_type,'receita') = 'receita') AND LOWER(s.name) IN ('a receber','em atraso','pending','overdue') AND (p.currency = 'BRL' OR p.currency IS NULL)
                      THEN p.amount
                    WHEN COALESCE(p.transaction_type,'receita') = 'despesa' AND LOWER(s.name) IN ('pendente','vencido','parcelado','pending','overdue') AND (p.currency = 'BRL' OR p.currency IS NULL)
                      THEN p.amount
                    ELSE 0
                  END) AS valor_pendente
            FROM payments p
            LEFT JOIN status_catalog s ON s.id = p.status_id
        ";

        $row = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'receita_recebida' => (float)($row['receita_recebida'] ?? 0),
            'despesa_paga'     => (float)($row['despesa_paga'] ?? 0),
            'valor_perdido'    => (float)($row['valor_perdido'] ?? 0),
            'valor_pendente'   => (float)($row['valor_pendente'] ?? 0),
        ];
    }

    private function monthlyCash(): array {
        $start = new DateTimeImmutable('first day of this month');
        $end = new DateTimeImmutable('last day of this month');

        $sql = "
            SELECT
              SUM(CASE WHEN (COALESCE(p.transaction_type,'receita') = 'receita') AND LOWER(s.name) IN ('recebido','paid') AND p.paid_at IS NOT NULL AND p.paid_at BETWEEN :ini AND :fim AND (p.currency = 'BRL' OR p.currency IS NULL)
                       THEN p.amount ELSE 0 END) AS receita_mes,
              SUM(CASE WHEN COALESCE(p.transaction_type,'receita') = 'despesa' AND LOWER(s.name) IN ('pago','paid') AND p.paid_at IS NOT NULL AND p.paid_at BETWEEN :ini AND :fim AND (p.currency = 'BRL' OR p.currency IS NULL)
                       THEN p.amount ELSE 0 END) AS despesa_mes
            FROM payments p
            LEFT JOIN status_catalog s ON s.id = p.status_id
        ";
        $st = $this->pdo->prepare($sql);

        $st->bindValue(':ini', $start->format('Y-m-d'));
        $st->bindValue(':fim', $end->format('Y-m-d'));
        $st->execute();

        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'receita_mes' => (float)($row['receita_mes'] ?? 0),
            'despesa_mes' => (float)($row['despesa_mes'] ?? 0),
        ];
    }

    private function goalTarget(string $period): float {
        // tenta pegar a meta mais recente para o tipo
        $st = $this->pdo->prepare("SELECT target_value FROM goals WHERE period_type = :p ORDER BY id DESC LIMIT 1");
        $st->execute([':p'=>$period]);
        return (float)($st->fetchColumn() ?: 0);
    }

    private function goalCard(string $label, string $period): array {
        [$d1,$d2] = $this->rangeFor($period);
        $alvo = $this->goalTarget($period);
        $real = $this->paidSumBetween($d1,$d2);
        // garante render do donut mesmo sem dados
        if ($alvo == 0 && $real == 0) { $alvo = 1; $real = 0; }
        return ['label'=>$label, 'alvo'=>$alvo, 'real'=>$real];
    }

    private function lastMonthsPaidSeries(int $monthsBack = 6): array {
        $end = new DateTimeImmutable('first day of this month');
        $start = $end->modify('-' . ($monthsBack - 1) . ' months');

        $labels = []; $series = []; $map = [];
        $c = $start;
        for ($i=0; $i<$monthsBack; $i++) {
            $k = $c->format('Y-m');
            $labels[] = $c->format('m/Y');
            $series[] = 0.0;
            $map[$k] = $i;
            $c = $c->modify('+1 month');
        }

        $st = $this->pdo->prepare("
            SELECT DATE_FORMAT(paid_at, '%Y-%m') AS ym, COALESCE(SUM(amount),0) AS total
            FROM payments
            WHERE paid_at IS NOT NULL
              AND paid_at >= :dini
              AND paid_at <= LAST_DAY(:dend)
              AND (currency = 'BRL' OR currency IS NULL)
            GROUP BY ym
            ORDER BY ym
        ");
        $st->execute([':dini'=>$start->format('Y-m-01'), ':dend'=>$end->format('Y-m-01')]);
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            if (isset($map[$r['ym']])) $series[$map[$r['ym']]] = (float)$r['total'];
        }
        return [$labels, $series];
    }

    public function index() {
        $this->seedStatusesIfMissing();

        $clientModel  = new ClientModel($this->pdo);
        $projectModel = new ProjectModel($this->pdo);
        $paymentModel = new PaymentModel($this->pdo);

        $summary = $this->paymentsSummary();
        $monthly = $this->monthlyCash();
        $caixaGeral = $summary['receita_recebida'] - $summary['despesa_paga'];
        $caixaMensal = $monthly['receita_mes'] - $monthly['despesa_mes'];

        $kpiCards = [
            [
                'label'  => 'Receita',
                'helper' => 'Receitas recebidas (BRL)',
                'icon'   => 'arrow-up',
                'accent' => 'success',
                'raw'    => $summary['receita_recebida'],
                'value'  => Utils::formatMoney($summary['receita_recebida']),
                'type'   => 'money',
            ],
            [
                'label'  => 'Despesa',
                'helper' => 'Despesas pagas (BRL)',
                'icon'   => 'arrow-down',
                'accent' => 'danger',
                'raw'    => $summary['despesa_paga'],
                'value'  => Utils::formatMoney($summary['despesa_paga']),
                'type'   => 'money',
            ],
            [
                'label'  => 'Valor perdido',
                'helper' => 'Transações canceladas',
                'icon'   => 'ban',
                'accent' => 'secondary',
                'raw'    => $summary['valor_perdido'],
                'value'  => Utils::formatMoney($summary['valor_perdido']),
                'type'   => 'money',
            ],
            [
                'label'  => 'Valor pendente',
                'helper' => 'A receber / pagar',
                'icon'   => 'hourglass-half',
                'accent' => 'warning',
                'raw'    => $summary['valor_pendente'],
                'value'  => Utils::formatMoney($summary['valor_pendente']),
                'type'   => 'money',
            ],
            [
                'label'  => 'Caixa geral',
                'helper' => 'Receita - Despesa',
                'icon'   => 'wallet',
                'accent' => 'info',
                'raw'    => $caixaGeral,
                'value'  => Utils::formatMoney($caixaGeral),
                'type'   => 'money',
            ],
            [
                'label'  => 'Caixa mensal',
                'helper' => 'Fluxo do mês atual',
                'icon'   => 'calendar-check',
                'accent' => 'primary',
                'raw'    => $caixaMensal,
                'value'  => Utils::formatMoney($caixaMensal),
                'type'   => 'money',
            ],
        ];

        // Série últimos 6 meses
        [$months, $values] = $this->lastMonthsPaidSeries(6);

        // Status dos projetos (com zeros)
        [$statusLabels, $statusCounts, $statusColors] = $this->statusDistribution();

        // Últimos pagamentos (pode ser vazio, mas variável sempre definida)
        $recentPayments = $this->pdo->query("
            SELECT p.id, p.amount, p.currency, p.transaction_type, p.description, p.category,
                   p.due_date, p.paid_at,
                   COALESCE(s.name,'(Undefined)') AS status,
                   pr.name AS project_name
            FROM payments p
            LEFT JOIN projects pr ON p.project_id = pr.id
            LEFT JOIN status_catalog s ON s.id = p.status_id
            ORDER BY COALESCE(p.paid_at, p.due_date) DESC, p.id DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Metas – sempre 4 cartões
        $goalsCards = [
            $this->goalCard('Diária',     'daily'),
            $this->goalCard('Semanal',    'weekly'),
            $this->goalCard('Mensal',     'monthly'),
            $this->goalCard('Trimestral', 'quarterly'),
        ];

        include __DIR__ . '/../Views/dashboard.php';
    }
}
