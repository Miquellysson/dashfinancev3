<?php

require_once __DIR__ . '/../Models/UserModel.php';

class PerfilController {
    private $pdo;
    private $users;

    public function __construct(PDO $pdo) {
        Auth::check();
        $this->pdo = $pdo;
        $this->users = new UserModel($pdo);
    }

    public function index() {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            Utils::redirect('/auth/login');
        }

        $usuario = $this->users->find((int)$userId);
        if (!$usuario) {
            Utils::redirect('/dashboard', 'Perfil não encontrado.');
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = [
                'nome_completo' => Utils::sanitize($_POST['nome_completo'] ?? $usuario['nome_completo']),
                'email' => $usuario['email'],
                'tipo_usuario' => $usuario['tipo_usuario'],
                'telefone' => Utils::normalizePhone($_POST['telefone'] ?? $usuario['telefone']),
                'cargo' => Utils::sanitize($_POST['cargo'] ?? $usuario['cargo']),
                'ativo' => $usuario['ativo'],
            ];

            $senhaNova = $_POST['password'] ?? '';
            $senhaConfirm = $_POST['password_confirmation'] ?? '';
            if ($senhaNova !== '') {
                if (mb_strlen($senhaNova) < 8 || !preg_match('/[A-Z]/', $senhaNova) || !preg_match('/[a-z]/', $senhaNova) || !preg_match('/\d/', $senhaNova)) {
                    $errors['password'] = 'Senha deve ter ao menos 8 caracteres, com maiúsculas, minúsculas e números.';
                } elseif ($senhaNova !== $senhaConfirm) {
                    $errors['password_confirmation'] = 'Senhas não conferem.';
                } else {
                    $payload['password'] = $senhaNova;
                }
            }

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
            }

            if (empty($errors)) {
                $this->users->update((int)$userId, $payload);
                $this->users->logAction((int)$userId, 'update', ['self' => true]);
                $_SESSION['user_name'] = $payload['nome_completo'];
                Utils::redirect('/perfil', 'Perfil atualizado!');
            }

            $usuario = array_merge($usuario, $_POST);
        }

        $title = 'Meu Perfil';
        ob_start();
        include __DIR__ . '/../Views/perfil/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
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

        $info = getimagesize($file['tmp_name']);
        if (!$info) {
            return ['error' => 'Imagem inválida.'];
        }

        [$origWidth, $origHeight] = $info;
        $type = $info[2];

        switch ($type) {
            case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($file['tmp_name']); break;
            case IMAGETYPE_PNG: $image = imagecreatefrompng($file['tmp_name']); break;
            case IMAGETYPE_WEBP: $image = imagecreatefromwebp($file['tmp_name']); break;
            default: return ['error' => 'Formato não suportado.'];
        }

        $thumb = imagecreatetruecolor(200, 200);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, 200, 200, $origWidth, $origHeight);

        switch ($type) {
            case IMAGETYPE_JPEG: imagejpeg($thumb, $fullPath, 85); break;
            case IMAGETYPE_PNG: imagepng($thumb, $fullPath, 8); break;
            case IMAGETYPE_WEBP: imagewebp($thumb, $fullPath, 80); break;
        }

        imagedestroy($image);
        imagedestroy($thumb);

        return ['path' => '/uploads/avatars/' . $filename];
    }
}
