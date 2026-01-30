<?php

namespace Ludelix\Security\Logging;

/**
 * Security Logger
 * 
 * Sistema avançado de logging de segurança
 */
class SecurityLogger
{
    private string $logPath;
    private array $config;
    private array $violationCounts = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'log_path' => $this->getProjectRoot() . '/cubby/logs/security',
            'max_file_size' => 10 * 1024 * 1024, // 10MB
            'max_files' => 30,
            'alert_threshold' => 10, // Alertar após 10 violações
            'block_threshold' => 50, // Bloquear após 50 violações
            'alert_email' => null,
        ], $config);
        
        $this->logPath = $this->config['log_path'];
        $this->ensureLogDirectory();
    }

    /**
     * Registra uma violação de segurança
     */
    public function logViolation(string $type, array $data = []): void
    {
        $logEntry = $this->createLogEntry($type, $data);
        
        // Salvar no arquivo de log
        $this->writeToLog($logEntry);
        
        // Atualizar contadores
        $this->updateViolationCount($type, $data);
        
        // Verificar se deve alertar ou bloquear
        $this->checkThresholds($type, $data);
        
        // Log adicional para syslog se configurado
        if (function_exists('syslog')) {
            syslog(LOG_WARNING, "SECURITY_VIOLATION: {$type} - " . json_encode($data));
        }
    }

    /**
     * Registra tentativa de upload malicioso
     */
    public function logUploadViolation(string $violationType, array $fileData, array $requestData = []): void
    {
        $data = array_merge($fileData, [
            'request_ip' => $requestData['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'request_user_agent' => $requestData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_referer' => $requestData['referer'] ?? $_SERVER['HTTP_REFERER'] ?? 'unknown',
            'request_method' => $requestData['method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'session_id' => session_id() ?? 'none',
        ]);

        $this->logViolation("upload_{$violationType}", $data);
    }

    /**
     * Registra tentativa de path traversal
     */
    public function logPathTraversal(string $attemptedPath, array $requestData = []): void
    {
        $data = array_merge([
            'attempted_path' => $attemptedPath,
            'base_path' => cubby_path('up'),
            'normalized_path' => realpath($attemptedPath),
        ], $requestData);

        $this->logViolation('path_traversal', $data);
    }

    /**
     * Registra tentativa de rate limiting
     */
    public function logRateLimitViolation(string $key, array $requestData = []): void
    {
        $data = array_merge([
            'rate_limit_key' => $key,
            'attempts' => $this->getViolationCount($key),
        ], $requestData);

        $this->logViolation('rate_limit_exceeded', $data);
    }

    /**
     * Registra tentativa de SQL injection
     */
    public function logSQLInjection(string $query, array $requestData = []): void
    {
        $data = array_merge([
            'suspicious_query' => $query,
            'detection_method' => 'pattern_matching',
        ], $requestData);

        $this->logViolation('sql_injection', $data);
    }

    /**
     * Registra tentativa de XSS
     */
    public function logXSSAttempt(string $payload, array $requestData = []): void
    {
        $data = array_merge([
            'xss_payload' => $payload,
            'detection_method' => 'content_analysis',
        ], $requestData);

        $this->logViolation('xss_attempt', $data);
    }

    /**
     * Cria entrada de log
     */
    private function createLogEntry(string $type, array $data): array
    {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'data' => $data,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'session_id' => session_id() ?? 'none',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
        ];
    }

    /**
     * Escreve no arquivo de log
     */
    private function writeToLog(array $entry): void
    {
        $logFile = $this->logPath . '/security_violations.log';
        $logLine = json_encode($entry) . PHP_EOL;
        
        // Rotacionar arquivo se necessário
        $this->rotateLogFile($logFile);
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Atualiza contadores de violação
     */
    private function updateViolationCount(string $type, array $data): void
    {
        $key = $data['ip_address'] ?? 'unknown';
        
        if (!isset($this->violationCounts[$key])) {
            $this->violationCounts[$key] = [];
        }
        
        if (!isset($this->violationCounts[$key][$type])) {
            $this->violationCounts[$key][$type] = 0;
        }
        
        $this->violationCounts[$key][$type]++;
    }

    /**
     * Verifica thresholds para alertas e bloqueios
     */
    private function checkThresholds(string $type, array $data): void
    {
        $key = $data['ip_address'] ?? 'unknown';
        $count = $this->violationCounts[$key][$type] ?? 0;
        
        if ($count >= $this->config['block_threshold']) {
            $this->logViolation('ip_blocked', [
                'ip' => $key,
                'violation_type' => $type,
                'count' => $count,
                'reason' => 'threshold_exceeded'
            ]);
            
            // Aqui você pode implementar bloqueio real do IP
            $this->blockIP($key);
        } elseif ($count >= $this->config['alert_threshold']) {
            $this->logViolation('security_alert', [
                'ip' => $key,
                'violation_type' => $type,
                'count' => $count,
                'threshold' => $this->config['alert_threshold']
            ]);
            
            // Enviar alerta por email se configurado
            $this->sendAlert($type, $data, $count);
        }
    }

    /**
     * Obtém contagem de violações
     */
    private function getViolationCount(string $key): int
    {
        return $this->violationCounts[$key]['rate_limit_exceeded'] ?? 0;
    }

    /**
     * Bloqueia IP
     */
    private function blockIP(string $ip): void
    {
        // Implementar bloqueio real (firewall, banco de dados, etc.)
        $blockFile = $this->logPath . '/blocked_ips.txt';
        $blockEntry = $ip . ' - ' . date('Y-m-d H:i:s') . ' - Auto-blocked' . PHP_EOL;
        file_put_contents($blockFile, $blockEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Envia alerta por email
     */
    private function sendAlert(string $type, array $data, int $count): void
    {
        if (!$this->config['alert_email']) {
            return;
        }
        
        $subject = "Security Alert: {$type} violation detected";
        $message = "Security violation detected:\n";
        $message .= "Type: {$type}\n";
        $message .= "Count: {$count}\n";
        $message .= "IP: " . ($data['ip_address'] ?? 'unknown') . "\n";
        $message .= "Time: " . date('Y-m-d H:i:s') . "\n";
        $message .= "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        
        mail($this->config['alert_email'], $subject, $message);
    }

    /**
     * Rotaciona arquivo de log se necessário
     */
    private function rotateLogFile(string $logFile): void
    {
        if (!file_exists($logFile)) {
            return;
        }
        
        $fileSize = filesize($logFile);
        if ($fileSize < $this->config['max_file_size']) {
            return;
        }
        
        $backupFile = $logFile . '.' . date('Y-m-d-H-i-s');
        rename($logFile, $backupFile);
        
        // Remover arquivos antigos
        $this->cleanupOldLogs();
    }

    /**
     * Remove logs antigos
     */
    private function cleanupOldLogs(): void
    {
        $pattern = $this->logPath . '/security_violations.log.*';
        $files = glob($pattern);
        
        if (count($files) <= $this->config['max_files']) {
            return;
        }
        
        // Ordenar por data de modificação
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Remover arquivos mais antigos
        $filesToRemove = array_slice($files, 0, count($files) - $this->config['max_files']);
        foreach ($filesToRemove as $file) {
            unlink($file);
        }
    }

    /**
     * Obtém o diretório raiz do projeto
     */
    private function getProjectRoot(): string
    {
        // Tentar diferentes métodos para encontrar o diretório raiz
        $possibleRoots = [
            // Se estamos no diretório do projeto
            getcwd(),
            // Se estamos em um subdiretório, tentar subir até encontrar composer.json
            dirname(__DIR__, 6), // Subir 6 níveis a partir de Security/Logging/
        ];
        
        foreach ($possibleRoots as $root) {
            if (file_exists($root . '/composer.json')) {
                return $root;
            }
        }
        
        // Fallback para o diretório atual
        return getcwd();
    }

    /**
     * Garante que o diretório de log existe
     */
    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Obtém estatísticas de segurança
     */
    public function getSecurityStats(): array
    {
        $stats = [
            'total_violations' => 0,
            'violations_by_type' => [],
            'blocked_ips' => [],
            'recent_violations' => []
        ];
        
        // Contar violações por tipo
        foreach ($this->violationCounts as $ip => $types) {
            foreach ($types as $type => $count) {
                $stats['total_violations'] += $count;
                $stats['violations_by_type'][$type] = ($stats['violations_by_type'][$type] ?? 0) + $count;
            }
        }
        
        // Ler IPs bloqueados
        $blockFile = $this->logPath . '/blocked_ips.txt';
        if (file_exists($blockFile)) {
            $stats['blocked_ips'] = file($blockFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        return $stats;
    }
} 