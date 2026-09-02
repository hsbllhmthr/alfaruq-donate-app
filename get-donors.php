<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json');

require_once 'db-connect.php';

$campaignId = isset($_GET['campaignId']) ? trim($_GET['campaignId']) : '';

try {
    if (!empty($campaignId)) {
        $sql = "SELECT id, name, isAnonymous, amount, createdAt 
                FROM donations 
                WHERE campaignId = :campaignId AND status = 'SUCCESS'
                ORDER BY createdAt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':campaignId' => $campaignId]);
    } else {
        $sql = "SELECT id, name, isAnonymous, amount, createdAt 
                FROM donations 
                WHERE status = 'SUCCESS'
                ORDER BY createdAt DESC";
        $stmt = $pdo->query($sql);
    }
    
    $donors = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $donors
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengambil data: ' . $e->getMessage()
    ]);
}
?>
