<?php
$title = 'Reserva Financeira';
ob_start();

$typeLabels = [
    'deposit' => ['label' => 'Depósito', 'badge' => 'badge-soft-success', 'icon' => 'arrow-down'],
    'withdraw' => ['label' => 'Retirada', 'badge' => 'badge-soft-danger', 'icon' => 'arrow-up'],
];

$currentFilters = [
    'from' => $filters['from'] ?? '',
    'to' => $filters['to'] ?? '',
    'type' => $filters['type'] ?? '',
    'search' => $filters['search'] ?? '',
];

$queryString = static function (array $overrides = []) use ($currentFilters) {
    $params = array_filter(array_merge($currentFilters, $overrides), static fn($value) => $value !== '' && $value !== null);
    return $params ? '?' . http_build_query($params) : '';
};

$totalPages = $pagination['total_pages'] ?? 1;
$currentPage = $pagination['current'] ?? 1;
?>

<div class="d-flex flex-wrap align-items-start justify-content-between mb-4">
  <div>
    <h1 class="h3 text-gray-900 mb-1">Reserva financeira</h1>
    <p class="text-muted mb-0">Controle os aportes e retiradas do caixa reserva.</p>
  </div>
  <div class="d-flex flex-wrap align-items-center justify-content-end">
    <a href="/financeiro/reserva-criar" class="btn btn-primary mr-2 mb-2">
      <i class="fas fa-plus-circle mr-2"></i>Nova movimentação
    </a>
    <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary mb-2">
      <i class="fas fa-file-export mr-2"></i>Exportar CSV
    </a>
  </div>
</div>

<div class="row">
  <div class="col-xl-4 col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="text-muted text-uppercase mb-2">Saldo atual</h6>
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <p class="h3 mb-0"><?= Utils::formatMoney($overallBalance) ?></p>
            <small class="text-muted">Disponível para uso imediato</small>
          </div>
          <div class="icon-circle icon-circle-lg bg-gradient-success text-white">
            <i class="fas fa-piggy-bank"></i>
          </div>
        </div>
        <?php if ($totals['deposits'] > 0): ?>
          <div class="mt-4">
            <div class="d-flex justify-content-between small text-muted mb-1">
              <span>Utilização da reserva</span>
              <span><?= $usage ?>%</span>
            </div>
            <div class="progress progress-thin">
              <div class="progress-bar bg-success" role="progressbar" style="width: <?= $usage ?>%"></div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="text-muted text-uppercase mb-2">Total depositado</h6>
        <p class="h4 mb-1 text-success"><?= Utils::formatMoney($totals['deposits']) ?></p>
        <small class="text-muted">Somatório de aportes no período filtrado.</small>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="text-muted text-uppercase mb-2">Total retirado</h6>
        <p class="h4 mb-1 text-danger"><?= Utils::formatMoney($totals['withdrawals']) ?></p>
        <small class="text-muted">Saídas realizadas para cobrir despesas ou investimentos.</small>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <form method="get" class="form-row">
      <div class="form-group col-md-3">
        <label for="filterFrom">De</label>
        <input type="date" id="filterFrom" name="from" value="<?= htmlspecialchars($currentFilters['from'], ENT_QUOTES, 'UTF-8') ?>" class="form-control">
      </div>
      <div class="form-group col-md-3">
        <label for="filterTo">Até</label>
        <input type="date" id="filterTo" name="to" value="<?= htmlspecialchars($currentFilters['to'], ENT_QUOTES, 'UTF-8') ?>" class="form-control">
      </div>
      <div class="form-group col-md-3">
        <label for="filterType">Tipo</label>
        <select id="filterType" name="type" class="form-control">
          <option value="">Todos</option>
          <option value="deposit" <?= $currentFilters['type'] === 'deposit' ? 'selected' : '' ?>>Depósito</option>
          <option value="withdraw" <?= $currentFilters['type'] === 'withdraw' ? 'selected' : '' ?>>Retirada</option>
        </select>
      </div>
      <div class="form-group col-md-3">
        <label for="filterSearch">Buscar</label>
        <input type="text" id="filterSearch" name="search" value="<?= htmlspecialchars($currentFilters['search'], ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Descrição ou categoria">
      </div>
      <div class="form-group col-12 d-flex justify-content-end">
        <a href="/financeiro/reserva" class="btn btn-link mr-2">Limpar</a>
        <button type="submit" class="btn btn-primary">Aplicar filtros</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h6 class="mb-0">Movimentações da reserva</h6>
      <small class="text-muted">Histórico completo com edição e exclusão.</small>
    </div>
    <span class="badge badge-soft-secondary">Total: <?= $pagination['total'] ?? count($entries) ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-modern mb-0">
      <thead>
        <tr>
          <th>Data</th>
          <th>Tipo</th>
          <th>Valor</th>
          <th>Categoria</th>
          <th>Descrição</th>
          <th>Observações</th>
          <th class="text-right">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$entries): ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-4">Nenhuma movimentação encontrada para os filtros selecionados.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($entries as $entry): ?>
          <?php
            $type = $entry['operation_type'];
            $labelInfo = $typeLabels[$type] ?? $typeLabels['deposit'];
            $amountSign = $type === 'withdraw' ? -1 : 1;
          ?>
          <tr>
            <td><?= Utils::formatDate($entry['reference_date']) ?></td>
            <td>
              <span class="badge <?= $labelInfo['badge'] ?>">
                <i class="fas fa-<?= $labelInfo['icon'] ?> mr-1"></i>
                <?= $labelInfo['label'] ?>
              </span>
            </td>
            <td class="<?= $amountSign < 0 ? 'text-danger' : 'text-success' ?>">
              <?= $amountSign < 0 ? '− ' : '' ?><?= Utils::formatMoney($entry['amount']) ?>
            </td>
            <td><?= $entry['category'] ? htmlspecialchars($entry['category'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
            <td><?= $entry['description'] ? htmlspecialchars($entry['description'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
            <td><?= $entry['notes'] ? nl2br(htmlspecialchars($entry['notes'], ENT_QUOTES, 'UTF-8')) : '—' ?></td>
            <td class="text-right">
              <a href="/financeiro/reserva-editar/<?= $entry['id'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-edit"></i>
              </a>
              <form action="/financeiro/reserva-excluir/<?= $entry['id'] ?>" method="post" class="d-inline" onsubmit="return confirm('Confirma remover esta movimentação?');">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <span class="small text-muted">Página <?= $currentPage ?> de <?= $totalPages ?></span>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
              <a class="page-link" href="<?= $queryString(['page' => $p]) ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
