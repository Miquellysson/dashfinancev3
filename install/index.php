
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Gestão Financeira</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet" />
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-xl-8 col-lg-10">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="p-5">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4">Instalação do Sistema</h1>
                            </div>

                            <div class="alert alert-info">
                                <h5>Passos para instalação:</h5>
                                <ol>
                                    <li>Configure as credenciais do banco em <code>config/database.php</code></li>
                                    <li>Importe o arquivo <code>install/schema.sql</code> no seu banco MySQL</li>
                                    <li>Acesse <a href="/auth/login">/auth/login</a> para entrar no sistema</li>
                                </ol>
                            </div>

                            <div class="alert alert-success">
                                <h5>Credenciais padrão:</h5>
                                <p><strong>Admin:</strong> admin@arkaleads.com / admin123</p>
                                <p><strong>Operador:</strong> operador@arkaleads.com / admin123</p>
                            </div>

                            <div class="text-center">
                                <a href="/auth/login" class="btn btn-primary">Ir para Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
