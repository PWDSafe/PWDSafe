<?php

return [
    'enabled' => env('USE_LDAP', false),
    'basedn' => env('AD_USERCONTAINER', ''),
    'domain' => env('AD_DOM', ''),
    'server' => env('AD_SRV', ''),

    // Determins if $user@$domain or cn=$user,$basedn should be used for binding to ldap server
    'openldap' => env('USE_OPENLDAP', false),
];
