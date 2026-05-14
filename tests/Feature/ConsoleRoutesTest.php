<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsoleRoutesTest extends TestCase
{
    public function test_inspire_command_from_console_routes_executes(): void
    {
        $this->artisan('inspire')
            ->assertExitCode(0);
    }
}
