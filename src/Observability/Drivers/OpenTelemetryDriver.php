<?php

namespace Ludelix\Observability\Drivers;

/**
 * OpenTelemetry Driver
 * 
 * Exports traces and metrics to OpenTelemetry format
 */
class OpenTelemetryDriver
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'endpoint' => 'http://localhost:4317',
            'service_name' => 'ludelix-app',
            'service_version' => '1.0.0',
            'environment' => 'production',
            'headers' => []
        ], $config);
    }

    /**
     * Export traces
     */
    public function exportTraces(array $traces): bool
    {
        $payload = $this->buildTracesPayload($traces);
        return $this->sendToCollector('/v1/traces', $payload);
    }

    /**
     * Export metrics
     */
    public function exportMetrics(array $metrics): bool
    {
        $payload = $this->buildMetricsPayload($metrics);
        return $this->sendToCollector('/v1/metrics', $payload);
    }

    /**
     * Build traces payload
     */
    protected function buildTracesPayload(array $traces): array
    {
        $resourceSpans = [];
        
        foreach ($traces as $trace) {
            $spans = [];
            
            foreach ($trace['spans'] ?? [] as $spanId) {
                $span = $this->getSpanData($spanId);
                if ($span) {
                    $spans[] = $this->formatSpan($span);
                }
            }
            
            if (!empty($spans)) {
                $resourceSpans[] = [
                    'resource' => $this->buildResource(),
                    'scopeSpans' => [
                        [
                            'scope' => $this->buildScope(),
                            'spans' => $spans
                        ]
                    ]
                ];
            }
        }
        
        return [
            'resourceSpans' => $resourceSpans
        ];
    }

    /**
     * Build metrics payload
     */
    protected function buildMetricsPayload(array $metrics): array
    {
        $resourceMetrics = [];
        $scopeMetrics = [];
        
        foreach ($metrics as $metric) {
            $scopeMetrics[] = $this->formatMetric($metric);
        }
        
        if (!empty($scopeMetrics)) {
            $resourceMetrics[] = [
                'resource' => $this->buildResource(),
                'scopeMetrics' => [
                    [
                        'scope' => $this->buildScope(),
                        'metrics' => $scopeMetrics
                    ]
                ]
            ];
        }
        
        return [
            'resourceMetrics' => $resourceMetrics
        ];
    }

    /**
     * Format span for OpenTelemetry
     */
    protected function formatSpan(array $span): array
    {
        return [
            'traceId' => $this->hexToBytes($span['trace_id']),
            'spanId' => $this->hexToBytes($span['span_id']),
            'parentSpanId' => $span['parent_span_id'] ? $this->hexToBytes($span['parent_span_id']) : null,
            'name' => $span['operation'],
            'kind' => $this->getSpanKind($span),
            'startTimeUnixNano' => $this->timeToNanos($span['start_time']),
            'endTimeUnixNano' => isset($span['end_time']) ? $this->timeToNanos($span['end_time']) : null,
            'attributes' => $this->formatAttributes($span['tags'] ?? []),
            'events' => $this->formatEvents($span['logs'] ?? []),
            'status' => $this->getSpanStatus($span)
        ];
    }

    /**
     * Format metric for OpenTelemetry
     */
    protected function formatMetric(array $metric): array
    {
        $formatted = [
            'name' => $metric['name'],
            'description' => '',
            'unit' => $this->getMetricUnit($metric)
        ];

        switch ($metric['type']) {
            case 'counter':
                $formatted['sum'] = [
                    'dataPoints' => [
                        [
                            'attributes' => $this->formatAttributes($metric['labels'] ?? []),
                            'timeUnixNano' => $this->timeToNanos($metric['timestamp']),
                            'asDouble' => (float)$metric['value']
                        ]
                    ],
                    'aggregationTemporality' => 2, // CUMULATIVE
                    'isMonotonic' => true
                ];
                break;

            case 'gauge':
                $formatted['gauge'] = [
                    'dataPoints' => [
                        [
                            'attributes' => $this->formatAttributes($metric['labels'] ?? []),
                            'timeUnixNano' => $this->timeToNanos($metric['timestamp']),
                            'asDouble' => (float)$metric['value']
                        ]
                    ]
                ];
                break;

            case 'histogram':
                $formatted['histogram'] = [
                    'dataPoints' => [
                        [
                            'attributes' => $this->formatAttributes($metric['labels'] ?? []),
                            'timeUnixNano' => $this->timeToNanos($metric['timestamp']),
                            'count' => $metric['count'],
                            'sum' => $metric['sum'],
                            'bucketCounts' => $this->calculateBucketCounts($metric['values'] ?? []),
                            'explicitBounds' => [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10]
                        ]
                    ],
                    'aggregationTemporality' => 2 // CUMULATIVE
                ];
                break;
        }

        return $formatted;
    }

    /**
     * Build resource information
     */
    protected function buildResource(): array
    {
        return [
            'attributes' => [
                [
                    'key' => 'service.name',
                    'value' => ['stringValue' => $this->config['service_name']]
                ],
                [
                    'key' => 'service.version',
                    'value' => ['stringValue' => $this->config['service_version']]
                ],
                [
                    'key' => 'deployment.environment',
                    'value' => ['stringValue' => $this->config['environment']]
                ]
            ]
        ];
    }

    /**
     * Build scope information
     */
    protected function buildScope(): array
    {
        return [
            'name' => 'ludelix-framework',
            'version' => '1.0.0'
        ];
    }

    /**
     * Format attributes
     */
    protected function formatAttributes(array $attributes): array
    {
        $formatted = [];
        
        foreach ($attributes as $key => $value) {
            $formatted[] = [
                'key' => $key,
                'value' => $this->formatAttributeValue($value)
            ];
        }
        
        return $formatted;
    }

    /**
     * Format attribute value
     */
    protected function formatAttributeValue(mixed $value): array
    {
        if (is_string($value)) {
            return ['stringValue' => $value];
        } elseif (is_int($value)) {
            return ['intValue' => $value];
        } elseif (is_float($value)) {
            return ['doubleValue' => $value];
        } elseif (is_bool($value)) {
            return ['boolValue' => $value];
        } else {
            return ['stringValue' => (string)$value];
        }
    }

    /**
     * Format events (logs)
     */
    protected function formatEvents(array $logs): array
    {
        $events = [];
        
        foreach ($logs as $log) {
            $events[] = [
                'timeUnixNano' => $this->timeToNanos($log['timestamp']),
                'name' => $log['message'],
                'attributes' => $this->formatAttributes($log['fields'] ?? [])
            ];
        }
        
        return $events;
    }

    /**
     * Get span kind
     */
    protected function getSpanKind(array $span): int
    {
        $component = $span['tags']['component'] ?? '';
        
        return match($component) {
            'http' => 2, // SERVER
            'http_client' => 3, // CLIENT
            'database' => 3, // CLIENT
            default => 1 // INTERNAL
        };
    }

    /**
     * Get span status
     */
    protected function getSpanStatus(array $span): array
    {
        $error = $span['tags']['error'] ?? false;
        
        return [
            'code' => $error ? 2 : 1, // ERROR : OK
            'message' => $error ? ($span['tags']['error.message'] ?? 'Error') : ''
        ];
    }

    /**
     * Get metric unit
     */
    protected function getMetricUnit(array $metric): string
    {
        $name = $metric['name'];
        
        if (str_contains($name, 'duration') || str_contains($name, 'time')) {
            return 's';
        } elseif (str_contains($name, 'bytes') || str_contains($name, 'memory')) {
            return 'By';
        } elseif (str_contains($name, 'percent')) {
            return '%';
        }
        
        return '1';
    }

    /**
     * Calculate histogram bucket counts
     */
    protected function calculateBucketCounts(array $values): array
    {
        $bounds = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10];
        $counts = array_fill(0, count($bounds) + 1, 0);
        
        foreach ($values as $value) {
            for ($i = 0; $i < count($bounds); $i++) {
                if ($value <= $bounds[$i]) {
                    $counts[$i]++;
                    break;
                }
            }
            if ($value > end($bounds)) {
                $counts[count($bounds)]++;
            }
        }
        
        return $counts;
    }

    /**
     * Convert hex string to bytes
     */
    protected function hexToBytes(string $hex): string
    {
        return base64_encode(hex2bin($hex));
    }

    /**
     * Convert timestamp to nanoseconds
     */
    protected function timeToNanos(float $timestamp): int
    {
        return (int)($timestamp * 1_000_000_000);
    }

    /**
     * Get span data (mock implementation)
     */
    protected function getSpanData(string $spanId): ?array
    {
        // In real implementation, this would fetch from TraceManager
        return null;
    }

    /**
     * Send data to OpenTelemetry collector
     */
    protected function sendToCollector(string $endpoint, array $payload): bool
    {
        $url = rtrim($this->config['endpoint'], '/') . $endpoint;
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => array_merge([
                    'Content-Type: application/json',
                    'User-Agent: ludelix-framework/1.0'
                ], $this->config['headers']),
                'content' => json_encode($payload)
            ]
        ]);
        
        $result = @file_get_contents($url, false, $context);
        return $result !== false;
    }
}