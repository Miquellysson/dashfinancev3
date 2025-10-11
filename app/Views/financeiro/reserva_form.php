<?php
$isEdit = !empty($movement['id']);
$title = $isEdit ? 'Editar movimentação da reserva' : 'Nova movimentação da reserva';
ob_start();

$action = $isEdit
    ? '/financeiro/reserva/atualizar/' . $movement['id']
    : '/financeiro/reserva/salvar';

$operation = $movement['operation_type'] ?? 'deposit';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="h3 text-gray-900 mb-1"><?= $isEdit ? 'Editar movimentação' : 'Nova movimentação' ?></h1>
    <p class="text-muted mb-0"><?= $isEdit ? 'Atualize os dados cadastrados.' : 'Registre um depósito ou retirada no caixa reserva.' ?></p>
  </div>
  <a href="/financeiro/reserva" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left mr-2"></i>Voltar
  </a>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">
      <div class="form-row">
        <div class="form-group col-md-3">
          <label for="referenceDate">Data da movimentação</label>
          <input type="date" id="referenceDate" name="reference_date" class="form-control"
                 value="<?= htmlspecialchars($movement['reference_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-group col-md-3">
          <label for="operationType">Tipo</label>
          <select id="operationType" name="operation_type" class="form-control">
            <option value="deposit" <?= $operation === 'deposit' ? 'selected' : '' ?>>Depósito</option>
            <option value="withdraw" <?= $operation === 'withdraw' ? 'selected' : '' ?>>Retirada</option>
          </select>
        </div>
        <div class="form-group col-md-3">
          <label for="amount">Valor</label>
          <input type="text" id="amount" name="amount" class="form-control"
                 value="<?= htmlspecialchars($movement['amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 placeholder="0,00" required>
        </div>
        <div class="form-group col-md-3">
          <label for="category">Categoria</label>
          <input type="text" id="category" name="category" class="form-control"
                 value="<?= htmlspecialchars($movement['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 placeholder="ex.: Reserva de emergência">
        </div>
      </div>

      <div class="form-group">
        <label for="description">Descrição</label>
        <input type="text" id="description" name="description" class="form-control"
               value="<?= htmlspecialchars($movement['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="form-group">
        <label for="notes">Observações</label>
        <textarea id="notes" name="notes" class="form-control" rows="4"><?= htmlspecialchars($movement['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
      </div>

      <div class="d-flex justify-content-end">
        <a href="/financeiro/reserva" class="btn btn-link mr-2">Cancelar</a>
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salvar alterações' : 'Registrar movimentação' ?></button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
