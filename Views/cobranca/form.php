<?php
$valorTotalField = isset($case['valor_total']) ? number_format((float)$case['valor_total'], 2, ',', '.') : '0,00';
$valorPendenteField = isset($case['valor_pendente']) ? number_format((float)$case['valor_pendente'], 2, ',', '.') : $valorTotalField;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="h3 mb-0 text-gray-800"><?= isset($case) ? 'Editar cobrança' : 'Nova cobrança' ?></h1>
  </div>
  <div>
    <a href="/cobranca" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-2"></i>Voltar</a>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?= isset($case) ? '/cobranca/editar/'.$case['id'] : '/cobranca/criar' ?>">
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Cliente</label>
          <select name="client_id" class="form-control" required>
            <option value="">Selecione...</option>
            <?php foreach ($clients as $cliente): ?>
              <option value="<?= $cliente['id'] ?>" <?= ($case['client_id'] ?? null) == $cliente['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cliente['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>Título</label>
          <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($case['titulo'] ?? '') ?>" required>
        </div>
        <div class="form-group col-md-4">
          <label>Responsável</label>
          <select name="responsavel_id" class="form-control">
            <option value="">Não definido</option>
            <?php foreach ($responsaveis as $user): ?>
              <option value="<?= $user['id'] ?>" <?= ($case['responsavel_id'] ?? null) == $user['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($user['nome_completo']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-3">
          <label>Valor total</label>
          <input type="text" name="valor_total" class="form-control" value="<?= htmlspecialchars($valorTotalField) ?>">
        </div>
        <div class="form-group col-md-3">
          <label>Valor pendente</label>
          <input type="text" name="valor_pendente" class="form-control" value="<?= htmlspecialchars($valorPendenteField) ?>">
        </div>
        <div class="form-group col-md-3">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="aberto" <?= ($case['status'] ?? '') === 'aberto' ? 'selected' : '' ?>>Aberto</option>
            <option value="negociando" <?= ($case['status'] ?? '') === 'negociando' ? 'selected' : '' ?>>Negociando</option>
            <option value="pago" <?= ($case['status'] ?? '') === 'pago' ? 'selected' : '' ?>>Pago</option>
            <option value="cancelado" <?= ($case['status'] ?? '') === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>Prioridade</label>
          <select name="prioridade" class="form-control">
            <option value="baixa" <?= ($case['prioridade'] ?? '') === 'baixa' ? 'selected' : '' ?>>Baixa</option>
            <option value="media" <?= ($case['prioridade'] ?? '') === 'media' ? 'selected' : '' ?>>Média</option>
            <option value="alta" <?= ($case['prioridade'] ?? '') === 'alta' ? 'selected' : '' ?>>Alta</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-4">
          <label>Próxima ação</label>
          <input type="datetime-local" name="proxima_acao_em" class="form-control" value="<?= isset($case['proxima_acao_em']) ? date('Y-m-d\TH:i', strtotime($case['proxima_acao_em'])) : '' ?>">
        </div>
        <div class="form-group col-md-4">
          <label>Encerrado em</label>
          <input type="datetime-local" name="encerrado_em" class="form-control" value="<?= isset($case['encerrado_em']) ? date('Y-m-d\TH:i', strtotime($case['encerrado_em'])) : '' ?>">
        </div>
        <div class="form-group col-md-4">
          <label>Origem</label>
          <select name="origem" class="form-control">
            <option value="manual" <?= ($case['origem'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual</option>
            <option value="payment" <?= ($case['origem'] ?? '') === 'payment' ? 'selected' : '' ?>>Pagamentos</option>
          </select>
          <?php if (!empty($case['origem_id'])): ?>
            <input type="hidden" name="origem_id" value="<?= (int)$case['origem_id'] ?>">
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Observações</label>
        <textarea name="observacoes" rows="4" class="form-control"><?= htmlspecialchars($case['observacoes'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary">Salvar</button>
    </form>
  </div>
</div>
