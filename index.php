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

// Receives AJAX requests from frontend and performs server-side logic
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
                $all = $taskManager->loadTasks();
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
    window.statusOptions = `<?= $statusOptionsHTML ?>`;
</script>
<script src="actions.js"></script>

</body>
</html>