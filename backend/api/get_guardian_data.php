<?php
include dirname(__DIR__) . '/db_connect.php';

header('Content-Type: application/json');

$guardian_id = intval($_GET['guardian_id'] ?? 0);

if ($guardian_id > 0) {
    $result = $conn->query("SELECT * FROM GUARDIAN WHERE Guardian_ID = $guardian_id");
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Guardian not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid guardian ID']);
}

$conn->close();
?>
