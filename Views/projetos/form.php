<?php
use function htmlspecialchars as h;

$isEdit = !empty($project['id']);
$formTitle = $isEdit ? 'Editar Projeto' : 'Novo Projeto';
$actionUrl = $isEdit ? "/projeto/edit/{$project['id']}" : '/projeto/create';
$title = $formTitle;

$old = static function ($field, $default = '') use ($project) {
    return h($project[$field] ?? $default);
};

$errors = $errors ?? [];
?>

<div class="mb-4">
  <h1 class="h3 text-gray-800"><?= $formTitle ?></h1>
  <p class="text-muted mb-0">Preencha os dados principais para acompanhar a execução e o financeiro.</p>
</div>

<?php if (!empty($errors['general'])): ?>
  <div class="alert alert-danger"><?= h($errors['general']) ?></div>
<?php endif; ?>

<form method="post" action="<?= h($actionUrl) ?>" novalidate>
  <div class="row">
    <div class="col-lg-4 mb-4">
      <div class="card dashboard-card h-100">
        <div class="card-header">
          <h6 class="card-title mb-0">Cliente e responsável</h6>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Cliente (opcional)</label>
            <select name="client_id" class="form-control">
              <option value="">Sem vínculo</option>
              <?php foreach ($clients as $client): ?>
                <option value="<?= (int)$client['id'] ?>" <?= (isset($project['client_id']) && (int)$project['client_id'] === (int)$client['id']) ? 'selected' : '' ?>>
                  <?= h($client['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Nome do cliente *</label>
            <input type="text" name="nome_cliente" class="form-control <?= isset($errors['nome_cliente']) ? 'is-invalid' : '' ?>"
                   value="<?= $old('nome_cliente') ?>" minlength="3" required>
            <?php if (isset($errors['nome_cliente'])): ?>
              <div class="invalid-feedback"><?= h($errors['nome_cliente']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Título do projeto</label>
            <input type="text" name="name" class="form-control" value="<?= $old('name') ?>" placeholder="Ex.: Site institucional">
          </div>
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Responsável</label>
            <select name="usuario_responsavel_id" class="form-control">
              <option value="">Selecione</option>
              <?php foreach ($responsaveis as $resp): ?>
                <option value="<?= (int)$resp['id'] ?>" <?= (isset($project['usuario_responsavel_id']) && (int)$project['usuario_responsavel_id'] === (int)$resp['id']) ? 'selected' : '' ?>>
                  <?= h($resp['nome_completo']) ?> (<?= h($resp['tipo_usuario']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4 mb-4">
      <div class="card dashboard-card h-100">
        <div class="card-header">
          <h6 class="card-title mb-0">Informações do projeto</h6>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Data de entrada *</label>
            <input type="datetime-local" name="data_entrada" class="form-control <?= isset($errors['data_entrada']) ? 'is-invalid' : '' ?>"
                   value="<?= $old('data_entrada', date('Y-m-d\TH:i')) ?>" required>
            <?php if (isset($errors['data_entrada'])): ?>
              <div class="invalid-feedback"><?= h($errors['data_entrada']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Tipo de serviço</label>
            <select name="tipo_servico" class="form-control">
              <?php foreach (ProjectModel::SERVICE_TYPES as $tipo): ?>
                <option value="<?= h($tipo) ?>" <?= ($project['tipo_servico'] ?? '') === $tipo ? 'selected' : '' ?>>
                  <?= h($tipo) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Status satisfação</label>
            <select name="status_satisfacao" class="form-control">
              <?php foreach (ProjectModel::SATISFACTION_STATUS as $status): ?>
                <option value="<?= h($status) ?>" <?= ($project['status_satisfacao'] ?? '') === $status ? 'selected' : '' ?>>
                  <?= h($status) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Status do projeto</label>
            <?php $statusProjeto = $project['status'] ?? 'ativo'; ?>
            <select name="status" class="form-control">
              <option value="ativo" <?= $statusProjeto === 'ativo' ? 'selected' : '' ?>>Ativo</option>
              <option value="pausado" <?= $statusProjeto === 'pausado' ? 'selected' : '' ?>>Pausado</option>
              <option value="concluido" <?= $statusProjeto === 'concluido' ? 'selected' : '' ?>>Concluído</option>
              <option value="cancelado" <?= $statusProjeto === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4 mb-4">
      <div class="card dashboard-card h-100">
        <div class="card-header">
          <h6 class="card-title mb-0">Financeiro</h6>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Valor do projeto *</label>
            <input type="text" name="valor_projeto" class="form-control money-input <?= isset($errors['valor_projeto']) ? 'is-invalid' : '' ?>"
                   value="<?= $old('valor_projeto', '0,00') ?>" required>
            <?php if (isset($errors['valor_projeto'])): ?>
              <div class="invalid-feedback"><?= h($errors['valor_projeto']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Valor pago</label>
            <input type="text" name="valor_pago" class="form-control money-input <?= isset($errors['valor_pago']) ? 'is-invalid' : '' ?>"
                   value="<?= $old('valor_pago', '0,00') ?>">
            <?php if (isset($errors['valor_pago'])): ?>
              <div class="invalid-feedback"><?= h($errors['valor_pago']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Status de pagamento</label>
            <select name="status_pagamento" class="form-control">
              <?php foreach (ProjectModel::PAYMENT_STATUS as $status): ?>
                <option value="<?= h($status) ?>" <?= ($project['status_pagamento'] ?? 'Pendente') === $status ? 'selected' : '' ?>><?= h($status) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="small text-uppercase font-weight-semibold">Observações</label>
            <textarea name="observacoes" rows=5 class="form-control" placeholder="Detalhes importantes, combinações comerciais, etc."><?= h($project['observacoes'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-end mt-3">
    <a href="/projeto" class="btn btn-light mr-3">Cancelar</a>
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save mr-1"></i>Salvar projeto
    </button>
  </div>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const moneyInputs = document.querySelectorAll('.money-input');
    const formatMoney = (value) => {
      const clean = value.replace(/[^\d,]/g, '').replace(/\./g, '').replace(',', '.');
      const number = Number(clean || 0);
      return number.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };

    moneyInputs.forEach((input) => {
      input.addEventListener('blur', () => {
        input.value = formatMoney(input.value);
      });
      input.dispatchEvent(new Event('blur'));
    });
  });
</script>
