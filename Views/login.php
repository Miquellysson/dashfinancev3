
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Gestão Financeira</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet" />
</head>
<body class="bg-gradient-primary">
  <div class="container">
    <div class="row justify-content-center mt-5">
      <div class="col-xl-5 col-lg-6 col-md-8">
        <div class="card o-hidden border-0 shadow-lg my-5">
          <div class="card-body p-0">
            <div class="p-5">
              <div class="text-center">
                <h1 class="h4 text-gray-900 mb-4">Bem-vindo ao Sistema!</h1>
              </div>
              <?php if (!empty($error) || isset($_GET['error'])): ?>
                <div class="alert alert-danger">Usuário ou senha inválidos</div>
              <?php endif; ?>
              <form class="user" method="post" action="/auth/doLogin">
                <div class="form-group">
                  <input type="email" class="form-control form-control-user" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                  <input type="password" class="form-control form-control-user" name="senha" placeholder="Senha" required>
                </div>
                <button class="btn btn-primary btn-user btn-block" type="submit">Entrar</button>
              </form>
              <hr>
              <div class="text-center">
                <small class="text-muted">
                  <strong>Acesso padrão:</strong><br>
                  Email: admin@arkaleads.com<br>
                  Senha: admin123
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
