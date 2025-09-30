<?php include 'includes/admin-header.php'; ?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    </div>

    <!-- Statistics -->
    <div class="admin-stats">
        <div class="admin-stat-card">
            <h3><?php echo $total_events; ?></h3>
            <p>Total Events</p>
        </div>
        <div class="admin-stat-card">
            <h3><?php echo $upcoming_events; ?></h3>
            <p>Upcoming Events</p>
        </div>
        <div class="admin-stat-card">
            <h3><?php echo $total_users; ?></h3>
            <p>Total Users</p>
        </div>
        <div class="admin-stat-card">
            <h3><?php echo $total_registrations; ?></h3>
            <p>Total Registrations</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="add-event.php" class="btn btn-primary">Add New Event</a>
            <a href="manage-events.php" class="btn btn-outline">Manage Events</a>
            <?php if(isAdmin()): ?>
                <a href="manage-users.php" class="btn btn-outline">Manage Users</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Events -->
    <div class="recent-events">
        <h2>Recent Events</h2>
        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Date</th>
                        <th>Registrations</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                        <td><?php echo $event['registrations']; ?></td>
                        <td class="admin-actions">
                            <a href="../event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-sm">View</a>
                            <a href="add-event.php?edit=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>