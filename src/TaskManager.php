<?php
namespace App;

use App\Enums\TaskStatus;
use DateTimeImmutable;
use Exception;
use PDO;
use RuntimeException;

class TaskManager {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * @throws Exception
     */
    public function loadTasks(): array {
        $stmt = $this->db->query("SELECT * FROM tasks ORDER BY id");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tasks = [];
        foreach ($rows as $row) {
            $tasks[] = new Task(
                name: $row['name'],
                id: (int)$row['id'],
                status: TaskStatus::from($row['status']),
                creationDate: new DateTimeImmutable($row['creation_date'])
            );
        }

        return $tasks;
    }

    public function addTask(string $name): Task {
        TaskValidator::validateTaskName($name);

        $now = new DateTimeImmutable();
        $stmt = $this->db->prepare("
            INSERT INTO tasks (name, status, creation_date)
            VALUES (:name, :status, :creation_date)
            RETURNING id
        ");
        $stmt->execute([
            ':name' => $name,
            ':status' => TaskStatus::PENDING->value,
            ':creation_date' => $now->format('Y-m-d H:i:s')
        ]);
        $id = $stmt->fetchColumn();

        $task = new Task($name, $id, TaskStatus::PENDING, $now);
        TaskValidator::validateTask($task);

        return $task;
    }

    public function renameTask(int $id, string $name): void {
        TaskValidator::validateTaskId($id);
        TaskValidator::validateTaskName($name);

        $stmt = $this->db->prepare("UPDATE tasks SET name = :name WHERE id = :id");
        $stmt->execute([':name' => $name, ':id' => $id]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException("Task with ID $id not found");
        }
    }

    public function updateTaskStatus(int $id, TaskStatus $status): void {
        TaskValidator::validateTaskId($id);

        $stmt = $this->db->prepare("UPDATE tasks SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status->value, ':id' => $id]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException("Task with ID $id not found");
        }
    }

    public function deleteTask(int $id): void {
        TaskValidator::validateTaskId($id);

        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException("Task with ID $id not found");
        }
    }

    /**
     * @throws Exception
     */
    public function getTaskById(int $id): Task {
        TaskValidator::validateTaskId($id);

        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RuntimeException("Task with ID $id not found");
        }

        return new Task(
            name: $row['name'],
            id: (int)$row['id'],
            status: TaskStatus::from($row['status']),
            creationDate: new DateTimeImmutable($row['creation_date'])
        );
    }
}
