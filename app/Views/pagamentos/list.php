<?php
use function htmlspecialchars as h;

$title = 'Pagamentos';
ob_start();

$filters = $filters ?? [];
$typeFilter = $filters['transaction_type'] ?? '';
$categoryFilter = $filters['category'] ?? '';
$searchFilter = $filters['search'] ?? '';
$exportUrl = '/pagamento/export' . ($filters ? '?' . http_build_query($filters) : '');

$typeLabels = [
  'receita' => ['label' => 'Receita', 'badge' => 'badge-soft-success', 'row' => 'transaction-row-receita'],
  'despesa' => ['label' => 'Despesa', 'badge' => 'badge-soft-danger', 'row' => 'transaction-row-despesa'],
];

$statusAliases = [
  'paid'      => 'Recebido',
  'recebido'  => 'Recebido',
  'pending'   => 'Pendente',
  'pendente'  => 'Pendente',
  'overdue'   => 'Em Atraso',
  'em atraso' => 'Em Atraso',
  'dropped'   => 'Cancelado',
  'cancelado' => 'Cancelado',
  'vencido'   => 'Vencido',
  'parcelado' => 'Parcelado',
  'a receber' => 'A Receber',
];

$normalizeStatus = static function (?string $status) use ($statusAliases) {
    if (!$status) return '—';
    $key = mb_strtolower(trim($status), 'UTF-8');
    return $statusAliases[$key] ?? ucfirst($status);
};
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Pagamentos</h1>
  <div>
    <a class="btn btn-sm btn-secondary" href="<?= h($exportUrl) ?>">Exportar CSV</a>
    <a class="btn btn-sm btn-primary" href="/pagamento/create">Novo pagamento</a>
  </div>
</div>

<?php if ($typeFilter || $categoryFilter || $searchFilter): ?>
  <div class="alert alert-info py-2 px-3 mb-3 small">
    <strong>Filtros ativos:</strong>
    <?php if ($typeFilter): ?>
      <span class="ml-1">Tipo <?= $typeFilter === 'despesa' ? 'Despesas' : 'Receitas' ?>;</span>
    <?php endif; ?>
    <?php if ($categoryFilter): ?>
      <span class="ml-1">Categoria "<?= h($categoryFilter) ?>";</span>
    <?php endif; ?>
    <?php if ($searchFilter): ?>
      <span class="ml-1">Busca por "<?= h($searchFilter) ?>".</span>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="get" class="form-row align-items-end mb-4">
      <div class="form-group col-md-3 col-sm-6">
        <label class="small text-uppercase text-muted">Tipo</label>
        <select name="transaction_type" class="form-control">
          <option value="">Todos</option>
          <option value="receita" <?= $typeFilter === 'receita' ? 'selected' : '' ?>>Receitas</option>
          <option value="despesa" <?= $typeFilter === 'despesa' ? 'selected' : '' ?>>Despesas</option>
        </select>
      </div>
      <div class="form-group col-md-3 col-sm-6">
        <label class="small text-uppercase text-muted">Categoria</label>
        <input type="text" name="category" class="form-control" value="<?= h($categoryFilter) ?>" placeholder="Ex.: Serviços">
      </div>
      <div class="form-group col-md-4">
        <label class="small text-uppercase text-muted">Buscar</label>
        <input type="text" name="search" class="form-control" value="<?= h($searchFilter) ?>" placeholder="Descrição, projeto ou cliente">
      </div>
      <div class="form-group col-md-2 col-sm-12 text-sm-left text-md-right">
        <a href="/pagamento" class="btn btn-link">Limpar</a>
        <button type="submit" class="btn btn-primary">Filtrar</button>
      </div>
    </form>

    <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Data</th>
          <th>Tipo</th>
          <th>Descrição</th>
          <th>Categoria</th>
          <th>Valor</th>
          <th>Status</th>
          <th width="140">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($payments ?? []) as $p):
          $typeKey = strtolower($p['transaction_type'] ?? 'receita');
          $typeInfo = $typeLabels[$typeKey] ?? $typeLabels['receita'];
          $statusLabel = $normalizeStatus($p['status_name'] ?? '');
          $rowClass = $typeInfo['row'];
          $dateRef = $p['paid_at'] ?? $p['due_date'] ?? null;
          $currency = strtoupper($p['currency'] ?? 'BRL');
          $amountRaw = (float)($p['amount'] ?? 0);
          $amountFormatted = $currency === 'USD'
            ? 'US$ ' . number_format($amountRaw, 2, '.', ',')
            : Utils::formatMoney($amountRaw);
          $description = $p['description'] ?? '';
          if ($description === '' && !empty($p['project_name'])) {
              $description = $p['project_name'];
          }
          $category = $p['category'] ?? '';
        ?>
        <tr class="<?= h($rowClass) ?>">
          <td><?= $dateRef ? Utils::formatDate($dateRef) : '—' ?></td>
          <td><span class="badge <?= h($typeInfo['badge']) ?>"><?= h($typeInfo['label']) ?></span></td>
          <td>
            <strong><?= h($description !== '' ? $description : 'Sem descrição') ?></strong>
            <?php if (!empty($p['project_name']) && $description !== $p['project_name']): ?>
              <div class="text-muted small">Projeto: <?= h($p['project_name']) ?></div>
            <?php endif; ?>
            <?php if (!empty($p['client_name'])): ?>
              <div class="text-muted small">Cliente: <?= h($p['client_name']) ?></div>
            <?php endif; ?>
          </td>
          <td><?= $category !== '' ? h($category) : '—' ?></td>
          <td><?= h($amountFormatted) ?></td>
          <td><span class="status-pill badge-soft-secondary"><?= h($statusLabel) ?></span></td>
          <td class="btn-group">
            <a class="btn btn-sm btn-outline-primary" href="/pagamento/edit/<?= (int)$p['id'] ?>">Editar</a>
            <a class="btn btn-sm btn-outline-danger" href="/pagamento/delete/<?= (int)$p['id'] ?>" onclick="return confirm('Excluir pagamento?');">Excluir</a>
            <a class="btn btn-sm btn-outline-success" href="/cobranca/criar?payment_id=<?= (int)$p['id'] ?>">
              <i class="fas fa-hand-holding-usd mr-1"></i>Cobrar
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($payments)): ?>
          <tr><td colspan="7" class="text-center text-muted">Nenhum pagamento cadastrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
