<?php
include 'includes/admin-header.php';

// Get events (admin sees all, staff sees only their events)
if (isAdmin()) {
    $events = $pdo->query("SELECT e.*, u.name as creator_name, COUNT(er.id) as registrations 
                          FROM events e 
                          LEFT JOIN users u ON e.created_by = u.id 
                          LEFT JOIN event_registrations er ON e.id = er.event_id 
                          GROUP BY e.id 
                          ORDER BY e.event_date DESC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT e.*, u.name as creator_name, COUNT(er.id) as registrations 
                          FROM events e 
                          LEFT JOIN users u ON e.created_by = u.id 
                          LEFT JOIN event_registrations er ON e.id = er.event_id 
                          WHERE e.created_by = ? 
                          GROUP BY e.id 
                          ORDER BY e.event_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Manage Events</h1>
        <a href="add-event.php" class="btn btn-primary">Add New Event</a>
    </div>

    <div class="admin-table">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Venue</th>
                    <th>Registrations</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($events)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No events found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                        <td><?php echo htmlspecialchars($event['venue']); ?></td>
                        <td><?php echo $event['registrations']; ?></td>
                        <td><?php echo htmlspecialchars($event['creator_name']); ?></td>
                        <td class="admin-actions">
                            <a href="../event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-sm">View</a>
                            <a href="add-event.php?edit=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <?php if(isAdmin() || $event['created_by'] == $_SESSION['user_id']): ?>
                                <a href="delete-event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>