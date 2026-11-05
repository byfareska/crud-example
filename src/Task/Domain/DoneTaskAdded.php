<?php

declare(strict_types=1);

namespace App\Task\Domain;

use DomainException;

/**
 * A task is added to be done, not done already.
 */
final class DoneTaskAdded extends DomainException
{
}
