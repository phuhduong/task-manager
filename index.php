<?php
require 'src/task.php';

$taskManager = new TaskManager();
$tasks = $taskManager->loadTasks();

// Handle AJAX POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json'); // Respond with JSON
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add' && isset($_POST['name'])) {
                $task = $taskManager->addTask(trim($_POST['name']));
                echo json_encode(['success' => true, 'task' => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'status' => $task->status->value,
                    'creationDate' => $task->creationDate->format('Y-m-d H:i')
                ]]);
                exit;
            } elseif ($_POST['action'] === 'delete' && isset($_POST['id']) && is_numeric($_POST['id'])) {
                $taskManager->deleteTask((int)$_POST['id']);
                echo json_encode(['success' => true]);
                exit;
            } elseif ($_POST['action'] === 'update' && isset($_POST['id'], $_POST['status'])) {
                $taskManager->updateTaskStatus((int)$_POST['id'], TaskStatus::from($_POST['status']));
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$tasks = $taskManager->loadTasks();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Task Manager</title>
</head>
<body>

<h1>Task Manager</h1>

<!-- Add Task Form -->
<input type="text" id="task-name" placeholder="New task name">
<button onclick="addTask()">Add</button>

<!-- Tasks Table -->
<table id="task-table" border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Status</th>
        <th>Created</th>
        <th>Update</th>
        <th>Delete</th>
    </tr>
    <?php foreach ($tasks as $task): ?>
    <tr id="task-<?= $task->id ?>">
        <td><?= $task->id ?></td>
        <td><?= htmlspecialchars($task->name) ?></td>
        <td><?= $task->status->value ?></td>
        <td><?= $task->creationDate->format('Y-m-d H:i') ?></td>
        <td>
            <select onchange="updateTask(<?= $task->id ?>, this.value)">
                <?php foreach (TaskStatus::cases() as $status): ?>
                    <option value="<?= $status->value ?>" <?= $task->status === $status ? 'selected' : '' ?>>
                        <?= $status->value ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><button onclick="deleteTask(<?= $task->id ?>)">Delete</button></td>
    </tr>
    <?php endforeach; ?>
</table>

<script>
function addTask() {
    const name = document.getElementById('task-name').value;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'add', name: name})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload(); // Reload to show the task
        else alert(data.message || 'Failed to add task');
    });
}

function deleteTask(id) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'delete', id: id})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) document.getElementById('task-' + id).remove();
        else alert(data.message || 'Failed to delete task');
    });
}

function updateTask(id, status) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'update', id: id, status: status})
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) alert(data.message || 'Failed to update task');
    });
}
</script>

</body>
</html>