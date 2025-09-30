<?php
include 'includes/admin-header.php';

// Only admin can access this page
requireAdmin();

// Handle user role updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    if ($stmt->execute([$new_role, $user_id])) {
        $_SESSION['message'] = "User role updated successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Failed to update user role!";
        $_SESSION['message_type'] = 'error';
    }
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot delete your own account!";
        $_SESSION['message_type'] = 'error';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $_SESSION['message'] = "User deleted successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to delete user!";
            $_SESSION['message_type'] = 'error';
        }
    }
    header("Location: manage-users.php");
    exit();
}

// Get all users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$total_users = count($users);
$admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$staff_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn();
$student_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Manage Users</h1>
        <p>Administrator access only - User management panel</p>
    </div>

    <!-- User Statistics -->
    <div class="admin-stats">
        <div class="admin-stat-card">
            <h3><?php echo $total_users; ?></h3>
            <p>Total Users</p>
        </div>
        <div class="admin-stat-card">
            <h3><?php echo $admin_count; ?></h3>
            <p>Administrators</p>
        </div>
        <div class="admin-stat-card">
            <h3><?php echo $staff_count; ?></h3>
            <p>Staff Members</p>
        </div>
        <div class="admin-stat-card">
            <h3><?php echo $student_count; ?></h3>
            <p>Students</p>
        </div>
    </div>

    <!-- Users Table -->
    <div class="admin-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($user['name']); ?>
                            <?php if($user['id'] == $_SESSION['user_id']): ?>
                                <span class="badge" style="background: #3498db;">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <form method="POST" class="role-form">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="role" onchange="this.form.submit()" <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                    <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                    <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <input type="hidden" name="update_role" value="1">
                            </form>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td class="admin-actions">
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <a href="manage-users.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">Delete</a>
                            <?php else: ?>
                                <span class="text-muted">Current User</span>
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