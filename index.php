<?php include 'includes/config.php'; ?>
<?php include 'includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RP Tumba College - Events Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <nav class="navbar">
            <div class="nav-brand">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>RP Tumba Events</span>
                </div>
            </div>
            <div class="nav-links">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="events.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i> Events
                </a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff'): ?>
                        <a href="admin/dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    <?php endif; ?>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <i class="fas fa-user-circle"></i>
                            <?php echo $_SESSION['name']; ?>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-user-plus"></i> Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-background">
            <div class="hero-overlay"></div>
        </div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">
                        Discover Amazing <span class="highlight">College Events</span>
                    </h1>
                    <p class="hero-subtitle">
                        Join thousands of students in academic, cultural, sports, and workshop events. 
                        Your campus experience starts here.
                    </p>
                    <div class="hero-stats">
                        <div class="stat">
                            <span class="stat-number">50+</span>
                            <span class="stat-label">Events</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">2K+</span>
                            <span class="stat-label">Students</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">15+</span>
                            <span class="stat-label">Departments</span>
                        </div>
                    </div>
                    <div class="hero-actions">
                        <a href="events.php" class="btn btn-primary btn-large">
                            <i class="fas fa-calendar-plus"></i> Explore Events
                        </a>
                        <?php if(!isLoggedIn()): ?>
                            <a href="register.php" class="btn btn-outline-white btn-large">
                                <i class="fas fa-user-graduate"></i> Join Now
                            </a>
                        <?php elseif(isStaff()): ?>
                            <a href="admin/dashboard.php" class="btn btn-outline-white btn-large">
                                <i class="fas fa-tachometer-alt"></i> Admin Panel
                            </a>
                        <?php else: ?>
                            <a href="events.php" class="btn btn-outline-white btn-large">
                                <i class="fas fa-calendar-check"></i> My Events
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="floating-card card-1">
                        <i class="fas fa-microphone"></i>
                        <span>Seminar</span>
                    </div>
                    <div class="floating-card card-2">
                        <i class="fas fa-futbol"></i>
                        <span>Sports</span>
                    </div>
                    <div class="floating-card card-3">
                        <i class="fas fa-music"></i>
                        <span>Cultural</span>
                    </div>
                    <div class="floating-card card-4">
                        <i class="fas fa-laptop-code"></i>
                        <span>Workshop</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose Our Platform</h2>
                <p>Everything you need to stay connected with campus life</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Easy Registration</h3>
                    <p>Register for events with just one click. No paperwork, no hassle.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Smart Reminders</h3>
                    <p>Get timely notifications so you never miss an important event.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community Building</h3>
                    <p>Connect with fellow students who share your interests.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Real-time Updates</h3>
                    <p>Stay informed with live participant counts and event changes.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- Upcoming & Ongoing Events Section -->
<section class="events-section">
    <div class="container">
        <div class="section-header">
            <h2>Current & Upcoming Events</h2>
            <p>Don't miss these exciting events happening now and coming soon</p>
        </div>
        
        <?php
        // Get both ongoing and upcoming events (limit to 3)
        $current_events = getCurrentAndUpcomingEvents(3);
        
        if(empty($current_events)): ?>
            <div class="no-events">
                <i class="fas fa-calendar-times"></i>
                <h3>No Current Events</h3>
                <p>Check back later for new events!</p>
                <?php if(isLoggedIn() && (isAdmin() || isStaff())): ?>
                    <a href="admin/add-event.php" class="btn btn-primary">Create First Event</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="events-tabs">
                <button class="tab-button active" data-tab="all">All Events</button>
                <button class="tab-button" data-tab="ongoing">Ongoing</button>
                <button class="tab-button" data-tab="upcoming">Upcoming</button>
            </div>
            
            <div class="events-grid featured-events">
                <?php foreach($current_events as $event): 
                    $date = date('M j, Y', strtotime($event['event_date']));
                    $time = date('g:i A', strtotime($event['event_time']));
                    $days_left = floor((strtotime($event['event_date']) - time()) / (60 * 60 * 24));
                    
                    // Determine event status and calculate end time
                    $event_datetime = strtotime($event['event_date'] . ' ' . $event['event_time']);
                    $is_ongoing = false;
                    
                    if ($event['duration'] && $event['duration_unit']) {
                        // Calculate end time based on duration and unit
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
                        // If no duration specified, consider event ongoing only on the day it starts
                        $is_ongoing = (date('Y-m-d') == $event['event_date'] && time() >= $event_datetime);
                    }
                    
                    $status_class = $is_ongoing ? 'ongoing' : 'upcoming';
                    $status_text = $is_ongoing ? 'Ongoing' : 'Upcoming';
                ?>
                <div class="event-card featured <?php echo $status_class; ?>" data-status="<?php echo $is_ongoing ? 'ongoing' : 'upcoming'; ?>">
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
                                ?>
                            "></i>
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
                        <p class="event-description"><?php echo substr($event['description'], 0, 80); ?>...</p>
                        
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
                        </div>
                        
                        <div class="event-footer">
                            <div class="event-participants">
                                <i class="fas fa-users"></i>
                                <span><?php echo $event['registered_count']; ?> registered</span>
                                <?php if($event['max_participants']): ?>
                                    <span class="max-participants">/ <?php echo $event['max_participants']; ?> max</span>
                                <?php endif; ?>
                            </div>
                            <br>
                            <div class="event-actions">
                                <?php if(isLoggedIn()): 
                                    $registration = isRegistered($event['id'], $_SESSION['user_id']);
                                    if($registration): ?>
                                        <button class="btn btn-success btn-sm" disabled>
                                            <i class="fas fa-check"></i> Registered
                                        </button>
                                    <?php else: ?>
                                        <form action="register-event.php" method="POST" class="inline-form">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" name="action" value="register" class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus"></i> Join
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php?redirect=event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-sign-in-alt"></i> Login to Join
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="section-cta">
                <a href="events.php" class="btn btn-outline btn-large">
                    <i class="fas fa-calendar-week"></i> View All Events
                </a>
                <?php if(isLoggedIn() && (isAdmin() || isStaff())): ?>
                    <a href="admin/add-event.php" class="btn btn-primary btn-large">
                        <i class="fas fa-plus"></i> Create New Event
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

    <!-- Quick Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="ongoing-count">0</h3>
                        <p class="stat-label">Events Happening Now</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="upcoming-count">0</h3>
                        <p class="stat-label">Upcoming Events</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" id="total-participants">0</h3>
                        <p class="stat-label">Total Participants</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section">
        <div class="container">
            <div class="section-header">
                <h2>Event Categories</h2>
                <p>Find events that match your interests</p>
            </div>
            <div class="categories-grid">
                <a href="events.php?category=academic" class="category-card academic">
                    <div class="category-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Academic</h3>
                    <p>Seminars, Workshops, Conferences</p>
                    <span class="category-link">Explore →</span>
                </a>
                <a href="events.php?category=sports" class="category-card sports">
                    <div class="category-icon">
                        <i class="fas fa-running"></i>
                    </div>
                    <h3>Sports</h3>
                    <p>Tournaments, Matches, Fitness</p>
                    <span class="category-link">Explore →</span>
                </a>
                <a href="events.php?category=cultural" class="category-card cultural">
                    <div class="category-icon">
                        <i class="fas fa-music"></i>
                    </div>
                    <h3>Cultural</h3>
                    <p>Music, Dance, Arts, Festivals</p>
                    <span class="category-link">Explore →</span>
                </a>
                <a href="events.php?category=workshop" class="category-card workshop">
                    <div class="category-icon">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3>Workshops</h3>
                    <p>Skills, Training, Development</p>
                    <span class="category-link">Explore →</span>
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Get Started?</h2>
                <p>Join our community and never miss another campus event</p>
                <div class="cta-actions">
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-primary btn-large">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                        <a href="events.php" class="btn btn-outline-white btn-large">
                            <i class="fas fa-calendar-alt"></i> Browse Events
                        </a>
                    <?php else: ?>
                        <a href="events.php" class="btn btn-primary btn-large">
                            <i class="fas fa-calendar-plus"></i> Browse Events
                        </a>
                        <?php if(isAdmin() || isStaff()): ?>
                            <a href="admin/add-event.php" class="btn btn-outline-white btn-large">
                                <i class="fas fa-plus"></i> Create Event
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
    // Tab functionality for events
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const eventCards = document.querySelectorAll('.event-card');
        
        // Count events for stats
        let ongoingCount = 0;
        let upcomingCount = 0;
        let totalParticipants = 0;
        
        eventCards.forEach(card => {
            const status = card.getAttribute('data-status');
            const participantText = card.querySelector('.event-participants span').textContent;
            const participantCount = parseInt(participantText) || 0;
            
            if (status === 'ongoing') {
                ongoingCount++;
            } else {
                upcomingCount++;
            }
            
            totalParticipants += participantCount;
        });
        
        // Update stats
        document.getElementById('ongoing-count').textContent = ongoingCount;
        document.getElementById('upcoming-count').textContent = upcomingCount;
        document.getElementById('total-participants').textContent = totalParticipants.toLocaleString();
        
        // Tab switching
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tab = this.getAttribute('data-tab');
                
                // Update active tab
                tabButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide events based on tab
                let visibleCount = 0;
                eventCards.forEach(card => {
                    if (tab === 'all') {
                        if (visibleCount < 3) {
                            card.style.display = 'block';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    } else {
                        const cardStatus = card.getAttribute('data-status');
                        if (cardStatus === tab && visibleCount < 3) {
                            card.style.display = 'block';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });
    });
</script>
</body>
</html>