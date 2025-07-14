<?php
namespace App;

use App\Attributes\NonNegative;
use App\Attributes\NotEmpty;
use App\Enums\TaskStatus;
use DateTimeImmutable;

class Task {
    public function __construct(
        #[NotEmpty("Task name cannot be empty")]
        private readonly string $name,

        #[NonNegative("Task ID cannot be negative")]
        private readonly int $id,

        private readonly TaskStatus $status = TaskStatus::PENDING,

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

}
