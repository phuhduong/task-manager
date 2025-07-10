<?php
namespace App;

use App\Attributes\NonNegative;
use App\Attributes\NotEmpty;
use App\Enums\TaskStatus;
use DateTimeImmutable;

class Task {
    public function __construct(
        #[NotEmpty("Task name cannot be empty")]
        private string $name,

        #[NonNegative("Task ID cannot be negative")]
        private readonly int $id,

        private TaskStatus $status = TaskStatus::PENDING,

        private readonly DateTimeImmutable $creationDate
    ) {}

    public function getName(): string {
        return $this->name;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getStatus(): TaskStatus {
        return $this->status;
    }

    public function getCreationDate(): DateTimeImmutable {
        return $this->creationDate;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function setStatus(TaskStatus $status): void {
        $this->status = $status;
    }

}
