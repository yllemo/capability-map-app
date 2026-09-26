<?php

/**
 * Editor accounts.
 * Copy this example to config/auth.php and edit that file for your installation.
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
 * ADMINISTRATORS / ADMIN-GRUPP
 * Add 'role' => 'admin' to each administrator under 'users' in config/auth.php.
 * There is no separate 'admins' or 'groups' setting: the role on each account
 * controls access to /admin/ and visibility of the Admin navigation link.
 * Use 'role' => 'editor' for ordinary accounts. See Anna and Erik below.
 * Multiple accounts can have the admin role; usernames can be freely chosen.
 * When role is omitted, usernames 'admin' and 'editor' default to admin for
 * backwards compatibility; all other usernames default to editor.
 * The legacy shared-password account 'editor' also has administrator access.
 * An administrator role does not override map permissions in config/acl.php.
 *
 * Changes saved through /admin/ are stored in content/.capmap-admin.php.
 * A user entry there overrides the entire matching entry in config/auth.php,
 * including role and password. For such accounts, change the role through
 * Admin > Användare or update the corresponding persistent override.
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
    //   'role' => 'admin', // Can manage users and settings at /admin/.
    //   'password_hash' => '$2y$10$replace.with.a.real.bcrypt.hash.generated.above',
    // ],
    // 'erik' => [
    //   'name' => 'Erik Eriksson',
    //   'role' => 'editor', // No access to /admin/.
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
