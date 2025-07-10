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
assertEquals('Do laundry', $task->getName(), 'Add Task - name matches');
assertEquals(TaskStatus::PENDING, $task->getStatus(), 'Add Task - status is PENDING');

$manager->renameTask($task->getId(), 'Clean dishes');
$renamed = $manager->getTaskById($task->getId());
assertEquals('Clean dishes', $renamed->getName(), 'Rename Task - name updated');

$manager->updateTaskStatus($task->getId(), TaskStatus::COMPLETED);
$updated = $manager->getTaskById($task->getId());
assertEquals(TaskStatus::COMPLETED, $updated->getStatus(), 'Update Status - status updated');

$manager->deleteTask($task->getId());
try {
    $manager->getTaskById($task->getId());
    echo "FAIL: Delete task - expected exception not thrown\n";
} catch (RuntimeException $e) {
    echo "PASS: Delete task - exception thrown as expected\n";
}
