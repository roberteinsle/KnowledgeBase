<?php

namespace App\Modules\Workspace\Services;

use App\Core\Activity\ActivityLogger;
use App\Modules\Workspace\Models\Workspace;

final class WorkspaceService
{
    public function __construct(private ActivityLogger $activity) {}

    /**
     * @param  array{name: string, description?: string|null, default_locale: string, enforce_mfa: bool}  $data
     */
    public function update(array $data): Workspace
    {
        $workspace = Workspace::current();
        $workspace->fill($data)->save();

        $this->activity->log('workspace.updated', $workspace, $workspace->getChanges());

        return $workspace;
    }
}
