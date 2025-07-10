<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Enums\TaskStatus;
use App\Task;
use App\TaskManager;
use Dotenv\Dotenv;

// Load environment & connect to DB
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$pdo = new PDO(
    "pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$taskManager = new TaskManager($pdo);

// Helpers
function post($key) {
    return $_POST[$key] ?? '';
}
function get($key) {
    return $_GET[$key] ?? '';
}

// Generate status option tags
function buildStatusOptions(string $selected = ''): string {
    $html = '';
    foreach (TaskStatus::cases() as $status) {
        $value = $status->value;
        $isSelected = $value === $selected ? ' selected' : '';
        $html .= "<option value=\"$value\"$isSelected>$value</option>";
    }
    return $html;
}

// Filter tasks by status
function filterTasks(array $tasks, string $status): array {
    return match ($status) {
        'Pending' => array_filter($tasks, fn($t) => $t->getStatus() === TaskStatus::PENDING),
        'In Progress' => array_filter($tasks, fn($t) => $t->getStatus() === TaskStatus::IN_PROGRESS),
        'Completed' => array_filter($tasks, fn($t) => $t->getStatus() === TaskStatus::COMPLETED),
        default => $tasks,
    };
}

// Render HTML row for one task
function renderTaskRow(Task $task): string {
    $options = buildStatusOptions($task->getStatus()->value);
    return <<<HTML
<tr data-id="{$task->getId()}">
    <td><input class="nameInput" type="text" value="{$task->getName()}"></td>
    <td>{$task->getStatus()->value}</td>
    <td>{$task->getCreationDate()->format('Y-m-d H:i')}</td>
    <td><select class="statusSelect">{$options}</select></td>
    <td><button class="deleteBtn">Delete</button></td>
</tr>
HTML;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $action = post('action');

        switch ($action) {
            case 'add':
                $task = $taskManager->addTask(trim(post('name')));
                echo json_encode([
                    'success' => true,
                    'task' => [
                        'id' => $task->getId(),
                        'name' => $task->getName(),
                        'status' => $task->getStatus()->value,
                        'creationDate' => $task->getCreationDate()->format('Y-m-d H:i'),
                    ],
                ]);
                break;

            case 'delete':
                $taskManager->deleteTask((int)post('id'));
                echo json_encode(['success' => true]);
                break;

            case 'update':
                $taskManager->updateTaskStatus((int)post('id'), TaskStatus::from(post('status')));
                echo json_encode(['success' => true]);
                break;

            case 'rename':
                $taskManager->renameTask((int)post('id'), trim(post('name')));
                echo json_encode(['success' => true]);
                break;

            case 'filter':
                $all = iterator_to_array($taskManager->loadTasks());
                $filtered = filterTasks($all, post('status'));
                $html = implode('', array_map('renderTaskRow', $filtered));
                echo json_encode(['success' => true, 'html' => $html]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid request']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Initial page load
$allTasks = $taskManager->loadTasks();
$statusFilter = get('status');
$tasks = filterTasks($allTasks, $statusFilter);
$statusOptionsHTML = buildStatusOptions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Task Manager</h1>

<label for="statusFilter">Filter by Status:</label>
<select id="statusFilter">
    <option value="">All</option>
    <?= buildStatusOptions($statusFilter) ?>
</select>

<form id="addTaskForm">
    <label>
        <input type="text" name="name" placeholder="New task name" required>
    </label>
    <button type="submit">Add</button>
</form>

<table id="taskTable">
    <thead>
    <tr>
        <th>Name</th><th>Status</th><th>Created</th><th>Update</th><th>Delete</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($tasks as $task) echo renderTaskRow($task); ?>
    </tbody>
</table>

<script>
    const statusOptions = `<?= $statusOptionsHTML ?>`;

    function sendForm(data, onSuccess) {
        fetch('', { method: 'POST', body: data })
            .then(res => res.json())
            .then(json => { if (json.success) onSuccess(json); });
    }

    function attachHandlers() {
        document.querySelectorAll('.deleteBtn').forEach(btn => {
            btn.onclick = () => {
                const row = btn.closest('tr');
                const data = new FormData();
                data.append('action', 'delete');
                data.append('id', row.dataset.id);
                sendForm(data, () => row.remove());
            };
        });

        document.querySelectorAll('.statusSelect').forEach(select => {
            select.onchange = () => {
                const row = select.closest('tr');
                const data = new FormData();
                data.append('action', 'update');
                data.append('id', row.dataset.id);
                data.append('status', select.value);
                sendForm(data, () => {
                    row.cells[1].textContent = select.value;
                });
            };
        });

        document.querySelectorAll('.nameInput').forEach(input => {
            input.onblur = () => {
                const row = input.closest('tr');
                const data = new FormData();
                data.append('action', 'rename');
                data.append('id', row.dataset.id);
                data.append('name', input.value.trim());
                sendForm(data, () => {});
            };
        });
    }

    document.getElementById('addTaskForm').onsubmit = function (e) {
        e.preventDefault();
        const name = this.name.value.trim();
        if (!name) return;

        const data = new FormData();
        data.append('action', 'add');
        data.append('name', name);

        sendForm(data, json => {
            const task = json.task;
            const row = document.createElement('tr');
            row.dataset.id = task.id;
            row.innerHTML = `
                <td><input class="nameInput" type="text" value="${task.name}"></td>
                <td>${task.status}</td>
                <td>${task.creationDate}</td>
                <td><select class="statusSelect">${statusOptions.replace(
                `value="${task.status}"`,
                `value="${task.status}" selected`
            )}</select></td>
                <td><button class="deleteBtn">Delete</button></td>
            `;
            document.querySelector('#taskTable tbody').appendChild(row);
            attachHandlers();
            this.reset();
        });
    };

    document.getElementById('statusFilter').onchange = function () {
        const data = new FormData();
        data.append('action', 'filter');
        data.append('status', this.value);
        sendForm(data, json => {
            document.querySelector('#taskTable tbody').innerHTML = json.html;
            attachHandlers();
        });
    };

    attachHandlers();
</script>
</body>
</html>
