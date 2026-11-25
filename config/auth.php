<?php

use App\Models\Admin;
use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    |
    | "web" is used by storefront customers, "admin" by the store owner.
    | They are completely separate: a customer session can never reach
    | the admin panel, whatever permissions or data it carries.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => Admin::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'admins' => [
            'provider' => 'admins',
            'table' => 'admin_password_reset_tokens',
            'expire' => 15,   // admin reset links last 15 minutes
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
