<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json');

require_once 'db-connect.php';

// Target donasi masing-masing campaign
$targets = [
    'masjid' => 2700000000,    // 2,7 Miliar
    'wakaf'  => 200000000000   // 200 Miliar
];

$campaignId = isset($_GET['campaignId']) ? trim($_GET['campaignId']) : '';

try {
    if (!empty($campaignId)) {
        // Ambil statistik per campaign (hanya yang berstatus SUCCESS)
        $sql = "SELECT COALESCE(SUM(amount), 0) AS total_raised, COUNT(id) AS total_donors 
                FROM donations 
                WHERE campaignId = :campaignId AND status = 'SUCCESS'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':campaignId' => $campaignId]);
        $row = $stmt->fetch();

        $target = isset($targets[$campaignId]) ? $targets[$campaignId] : 10000000;
        $totalRaised = intval($row['total_raised']);
        $percentage = $target > 0 ? min(round(($totalRaised / $target) * 100, 2), 100) : 0;

        echo json_encode([
            'status' => 'success',
            'data' => [
                'campaignId' => $campaignId,
                'totalRaised' => $totalRaised,
                'totalDonors' => intval($row['total_donors']),
                'target' => $target,
                'percentage' => $percentage
            ]
        ]);
    } else {
        // Ambil semua statistik campaign (hanya yang berstatus SUCCESS)
        $sql = "SELECT campaignId, COALESCE(SUM(amount), 0) AS total_raised, COUNT(id) AS total_donors 
                FROM donations 
                WHERE status = 'SUCCESS'
                GROUP BY campaignId";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();

        $result = [];
        // Isi default targets
        foreach ($targets as $id => $target) {
            $result[$id] = [
                'campaignId' => $id,
                'totalRaised' => 0,
                'totalDonors' => 0,
                'target' => $target,
                'percentage' => 0
            ];
        }

        foreach ($rows as $row) {
            $id = $row['campaignId'];
            $target = isset($targets[$id]) ? $targets[$id] : 10000000;
            $totalRaised = intval($row['total_raised']);
            $percentage = $target > 0 ? min(round(($totalRaised / $target) * 100, 2), 100) : 0;

            $result[$id] = [
                'campaignId' => $id,
                'totalRaised' => $totalRaised,
                'totalDonors' => intval($row['total_donors']),
                'target' => $target,
                'percentage' => $percentage
            ];
        }

        echo json_encode([
            'status' => 'success',
            'data' => array_values($result)
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengambil data statistik: ' . $e->getMessage()
    ]);
}
?>
