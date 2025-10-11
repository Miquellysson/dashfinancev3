
<?php
use function htmlspecialchars as h;

$title = 'Clientes';
ob_start();

$orderOptions = [
  'name' => 'Nome (A-Z)',
  'id' => 'ID',
  'entry_date' => 'Data de entrada',
  'created_at' => 'Criado em',
  'email' => 'Email',
];

$directionOptions = [
  'ASC' => 'Ascendente',
  'DESC' => 'Descendente',
];

$currentOrderBy = $currentOrderBy ?? ($_GET['order_by'] ?? 'name');
$currentOrderDir = strtoupper($currentOrderDir ?? ($_GET['order_dir'] ?? 'ASC'));
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <h1 class="h3 mb-0 text-gray-800">Clientes</h1>
  <a href="/cliente/create" class="btn btn-primary btn-sm">
    <i class="fas fa-plus"></i> Novo Cliente
  </a>
</div>

<div class="card shadow mb-4">
  <div class="card-body">
    <form method="get" class="form-inline mb-3" id="clientSortForm">
      <div class="form-group mr-2">
        <label for="order_by" class="mr-2 small text-muted text-uppercase">Ordenar por</label>
        <select name="order_by" id="order_by" class="form-control form-control-sm">
          <?php foreach ($orderOptions as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= $currentOrderBy === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group mr-2">
        <label for="order_dir" class="mr-2 small text-muted text-uppercase">Direção</label>
        <select name="order_dir" id="order_dir" class="form-control form-control-sm">
          <?php foreach ($directionOptions as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= $currentOrderDir === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-outline-secondary btn-sm">Aplicar</button>
    </form>
    <div class="table-responsive">
      <table class="table table-bordered" width="100%" cellspacing="0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Telefone</th>
            <th>Entrada</th>
            <th>Briefing</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clients as $client): ?>
          <tr>
            <td><?= (int)$client['id'] ?></td>
            <td><?= h($client['name']) ?></td>
            <td><?= h($client['email'] ?? '') ?></td>
            <td><?= h($client['phone'] ?? '') ?></td>
            <td><?= !empty($client['entry_date']) ? date('d/m/Y', strtotime($client['entry_date'])) : '—' ?></td>
            <td class="text-muted">
              <?php
                $briefing = $client['notes'] ?? '';
                $briefing = trim($briefing);
                echo $briefing !== '' ? h(mb_strimwidth($briefing, 0, 70, '…')) : '—';
              ?>
            </td>
            <td>
              <a href="/cliente/edit/<?= $client['id'] ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-edit"></i>
              </a>
              <?php if (Auth::isAdmin()): ?>
              <a href="/cliente/delete/<?= $client['id'] ?>" class="btn btn-sm btn-danger" 
                 onclick="return confirm('Tem certeza que deseja excluir?')">
                <i class="fas fa-trash"></i>
              </a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
    <nav>
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
          <a class="page-link" href="/cliente?<?= h(http_build_query([
              'page' => $i,
              'order_by' => $currentOrderBy,
              'order_dir' => $currentOrderDir,
          ])) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
