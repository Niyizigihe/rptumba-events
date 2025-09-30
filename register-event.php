<?php
include 'includes/config.php';
include 'includes/functions.php';

requireLogin();

// Only students can register for events
if (!isStudent()) {
    $_SESSION['message'] = "Only students can register for events. Staff members can create and manage events.";
    $_SESSION['message_type'] = 'error';
    header("Location: events.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];
    
    // Check if already registered
    $existing = isRegistered($event_id, $user_id);
    
    if ($existing) {
        $_SESSION['message'] = "You are already registered for this event!";
        $_SESSION['message_type'] = 'error';
    } else {
        // Register for event
        $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, status) VALUES (?, ?, ?)");
        
        if ($stmt->execute([$event_id, $user_id, $action])) {
            $status_text = $action === 'register' ? 'registered' : 'interested';
            $_SESSION['message'] = "Successfully $status_text for the event!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Registration failed! Please try again.";
            $_SESSION['message_type'] = 'error';
        }
    }
    
    header("Location: event-details.php?id=$event_id");
    exit();
}

header("Location: events.php");
exit();
?>