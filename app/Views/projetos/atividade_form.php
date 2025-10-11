<?php
use function htmlspecialchars as h;

$statusOptions = ProjectActivityModel::STATUS;
$prioridadeOptions = ProjectActivityModel::PRIORIDADES;
$actionUrl = "/projeto/atividadeEditar/{$activity['id']}";
?>

<div class="mb-4">
  <h1 class="h3 text-gray-800">Editar atividade</h1>
  <p class="text-muted mb-0"><?= h($project['name'] ?? $project['nome_cliente']) ?></p>
</div>

<form method="post">
  <div class="card dashboard-card">
    <div class="card-body">
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="small text-uppercase text-muted">Título</label>
          <input type="text" name="titulo_atividade" class="form-control" value="<?= h($activity['titulo_atividade']) ?>" required>
        </div>
        <div class="form-group col-md-3">
          <label class="small text-uppercase text-muted">Status</label>
          <select name="status_atividade" class="form-control">
            <?php foreach ($statusOptions as $status): ?>
              <option value="<?= h($status) ?>" <?= $status === $activity['status_atividade'] ? 'selected' : '' ?>><?= h($status) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-3">
          <label class="small text-uppercase text-muted">Prioridade</label>
          <select name="prioridade" class="form-control">
            <?php foreach ($prioridadeOptions as $prioridade): ?>
              <option value="<?= h($prioridade) ?>" <?= $prioridade === $activity['prioridade'] ? 'selected' : '' ?>><?= h($prioridade) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="small text-uppercase text-muted">Descrição</label>
        <textarea name="descricao" rows="5" class="form-control" required><?= h($activity['descricao']) ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="small text-uppercase text-muted">Data início</label>
          <input type="datetime-local" name="data_inicio" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($activity['data_inicio'])) ?>" required>
        </div>
        <div class="form-group col-md-6">
          <label class="small text-uppercase text-muted">Data conclusão</label>
          <input type="datetime-local" name="data_conclusao" class="form-control"
                 value="<?= $activity['data_conclusao'] ? date('Y-m-d\TH:i', strtotime($activity['data_conclusao'])) : '' ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-4">
          <label class="small text-uppercase text-muted">Responsável</label>
          <select name="responsavel_id" class="form-control">
            <option value="">Selecione</option>
            <?php foreach ($responsaveis as $resp): ?>
              <option value="<?= (int)$resp['id'] ?>" <?= (int)$resp['id'] === (int)$activity['responsavel_id'] ? 'selected' : '' ?>>
                <?= h($resp['nome_completo']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label class="small text-uppercase text-muted">Horas estimadas</label>
          <input type="number" step="0.25" min="0" name="horas_estimadas" class="form-control"
                 value="<?= h($activity['horas_estimadas']) ?>">
        </div>
        <div class="form-group col-md-4">
          <label class="small text-uppercase text-muted">Horas reais</label>
          <input type="number" step="0.25" min="0" name="horas_reais" class="form-control"
                 value="<?= h($activity['horas_reais']) ?>">
        </div>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-end">
      <a href="/projeto/atividades/<?= (int)$project['id'] ?>" class="btn btn-light mr-3">Cancelar</a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Salvar alterações</button>
    </div>
  </div>
</form>
