<?php

declare(strict_types=1);

namespace Anode\ErrorHandler\Tests;

use Anode\ErrorHandler\ErrorHandler;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
   public function testSomething()
   {
      $handler = new ErrorHandler();
      // ... your test assertions ...
      $this->assertTrue(true);
   }
}