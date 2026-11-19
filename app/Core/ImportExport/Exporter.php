<?php

namespace App\Core\ImportExport;

use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

interface Exporter
{
    public function key(): string;

    /** Untranslated label for the export menu. */
    public function label(): string;

    /** 'page' or 'space' */
    public function scope(): string;

    /**
     * Callers have already checked that the user may read the subject;
     * implementations must still skip content the user cannot see.
     */
    public function export(User $user, object $subject): Response;
}
