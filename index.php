<?php
declare(strict_types=1);
require 'src/task.php';

// Helpers
function post($key) {
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }
    return '';
}

function get($key) {
    if (isset($_GET[$key])) {
        return $_GET[$key];
    }
    return '';
}

function selected($current, $option) {
    if ($current === $option) {
        return 'selected';
    }
    return '';
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Generate status options HTML
$statusOptionsHTML = '';
foreach (TaskStatus::cases() as $status) {
    $value = $status->value;
    $statusOptionsHTML .= '<option value="' . $value . '">' . $value . '</option>';
}

// Logic to filter tasks by status
function filterTasks(array $tasks, string $status): array {
    return match ($status) {
        'Pending'     => array_filter($tasks, fn($t) => $t->status === TaskStatus::PENDING),
        'In Progress' => array_filter($tasks, fn($t) => $t->status === TaskStatus::IN_PROGRESS),
        'Completed'   => array_filter($tasks, fn($t) => $t->status === TaskStatus::COMPLETED),
        default       => $tasks,
    };
}

// Render task row HTML
function renderTaskRow(Task $task, string $statusOptionsHTML): string {
    $options = str_replace(
        'value="' . $task->status->value . '"',
        'value="' . $task->status->value . '" selected',
        $statusOptionsHTML
    );

    $html = '';
    $html .= '<tr data-id="' . $task->id . '">';
    $html .= '<td>' . $task->id . '</td>';
    $html .= '<td><input class="nameInput" type="text" value="' . e($task->name) . '"></td>';
    $html .= '<td>' . $task->status->value . '</td>';
    $html .= '<td>' . $task->creationDate->format('Y-m-d H:i') . '</td>';
    $html .= '<td><select class="statusSelect">' . $options . '</select></td>';
    $html .= '<td><button class="deleteBtn">Delete</button></td>';
    $html .= '</tr>';
    return $html;
}

// App logic
$taskManager = new TaskManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $action = post('action');

        if ($action === 'add' && isset($_POST['name'])) {
            $task = $taskManager->addTask(trim($_POST['name']));
            $response = [
                'success' => true,
                'task' => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'status' => $task->status->value,
                    'creationDate' => $task->creationDate->format('Y-m-d H:i')
                ]
            ];
            echo json_encode($response);

        } elseif ($action === 'delete' && isset($_POST['id'])) {
            $taskManager->deleteTask((int)$_POST['id']);
            echo json_encode(['success' => true]);

        } elseif ($action === 'update' && isset($_POST['id'], $_POST['status'])) {
            $taskManager->updateTaskStatus((int)$_POST['id'], TaskStatus::from($_POST['status']));
            echo json_encode(['success' => true]);

        } elseif ($action === 'filter' && isset($_POST['status'])) {
            $allTasks = iterator_to_array($taskManager->loadTasks());
            $filtered = filterTasks($allTasks, $_POST['status']);
            $rows = [];
            foreach ($filtered as $t) {
                $rows[] = renderTaskRow($t, $statusOptionsHTML);
            }
            echo json_encode(['success' => true, 'html' => implode('', $rows)]);

        } elseif ($action === 'rename' && isset($_POST['id'], $_POST['name'])) {
            $taskManager->renameTask((int)$_POST['id'], trim($_POST['name']));
            echo json_encode(['success' => true]);

        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Initial load
$allTasks = iterator_to_array($taskManager->loadTasks());
$statusFilter = get('status');
$tasks = filterTasks($allTasks, $statusFilter);
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

<label for="statusFilter">Filter by Status:</label>
<select id="statusFilter">
    <option value="">All</option>
    <?php
    foreach (TaskStatus::cases() as $status) {
        echo '<option value="' . $status->value . '">' . $status->value . '</option>';
    }
    ?>
</select>

<form id="addTaskForm">
    <input type="text" name="name" placeholder="New task name" required>
    <button type="submit">Add</button>
</form>

<table id="taskTable">
    <thead>
        <tr>
            <th>ID</th><th>Name</th><th>Status</th><th>Created</th><th>Update</th><th>Delete</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($tasks as $task) {
            echo renderTaskRow($task, $statusOptionsHTML);
        }
        ?>
    </tbody>
</table>

<script>
const statusOptions = `<?= $statusOptionsHTML ?>`;

// AJAX Form Submission
function sendForm(data, onSuccess) {
    fetch('', { method: 'POST', body: data })
        .then(function (res) {
            return res.json();
        })
        .then(function (json) {
            if (json.success) {
                onSuccess(json);
            }
        });
}

function attachHandlers() {
    document.querySelectorAll('.deleteBtn').forEach(function (btn) {
        btn.onclick = function () {
            const row = btn.closest('tr');
            const data = new FormData();
            data.append('action', 'delete');
            data.append('id', row.dataset.id);
            sendForm(data, function () {
                row.remove();
            });
        };
    });

    document.querySelectorAll('.statusSelect').forEach(function (select) {
        select.onchange = function () {
            const row = select.closest('tr');
            const data = new FormData();
            data.append('action', 'update');
            data.append('id', row.dataset.id);
            data.append('status', select.value);
            sendForm(data, function () {
                row.cells[2].textContent = select.value;
            });
        };
    });

    document.querySelectorAll('.nameInput').forEach(function (input) {
        input.addEventListener('blur', function () {
            const row = input.closest('tr');
            const id = row.dataset.id;
            const name = input.value.trim();

            const data = new FormData();
            data.append('action', 'rename');
            data.append('id', id);
            data.append('name', name);

            sendForm(data, function () {
                // Optionally provide user feedback
                row.classList.add('updated');
                setTimeout(() => row.classList.remove('updated'), 500);
            });
        });
    });
}

document.getElementById('addTaskForm').onsubmit = function (e) {
    e.preventDefault();
    const name = this.name.value.trim();
    if (name === '') return;

    const data = new FormData();
    data.append('action', 'add');
    data.append('name', name);

    sendForm(data, function (json) {
        const task = json.task;
        const row = document.createElement('tr');
        row.dataset.id = task.id;
        row.innerHTML = `
            <td>${task.id}</td>
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
        document.getElementById('addTaskForm').reset();
    });
};

document.getElementById('statusFilter').onchange = function () {
    const data = new FormData();
    data.append('action', 'filter');
    data.append('status', this.value);

    sendForm(data, function (json) {
        const tbody = document.querySelector('#taskTable tbody');
        tbody.innerHTML = json.html;
        attachHandlers();
    });
};

attachHandlers();
</script>

</body>
</html>
