
<?php
$title = ($client ? 'Editar' : 'Novo') . ' Cliente';
ob_start();
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
  <h1 class="h3 mb-0 text-gray-800"><?= $client ? 'Editar' : 'Novo' ?> Cliente</h1>
  <a href="/cliente" class="btn btn-secondary btn-sm">
    <i class="fas fa-arrow-left"></i> Voltar
  </a>
</div>

<div class="card shadow mb-4">
  <div class="card-body">
    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="name">Nome *</label>
            <input type="text" class="form-control" id="name" name="name" 
                   value="<?= htmlspecialchars($client['name'] ?? '') ?>" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" 
                   value="<?= htmlspecialchars($client['email'] ?? '') ?>">
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="phone">Telefone</label>
            <input type="text" class="form-control" id="phone" name="phone" 
                   value="<?= htmlspecialchars($client['phone'] ?? '') ?>">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="address">Endereço</label>
            <input type="text" class="form-control" id="address" name="address" 
                   value="<?= htmlspecialchars($client['address'] ?? '') ?>">
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label for="entry_date">Data de entrada</label>
            <?php
              $entryDate = $client['entry_date'] ?? ($_POST['entry_date'] ?? date('Y-m-d'));
              $entryDate = $entryDate ? date('Y-m-d', strtotime($entryDate)) : date('Y-m-d');
            ?>
            <input type="date" class="form-control" id="entry_date" name="entry_date" value="<?= htmlspecialchars($entryDate) ?>">
          </div>
        </div>
        <div class="col-md-8">
          <div class="form-group">
            <label for="notes">Briefing do projeto</label>
            <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Resumo do briefing, principais necessidades, metas..."><?= htmlspecialchars($client['notes'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Salvar
      </button>
      <a href="/cliente" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
