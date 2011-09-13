<?php
require_once dirname(__FILE__) . '/db.php';

function deauthorize_page() {
  // Remove user from the cache
  global $data;  
  global $db;
  $q = $db->prepare('DELETE FROM cache WHERE name = ?');
  $name = 'user:' . $data['user_id'];
  $q->bind_param('s', $name);
  $q->execute();
  
  // Log users that delete the app :( (just log the ID)
  log_insert('user_removed_app', $data['user_id']);
}


