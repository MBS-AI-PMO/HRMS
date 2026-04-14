<?php

namespace Tests\Trait;

trait AuthenticateTrait
{
    public function userAuthenticated()
    {
        $this->post('/login',[
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $this->assertAuthenticated();
    }
}
