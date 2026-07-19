<?php

namespace App\Domain\Workflow\Actions\Builtin;

use App\Models\Tenant\WorkflowInstance;
use Illuminate\Support\Facades\Mail;

class SendEmail implements WorkflowActionInterface
{
    public function execute(WorkflowInstance $instance, array $config): array
    {
        $to = $config['to'] ?? null;
        $subject = $config['subject'] ?? 'Workflow Notification';
        $body = $config['body'] ?? '';

        if (! $to) {
            return ['error' => 'No recipient specified'];
        }

        // Replace placeholders with entry/context data
        $entry = \App\Models\Tenant\Entry::find($instance->entry_id);
        $context = array_merge(
            ['entry' => $entry?->toArray() ?? []],
            $instance->context ?? [],
        );

        $subject = $this->interpolate($subject, $context);
        $body = $this->interpolate($body, $context);

        Mail::raw($body, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });

        return ['sent_to' => $to, 'subject' => $subject];
    }

    protected function interpolate(string $text, array $context): string
    {
        foreach (array_dot($context) as $key => $value) {
            if (is_scalar($value)) {
                $text = str_replace('{{' . $key . '}}', (string) $value, $text);
            }
        }
        return $text;
    }
}
