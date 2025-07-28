<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Enums\TaskStatus;
use App\Services\TaskManager;
use Dotenv\Dotenv;

// Simple assertion helper
function assertEquals($expected, $actual, $message): void {
    if ($expected !== $actual) {
        echo "FAIL: $message\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n\n";
    } else {
        echo "PASS: $message\n";
    }
}

// Load environment & connect to DB
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$pdo = new PDO(
    "pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Clear existing tasks
$pdo->exec("DELETE FROM tasks");

$manager = new TaskManager($pdo);

// Test: Add Task
$task = $manager->addTask('Do laundry');
assertEquals('Do laundry', $task->getName(), 'Add Task - name matches');
assertEquals(TaskStatus::PENDING, $task->getStatus(), 'Add Task - status is PENDING');

// Test: Rename Task
$manager->renameTask($task->getId(), 'Clean dishes');
$renamed = $manager->getTaskById($task->getId());
assertEquals('Clean dishes', $renamed->getName(), 'Rename Task - name updated');

// Test: Update Status
$manager->updateTaskStatus($task->getId(), TaskStatus::COMPLETED);
$updated = $manager->getTaskById($task->getId());
assertEquals(TaskStatus::COMPLETED, $updated->getStatus(), 'Update Status - status updated');

// Test: Delete Task
$manager->deleteTask($task->getId());
try {
    $manager->getTaskById($task->getId());
    echo "FAIL: Delete Task - expected exception not thrown\n";
} catch (RuntimeException $e) {
    echo "PASS: Delete Task - exception thrown as expected\n";
}
