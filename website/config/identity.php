<?php

return [
    /*
    | Keep enabled outside temporary local development. When disabled, newly
    | registered accounts are activated immediately and no email is sent.
    */
    'email_verification_enabled' => env('IDENTITY_EMAIL_VERIFICATION_ENABLED', true),

    'admin_default_password' => env('ADMIN_DEFAULT_PASSWORD', 'ChangeMe123!'),
];
