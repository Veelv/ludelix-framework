<?php

namespace Ludelix\Core\Console\Commands\Security;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Security Logs Command
 * 
 * Visualiza logs de segurança e estatísticas
 */
class SecurityLogsCommand extends BaseCommand
{
    protected string $name = 'security:logs';
    protected string $description = 'View security logs and statistics';
    protected array $options = [
        'type' => 'Filter by violation type',
        'ip' => 'Filter by IP address',
        'limit' => 'Number of entries to show (default: 50)',
        'stats' => 'Show statistics only',
        'blocked' => 'Show blocked IPs only',
    ];

    public function execute(array $arguments, array $options): int
    {
        try {
            if ($options['stats'] ?? false) {
                return $this->showStats();
            }
            
            if ($options['blocked'] ?? false) {
                return $this->showBlockedIPs();
            }
            
            return $this->showLogs($options);
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to read security logs: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Mostra logs de segurança
     */
    private function showLogs(array $options): int
    {
        $this->info("🔒 Security Logs");
        
        $logFile = cubby_path('logs/security/security_violations.log');
        if (!file_exists($logFile)) {
            $this->line("No security logs found.");
            return 0;
        }
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_reverse($lines); // Mostrar mais recentes primeiro
        
        $limit = (int) ($options['limit'] ?? 50);
        $filterType = $options['type'] ?? null;
        $filterIP = $options['ip'] ?? null;
        
        $count = 0;
        foreach ($lines as $line) {
            if ($count >= $limit) break;
            
            $entry = json_decode($line, true);
            if (!$entry) continue;
            
            // Aplicar filtros
            if ($filterType && $entry['type'] !== $filterType) continue;
            if ($filterIP && $entry['ip_address'] !== $filterIP) continue;
            
            $this->displayLogEntry($entry);
            $count++;
        }
        
        if ($count === 0) {
            $this->line("No matching entries found.");
        }
        
        return 0;
    }

    /**
     * Mostra estatísticas
     */
    private function showStats(): int
    {
        $this->info("📊 Security Statistics");
        
        if (class_exists('\Ludelix\Security\Logging\SecurityLogger')) {
            $logger = new \Ludelix\Security\Logging\SecurityLogger();
            $stats = $logger->getSecurityStats();
            
            $this->line("Total Violations: " . $stats['total_violations']);
            $this->line("Blocked IPs: " . count($stats['blocked_ips']));
            
            if (!empty($stats['violations_by_type'])) {
                $this->line("\nViolations by Type:");
                foreach ($stats['violations_by_type'] as $type => $count) {
                    $this->line("  • {$type}: {$count}");
                }
            }
            
            if (!empty($stats['blocked_ips'])) {
                $this->line("\nBlocked IPs:");
                foreach (array_slice($stats['blocked_ips'], -10) as $ip) {
                    $this->line("  • {$ip}");
                }
            }
        } else {
            $this->error("SecurityLogger not available");
            return 1;
        }
        
        return 0;
    }

    /**
     * Mostra IPs bloqueados
     */
    private function showBlockedIPs(): int
    {
        $this->info("🚫 Blocked IPs");
        
        $blockFile = cubby_path('logs/security/blocked_ips.txt');
        if (!file_exists($blockFile)) {
            $this->line("No blocked IPs found.");
            return 0;
        }
        
        $ips = file($blockFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (empty($ips)) {
            $this->line("No blocked IPs found.");
            return 0;
        }
        
        foreach ($ips as $ip) {
            $this->line("  • {$ip}");
        }
        
        return 0;
    }

    /**
     * Exibe uma entrada de log
     */
    private function displayLogEntry(array $entry): void
    {
        $timestamp = $entry['timestamp'] ?? 'unknown';
        $type = $entry['type'] ?? 'unknown';
        $ip = $entry['ip_address'] ?? 'unknown';
        
        $this->line("📅 {$timestamp} | 🔒 {$type} | 🌐 {$ip}");
        
        if (isset($entry['data'])) {
            foreach ($entry['data'] as $key => $value) {
                if (is_string($value) && strlen($value) > 100) {
                    $value = substr($value, 0, 100) . '...';
                }
                $this->line("    {$key}: {$value}");
            }
        }
        
        $this->line(""); // Linha em branco
    }
} 