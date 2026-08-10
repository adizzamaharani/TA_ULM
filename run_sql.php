<?php
require 'config/database.php';
$sql = file_get_contents('database mysql/update_dosen_kegiatan.sql');
try {
    $pdo->exec($sql);
    echo "SQL executed successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
