<?php

#[Attribute]
class NotEmpty {
    public function __construct(public string $message = "Value cannot be empty") {}
}

#[Attribute]
class NonNegative {
    public function __construct(public string $message = "Value cannot be negative") {}
}

enum TaskStatus: string {
    case PENDING = 'Pending';
    case IN_PROGRESS = 'In Progress';
    case COMPLETED = 'Completed';
}

class Task {
    public function __construct(
        #[NotEmpty("Task name cannot be empty")]
        public readonly string $name,

        #[NonNegative("Task ID cannot be negative")]
        public readonly int $id, 

        public TaskStatus $status = TaskStatus::PENDING,

        public readonly DateTimeImmutable $creationDate
    ) {}
}

class TaskManager {
    public function loadTasks(): array {
        $file = 'data/tasks.json';
        if (!file_exists($file)) {
            return [];
        }

        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException("Invalid task data format");
        }

        $tasks = [];
        foreach ($data as $item) {
            $tasks[] = new Task(
                name: $item['name'],
                id: $item['id'],
                status: TaskStatus::from($item['status']),
                creationDate: new DateTimeImmutable($item['creationDate'])
            );
        }

        return $tasks;
    }

    public function saveTasks(array $tasks): void {
        $data = [];
        foreach ($tasks as $task) {
            $data[] = [
                'id' => $task->id,
                'name' => $task->name,
                'status' => $task->status->value,
                'creationDate' => $task->creationDate->format(DateTimeInterface::ATOM)
            ];

        }

        // JSON_PRETTY_PRINT makes json file more readable for debugging
        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException("Failed to encode tasks to .json");
        }

        // LOCK_EX ensures only one write at a time, prevents corruption
        $result = file_put_contents('data/tasks.json', $json, LOCK_EX);
        if ($result === false) {
            throw new RuntimeException("Failed to write tasks to file");
        }
    }

    public function addTask(string $name): Task {
        if (empty($name)) {
            throw new InvalidArgumentException("Task name cannot be empty");
        }

        $tasks = $this->loadTasks();

        $ids = [];
        foreach ($tasks as $task) {
            $ids[] = $task->id;
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

    public function updateTaskStatus(int $id, TaskStatus $status): void {
        if ($id < 0) {
            throw new InvalidArgumentException("Task ID must be a non-negative integer");
        }
        
        $tasks = $this->loadTasks();

        foreach ($tasks as $task) {
            if ($task->id === $id) {
                $task->status = $status;
                $this->saveTasks($tasks);
                return;
            }
        }

        throw new RuntimeException("Task with ID $id not found");
    }

    public function deleteTask(int $id): void {
        if ($id < 0) {
            throw new InvalidArgumentException("Task ID must be a non-negative integer");
        }

        $tasks = $this->loadTasks();

        foreach($tasks as $key => $task) {
            if($task->id === $id) {
                unset($tasks[$key]);
                $this->saveTasks(array_values($tasks)); // Reindexes array
                return;
            }
        }

        throw new RuntimeException("Task with ID $id not found");
    }

    public function getTaskById(int $id): Task {
        if ($id < 0) {
            throw new InvalidArgumentException("Task ID must be a non-negative integer");
        }

        $tasks = $this->loadTasks();

        foreach($tasks as $task) {
            if($task->id === $id) {
                return $task;
            }
        }
        throw new RuntimeException("Task with ID $id not found");
    }
}

class TaskValidator {
    public static function validateTask(Task $task): void {
        $reflection = new ReflectionClass($task);

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute) {
                $attrInstance = $attribute->newInstance();
                $value = $property->getValue($task);

                if ($attrInstance instanceof NotEmpty && empty($value)) {
                    throw new InvalidArgumentException($attrInstance->message);
                }
                if ($attrInstance instanceof NonNegative && $value < 0) {
                    throw new InvalidArgumentException($attrInstance->message);
                }
            }
        }
    }
}

?>