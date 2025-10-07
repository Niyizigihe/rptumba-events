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

function getEvents($limit = null, $category = 'all') {
    global $pdo;
    
    $sql = "SELECT e.*, 
                   COUNT(er.id) as registered_count,
                   e.organizer as organizer_name
            FROM events e 
            LEFT JOIN event_registrations er ON e.id = er.event_id 
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.event_date >= CURDATE()";
    
    $params = [];
    
    if ($category !== 'all') {
        $sql .= " AND e.category = ?";
        $params[] = $category;
    }
    
    $sql .= " GROUP BY e.id ORDER BY e.event_date ASC";
    
    // Handle LIMIT properly
    if ($limit && is_numeric($limit)) {
        $sql .= " LIMIT ?";
        $params[] = (int)$limit;
        
        $stmt = $pdo->prepare($sql);
        // Bind parameters with types
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
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCurrentAndUpcomingEvents($limit = null) {
    global $pdo;
    
    $sql = "SELECT e.*, 
                   COUNT(er.id) as registered_count,
                   e.organizer as organizer_name
            FROM events e 
            LEFT JOIN event_registrations er ON e.id = er.event_id 
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.event_date >= CURDATE()";
    
    $sql .= " GROUP BY e.id ORDER BY 
              CASE 
                WHEN (e.event_date = CURDATE() AND 
                     (e.duration IS NULL OR 
                      TIME(e.event_time) <= TIME(NOW() + INTERVAL 
                        CASE e.duration_unit
                            WHEN 'minutes' THEN e.duration
                            WHEN 'hours' THEN e.duration * 60
                            WHEN 'days' THEN e.duration * 1440
                            WHEN 'weeks' THEN e.duration * 10080
                            WHEN 'months' THEN e.duration * 43800
                            ELSE 0
                        END MINUTE))) THEN 0 
                ELSE 1 
              END,
              e.event_date ASC, 
              e.event_time ASC";
    
    if ($limit && is_numeric($limit)) {
        $sql .= " LIMIT ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getEventById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT e.*, e.organizer as organizer_name 
                          FROM events e 
                          LEFT JOIN users u ON e.created_by = u.id 
                          WHERE e.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function isRegistered($event_id, $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function registerForEvent($event_id, $user_id, $status = 'registered') {
    global $pdo;
    
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

// Add these functions to your functions.php
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
function redirect($url) {
    header("Location: $url");
    exit();
}

function displayMessage($message, $type = 'success') {
    return "<div class='alert alert-$type'>$message</div>";
}
?>