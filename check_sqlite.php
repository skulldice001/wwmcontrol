<?php
try {
    $db = new PDO('sqlite:database/database.sqlite');
    echo "SQLite connected successfully\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
