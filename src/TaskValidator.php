<?php
namespace App;

use InvalidArgumentException;
use App\Constants\TaskConstants;

class TaskValidator {
    public static function validateTaskName(string $name): void {
        if (empty(trim($name))) {
            throw new InvalidArgumentException("Task name cannot be empty");
        }

        if (strlen($name) > TaskConstants::MAX_TASK_NAME_LENGTH) {
            throw new InvalidArgumentException("Task name cannot exceed 255 characters");
        }
    }

    public static function validateTaskId(int $id): void {
        if ($id < 0) {
            throw new InvalidArgumentException("Task ID cannot be negative");
        }
    }

    public static function validateTask(Task $task): void {
        self::validateTaskName($task->name);
        self::validateTaskId($task->id);
    }
}