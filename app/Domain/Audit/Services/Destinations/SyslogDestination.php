<?php

namespace App\Domain\Audit\Services\Destinations;

class SyslogDestination implements DestinationInterface
{
    public function send(array $config, array $payload): array
    {
        $host = $config['host'];
        $port = $config['port'] ?? 514;
        $protocol = $config['protocol'] ?? 'udp';
        $facility = $config['facility'] ?? LOG_LOCAL0;

        $severity = config("audit_streams.severity_map.{$payload['severity']}", LOG_INFO);
        $priority = ($facility * 8) + $severity;

        // RFC 5424 syslog format
        $timestamp = now()->format('c');
        $hostName = gethostname() ?: 'localhost';
        $appName = 'cms-platform';
        $pid = getmypid();
        $messageId = $payload['id'] ?? '';
        $structuredData = '[cms@1 tenant_id="' . ($payload['tenant_id'] ?? '') . '"]';
        $message = $payload['description'] ?? $payload['event_type'] ?? 'Activity';

        $syslogMessage = "<{$priority}>1 {$timestamp} {$hostName} {$appName} {$pid} {$messageId} {$structuredData} " . json_encode($payload);

        if ($protocol === 'tcp') {
            $socket = @fsockopen("tcp://{$host}", $port, $errno, $errstr, 5);
        } else {
            $socket = @fsockopen("udp://{$host}", $port, $errno, $errstr, 5);
        }

        if (! $socket) {
            return [
                'status' => 500,
                'body' => "Syslog connection failed: {$errstr} ({$errno})",
            ];
        }

        fwrite($socket, $syslogMessage);
        fclose($socket);

        return [
            'status' => 200,
            'body' => 'Sent',
        ];
    }
}
