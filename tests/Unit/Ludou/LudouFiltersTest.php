<?php

namespace Ludelix\Tests\Unit\Ludou;

use Ludelix\Ludou\Partials\LudouFilters;
use PHPUnit\Framework\TestCase;

class LudouFiltersTest extends TestCase
{
    /** @test */
    public function it_converts_string_to_uppercase()
    {
        $this->assertEquals('HELLO WORLD', LudouFilters::apply('upper', 'hello world'));
    }

    /** @test */
    public function it_converts_string_to_lowercase()
    {
        $this->assertEquals('hello world', LudouFilters::apply('lower', 'HELLO WORLD'));
    }

    /** @test */
    public function it_slugifies_strings()
    {
        $this->assertEquals('hello-world', LudouFilters::apply('slug', 'Hello World!'));
        $this->assertEquals('ludelix-framework', LudouFilters::apply('slug', 'Ludelix Framework'));
    }

    /** @test */
    public function it_formats_numbers()
    {
        // number(value, decimals, dec_point, thousands_sep)
        $this->assertEquals('1.234,56', LudouFilters::apply('number', 1234.56, 2, ',', '.'));
        $this->assertEquals('10,00', LudouFilters::apply('number', 10, 2, ',', '.'));
    }

    /** @test */
    public function it_formats_currency()
    {
        $this->assertEquals('R$ 1.234,56', LudouFilters::apply('currency', 1234.56, 'BRL'));
    }

    /** @test */
    public function it_truncates_text()
    {
        $text = 'Lorem ipsum dolor sit amet';
        $this->assertEquals('Lorem ipsum...', LudouFilters::apply('truncate', $text, 11));
    }

    /** @test */
    public function it_joins_array_elements()
    {
        $array = ['a', 'b', 'c'];
        $this->assertEquals('a, b, c', LudouFilters::apply('join', $array, ', '));
    }

    /** @test */
    public function it_gets_first_and_last_element()
    {
        $array = ['a', 'b', 'c'];
        $this->assertEquals('a', LudouFilters::apply('first', $array));
        $this->assertEquals('c', LudouFilters::apply('last', $array));
    }

    /** @test */
    public function it_escapes_html()
    {
        $html = '<script>alert("xss")</script>';
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', LudouFilters::apply('escape', $html));
    }

    /** @test */
    public function it_handles_custom_filters()
    {
        LudouFilters::addFilter('double', function ($value) {
            return $value * 2;
        });

        $this->assertEquals(20, LudouFilters::apply('double', 10));
    }
}
