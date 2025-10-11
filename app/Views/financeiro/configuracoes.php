<?php
$title = 'Configurações Financeiras';
ob_start();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="h3 text-gray-900 mb-1">Configurações Financeiras</h1>
    <p class="text-muted mb-0">Defina regras, categorias e integrações para os módulos financeiros.</p>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <h5 class="card-title">Categorias padrão</h5>
    <p class="card-text text-muted">
      Organize as categorias utilizadas em pagamentos e na reserva financeira para manter relatórios consistentes. Uma
      futura melhoria pode incluir cadastro dinâmico diretamente nesta tela.
    </p>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="card-title">Integrações e alertas</h5>
    <p class="card-text text-muted">
      Configure notificações, webhooks ou integrações com sistemas externos conforme a evolução do projeto. Utilize este
      espaço como central de ajustes do módulo financeiro.
    </p>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
