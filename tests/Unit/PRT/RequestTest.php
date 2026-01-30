<?php

namespace Ludelix\Tests\Unit\PRT;

use PHPUnit\Framework\TestCase;
use Ludelix\PRT\Request;

class RequestTest extends TestCase
{
    public function testBasicRequestCreation(): void
    {
        $serverData = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/users?page=1',
            'HTTP_HOST' => 'example.com'
        ];

        $request = new Request($serverData);

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/users?page=1', $request->getUri());
        $this->assertEquals('/users', $request->getPath());
    }

    public function testHeaders(): void
    {
        $serverData = [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer token123',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ];

        $request = new Request($serverData);

        $this->assertEquals('application/json', $request->getHeader('content-type'));
        $this->assertEquals('Bearer token123', $request->getHeader('authorization'));
        $this->assertTrue($request->hasHeader('x-requested-with'));
        $this->assertFalse($request->hasHeader('nonexistent'));
    }

    public function testIsAjax(): void
    {
        $serverData = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
        $request = new Request($serverData);

        $this->assertTrue($request->isAjax());
    }

    public function testAttributes(): void
    {
        $request = new Request();
        
        $request->setAttribute('user_id', 123);
        $this->assertEquals(123, $request->getAttribute('user_id'));
        $this->assertEquals('default', $request->getAttribute('nonexistent', 'default'));
        
        $this->assertEquals(['user_id' => 123], $request->getAttributes());
    }
}