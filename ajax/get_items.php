<?php
require_once '../config/config.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

if ($type === 'product') {
    $stmt = $pdo->query("SELECT id, name, price FROM products ORDER BY name");
} else if ($type === 'service') {
    $stmt = $pdo->query("SELECT id, name, price FROM services ORDER BY name");
} else {
    echo json_encode([]);
    exit;
}

$items = $stmt->fetchAll();
echo json_encode($items);