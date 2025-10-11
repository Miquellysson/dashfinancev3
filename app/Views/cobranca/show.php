<?php
$prioridades = [
    'baixa' => 'badge-soft-success',
    'media' => 'badge-soft-warning',
    'alta' => 'badge-soft-danger',
];
$statusLabels = [
    'aberto' => 'Aberto',
    'negociando' => 'Negociando',
    'pago' => 'Pago',
    'cancelado' => 'Cancelado',
];
$tiposTarefa = [
    'ligacao' => 'Ligação',
    'whatsapp' => 'WhatsApp',
    'email' => 'E-mail',
    'reuniao' => 'Reunião',
    'outro' => 'Outro',
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($case['titulo']) ?></h1>
    <div class="text-muted small">
      Cliente: <?= htmlspecialchars($case['cliente_nome']) ?> &bullet;
      Responsável: <?= htmlspecialchars($case['responsavel_nome'] ?? '—') ?>
    </div>
  </div>
  <div class="btn-group">
    <a href="/cobranca" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-2"></i>Voltar</a>
    <a href="/cobranca/editar/<?= $case['id'] ?>" class="btn btn-primary"><i class="fas fa-edit mr-2"></i>Editar</a>
    <a href="/cobranca/excluir/<?= $case['id'] ?>" class="btn btn-danger" onclick="return confirm('Confirmar exclusão?')"><i class="fas fa-trash mr-2"></i>Excluir</a>
  </div>
</div>

<div class="row">
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="text-muted text-uppercase">Resumo</h6>
        <p class="mb-1"><strong>Status:</strong> <?= htmlspecialchars($statusLabels[$case['status']] ?? $case['status']) ?></p>
        <p class="mb-1">
          <strong>Prioridade:</strong>
          <span class="badge <?= $prioridades[$case['prioridade']] ?? 'badge-soft-secondary' ?>">
            <?= strtoupper($case['prioridade']) ?>
          </span>
        </p>
        <p class="mb-1"><strong>Valor total:</strong> <?= Utils::formatMoney($case['valor_total']) ?></p>
        <p class="mb-1"><strong>Pendente:</strong> <?= Utils::formatMoney($case['valor_pendente']) ?></p>
        <?php if (!empty($case['proxima_acao_em'])): ?>
          <p class="mb-1"><strong>Próxima ação:</strong> <?= Utils::formatDate($case['proxima_acao_em']) ?></p>
        <?php endif; ?>
        <?php if (!empty($case['encerrado_em'])): ?>
          <p class="mb-1"><strong>Encerrado em:</strong> <?= Utils::formatDate($case['encerrado_em']) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">Adicionar tarefa</div>
      <div class="card-body">
        <form action="/cobranca/tarefa-criar/<?= $case['id'] ?>" method="post">
          <div class="form-group">
            <label>Título</label>
            <input type="text" name="titulo" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Responsável</label>
            <select name="responsavel_id" class="form-control">
              <option value="">Não definido</option>
              <?php foreach ($responsaveis as $user): ?>
                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['nome_completo']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Tipo</label>
            <select name="tipo" class="form-control">
              <?php foreach ($tiposTarefa as $value => $label): ?>
                <option value="<?= $value ?>"><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Prazo</label>
            <input type="datetime-local" name="due_at" class="form-control">
          </div>
          <div class="form-group">
            <label>Lembrete (minutos antes)</label>
            <input type="number" name="lembrete_minutos" class="form-control" min="0" step="5">
          </div>
          <div class="form-group">
            <label>Observações</label>
            <textarea name="descricao" rows="3" class="form-control"></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Adicionar tarefa</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">Atividades</h5>
        <?php if (!$tasks): ?>
          <p class="text-muted mb-0">Nenhuma tarefa registrada.</p>
        <?php endif; ?>
        <?php foreach ($tasks as $task): ?>
          <div class="cobranca-task <?= $task['status'] === 'feito' ? 'done' : '' ?>">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong><?= htmlspecialchars($task['titulo']) ?></strong>
                <div class="small text-muted">
                  <?= htmlspecialchars($tiposTarefa[$task['tipo']] ?? $task['tipo']) ?> &bullet;
                  Responsável: <?= htmlspecialchars($task['responsavel_nome'] ?? '—') ?>
                </div>
              </div>
              <form action="/cobranca/tarefa-excluir/<?= $case['id'] ?>/<?= $task['id'] ?>" method="post" onsubmit="return confirm('Remover tarefa?')">
                <button class="btn btn-link text-danger p-0"><i class="fas fa-trash"></i></button>
              </form>
            </div>
            <?php if (!empty($task['descricao'])): ?>
              <p class="small mb-2"><?= nl2br(htmlspecialchars($task['descricao'])) ?></p>
            <?php endif; ?>
            <div class="small text-muted">
              <?= $task['due_at'] ? 'Prazo: ' . Utils::formatDate($task['due_at']) : '' ?>
              <?= $task['completed_at'] ? ' &bullet; Concluída em ' . Utils::formatDate($task['completed_at']) : '' ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (!empty($case['observacoes'])): ?>
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Observações do caso</h5>
          <p class="mb-0"><?= nl2br(htmlspecialchars($case['observacoes'])) ?></p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
