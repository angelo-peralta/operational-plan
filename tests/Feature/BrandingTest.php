<?php

use Inertia\Testing\AssertableInertia as Assert;

test('public pages expose the SPUP-OPS brand', function () {
    $response = $this->get(route('login'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/login')
        ->where('name', 'SPUP-OPS'),
    );
});

test('the shared application mark uses the SPUP seal', function () {
    $logoSource = file_get_contents(resource_path('js/components/app-logo-icon.tsx'));

    expect($logoSource)
        ->toBeString()
        ->toContain('src="/SPUP-final-logo.png"')
        ->not->toContain('<svg');
});
