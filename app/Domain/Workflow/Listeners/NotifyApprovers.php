<?php

namespace App\Domain\Workflow\Listeners;

use App\Domain\Workflow\Events\ApprovalRequired;
use App\Models\Central\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class NotifyApprovers
{
    public function handle(ApprovalRequired $event): void
    {
        $nodeDef = $event->nodeDef;
        $assigneeType = $nodeDef['assignee_type'] ?? null;
        $assigneeValue = $nodeDef['assignee_value'] ?? null;

        if (! $assigneeType || ! $assigneeValue) {
            return;
        }

        $approvers = collect();

        if ($assigneeType === 'role') {
            // Find users with this role in the current tenant
            $users = User::whereHas('roles', function ($q) use ($assigneeValue) {
                $q->where('name', $assigneeValue);
            })->get();
            $approvers = $users;
        } elseif ($assigneeType === 'user') {
            $user = User::where('email', $assigneeValue)->first();
            if ($user) {
                $approvers = collect([$user]);
            }
        } elseif ($assigneeType === 'email') {
            // Send to a specific email address
            Notification::route('mail', $assigneeValue)
                ->notify(new \App\Notifications\WorkflowApprovalRequired($event->instance, $nodeDef));
            return;
        }

        // Send notifications to all approvers
        foreach ($approvers as $user) {
            $user->notify(new \App\Notifications\WorkflowApprovalRequired($event->instance, $nodeDef));
        }
    }
}
