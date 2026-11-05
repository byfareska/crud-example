<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use DomainException;

/**
 * A rule of the notes: no links in them.
 */
final class NoteWithALink extends DomainException
{
}
