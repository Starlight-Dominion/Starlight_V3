<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use sdo\Controllers\HomeController;

class HomeControllerTest extends TestCase
{
    public function testIndexReturnsWelcomeMessage()
    {
        $db = new \PDO('sqlite::memory:');
        $controller = new HomeController($db);
        $response = $controller->index();

        $this->assertStringContainsString('Shadowreign', $response);
    }
}
