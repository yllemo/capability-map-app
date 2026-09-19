<?php

/**
 * Editor accounts.
 *
 * ADDING A USER
 * 1. Generate a password hash (run once, anywhere PHP is available):
 *      php -r "echo password_hash('their-password', PASSWORD_DEFAULT), PHP_EOL;"
 * 2. Add an entry under 'users' below with that hash. Never store a plain
 *    password in 'password_hash' — use the 'password' field instead if you
 *    can't run the command above (fine for internal/low-risk installs, but
 *    prefer a hash when you can).
 * 3. Reference the same username in config/acl.php to control which
 *    capability maps ("förmågekartor") that person may read or edit.
 *
 * LEGACY SINGLE PASSWORD
 * Older installs used one shared 'editor_password' for everyone. It still
 * works — anyone who enters it logs in as the built-in "editor" user — so
 * upgrading never locks existing users out. Set it to '' once every person
 * has their own account below.
 */
return [
  'users' => [
    // 'anna' => [
    //   'name' => 'Anna Andersson',
    //   'password_hash' => '$2y$10$replace.with.a.real.bcrypt.hash.generated.above',
    // ],
    // 'erik' => [
    //   'name' => 'Erik Eriksson',
    //   'password' => 'a-plain-password-only-for-low-risk-installs',
    // ],
  ],

  // Legacy shared password. Leave empty ('') once real accounts are set up above.
  'editor_password' => getenv('EDITOR_PASSWORD') ?: 'CHANGE-ME-TO-SECURE-PASSWORD-BEFORE-DEPLOYMENT',

  // Cookie name used for the editor login session
  'cookie_name' => 'capmap_editor',

  // Cookie/session lifetime (seconds)
  'cookie_ttl' => 60 * 60 * 8, // 8 hours

  // Secret used to sign the login cookie so it can't be forged. Change this
  // to a long random string before deployment, e.g.: openssl rand -hex 32
  'session_secret' => getenv('AUTH_SESSION_SECRET') ?: 'CHANGE-ME-TO-A-LONG-RANDOM-SECRET',
];
