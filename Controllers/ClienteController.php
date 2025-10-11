
<?php
require_once __DIR__ . '/../Models/ClientModel.php';

class ClienteController {
    private $pdo;
    private $model;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->model = new ClientModel($pdo);
    }

    public function index() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $orderBy = $_GET['order_by'] ?? 'name';
        $orderDir = strtoupper($_GET['order_dir'] ?? 'ASC');

        $clients = $this->model->getAll($limit, $offset, $orderBy, $orderDir);
        $total = $this->model->count();
        $totalPages = ceil($total / $limit);
        $currentOrderBy = $orderBy;
        $currentOrderDir = $orderDir;

        include __DIR__ . '/../Views/clientes/list.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => Utils::sanitize($_POST['name'] ?? ''),
                'email' => Utils::sanitize($_POST['email'] ?? ''),
                'phone' => Utils::sanitize($_POST['phone'] ?? ''),
                'address' => Utils::sanitize($_POST['address'] ?? ''),
                'entry_date' => $_POST['entry_date'] ?? date('Y-m-d'),
                'notes' => Utils::sanitize($_POST['notes'] ?? ''),
            ];

            if (empty($data['name'])) {
                $error = 'Nome é obrigatório';
                $client = $data;
                include __DIR__ . '/../Views/clientes/form.php';
                return;
            }

            if ($this->model->create($data)) {
                Utils::redirect('/cliente', 'Cliente criado com sucesso!');
            } else {
                $error = 'Erro ao criar cliente';
                $client = $data;
                include __DIR__ . '/../Views/clientes/form.php';
            }
        } else {
            $client = [
                'entry_date' => date('Y-m-d'),
            ];
            include __DIR__ . '/../Views/clientes/form.php';
        }
    }

    public function edit($id) {
        $client = $this->model->getById($id);
        if (!$client) {
            Utils::redirect('/cliente', 'Cliente não encontrado');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => Utils::sanitize($_POST['name'] ?? ''),
                'email' => Utils::sanitize($_POST['email'] ?? ''),
                'phone' => Utils::sanitize($_POST['phone'] ?? ''),
                'address' => Utils::sanitize($_POST['address'] ?? ''),
                'entry_date' => $_POST['entry_date'] ?? ($client['entry_date'] ?? date('Y-m-d')),
                'notes' => Utils::sanitize($_POST['notes'] ?? ($client['notes'] ?? '')),
            ];

            if (empty($data['name'])) {
                $error = 'Nome é obrigatório';
                $client = array_merge($client, $data);
                include __DIR__ . '/../Views/clientes/form.php';
                return;
            }

            if ($this->model->update($id, $data)) {
                Utils::redirect('/cliente', 'Cliente atualizado com sucesso!');
            } else {
                $error = 'Erro ao atualizar cliente';
                $client = array_merge($client, $data);
                include __DIR__ . '/../Views/clientes/form.php';
            }
        } else {
            include __DIR__ . '/../Views/clientes/form.php';
        }
    }

    public function delete($id) {
        Auth::requireAdmin();

        if ($this->model->delete($id)) {
            Utils::redirect('/cliente', 'Cliente excluído com sucesso!');
        } else {
            Utils::redirect('/cliente', 'Erro ao excluir cliente');
        }
    }
}
