<?php
$title = isset($goal['id']) ? 'Editar Meta' : 'Nova Meta';
ob_start();

$id          = $goal['id']           ?? null;
$periodType  = $goal['period_type']  ?? 'monthly';
$periodStart = $goal['period_start'] ?? '';
$periodEnd   = $goal['period_end']   ?? '';
$targetValue = $goal['target_value'] ?? '';
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($title) ?></h1>

<div class="card">
  <div class="card-body">
    <form method="post" action="/goals/save" autocomplete="off">
      <?php if ($id): ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Tipo de Período</label>
          <select name="period_type" class="form-control" required>
            <?php
              $opts = [
                'daily'     => 'Diária',
                'weekly'    => 'Semanal',
                'biweekly'  => 'Quinzenal',
                'monthly'   => 'Mensal',
                'quarterly' => 'Trimestral',
              ];
              foreach ($opts as $val=>$label):
            ?>
              <option value="<?= $val ?>" <?= $periodType===$val?'selected':'' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>Início</label>
          <input type="date" class="form-control" name="period_start" value="<?= htmlspecialchars(substr((string)$periodStart,0,10)) ?>" required>
        </div>
        <div class="form-group col-md-4">
          <label>Fim</label>
          <input type="date" class="form-control" name="period_end" value="<?= htmlspecialchars(substr((string)$periodEnd,0,10)) ?>" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Valor da meta (R$)</label>
          <?php
            $targetInputValue = $targetValue;
            if ($targetInputValue !== '' && $targetInputValue !== null) {
              $targetInputValue = number_format((float)$targetInputValue, 2, '.', '');
            }
          ?>
          <input type="number" step="0.01" min="0" class="form-control" name="target_value" value="<?= htmlspecialchars((string)$targetInputValue) ?>" required>
        </div>
      </div>

      <div class="text-right">
        <a href="/goals" class="btn btn-light">Cancelar</a>
        <button class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
