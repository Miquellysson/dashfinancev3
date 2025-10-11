<?php
use function htmlspecialchars as h;

$statusPagamentoOptions = ProjectModel::PAYMENT_STATUS;
$satisfacaoOptions = ProjectModel::SATISFACTION_STATUS;
$tipoServicoOptions = ProjectModel::SERVICE_TYPES;

$selected = static function ($value, $current) {
    return $value === $current ? 'selected' : '';
};

$searchFilters = [
    'status_pagamento' => $_GET['status_pagamento'] ?? '',
    'tipo_servico' => $_GET['tipo_servico'] ?? '',
    'status_satisfacao' => $_GET['status_satisfacao'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'busca' => $_GET['busca'] ?? '',
    'responsavel_id' => $_GET['responsavel_id'] ?? '',
];
?>

<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4">
  <div>
    <h1 class="h3 mb-1 text-gray-800">Projetos</h1>
    <p class="text-muted mb-0">Gestão completa de projetos, financeiro e satisfação do cliente.</p>
  </div>
  <div class="mt-3 mt-lg-0">
    <a href="/projeto/create" class="btn btn-primary mr-2"><i class="fas fa-plus mr-1"></i>Novo Projeto</a>
    <a href="/projeto/dashboard" class="btn btn-outline-primary"><i class="fas fa-chart-pie mr-1"></i>Dashboard Financeiro</a>
  </div>
</div>

<?php include __DIR__ . '/partials/summary-cards.php'; ?>

<div class="card dashboard-card mb-4">
  <div class="card-header d-flex flex-column flex-xl-row align-items-xl-center justify-content-between">
    <div>
      <h6 class="card-title mb-1">Filtros avançados</h6>
      <span class="card-subtitle">Combine filtros para encontrar projetos específicos</span>
    </div>
    <button class="btn btn-link p-0 mt-2 mt-xl-0" data-toggle="collapse" data-target="#projectFilters" aria-expanded="true">
      <span class="text-primary font-weight-semibold">Mostrar/Ocultar filtros</span>
    </button>
  </div>
  <div id="projectFilters" class="collapse show">
    <div class="card-body">
      <form method="get" class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label small text-uppercase">Status de pagamento</label>
          <select name="status_pagamento" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($statusPagamentoOptions as $option): ?>
              <option value="<?= h($option) ?>" <?= $selected($option, $searchFilters['status_pagamento']) ?>><?= h($option) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label small text-uppercase">Tipo de serviço</label>
          <select name="tipo_servico" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($tipoServicoOptions as $option): ?>
              <option value="<?= h($option) ?>" <?= $selected($option, $searchFilters['tipo_servico']) ?>><?= h($option) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label small text-uppercase">Satisfação</label>
          <select name="status_satisfacao" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($satisfacaoOptions as $option): ?>
              <option value="<?= h($option) ?>" <?= $selected($option, $searchFilters['status_satisfacao']) ?>><?= h($option) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label small text-uppercase">Responsável</label>
          <select name="responsavel_id" class="form-control">
            <option value="">Todos</option>
            <?php foreach ($responsaveis as $resp): ?>
              <option value="<?= (int)$resp['id'] ?>" <?= $selected((string)$resp['id'], $searchFilters['responsavel_id']) ?>>
                <?= h($resp['nome_completo']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label small text-uppercase">Data inicial</label>
          <input type="date" class="form-control" name="data_inicio" value="<?= h($searchFilters['data_inicio']) ?>">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label small text-uppercase">Data final</label>
          <input type="date" class="form-control" name="data_fim" value="<?= h($searchFilters['data_fim']) ?>">
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label small text-uppercase">Buscar cliente ou projeto</label>
          <input type="text" class="form-control" name="busca" placeholder="Ex.: Empresa ABC" value="<?= h($searchFilters['busca']) ?>">
        </div>
        <div class="col-12 d-flex align-items-center justify-content-end">
          <a href="/projeto" class="btn btn-link text-muted mr-2">Limpar</a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter mr-1"></i>Aplicar filtros
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="card dashboard-card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-modern table-hover mb-0">
        <thead>
          <tr>
            <th><a href="<?= Utils::buildSortUrl('nome_cliente') ?>">Cliente</a></th>
            <th><a href="<?= Utils::buildSortUrl('tipo_servico') ?>">Serviço</a></th>
            <th><a href="<?= Utils::buildSortUrl('data_entrada') ?>">Entrada</a></th>
            <th>Status Pagamento</th>
            <th>Satisfação</th>
            <th class="text-right"><a href="<?= Utils::buildSortUrl('valor_projeto') ?>">Valor</a></th>
            <th class="text-right">Recebido</th>
            <th>Responsável</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($projects as $project): ?>
          <?php
            $rowClass = '';
            if (($project['status_satisfacao'] ?? '') === 'Insatisfeito') {
              $rowClass = 'project-row-insatisfeito';
            }
          ?>
          <tr class="<?= $rowClass ?>">
            <td>
              <span class="font-weight-semibold"><?= h($project['nome_cliente']) ?></span><br>
              <small class="text-muted"><?= h($project['name']) ?></small>
            </td>
            <td><?= h($project['tipo_servico']) ?></td>
            <td><?= Utils::formatDate($project['data_entrada']) ?></td>
            <td>
              <span class="status-pill <?= Utils::badgeForPaymentStatus($project['status_pagamento']) ?>">
                <span class="status-dot"></span><?= h($project['status_pagamento']) ?>
              </span>
            </td>
            <td><?= Utils::renderSatisfactionBadge($project['status_satisfacao']) ?></td>
            <td class="text-right"><?= Utils::formatMoney($project['valor_projeto']) ?></td>
            <td class="text-right">
              <span class="text-success font-weight-semibold"><?= Utils::formatMoney($project['valor_pago']) ?></span><br>
              <small class="text-muted">Pendente: <?= Utils::formatMoney($project['valor_pendente']) ?></small>
            </td>
            <td><?= h($project['responsavel_nome']) ?></td>
            <td class="text-right">
              <div class="btn-group btn-group-sm">
                <a href="/projeto/show/<?= (int)$project['id'] ?>" class="btn btn-outline-primary" data-toggle="tooltip" title="Visualizar">
                  <i class="fas fa-eye"></i>
                </a>
                <a href="/projeto/edit/<?= (int)$project['id'] ?>" class="btn btn-outline-secondary" data-toggle="tooltip" title="Editar">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="/projeto/atividades/<?= (int)$project['id'] ?>" class="btn btn-outline-info" data-toggle="tooltip" title="Atividades">
                  <i class="fas fa-tasks"></i>
                </a>
                <?php if (Auth::isAdmin()): ?>
                <a href="/projeto/delete/<?= (int)$project['id'] ?>" class="btn btn-outline-danger" data-toggle="tooltip" title="Excluir"
                   onclick="return confirm('Confirma remover o projeto?')">
                  <i class="fas fa-trash"></i>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($projects)): ?>
          <tr>
            <td colspan="9" class="text-center text-muted py-5">
              Nenhum projeto encontrado com os filtros atuais.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="border-top px-4 py-3">
      <ul class="pagination justify-content-center mb-0">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= Utils::buildPageUrl($i) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</div>

<div class="row mt-4">
  <div class="col-xl-4">
    <div class="card dashboard-card h-100">
      <div class="card-header">
        <h6 class="card-title mb-0">Distribuição de pagamentos</h6>
      </div>
      <div class="card-body">
        <div id="chartPaymentDistribution" class="apex-chart"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card dashboard-card h-100">
      <div class="card-header">
        <h6 class="card-title mb-0">Faturamento por serviço</h6>
      </div>
      <div class="card-body">
        <div id="chartServiceRevenue" class="apex-chart"></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card dashboard-card h-100">
      <div class="card-header">
        <h6 class="card-title mb-0">Evolução mensal</h6>
      </div>
      <div class="card-body">
        <div id="chartMonthlyEvolution" class="apex-chart"></div>
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
