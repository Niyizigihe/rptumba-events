<?php 
include 'includes/config.php';
include 'includes/functions.php';

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

$sql = "SELECT e.*, 
               COUNT(CASE WHEN er.status = 'registered' THEN 1 END) as registered_count,
               COUNT(CASE WHEN er.status = 'interested' THEN 1 END) as interested_count,
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

if (!empty($search)) {
    $sql .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Fixed status filter for ongoing/upcoming
if ($status === 'ongoing') {
    $sql .= " AND (e.event_date = CURDATE() OR 
                  (e.duration IS NOT NULL AND 
                   TIMESTAMP(e.event_date, e.event_time) + INTERVAL 
                   CASE e.duration_unit
                       WHEN 'minutes' THEN e.duration
                       WHEN 'hours' THEN e.duration * 60
                       WHEN 'days' THEN e.duration * 1440
                       WHEN 'weeks' THEN e.duration * 10080
                       WHEN 'months' THEN e.duration * 43800
                       ELSE 0
                   END MINUTE >= NOW()))";
} elseif ($status === 'upcoming') {
    $sql .= " AND (e.event_date > CURDATE() OR 
                  (e.event_date = CURDATE() AND e.event_time > TIME(NOW())))";
}

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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>Discover Events</h1>
        <p>Find and join amazing college events happening around you</p>
    </div>
    
    <!-- Admin/Staff Quick Actions -->
    <?php if(isStaff()): ?>
    <div class="admin-quick-actions">
        <div class="quick-actions-header">
            <i class="fas fa-tachometer-alt"></i>
            <h3>Staff Quick Actions</h3>
        </div>
        <div class="action-buttons">
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
    
    <!-- Advanced Filters -->
    <div class="filters-card">
        <div class="filters-header">
            <h3><i class="fas fa-filter"></i> Filter Events</h3>
            <?php if($category !== 'all' || !empty($search) || $status !== 'all'): ?>
                <span class="active-filters">Active filters</span>
            <?php endif; ?>
        </div>
        <form method="GET" class="filter-form">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="search">Search Events</label>
                    <input type="text" id="search" name="search" placeholder="Search by title, description, or venue..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <option value="academic" <?php echo $category === 'academic' ? 'selected' : ''; ?>>Academic</option>
                        <option value="sports" <?php echo $category === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="cultural" <?php echo $category === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                        <option value="workshop" <?php echo $category === 'workshop' ? 'selected' : ''; ?>>Workshop</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status">Event Status</label>
                    <select id="status" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Events</option>
                        <option value="ongoing" <?php echo $status === 'ongoing' ? 'selected' : ''; ?>>Ongoing Now</option>
                        <option value="upcoming" <?php echo $status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="events.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear All
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Header -->
    <div class="results-header">
        <div class="results-count">
            <h3><?php echo count($events); ?> Events Found</h3>
            <?php if($category !== 'all' || !empty($search) || $status !== 'all'): ?>
                <p class="filter-summary">
                    <?php
                    $filters = [];
                    if ($category !== 'all') $filters[] = "Category: " . ucfirst($category);
                    if (!empty($search)) $filters[] = "Search: \"$search\"";
                    if ($status !== 'all') $filters[] = "Status: " . ucfirst($status);
                    echo implode(' • ', $filters);
                    ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="view-options">
            <button class="view-option active" data-view="grid">
                <i class="fas fa-th"></i> Grid
            </button>
            <button class="view-option" data-view="list">
                <i class="fas fa-list"></i> List
            </button>
        </div>
    </div>

    <!-- Events Grid -->
    <div class="events-grid" id="events-container">
        <?php if(empty($events)): ?>
            <div class="no-events">
                <i class="fas fa-calendar-times"></i>
                <h3>No events found</h3>
                <p>Try adjusting your filters or check back later for new events!</p>
                <?php if(isStaff()): ?>
                    <a href="admin/add-event.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create First Event
                    </a>
                <?php endif; ?>
                <a href="events.php" class="btn btn-outline">View All Events</a>
            </div>
        <?php else: ?>
            <?php foreach($events as $event): 
                $date = date('M j, Y', strtotime($event['event_date']));
                $time = date('g:i A', strtotime($event['event_time']));
                $days_left = floor((strtotime($event['event_date']) - time()) / (60 * 60 * 24));
                
                // Determine event status
                $event_datetime = strtotime($event['event_date'] . ' ' . $event['event_time']);
                $is_ongoing = false;
                
                if ($event['duration'] && $event['duration_unit']) {
                    $end_time = $event_datetime;
                    switch($event['duration_unit']) {
                        case 'minutes': $end_time += $event['duration'] * 60; break;
                        case 'hours': $end_time += $event['duration'] * 3600; break;
                        case 'days': $end_time += $event['duration'] * 86400; break;
                        case 'weeks': $end_time += $event['duration'] * 604800; break;
                        case 'months': $end_time += $event['duration'] * 2592000; break;
                    }
                    $is_ongoing = (time() >= $event_datetime && time() <= $end_time);
                } else {
                    $is_ongoing = (date('Y-m-d') == $event['event_date'] && time() >= $event_datetime);
                }
                
                $status_class = $is_ongoing ? 'ongoing' : 'upcoming';
                $status_text = $is_ongoing ? 'Ongoing' : 'Upcoming';
            ?>
            <div class="event-card featured" data-status="<?php echo $status_class; ?>">
                <div class="event-badge">
                    <span class="badge <?php echo $event['category']; ?>">
                        <i class="fas 
                            <?php 
                            switch($event['category']) {
                                case 'academic': echo 'fa-graduation-cap'; break;
                                case 'sports': echo 'fa-running'; break;
                                case 'cultural': echo 'fa-music'; break;
                                case 'workshop': echo 'fa-laptop-code'; break;
                                default: echo 'fa-calendar';
                            }
                            ?>">
                        </i>
                        <?php echo ucfirst($event['category']); ?>
                    </span>
                    <span class="badge status-<?php echo $status_class; ?>">
                        <i class="fas <?php echo $is_ongoing ? 'fa-play-circle' : 'fa-clock'; ?>"></i>
                        <?php echo $status_text; ?>
                    </span>
                    <?php if(!$is_ongoing && $days_left <= 3): ?>
                        <span class="badge urgent">
                            <i class="fas fa-exclamation-circle"></i>
                            Soon
                        </span>
                    <?php endif; ?>
                </div>
                <div class="event-image">
                    <img src="<?php echo $event['image_path']; ?>" alt="<?php echo $event['title']; ?>">
                    <div class="event-overlay">
                        <a href="event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-view">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
                <div class="event-content">
                    <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                    <div class="event-description-container">
                        <p class="event-description">
                            <?php 
                            $description = htmlspecialchars($event['description']);
                            // Limit to 3 lines (approximately 150 characters)
                            if (strlen($description) > 150) {
                                $short_description = substr($description, 0, 150) . '...';
                                echo $short_description;
                            } else {
                                echo $description;
                            }
                            ?>
                        </p>
                        <?php if (strlen($description) > 150): ?>
                            <a href="event-details.php?id=<?php echo $event['id']; ?>" class="view-more-link">
                                Read More <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="event-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <span><?php echo $date; ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo $time; ?></span>
                            <?php if($event['duration'] && $event['duration_unit']): ?>
                                <span class="duration">• <?php echo $event['duration'] . ' ' . $event['duration_unit']; ?></span>
                            <?php endif; ?>
                            <?php if($is_ongoing): ?>
                                <span class="live-indicator">
                                    <i class="fas fa-circle"></i> Live
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($event['venue']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars($event['organizer_name']); ?></span>
                        </div>
                    </div>
                    
                    <div class="event-footer">
                        <div class="event-engagement">
                            <div class="engagement-item">
                                <i class="fas fa-user-check"></i>
                                <span><?php echo $event['registered_count']; ?> Going</span>
                            </div>
                            <div class="engagement-item">
                                <i class="fas fa-star"></i>
                                <span><?php echo $event['interested_count']; ?> Interested</span>
                            </div>
                            <?php if($event['max_participants']): ?>
                                <div class="engagement-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $event['max_participants']; ?> Max</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="event-actions">
                            <?php if(isLoggedIn() && isStudent()): 
                                $registration = isRegistered($event['id'], $_SESSION['user_id']);
                                if($registration): ?>
                                    <?php if($registration['status'] === 'registered'): ?>
                                        <div class="registration-status">
                                            <span class="status-badge registered">
                                                <i class="fas fa-check"></i>
                                                Registered
                                            </span>
                                            <button class="btn btn-outline btn-sm cancel-btn" 
                                                    data-event-id="<?php echo $event['id']; ?>"
                                                    data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <!-- User is interested - show both status and join button -->
                                        <div class="registration-status">
                                            <span class="status-badge interested">
                                                <i class="fas fa-star"></i>
                                                Interested
                                            </span>
                                            <button class="btn btn-primary btn-sm join-btn" 
                                                    data-event-id="<?php echo $event['id']; ?>"
                                                    data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                                <i class="fas fa-plus"></i> Join Now
                                            </button>
                                            <button class="btn btn-outline btn-sm cancel-btn" 
                                                    data-event-id="<?php echo $event['id']; ?>"
                                                    data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                                <i class="fas fa-times"></i> Remove
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- No registration - show both buttons -->
                                    <button class="btn btn-primary btn-sm join-btn" 
                                            data-event-id="<?php echo $event['id']; ?>"
                                            data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                        <i class="fas fa-plus"></i> Join
                                    </button>
                                    <button class="btn btn-outline btn-sm interested-btn" 
                                            data-event-id="<?php echo $event['id']; ?>"
                                            data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                        <i class="fas fa-star"></i> Interested
                                    </button>
                                <?php endif; ?>
                            <?php elseif(isLoggedIn() && isStaff()): ?>
                                <div class="staff-actions">
                                    <a href="admin/add-event.php?edit=<?php echo $event['id']; ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            <?php elseif(!isLoggedIn()): ?>
                                <a href="login.php?redirect=event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-sign-in-alt"></i> Login to Join
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
/* Events Page Specific Styles */
.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-header h1 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.page-header p {
    font-size: 1.2rem;
    color: #7f8c8d;
}

.admin-quick-actions {
    background: linear-gradient(135deg, #e8f4fd 0%, #f0f8ff 100%);
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    border-left: 4px solid #3498db;
}

.quick-actions-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.quick-actions-header h3 {
    margin: 0;
    color: #2c3e50;
}

.quick-actions-header i {
    color: #3498db;
    font-size: 1.2rem;
}

.filters-card {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.filters-header h3 {
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.active-filters {
    background: #3498db;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.filter-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
}

.filter-group input,
.filter-group select {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.results-count h3 {
    margin: 0 0 0.25rem 0;
    color: #2c3e50;
}

.filter-summary {
    margin: 0;
    color: #7f8c8d;
    font-size: 0.9rem;
}

.view-options {
    display: flex;
    gap: 0.5rem;
    background: #f8f9fa;
    padding: 0.25rem;
    border-radius: 8px;
}

.view-option {
    padding: 0.5rem 1rem;
    border: none;
    background: transparent;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #7f8c8d;
}

.view-option.active {
    background: white;
    color: #3498db;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.staff-actions {
    display: flex;
    gap: 0.5rem;
}

/* Event Description Container */
.event-description-container {
    margin-bottom: 1rem;
}

.event-description {
    color: #7f8c8d;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3; /* Limit to 3 lines */
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.view-more-link {
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    transition: color 0.3s ease;
}

.view-more-link:hover {
    color: #2980b9;
}

.view-more-link i {
    font-size: 0.8em;
    transition: transform 0.3s ease;
}

.view-more-link:hover i {
    transform: translateX(2px);
}


/* List View Styles */
.events-grid.list-view {
    grid-template-columns: 1fr !important;
}

.events-grid.list-view .event-card {
    display: flex;
    flex-direction: row;
    height: auto;
    min-height: 200px;
    max-height: none;
}

.events-grid.list-view .event-image {
    width: 250px;
    height: 200px;
    flex-shrink: 0;
}

.events-grid.list-view .event-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 1.5rem;
    min-height: 200px;
}

.events-grid.list-view .event-description {
    -webkit-line-clamp: 2;
    flex: 1;
}

.events-grid.list-view .event-badge {
    flex-direction: row;
    position: relative;
    top: auto;
    left: auto;
    margin-bottom: 0.5rem;
}

.events-grid.list-view .event-meta {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 1rem;
    margin: 0.5rem 0;
}

.events-grid.list-view .meta-item {
    margin-bottom: 0;
    font-size: 0.85rem;
}

.events-grid.list-view .event-footer {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.events-grid.list-view .event-engagement {
    flex: 1;
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.events-grid.list-view .event-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

/* Hide overlay in list view */
.events-grid.list-view .event-overlay {
    display: none;
}

/* Responsive list view */
@media (max-width: 768px) {
    .events-grid.list-view .event-card {
        flex-direction: column;
        min-height: auto;
    }
    
    .events-grid.list-view .event-image {
        width: 100%;
        height: 150px;
    }
    
    .events-grid.list-view .event-content {
        min-height: auto;
        padding: 1rem;
    }
    
    .events-grid.list-view .event-footer {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .events-grid.list-view .event-engagement {
        justify-content: center;
        order: 2;
    }
    
    .events-grid.list-view .event-actions {
        justify-content: center;
        order: 1;
    }
    
    .events-grid.list-view .meta-item {
        font-size: 0.8rem;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .results-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .view-options {
        align-self: stretch;
    }
    
    .event-description {
        -webkit-line-clamp: 2; /* Show only 2 lines on mobile */
    }
}
/* Engagement Stats Styles */
.event-engagement {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
    flex: 1;
}

.engagement-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    color: #7f8c8d;
    white-space: nowrap;
}

.engagement-item i {
    font-size: 0.8em;
}

.engagement-item .fa-user-check {
    color: #27ae60;
}

.engagement-item .fa-star {
    color: #f39c12;
}

.engagement-item .fa-users {
    color: #3498db;
}

/* Fix event-footer layout */
.event-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
    gap: 1rem;
    flex-wrap: wrap;
}

.event-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

/* Registration status styles */
.registration-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
}

.status-badge.registered {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-badge.interested {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .event-footer {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .event-engagement {
        justify-content: center;
        order: 2;
    }
    
    .event-actions {
        justify-content: center;
        order: 1;
    }
    
    .engagement-item {
        font-size: 0.75rem;
    }
    
    .engagement-stats {
        gap: 0.75rem;
    }
}

</style>

<script>
// View toggle functionality with localStorage and error handling
document.addEventListener('DOMContentLoaded', function() {
    const viewOptions = document.querySelectorAll('.view-option');
    const eventsContainer = document.getElementById('events-container');
    
    // Function to get saved view with error handling
    function getSavedView() {
        try {
            return localStorage.getItem('eventsView') || 'grid';
        } catch (error) {
            console.warn('Could not access localStorage, using default view');
            return 'grid';
        }
    }
    
    // Function to save view with error handling
    function saveView(view) {
        try {
            localStorage.setItem('eventsView', view);
        } catch (error) {
            console.warn('Could not save view to localStorage');
        }
    }
    
    // Get saved view
    const savedView = getSavedView();
    
    // Set initial view
    setView(savedView);
    
    // Add click handlers
    viewOptions.forEach(option => {
        option.addEventListener('click', function() {
            const view = this.getAttribute('data-view');
            setView(view);
            saveView(view);
        });
    });
    
    function setView(view) {
        // Validate view
        if (view !== 'grid' && view !== 'list') {
            view = 'grid'; // Fallback to grid
        }
        
        // Update active button
        viewOptions.forEach(opt => {
            const isActive = opt.getAttribute('data-view') === view;
            opt.classList.toggle('active', isActive);
            
            // Update ARIA attributes for accessibility
            opt.setAttribute('aria-pressed', isActive);
        });
        
        // Update grid layout
        eventsContainer.className = 'events-grid';
        if (view === 'list') {
            eventsContainer.classList.add('list-view');
        }
        
        // Add a data attribute for CSS if needed
        eventsContainer.setAttribute('data-view', view);
    }
});
</script>

<!-- Confirmation Modals -->
<div id="joinModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Join Event</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to join "<span id="joinEventTitle"></span>"?</p>
            <p class="modal-note">You'll be registered as a participant for this event.</p>
        </div>
        <div class="modal-footer">
            <form id="joinForm" action="register-event.php" method="POST">
                <input type="hidden" name="event_id" id="joinEventId">
                <input type="hidden" name="action" value="register">
                <button type="button" class="btn btn-outline cancel-modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Yes, Join Event</button>
            </form>
        </div>
    </div>
</div>

<div id="interestedModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Mark as Interested</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to mark "<span id="interestedEventTitle"></span>" as interested?</p>
            <p class="modal-note">
                ✅ You can still join later<br>
                ✅ Shows you're considering this event<br>
                ✅ Doesn't count toward event capacity
            </p>
        </div>
        <div class="modal-footer">
            <form id="interestedForm" action="register-event.php" method="POST">
                <input type="hidden" name="event_id" id="interestedEventId">
                <input type="hidden" name="action" value="interested">
                <button type="button" class="btn btn-outline cancel-modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Yes, Mark as Interested</button>
            </form>
        </div>
    </div>
</div>

<div id="cancelModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Cancel Registration</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to cancel your registration for "<span id="cancelEventTitle"></span>"?</p>
            <p class="modal-note">This will remove you from the event participants list.</p>
        </div>
        <div class="modal-footer">
            <form id="cancelForm" action="register-event.php" method="POST">
                <input type="hidden" name="event_id" id="cancelEventId">
                <input type="hidden" name="action" value="cancel">
                <button type="button" class="btn btn-outline cancel-modal">No, Keep It</button>
                <button type="submit" class="btn btn-danger">Yes, Cancel</button>
            </form>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.close {
    color: #7f8c8d;
    font-size: 1.5rem;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close:hover {
    color: #e74c3c;
}

.modal-body {
    padding: 1.5rem;
}

.modal-body p {
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.modal-note {
    font-size: 0.9rem;
    color: #7f8c8d;
    font-style: italic;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* Registration Status Styles */
.registration-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.status-badge.registered {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-badge.interested {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.current-status {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
}

.status-indicator.registered {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-indicator.interested {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}
</style>

<script>
// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modals = {
        join: document.getElementById('joinModal'),
        interested: document.getElementById('interestedModal'),
        cancel: document.getElementById('cancelModal')
    };
    
    const closeButtons = document.querySelectorAll('.close, .cancel-modal');
    
    // Join button handlers
    document.querySelectorAll('.join-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            const eventTitle = this.getAttribute('data-event-title');
            
            document.getElementById('joinEventId').value = eventId;
            document.getElementById('joinEventTitle').textContent = eventTitle;
            
            modals.join.style.display = 'block';
        });
    });
    
    // Interested button handlers
    document.querySelectorAll('.interested-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            const eventTitle = this.getAttribute('data-event-title');
            
            document.getElementById('interestedEventId').value = eventId;
            document.getElementById('interestedEventTitle').textContent = eventTitle;
            
            modals.interested.style.display = 'block';
        });
    });
    
    // Cancel button handlers
    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            const eventTitle = this.getAttribute('data-event-title');
            
            document.getElementById('cancelEventId').value = eventId;
            document.getElementById('cancelEventTitle').textContent = eventTitle;
            
            modals.cancel.style.display = 'block';
        });
    });
    
    // Close modal handlers
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            Object.values(modals).forEach(modal => {
                modal.style.display = 'none';
            });
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        Object.values(modals).forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
});
</script>