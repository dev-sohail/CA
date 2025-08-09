<?php
    // Include config.php for database connection and session
    include_once __DIR__ . '/../../config/config.php';

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

    // Handle registration submission
    if (isset($_POST['first_name'], $_POST['last_name'], $_POST['password'], $_POST['role'], $_POST['email'], $_POST['phone'])) 
    {
            $firstName = trim($_POST['first_name']);
            $lastName = trim($_POST['last_name']);
            $emailInput = trim($_POST['email']);
            $passwordInput = $_POST['password'];
            $role = $_POST['role'];
            $phone = trim($_POST['phone']);

            // Generate a base username: first.last (lowercase, no spaces)
            $baseUsername = strtolower(preg_replace('/\s+/', '', $firstName)) . '.' . strtolower(preg_replace('/\s+/', '', $lastName));
            $username = $baseUsername;

            // Use a single query to find similar usernames and pick the next available one
            $stmt = $conn->prepare("SELECT username FROM user_info WHERE username LIKE CONCAT(?, '%')");
            if ($stmt) {
                $stmt->bind_param("s", $baseUsername);
                $stmt->execute();
                $result = $stmt->get_result();
                $existingUsernames = [];
                while ($row = $result->fetch_assoc()) {
                $existingUsernames[] = $row['username'];
                }
                $stmt->close();

                if (in_array($baseUsername, $existingUsernames)) {
                $suffix = 1;
                do {
                    $username = $baseUsername . $suffix;
                    $suffix++;
                } while (in_array($username, $existingUsernames));
                }
            }

            // Validate email format
            if (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
                echo "<script>alert('Invalid email format.');</script>";
            } elseif (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
                echo "<script>alert('Invalid phone number format.');</script>";
            } else {
                // Check if email already exists for the same role
                $stmt = $conn->prepare("SELECT user_id FROM user_info WHERE email = ? AND role = ?");
                if ($stmt) {
                    $stmt->bind_param("ss", $emailInput, $role);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows > 0) {
                        echo "<script>alert('You have already registered or logged in with this email.');</script>";
                    } else {
                        // Hash the password before storing
                        // $hashedPassword = password_hash($passwordInput, PASSWORD_DEFAULT);

                        // Insert the new user into the database, including the username and phone
                        $insertStmt = $conn->prepare("INSERT INTO user_info (first_name, last_name, username, email, password, role, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        if ($insertStmt) {
                            $insertStmt->bind_param("sssssss", $firstName, $lastName, $username, $emailInput, //$hashedPassword
                            $passwordInput, $role, $phone);

                            if ($insertStmt->execute()) {
                                echo "<script>alert('Registration successful! You can now log in.'); window.location.href='login.php';</script>";
                                unset($_SESSION['register_role']);
                                exit;
                            } else {
                                echo "<script>alert('Error during registration. Please try again.');</script>";
                            }
                            $insertStmt->close();
                        } else {
                            echo "<script>alert('Database error: Unable to prepare insert statement.');</script>";
                        }
                    }
                    $stmt->close();
                } else {
                    echo "<script>alert('Database error: Unable to prepare select statement.');</script>";
                }
            }
    }
?>

<?php include_once APP_HEADER_FILE; ?>
<hr>

<body>
    <main>
        <center>
            <section id="register" class="sec-register py-5">
                <div class="form-div">
                    <?php if (!$selectedRole): ?>
                        <!-- Role Selection -->
                        <p>Select your role to register:</p>
                        <form method="post" action="">
                            <button type="submit" name="role" value="student" class="btn btn-primary">Register as Student</button>
                            <button type="submit" name="role" value="teacher" class="btn btn-secondary">Register as Teacher</button>
                            <button type="submit" name="role" value="parent" class="btn btn-success">Register as Parent</button>
                            <button type="submit" name="role" value="staff" class="btn btn-warning">Register as Staff</button>
                        </form>
                    <?php else: ?>
                        <!-- Registration Form -->
                        <p>You are registering as a <strong><?php echo ucfirst($selectedRole); ?></strong>.</p>
                        <form method="post" action="">
                            <input type="hidden" name="role" value="<?php echo $selectedRole; ?>">
                            <div class="form-group">
                                <label for="first_name">First Name:</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name:</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number:</label>
                                <input type="tel" id="phone" name="phone" required pattern="^\+?[0-9]{7,15}$" placeholder="+1234567890">
                            </div>
                            <div class="form-group">
                                <label for="password">Password:</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-success">Register</button>
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
<?php include_once APP_FOOTER_FILE; ?>