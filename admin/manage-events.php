<?php
include 'includes/admin-header.php';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $event_id = $_POST['event_id'];
    $action = $_POST['action'];
    
    // Verify the user has permission to modify this event - FIXED THIS LINE
    $stmt = $pdo->prepare("SELECT created_by FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event_data = $stmt->fetch(PDO::FETCH_ASSOC); // Now calling fetch() on the statement
    
    if ($event_data && (isAdmin() || $event_data['created_by'] == $_SESSION['user_id'])) {
        switch($action) {
            case 'complete':
                // We need to add these functions first - let's create them
                $stmt = $pdo->prepare("UPDATE events SET status = 'completed', completed_at = NOW(), completed_by = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $event_id]);
                $_SESSION['message'] = "Event marked as completed successfully!";
                $_SESSION['message_type'] = 'success';
                break;
                
            case 'cancel':
                $stmt = $pdo->prepare("UPDATE events SET status = 'cancelled', completed_at = NOW(), completed_by = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $event_id]);
                $_SESSION['message'] = "Event cancelled successfully!";
                $_SESSION['message_type'] = 'success';
                break;
                
            case 'reactivate':
                $stmt = $pdo->prepare("UPDATE events SET status = 'upcoming', completed_at = NULL, completed_by = NULL WHERE id = ?");
                $stmt->execute([$event_id]);
                $_SESSION['message'] = "Event reactivated successfully!";
                $_SESSION['message_type'] = 'success';
                break;
        }
    } else {
        $_SESSION['message'] = "You don't have permission to modify this event!";
        $_SESSION['message_type'] = 'error';
    }
    
    // header("Location: manage-events.php");
    echo "<script>window.location.href='manage-events.php';</script>";
    exit();
}

// First, let's add the missing status column to events table if it doesn't exist
try {
    $pdo->query("SELECT status FROM events LIMIT 1");
} catch (PDOException $e) {
    // Add status column if it doesn't exist
    $pdo->exec("ALTER TABLE events ADD COLUMN status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming'");
    $pdo->exec("ALTER TABLE events ADD COLUMN completed_at DATETIME NULL");
    $pdo->exec("ALTER TABLE events ADD COLUMN completed_by INT NULL");
    $pdo->exec("ALTER TABLE events ADD COLUMN duration INT NULL");
    $pdo->exec("ALTER TABLE events ADD COLUMN duration_unit ENUM('minutes', 'hours', 'days') DEFAULT 'hours'");
}

// Get events (admin sees all, staff sees only their events)
if (isAdmin()) {
    $events = $pdo->query("SELECT e.*, u.name as creator_name, 
                                  COUNT(er.id) as registrations,
                                  COUNT(CASE WHEN er.status = 'registered' THEN 1 END) as registered_count,
                                  COUNT(CASE WHEN er.status = 'interested' THEN 1 END) as interested_count
                          FROM events e 
                          LEFT JOIN users u ON e.created_by = u.id 
                          LEFT JOIN event_registrations er ON e.id = er.event_id 
                          GROUP BY e.id 
                          ORDER BY 
                            CASE e.status
                                WHEN 'ongoing' THEN 0
                                WHEN 'upcoming' THEN 1
                                WHEN 'completed' THEN 2
                                WHEN 'cancelled' THEN 3
                                ELSE 4
                            END,
                            e.event_date DESC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT e.*, u.name as creator_name, 
                                  COUNT(er.id) as registrations,
                                  COUNT(CASE WHEN er.status = 'registered' THEN 1 END) as registered_count,
                                  COUNT(CASE WHEN er.status = 'interested' THEN 1 END) as interested_count
                          FROM events e 
                          LEFT JOIN users u ON e.created_by = u.id 
                          LEFT JOIN event_registrations er ON e.id = er.event_id 
                          WHERE e.created_by = ? 
                          GROUP BY e.id 
                          ORDER BY 
                            CASE e.status
                                WHEN 'ongoing' THEN 0
                                WHEN 'upcoming' THEN 1
                                WHEN 'completed' THEN 2
                                WHEN 'cancelled' THEN 3
                                ELSE 4
                            END,
                            e.event_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate dynamic status for each event - let's create these helper functions
// function calculateEventStatus($event) {
//     if ($event['status'] === 'cancelled' || $event['status'] === 'completed') {
//         return $event['status'];
//     }
    
//     $event_datetime = strtotime($event['event_date'] . ' ' . $event['event_time']);
//     $current_time = time();
    
//     // If event is in the past, mark as completed
//     if ($event_datetime < $current_time) {
//         return 'completed';
//     }
    
//     // If event is within the next 2 hours, mark as ongoing
//     if (($event_datetime - $current_time) <= 7200) { // 2 hours in seconds
//         return 'ongoing';
//     }
    
//     return 'upcoming';
// }

// function isEventLive($event) {
//     $status = calculateEventStatus($event);
//     return $status === 'ongoing';
// }

// function isEventSoon($event) {
//     $event_datetime = strtotime($event['event_date'] . ' ' . $event['event_time']);
//     $current_time = time();
//     return ($event_datetime - $current_time) <= 86400; // Within 24 hours
// }

// function formatDuration($duration, $unit) {
//     if (!$duration) return '';
    
//     $units = [
//         'minutes' => 'min',
//         'hours' => 'hr',
//         'days' => 'day'
//     ];
    
//     return $duration . ' ' . ($units[$unit] ?? $unit);
// }

foreach($events as &$event) {
    $event['calculated_status'] = calculateEventStatus($event);
    $event['is_live'] = isEventLive($event);
    $event['is_soon'] = isEventSoon($event);
}
unset($event); // Important: break the reference
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Manage Events</h1>
        <a href="add-event.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Event
        </a>
    </div>

    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php echo $_SESSION['message']; ?>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count(array_filter($events, fn($e) => $e['calculated_status'] === 'upcoming')); ?></h3>
                <p>Upcoming Events</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-play-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count(array_filter($events, fn($e) => $e['calculated_status'] === 'ongoing')); ?></h3>
                <p>Ongoing Events</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count(array_filter($events, fn($e) => $e['calculated_status'] === 'completed')); ?></h3>
                <p>Completed Events</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count(array_filter($events, fn($e) => $e['calculated_status'] === 'cancelled')); ?></h3>
                <p>Cancelled Events</p>
            </div>
        </div>
    </div>

    <div class="admin-table">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date & Time</th>
                    <th>Venue</th>
                    <th>Status</th>
                    <th>Registrations</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($events)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No events found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($events as $event): 
                        $date = date('M j, Y', strtotime($event['event_date']));
                        $time = date('g:i A', strtotime($event['event_time']));
                        $status_class = $event['calculated_status'];
                        $status_text = ucfirst($event['calculated_status']);
                    ?>
                    <tr class="event-row status-<?php echo $status_class; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo ucfirst($event['category']); ?></small>
                        </td>
                        <td>
                            <?php echo $date; ?><br>
                            <small class="text-muted"><?php echo $time; ?></small>
                            <?php if($event['duration'] && $event['duration_unit']): ?>
                                <br>
                                <small class="text-muted">Duration: <?php echo formatDuration($event['duration'], $event['duration_unit']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($event['venue']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $status_class; ?>">
                                <i class="fas 
                                    <?php 
                                    switch($status_class) {
                                        case 'ongoing': echo 'fa-play-circle'; break;
                                        case 'upcoming': echo 'fa-clock'; break;
                                        case 'completed': echo 'fa-check-circle'; break;
                                        case 'cancelled': echo 'fa-times-circle'; break;
                                        default: echo 'fa-calendar';
                                    }
                                    ?>">
                                </i>
                                <?php echo $status_text; ?>
                            </span>
                            <?php if($event['is_live']): ?>
                                <br>
                                <small class="live-badge">
                                    <i class="fas fa-circle"></i> Live Now
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="registration-stats">
                                <span class="stat-going"><?php echo $event['registered_count'] ?? 0; ?> Going</span>
                                <span class="stat-interested"><?php echo $event['interested_count'] ?? 0; ?> Interested</span>
                                <?php if($event['max_participants']): ?>
                                    <span class="stat-max">Max: <?php echo $event['max_participants']; ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($event['creator_name']); ?></td>
                        <td class="admin-actions">
                            <div class="action-buttons">
                                <a href="../event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-sm" title="View Event">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="add-event.php?edit=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline" title="Edit Event">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <!-- Status Management Buttons -->
                                <?php if(isAdmin() || $event['created_by'] == $_SESSION['user_id']): ?>
                                    <?php if($event['calculated_status'] === 'upcoming' || $event['calculated_status'] === 'ongoing'): ?>
                                        <form method="POST" class="inline-form" onsubmit="return confirm('Mark this event as completed?')">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="btn btn-sm btn-success" title="Mark as Completed">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" class="inline-form" onsubmit="return confirm('Cancel this event? This cannot be undone.')">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn btn-sm btn-warning" title="Cancel Event">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    <?php elseif($event['calculated_status'] === 'completed' || $event['calculated_status'] === 'cancelled'): ?>
                                        <form method="POST" class="inline-form" onsubmit="return confirm('Reactivate this event?')">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <input type="hidden" name="action" value="reactivate">
                                            <button type="submit" class="btn btn-sm btn-info" title="Reactivate Event">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="delete-event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this event? This action cannot be undone.')"
                                       title="Delete Event">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
/* Add keyframes for live badge animation */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

/* Your existing CSS styles remain the same */
.admin-table {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.admin-table table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 1px solid #e9ecef;
}

.admin-table td {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    vertical-align: top;
}

.admin-table tr:hover {
    background: #f8f9fa;
}

/* Status Badges */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.status-ongoing {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-upcoming {
    background: #cce7ff;
    color: #004085;
    border: 1px solid #b3d7ff;
}

.status-completed {
    background: #e2e3e5;
    color: #383d41;
    border: 1px solid #d6d8db;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.live-badge {
    color: #e74c3c;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.live-badge i {
    animation: pulse 2s infinite;
}

/* Registration Stats */
.registration-stats {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.8rem;
}

.stat-going { color: #27ae60; }
.stat-interested { color: #f39c12; }
.stat-max { color: #3498db; }

/* Action Buttons */
.admin-actions .action-buttons {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.inline-form {
    display: inline;
}

.admin-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    min-width: auto;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: #3498db;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.stat-content h3 {
    font-size: 1.5rem;
    color: #2c3e50;
    margin: 0;
}

.stat-content p {
    color: #7f8c8d;
    margin: 0;
    font-size: 0.9rem;
}

/* Row Status Colors */
.event-row.status-completed,
.event-row.status-cancelled {
    opacity: 0.7;
    background: #f8f9fa;
}

.event-row.status-completed:hover,
.event-row.status-cancelled:hover {
    background: #e9ecef;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-table {
        overflow-x: auto;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .admin-actions .action-buttons {
        flex-direction: column;
    }
    
    .admin-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include 'includes/admin-footer.php'; ?>