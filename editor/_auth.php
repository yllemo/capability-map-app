<?php
// Kept for backwards compatibility: every editor/ai/mcp script still does
// `require __DIR__ . '/_auth.php'`. The actual auth/ACL functions
// (is_authed, current_user, require_auth, require_edit, require_admin, ...)
// live in app/bootstrap.php so that view/*.php can use them too.
require __DIR__ . '/../app/bootstrap.php';
