<?php

declare(strict_types=1);

namespace App\Task\Domain;

enum Priority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
}
