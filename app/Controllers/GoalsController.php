<?php
require_once __DIR__ . '/../Models/GoalModel.php';

class GoalsController {
    private $pdo;
    private $goalModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['user_id'])) { Utils::redirect('/auth/login'); }
        $this->goalModel = new GoalModel($pdo);
    }

    private function sumPaidBetween(string $from, string $to): float {
        $st = $this->pdo->prepare("
            SELECT COALESCE(SUM(amount),0)
            FROM payments
            WHERE paid_at BETWEEN :a AND :b
        ");
        $st->execute([':a'=>$from, ':b'=>$to]);
        return (float)$st->fetchColumn();
    }

    private function typeLabel(string $t): string {
        return match ($t) {
            'daily'     => 'Diária',
            'weekly'    => 'Semanal',
            'biweekly'  => 'Quinzenal',
            'monthly'   => 'Mensal',
            'quarterly' => 'Trimestral',
            default     => ucfirst($t),
        };
    }

    public function index() {
        $goals = $this->goalModel->all();

        // complementa com label e "Atual" (soma pagos no período)
        foreach ($goals as &$g) {
            $g['type_label'] = $this->typeLabel($g['period_type'] ?? '');
            $from = (string)($g['period_start'] ?? '');
            $to   = (string)($g['period_end'] ?? '');
            $g['current_value'] = ($from && $to) ? $this->sumPaidBetween($from, $to) : 0.0;
        }
        unset($g);

        include __DIR__ . '/../Views/goals/index.php';
    }

    public function create() {
        $goal = null;
        include __DIR__ . '/../Views/goals/form.php';
    }

    public function edit($id) {
        $goal = $this->goalModel->find((int)$id);
        include __DIR__ . '/../Views/goals/form.php';
    }

    public function save() {
        // Aceita payload novo e antigo (o model normaliza)
        $data = [
            'period_type'  => $_POST['period_type']  ?? null,
            'period_start' => $_POST['period_start'] ?? null,
            'period_end'   => $_POST['period_end']   ?? null,
            'target_value' => $_POST['target_value'] ?? null,

            // compat antigo:
            'title'         => $_POST['title']         ?? null,
            'target_amount' => $_POST['target_amount'] ?? null,
            'current_amount'=> $_POST['current_amount']?? null,
            'target_date'   => $_POST['target_date']   ?? null,
            'periodo_tipo'  => $_POST['periodo_tipo']  ?? null,
            'periodo_inicio'=> $_POST['periodo_inicio']?? null,
            'periodo_fim'   => $_POST['periodo_fim']   ?? null,
        ];

        if (!empty($_POST['id'])) {
            $ok = $this->goalModel->update((int)$_POST['id'], $data);
        } else {
            $ok = $this->goalModel->create($data);
        }
        Utils::redirect('/goals' . ($ok ? '' : '?error=1'));
    }

    public function delete($id) {
        $this->goalModel->delete((int)$id);
        Utils::redirect('/goals');
    }
}
