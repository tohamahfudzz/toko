<?php
function log_activity($conn, $aktor, $aksi) {
    $waktu = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO historytb (aktor, aksi, waktu) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $aktor, $aksi, $waktu);
    $stmt->execute();
    $stmt->close();
}
?>