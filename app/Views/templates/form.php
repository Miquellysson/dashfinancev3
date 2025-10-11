<?php
use function htmlspecialchars as h;

$isEdit = !empty($template['id']);
$formTitle = $isEdit ? 'Editar Template' : 'Novo Template';
$actionUrl = $isEdit ? "/templates/edit/{$template['id']}" : '/templates/create';
$errors = $errors ?? [];
$categories = $categories ?? ($this->categories ?? []);
$templateTypes = $templateTypes ?? ($this->templateTypes ?? []);
$title = $formTitle;
?>

<div class="mb-4">
  <h1 class="h3 text-gray-800 mb-1"><?= h($formTitle) ?></h1>
  <p class="text-muted mb-0">Preencha as informações do template para disponibilizá-lo na biblioteca.</p>
</div>

<?php if (!empty($errors['general'])): ?>
  <div class="alert alert-danger"><?= h($errors['general']) ?></div>
<?php endif; ?>

<form method="post" action="<?= h($actionUrl) ?>" enctype="multipart/form-data" novalidate>
  <div class="row">
    <div class="col-lg-6 mb-4">
      <div class="card dashboard-card h-100">
        <div class="card-header">
          <h6 class="card-title mb-0">Informações principais</h6>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="small text-uppercase text-muted">Nome *</label>
            <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                   value="<?= h($template['name'] ?? '') ?>" required minlength="3">
            <?php if (isset($errors['name'])): ?>
              <div class="invalid-feedback"><?= h($errors['name']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase text-muted">Categoria *</label>
            <select name="category" class="form-control <?= isset($errors['category']) ? 'is-invalid' : '' ?>" required>
              <option value="">Selecione</option>
              <?php foreach ($categories as $key => $label): ?>
                <option value="<?= h($key) ?>" <?= (($template['category'] ?? '') === $key) ? 'selected' : '' ?>>
                  <?= h($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category'])): ?>
              <div class="invalid-feedback"><?= h($errors['category']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase text-muted">Tipo *</label>
            <select name="template_type" class="form-control <?= isset($errors['template_type']) ? 'is-invalid' : '' ?>" required>
              <option value="">Selecione</option>
              <?php foreach ($templateTypes as $key => $label): ?>
                <option value="<?= h($key) ?>" <?= (($template['template_type'] ?? '') === $key) ? 'selected' : '' ?>>
                  <?= h($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['template_type'])): ?>
              <div class="invalid-feedback"><?= h($errors['template_type']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase text-muted">Palavras-chave</label>
            <input type="text" name="keywords" class="form-control" value="<?= h($template['keywords'] ?? '') ?>" placeholder="Ex.: landing page, hero, conversão">
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-4">
      <div class="card dashboard-card h-100">
        <div class="card-header">
          <h6 class="card-title mb-0">Descrição e origem</h6>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="small text-uppercase text-muted">Link / URL</label>
            <input type="url" name="link" class="form-control <?= isset($errors['link']) ? 'is-invalid' : '' ?>"
                   value="<?= h($template['link'] ?? '') ?>" placeholder="https://...">
            <?php if (isset($errors['link'])): ?>
              <div class="invalid-feedback"><?= h($errors['link']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase text-muted">Link para download</label>
            <input type="url" name="download_url" class="form-control <?= isset($errors['download_url']) ? 'is-invalid' : '' ?>"
                   value="<?= h($template['download_url'] ?? ($template['file_path'] ?? '')) ?>" placeholder="https://...">
            <small class="form-text text-muted">Informe a URL direta do arquivo ZIP quando o download estiver hospedado externamente.</small>
            <?php if (isset($errors['download_url'])): ?>
              <div class="invalid-feedback"><?= h($errors['download_url']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase text-muted">Caminho na pasta templates</label>
            <input type="text" name="source_path" class="form-control <?= isset($errors['source_path']) ? 'is-invalid' : '' ?>"
                   value="<?= h($template['source_path'] ?? '') ?>" placeholder="Ex.: html/landing-pages/saas-hero">
            <small class="form-text text-muted">Informe o diretório dentro de <code>/templates</code> caso deseje copiar diretamente da estrutura física.</small>
            <?php if (isset($errors['source_path'])): ?>
              <div class="invalid-feedback"><?= h($errors['source_path']) ?></div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase text-muted">Descrição</label>
            <textarea name="description" rows="6" class="form-control" placeholder="Resumo do template, funcionalidades, instruções"><?= h($template['description'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-4">
      <div class="card dashboard-card h-100">
        <div class="card-header">
          <h6 class="card-title mb-0">Anexos</h6>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="small text-uppercase text-muted">Thumbnail (PNG/JPG/WEBP) <?= $isEdit ? '' : '*' ?></label>
            <input type="file" name="screenshot" class="form-control-file <?= isset($errors['screenshot']) ? 'is-invalid' : '' ?>" accept="image/png,image/jpeg,image/webp">
            <?php if (isset($errors['screenshot'])): ?>
              <div class="invalid-feedback d-block"><?= h($errors['screenshot']) ?></div>
            <?php endif; ?>
            <?php if ($isEdit && !empty($template['screenshot_path'])): ?>
              <div class="mt-2">
                <img src="<?= h($template['screenshot_path']) ?>" alt="Thumbnail atual" class="rounded shadow-sm" style="height:80px;">
              </div>
            <?php endif; ?>
          </div>
          <div class="form-group">
            <label class="small text-uppercase text-muted">Arquivo do template (ZIP) <?= $isEdit ? '' : '*' ?></label>
            <input type="file" name="template_file" class="form-control-file <?= isset($errors['template_file']) ? 'is-invalid' : '' ?>" accept=".zip">
            <small class="form-text text-muted">Obrigatório caso não seja informado um diretório em <code>/templates</code>.</small>
            <?php if (isset($errors['template_file'])): ?>
              <div class="invalid-feedback d-block"><?= h($errors['template_file']) ?></div>
            <?php endif; ?>
            <?php if ($isEdit && !empty($template['file_path'])): ?>
              <div class="mt-2">
                <a href="/templates/download/<?= (int)$template['id'] ?>" class="btn btn-outline-primary btn-sm">
                  <i class="fas fa-download mr-1"></i>Baixar arquivo atual
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end">
    <a href="/templates" class="btn btn-light mr-3">Cancelar</a>
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save mr-1"></i>Salvar template
    </button>
  </div>
</form>
