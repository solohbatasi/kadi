<?php

namespace App\Services\Mpesa;

class StkPushService
{
    public function __construct(DarajaAuthService $auth)
    {
        $this->auth = $auth;
    }
}
