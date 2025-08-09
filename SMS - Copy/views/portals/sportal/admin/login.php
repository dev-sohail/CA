<?php
// Start session and include configuration
include_once __DIR__ . '/../../../../config/config.php';

// Redirect if already logged in as admin
if (isset($_SESSION['logged_in'], $_SESSION['role']) && $_SESSION['logged_in'] === true && $_SESSION['role'] === 'admin') {
    header('Location: ' . APP_ADMIN_URL);
    exit;
}

// Handle form submission
if (isset($_POST['username'], $_POST['password'])) {
    $usernameInput = trim($_POST['username']);
    $passwordInput = trim($_POST['password']);

    // Query admin user
    $stmt = $conn->prepare("SELECT password FROM user_info WHERE username = ? AND role = 'admin'");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $usernameInput);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $storedPassword = $row['password'];

        // ⚠️ If using hashed passwords, replace the line below with password_verify()
        if ($passwordInput === $storedPassword) {
            $_SESSION['user'] = $usernameInput;
            $_SESSION['role'] = 'admin';
            $_SESSION['logged_in'] = true;

            header('Location: ' . APP_ADMIN_URL);
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Invalid username or not an admin.";
    }

    $stmt->close();
}
?>

<?php if (defined('APP_HEADER_FILE')) include_once APP_HEADER_FILE; ?>

<body>
    <main>
        <center>
            <section id="admin-login" class="sec-login py-5">
                <div class="form-div">
                    <h2>Admin Login</h2>
                    <?php if (isset($error)): ?>
                        <p style="color: red;"><?php echo $error; ?></p>
                    <?php endif; ?>
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-dark">Login as Admin</button>
                    </form>
                </div>
            </section>
        </center>
    </main>
</body>

<?php if (defined('APP_FOOTER_FILE')) include_once APP_FOOTER_FILE; ?>