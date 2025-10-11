<?php
$statuses = [
    'aberto' => 'Aberto',
    'negociando' => 'Negociando',
    'pago' => 'Pago',
    'cancelado' => 'Cancelado',
];
$prioridades = [
    'baixa' => 'Baixa',
    'media' => 'Média',
    'alta' => 'Alta',
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="h3 mb-0 text-gray-800">Cobranças</h1>
    <p class="text-muted mb-0">Acompanhe casos em negociação e organize seus follow-ups.</p>
  </div>
  <div>
    <a href="/cobranca/criar" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Novo caso</a>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <form id="filtersForm" class="form-row">
      <div class="form-group col-md-3">
        <label>Status</label>
        <select name="status" class="form-control">
          <option value="">Todos</option>
          <?php foreach ($statuses as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group col-md-3">
        <label>Responsável</label>
        <select name="responsavel_id" class="form-control">
          <option value="">Todos</option>
          <?php foreach ($responsaveis as $user): ?>
            <option value="<?= $user['id'] ?>" <?= ($filters['responsavel_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($user['nome_completo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group col-md-4">
        <label>Cliente ou título</label>
        <input type="text" name="cliente" class="form-control" value="<?= htmlspecialchars($filters['cliente'] ?? '') ?>">
      </div>
      <div class="form-group col-md-2 d-flex align-items-end">
        <button class="btn btn-outline-secondary btn-block" type="submit">Filtrar</button>
      </div>
    </form>
  </div>
</div>

<div class="row">
  <?php
  $grouped = ['aberto'=>[], 'negociando'=>[], 'pago'=>[], 'cancelado'=>[]];
  foreach ($cases as $case) {
      if (!isset($grouped[$case['status']])) {
          $grouped[$case['status']] = [];
      }
      $grouped[$case['status']][] = $case;
  }
  ?>
  <?php foreach ($grouped as $statusKey => $items): ?>
    <div class="col-lg-3 mb-4">
      <div class="card h-100 shadow-sm">
        <div class="card-header bg-light">
          <strong><?= htmlspecialchars($statuses[$statusKey]) ?></strong>
          <span class="badge badge-pill badge-secondary ml-2"><?= count($items) ?></span>
        </div>
        <div class="card-body p-2">
          <?php if (!$items): ?>
            <div class="text-center text-muted small py-3">Sem registros.</div>
          <?php endif; ?>
          <?php foreach ($items as $case): ?>
            <a class="cobranca-card" href="/cobranca/ver/<?= $case['id'] ?>">
              <div class="cobranca-title"><?= htmlspecialchars($case['titulo']) ?></div>
              <div class="cobranca-meta">
                Cliente: <?= htmlspecialchars($case['cliente_nome']) ?><br>
                Responsável: <?= htmlspecialchars($case['responsavel_nome'] ?? '—') ?>
              </div>
              <div class="cobranca-footer">
                <span class="badge badge-pill badge-light"><?= strtoupper($case['prioridade']) ?></span>
                <span><?= Utils::formatMoney($case['valor_pendente']) ?></span>
              </div>
              <?php if (!empty($case['proxima_acao_em'])): ?>
                <div class="cobranca-next">
                  Próxima ação: <?= Utils::formatDate($case['proxima_acao_em']) ?>
                </div>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
