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
$is_registered = isLoggedIn() ? isRegistered($event_id, $_SESSION['user_id']) : false;

$date = date('F j, Y', strtotime($event['event_date']));
$time = date('g:i A', strtotime($event['event_time']));
?>

<?php include 'includes/header.php'; ?>

    <div class="container">
        <a href="events.php" class="btn btn-outline back-btn">← Back to Events</a>
        
        <div class="event-detail">
            <div class="event-header">
                <span class="event-category <?php echo $event['category']; ?>"><?php echo ucfirst($event['category']); ?></span>
                <h1><?php echo htmlspecialchars($event['title']); ?></h1>
            </div>
            
            <div class="event-meta">
                <div class="meta-item">
                    <span class="meta-icon">📅</span>
                    <div>
                        <strong>Date & Time</strong>
                        <p><?php echo $date; ?> at <?php echo $time; ?></p>
                    </div>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">📍</span>
                    <div>
                        <strong>Venue</strong>
                        <p><?php echo htmlspecialchars($event['venue']); ?></p>
                    </div>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">👥</span>
                    <div>
                        <strong>Participants</strong>
                        <p><?php echo $registered_count; ?> registered</p>
                    </div>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">👤</span>
                    <div>
                        <strong>Organizer</strong>
                        <p><?php echo htmlspecialchars($event['organizer_name']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="event-description">
                <h3>About this Event</h3>
                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>
            
            <?php if(isLoggedIn()): ?>
                <div class="event-actions-detail">
                    <?php if($is_registered): ?>
                        <button class="btn btn-success btn-large" disabled>
                            ✅ <?php echo $is_registered['status'] === 'registered' ? 'Registered' : 'Interested'; ?>
                        </button>
                    <?php else: ?>
                        <form action="register-event.php" method="POST" class="inline-form">
                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                            <button type="submit" name="action" value="register" class="btn btn-primary btn-large">Register for Event</button>
                        </form>
                        <form action="register-event.php" method="POST" class="inline-form">
                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                            <button type="submit" name="action" value="interested" class="btn btn-outline btn-large">Mark as Interested</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="event-login-prompt">
                    <p>Please <a href="login.php">login</a> to register for this event.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>