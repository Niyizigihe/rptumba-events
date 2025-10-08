<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStaff() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'staff' || $_SESSION['role'] === 'admin');
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function requireAdmin() {
    if (!isAdmin()) {
        $_SESSION['message'] = "Access denied! Administrator privileges required.";
        $_SESSION['message_type'] = 'error';
        header("Location: ../index.php");
        exit();
    }
}

function requireStaff() {
    if (!isStaff()) {
        $_SESSION['message'] = "Access denied! Staff privileges required.";
        $_SESSION['message_type'] = 'error';
        header("Location: ../index.php");
        exit();
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['message'] = "Please login to access this page.";
        $_SESSION['message_type'] = 'error';
        header("Location: login.php");
        exit();
    }
}

/**
 * Calculate event end time based on duration and unit
 */
function calculateEventEndTime($event_date, $event_time, $duration, $duration_unit) {
    if (!$duration || !$duration_unit) {
        return null;
    }
    
    $start_datetime = $event_date . ' ' . $event_time;
    
    // Calculate end time based on duration unit using DateTime for better accuracy
    try {
        $start = new DateTime($start_datetime);
        
        switch($duration_unit) {
            case 'minutes':
                $interval = new DateInterval('PT' . $duration . 'M');
                break;
            case 'hours':
                $interval = new DateInterval('PT' . $duration . 'H');
                break;
            case 'days':
                $interval = new DateInterval('P' . $duration . 'D');
                break;
            case 'weeks':
                $interval = new DateInterval('P' . $duration . 'W');
                break;
            case 'months':
                $interval = new DateInterval('P' . $duration . 'M');
                break;
            default:
                return null;
        }
        
        $end = clone $start;
        $end->add($interval);
        return $end->getTimestamp();
        
    } catch (Exception $e) {
        return null;
    }
}



/**
 * Calculate event status based on current time and duration
 */
function calculateEventStatus($event) {
    $current_time = time();
    
    // If event is manually marked as completed or cancelled, return that status
    if (isset($event['status']) && ($event['status'] === 'completed' || $event['status'] === 'cancelled')) {
        return $event['status'];
    }
    
    $event_datetime = strtotime($event['event_date'] . ' ' . $event['event_time']);
    
    // Check if event has NOT started yet
    if ($current_time < $event_datetime) {
        return 'upcoming';
    }
    
    // Event has started - check if it has duration
    if ($event['duration'] && $event['duration_unit']) {
        $end_time = calculateEventEndTime($event['event_date'], $event['event_time'], $event['duration'], $event['duration_unit']);
        
        if ($end_time) {
            // If current time is within event duration (including the end time)
            if ($current_time <= $end_time) {
                return 'ongoing';
            } else {
                // Event duration has passed - mark as completed
                // Only auto-complete if not already completed
                if (!isset($event['status']) || $event['status'] !== 'completed') {
                    autoCompleteEvent($event['id']);
                }
                return 'completed';
            }
        }
    }
    
    // No duration specified - check if we're still on the event date
    $event_date_only = date('Y-m-d', $event_datetime);
    $current_date_only = date('Y-m-d');
    
    if ($event_date_only === $current_date_only) {
        // Same day - event is ongoing
        return 'ongoing';
    } else {
        // Different day - check if it's exactly one day after
        $event_next_day = date('Y-m-d', strtotime('+1 day', $event_datetime));
        
        if ($current_date_only === $event_next_day) {
            // Exactly one day after - still show as ongoing for the full day
            return 'ongoing';
        } elseif (strtotime($current_date_only) > strtotime($event_next_day)) {
            // More than one day after - mark as completed
            if (!isset($event['status']) || $event['status'] !== 'completed') {
                autoCompleteEvent($event['id']);
            }
            return 'completed';
        } else {
            // Shouldn't happen, but fallback
            return 'ongoing';
        }
    }
}

/**
 * Check if event should be shown in active listings
 */
function shouldShowEvent($event) {
    $status = calculateEventStatus($event);
    
    // Show only upcoming and ongoing events in main listings
    // Completed events should be accessible via direct links or archive
    return $status === 'upcoming' || $status === 'ongoing' || $status === 'cancelled';
}


/**
 * Get attended count for completed events
 */
function getAttendedCount($event_id) {
    global $pdo;
    // For now, we'll use registered count as attended count
    // In a real system, you might have an attendance tracking system
    return getRegistrationCount($event_id);
}

/**
 * Get event statistics based on status
 */
function getEventStats($event) {
    $event_id = $event['id'];
    $status = calculateEventStatus($event);
    
    if ($status === 'completed') {
        // For completed events, show attendance stats
        $attended_count = getAttendedCount($event_id);
        $registered_count = getRegistrationCount($event_id);
        
        return [
            'type' => 'past',
            'stats' => [
                'attended' => $attended_count,
                'registered' => $registered_count
            ]
        ];
    } else {
        // For upcoming/ongoing events, show engagement stats
        return [
            'type' => 'current',
            'stats' => [
                'going' => getRegistrationCount($event_id),
                'interested' => getInterestedCount($event_id),
                'max_participants' => $event['max_participants'] ?? null
            ]
        ];
    }
}



/**
 * Check if event is currently live (ongoing)
 */
function isEventLive($event) {
    $status = calculateEventStatus($event);
    return $status === 'ongoing';
}

/**
 * Check if event is upcoming and happening soon (within 3 days)
 */
function isEventSoon($event) {
    if (calculateEventStatus($event) !== 'upcoming') {
        return false;
    }
    
    $event_datetime = strtotime($event['event_date'] . ' ' . $event['event_time']);
    $days_until = floor(($event_datetime - time()) / (60 * 60 * 24));
    return $days_until <= 3;
}

/**
 * Get days until event starts
 */
function getDaysUntilEvent($event) {
    $event_datetime = strtotime($event['event_date'] . ' ' . $event['event_time']);
    return floor(($event_datetime - time()) / (60 * 60 * 24));
}

/**
 * Automatically mark event as completed when duration ends
 */
function autoCompleteEvent($event_id) {
    global $pdo;
    
    // Check if already completed
    $stmt = $pdo->prepare("SELECT status FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $current_status = $stmt->fetchColumn();
    
    if ($current_status !== 'completed') {
        // Get registration counts
        $total_registrations = getRegistrationCount($event_id);
        $total_interested = getInterestedCount($event_id);
        
        // Update event status
        $stmt = $pdo->prepare("UPDATE events SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->execute([$event_id]);
        
        // Log the completion
        $stmt = $pdo->prepare("INSERT INTO event_completion_logs (event_id, completed_at, completion_type, total_registrations, total_interested) VALUES (?, NOW(), 'auto', ?, ?)");
        $stmt->execute([$event_id, $total_registrations, $total_interested]);
        
        return true;
    }
    
    return false;
}

/**
 * Manually mark event as completed by admin/staff
 */
function manuallyCompleteEvent($event_id, $user_id, $notes = '') {
    global $pdo;
    
    // Get registration counts
    $total_registrations = getRegistrationCount($event_id);
    $total_interested = getInterestedCount($event_id);
    
    // Update event status
    $stmt = $pdo->prepare("UPDATE events SET status = 'completed', completed_at = NOW(), completed_by = ? WHERE id = ?");
    $stmt->execute([$user_id, $event_id]);
    
    // Log the completion
    $stmt = $pdo->prepare("INSERT INTO event_completion_logs (event_id, completed_at, completed_by, completion_type, total_registrations, total_interested, notes) VALUES (?, NOW(), ?, 'manual', ?, ?, ?)");
    $stmt->execute([$event_id, $user_id, $total_registrations, $total_interested, $notes]);
    
    return true;
}

/**
 * Set automatic completion time for events without duration
 */
function setAutoCompleteTime($event_id, $auto_complete_time) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE events SET auto_complete_time = ? WHERE id = ?");
    return $stmt->execute([$auto_complete_time, $event_id]);
}

/**
 * Get events for display (excludes completed events by default)
 */

function getEvents($limit = null, $category = 'all', $include_completed = false) {
    global $pdo;
    
    $sql = "SELECT e.*, 
                   COUNT(CASE WHEN er.status = 'registered' THEN 1 END) as registered_count,
                   COUNT(CASE WHEN er.status = 'interested' THEN 1 END) as interested_count,
                   e.organizer as organizer_name
            FROM events e 
            LEFT JOIN event_registrations er ON e.id = er.event_id 
            LEFT JOIN users u ON e.created_by = u.id
            WHERE 1=1";
    
    $params = [];
    
    if (!$include_completed) {
        // Only show events that are not completed
        $sql .= " AND (e.status != 'completed' OR e.status IS NULL)";
    }
    
    if ($category !== 'all') {
        $sql .= " AND e.category = ?";
        $params[] = $category;
    }
    
    // Simplified ordering - we'll handle complex status ordering in PHP
    $sql .= " GROUP BY e.id 
              ORDER BY e.event_date ASC, e.event_time ASC";
    
    if ($limit && is_numeric($limit)) {
        $sql .= " LIMIT ?";
        $params[] = (int)$limit;
        
        $stmt = $pdo->prepare($sql);
        foreach($params as $key => $value) {
            if(is_int($value)) {
                $stmt->bindValue($key + 1, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key + 1, $value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate dynamic status for each event
    foreach($events as &$event) {
        $event['calculated_status'] = calculateEventStatus($event);
        $event['is_live'] = isEventLive($event);
        $event['is_soon'] = isEventSoon($event);
        $event['days_until'] = getDaysUntilEvent($event);
        $event['stats'] = getEventStats($event);
        
        // Calculate end time if duration is set
        if ($event['duration'] && $event['duration_unit']) {
            $event['end_time'] = calculateEventEndTime($event['event_date'], $event['event_time'], $event['duration'], $event['duration_unit']);
            $event['end_date'] = date('Y-m-d H:i:s', $event['end_time']);
        }
    }
    
    // Sort events by status: ongoing first, then upcoming, then completed
    usort($events, function($a, $b) {
        $status_order = [
            'ongoing' => 0,
            'upcoming' => 1,
            'completed' => 2
        ];
        
        $a_order = $status_order[$a['calculated_status']] ?? 3;
        $b_order = $status_order[$b['calculated_status']] ?? 3;
        
        if ($a_order === $b_order) {
            // Same status, sort by date
            return strtotime($a['event_date'] . ' ' . $a['event_time']) - strtotime($b['event_date'] . ' ' . $b['event_time']);
        }
        
        return $a_order - $b_order;
    });
    
    return $events;
}

/**
 * Get events with proper SQL filtering for status
 */
function getEventsWithStatusFilter($status = 'all', $category = 'all', $search = '') {
    global $pdo;
    
    $sql = "SELECT e.*, 
                   COUNT(CASE WHEN er.status = 'registered' THEN 1 END) as registered_count,
                   COUNT(CASE WHEN er.status = 'interested' THEN 1 END) as interested_count,
                   e.organizer as organizer_name
            FROM events e 
            LEFT JOIN event_registrations er ON e.id = er.event_id 
            LEFT JOIN users u ON e.created_by = u.id
            WHERE 1=1";
    
    $params = [];
    
    // Build WHERE conditions based on status
    if ($status === 'ongoing') {
        $sql .= " AND (e.event_date = CURDATE() OR 
                      (e.duration IS NOT NULL AND 
                       TIMESTAMP(e.event_date, e.event_time) + INTERVAL e.duration ";
        
        // Handle different duration units
        $sql .= " CASE 
                    WHEN e.duration_unit = 'minutes' THEN MINUTE
                    WHEN e.duration_unit = 'hours' THEN HOUR
                    WHEN e.duration_unit = 'days' THEN DAY
                    WHEN e.duration_unit = 'weeks' THEN WEEK
                    WHEN e.duration_unit = 'months' THEN MONTH
                    ELSE MINUTE
                  END >= NOW()))";
                  
    } elseif ($status === 'upcoming') {
        $sql .= " AND (e.event_date > CURDATE() OR 
                      (e.event_date = CURDATE() AND e.event_time > CURTIME()))";
    } elseif ($status === 'completed') {
        $sql .= " AND e.status = 'completed'";
    } else {
        // 'all' - show active events (not completed)
        $sql .= " AND (e.status != 'completed' OR e.status IS NULL)";
    }
    
    if ($category !== 'all') {
        $sql .= " AND e.category = ?";
        $params[] = $category;
    }
    
    if (!empty($search)) {
        $sql .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " GROUP BY e.id 
              ORDER BY e.event_date ASC, e.event_time ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate dynamic status for each event
    foreach($events as &$event) {
        $event['calculated_status'] = calculateEventStatus($event);
        $event['is_live'] = isEventLive($event);
        $event['is_soon'] = isEventSoon($event);
        $event['days_until'] = getDaysUntilEvent($event);
        $event['stats'] = getEventStats($event);
        
        // Calculate end time if duration is set
        if ($event['duration'] && $event['duration_unit']) {
            $event['end_time'] = calculateEventEndTime($event['event_date'], $event['event_time'], $event['duration'], $event['duration_unit']);
        }
    }
    
    return $events;
}
function getCurrentAndUpcomingEvents($limit = null) {
    global $pdo;
    
    // Simplified SQL without the complex duration calculation
    $sql = "SELECT e.*, 
                   COUNT(er.id) as registered_count,
                   u.name as organizer_name
            FROM events e 
            LEFT JOIN event_registrations er ON e.id = er.event_id 
            LEFT JOIN users u ON e.created_by = u.id
            WHERE (e.status IS NULL OR e.status != 'completed') 
              AND (e.status IS NULL OR e.status != 'cancelled')
              AND (e.event_date > CURDATE() OR (e.event_date = CURDATE() AND e.event_time >= CURTIME()))
            GROUP BY e.id 
            ORDER BY e.event_date ASC, e.event_time ASC";
    
    if ($limit && is_numeric($limit)) {
        $sql .= " LIMIT ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate dynamic status for each event
    foreach($events as &$event) {
        $event['calculated_status'] = calculateEventStatus($event);
        $event['is_live'] = isEventLive($event);
        $event['is_soon'] = isEventSoon($event);
        $event['days_until'] = getDaysUntilEvent($event);
        
        // Calculate end time if duration is set
        if ($event['duration'] && $event['duration_unit']) {
            $event['end_time'] = calculateEventEndTime($event['event_date'], $event['event_time'], $event['duration'], $event['duration_unit']);
        }
    }
    unset($event); // Break reference
    
    return $events;
}

// Add these helper functions if they don't exist
// function getDaysUntilEvent($event) {
//     $event_datetime = strtotime($event['event_date'] . ' ' . $event['event_time']);
//     $current_time = time();
//     $diff_seconds = $event_datetime - $current_time;
//     return ceil($diff_seconds / (60 * 60 * 24)); // Convert to days
// }

// function calculateEventEndTime($event_date, $event_time, $duration, $duration_unit) {
//     $start_timestamp = strtotime($event_date . ' ' . $event_time);
    
//     // Convert duration to seconds based on unit
//     switch($duration_unit) {
//         case 'minutes':
//             $duration_seconds = $duration * 60;
//             break;
//         case 'hours':
//             $duration_seconds = $duration * 60 * 60;
//             break;
//         case 'days':
//             $duration_seconds = $duration * 24 * 60 * 60;
//             break;
//         case 'weeks':
//             $duration_seconds = $duration * 7 * 24 * 60 * 60;
//             break;
//         case 'months':
//             // Approximate month as 30 days
//             $duration_seconds = $duration * 30 * 24 * 60 * 60;
//             break;
//         default:
//             $duration_seconds = $duration * 60 * 60; // Default to hours
//     }
    
//     $end_timestamp = $start_timestamp + $duration_seconds;
//     return date('Y-m-d H:i:s', $end_timestamp);
// }

function getEventById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT e.*, e.organizer as organizer_name 
                          FROM events e 
                          LEFT JOIN users u ON e.created_by = u.id 
                          WHERE e.id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($event) {
        $event['calculated_status'] = calculateEventStatus($event);
        $event['is_live'] = isEventLive($event);
        $event['is_soon'] = isEventSoon($event);
        $event['days_until'] = getDaysUntilEvent($event);
        
        // Calculate end time if duration is set
        if ($event['duration'] && $event['duration_unit']) {
            $event['end_time'] = calculateEventEndTime($event['event_date'], $event['event_time'], $event['duration'], $event['duration_unit']);
        }
    }
    
    return $event;
}

function isRegistered($event_id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function registerForEvent($event_id, $user_id, $status = 'registered') {
    global $pdo;
    
    // Check if event is still active
    $event = getEventById($event_id);
    if ($event['calculated_status'] === 'completed' || $event['calculated_status'] === 'cancelled') {
        return false; // Cannot register for completed/cancelled events
    }
    
    // Check if already registered
    $existing = isRegistered($event_id, $user_id);
    
    if ($existing) {
        // Update existing registration
        $stmt = $pdo->prepare("UPDATE event_registrations SET status = ?, updated_at = NOW() WHERE event_id = ? AND user_id = ?");
        return $stmt->execute([$status, $event_id, $user_id]);
    } else {
        // Create new registration
        $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, status, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$event_id, $user_id, $status]);
    }
}

function unregisterFromEvent($event_id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ? AND user_id = ?");
    return $stmt->execute([$event_id, $user_id]);
}

function getRegistrationCount($event_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ? AND status = 'registered'");
    $stmt->execute([$event_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getInterestedCount($event_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ? AND status = 'interested'");
    $stmt->execute([$event_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function getTotalEngagementCount($event_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ?");
    $stmt->execute([$event_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

/**
 * Get completion logs for an event
 */
function getEventCompletionLogs($event_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT ecl.*, u.name as completed_by_name 
                          FROM event_completion_logs ecl 
                          LEFT JOIN users u ON ecl.completed_by = u.id 
                          WHERE ecl.event_id = ? 
                          ORDER BY ecl.completed_at DESC");
    $stmt->execute([$event_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Process automatic completions for events with auto_complete_time
 */
function processAutoCompletions() {
    global $pdo;
    
    // Find events that have auto_complete_time set and that time has passed
    $stmt = $pdo->prepare("SELECT id FROM events WHERE auto_complete_time IS NOT NULL AND auto_complete_time <= NOW() AND status = 'ongoing'");
    $stmt->execute();
    $events_to_complete = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $completed_count = 0;
    foreach($events_to_complete as $event_id) {
        if (manuallyCompleteEvent($event_id, null, 'Automatically completed by system based on scheduled time')) {
            $completed_count++;
        }
    }
    
    return $completed_count;
}

/**
 * Format duration for display
 */
function formatDuration($duration, $duration_unit) {
    if (!$duration || !$duration_unit) {
        return 'Single day event';
    }
    
    $units = [
        'minutes' => 'minute',
        'hours' => 'hour',
        'days' => 'day',
        'weeks' => 'week',
        'months' => 'month'
    ];
    
    $unit = $units[$duration_unit] ?? $duration_unit;
    $plural = $duration > 1 ? 's' : '';
    
    return $duration . ' ' . $unit . $plural;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function displayMessage($message, $type = 'success') {
    return "<div class='alert alert-$type'>$message</div>";
}
?>