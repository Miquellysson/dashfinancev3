<?php
use function htmlspecialchars as h;
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
  <div>
    <h1 class="h3 text-gray-800 mb-1">Usuários</h1>
    <p class="text-muted mb-0">Gerencie acessos, permissões e status.</p>
  </div>
  <div class="mt-3 mt-md-0">
    <a href="/usuario/create" class="btn btn-primary"><i class="fas fa-user-plus mr-1"></i>Novo usuário</a>
  </div>
</div>

<div class="card dashboard-card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-modern mb-0">
        <thead>
          <tr>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Tipo</th>
            <th>Telefone</th>
            <th>Status</th>
            <th>Último acesso</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $usuario): ?>
            <?php $telefoneFormatado = Utils::formatPhone($usuario['telefone'] ?? null); ?>
            <tr>
              <td><?= h($usuario['nome_completo']) ?></td>
              <td><?= h($usuario['email']) ?></td>
              <td><span class="badge badge-soft-secondary"><?= h($usuario['tipo_usuario']) ?></span></td>
              <td><?= h($telefoneFormatado !== '' ? $telefoneFormatado : '—') ?></td>
              <td>
                <span class="status-pill <?= $usuario['ativo'] ? 'badge-soft-success' : 'badge-soft-danger' ?>">
                  <span class="status-dot"></span><?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?>
                </span>
              </td>
              <td><?= $usuario['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) : '—' ?></td>
              <td class="text-right">
                <div class="btn-group btn-group-sm">
                  <a href="/usuario/edit/<?= (int)$usuario['id'] ?>" class="btn btn-outline-secondary" data-toggle="tooltip" title="Editar"><i class="fas fa-edit"></i></a>
                  <a href="/usuario/toggle/<?= (int)$usuario['id'] ?>" class="btn btn-outline-warning" data-toggle="tooltip" title="Ativar/Inativar"><i class="fas fa-power-off"></i></a>
                  <a href="/usuario/reset-senha/<?= (int)$usuario['id'] ?>" class="btn btn-outline-danger" data-toggle="tooltip" title="Resetar senha" onclick="return confirm('Gerar nova senha para este usuário?')"><i class="fas fa-key"></i></a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($usuarios)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum usuário cadastrado.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
