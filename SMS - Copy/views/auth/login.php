<?php
// Include config.php for database connection and session
include_once './../../config/config.php';

session_start();

// Check if the user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Redirect based on role
    if ($_SESSION['role'] === 'teacher') {
        header('Location: ' . APP_TPORTAL_URL);
        exit;
    } elseif ($_SESSION['role'] === 'student') {
        header('Location: ' . APP_STPORTAL_URL);
        exit;
    } elseif ($_SESSION['role'] === 'admin') {
        header('Location: ' . APP_ADMIN_URL);
        exit;
    } elseif ($_SESSION['role'] === 'parent') {
        header('Location: ' . APP_PPORTAL_URL);
        exit;
    } elseif ($_SESSION['role'] === 'staff') {
        header('Location: ' . APP_SPORTAL_URL);
        exit;
    } else {
        // Handle undefined roles or redirect to a default page
        header('Location: ' . APP_HOST_ROOT);
        exit;
    }
}

// Define role selection and reset logic
$selectedRole = isset($_POST['role']) ? $_POST['role'] : (isset($_SESSION['role']) ? $_SESSION['role'] : null);

if (isset($_POST['role'])) {
    $_SESSION['role'] = $selectedRole;
}

if (isset($_POST['reset'])) {
    $selectedRole = null;
    unset($_SESSION['role']);
}

// Handle login submission
if (isset($_POST['username'], $_POST['password'], $_POST['role'])) {
    $usernameInput = trim($_POST['username']);
    $passwordInput = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Log inputs for debugging (remove in production)
    // echo "<script>console.log('Inputs: Role: $role, Username: $usernameInput');</script>";
    
    // Query to fetch the password and role from the database
    $stmt = $conn->prepare("SELECT password FROM user_info WHERE username = ? AND role = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $usernameInput, $role); // Bind user inputs securely
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $storedPassword = $row['password'];
        
        // Compare the provided password with the stored password
        if ($passwordInput === $storedPassword) { // Replace with password_verify if hashed
            // Set session or any other logic needed
            $_SESSION['user'] = $usernameInput;
            $_SESSION['role'] = $role;
            $_SESSION['logged_in'] = true;


            // Redirect to respective portal
            switch ($role) {
                case 'student':
                    header('Location: '.APP_STPORTAL_URL);
                    break;
                case 'teacher':
                    header('Location: '. APP_TPORTAL_URL);
                    break;
                case 'parent':
                    header('Location: '.APP_PPORTAL_URL);
                    break;
                case 'staff':
                    header('Location: '.APP_SPORTAL_URL);
                    break;
                default:
                    echo "<script>alert('Invalid role specified.');</script>";
            }
            exit(); // Ensure no further code is executed after redirection
        } else {
            echo "<script>alert('Invalid password.');</script>";
        }
    } else {
        echo "<script>alert('Invalid username or role.');</script>";
    }

    $stmt->close();
}
// echo $_SESSION['role'];
// echo $_SESSION['user'];
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// var_dump($_POST);  // Check what values are being submitted via POST
// var_dump($_SESSION);
?>
<?php if (defined('APP_HEADER_FILE')) include_once APP_HEADER_FILE; ?>
<!-- <hr> -->

<body>
    <main>
        <center>
            <section id="login" class="sec-login py-5">
                <div class="form-div">
                <?php if (!$selectedRole): ?>
                        <!-- Role Selection -->
                        <p>Select your role to proceed:</p>
                        <form method="post" action="">
                            <button type="submit" name="role" value="student" class="btn btn-primary">Student Login</button>
                            <button type="submit" name="role" value="teacher" class="btn btn-secondary">Teacher Login</button>
                            <button type="submit" name="role" value="parent" class="btn btn-success">Parent Login</button>
                            <button type="submit" name="role" value="staff" class="btn btn-warning">Staff Login</button>
                        </form>
                    <?php else: ?>
                        <!-- Login Form -->
                        <p>You are logging in as a <strong><?php echo ucfirst($selectedRole); ?></strong>.</p>
                        <form method="post" action="">
                            <input type="hidden" name="role" value="<?php echo $selectedRole; ?>">
                            <div class="form-group">
                                <label for="username">Username:</label>
                                <input type="text" id="username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password:</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-success">Login</button>
                        </form>
                        <form method="post" action="" style="margin-top: 10px;">
                            <button type="submit" name="reset" class="btn btn-danger">Back to Role Selection</button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        </center>
    </main>
</body>

<!-- Footer -->
<?php if (defined('APP_FOOTER_FILE')) include_once APP_FOOTER_FILE; ?>