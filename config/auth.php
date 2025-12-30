<?php
return [
  // Simple password gate for editor. Set to empty string to disable.
  // SECURITY: Change this to a secure random password before deployment!
  // Tip: use a long random value (minimum 32 characters)
  'editor_password' => 'CHANGE-ME-TO-SECURE-PASSWORD-BEFORE-DEPLOYMENT',
  
  // Cookie name used for editor login session
  'cookie_name' => 'capmap_editor',
  
  // Cookie lifetime (seconds)
  'cookie_ttl' => 60 * 60 * 8, // 8 hours
];
