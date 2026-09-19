<?php

/**
 * Access control for capability maps ("förmågekartor").
 *
 * Each map is identified by its key from the content directories config
 * (the same keys shown in the "Välj karta" picker — e.g. "content",
 * "content2"). Usernames must match the keys under 'users' in
 * config/auth.php (or the built-in "editor" user in legacy single-password
 * installs).
 *
 * A rule (for 'read' or 'edit') is one of:
 *   '*'              – anyone, including a logged-out visitor
 *   'authenticated'  – any logged-in user, regardless of which account
 *   ['alice','bob']  – only these usernames
 *   true / false     – shortcuts for '*' / nobody
 *
 * 'default' applies to any map with no entry under 'maps'. Out of the box
 * this matches the app's original behaviour: every map is publicly
 * readable, and any logged-in editor can edit any map. Add an entry under
 * 'maps' to lock a specific map down further.
 */
return [
  'default' => [
    'read' => '*',
    'edit' => 'authenticated',
  ],

  'maps' => [
    // Example: only alice and bob may open content2 at all, and only alice
    // may edit it.
    // 'content2' => [
    //   'read' => ['alice', 'bob'],
    //   'edit' => ['alice'],
    // ],

    // Example: a fully private draft map, invisible to everyone but its editors.
    // 'drafts' => [
    //   'read' => ['alice'],
    //   'edit' => ['alice'],
    // ],
  ],
];
