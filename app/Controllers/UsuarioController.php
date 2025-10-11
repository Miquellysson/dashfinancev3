<?php

require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Helpers/RateLimiter.php';

class UsuarioController {
    private $pdo;
    private $users;
    private $rateLimiter;

    public function __construct(PDO $pdo) {
        Auth::requireAdmin();
        $this->pdo = $pdo;
        $this->users = new UserModel($pdo);
        $this->rateLimiter = new RateLimiter($pdo);
    }

    public function index() {
        $stmt = $this->pdo->query('SELECT id, nome_completo, email, tipo_usuario, telefone, ativo, ultimo_acesso FROM users WHERE deleted_at IS NULL ORDER BY nome_completo');
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $title = 'Usuários';
        ob_start();
        include __DIR__ . '/../Views/usuarios/list.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function create() {
        $errors = [];
        $usuario = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (!$this->rateLimiter->allow($ip, 'user_create', 5, 10)) {
                $errors['general'] = 'Muitas tentativas. Aguarde alguns minutos antes de tentar novamente.';
            } else {
                [$payload, $errors] = $this->validateUser($_POST, true);
                if (empty($errors)) {
                    if (!empty($_FILES['foto_perfil']['name'])) {
                        $upload = $this->handleProfileUpload($_FILES['foto_perfil']);
                        if (isset($upload['error'])) {
                            $errors['foto_perfil'] = $upload['error'];
                        } else {
                            $payload['foto_perfil'] = $upload['path'];
                        }
                    }
                    if (empty($errors)) {
                        $userId = $this->users->create($payload);
                        $this->users->logAction($_SESSION['user_id'], 'create', ['user_id' => $userId]);
                        Utils::redirect('/usuario', 'Usuário criado com sucesso!');
                        return;
                    }
                }
                $usuario = $_POST;
            }
        }

        $title = 'Novo Usuário';
        ob_start();
        include __DIR__ . '/../Views/usuarios/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function edit($id) {
        $id = (int)$id;
        $usuario = $this->users->find($id);
        if (!$usuario) {
            Utils::redirect('/usuario', 'Usuário não encontrado.');
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$payload, $errors] = $this->validateUser($_POST, false, $id);
            if (empty($errors)) {
                if (!empty($_FILES['foto_perfil']['name'])) {
                    $upload = $this->handleProfileUpload($_FILES['foto_perfil']);
                    if (isset($upload['error'])) {
                        $errors['foto_perfil'] = $upload['error'];
                    } else {
                        $payload['foto_perfil'] = $upload['path'];
                    }
                } else {
                    $payload['foto_perfil'] = $usuario['foto_perfil'];
                }

                if (empty($errors)) {
                    $this->users->update($id, $payload);
                    $this->users->logAction($_SESSION['user_id'], 'update', ['user_id' => $id]);
                    Utils::redirect('/usuario', 'Usuário atualizado com sucesso!');
                    return;
                }
            }
            $usuario = array_merge($usuario, $_POST);
        }

        $title = 'Editar Usuário';
        ob_start();
        include __DIR__ . '/../Views/usuarios/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function toggle($id) {
        $id = (int)$id;
        $user = $this->users->find($id);
        if (!$user) {
            Utils::redirect('/usuario', 'Usuário não encontrado.');
        }
        $this->users->toggleActive($id, !(bool)$user['ativo']);
        $this->users->logAction($_SESSION['user_id'], 'status_change', ['user_id' => $id, 'active' => !$user['ativo']]);
        Utils::redirect('/usuario', 'Status atualizado.');
    }

    public function resetSenha($id) {
        $id = (int)$id;
        $user = $this->users->find($id);
        if (!$user) {
            Utils::redirect('/usuario', 'Usuário não encontrado.');
        }

        $novaSenha = bin2hex(random_bytes(4));
        $payload = [
            'nome_completo' => $user['nome_completo'],
            'email' => $user['email'],
            'tipo_usuario' => $user['tipo_usuario'],
            'telefone' => $user['telefone'],
            'cargo' => $user['cargo'],
            'foto_perfil' => $user['foto_perfil'],
            'ativo' => $user['ativo'],
            'password' => $novaSenha,
        ];
        $this->users->update($id, $payload);
        $this->users->logAction($_SESSION['user_id'], 'password_reset', ['user_id' => $id]);

        Utils::redirect('/usuario', 'Senha redefinida: ' . $novaSenha);
    }

    private function validateUser(array $input, bool $isCreate, ?int $userId = null): array {
        $errors = [];
        $nome = trim($input['nome_completo'] ?? '');
        $email = strtolower(trim($input['email'] ?? ''));
        $senha = $input['password'] ?? '';
        $confirm = $input['password_confirmation'] ?? '';
        $tipo = $input['tipo_usuario'] ?? 'Colaborador';

        if (mb_strlen($nome) < 3) {
            $errors['nome_completo'] = 'Nome precisa ter ao menos 3 caracteres.';
        }
        if (!Utils::validateEmail($email)) {
            $errors['email'] = 'Informe um e-mail válido.';
        } else {
            $existing = $this->users->findByEmail($email);
            if ($existing && ($isCreate || $existing['id'] !== $userId)) {
                $errors['email'] = 'E-mail já cadastrado.';
            }
        }

        if ($isCreate || $senha !== '') {
            if (mb_strlen($senha) < 8 || !preg_match('/[A-Z]/', $senha) || !preg_match('/[a-z]/', $senha) || !preg_match('/\\d/', $senha)) {
                $errors['password'] = 'Senha deve ter ao menos 8 caracteres, com maiúsculas, minúsculas e números.';
            }
            if ($senha !== $confirm) {
                $errors['password_confirmation'] = 'Senhas não conferem.';
            }
        }

        if (!in_array($tipo, ['Admin','Gerente','Colaborador','Cliente'], true)) {
            $tipo = 'Colaborador';
        }

        $payload = [
            'nome_completo' => Utils::sanitize($nome),
            'email' => $email,
            'tipo_usuario' => $tipo,
            'telefone' => Utils::normalizePhone($input['telefone'] ?? ''),
            'cargo' => Utils::sanitize($input['cargo'] ?? ''),
            'ativo' => isset($input['ativo']) ? 1 : 0,
        ];

        if ($isCreate || $senha !== '') {
            $payload['password'] = $senha;
        }

        return [$payload, $errors];
    }

    private function handleProfileUpload(array $file): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Falha ao enviar arquivo.'];
        }

        $allowed = ['image/jpeg','image/png','image/webp'];
        if (!in_array(mime_content_type($file['tmp_name']), $allowed, true)) {
            return ['error' => 'Formato de imagem inválido.'];
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            return ['error' => 'Imagem acima de 2MB.'];
        }

        $targetDir = __DIR__ . '/../../uploads/avatars';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('avatar_', true) . '.' . strtolower($ext);
        $fullPath = $targetDir . '/' . $filename;

        if (!$this->resizeImage($file['tmp_name'], $fullPath, 200, 200)) {
            return ['error' => 'Não foi possível processar a imagem.'];
        }

        return ['path' => '/uploads/avatars/' . $filename];
    }

    private function resizeImage(string $src, string $dest, int $width, int $height): bool {
        $info = getimagesize($src);
        if (!$info) return false;

        [$origWidth, $origHeight] = $info;
        $type = $info[2];

        switch ($type) {
            case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($src); break;
            case IMAGETYPE_PNG: $image = imagecreatefrompng($src); break;
            case IMAGETYPE_WEBP: $image = imagecreatefromwebp($src); break;
            default: return false;
        }

        $thumb = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        switch ($type) {
            case IMAGETYPE_JPEG: imagejpeg($thumb, $dest, 85); break;
            case IMAGETYPE_PNG: imagepng($thumb, $dest, 8); break;
            case IMAGETYPE_WEBP: imagewebp($thumb, $dest, 80); break;
        }

        imagedestroy($image);
        imagedestroy($thumb);
        return true;
    }
}
