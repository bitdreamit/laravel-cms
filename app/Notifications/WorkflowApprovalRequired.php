<?php

namespace App\Notifications;

use App\Models\Tenant\WorkflowInstance;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowApprovalRequired extends Notification
{
    public function __construct(
        public WorkflowInstance $instance,
        public array $nodeDef,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $entry = $this->instance->entry_id;
        $url = url('/admin/workflow-instances/' . $this->instance->id);

        return (new MailMessage)
            ->subject('Approval Required: ' . ($this->nodeDef['label'] ?? 'Workflow Approval'))
            ->line('Your approval is required for a workflow.')
            ->line('Workflow: ' . $this->instance->workflow->name)
            ->line('Entry ID: ' . $entry)
            ->line('Node: ' . ($this->nodeDef['label'] ?? $this->nodeDef['id']))
            ->action('Review & Approve', $url)
            ->line('Thank you for using our platform!');
    }

    public function toArray($notifiable): array
    {
        return [
            'workflow_instance_id' => $this->instance->id,
            'workflow_name' => $this->instance->workflow->name,
            'node_label' => $this->nodeDef['label'] ?? $this->nodeDef['id'],
            'entry_id' => $this->instance->entry_id,
            'url' => '/admin/workflow-instances/' . $this->instance->id,
        ];
    }
}
