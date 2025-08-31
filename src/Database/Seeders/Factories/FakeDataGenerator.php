<?php

namespace Ludelix\Database\Seeders\Factories;

class FakeDataGenerator
{
    protected array $names = ['Sample Name'];
    protected array $words = ['sample', 'word', 'text', 'data'];
    protected array $domains = ['example.com'];

    public function name(): string
    {
        return $this->randomElement($this->names);
    }

    public function email(): string
    {
        $name = strtolower(str_replace(' ', '.', $this->name()));
        $domain = $this->randomElement($this->domains);
        return $name . '@' . $domain;
    }

    public function word(): string
    {
        return $this->randomElement($this->words);
    }

    public function words(int $count = 3): array
    {
        $words = [];
        for ($i = 0; $i < $count; $i++) {
            $words[] = $this->word();
        }
        return $words;
    }

    public function sentence(int $wordCount = 6): string
    {
        $words = $this->words($wordCount);
        return ucfirst(implode(' ', $words)) . '.';
    }

    public function paragraph(int $sentences = 3): string
    {
        $paragraphs = [];
        for ($i = 0; $i < $sentences; $i++) {
            $paragraphs[] = $this->sentence(rand(4, 12));
        }
        return implode(' ', $paragraphs);
    }

    public function paragraphs(int $count = 3, bool $asText = false): array|string
    {
        $paragraphs = [];
        for ($i = 0; $i < $count; $i++) {
            $paragraphs[] = $this->paragraph(rand(2, 5));
        }
        
        return $asText ? implode("\n\n", $paragraphs) : $paragraphs;
    }

    public function number(int $min = 1, int $max = 100): int
    {
        return rand($min, $max);
    }

    public function boolean(int $chanceOfTrue = 50): bool
    {
        return rand(1, 100) <= $chanceOfTrue;
    }

    public function date(string $format = 'Y-m-d'): string
    {
        $timestamp = rand(strtotime('-1 year'), time());
        return date($format, $timestamp);
    }

    public function datetime(string $format = 'Y-m-d H:i:s'): string
    {
        $timestamp = rand(strtotime('-1 year'), time());
        return date($format, $timestamp);
    }

    public function url(): string
    {
        $protocols = ['http', 'https'];
        $domains = ['example.com', 'test.com', 'demo.org', 'sample.net'];
        
        return $this->randomElement($protocols) . '://' . $this->randomElement($domains);
    }

    public function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function slug(string $text = null): string
    {
        $text = $text ?: $this->sentence(3);
        return strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $text));
    }

    protected function randomElement(array $array): mixed
    {
        return $array[array_rand($array)];
    }
}