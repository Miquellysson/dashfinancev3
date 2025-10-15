<?php

require_once __DIR__ . '/../Models/TemplateModel.php';

class TemplatesController {
    private const SCREENSHOT_DIR = __DIR__ . '/../../uploads/templates/screenshots';
    private const FILE_DIR = __DIR__ . '/../../uploads/templates/files';
    private const INSTANCE_DIR = __DIR__ . '/../../uploads/templates/instances';

    private PDO $pdo;
    private TemplateModel $templates;

    private array $categories = [
        'wordpress'  => 'WordPress',
        'html'       => 'HTML',
        'react'      => 'React',
        'nextjs'     => 'Next.js',
        'email'      => 'Templates de Email',
        'outros'     => 'Outros',
    ];

    private array $templateTypes = [
        'landing-page' => 'Landing Page',
        'dashboard'    => 'Dashboard',
        'ecommerce'    => 'E-commerce',
        'portfolio'    => 'Portfólio',
        'blog'         => 'Blog',
        'corporate'    => 'Corporativo',
        'component'    => 'Componentes/UI',
        'starter'      => 'Starter/Boilerplate',
        'email'        => 'Template de Email',
        'plugin'       => 'Plugin/Extensão',
        'outro'        => 'Outro',
    ];

    public function __construct(PDO $pdo) {
        Auth::check();
        $this->pdo = $pdo;
        $this->templates = new TemplateModel($pdo);
    }

    public function index(): void {
        $filters = [
            'query'         => trim($_GET['q'] ?? ''),
            'category'      => $_GET['category'] ?? '',
            'template_type' => $_GET['type'] ?? '',
        ];

        $templates = $this->templates->search($filters);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $favoriteIds = [];
        $favoriteTemplates = [];

        if ($userId > 0) {
            $favoriteIds = $this->templates->getFavoriteIds($userId);
            $favoriteTemplates = $this->templates->listFavorites($userId);
        }

        foreach ($templates as &$item) {
            $item['is_favorite'] = in_array((int)$item['id'], $favoriteIds, true);
        }
        unset($item);

        foreach ($favoriteTemplates as &$favorite) {
            $favorite['is_favorite'] = true;
        }
        unset($favorite);

        $categories = $this->categories;
        $templateTypes = $this->templateTypes;
        $activeFilters = $filters;
        $favorites = $favoriteTemplates;
        $title = 'Templates';

        ob_start();
        include __DIR__ . '/../Views/templates/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function create(): void {
        $template = [
            'name' => '',
            'category' => '',
            'template_type' => '',
            'link' => '',
            'description' => '',
            'source_path' => '',
            'keywords' => '',
            'download_url' => '',
        ];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->sanitize($_POST);
            [$isValid, $errors, $payload] = $this->validate($data, $_FILES, false);

            if ($isValid) {
                $this->templates->create($payload);
                Utils::redirect('/templates', 'Template cadastrado com sucesso!');
                return;
            }

            $template = array_merge($template, $data);
        }

        $categories = $this->categories;
        $templateTypes = $this->templateTypes;
        $title = 'Novo Template';

        ob_start();
        include __DIR__ . '/../Views/templates/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function edit($id): void {
        $id = (int)$id;
        $template = $this->templates->find($id);
        if (!$template) {
            Utils::redirect('/templates', 'Template não encontrado.');
        }
        $template['download_url'] = filter_var($template['file_path'] ?? '', FILTER_VALIDATE_URL) ? $template['file_path'] : '';

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->sanitize($_POST);
            [$isValid, $errors, $payload] = $this->validate($data, $_FILES, true, $template);

            if ($isValid) {
                $this->templates->update($id, $payload);
                Utils::redirect('/templates', 'Template atualizado com sucesso!');
                return;
            }

            $template = array_merge($template, $data);
        }

        $categories = $this->categories;
        $templateTypes = $this->templateTypes;
        $title = 'Editar Template';

        ob_start();
        include __DIR__ . '/../Views/templates/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function delete($id): void {
        Auth::requireAdmin();
        $id = (int)$id;
        $template = $this->templates->find($id);
        if (!$template) {
            Utils::redirect('/templates', 'Template não encontrado.');
        }

        $this->templates->delete($id);
        $this->deleteFiles($template);
        Utils::redirect('/templates', 'Template removido com sucesso.');
    }

    public function download($id): void {
        $id = (int)$id;
        $template = $this->templates->find($id);
        if (!$template || empty($template['file_path'])) {
            Utils::redirect('/templates', 'Arquivo não disponível.');
        }

        if (filter_var($template['file_path'], FILTER_VALIDATE_URL)) {
            header('Location: ' . $template['file_path']);
            exit;
        }

        $absolute = __DIR__ . '/../../' . ltrim($template['file_path'], '/');
        if (!is_file($absolute)) {
            Utils::redirect('/templates', 'Arquivo não encontrado no servidor.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($absolute) . '"');
        header('Content-Length: ' . filesize($absolute));
        readfile($absolute);
        exit;
    }

    public function useTemplate($id): void {
        $id = (int)$id;
        $template = $this->templates->find($id);
        if (!$template) {
            Utils::redirect('/templates', 'Template não encontrado.');
        }

        $errors = [];
        $form = [
            'project_name' => $template['name'],
            'target_slug' => Utils::slugify($template['name']),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form['project_name'] = trim($_POST['project_name'] ?? '');
            $form['target_slug'] = trim($_POST['target_slug'] ?? '');

            if (mb_strlen($form['project_name']) < 3) {
                $errors['project_name'] = 'Informe um nome com pelo menos 3 caracteres.';
            }

            $slug = $form['target_slug'] !== '' ? Utils::slugify($form['target_slug']) : Utils::slugify($form['project_name']);
            if ($slug === '') {
                $errors['target_slug'] = 'Slug inválido.';
            }
            $form['target_slug'] = $slug;

            if (empty($errors)) {
                try {
                    $relativePath = $this->instantiateTemplate($template, $form['project_name'], $form['target_slug']);
                    Utils::redirect('/templates', 'Template copiado para ' . $relativePath);
                    return;
                } catch (Throwable $e) {
                    $errors['general'] = 'Não foi possível copiar o template: ' . $e->getMessage();
                }
            }
        }

        $categories = $this->categories;
        $templateTypes = $this->templateTypes;
        $title = 'Usar Template';

        ob_start();
        include __DIR__ . '/../Views/templates/use.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    private function sanitize(array $input): array {
        return [
            'name' => Utils::sanitize($input['name'] ?? ''),
            'category' => $input['category'] ?? '',
            'template_type' => $input['template_type'] ?? '',
            'link' => trim($input['link'] ?? ''),
            'description' => Utils::sanitize($input['description'] ?? ''),
            'source_path' => trim($input['source_path'] ?? ''),
            'keywords' => Utils::sanitize($input['keywords'] ?? ''),
            'download_url' => trim($input['download_url'] ?? ''),
        ];
    }

    private function validate(array $data, array $files, bool $isEdit, array $current = []): array {
        $errors = [];

        if (mb_strlen($data['name']) < 3) {
            $errors['name'] = 'Informe um nome com pelo menos 3 caracteres.';
        }

        if (!array_key_exists($data['category'], $this->categories)) {
            $errors['category'] = 'Selecione uma categoria válida.';
        }

        if (!array_key_exists($data['template_type'], $this->templateTypes)) {
            $errors['template_type'] = 'Selecione um tipo de template válido.';
        }

        $link = $data['link'] !== '' ? $data['link'] : null;
        if ($link && !filter_var($link, FILTER_VALIDATE_URL)) {
            $errors['link'] = 'Informe uma URL válida (inclua http/https).';
        }

        $keywords = $data['keywords'] !== '' ? $data['keywords'] : null;

        $sourcePath = $current['source_path'] ?? null;
        if ($data['source_path'] !== '') {
            try {
                $sourcePath = $this->normalizeSourcePath($data['source_path']);
            } catch (RuntimeException $exception) {
                $errors['source_path'] = $exception->getMessage();
            }
        }

        $screenshotPath = $current['screenshot_path'] ?? null;
        $filePath = $current['file_path'] ?? null;
        $downloadUrl = $data['download_url'] !== '' ? $data['download_url'] : null;
        if ($downloadUrl) {
            if (!filter_var($downloadUrl, FILTER_VALIDATE_URL)) {
                $errors['download_url'] = 'Informe uma URL válida (inclua http/https).';
            } else {
                $filePath = $downloadUrl;
            }
        }

        $screenshotUpload = $files['screenshot'] ?? null;
        $templateUpload = $files['template_file'] ?? null;

        if (!$isEdit || ($screenshotUpload && $screenshotUpload['error'] !== UPLOAD_ERR_NO_FILE)) {
            $result = $this->handleUpload($screenshotUpload, self::SCREENSHOT_DIR, ['image/jpeg','image/png','image/webp'], $screenshotPath);
            if (isset($result['error'])) {
                $errors['screenshot'] = $result['error'];
            } else {
                $screenshotPath = $result['path'];
            }
        }

        if (!$downloadUrl && (!$isEdit || ($templateUpload && $templateUpload['error'] !== UPLOAD_ERR_NO_FILE))) {
            $result = $this->handleUpload($templateUpload, self::FILE_DIR, ['application/zip','application/x-zip-compressed','application/octet-stream'], $filePath);
            if (isset($result['error'])) {
                $errors['template_file'] = $result['error'];
            } else {
                $filePath = $result['path'];
            }
        }

        if (!$isEdit && !$screenshotPath) {
            $errors['screenshot'] = 'Envie uma imagem de capa.';
        }

        if (!$sourcePath && !$filePath) {
            $errors['template_file'] = 'Informe um arquivo ZIP, URL de download ou caminho de origem em /templates.';
        }

        if (!empty($errors)) {
            return [false, $errors, []];
        }

        $payload = [
            'name' => $data['name'],
            'category' => $data['category'],
            'template_type' => $data['template_type'],
            'link' => $link,
            'description' => $data['description'],
            'source_path' => $sourcePath,
            'keywords' => $keywords,
            'screenshot_path' => $screenshotPath,
            'file_path' => $filePath,
        ];

        return [true, [], $payload];
    }

    private function handleUpload(?array $file, string $destination, array $allowedMime, ?string $currentPath): array {
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['path' => $currentPath];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Falha ao enviar arquivo (código ' . $file['error'] . ').'];
        }

        if (!in_array(mime_content_type($file['tmp_name']), $allowedMime, true)) {
            return ['error' => 'Tipo de arquivo não permitido.'];
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            return ['error' => 'Arquivo excede 10MB.'];
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0775, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = Utils::slugify(pathinfo($file['name'], PATHINFO_FILENAME)) . '-' . uniqid() . '.' . strtolower($extension);
        $relativePath = str_replace(__DIR__ . '/../../', '', $destination . '/' . $filename);

        if (!move_uploaded_file($file['tmp_name'], $destination . '/' . $filename)) {
            return ['error' => 'Não foi possível salvar o arquivo.'];
        }

        if ($currentPath) {
            $this->unlinkFile($currentPath);
        }

        return ['path' => '/' . ltrim($relativePath, '/')];
    }

    private function instantiateTemplate(array $template, string $projectName, string $slug): string {
        if (!is_dir(self::INSTANCE_DIR)) {
            mkdir(self::INSTANCE_DIR, 0775, true);
        }

        $timestamp = (new DateTime())->format('Ymd-His');
        $targetDir = rtrim(self::INSTANCE_DIR, '/') . '/' . $slug . '-' . $timestamp;
        if (!mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Não foi possível criar a pasta de destino.');
        }

        $copied = false;

        if (!empty($template['file_path'])) {
            $sourceFile = __DIR__ . '/../../' . ltrim($template['file_path'], '/');
            if (!is_file($sourceFile)) {
                throw new RuntimeException('Arquivo ZIP não encontrado no servidor.');
            }
            $this->extractZip($sourceFile, $targetDir);
            $copied = true;
        }

        if (!$copied) {
            $sourcePath = $template['source_path'] ?? null;
            if (!$sourcePath) {
                throw new RuntimeException('Template não possui arquivo ou caminho de origem configurado.');
            }
            $sourceDir = __DIR__ . '/../../templates/' . ltrim($sourcePath, '/');
            if (!is_dir($sourceDir)) {
                throw new RuntimeException('Diretório de origem não encontrado: ' . $sourcePath);
            }
            $this->copyDirectory($sourceDir, $targetDir);
            $copied = true;
        }

        if (!$copied) {
            throw new RuntimeException('Nenhum recurso disponível para cópia.');
        }

        $manifest = [
            'name' => $template['name'],
            'project_name' => $projectName,
            'category' => $template['category'],
            'template_type' => $template['template_type'],
            'copied_at' => (new DateTime())->format(DateTime::ATOM),
        ];
        file_put_contents($targetDir . '/template-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return str_replace(__DIR__ . '/../../', '/', $targetDir);
    }

    private function extractZip(string $zipPath, string $destination): void {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Extensão ZipArchive não está disponível no servidor.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Não foi possível abrir o arquivo ZIP.');
        }

        if (!$zip->extractTo($destination)) {
            $zip->close();
            throw new RuntimeException('Falha ao extrair o arquivo ZIP.');
        }
        $zip->close();
    }

    private function copyDirectory(string $source, string $destination): void {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
                    throw new RuntimeException('Falha ao criar diretório: ' . $targetPath);
                }
            } else {
                if (!copy($item->getPathname(), $targetPath)) {
                    throw new RuntimeException('Falha ao copiar arquivo: ' . $item->getPathname());
                }
            }
        }
    }

    private function normalizeSourcePath(string $path): string {
        $clean = str_replace('\\', '/', trim($path));
        if ($clean === '') {
            throw new RuntimeException('Informe um caminho relativo dentro da pasta templates/.');
        }

        $clean = preg_replace('#/+#', '/', $clean);
        $clean = ltrim($clean, '/');
        if (strpos($clean, 'templates/') === 0) {
            $clean = substr($clean, strlen('templates/'));
        }

        if (str_contains($clean, '..')) {
            throw new RuntimeException('Caminho inválido.');
        }

        $absolute = __DIR__ . '/../../templates/' . $clean;
        if (!is_dir($absolute)) {
            throw new RuntimeException('Diretório não encontrado dentro de /templates.');
        }

        return $clean;
    }

    private function deleteFiles(array $template): void {
        if (!empty($template['screenshot_path'])) {
            $this->unlinkFile($template['screenshot_path']);
        }
        if (!empty($template['file_path'])) {
            $this->unlinkFile($template['file_path']);
        }
    }

    private function unlinkFile(string $relativePath): void {
        $absolute = __DIR__ . '/../../' . ltrim($relativePath, '/');
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    public function toggleFavorite($id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Utils::redirect('/templates');
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            Utils::redirect('/auth/login');
        }

        $templateId = (int)$id;
        $template = $this->templates->find($templateId);
        if (!$template) {
            Utils::redirect('/templates', 'Template não encontrado.');
        }

        $shouldFavorite = isset($_POST['favorite']) && (int)$_POST['favorite'] === 1;

        if ($shouldFavorite) {
            $this->templates->addFavorite($userId, $templateId);
            $message = 'Template salvo em favoritos.';
        } else {
            $this->templates->removeFavorite($userId, $templateId);
            $message = 'Template removido dos favoritos.';
        }

        $returnTo = $_POST['return_to'] ?? ($_SERVER['HTTP_REFERER'] ?? '/templates');
        if (!is_string($returnTo)) {
            $returnTo = '/templates';
        } else {
            $parsed = parse_url($returnTo);
            $path = $parsed['path'] ?? '/templates';
            $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
            $returnTo = $path . $query;
            if ($returnTo === '') {
                $returnTo = '/templates';
            }
        }

        Utils::redirect($returnTo, $message);
    }
}
