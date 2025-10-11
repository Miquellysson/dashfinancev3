<?php
use function htmlspecialchars as h;

$statusOptions = ProjectActivityModel::STATUS;
$prioridadeOptions = ProjectActivityModel::PRIORIDADES;

$selected = static function ($value, $current) {
    return $value === $current ? 'selected' : '';
};
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
  <div>
    <h1 class="h3 text-gray-800 mb-1">Atividades • <?= h($project['name'] ?? $project['nome_cliente']) ?></h1>
    <p class="text-muted mb-0">Acompanhe o progresso e as tarefas vinculadas a este projeto.</p>
  </div>
  <div class="mt-3 mt-md-0">
    <a href="/projeto/show/<?= (int)$project['id'] ?>" class="btn btn-outline-primary mr-2">
      <i class="fas fa-arrow-left mr-1"></i>Voltar ao projeto
    </a>
    <button class="btn btn-primary" data-toggle="modal" data-target="#modalNovaAtividade">
      <i class="fas fa-plus mr-1"></i>Nova atividade
    </button>
  </div>
</div>

<?php if (!empty($errors['general'])): ?>
  <div class="alert alert-danger"><?= h($errors['general']) ?></div>
<?php endif; ?>

<?php if (!empty($errors) && empty($errors['general'])): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $message): ?>
      <div><?= h($message) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card dashboard-card mb-4">
  <div class="card-header d-flex flex-column flex-xl-row justify-content-between align-items-xl-center">
    <div>
      <h6 class="card-title mb-1">Indicadores do projeto</h6>
      <span class="card-subtitle">Resumo da execução com base nas tarefas registradas</span>
    </div>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-4 mb-3">
        <div class="metric-pill neutral">
          <div class="metric-label">Taxa de conclusão</div>
          <div class="metric-value"><?= $metrics['taxa_conclusao'] ?>%</div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="metric-pill success">
          <div class="metric-label">Concluídas</div>
          <div class="metric-value"><?= $metrics['concluidas'] ?>/<?= $metrics['total'] ?></div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="metric-pill danger">
          <div class="metric-label">Atrasadas</div>
          <div class="metric-value"><?= $metrics['atrasadas'] ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card dashboard-card">
  <div class="card-header">
    <h6 class="card-title mb-0">Atividades</h6>
  </div>
  <div class="card-body">
    <form method="get" class="form-row mb-4">
      <div class="col-md-4 mb-2">
        <label class="small text-uppercase text-muted font-weight-semibold">Status</label>
        <select name="status" class="form-control">
          <option value="">Todos</option>
          <?php foreach ($statusOptions as $status): ?>
            <option value="<?= h($status) ?>" <?= $selected($status, $_GET['status'] ?? '') ?>><?= h($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4 mb-2">
        <label class="small text-uppercase text-muted font-weight-semibold">Responsável</label>
        <select name="responsavel_id" class="form-control">
          <option value="">Todos</option>
          <?php foreach ($responsaveis as $resp): ?>
            <option value="<?= (int)$resp['id'] ?>" <?= $selected((string)$resp['id'], $_GET['responsavel_id'] ?? '') ?>>
              <?= h($resp['nome_completo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4 mb-2 d-flex align-items-end justify-content-end">
        <a href="/projeto/atividades/<?= (int)$project['id'] ?>" class="btn btn-link mr-2">Limpar</a>
        <button class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Filtrar</button>
      </div>
    </form>

    <?php if (empty($activities)): ?>
      <div class="text-center text-muted py-5">Nenhuma atividade registrada com os filtros atuais.</div>
    <?php else: ?>
      <ul class="activity-list">
        <?php foreach ($activities as $activity): ?>
          <?php $statusSlug = 'status-' . Utils::slugify($activity['status_atividade']); ?>
          <li class="activity-item">
            <div class="activity-status <?= $statusSlug ?>">
              <?= htmlspecialchars($activity['status_atividade']) ?>
            </div>
            <div class="activity-content">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h5 class="mb-1"><?= htmlspecialchars($activity['titulo_atividade']) ?></h5>
                  <p class="text-muted mb-2"><?= nl2br(htmlspecialchars($activity['descricao'])) ?></p>
                  <div class="small text-muted">
                    <span class="mr-3"><i class="far fa-calendar mr-1"></i><?= Utils::formatDate($activity['data_inicio']) ?></span>
                    <?php if (!empty($activity['data_conclusao'])): ?>
                      <span class="mr-3"><i class="far fa-calendar-check mr-1"></i><?= Utils::formatDate($activity['data_conclusao']) ?></span>
                    <?php endif; ?>
                    <span><i class="far fa-user mr-1"></i><?= htmlspecialchars($activity['responsavel_nome']) ?></span>
                  </div>
                </div>
                <div class="text-right">
                  <span class="badge badge-light mb-2"><?= htmlspecialchars($activity['prioridade']) ?></span>
                  <div class="btn-group btn-group-sm">
                    <a href="/projeto/atividadeEditar/<?= (int)$activity['id'] ?>" class="btn btn-outline-secondary"><i class="fas fa-edit"></i></a>
                    <?php if (Auth::isAdmin()): ?>
                      <a href="/projeto/atividadeExcluir/<?= (int)$activity['id'] ?>" class="btn btn-outline-danger"
                         onclick="return confirm('Deseja excluir esta atividade?')"><i class="fas fa-trash"></i></a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="activity-hours mt-3">
                <span><strong>Horas estimadas:</strong> <?= number_format((float)$activity['horas_estimadas'], 2, ',', '.') ?></span>
                <span><strong>Horas reais:</strong> <?= number_format((float)$activity['horas_reais'], 2, ',', '.') ?></span>
                <?php if ($activity['horas_estimadas'] !== null && $activity['horas_reais'] !== null): ?>
                  <?php $diff = Utils::diffHours((float)$activity['horas_estimadas'], (float)$activity['horas_reais']); ?>
                  <span class="<?= $diff > 0 ? 'text-danger' : 'text-success' ?>">
                    <strong>Diferença:</strong> <?= number_format($diff, 2, ',', '.') ?>h
                  </span>
                <?php endif; ?>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Nova atividade -->
<div class="modal fade" id="modalNovaAtividade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">Nova atividade</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label class="small text-uppercase text-muted">Título *</label>
              <input type="text" name="titulo_atividade" class="form-control" required>
            </div>
            <div class="form-group col-md-3">
              <label class="small text-uppercase text-muted">Status</label>
              <select name="status_atividade" class="form-control">
                <?php foreach ($statusOptions as $status): ?>
                  <option value="<?= h($status) ?>"><?= h($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label class="small text-uppercase text-muted">Prioridade</label>
              <select name="prioridade" class="form-control">
                <?php foreach ($prioridadeOptions as $prioridade): ?>
                  <option value="<?= h($prioridade) ?>"><?= h($prioridade) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="small text-uppercase text-muted">Descrição *</label>
            <textarea name="descricao" rows="4" class="form-control" required></textarea>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label class="small text-uppercase text-muted">Data início *</label>
              <input type="datetime-local" name="data_inicio" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
              <label class="small text-uppercase text-muted">Data conclusão</label>
              <input type="datetime-local" name="data_conclusao" class="form-control">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label class="small text-uppercase text-muted">Responsável</label>
              <select name="responsavel_id" class="form-control">
                <option value="">Selecione</option>
                <?php foreach ($responsaveis as $resp): ?>
                  <option value="<?= (int)$resp['id'] ?>"><?= h($resp['nome_completo']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label class="small text-uppercase text-muted">Horas estimadas</label>
              <input type="number" step="0.25" min="0" name="horas_estimadas" class="form-control">
            </div>
            <div class="form-group col-md-3">
              <label class="small text-uppercase text-muted">Horas reais</label>
              <input type="number" step="0.25" min="0" name="horas_reais" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
