<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Enums\TaskStatus;
use App\TaskManager;
use App\Constants\TaskConstants;

// Assert helper
function assertEquals($expected, $actual, $message): void {
    if ($expected !== $actual) {
        echo "FAIL: $message\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n\n";
    } else {
        echo "PASS: $message\n";
    }
}

// Clear existing task data
file_put_contents(TaskConstants::TASKS_FILE, json_encode([]));

$manager = new TaskManager();

$task = $manager->addTask('Do laundry');
assertEquals('Do laundry', $task->name, 'Add Task - name matches');
assertEquals(TaskStatus::PENDING, $task->status, 'Add Task - status is PENDING');

$manager->renameTask($task->id, 'Clean dishes');
$renamed = $manager->getTaskById($task->id);
assertEquals('Clean dishes', $renamed->name, 'Rename Task - name updated');

$manager->updateTaskStatus($task->id, TaskStatus::COMPLETED);
$updated = $manager->getTaskById($task->id);
assertEquals(TaskStatus::COMPLETED, $updated->status, 'Update Status - status updated');

$manager->deleteTask($task->id);
try {
    $manager->getTaskById($task->id);
    echo "FAIL: Delete task - expected exception not thrown\n";
} catch (RuntimeException $e) {
    echo "PASS: Delete task - exception thrown as expected\n";
}
