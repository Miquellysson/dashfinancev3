<?php
$title = 'Metas';
ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Metas</h1>
  <a class="btn btn-sm btn-primary" href="/goals/create">Nova meta</a>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>#</th>
          <th>Período</th>
          <th>Início</th>
          <th>Fim</th>
          <th>Alvo</th>
          <th>Atual (pagos)</th>
          <th width="140">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($goals ?? []) as $g): ?>
          <tr>
            <td><?= (int)($g['id'] ?? 0) ?></td>
            <td><?= htmlspecialchars($g['type_label'] ?? ucfirst((string)($g['period_type'] ?? ''))) ?></td>
            <td><?= Utils::formatDate($g['period_start'] ?? null) ?></td>
            <td><?= Utils::formatDate($g['period_end'] ?? null) ?></td>
            <td><?= Utils::formatMoney($g['target_value'] ?? 0) ?></td>
            <td><?= Utils::formatMoney($g['current_value'] ?? 0) ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="/goals/edit/<?= (int)($g['id'] ?? 0) ?>">Editar</a>
              <a class="btn btn-sm btn-outline-danger" href="/goals/delete/<?= (int)($g['id'] ?? 0) ?>" onclick="return confirm('Excluir meta?');">Excluir</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($goals)): ?>
          <tr><td colspan="7" class="text-center text-muted">Nenhuma meta cadastrada.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
