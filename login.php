<?php
include 'includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        
        $_SESSION['message'] = "Welcome back, " . $user['name'] . "!";
        $_SESSION['message_type'] = 'success';
        if(isset($_GET['redirect']) && !empty($_GET['redirect'])) header("Location: ".$_GET['redirect']);
        else
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<?php include 'includes/header.php'; ?>

    <div class="auth-container">
        <h2>Login to Your Account</h2>
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        
        <p>Don't have an account? <a href="register.php">Register here</a></p>
        
        <div class="demo-accounts">
            <h4>Demo Accounts:</h4>
            <p><strong>Admin:</strong> admin@rptumba.ac.rw / admin123</p>
            <p><strong>Student:</strong> student@rptumba.ac.rw / admin123</p>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>