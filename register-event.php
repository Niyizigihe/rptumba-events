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
    
    // Get event details to check capacity
    $event = getEventById($event_id);
    if (!$event) {
        $_SESSION['message'] = "Event not found!";
        $_SESSION['message_type'] = 'error';
        header("Location: events.php");
        exit();
    }
    
    // Check current registration status
    $existing = isRegistered($event_id, $user_id);
    
    switch ($action) {
        case 'register':
            // Check if event is full
            $registered_count = getRegistrationCount($event_id);
            if ($event['max_participants'] && $registered_count >= $event['max_participants']) {
                $_SESSION['message'] = "This event is full! Cannot register.";
                $_SESSION['message_type'] = 'error';
            } else if ($existing) {
                if ($existing['status'] === 'registered') {
                    $_SESSION['message'] = "You are already registered for this event!";
                    $_SESSION['message_type'] = 'error';
                } else {
                    // Update from interested to registered
                    $stmt = $pdo->prepare("UPDATE event_registrations SET status = 'registered' WHERE event_id = ? AND user_id = ?");
                    if ($stmt->execute([$event_id, $user_id])) {
                        $_SESSION['message'] = "Successfully registered for the event!";
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = "Failed to register for the event";
                        $_SESSION['message_type'] = 'error';
                    }
                }
            } else {
                // New registration
                $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, status) VALUES (?, ?, 'registered')");
                if ($stmt->execute([$event_id, $user_id])) {
                    $_SESSION['message'] = "Successfully registered for the event!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Registration failed! Please try again.";
                    $_SESSION['message_type'] = 'error';
                }
            }
            break;
            
        case 'interested':
            if ($existing) {
                if ($existing['status'] === 'interested') {
                    $_SESSION['message'] = "You've already marked this event as interested!";
                    $_SESSION['message_type'] = 'error';
                } else {
                    // Update from registered to interested
                    $stmt = $pdo->prepare("UPDATE event_registrations SET status = 'interested' WHERE event_id = ? AND user_id = ?");
                    if ($stmt->execute([$event_id, $user_id])) {
                        $_SESSION['message'] = "Changed to interested!";
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = "Failed to update status";
                        $_SESSION['message_type'] = 'error';
                    }
                }
            } else {
                // New interested mark
                $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, status) VALUES (?, ?, 'interested')");
                if ($stmt->execute([$event_id, $user_id])) {
                    $_SESSION['message'] = "Marked as interested!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Failed to mark as interested";
                    $_SESSION['message_type'] = 'error';
                }
            }
            break;
            
        case 'cancel':
            if ($existing) {
                $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ? AND user_id = ?");
                if ($stmt->execute([$event_id, $user_id])) {
                    $_SESSION['message'] = "Registration cancelled successfully";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Failed to cancel registration";
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = "You are not registered for this event";
                $_SESSION['message_type'] = 'error';
            }
            break;
            
        default:
            $_SESSION['message'] = "Invalid action";
            $_SESSION['message_type'] = 'error';
    }
    
    // Redirect back to the previous page
    $redirect = $_SERVER['HTTP_REFERER'] ?? "event-details.php?id=$event_id";
    header("Location: $redirect");
    exit();
}

header("Location: events.php");
exit();
?>