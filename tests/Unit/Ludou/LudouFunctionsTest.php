<?php

namespace Ludelix\Tests\Unit\Ludou;

use Ludelix\Ludou\Partials\LudouFunctions;
use PHPUnit\Framework\TestCase;

class LudouFunctionsTest extends TestCase
{
    /** @test */
    public function it_calculates_sum_of_array()
    {
        $numbers = [1, 2, 3, 4, 5];
        $this->assertEquals(15, LudouFunctions::apply('sum', $numbers));
    }

    /** @test */
    public function it_calculates_average_of_array()
    {
        $numbers = [2, 4, 6, 8, 10];
        $this->assertEquals(6, LudouFunctions::apply('avg', $numbers));
    }

    /** @test */
    public function it_generates_range_of_numbers()
    {
        $range = LudouFunctions::apply('range', 1, 5);
        $this->assertEquals([1, 2, 3, 4, 5], $range);

        $stepRange = LudouFunctions::apply('range', 0, 10, 2);
        $this->assertEquals([0, 2, 4, 6, 8, 10], $stepRange);
    }

    /** @test */
    public function it_counts_items()
    {
        $array = [1, 2, 3];
        $this->assertEquals(3, LudouFunctions::apply('count', $array));

        $string = "hello";
        $this->assertEquals(5, LudouFunctions::apply('count', $string));
    }

    /** @test */
    public function it_merges_arrays()
    {
        $arr1 = ['a', 'b'];
        $arr2 = ['c', 'd'];

        $merged = LudouFunctions::apply('merge', $arr1, $arr2);
        $this->assertEquals(['a', 'b', 'c', 'd'], $merged);
    }

    /** @test */
    public function it_checks_empty_values()
    {
        $this->assertTrue(LudouFunctions::apply('empty', []));
        $this->assertTrue(LudouFunctions::apply('empty', ''));
        $this->assertTrue(LudouFunctions::apply('empty', null));

        $this->assertFalse(LudouFunctions::apply('empty', ['a']));
        $this->assertFalse(LudouFunctions::apply('empty', 'hello'));
    }

    /** @test */
    public function it_finds_min_and_max()
    {
        $numbers = [5, 1, 9, 3];
        $this->assertEquals(1, LudouFunctions::apply('min', ...$numbers));
        $this->assertEquals(9, LudouFunctions::apply('max', ...$numbers));
    }

    /** @test */
    public function it_handles_custom_functions()
    {
        LudouFunctions::registerCustomFunction('greet', function ($name) {
            return "Hello, {$name}!";
        });

        $this->assertEquals('Hello, World!', LudouFunctions::apply('greet', 'World'));
    }
}
