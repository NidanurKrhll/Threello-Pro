<?php
include 'baglan.php';

$sql = "SELECT * FROM cards ORDER BY created_at DESC";
$result = $conn->query($sql);

$cards = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $cards[] = $row;
    }
}

echo json_encode($cards);
?>