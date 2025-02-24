<?php
// File: /public_html/php/migrate_checkins.php
require_once 'db_config.php';

try {
    // Fetch all check-ins from defendant_checkins for DEF065579
    $stmt = $pdo->prepare("SELECT date, status, latitude, longitude FROM defendant_checkins WHERE defendant_id = ? ORDER BY date");
    $stmt->execute(['DEF065579']);
    $checkinsFromTable = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch current checkins from defendants
    $stmt = $pdo->prepare("SELECT checkins FROM defendants WHERE user_id = ?");
    $stmt->execute(['DEF065579']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentCheckins = $result['checkins'] ? json_decode($result['checkins'], true) : [];

    // Convert defendant_checkins format to match defendants.checkins
    foreach ($checkinsFromTable as $checkin) {
        $newCheckin = [
            'date' => $checkin['date'],
            'status' => $checkin['status'],
            'latitude' => $checkin['latitude'],
            'longitude' => $checkin['longitude'],
            'ip_address' => 'unknown', // Placeholder, adjust if available
            'selfie_url' => null, // Placeholder, adjust if available
            'employment_status' => 'unknown', // Placeholder
            'employer_name' => null,
            'living_situation' => 'unknown',
            'current_address' => 'unknown',
            'contact_number' => 'unknown',
            'travel_plans' => 'unknown',
            'comments' => null,
            'spoofed' => false
        ];
        // Avoid duplicates by checking date
        if (!in_array($newCheckin['date'], array_column($currentCheckins, 'date'))) {
            $currentCheckins[] = $newCheckin;
        }
    }

    // Update defendants.checkins
    $stmt = $pdo->prepare("UPDATE defendants SET checkins = ? WHERE user_id = ?");
    $stmt->execute([json_encode($currentCheckins), 'DEF065579']);

    echo "Check-ins migrated successfully for DEF065579!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>