<?php

namespace Ludelix\Observability\Drivers;

/**
 * Prometheus Driver
 * 
 * Exports metrics in Prometheus format
 */
class PrometheusDriver
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'endpoint' => '/metrics',
            'namespace' => 'ludelix',
            'job' => 'ludelix-app'
        ], $config);
    }

    /**
     * Export metrics
     */
    public function export(array $metrics): string
    {
        $output = '';
        
        foreach ($metrics as $metric) {
            $output .= $this->formatMetric($metric) . "\n";
        }
        
        return $output;
    }

    /**
     * Format single metric
     */
    protected function formatMetric(array $metric): string
    {
        $name = $this->sanitizeName($metric['name']);
        $labels = $this->formatLabels($metric['labels'] ?? []);
        
        switch ($metric['type']) {
            case 'counter':
                return "# TYPE {$name} counter\n{$name}{$labels} {$metric['value']}";
                
            case 'gauge':
                return "# TYPE {$name} gauge\n{$name}{$labels} {$metric['value']}";
                
            case 'histogram':
                return $this->formatHistogram($name, $metric, $labels);
                
            default:
                return "# Unknown metric type: {$metric['type']}";
        }
    }

    /**
     * Format histogram metric
     */
    protected function formatHistogram(string $name, array $metric, string $labels): string
    {
        $output = "# TYPE {$name} histogram\n";
        
        // Calculate buckets
        $buckets = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10];
        $values = $metric['values'] ?? [];
        
        foreach ($buckets as $bucket) {
            $count = count(array_filter($values, fn($v) => $v <= $bucket));
            $bucketLabels = $this->addLabel($labels, 'le', (string)$bucket);
            $output .= "{$name}_bucket{$bucketLabels} {$count}\n";
        }
        
        // Add +Inf bucket
        $infLabels = $this->addLabel($labels, 'le', '+Inf');
        $output .= "{$name}_bucket{$infLabels} {$metric['count']}\n";
        
        // Add sum and count
        $output .= "{$name}_sum{$labels} {$metric['sum']}\n";
        $output .= "{$name}_count{$labels} {$metric['count']}";
        
        return $output;
    }

    /**
     * Format labels
     */
    protected function formatLabels(array $labels): string
    {
        if (empty($labels)) {
            return '';
        }
        
        $formatted = [];
        foreach ($labels as $key => $value) {
            $key = $this->sanitizeName($key);
            $value = $this->escapeValue($value);
            $formatted[] = "{$key}=\"{$value}\"";
        }
        
        return '{' . implode(',', $formatted) . '}';
    }

    /**
     * Add label to existing labels string
     */
    protected function addLabel(string $labels, string $key, string $value): string
    {
        $key = $this->sanitizeName($key);
        $value = $this->escapeValue($value);
        $newLabel = "{$key}=\"{$value}\"";
        
        if (empty($labels)) {
            return "{{$newLabel}}";
        }
        
        return str_replace('}', ",{$newLabel}}", $labels);
    }

    /**
     * Sanitize metric name
     */
    protected function sanitizeName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_:]/', '_', $name);
    }

    /**
     * Escape label value
     */
    protected function escapeValue(string $value): string
    {
        return str_replace(['"', '\\', "\n"], ['\\"', '\\\\', '\\n'], $value);
    }

    /**
     * Serve metrics endpoint
     */
    public function serveMetrics(array $metrics): void
    {
        header('Content-Type: text/plain; version=0.0.4; charset=utf-8');
        echo $this->export($metrics);
    }

    /**
     * Push metrics to Prometheus gateway
     */
    public function pushGateway(array $metrics, string $gatewayUrl): bool
    {
        $data = $this->export($metrics);
        $job = $this->config['job'];
        $url = rtrim($gatewayUrl, '/') . "/metrics/job/{$job}";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: text/plain',
                'content' => $data
            ]
        ]);
        
        $result = file_get_contents($url, false, $context);
        return $result !== false;
    }

    /**
     * Get metrics endpoint URL
     */
    public function getEndpoint(): string
    {
        return $this->config['endpoint'];
    }
}