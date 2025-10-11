<?php
use function htmlspecialchars as h;

$errors = $errors ?? [];
$form = $form ?? ['project_name' => '', 'target_slug' => ''];
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
  <div>
    <h1 class="h3 text-gray-800 mb-1">Usar Template</h1>
    <p class="text-muted mb-0">Copie o template selecionado para iniciar um novo projeto.</p>
  </div>
  <div class="mt-3 mt-md-0">
    <a href="/templates" class="btn btn-outline-primary"><i class="fas fa-arrow-left mr-1"></i>Voltar</a>
  </div>
</div>

<?php if (!empty($errors['general'])): ?>
  <div class="alert alert-danger"><?= h($errors['general']) ?></div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-5 mb-4">
    <div class="card dashboard-card h-100">
      <div class="card-header">
        <h6 class="card-title mb-0">Detalhes do template</h6>
      </div>
      <div class="card-body">
        <h5 class="mb-2"><?= h($template['name']) ?></h5>
        <p class="text-muted mb-3"><?= h($template['description'] ?? 'Sem descrição.') ?></p>
        <dl class="row small mb-0">
          <dt class="col-5 text-uppercase text-muted">Categoria</dt>
          <dd class="col-7"><?= h($categories[$template['category']] ?? $template['category']) ?></dd>
          <dt class="col-5 text-uppercase text-muted">Tipo</dt>
          <dd class="col-7"><?= h($templateTypes[$template['template_type']] ?? $template['template_type']) ?></dd>
          <?php if (!empty($template['keywords'])): ?>
            <dt class="col-5 text-uppercase text-muted">Tags</dt>
            <dd class="col-7"><?= h($template['keywords']) ?></dd>
          <?php endif; ?>
          <?php if (!empty($template['source_path'])): ?>
            <dt class="col-5 text-uppercase text-muted">Diretório</dt>
            <dd class="col-7"><code>templates/<?= h($template['source_path']) ?></code></dd>
          <?php endif; ?>
          <?php if (!empty($template['file_path'])): ?>
            <dt class="col-5 text-uppercase text-muted">Arquivo ZIP</dt>
            <dd class="col-7"><code><?= h($template['file_path']) ?></code></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-lg-7 mb-4">
    <div class="card dashboard-card h-100">
      <div class="card-header">
        <h6 class="card-title mb-0">Configurar novo projeto</h6>
      </div>
      <div class="card-body">
        <form method="post" id="useTemplateForm">
          <div class="form-group">
            <label class="small text-uppercase text-muted">Nome do projeto *</label>
            <input type="text" name="project_name" id="projectNameInput" class="form-control <?= isset($errors['project_name']) ? 'is-invalid' : '' ?>" value="<?= h($form['project_name'] ?? '') ?>" required minlength="3">
            <?php if (isset($errors['project_name'])): ?>
              <div class="invalid-feedback"><?= h($errors['project_name']) ?></div>
            <?php else: ?>
              <small class="form-text text-muted">Será utilizado para registrar o contexto no manifesto gerado.</small>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase text-muted">Slug destino *</label>
            <input type="text" name="target_slug" id="projectSlugInput" class="form-control <?= isset($errors['target_slug']) ? 'is-invalid' : '' ?>" value="<?= h($form['target_slug'] ?? '') ?>" required>
            <?php if (isset($errors['target_slug'])): ?>
              <div class="invalid-feedback"><?= h($errors['target_slug']) ?></div>
            <?php else: ?>
              <small class="form-text text-muted">Será criado em <code>/uploads/templates/instances/</code>. Ajuste se desejar um identificador diferente.</small>
            <?php endif; ?>
          </div>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-clone mr-1"></i>Copiar template
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/templates.js"></script>
