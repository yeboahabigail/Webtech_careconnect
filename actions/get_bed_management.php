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
    $hospital_query = "SELECT name, num_beds, available_beds FROM hospitals WHERE hospital_id = ?";
    $stmt = $conn->prepare($hospital_query);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hospital = $result->fetch_assoc();

    // Get bed statistics
    $stats_query = "SELECT 
        COUNT(*) as total_beds,
        SUM(CASE WHEN status = 'marked_damage' THEN 1 ELSE 0 END) as damaged_beds,
        SUM(CASE WHEN is_available = 0 AND status != 'marked_damage' THEN 1 ELSE 0 END) as occupied_beds,
        SUM(CASE WHEN is_available = 1 AND status = 'available' THEN 1 ELSE 0 END) as available_beds
        FROM beds 
        WHERE hospital_id = ?";
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    // Build HTML for the modal
    $html = "
        <div class='space-y-6'>
            <div class='bg-gray-50 p-4 rounded-lg'>
                <h3 class='text-lg font-medium text-gray-900 mb-4'>Manage Beds at " . htmlspecialchars($hospital['name']) . "</h3>
                
                <div class='grid grid-cols-2 md:grid-cols-4 gap-4 mb-6'>
                    <div class='bg-white p-4 rounded-lg shadow text-center'>
                        <div class='text-2xl font-bold text-gray-800'>{$stats['total_beds']}</div>
                        <div class='text-sm text-gray-500'>Total Beds</div>
                    </div>
                    <div class='bg-white p-4 rounded-lg shadow text-center'>
                        <div class='text-2xl font-bold text-green-600'>{$stats['available_beds']}</div>
                        <div class='text-sm text-gray-500'>Available</div>
                    </div>
                    <div class='bg-white p-4 rounded-lg shadow text-center'>
                        <div class='text-2xl font-bold text-blue-600'>{$stats['occupied_beds']}</div>
                        <div class='text-sm text-gray-500'>Occupied</div>
                    </div>
                    <div class='bg-white p-4 rounded-lg shadow text-center'>
                        <div class='text-2xl font-bold text-red-600'>{$stats['damaged_beds']}</div>
                        <div class='text-sm text-gray-500'>Damaged</div>
                    </div>
                </div>

                <div class='space-y-4'>
                    <div class='flex items-center justify-between'>
                        <h4 class='font-medium text-gray-700'>Quick Actions</h4>
                    </div>
                    
                    <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                        <button onclick='addBeds({$hospital_id})' 
                                class='bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600'>
                            <i class='fas fa-plus mr-2'></i>Add New Beds
                        </button>
                        
                        <button onclick='markAllDamagedAsAvailable({$hospital_id})' 
                                class='bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600'>
                            <i class='fas fa-check mr-2'></i>Mark All Damaged as Available
                        </button>
                    </div>
                </div>
            </div>

            <div class='bg-gray-50 p-4 rounded-lg'>
                <h4 class='font-medium text-gray-700 mb-4'>Maintenance Actions</h4>
                <div class='space-y-4'>
                    <div class='flex items-center space-x-4'>
                        <button onclick='performMaintenance({$hospital_id}, \"check_damaged\")' 
                                class='bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600'>
                            <i class='fas fa-tools mr-2'></i>Check Damaged Beds
                        </button>
                        
                        <button onclick='performMaintenance({$hospital_id}, \"verify_bookings\")' 
                                class='bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600'>
                            <i class='fas fa-clipboard-check mr-2'></i>Verify Bookings
                        </button>
                    </div>
                </div>
            </div>
        </div>";

    echo json_encode([
        'success' => true,
        'html' => $html,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading bed management: ' . $e->getMessage()]);
}

$conn->close();
?>
