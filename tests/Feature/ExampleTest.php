<?php

test('home redirects to companies index', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('companies.index'));
});