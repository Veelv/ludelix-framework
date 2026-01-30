<?php

namespace Ludelix\Core\Console\Engine;

class OutputFormatter
{
    protected array $colors = [
        'black' => '0;30',
        'red' => '0;31',
        'green' => '0;32',
        'yellow' => '0;33',
        'blue' => '0;34',
        'magenta' => '0;35',
        'cyan' => '0;36',
        'white' => '0;37',
        'bright_black' => '1;30',
        'bright_red' => '1;31',
        'bright_green' => '1;32',
        'bright_yellow' => '1;33',
        'bright_blue' => '1;34',
        'bright_magenta' => '1;35',
        'bright_cyan' => '1;36',
        'bright_white' => '1;37'
    ];

    protected array $backgrounds = [
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'white' => '47'
    ];

    public function line(string $text = '', ?string $color = null): void
    {
        echo $this->format($text, $color) . "\n";
    }

    public function info(string $text): void
    {
        $this->line($text, 'cyan');
    }

    public function success(string $text): void
    {
        $this->line("✅ " . $text, 'green');
    }

    public function warning(string $text): void
    {
        $this->line("⚠️  " . $text, 'yellow');
    }

    public function error(string $text): void
    {
        $this->line("❌ " . $text, 'red');
    }

    public function title(string $text): void
    {
        $this->line($text, 'bright_green');
    }

    public function section(string $text): void
    {
        $this->line($text, 'bright_yellow');
    }

    public function table(array $headers, array $rows): void
    {
        $widths = $this->calculateColumnWidths($headers, $rows);

        // Header
        $this->line($this->formatTableRow($headers, $widths), 'cyan');
        $this->line(str_repeat('-', array_sum($widths) + (count($widths) * 3) - 1));

        // Rows
        foreach ($rows as $row) {
            $this->line($this->formatTableRow($row, $widths));
        }
    }

    public function progressBar(int $current, int $total, int $width = 50): void
    {
        $percent = $total > 0 ? ($current / $total) : 0;
        $filled = (int) ($width * $percent);
        $empty = $width - $filled;

        $bar = str_repeat('█', $filled) . str_repeat('░', $empty);
        $percentage = number_format($percent * 100, 1);

        echo "\r" . $this->format("Progress: [{$bar}] {$percentage}% ({$current}/{$total})", 'cyan');

        if ($current >= $total) {
            echo "\n";
        }
    }

    public function ask(string $question, ?string $default = null): string
    {
        $prompt = $this->format($question, 'yellow');
        if ($default) {
            $prompt .= $this->format(" [{$default}]", 'bright_black');
        }
        $prompt .= ': ';

        echo $prompt;
        $answer = trim(fgets(STDIN));

        return $answer ?: $default;
    }

    public function confirm(string $question, bool $default = false): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        $answer = $this->ask("{$question} ({$defaultText})", $default ? 'yes' : 'no');

        return in_array(strtolower($answer), ['y', 'yes', '1', 'true']);
    }

    public function choice(string $question, array $choices, ?string $default = null): string
    {
        $this->line($question, 'yellow');

        foreach ($choices as $key => $choice) {
            $marker = $choice === $default ? '●' : '○';
            $this->line("  {$marker} [{$key}] {$choice}");
        }

        do {
            $answer = $this->ask('Please select', $default);
        } while (!isset($choices[$answer]));

        return $answer;
    }

    protected function format(string $text, ?string $color = null, ?string $background = null): string
    {
        if (!$this->supportsColor()) {
            return $text;
        }

        $codes = [];

        if ($color && isset($this->colors[$color])) {
            $codes[] = $this->colors[$color];
        }

        if ($background && isset($this->backgrounds[$background])) {
            $codes[] = $this->backgrounds[$background];
        }

        if (empty($codes)) {
            return $text;
        }

        return "\033[" . implode(';', $codes) . "m{$text}\033[0m";
    }

    protected function supportsColor(): bool
    {
        return DIRECTORY_SEPARATOR === '/' ||
            getenv('ANSICON') !== false ||
            getenv('ConEmuANSI') === 'ON' ||
            getenv('TERM_PROGRAM') === 'Hyper';
    }

    protected function calculateColumnWidths(array $headers, array $rows): array
    {
        $widths = array_map('strlen', $headers);

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, strlen((string) $cell));
            }
        }

        return $widths;
    }

    protected function formatTableRow(array $row, array $widths): string
    {
        $formatted = [];
        foreach ($row as $i => $cell) {
            $formatted[] = str_pad((string) $cell, $widths[$i]);
        }
        return implode(' | ', $formatted);
    }
}