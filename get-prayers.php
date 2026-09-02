<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json');

require_once 'db-connect.php';

// Ambil campaignId dari parameter URL jika ada
$campaignId = isset($_GET['campaignId']) ? trim($_GET['campaignId']) : '';

try {
    if (!empty($campaignId)) {
        // Ambil doa-doa untuk campaign tertentu yang ada isinya dan tidak null (status SUCCESS)
        $sql = "SELECT id, name, isAnonymous, prayer, createdAt 
                FROM donations 
                WHERE campaignId = :campaignId 
                  AND status = 'SUCCESS'
                  AND prayer IS NOT NULL 
                  AND TRIM(prayer) != '' 
                ORDER BY createdAt DESC
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':campaignId' => $campaignId]);
    } else {
        // Ambil semua doa (status SUCCESS)
        $sql = "SELECT id, name, isAnonymous, prayer, createdAt 
                FROM donations 
                WHERE status = 'SUCCESS'
                  AND prayer IS NOT NULL 
                  AND TRIM(prayer) != '' 
                ORDER BY createdAt DESC
                LIMIT 5";
        $stmt = $pdo->query($sql);
    }
    
    $prayers = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $prayers
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengambil data: ' . $e->getMessage()
    ]);
}
?>
