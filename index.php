<?php
require 'src/task.php';

$taskManager = new TaskManager();
$tasks = $taskManager->loadTasks();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add' && isset($_POST['name'])) {
                $taskManager->addTask(trim($_POST['name']));
            } elseif ($_POST['action'] === 'delete' && isset($_POST['id']) && is_numeric($_POST['id'])) {
                $taskManager->deleteTask((int)$_POST['id']);
            } elseif ($_POST['action'] === 'update' && isset($_POST['id'], $_POST['status'])) {
                $taskManager->updateTaskStatus((int)$_POST['id'], TaskStatus::from($_POST['status']));
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$tasks = $taskManager->loadTasks();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Task Manager</title>
</head>
<body>

<h1>Task Manager</h1>

<?php if (isset($error)): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<!-- Add Task Form -->
<form method="post">
    <input type="text" name="name" placeholder="New task name" required>
    <input type="hidden" name="action" value="add">
    <button type="submit">Add Task</button>
</form>

<!-- Tasks Table -->
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Status</th>
        <th>Created</th>
        <th>Update</th>
        <th>Delete</th>
    </tr>

    <?php foreach ($tasks as $task): ?>
    <tr>
        <td><?= $task->id ?></td>
        <td><?= htmlspecialchars($task->name) ?></td>
        <td><?= $task->status->value ?></td>
        <td><?= $task->creationDate->format('Y-m-d H:i') ?></td>

        <!-- Update Form -->
        <td>
            <form method="post">
                <select name="status">
                    <?php foreach (TaskStatus::cases() as $status): ?>
                        <option value="<?= $status->value ?>" <?= $task->status === $status ? 'selected' : '' ?>>
                            <?= $status->value ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="id" value="<?= $task->id ?>">
                <input type="hidden" name="action" value="update">
                <button type="submit">Update</button>
            </form>
        </td>

        <!-- Delete Form -->
        <td>
            <form method="post">
                <input type="hidden" name="id" value="<?= $task->id ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>