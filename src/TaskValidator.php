<?php
namespace App;

use InvalidArgumentException;

class TaskValidator {
    public static function validateTaskName(string $name): void {
        if (empty(trim($name))) {
            throw new InvalidArgumentException("Task name cannot be empty");
        }
    }

    public static function validateTaskId(int $id): void {
        if ($id < 0) {
            throw new InvalidArgumentException("Task ID cannot be negative");
        }
    }

    public static function validateTask(Task $task): void {
        self::validateTaskName($task->getName());
        self::validateTaskId($task->getId());
    }
}