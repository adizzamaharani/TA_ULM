<?php
require 'config/database.php';
$s = $pdo->query('SELECT id, role, username, password FROM users LIMIT 10');
print_r($s->fetchAll(PDO::FETCH_ASSOC));
