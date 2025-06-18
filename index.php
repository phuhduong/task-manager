<?php
declare(strict_types=1);

require 'src/task.php';

function post($key) {
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }
    return '';
}

function selected($current, $option) {
    if ($current === $option) {
        return 'selected';
    }
    return '';
}

$taskManager = new TaskManager();

// Handle AJAX POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $action = post('action');

        if ($action === 'add' && isset($_POST['name'])) {
            $task = $taskManager->addTask(trim($_POST['name']));
            echo json_encode([
                'success' => true,
                'task' => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'status' => $task->status->value,
                    'creationDate' => $task->creationDate->format('Y-m-d H:i')
                ]
            ]);
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            $taskManager->deleteTask((int)$_POST['id']);
            echo json_encode(['success' => true]);
        } elseif ($action === 'update' && isset($_POST['id'], $_POST['status'])) {
            $taskManager->updateTaskStatus((int)$_POST['id'], TaskStatus::from($_POST['status']));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
}

$tasks = $taskManager->loadTasks();

// Filter by status using match
$statusFilter = '';
if (isset($_GET['status'])) {
    $statusFilter = $_GET['status'];
}

$tasks = match ($statusFilter) {
    'Pending' => array_filter($tasks, function ($task) {
        return $task->status === TaskStatus::PENDING;
    }),
    'In Progress' => array_filter($tasks, function ($task) {
        return $task->status === TaskStatus::IN_PROGRESS;
    }),
    'Completed' => array_filter($tasks, function ($task) {
        return $task->status === TaskStatus::COMPLETED;
    }),
    default => $tasks,
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<h1>Task Manager</h1>

<!-- Status Filter -->
<form method="get" id="statusFilterForm">
    <label for="status">Filter by status:</label>
    <select name="status" id="status" onchange="this.form.submit()">
        <option value="">-- All --</option>
        <option value="Pending" <?= selected($statusFilter, 'Pending') ?>>Pending</option>
        <option value="In Progress" <?= selected($statusFilter, 'In Progress') ?>>In Progress</option>
        <option value="Completed" <?= selected($statusFilter, 'Completed') ?>>Completed</option>
    </select>
</form>

<!-- Add Task Form -->
<form id="addTaskForm">
    <input type="text" name="name" placeholder="New task name" required>
    <button type="submit">Add</button>
</form>

<!-- Task Table -->
<table id="taskTable">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Created</th>
            <th>Update</th>
            <th>Delete</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tasks as $task): ?>
            <tr data-id="<?= $task->id ?>">
                <td><?= $task->id ?></td>
                <td><?= htmlspecialchars($task->name) ?></td>
                <td><?= $task->status->value ?></td>
                <td><?= $task->creationDate->format('Y-m-d H:i') ?></td>

                <td>
                    <select class="statusSelect">
                        <?php foreach (TaskStatus::cases() as $status): ?>
                            <option value="<?= $status->value ?>" <?= selected($task->status, $status) ?>>
                                <?= $status->value ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>

                <td><button class="deleteBtn">Delete</button></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
function sendForm(data, onSuccess) {
    fetch('', { method: 'POST', body: data })
        .then(res => res.json())
        .then(json => {
            if (json.success && typeof onSuccess === 'function') {
                onSuccess(json);
            }
        });
}

// Add Task
document.getElementById('addTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const name = this.name.value;
    const data = new FormData();
    data.append('action', 'add');
    data.append('name', name);

    sendForm(data, () => location.reload());
});

// Delete Task
document.querySelectorAll('.deleteBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr');
        const id = row.dataset.id;

        const data = new FormData();
        data.append('action', 'delete');
        data.append('id', id);

        sendForm(data, () => row.remove());
    });
});

// Update Task
document.querySelectorAll('.statusSelect').forEach(select => {
    select.addEventListener('change', function () {
        const row = this.closest('tr');
        const id = row.dataset.id;
        const status = this.value;

        const data = new FormData();
        data.append('action', 'update');
        data.append('id', id);
        data.append('status', status);

        sendForm(data, () => {
            row.cells[2].textContent = status;
        });
    });
});
</script>

</body>
</html>
