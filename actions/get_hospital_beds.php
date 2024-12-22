<?php
session_start();
include('../db/config.php');

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$hospital_id = isset($_GET['hospital_id']) ? intval($_GET['hospital_id']) : 0;

if ($hospital_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid hospital ID']);
    exit;
}

try {
    // Get hospital details
    $hospital_query = "SELECT name FROM hospitals WHERE hospital_id = ?";
    $stmt = $conn->prepare($hospital_query);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hospital = $result->fetch_assoc();

    // Get all beds for the hospital
    $beds_query = "SELECT b.*, 
                          COALESCE(bb.user_id, 0) as booked_by,
                          COALESCE(u.fname, '') as user_fname,
                          COALESCE(u.lname, '') as user_lname
                   FROM beds b
                   LEFT JOIN bed_bookings bb ON b.bed_id = bb.bed_id AND bb.status = 'active'
                   LEFT JOIN users u ON bb.user_id = u.user_id
                   WHERE b.hospital_id = ?
                   ORDER BY b.bed_id";
    $stmt = $conn->prepare($beds_query);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $beds_result = $stmt->get_result();

    // Build HTML for the modal
    $html = "
        <h3 class='text-lg font-medium text-gray-900 mb-4'>Beds at " . htmlspecialchars($hospital['name']) . "</h3>
        <div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4'>";

    while ($bed = $beds_result->fetch_assoc()) {
        $status_class = '';
        $status_text = '';
        
        if ($bed['status'] === 'marked_damage') {
            $status_class = 'bg-red-100 border-red-300';
            $status_text = 'Damaged';
        } elseif (!$bed['is_available']) {
            $status_class = 'bg-blue-100 border-blue-300';
            $status_text = 'Occupied by ' . htmlspecialchars($bed['user_fname'] . ' ' . $bed['user_lname']);
        } else {
            $status_class = 'bg-green-100 border-green-300';
            $status_text = 'Available';
        }

        $html .= "
            <div class='bed-card p-4 rounded-lg border {$status_class}'>
                <div class='flex justify-between items-center mb-2'>
                    <span class='font-medium'>Bed #{$bed['bed_id']}</span>
                    <span class='text-sm'>{$status_text}</span>
                </div>
                <div class='flex justify-end space-x-2 mt-2'>
                    <button onclick='markBedStatus({$bed['bed_id']}, \"" . ($bed['status'] === 'marked_damage' ? 'available' : 'marked_damage') . "\")' 
                            class='text-sm " . ($bed['status'] === 'marked_damage' ? 'text-green-600' : 'text-red-600') . " hover:underline'>
                        " . ($bed['status'] === 'marked_damage' ? 'Mark as Available' : 'Mark as Damaged') . "
                    </button>
                </div>
            </div>";
    }

    $html .= "</div>";

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading beds: ' . $e->getMessage()]);
}

$conn->close();
?>
