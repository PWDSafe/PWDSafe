<?php

return [
    'enabled' => env('USE_LDAP', false),
    'basedn' => env('AD_USERCONTAINER', ''),
    'domain' => env('AD_DOM', ''),
    'server' => env('AD_SRV', ''),

    // Determins if $user@$domain or cn=$user,$basedn should be used for binding to ldap server
    'openldap' => env('USE_OPENLDAP', false),

    // When true, accept any TLS certificate from the LDAP server (useful for self-signed certs)
    'trust_certificate' => env('LDAP_TRUST_CERT', false),

    // Path to a custom CA certificate file (PEM format) for LDAP TLS verification
    'certificate' => env('LDAP_CERTIFICATE', null),
];
