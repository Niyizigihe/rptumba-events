<?php
include 'includes/admin-header.php';

if (isset($_GET['id'])) {
    $event_id = $_GET['id'];
    
    // Check ownership (staff can only delete their own events)
    if (!isAdmin()) {
        $stmt = $pdo->prepare("SELECT created_by FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event['created_by'] != $_SESSION['user_id']) {
            $_SESSION['message'] = "Access denied! You can only delete your own events.";
            $_SESSION['message_type'] = 'error';
            header("Location: manage-events.php");
            exit();
        }
    }
    
    // Delete event
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    
    if ($stmt->execute([$event_id])) {
        $_SESSION['message'] = "Event deleted successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Failed to delete event!";
        $_SESSION['message_type'] = 'error';
    }
}

header("Location: manage-events.php");
exit();
?>