<?php

namespace App\Helpers;

class LdapAuthentication
{
    public static function login(string $user, string $pass): bool
    {
        ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);

        $upn = $user . "@" . config('ldap.domain');

        $conn = ldap_connect(config('ldap.server'));
        if (!$conn) {
            throw new \Exception("Could not connect to LDAP-server");
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        $bind = @ldap_bind($conn, $upn, $pass);

        if ($bind) {
            $s = ldap_search(
                $conn,
                config('ldap.basedn'),
                "(|(sAMAccountName=$user))",
                ["cn", "dn", "userPrincipalName", "samaccountname"]
            );

            if ($s === false) {
                throw new \Exception("Could not search AD");
            }

            $info = ldap_get_entries($conn, $s);
            if ($info === false) {
                throw new \Exception("LDAP get entries failed");
            }

            return count($info) > 0;
        }

        // Wrong username or password
        return false;
    }
}
