<?php
session_start();
header('Content-Type: application/json');

// Proteksi Session Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized. Silakan login terlebih dahulu.'
    ]);
    exit;
}

require_once __DIR__ . '/../db-connect.php';

// Membaca data request JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    // Coba baca dari POST biasa jika bukan JSON raw
    $data = $_POST;
}

$id = isset($data['id']) ? intval($data['id']) : 0;
$status = isset($data['status']) ? strtoupper(trim($data['status'])) : '';

// Validasi input
if ($id <= 0 || !in_array($status, ['PENDING', 'SUCCESS', 'FAILED'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter tidak valid. Diperlukan ID donasi dan Status (PENDING/SUCCESS/FAILED).'
    ]);
    exit;
}

try {
    // Update ke database
    $sql = "UPDATE donations SET status = :status WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':status' => $status,
        ':id' => $id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Status donasi #' . $id . ' berhasil diperbarui menjadi ' . $status . '.'
        ]);
    } else {
        // Baris ada tapi statusnya memang sama
        echo json_encode([
            'status' => 'success',
            'message' => 'Tidak ada perubahan status (status sudah bernilai ' . $status . ' atau data tidak ditemukan).'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal memperbarui status ke database: ' . $e->getMessage()
    ]);
}
?>
