<?php
// Include the main config and functions
include '../includes/config.php';
include '../includes/functions.php';
// Restrict access to admin/staff only
requireStaff();

// Page-specific access control
$current_page = basename($_SERVER['PHP_SELF']);

// Restrict access to admin/staff only
if (!isLoggedIn() || (!isAdmin() && !isStaff())) {
    $_SESSION['message'] = "Access denied! Admin/staff access required.";
    $_SESSION['message_type'] = 'error';
    header("Location: ../login.php");
    exit();
}

// Only admin can access manage-users.php
if ($current_page == 'manage-users.php' && !isAdmin()) {
    $_SESSION['message'] = "Access denied! Administrator privileges required.";
    $_SESSION['message_type'] = 'error';
    header("Location: dashboard.php");
    exit();
}

// Get statistics for dashboard (only include this in dashboard.php)
if (basename($_SERVER['PHP_SELF']) == 'dashboard.php') {
    $total_events = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_registrations = $pdo->query("SELECT COUNT(*) FROM event_registrations")->fetchColumn();
    $upcoming_events = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn();

    // Get recent events
    $recent_events = $pdo->query("SELECT e.*, COUNT(er.id) as registrations 
                                 FROM events e 
                                 LEFT JOIN event_registrations er ON e.id = er.event_id 
                                 GROUP BY e.id 
                                 ORDER BY e.created_at DESC 
                                 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - RP Tumba College</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Admin-specific styles */
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .admin-stat-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }

        .admin-stat-card h3 {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 0.5rem;
        }

        .admin-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 2rem 0;
        }

        .admin-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .admin-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .admin-table tr:hover {
            background: #f8f9fa;
        }

        .admin-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .admin-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="main-header">
        <nav class="navbar">
            <div class="nav-brand">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>RP Tumba Admin</span>
                </div>
            </div>
            <div class="nav-links">
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-home"></i> Site Home
                </a>
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="manage-events.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-events.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Manage Events
                </a>
                <?php if(isAdmin()): ?>
                    <a href="manage-users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                <?php endif; ?>
                <div class="user-dropdown">
                    <button class="user-btn">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['name']); ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="../logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <?php if(isset($_SESSION['message'])): ?>
            <div class="container">
                <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                    <?php echo $_SESSION['message']; ?>
                </div>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>