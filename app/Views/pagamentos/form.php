<?php
$title = isset($payment['id']) ? 'Editar Pagamento' : 'Novo Pagamento';
ob_start();

$id        = $payment['id']        ?? null;
$projectId = $payment['project_id']?? null;
$clientId  = $payment['client_id'] ?? ($payment['project_client_id'] ?? ($_POST['client_id'] ?? null));
$amount    = $payment['amount']    ?? '';
$currency  = strtoupper($payment['currency'] ?? ($_POST['currency'] ?? 'BRL'));
$dueDate   = $payment['due_date']  ?? '';
$paidAt    = $payment['paid_at']   ?? '';
$statusId  = $payment['status_id'] ?? null;
$statusValue = $payment['status_name'] ?? ($_POST['status'] ?? null);
$kind      = $payment['kind']      ?? 'one_time';
$transactionType = strtolower($payment['transaction_type'] ?? ($_POST['transaction_type'] ?? 'receita'));
$description = $payment['description'] ?? ($_POST['description'] ?? '');
$category    = $payment['category']    ?? ($_POST['category'] ?? '');
$notes       = $payment['notes']       ?? ($_POST['notes'] ?? '');
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($title) ?></h1>

<div class="card">
  <div class="card-body">
    <form method="post" action="/pagamento/save" autocomplete="off">
      <?php if ($id): ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group col-md-5">
          <label>Projeto</label>
          <select name="project_id" class="form-control" required>
            <option value="">Selecione...</option>
            <?php foreach (($projects ?? []) as $pr): ?>
              <option value="<?= (int)$pr['id'] ?>" <?= $projectId==$pr['id']?'selected':'' ?>>
                <?= htmlspecialchars($pr['name']) ?> <?php if (!empty($pr['client_name'])) echo '— '.htmlspecialchars($pr['client_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group col-md-3">
          <label>Valor</label>
          <?php
            $amountValue = $amount;
            if ($amountValue !== '' && $amountValue !== null) {
              $amountValue = number_format((float)$amountValue, 2, '.', '');
            }
          ?>
          <input type="number" step="0.01" min="0" class="form-control" name="amount" value="<?= htmlspecialchars((string)$amountValue) ?>" required>
        </div>

        <div class="form-group col-md-2">
          <label>Moeda</label>
          <select name="currency" class="form-control">
            <option value="BRL" <?= $currency === 'BRL' ? 'selected' : '' ?>>Real (BRL)</option>
            <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>Dólar (USD)</option>
          </select>
        </div>

        <div class="form-group col-md-2">
          <label>Tipo de transação</label>
          <select name="transaction_type" id="transactionType" class="form-control">
            <option value="receita" <?= $transactionType === 'receita' ? 'selected' : '' ?>>Receita</option>
            <option value="despesa" <?= $transactionType === 'despesa' ? 'selected' : '' ?>>Despesa</option>
          </select>
        </div>

        <div class="form-group col-md-2">
          <label>Recorrência</label>
          <select name="kind" class="form-control">
            <option value="one_time"  <?= $kind==='one_time'?'selected':'' ?>>Avulso</option>
            <option value="recurring" <?= $kind==='recurring'?'selected':'' ?>>Recorrente</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label>Cliente (opcional)</label>
          <select name="client_id" id="paymentClientSelect" class="form-control">
            <option value="">Selecionar cliente...</option>
            <?php foreach (($clients ?? []) as $cl): ?>
              <option value="<?= (int)$cl['id'] ?>" <?= ((string)$clientId === (string)$cl['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cl['name']) ?><?php if (!empty($cl['email'])) echo ' — '.htmlspecialchars($cl['email']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-6">
          <label>Observações internas</label>
          <textarea name="notes" class="form-control" rows="1" placeholder="Informações adicionais sobre esta cobrança"><?= htmlspecialchars($notes) ?></textarea>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Status</label>
          <select name="status" id="statusSelect" class="form-control">
            <option value="">Selecione...</option>
            <?php foreach (($statusOptions ?? []) as $typeKey => $options): ?>
              <?php foreach ($options as $opt): ?>
                <option value="<?= htmlspecialchars($opt['value']) ?>" data-type="<?= htmlspecialchars($typeKey) ?>"
                  <?= ($statusValue ?? '') === $opt['value'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($opt['label']) ?>
                </option>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>Vencimento</label>
          <input type="date" class="form-control" name="due_date" value="<?= htmlspecialchars(substr((string)$dueDate,0,10)) ?>">
        </div>
        <div class="form-group col-md-4">
          <label>Pago em</label>
          <input type="date" class="form-control" name="paid_at" value="<?= htmlspecialchars(substr((string)$paidAt,0,10)) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label>Descrição</label>
          <input type="text" class="form-control" name="description" value="<?= htmlspecialchars($description) ?>" placeholder="Resumo da transação">
        </div>
        <div class="form-group col-md-4">
          <label>Categoria</label>
          <input type="text" class="form-control" name="category" value="<?= htmlspecialchars($category) ?>" placeholder="Ex.: Serviços, Operacional">
        </div>
      </div>

      <div class="text-right">
        <a href="/pagamento" class="btn btn-light">Cancelar</a>
        <button class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('transactionType');
    const statusSelect = document.getElementById('statusSelect');
    const projectSelect = document.querySelector('select[name="project_id"]');
    const clientSelect = document.getElementById('paymentClientSelect');
    const projectClientMap = <?= json_encode(array_reduce($projects ?? [], function (array $carry, array $pr) {
      if (!empty($pr['id'])) {
        $carry[(string)$pr['id']] = $pr['client_id'] ?? null;
      }
      return $carry;
    }, [])); ?>;

    if (typeSelect && statusSelect) {
      const filterStatuses = () => {
        const currentType = typeSelect.value || 'receita';
        let firstVisible = null;
        Array.from(statusSelect.options).forEach((option) => {
          const optionType = option.dataset.type;
          const shouldShow = !optionType || optionType === currentType;
          option.hidden = !shouldShow;
          if (!shouldShow && option.selected) {
            option.selected = false;
          }
          if (shouldShow && !firstVisible && option.value !== '') {
            firstVisible = option;
          }
        });
        if (!statusSelect.value && firstVisible) {
          firstVisible.selected = true;
        }
      };

      typeSelect.addEventListener('change', filterStatuses);
      filterStatuses();
    }

    if (projectSelect && clientSelect) {
      projectSelect.addEventListener('change', () => {
        const target = projectClientMap[projectSelect.value];
        if (!target) {
          return;
        }
        if (!clientSelect.value) {
          clientSelect.value = String(target);
        }
      });
    }
  });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
