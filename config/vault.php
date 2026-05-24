<?php

return [
    /*
     * PBKDF2-SHA256 iteration count for deriving the vault encryption key from
     * the user's password. Use a low value in tests (set VAULT_PBKDF2_ITERATIONS=1
     * in phpunit.xml) to keep the test suite fast.
     */
    'pbkdf2_iterations' => (int) env('VAULT_PBKDF2_ITERATIONS', 600_000),
];
