<?php

namespace Tests\Feature;

use Tests\TestCase;

class TokenTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/');

        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );

        $response->assertStatus(200);
    }
}
