<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
  <div>
    <h1 class="h3 text-gray-800 mb-1">Dashboard Financeiro</h1>
    <p class="text-muted mb-0">Visão consolidada dos projetos e receitas.</p>
  </div>
  <div class="mt-3 mt-md-0">
    <a href="/projeto" class="btn btn-outline-primary"><i class="fas fa-arrow-left mr-1"></i>Voltar aos projetos</a>
  </div>
</div>

<?php
$paymentBreakdown = $paymentBreakdown ?? [];
$summary = $summary ?? [
    'total_geral' => 0,
    'total_pago_projetos' => 0,
    'total_pendente' => 0,
    'total_recebido' => 0,
];
include __DIR__ . '/partials/summary-cards.php';
?>

<div class="row">
  <div class="col-xl-4 col-lg-6 mb-4">
    <div class="card dashboard-card h-100">
      <div class="card-header">
        <h6 class="card-title mb-0">Distribuição de pagamentos</h6>
      </div>
      <div class="card-body">
        <div id="dashboardPayment" class="apex-chart"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-lg-6 mb-4">
    <div class="card dashboard-card h-100">
      <div class="card-header">
        <h6 class="card-title mb-0">Faturamento por serviço</h6>
      </div>
      <div class="card-body">
        <div id="dashboardRevenue" class="apex-chart"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-lg-12 mb-4">
    <div class="card dashboard-card h-100">
      <div class="card-header">
        <h6 class="card-title mb-0">Evolução mensal</h6>
      </div>
      <div class="card-body">
        <div id="dashboardEvolution" class="apex-chart"></div>
      </div>
    </div>
  </div>
</div>

<script>
  window.projetosDashboard = {
    paymentBreakdown: <?= json_encode($paymentBreakdown, JSON_UNESCAPED_UNICODE) ?>,
    revenueByService: <?= json_encode($revenueByService, JSON_UNESCAPED_UNICODE) ?>,
    evolution: <?= json_encode($evolution, JSON_UNESCAPED_UNICODE) ?>,
  };
</script>
<script src="/assets/js/projects.js"></script>
