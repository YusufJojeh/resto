<?php

namespace Tests\Feature\RestoCafe;

class NotFoundPageTest extends RestoCafeTestCase
{
    public function test_unknown_web_route_renders_the_custom_not_found_page(): void
    {
        $this->get('/missing-page')
            ->assertStatus(404)
            ->assertSee('errors\/not-found', false);
    }

    public function test_unknown_inertia_route_renders_the_custom_not_found_component(): void
    {
        $this->get('/missing-page', [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
            ->assertStatus(404)
            ->assertSee('errors\/not-found', false);
    }
}
