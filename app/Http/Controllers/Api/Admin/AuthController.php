<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseAuthController;

class AuthController extends BaseAuthController
{
    protected function company(): string
    {
        return 'telcovantage';
    }
}
