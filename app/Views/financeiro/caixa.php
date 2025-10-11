<?php
$title = 'Caixa e Fluxo';
ob_start();

$totaisReceita = $totals['receita'] ?? ['pagos' => 0.0, 'previstos' => 0.0];
$totaisDespesa = $totals['despesa'] ?? ['pagos' => 0.0, 'previstos' => 0.0];

$fluxoPrevisto = ($totaisReceita['previstos'] ?? 0) - ($totaisDespesa['previstos'] ?? 0);
$fluxoPago = ($totaisReceita['pagos'] ?? 0) - ($totaisDespesa['pagos'] ?? 0);
?>

<div class="d-flex flex-wrap align-items-start justify-content-between mb-4">
  <div>
    <h1 class="h3 text-gray-900 mb-1">Caixa e Fluxo de Caixa</h1>
    <p class="text-muted mb-0">Acompanhe entradas, saídas e valores previstos para o mês atual.</p>
  </div>
  <a href="/financeiro/reserva" class="btn btn-outline-primary">
    <i class="fas fa-piggy-bank mr-2"></i>Ver reserva financeira
  </a>
</div>

<div class="row">
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="text-muted text-uppercase mb-2">Receitas pagas</h6>
        <p class="h4 text-success mb-0"><?= Utils::formatMoney($totaisReceita['pagos']) ?></p>
        <small class="text-muted">Confirmadas no mês atual.</small>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="text-muted text-uppercase mb-2">Despesas pagas</h6>
        <p class="h4 text-danger mb-0"><?= Utils::formatMoney($totaisDespesa['pagos']) ?></p>
        <small class="text-muted">Liquidadas no mês atual.</small>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="text-muted text-uppercase mb-2">Fluxo pago</h6>
        <p class="h4 mb-0 <?= $fluxoPago >= 0 ? 'text-success' : 'text-danger' ?>"><?= Utils::formatMoney($fluxoPago) ?></p>
        <small class="text-muted">Resultado líquido das entradas e saídas.</small>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="text-muted text-uppercase mb-2">Reserva disponível</h6>
        <p class="h4 mb-0"><?= Utils::formatMoney($reserveBalance) ?></p>
        <small class="text-muted">Saldo atual do caixa reserva.</small>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="text-muted text-uppercase mb-2">Receitas previstas</h6>
        <p class="h4 text-success mb-0"><?= Utils::formatMoney($totaisReceita['previstos']) ?></p>
        <small class="text-muted">Pagamentos aguardados até o fim do mês.</small>
      </div>
    </div>
  </div>
  <div class="col-xl-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="text-muted text-uppercase mb-2">Despesas previstas</h6>
        <p class="h4 text-danger mb-0"><?= Utils::formatMoney($totaisDespesa['previstos']) ?></p>
        <small class="text-muted">Compromissos agendados até o fim do mês.</small>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h6 class="mb-0">Resumo previsto do mês</h6>
      <small class="text-muted">Baseado nas datas de vencimento cadastradas.</small>
    </div>
    <span class="badge badge-soft-primary"><?= Utils::formatMoney($fluxoPrevisto) ?></span>
  </div>
  <div class="card-body">
    <div class="progress progress-lg mb-3">
      <?php
        $totalPrevistos = max(1, $totaisReceita['previstos'] + $totaisDespesa['previstos']);
        $percentReceita = round(($totaisReceita['previstos'] / $totalPrevistos) * 100);
        $percentDespesa = round(($totaisDespesa['previstos'] / $totalPrevistos) * 100);
      ?>
      <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percentReceita ?>%"></div>
      <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $percentDespesa ?>%"></div>
    </div>
    <div class="d-flex justify-content-between small text-muted">
      <span>Receitas previstas</span>
      <span><?= Utils::formatMoney($totaisReceita['previstos']) ?></span>
    </div>
    <div class="d-flex justify-content-between small text-muted">
      <span>Despesas previstas</span>
      <span><?= Utils::formatMoney($totaisDespesa['previstos']) ?></span>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h6 class="mb-0">Próximas movimentações</h6>
      <small class="text-muted">Até 8 próximos compromissos cadastrados.</small>
    </div>
    <a href="/pagamento" class="btn btn-sm btn-outline-primary">Ver todos</a>
  </div>
  <div class="table-responsive">
    <table class="table table-modern mb-0">
      <thead>
        <tr>
          <th>Data</th>
          <th>Descrição</th>
          <th>Tipo</th>
          <th>Valor</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$upcomingPayments): ?>
          <tr>
            <td colspan="4" class="text-center text-muted py-4">Nenhuma movimentação futura cadastrada.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($upcomingPayments as $payment): ?>
            <tr>
              <td><?= $payment['due_date'] ? Utils::formatDate($payment['due_date']) : '—' ?></td>
              <td><?= $payment['description'] ? htmlspecialchars($payment['description'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
              <td>
                <?php if (($payment['transaction_type'] ?? 'receita') === 'despesa'): ?>
                  <span class="badge badge-soft-danger">Despesa</span>
                <?php else: ?>
                  <span class="badge badge-soft-success">Receita</span>
                <?php endif; ?>
              </td>
              <td class="<?= ($payment['transaction_type'] ?? 'receita') === 'despesa' ? 'text-danger' : 'text-success' ?>">
                <?= Utils::formatMoney($payment['amount']) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
