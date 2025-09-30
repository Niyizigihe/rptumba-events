<?php 
include 'includes/config.php';
include 'includes/functions.php';

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT e.*, COUNT(er.id) as registered_count, u.name as organizer_name 
        FROM events e 
        LEFT JOIN event_registrations er ON e.id = er.event_id 
        LEFT JOIN users u ON e.created_by = u.id 
        WHERE e.event_date >= CURDATE()";

$params = [];

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

$sql .= " GROUP BY e.id ORDER BY e.event_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

    <div class="container">
        <h1>College Events</h1>
        
        <!-- Admin/Staff Quick Actions -->
        <?php if(isStaff()): ?>
        <div class="admin-quick-actions" style="background: #e8f4fd; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; border-left: 4px solid #3498db;">
            <h3 style="margin: 0 0 0.5rem 0; color: #2c3e50;">
                <i class="fas fa-tachometer-alt"></i> Staff Quick Actions
            </h3>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="admin/dashboard.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="admin/add-event.php" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Create Event
                </a>
                <a href="admin/manage-events.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-edit"></i> Manage Events
                </a>
                <?php if(isAdmin()): ?>
                    <a href="admin/manage-users.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="filter-form">
                <input type="text" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="category">
                    <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <option value="academic" <?php echo $category === 'academic' ? 'selected' : ''; ?>>Academic</option>
                    <option value="sports" <?php echo $category === 'sports' ? 'selected' : ''; ?>>Sports</option>
                    <option value="cultural" <?php echo $category === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                    <option value="workshop" <?php echo $category === 'workshop' ? 'selected' : ''; ?>>Workshop</option>
                    <option value="other" <?php echo $category === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="events.php" class="btn btn-outline">Clear</a>
            </form>
        </div>

        <!-- Events Grid -->
        <div class="events-grid">
            <?php if(empty($events)): ?>
                <div class="no-events">
                    <h3>No upcoming events found.</h3>
                    <p>Check back later for new events!</p>
                    <?php if(isStaff()): ?>
                        <a href="admin/add-event.php" class="btn btn-primary">Create First Event</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach($events as $event): 
                    $date = date('M j, Y', strtotime($event['event_date']));
                    $time = date('g:i A', strtotime($event['event_time']));
                ?>
                <div class="event-card">
                    <div class="event-image">
                        <img src="<?php echo $event['image_path']; ?>" alt="<?php echo $event['title']; ?>">
                    </div>
                    <div class="event-content">
                        <span class="event-category <?php echo $event['category']; ?>"><?php echo ucfirst($event['category']); ?></span>
                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p class="event-description"><?php echo substr($event['description'], 0, 100); ?>...</p>
                        <p class="event-date">📅 <?php echo $date; ?> at <?php echo $time; ?></p>
                        <p class="event-venue">📍 <?php echo htmlspecialchars($event['venue']); ?></p>
                        <p class="event-participants">👥 <?php echo $event['registered_count']; ?> registered</p>
                        
                        <!-- Show registration buttons only for students -->
                        <?php if(isLoggedIn() && isStudent()): ?>
                            <div class="event-actions">
                                <?php
                                $registration = isRegistered($event['id'], $_SESSION['user_id']);
                                if($registration): ?>
                                    <button class="btn btn-success" disabled>
                                        <?php echo $registration['status'] === 'registered' ? 'Registered' : 'Interested'; ?>
                                    </button>
                                <?php else: ?>
                                    <form action="register-event.php" method="POST" class="inline-form">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" name="action" value="register" class="btn btn-primary">Register</button>
                                    </form>
                                    <form action="register-event.php" method="POST" class="inline-form">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" name="action" value="interested" class="btn btn-outline">Interested</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php elseif(isLoggedIn() && isStaff()): ?>
                            <div class="event-actions">
                                <span class="text-muted">Staff members cannot register for events</span>
                                <a href="admin/add-event.php?edit=<?php echo $event['id']; ?>" class="btn btn-outline btn-sm">Edit Event</a>
                            </div>
                        <?php elseif(!isLoggedIn()): ?>
                            <div class="event-actions">
                                <a href="login.php" class="btn btn-primary">Login to Register</a>
                            </div>
                        <?php endif; ?>
                        
                        <a href="event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-link">View Details →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>