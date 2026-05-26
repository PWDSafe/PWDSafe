<?php

namespace App\Helpers;

class LdapAuthentication
{
    public function login(string $user, string $pass): bool
    {
        $certOption = config('ldap.trust_certificate') ? LDAP_OPT_X_TLS_ALLOW : LDAP_OPT_X_TLS_NEVER;
        ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, $certOption);
        if (config('ldap.certificate')) {
            ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, config('ldap.certificate'));
        }

        $upn = config('ldap.openldap') ?
            "cn=$user," . config('ldap.basedn') :
            $user . "@" . config('ldap.domain');

        $conn = ldap_connect(config('ldap.server'));
        if (!$conn) {
            throw new \Exception("Could not connect to LDAP-server");
        }

        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

        $bind = @ldap_bind($conn, $upn, $pass);

        if ($bind) {
            // For OpenLDAP, a successful bind is sufficient proof of valid credentials.
            // The sAMAccountName search below is AD-specific and would fail for regular
            // OpenLDAP users who lack directory search permissions.
            if (config('ldap.openldap')) {
                return true;
            }

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
