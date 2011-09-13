<?php

// Including this file makes a connection to the database
$db = new mysqli('localhost','root','Also root!','timecapsule');
//new PDO("mysql:host=localhost;dbname=timecapsule", 'root', 'Also root!');


function cache_set($name, $value, $expiry=NULL) {
  //Note: expiry not supported yet
  global $db;
  $q = $db->prepare('INSERT INTO cache (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?');
  $q->bind_param('sss', $name, $value, $value);
  $q->execute();
  
}

function log_insert($type, $data=NULL) {
  global $user;
  global $db;
  $q = $db->prepare('INSERT INTO log (type, fb_user_id, timestamp, data) VALUES (?, ?, ?, ?)');
  $uid = $user['id'];
  $time = time();
  $data_encoded = json_encode($data);
  $q->bind_param('ssis', $type, $uid, $time, $data_encoded);
  $q->execute();
}

function log_user($user) {
  // We want to know who the hell is this
  $encoded_user = json_encode($user);
  cache_set('user:' . $user['id'], $encoded_user);
  
  // Log this usage
  log_insert('user_hit');
}



