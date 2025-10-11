<?php
$title = 'Relatórios Financeiros';
ob_start();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="h3 text-gray-900 mb-1">Relatórios Financeiros</h1>
    <p class="text-muted mb-0">Centralize exportações e indicadores para acompanhar o desempenho do financeiro.</p>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <h5 class="card-title">Exportações rápidas</h5>
    <p class="card-text text-muted">Utilize os atalhos abaixo para gerar relatórios em CSV de acordo com sua necessidade.</p>
    <div class="d-flex flex-wrap align-items-center">
      <a href="/pagamento/export" class="btn btn-outline-primary mr-2 mb-2">
        <i class="fas fa-file-download mr-2"></i>Pagamentos completos
      </a>
      <a href="/financeiro/reserva-exportar" class="btn btn-outline-secondary mb-2">
        <i class="fas fa-piggy-bank mr-2"></i>Reserva financeira
      </a>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="card-title">Próximos passos</h5>
    <p class="card-text text-muted">
      Este módulo está pronto para receber novos relatórios personalizados. Mapeie as métricas essenciais ao seu fluxo
      financeiro e atualize esta área com dashboards adicionais, indicadores de inadimplência, comparativos por período e
      integrações com BI.
    </p>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
