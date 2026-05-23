<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=billht_db;charset=utf8', 'root', 'ServBay.dev');
    $stmt = $db->query("SELECT * FROM new_wards LIMIT 1");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($results);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
