<?php
return [
  // Exact browser origins, including scheme and optional port; no paths/wildcards.
  'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', getenv('MCP_ALLOWED_ORIGINS') ?: '')))),
  // Custom pre-shared Bearer authentication, not OAuth discovery.
  // Store SHA-256 of a random token, never a user's password.
  'token_sha256' => getenv('MCP_TOKEN_SHA256') ?: '',
  'token_user' => getenv('MCP_TOKEN_USER') ?: '',
];
