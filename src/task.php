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
        public string $name,

        #[NonNegative("Task ID cannot be negative")]
        public readonly int $id,

        public TaskStatus $status = TaskStatus::PENDING,

        public readonly DateTimeImmutable $creationDate
    ) {}
}

class TaskManager {
    /**
     * @throws Exception
     */
    public function loadTasks(): Generator {
        $file = 'data/tasks.json';
        if (!file_exists($file)) {
            return [];
        }

        $json = file_get_contents($file);
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
                'id' => $task->id,
                'name' => $task->name,
                'status' => $task->status->value,
                'creationDate' => $task->creationDate->format(DateTimeInterface::ATOM)
            ];
        }

        $dir = dirname('data/tasks.json');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException("Failed to encode tasks to .json");
        }

        $result = file_put_contents('data/tasks.json', $json, LOCK_EX);
        if ($result === false) {
            throw new RuntimeException("Failed to write tasks to file");
        }
    }

    public function addTask(string $name): Task {
        TaskValidator::validateTaskName($name);

        $tasks = iterator_to_array($this->loadTasks());

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

    public function renameTask(int $id, string $name): void {
        TaskValidator::validateTaskId($id);
        TaskValidator::validateTaskName($name);

        $tasks = iterator_to_array($this->loadTasks());

        foreach ($tasks as $task) {
            if ($task->id === $id) {
                $task->name = $name;
                $this->saveTasks($tasks);
                return;
            }
        }
    }

    public function updateTaskStatus(int $id, TaskStatus $status): void {
        TaskValidator::validateTaskId($id);

        $tasks = iterator_to_array($this->loadTasks());

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
        TaskValidator::validateTaskId($id);

        $tasks = iterator_to_array($this->loadTasks());

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
        TaskValidator::validateTaskId($id);

        foreach ($this->loadTasks() as $task) {
            if($task->id === $id) {
                return $task;
            }
        }
        throw new RuntimeException("Task with ID $id not found");
    }
}

class TaskValidator {
    public static function validateTaskName(string $name): void {
        if (empty(trim($name))) {
            throw new InvalidArgumentException("Task name cannot be empty");
        }

        if (strlen($name) > 255) {
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
