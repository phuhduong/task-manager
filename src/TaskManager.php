<?php
namespace App;

use App\Enums\TaskStatus;
use App\Constants\TaskConstants;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Generator;
use RuntimeException;

class TaskManager {
    /**
     * @throws Exception
     */
    public function loadTasks(): Generator {
        if (!file_exists(TaskConstants::TASKS_FILE)) {
            return;
        }

        $json = file_get_contents(TaskConstants::TASKS_FILE);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException("Invalid task data format");
        }

        foreach ($data as $item) {
            yield new Task(
                name: $item['name'],
                id: $item['id'],
                status: TaskStatus::from($item['status']),
                creationDate: new DateTimeImmutable($item['creationDate'])
            );
        }
    }

    public function saveTasks(array $tasks): void {
        $data = [];
        foreach ($tasks as $task) {
            $data[] = [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'status' => $task->getStatus()->value,
                'creationDate' => $task->getCreationDate()->format(DateTimeInterface::ATOM)
            ];
        }

        $dir = dirname(TaskConstants::TASKS_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException("Failed to encode tasks to .json");
        }

        $result = file_put_contents(TaskConstants::TASKS_FILE, $json, LOCK_EX);
        if ($result === false) {
            throw new RuntimeException("Failed to write tasks to file");
        }
    }

    /**
     * @throws Exception
     */
    public function addTask(string $name): Task {
        TaskValidator::validateTaskName($name);

        $tasks = iterator_to_array($this->loadTasks());

        $ids = [];
        foreach ($tasks as $task) {
            $ids[] = $task->getId();
        }
        if (count($ids) === 0) {
            $nextId = 1;
        } else {
            $nextId = max($ids) + 1;
        }

        $newTask = new Task($name, $nextId, TaskStatus::PENDING, new DateTimeImmutable());
        TaskValidator::validateTask($newTask);
        $tasks[] = $newTask;
        $this->saveTasks($tasks);

        return $newTask;
    }

    /**
     * @throws Exception
     */
    public function renameTask(int $id, string $name): void {
        TaskValidator::validateTaskId($id);
        TaskValidator::validateTaskName($name);

        $tasks = iterator_to_array($this->loadTasks());

        foreach ($tasks as $task) {
            if ($task->getId() === $id) {
                $task->setName($name);
                $this->saveTasks($tasks);
                return;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function updateTaskStatus(int $id, TaskStatus $status): void {
        TaskValidator::validateTaskId($id);

        $tasks = iterator_to_array($this->loadTasks());

        foreach ($tasks as $task) {
            if ($task->getId() === $id) {
                $task->setStatus($status);
                $this->saveTasks($tasks);
                return;
            }
        }

        throw new RuntimeException("Task with ID $id not found");
    }

    /**
     * @throws Exception
     */
    public function deleteTask(int $id): void {
        TaskValidator::validateTaskId($id);

        $tasks = iterator_to_array($this->loadTasks());

        foreach($tasks as $key => $task) {
            if($task->getId() === $id) {
                unset($tasks[$key]);
                $this->saveTasks(array_values($tasks)); // Reindex array
                return;
            }
        }

        throw new RuntimeException("Task with ID $id not found");
    }

    /**
     * @throws Exception
     */
    public function getTaskById(int $id): Task {
        TaskValidator::validateTaskId($id);

        foreach ($this->loadTasks() as $task) {
            if($task->getId() === $id) {
                return $task;
            }
        }

        throw new RuntimeException("Task with ID $id not found");
    }
}
