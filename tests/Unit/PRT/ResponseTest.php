<?php

namespace Ludelix\Tests\Unit\PRT;

use PHPUnit\Framework\TestCase;
use Ludelix\PRT\Response;

class ResponseTest extends TestCase
{
    public function testBasicResponse(): void
    {
        $response = new Response('Hello World', 200);

        $this->assertEquals('Hello World', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->isSuccessful());
    }

    public function testJsonResponse(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $response = new Response();
        $response->json($data);

        $this->assertEquals(json_encode($data), $response->getContent());
        $this->assertEquals('application/json; charset=UTF-8', $response->getHeader('Content-Type'));
    }

    public function testHeaders(): void
    {
        $response = new Response();
        $response->setHeader('X-Custom', 'value');

        $this->assertEquals('value', $response->getHeader('X-Custom'));
        $this->assertTrue($response->hasHeader('X-Custom'));
        $this->assertFalse($response->hasHeader('Nonexistent'));
    }

    public function testStatusCodes(): void
    {
        $response = new Response('', 404);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($response->isClientError());
        $this->assertFalse($response->isSuccessful());
    }

    public function testRedirect(): void
    {
        $response = new Response();
        $response->redirect('/login', 302);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeader('Location'));
        $this->assertTrue($response->isRedirection());
    }

    public function testCookies(): void
    {
        $response = new Response();
        $response->setCookie('session_id', 'abc123', ['expires' => time() + 3600]);

        $cookies = $response->getCookies();
        $this->assertArrayHasKey('session_id', $cookies);
        $this->assertEquals('abc123', $cookies['session_id']['value']);
    }
}