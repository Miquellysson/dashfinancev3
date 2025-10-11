<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
  <div>
    <h1 class="h3 text-gray-800 mb-1"><?= htmlspecialchars($project['name'] ?? 'Projeto') ?></h1>
    <p class="text-muted mb-0">Cliente: <strong><?= htmlspecialchars($project['nome_cliente']) ?></strong> • Responsável: <?= htmlspecialchars($project['responsavel_nome']) ?></p>
  </div>
  <div class="mt-3 mt-md-0">
    <a href="/projeto/edit/<?= (int)$project['id'] ?>" class="btn btn-outline-primary mr-2">
      <i class="fas fa-edit mr-1"></i>Editar
    </a>
    <a href="/projeto/atividades/<?= (int)$project['id'] ?>" class="btn btn-primary">
      <i class="fas fa-tasks mr-1"></i>Gerenciar atividades
    </a>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-4 mb-4">
    <div class="card dashboard-card">
      <div class="card-header">
        <h6 class="card-title mb-0">Situação financeira</h6>
      </div>
      <div class="card-body">
        <p class="mb-2 text-muted small text-uppercase">Status de Pagamento</p>
        <p>
          <span class="status-pill <?= Utils::badgeForPaymentStatus($project['status_pagamento']) ?>">
            <span class="status-dot"></span><?= htmlspecialchars($project['status_pagamento']) ?>
          </span>
        </p>
        <p class="mb-1 text-muted small text-uppercase">Valor do projeto</p>
        <h5><?= Utils::formatMoney($project['valor_projeto']) ?></h5>
        <p class="mb-1 text-muted small text-uppercase">Recebido</p>
        <h6 class="text-success"><?= Utils::formatMoney($project['valor_pago']) ?></h6>
        <p class="mb-1 text-muted small text-uppercase">Pendente</p>
        <h6 class="text-warning"><?= Utils::formatMoney($project['valor_pendente']) ?></h6>
      </div>
    </div>
  </div>
  <div class="col-md-4 mb-4">
    <div class="card dashboard-card">
      <div class="card-header">
        <h6 class="card-title mb-0">Detalhes do projeto</h6>
      </div>
      <div class="card-body">
        <p class="mb-2"><strong>Cliente:</strong> <?= htmlspecialchars($project['nome_cliente']) ?></p>
        <p class="mb-2"><strong>Serviço:</strong> <?= htmlspecialchars($project['tipo_servico']) ?></p>
        <p class="mb-2"><strong>Status:</strong> <?= htmlspecialchars($project['status']) ?></p>
        <p class="mb-2"><strong>Satisfação:</strong> <?= Utils::renderSatisfactionBadge($project['status_satisfacao']) ?></p>
        <p class="mb-2"><strong>Entrada:</strong> <?= Utils::formatDate($project['data_entrada']) ?></p>
        <?php if (!empty($project['paid_at'])): ?>
          <p class="mb-2"><strong>Finalização prevista:</strong> <?= Utils::formatDate($project['paid_at']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-4 mb-4">
    <div class="card dashboard-card">
      <div class="card-header">
        <h6 class="card-title mb-0">Performance das atividades</h6>
      </div>
      <div class="card-body">
        <p class="mb-2 text-muted small text-uppercase">Taxa de conclusão</p>
        <h5><?= $metrics['taxa_conclusao'] ?>%</h5>
        <p class="mb-2"><strong>Total:</strong> <?= $metrics['total'] ?></p>
        <p class="mb-2"><strong>Concluídas:</strong> <?= $metrics['concluidas'] ?></p>
        <p class="mb-2 text-danger"><strong>Atrasadas:</strong> <?= $metrics['atrasadas'] ?></p>
        <p class="mb-2"><strong>Horas estimadas:</strong> <?= number_format((float)$metrics['horas_estimadas'], 2, ',', '.') ?></p>
        <p class="mb-2"><strong>Horas reais:</strong> <?= number_format((float)$metrics['horas_reais'], 2, ',', '.') ?></p>
        <p class="mb-0">
          <strong>Desvio:</strong>
          <?php $desvio = Utils::diffHours((float)$metrics['horas_estimadas'], (float)$metrics['horas_reais']); ?>
          <span class="<?= $desvio > 0 ? 'text-danger' : 'text-success' ?>">
            <?= number_format($desvio, 2, ',', '.') ?> horas
          </span>
        </p>
      </div>
    </div>
  </div>
</div>

<div class="card dashboard-card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="card-title mb-0">Atividades recentes</h6>
    <a href="/projeto/atividades/<?= (int)$project['id'] ?>" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-calendar-alt mr-1"></i>Ver todas
    </a>
  </div>
  <div class="card-body">
    <?php if (empty($activities)): ?>
      <div class="text-center text-muted py-4">Nenhuma atividade cadastrada ainda.</div>
    <?php else: ?>
      <ul class="timeline">
        <?php foreach ($activities as $activity): ?>
          <?php $statusSlug = 'status-' . Utils::slugify($activity['status_atividade']); ?>
          <li class="timeline-item">
            <div class="timeline-marker <?= $statusSlug ?>"></div>
            <div class="timeline-content">
              <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-1"><?= htmlspecialchars($activity['titulo_atividade']) ?></h6>
                <span class="badge badge-light"><?= htmlspecialchars($activity['prioridade']) ?></span>
              </div>
              <p class="text-muted mb-2"><?= nl2br(htmlspecialchars($activity['descricao'])) ?></p>
              <div class="small text-muted">
                <span class="mr-3"><i class="far fa-clock mr-1"></i><?= Utils::formatDate($activity['data_inicio']) ?></span>
                <?php if (!empty($activity['data_conclusao'])): ?>
                  <span class="mr-3"><i class="far fa-calendar-check mr-1"></i><?= Utils::formatDate($activity['data_conclusao']) ?></span>
                <?php endif; ?>
                <span><i class="far fa-user mr-1"></i><?= htmlspecialchars($activity['responsavel_nome']) ?></span>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
