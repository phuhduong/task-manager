<?php
namespace App;

use App\Attributes\NonNegative;
use App\Attributes\NotEmpty;
use App\Enums\TaskStatus;
use DateTimeImmutable;

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
