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
                   u.name as organizer_name
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

function getEventById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT e.*, u.name as organizer_name 
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

function getRegistrationCount($event_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ? AND status = 'registered'");
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