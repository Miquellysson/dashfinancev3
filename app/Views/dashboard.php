<?php
$title = 'Dashboard';
ob_start();

$badgeForStatus = function ($status) {
    $s = mb_strtolower(trim((string)$status), 'UTF-8');
    return match ($s) {
        'paid', 'recebido', 'pago' => 'badge-soft-success',
        'pending', 'a receber', 'pendente', 'parcelado' => 'badge-soft-warning',
        'overdue', 'em atraso', 'vencido' => 'badge-soft-danger',
        'dropped', 'cancelado' => 'badge-soft-secondary',
        default   => 'badge-soft-secondary',
    };
};

$statusDisplayMap = [
    'paid'      => 'Recebido',
    'recebido'  => 'Recebido',
    'pago'      => 'Pago',
    'pending'   => 'Pendente',
    'pendente'  => 'Pendente',
    'overdue'   => 'Em Atraso',
    'em atraso' => 'Em Atraso',
    'dropped'   => 'Cancelado',
    'cancelado' => 'Cancelado',
    'a receber' => 'A Receber',
    'vencido'   => 'Vencido',
    'parcelado' => 'Parcelado',
];

$typeLabels = [
    'receita' => ['label' => 'Receita', 'badge' => 'badge-soft-success'],
    'despesa' => ['label' => 'Despesa', 'badge' => 'badge-soft-danger'],
];

$displayPaymentDate = function ($row) {
    $paid = $row['paid_at'] ?? null;
    $due  = $row['due_date'] ?? null;
    $date = $paid ?: $due;
    return $date ? Utils::formatDate($date) : '—';
};

$metaStructure = [
    ['key' => 'Diária',     'id' => 'metaDaily',     'title' => 'Meta Diária'],
    ['key' => 'Semanal',    'id' => 'metaWeekly',    'title' => 'Meta Semanal'],
    ['key' => 'Mensal',     'id' => 'metaMonthly',   'title' => 'Meta Mensal'],
    ['key' => 'Trimestral', 'id' => 'metaQuarterly', 'title' => 'Meta Trimestral'],
];

$calcProgress = static function (float $target, float $current): int {
    if ($target <= 0.0) {
        return $current > 0.0 ? 100 : 0;
    }
    return (int)min(100, round(($current / $target) * 100));
};

$metaCardsUi = [];
$goalsPayload = [];

foreach ($metaStructure as $metaInfo) {
    $raw = null;
    if (!empty($goalsCards) && is_array($goalsCards)) {
        foreach ($goalsCards as $candidate) {
            if (($candidate['label'] ?? '') === $metaInfo['key']) {
                $raw = $candidate;
                break;
            }
        }
    }

    $target = isset($raw['alvo']) ? (float)$raw['alvo'] : 0.0;
    $current = isset($raw['real']) ? (float)$raw['real'] : 0.0;
    $progress = $calcProgress($target, $current);
    $badgeVariant = $progress >= 100 ? 'max' : ($progress >= 60 ? 'mid' : 'low');

    $metaCardsUi[] = [
        'id'       => $metaInfo['id'],
        'title'    => $metaInfo['title'],
        'target'   => $target,
        'current'  => $current,
        'progress' => $progress,
        'badge'    => $badgeVariant,
    ];

    $goalsPayload[] = [
        'id'      => $metaInfo['id'],
        'label'   => $metaInfo['title'],
        'target'  => $target,
        'current' => $current,
    ];
}

?>

<div class="dashboard-hero mb-4">
  <div class="row align-items-center">
    <div class="col-lg-8 mb-3 mb-lg-0">
      <h1>Visão geral financeira</h1>
      <p>Acompanhe o ritmo de entrada de receitas, performance dos projetos e metas de cobrança em tempo real.</p>
    </div>
    <div class="col-lg-4 text-lg-right">
      <a href="/pagamento/export" class="btn btn-light d-inline-flex align-items-center">
        <i class="fas fa-download mr-2"></i>
        Exportar pagamentos
      </a>
    </div>
  </div>
</div>

<div class="row">
  <?php foreach ($kpiCards as $card): ?>
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="kpi-card h-100">
        <div class="kpi-icon <?= htmlspecialchars($card['accent'], ENT_QUOTES, 'UTF-8') ?>">
          <i class="fas fa-<?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
        </div>
        <div>
          <div class="kpi-label"><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="kpi-value"
               data-counter="<?= htmlspecialchars((string)$card['raw'], ENT_QUOTES, 'UTF-8') ?>"
               data-counter-type="<?= htmlspecialchars($card['type'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($card['value'], ENT_QUOTES, 'UTF-8') ?>
          </div>
          <div class="kpi-helper"><?= htmlspecialchars($card['helper'], ENT_QUOTES, 'UTF-8') ?></div>
          <?php if (isset($card['usage'])): ?>
            <div class="kpi-usage mt-3">
              <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Utilização</span>
                <span><?= (int)$card['usage'] ?>%</span>
              </div>
              <div class="progress progress-thin">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?= (int)$card['usage'] ?>%"></div>
              </div>
            </div>
          <?php endif; ?>
          <?php if (!empty($card['link'])): ?>
            <a href="<?= htmlspecialchars($card['link'], ENT_QUOTES, 'UTF-8') ?>" class="kpi-link mt-3 d-inline-flex align-items-center">
              Gerenciar <i class="fas fa-arrow-right ml-2"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row">
  <div class="col-xl-8 col-lg-7 mb-4">
    <div class="card dashboard-card h-100">
      <div class="card-header d-flex justify-content-between align-items-start">
        <div>
          <h6 class="card-title mb-1">Receita por mês</h6>
          <span class="card-subtitle">Últimos 6 meses confirmados</span>
        </div>
        <span class="badge badge-soft-secondary">Atualizado <?= Utils::formatDate(date('Y-m-d')) ?></span>
      </div>
      <div class="card-body">
        <div id="revenueChart" class="apex-chart"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-lg-5 mb-4">
    <div class="card dashboard-card h-100">
      <div class="card-header">
        <h6 class="card-title mb-1">Status dos projetos</h6>
        <span class="card-subtitle">Distribuição atual</span>
      </div>
      <div class="card-body">
        <div id="statusChart" class="apex-chart"></div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <?php foreach ($metaCardsUi as $meta): ?>
    <div class="col-xl-3 col-md-6 mb-4">
      <div class="card meta-card h-100">
        <div class="card-body">
          <div class="meta-header">
            <span class="meta-title"><?= htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge-progress <?= htmlspecialchars($meta['badge'], ENT_QUOTES, 'UTF-8') ?>">
              <?= $meta['progress'] ?>%
            </span>
          </div>
          <div class="meta-chart" id="<?= htmlspecialchars($meta['id'], ENT_QUOTES, 'UTF-8') ?>"></div>
          <div class="mt-3">
            <span class="meta-amount"><?= Utils::formatMoney($meta['current']) ?></span>
            <span class="meta-target">de <?= Utils::formatMoney($meta['target']) ?></span>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card dashboard-card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div>
      <h6 class="card-title mb-1">Últimos pagamentos</h6>
      <span class="card-subtitle">Movimentações recentes confirmadas ou previstas</span>
    </div>
    <a href="/pagamento" class="btn btn-outline-primary btn-sm">Ver todos</a>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-modern">
        <thead>
          <tr>
            <th>Descrição</th>
            <th>Tipo</th>
            <th>Valor</th>
            <th>Data</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($recentPayments ?? []) as $payment): ?>
            <?php
              $statusText = $payment['status'] ?? '(indefinido)';
              $statusKey = mb_strtolower(trim($statusText), 'UTF-8');
              $statusLabel = $statusDisplayMap[$statusKey] ?? ucfirst($statusText);
              $badgeClass = $badgeForStatus($statusText);
              $typeKey = strtolower($payment['transaction_type'] ?? 'receita');
              $typeInfo = $typeLabels[$typeKey] ?? $typeLabels['receita'];
              $currency = strtoupper($payment['currency'] ?? 'BRL');
              $amountRaw = (float)($payment['amount'] ?? 0);
              $amountFormatted = $currency === 'USD'
                ? 'US$ ' . number_format($amountRaw, 2, '.', ',')
                : Utils::formatMoney($amountRaw);
              $description = $payment['description'] ?? '';
              $projectName = $payment['project_name'] ?? '';
              if ($description === '' && $projectName !== '') {
                  $description = $projectName;
              }
            ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($description !== '' ? $description : 'Sem descrição', ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if ($projectName && $description !== $projectName): ?>
                  <div class="text-muted small">Projeto: <?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (!empty($payment['category'])): ?>
                  <div class="text-muted small">Categoria: <?= htmlspecialchars($payment['category'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
              </td>
              <td><span class="badge <?= htmlspecialchars($typeInfo['badge'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($typeInfo['label'], ENT_QUOTES, 'UTF-8') ?></span></td>
              <td><?= htmlspecialchars($amountFormatted, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= $displayPaymentDate($payment) ?></td>
              <td>
                <span class="status-pill <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>">
                  <span class="status-dot"></span>
                  <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($recentPayments)): ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                Ainda não há pagamentos registrados.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$chartData = [
    'months'       => array_map(static fn($v) => (string)$v, $months ?? []),
    'values'       => array_map(static fn($v) => (float)$v, $values ?? []),
    'statusLabels' => array_map(static fn($v) => (string)$v, $statusLabels ?? []),
    'statusCounts' => array_map(static fn($v) => (int)$v, $statusCounts ?? []),
    'statusColors' => array_map(static fn($v) => (string)$v, $statusColors ?? []),
    'goals'        => array_map(
        static fn($goal) => [
            'id'      => $goal['id'],
            'label'   => $goal['label'],
            'target'  => (float)$goal['target'],
            'current' => (float)$goal['current'],
        ],
        $goalsPayload
    ),
];
?>

<script>
  window.dashboardData = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/assets/js/dashboard.js"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
