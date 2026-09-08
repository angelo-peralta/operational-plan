<?php

test('redirects the public root to the sign-in page', function () {
    $response = $this->get(route('home'));

    $response->assertRedirectToRoute('login');
});
