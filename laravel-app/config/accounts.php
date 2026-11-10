<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Institutional email domains
    |--------------------------------------------------------------------------
    |
    | Accounts whose email domain matches (or is a subdomain of) one of these
    | are treated as Institutional. All other emails are Personal.
    |
    */

    'institutional_email_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('INSTITUTIONAL_EMAIL_DOMAINS', 'csucc.edu.ph'))
    ))),

];
