<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | AcadVault hashes passwords with Argon2id — the memory-hard winner of the
    | Password Hashing Competition and the OWASP first choice. Supported
    | drivers: "bcrypt", "argon", "argon2id".
    |
    */

    'driver' => 'argon2id',

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Retained so that password hashes created before the switch to Argon2id
    | can still be verified and transparently upgraded on next login.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
        'limit' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | memory  - KiB of memory used per hash (65536 KiB = 64 MB)
    | threads - degree of parallelism
    | time    - number of iterations
    |
    | These are PHP's defaults and meet the OWASP minimum for Argon2id
    | (19 MiB memory, 2 iterations, 1 degree of parallelism). The env
    | overrides let the test suite run with a cheap cost factor.
    |
    | 'verify' is deliberately FALSE. When true, Laravel's ArgonHasher throws
    | a RuntimeException if it is handed a hash that is not Argon2id — which
    | would lock out every account still holding a bcrypt hash from before the
    | switch. With it false, password_verify() reads the algorithm from the
    | hash prefix, and Laravel's rehash-on-login quietly upgrades each bcrypt
    | password to Argon2id the next time its owner signs in.
    |
    */

    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
        'verify' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash On Login
    |--------------------------------------------------------------------------
    |
    | Re-hashes a user's password on successful login when the stored hash no
    | longer matches the current driver or work factor. This is what migrates
    | legacy bcrypt hashes to Argon2id without anyone resetting a password.
    |
    */

    'rehash_on_login' => true,

];
