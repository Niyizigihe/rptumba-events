<?php
include 'includes/config.php';
include 'includes/functions.php';

if (!isset($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$event_id = $_GET['id'];
$event = getEventById($event_id);

if (!$event) {
    header("Location: events.php");
    exit();
}

$registered_count = getRegistrationCount($event_id);
$interested_count = getInterestedCount($event_id);
$is_registered = isLoggedIn() ? isRegistered($event_id, $_SESSION['user_id']) : false;

$date = date('F j, Y', strtotime($event['event_date']));
$time = date('g:i A', strtotime($event['event_time']));
$event_datetime = strtotime($event['event_date'] . ' ' . $event['event_time']);
$days_left = floor((strtotime($event['event_date']) - time()) / (60 * 60 * 24));

// Determine event status
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
$status_text = $is_ongoing ? 'Ongoing Now' : 'Upcoming';
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <!-- Back Button -->
    <a href="events.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Events
    </a>
    
    <div class="event-detail-container">
        <!-- Event Header Section -->
        <div class="event-header-section">
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
            
            <div class="event-title-section">
                <h1 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h1>
                <p class="event-organizer">
                    <i class="fas fa-user"></i>
                    Organized by <?php echo htmlspecialchars($event['organizer_name']); ?>
                </p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="event-content-grid">
            <!-- Left Column - Event Details -->
            <div class="event-details-column">
                <!-- Event Image -->
                <div class="event-image-large">
                    <img src="<?php echo $event['image_path']; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                    <?php if($is_ongoing): ?>
                        <div class="live-overlay">
                            <span class="live-badge">
                                <i class="fas fa-circle"></i> LIVE NOW
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Event Description -->
                <div class="event-description-card">
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        About this Event
                    </h3>
                    <div class="description-content">
                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                    </div>
                </div>

                <!-- Additional Event Info -->
                <div class="event-info-grid">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="info-content">
                            <h4>Date & Time</h4>
                            <p><?php echo $date; ?></p>
                            <span class="info-time"><?php echo $time; ?></span>
                            <?php if($event['duration'] && $event['duration_unit']): ?>
                                <span class="info-duration">• <?php echo $event['duration'] . ' ' . $event['duration_unit']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <h4>Venue</h4>
                            <p><?php echo htmlspecialchars($event['venue']); ?></p>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="info-content">
                            <h4>Participants</h4>
                            <p><?php echo $registered_count; ?> Going</p>
                            <p><?php echo $interested_count; ?> Interested</p>
                            <?php if($event['max_participants']): ?>
                                <span class="max-participants">Max: <?php echo $event['max_participants']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if(isLoggedIn() && isStaff()): ?>
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="info-content">
                            <h4>Staff Actions</h4>
                            <div class="staff-actions">
                                <a href="admin/add-event.php?edit=<?php echo $event['id']; ?>" class="btn btn-outline btn-sm">
                                    <i class="fas fa-edit"></i> Edit Event
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Registration & Actions -->
            <div class="event-actions-column">
                <div class="actions-card">
                    <div class="actions-header">
                        <h3>
                            <i class="fas fa-ticket-alt"></i>
                            Event Registration
                        </h3>
                        <?php if($event['max_participants'] && $registered_count >= $event['max_participants']): ?>
                            <span class="full-badge">FULL</span>
                        <?php endif; ?>
                    </div>

                    <div class="registration-status">
                        <?php if(isLoggedIn() && isStudent()): ?>
                            <?php if($is_registered): ?>
                                <?php if($is_registered['status'] === 'registered'): ?>
                                    <div class="registered-status">
                                        <div class="status-success">
                                            <i class="fas fa-check-circle"></i>
                                            <div>
                                                <h4>You're Registered</h4>
                                                <p>You'll receive event reminders and updates</p>
                                            </div>
                                        </div>
                                        <button class="btn btn-danger btn-block cancel-btn-detail" 
                                                data-event-id="<?php echo $event['id']; ?>"
                                                data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                            <i class="fas fa-times"></i> Cancel Registration
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <!-- User is interested -->
                                    <div class="interested-status">
                                        <div class="status-interested">
                                            <i class="fas fa-star"></i>
                                            <div>
                                                <h4>You're Interested</h4>
                                                <p>You've shown interest in this event</p>
                                            </div>
                                        </div>
                                        <?php if(!($event['max_participants'] && $registered_count >= $event['max_participants'])): ?>
                                            <button class="btn btn-primary btn-large btn-block join-btn-detail" 
                                                    data-event-id="<?php echo $event['id']; ?>"
                                                    data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                                <i class="fas fa-plus"></i> Join Event Now
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-outline btn-block cancel-btn-detail" 
                                                data-event-id="<?php echo $event['id']; ?>"
                                                data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                            <i class="fas fa-times"></i> Remove Interest
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- No registration -->
                                <?php if($event['max_participants'] && $registered_count >= $event['max_participants']): ?>
                                    <div class="full-event">
                                        <i class="fas fa-times-circle"></i>
                                        <h4>Event Full</h4>
                                        <p>This event has reached maximum capacity</p>
                                    </div>
                                <?php else: ?>
                                    <div class="registration-options">
                                        <button class="btn btn-primary btn-large btn-block join-btn-detail" 
                                                data-event-id="<?php echo $event['id']; ?>"
                                                data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                            <i class="fas fa-plus"></i> Register for Event
                                        </button>
                                        <button class="btn btn-outline btn-block interested-btn-detail" 
                                                data-event-id="<?php echo $event['id']; ?>"
                                                data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                            <i class="fas fa-star"></i> Mark as Interested
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif(!isLoggedIn()): ?>
                            <div class="login-prompt">
                                <i class="fas fa-sign-in-alt"></i>
                                <h4>Login to Register</h4>
                                <p>Please login to register for this event</p>
                                <a href="login.php?redirect=event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-block">
                                    <i class="fas fa-sign-in-alt"></i> Login Now
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Stats -->
                    <div class="quick-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $registered_count; ?></span>
                            <span class="stat-label">Going</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $interested_count; ?></span>
                            <span class="stat-label">Interested</span>
                        </div>
                        <?php if($event['max_participants']): ?>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo max(0, $event['max_participants'] - $registered_count); ?></span>
                            <span class="stat-label">Spots Left</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Share Event -->
                <div class="share-card">
                    <h4>
                        <i class="fas fa-share-alt"></i>
                        Share this Event
                    </h4>
                    <div class="share-buttons">
                        <button class="share-btn facebook" onclick="shareEvent('facebook')">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button class="share-btn twitter" onclick="shareEvent('twitter')">
                            <i class="fab fa-twitter"></i>
                        </button>
                        <button class="share-btn linkedin" onclick="shareEvent('linkedin')">
                            <i class="fab fa-linkedin-in"></i>
                        </button>
                        <button class="share-btn copy" onclick="copyEventLink()">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Confirmation Modals for Event Details -->
<div id="joinModalDetail" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Join Event</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to join "<span id="joinEventTitleDetail"></span>"?</p>
            <p class="modal-note">
                ✅ You'll be registered as a participant<br>
                ✅ You'll receive event reminders<br>
                ✅ This counts toward event capacity
            </p>
        </div>
        <div class="modal-footer">
            <form id="joinFormDetail" action="register-event.php" method="POST">
                <input type="hidden" name="event_id" id="joinEventIdDetail">
                <input type="hidden" name="action" value="register">
                <button type="button" class="btn btn-outline cancel-modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Yes, Join Event</button>
            </form>
        </div>
    </div>
</div>

<div id="interestedModalDetail" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Mark as Interested</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to mark "<span id="interestedEventTitleDetail"></span>" as interested?</p>
            <p class="modal-note">
                ✅ You can still join later<br>
                ✅ Shows you're considering this event<br>
                ✅ Doesn't count toward event capacity
            </p>
        </div>
        <div class="modal-footer">
            <form id="interestedFormDetail" action="register-event.php" method="POST">
                <input type="hidden" name="event_id" id="interestedEventIdDetail">
                <input type="hidden" name="action" value="interested">
                <button type="button" class="btn btn-outline cancel-modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Yes, Mark as Interested</button>
            </form>
        </div>
    </div>
</div>

<div id="cancelModalDetail" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Cancel Registration</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to cancel your registration for "<span id="cancelEventTitleDetail"></span>"?</p>
            <p class="modal-note">This will remove you from the event participants list.</p>
        </div>
        <div class="modal-footer">
            <form id="cancelFormDetail" action="register-event.php" method="POST">
                <input type="hidden" name="event_id" id="cancelEventIdDetail">
                <input type="hidden" name="action" value="cancel">
                <button type="button" class="btn btn-outline cancel-modal">No, Keep It</button>
                <button type="submit" class="btn btn-danger">Yes, Cancel</button>
            </form>
        </div>
    </div>
</div>

<style>
    
/* Event Details Page Styles */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
    margin-bottom: 2rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.back-btn:hover {
    background: #f8f9fa;
    transform: translateX(-5px);
}

.event-detail-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

/* Event Header Section */
.event-header-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2rem;
    position: relative;
}

.event-badge {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.event-title-section {
    max-width: 800px;
}

.event-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    line-height: 1.2;
}

.event-organizer {
    font-size: 1.1rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Content Grid */
.event-content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    padding: 2rem;
}

/* Left Column */
.event-image-large {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 2rem;
}

.event-image-large img {
    width: 100%;
    height: 400px;
    object-fit: cover;
}

.live-overlay {
    position: absolute;
    top: 1rem;
    right: 1rem;
}

.live-badge {
    background: #e74c3c;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: pulse 2s infinite;
}

.event-description-card {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.event-description-card h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.description-content {
    line-height: 1.8;
    color: #555;
}

.event-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.info-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.info-icon {
    width: 50px;
    height: 50px;
    background: #3498db;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.info-content h4 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.info-content p {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.info-time, .info-duration, .max-participants {
    color: #7f8c8d;
    font-size: 0.9rem;
}

/* Right Column */
.actions-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.actions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.actions-header h3 {
    color: #2c3e50;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.full-badge {
    background: #e74c3c;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 700;
}

.registration-status {
    margin-bottom: 1.5rem;
}

.registered-status, .full-event, .login-prompt {
    text-align: center;
    padding: 1rem 0;
}

.status-success {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    text-align: left;
}

.status-success i {
    color: #27ae60;
    font-size: 2rem;
}

.status-success h4 {
    margin: 0 0 0.25rem 0;
    color: #2c3e50;
}

.status-success p {
    margin: 0;
    color: #7f8c8d;
    font-size: 0.9rem;
}

.full-event i, .login-prompt i {
    color: #e74c3c;
    font-size: 3rem;
    margin-bottom: 1rem;
}

.full-event h4, .login-prompt h4 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.full-event p, .login-prompt p {
    color: #7f8c8d;
    margin-bottom: 1rem;
}

.registration-form {
    margin-bottom: 0.5rem;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #3498db;
}

.stat-label {
    font-size: 0.8rem;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Share Card */
.share-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
}

.share-card h4 {
    color: #2c3e50;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.share-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.share-btn {
    width: 45px;
    height: 45px;
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.share-btn:hover {
    transform: translateY(-2px);
}

.share-btn.facebook { background: #3b5998; }
.share-btn.twitter { background: #1da1f2; }
.share-btn.linkedin { background: #0077b5; }
.share-btn.copy { background: #6c757d; }

/* Staff Actions */
.staff-actions {
    margin-top: 0.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .event-content-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        padding: 1.5rem;
    }
    
    .event-header-section {
        padding: 2rem 1.5rem;
    }
    
    .event-title {
        font-size: 2rem;
    }
    
    .event-image-large img {
        height: 250px;
    }
    
    .event-info-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-stats {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .event-badge {
        flex-wrap: wrap;
    }
}
/* Add styles for the new status displays */
.status-interested {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    text-align: left;
}

.status-interested i {
    color: #f39c12;
    font-size: 2rem;
}

.status-interested h4 {
    margin: 0 0 0.25rem 0;
    color: #2c3e50;
}

.status-interested p {
    margin: 0;
    color: #7f8c8d;
    font-size: 0.9rem;
}

.interested-status {
    text-align: center;
}

.registration-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

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
</style>

<script>
// Modal functionality for event details page
document.addEventListener('DOMContentLoaded', function() {
    const modals = {
        join: document.getElementById('joinModalDetail'),
        interested: document.getElementById('interestedModalDetail'),
        cancel: document.getElementById('cancelModalDetail')
    };
    
    const closeButtons = document.querySelectorAll('.close, .cancel-modal');
    
    // Join button handlers for event details
    document.querySelectorAll('.join-btn-detail').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            const eventTitle = this.getAttribute('data-event-title');
            
            document.getElementById('joinEventIdDetail').value = eventId;
            document.getElementById('joinEventTitleDetail').textContent = eventTitle;
            
            modals.join.style.display = 'block';
        });
    });
    
    // Interested button handlers for event details
    document.querySelectorAll('.interested-btn-detail').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            const eventTitle = this.getAttribute('data-event-title');
            
            document.getElementById('interestedEventIdDetail').value = eventId;
            document.getElementById('interestedEventTitleDetail').textContent = eventTitle;
            
            modals.interested.style.display = 'block';
        });
    });
    
    // Cancel button handlers for event details
    document.querySelectorAll('.cancel-btn-detail').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            const eventTitle = this.getAttribute('data-event-title');
            
            document.getElementById('cancelEventIdDetail').value = eventId;
            document.getElementById('cancelEventTitleDetail').textContent = eventTitle;
            
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

// Share functions (keep your existing ones)
function shareEvent(platform) {
    const eventTitle = '<?php echo addslashes($event['title']); ?>';
    const eventUrl = window.location.href;
    
    let shareUrl = '';
    
    switch(platform) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(eventUrl)}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(eventTitle)}&url=${encodeURIComponent(eventUrl)}`;
            break;
        case 'linkedin':
            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(eventUrl)}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }
}

function copyEventLink() {
    const eventUrl = window.location.href;
    navigator.clipboard.writeText(eventUrl).then(() => {
        alert('Event link copied to clipboard!');
    });
}
</script>


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