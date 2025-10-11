<?php
$title = 'Editar Meta';
ob_start();
?>
<h1 class="h3 mb-3 text-gray-800">Editar Meta</h1>

<div class="card shadow mb-4">
  <div class="card-body">
    <form action="/goals/update" method="post">
      <input type="hidden" name="id" value="<?= (int)$goal['id'] ?>">

      <div class="form-group">
        <label for="title">Título*</label>
        <input id="title" name="title" class="form-control" 
               value="<?= htmlspecialchars($goal['title']) ?>" required>
      </div>

      <div class="form-group">
        <label for="description">Descrição</label>
        <textarea id="description" name="description" class="form-control"><?= htmlspecialchars($goal['description'] ?? '') ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label for="target_amount">Valor alvo*</label>
          <input id="target_amount" name="target_amount" type="number" step="0.01" 
                 class="form-control" value="<?= (float)$goal['target_amount'] ?>" required>
        </div>
        <div class="form-group col-md-4">
          <label for="current_amount">Valor atual</label>
          <input id="current_amount" name="current_amount" type="number" step="0.01" 
                 class="form-control" value="<?= (float)$goal['current_amount'] ?>">
        </div>
        <div class="form-group col-md-4">
          <label for="target_date">Prazo (data)</label>
          <input id="target_date" name="target_date" type="date" class="form-control" 
                 value="<?= htmlspecialchars($goal['target_date']) ?>">
        </div>
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="achieved" id="ach"
               <?= !empty($goal['achieved']) ? 'checked' : '' ?>>
        <label class="form-check-label" for="ach">Concluída</label>
      </div>

      <button class="btn btn-primary">Salvar</button>
      <a class="btn btn-light" href="/goals">Cancelar</a>
    </form>
  </div>
</div>
<?php
$content = ob_get_clean();
$__layout = $GLOBALS['__layout_path'] ?? (__DIR__ . '/../layout.php');
include $__layout;
