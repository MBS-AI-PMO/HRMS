<?php

it('login Page Load', function () {

    $response = $this->get('/login');

    $response->assertStatus(200);
});


test('login with username and password', function () {

    $this->post('/login',[
        'username' => 'admin',
        'password' => 'admin',
    ]);

    $this->assertAuthenticated();
});

it('After login, it will redirect to Admin Dashboard page', function () {
    $this->post('/login',[
        'username' => 'admin',
        'password' => 'admin',
    ]);
    $this->get(url('admin/dashboard'))->assertOk();
});
