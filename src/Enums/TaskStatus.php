<?php
namespace App\Enums;

enum TaskStatus: string {
    case PENDING = 'Pending';
    case IN_PROGRESS = 'In Progress';
    case COMPLETED = 'Completed';
}