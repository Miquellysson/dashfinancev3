<?php
$totalPago = $paymentBreakdown['Pago']['total_valor'] ?? 0;
$totalPendente = $paymentBreakdown['Pendente']['total_pendente'] ?? 0;
$totalParcial = $paymentBreakdown['Parcial']['total_valor'] ?? 0;
?>

<div class="row mb-4">
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="kpi-card">
      <div class="kpi-icon primary"><i class="fas fa-layer-group"></i></div>
      <div>
        <div class="kpi-label">Total geral</div>
        <div class="kpi-value" data-counter="<?= $summary['total_geral'] ?? 0 ?>" data-counter-type="money">
          <?= Utils::formatMoney($summary['total_geral'] ?? 0) ?>
        </div>
        <div class="kpi-helper">Somatória de todos os projetos</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="kpi-card">
      <div class="kpi-icon success"><i class="fas fa-circle-check"></i></div>
      <div>
        <div class="kpi-label">Pagos</div>
        <div class="kpi-value" data-counter="<?= $totalPago ?>" data-counter-type="money">
          <?= Utils::formatMoney($totalPago) ?>
        </div>
        <div class="kpi-helper">Projetos completamente pagos</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="kpi-card">
      <div class="kpi-icon warning"><i class="fas fa-clock"></i></div>
      <div>
        <div class="kpi-label">Pendentes</div>
        <div class="kpi-value" data-counter="<?= $summary['total_pendente'] ?? 0 ?>" data-counter-type="money">
          <?= Utils::formatMoney($summary['total_pendente'] ?? 0) ?>
        </div>
        <div class="kpi-helper">Valores a receber</div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="kpi-card">
      <div class="kpi-icon info"><i class="fas fa-wallet"></i></div>
      <div>
        <div class="kpi-label">Recebido</div>
        <div class="kpi-value" data-counter="<?= $summary['total_recebido'] ?? 0 ?>" data-counter-type="money">
          <?= Utils::formatMoney($summary['total_recebido'] ?? 0) ?>
        </div>
        <div class="kpi-helper">Total recebido até agora</div>
      </div>
    </div>
  </div>
</div>
