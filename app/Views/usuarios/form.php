<?php
use function htmlspecialchars as h;

$isEdit = !empty($usuario['id']);
$errors = $errors ?? [];

function field(array $usuario, string $name, $default = '') {
    return h($usuario[$name] ?? $default);
}
?>

<div class="mb-4">
  <h1 class="h3 text-gray-800 mb-1"><?= $isEdit ? 'Editar Usuário' : 'Novo Usuário' ?></h1>
  <p class="text-muted mb-0">Defina as permissões e dados de acesso.</p>
</div>

<?php if (!empty($errors['general'])): ?>
  <div class="alert alert-danger"><?= h($errors['general']) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <div class="card dashboard-card">
    <div class="card-body">
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="small text-uppercase text-muted">Nome completo *</label>
          <input type="text" name="nome_completo" class="form-control <?= isset($errors['nome_completo']) ? 'is-invalid' : '' ?>" value="<?= field($usuario ?? [], 'nome_completo') ?>" required minlength="3">
          <?php if (isset($errors['nome_completo'])): ?><div class="invalid-feedback"><?= h($errors['nome_completo']) ?></div><?php endif; ?>
        </div>
        <div class="form-group col-md-6">
          <label class="small text-uppercase text-muted">E-mail *</label>
          <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= field($usuario ?? [], 'email') ?>" required>
          <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= h($errors['email']) ?></div><?php endif; ?>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-4">
          <label class="small text-uppercase text-muted">Tipo de usuário</label>
          <?php $tipo = $usuario['tipo_usuario'] ?? 'Colaborador'; ?>
          <select name="tipo_usuario" class="form-control">
            <?php foreach (['Admin','Gerente','Colaborador','Cliente'] as $option): ?>
              <option value="<?= $option ?>" <?= $tipo === $option ? 'selected' : '' ?>><?= $option ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label class="small text-uppercase text-muted">Telefone</label>
          <input type="text" name="telefone" class="form-control" value="<?= field($usuario ?? [], 'telefone') ?>">
        </div>
        <div class="form-group col-md-4">
          <label class="small text-uppercase text-muted">Cargo</label>
          <input type="text" name="cargo" class="form-control" value="<?= field($usuario ?? [], 'cargo') ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="small text-uppercase text-muted">Senha <?= $isEdit ? '(opcional)' : '*' ?></label>
          <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" <?= $isEdit ? '' : 'required' ?>>
          <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= h($errors['password']) ?></div><?php endif; ?>
        </div>
        <div class="form-group col-md-6">
          <label class="small text-uppercase text-muted">Confirmar senha <?= $isEdit ? '' : '*' ?></label>
          <input type="password" name="password_confirmation" class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" <?= $isEdit ? '' : 'required' ?>>
          <?php if (isset($errors['password_confirmation'])): ?><div class="invalid-feedback"><?= h($errors['password_confirmation']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="small text-uppercase text-muted">Foto de perfil (200x200px)</label>
          <input type="file" name="foto_perfil" class="form-control-file <?= isset($errors['foto_perfil']) ? 'is-invalid' : '' ?>" accept="image/jpeg,image/png,image/webp">
          <?php if (isset($errors['foto_perfil'])): ?><div class="invalid-feedback d-block"><?= h($errors['foto_perfil']) ?></div><?php endif; ?>
          <?php if (!empty($usuario['foto_perfil'])): ?>
            <div class="mt-2"><img src="<?= h($usuario['foto_perfil']) ?>" alt="Avatar" class="rounded-circle" width="60" height="60"></div>
          <?php endif; ?>
        </div>
        <div class="form-group col-md-3 d-flex align-items-center">
          <div class="custom-control custom-switch mt-4">
            <input type="checkbox" class="custom-control-input" id="ativoSwitch" name="ativo" <?= !isset($usuario['ativo']) || $usuario['ativo'] ? 'checked' : '' ?>>
            <label class="custom-control-label" for="ativoSwitch">Usuário ativo</label>
          </div>
        </div>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-end">
      <a href="/usuario" class="btn btn-light mr-3">Cancelar</a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Salvar</button>
    </div>
  </div>
</form>
