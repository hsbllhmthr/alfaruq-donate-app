<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Tangani Preflight Request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db-connect.php';

// Membaca data JSON yang dikirimkan oleh fetch
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Format data tidak valid.'
    ]);
    exit;
}

// Ambil dan bersihkan data
$name = isset($data['name']) ? strip_tags(trim($data['name'])) : 'Hamba Allah';
$fullName = isset($data['fullName']) ? strip_tags(trim($data['fullName'])) : '';
$isAnonymous = isset($data['isAnonymous']) && $data['isAnonymous'] ? 1 : 0;
$contact = isset($data['contact']) ? strip_tags(trim($data['contact'])) : '';
$prayer = isset($data['prayer']) ? strip_tags(trim($data['prayer'])) : null;
$amount = isset($data['amount']) ? intval($data['amount']) : 0;
$campaignId = isset($data['campaignId']) ? strip_tags(trim($data['campaignId'])) : 'masjid';

// Validasi sederhana
if (empty($contact)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Kolom nomor ponsel atau email wajib diisi.'
    ]);
    exit;
}

if ($amount < 10000) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Minimal nominal donasi adalah Rp10.000.'
    ]);
    exit;
}

try {
    // Insert ke database MySQL dengan status default PENDING
    $sql = "INSERT INTO donations (name, fullName, isAnonymous, contact, prayer, amount, campaignId, status) 
            VALUES (:name, :fullName, :isAnonymous, :contact, :prayer, :amount, :campaignId, 'PENDING')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':fullName' => $fullName,
        ':isAnonymous' => $isAnonymous,
        ':contact' => $contact,
        ':prayer' => $prayer,
        ':amount' => $amount,
        ':campaignId' => $campaignId
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Donasi dan doa berhasil disimpan ke database.',
        'donation_id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal menyimpan data: ' . $e->getMessage()
    ]);
}
?>
