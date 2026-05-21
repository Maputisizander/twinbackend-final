<?php

namespace App\Http\Controllers\Api\Skycable;

use App\Http\Controllers\Api\BaseAuthController;

class AuthController extends BaseAuthController
{
    protected function company(): string
    {
        return 'skycable';
    }
}
