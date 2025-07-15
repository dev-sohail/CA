<?php
define('APP_ENV', 'development'); // development, production
error_log(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
// Debug settings
define('DEBUG', APP_ENV === 'development');

// Error reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
// Secure session initialization
define('SESSION_NAME', 'sms_session');

$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 0, // Session lasts until browser closes
    'path' => $cookieParams['path'] ?? '/',
    'domain' => $cookieParams['domain'] ?? '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// die('helkjqw224321ed2er2jl');

// Set default timezone
date_default_timezone_set('UTC');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');

// One-time pincode
define('PINCODE_EXPIRY_SECONDS', 90 * 24 * 60 * 60); // 3 months

function isPincodeValid()
{
    return isset($_SESSION['pincode'], $_SESSION['pincode_expiry']) &&
        time() < $_SESSION['pincode_expiry'];
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Connect to DB
$dbname = "calab";
$db_error = false;
try {
    $db = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    $db_error = true;
    $db = null;
}

// Handle all AJAX POST operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Handle pincode submission separately
    if (isset($data['type']) && $data['type'] === 'submit_pincode') {
        header('Content-Type: application/json');
        $correctPincode = '0013';

        if (isset($data['pincode']) && $data['pincode'] === $correctPincode) {
            $_SESSION['pincode'] = $data['pincode'];
            $_SESSION['pincode_expiry'] = time() + PINCODE_EXPIRY_SECONDS;

            setcookie('pincode', $data['pincode'], time() + PINCODE_EXPIRY_SECONDS, '/', '', false, true);

            echo json_encode(['status' => 'success']);
            exit;
        } else {
            echo json_encode(['error' => 'Invalid pincode']);
            exit;
        }
    }
}
// Generate CSRF token if needed
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle other AJAX POST actions with CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If this was pincode, already handled above
    if (isset($data['type']) && $data['type'] === 'submit_pincode') {
        // Already exited
    }

    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);

    // CSRF token validation for other requests
    if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }

    if ($db_error) {
        echo json_encode(['error' => 'DB unavailable']);
        exit;
    }

    try {
        switch ($data['type'] ?? null) {
            case 'add_note':
                if (empty($data['content'])) throw new Exception('Missing note content');
                $stmt = $db->prepare("INSERT INTO notes (content) VALUES (?)");
                $stmt->execute([$data['content']]);
                echo json_encode(['status' => 'success', 'id' => $db->lastInsertId()]);
                break;

            case 'delete_note':
                if (empty($data['id'])) throw new Exception('Missing note id');
                $stmt = $db->prepare("DELETE FROM notes WHERE id = ?");
                $stmt->execute([$data['id']]);
                echo json_encode(['status' => 'deleted']);
                break;

            case 'add_task':
                if (empty($data['title']) || empty($data['date']) || empty($data['details'])) {
                    throw new Exception('Missing task fields');
                }
                $stmt = $db->prepare("INSERT INTO tasks (title, link, date, details) VALUES (?,?,?,?)");
                $stmt->execute([
                    $data['title'],
                    $data['link'] ?? '',
                    $data['date'],
                    $data['details']
                ]);
                echo json_encode(['status' => 'success', 'id' => $db->lastInsertId()]);
                break;

            case 'delete_task':
                if (empty($data['id'])) throw new Exception('Missing task id');
                $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
                $stmt->execute([$data['id']]);
                echo json_encode(['status' => 'deleted']);
                break;

            case 'toggle_task':
                if (!isset($data['id'], $data['done'])) throw new Exception('Missing toggle params');
                $stmt = $db->prepare("UPDATE tasks SET done = ? WHERE id = ?");
                $stmt->execute([$data['done'] ? 1 : 0, $data['id']]);
                echo json_encode(['status' => 'updated']);
                break;

            case 'add_document':
                if (empty($data['title']) || empty($data['url'])) {
                    throw new Exception('Missing document fields');
                }
                $stmt = $db->prepare("INSERT INTO documents (title, url) VALUES (?, ?)");
                $stmt->execute([$data['title'], $data['url']]);
                echo json_encode(['status' => 'success', 'id' => $db->lastInsertId()]);
                break;

            case 'delete_document':
                if (empty($data['id'])) throw new Exception('Missing document id');
                $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
                $stmt->execute([$data['id']]);
                echo json_encode(['status' => 'deleted']);
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid request']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// --- LOAD DATA ---
if (!$db_error) {
    try {
        $notes = $db->query("SELECT * FROM notes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        $tasks = $db->query("SELECT * FROM tasks ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        $documents = $db->query("SELECT * FROM documents ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $db_error = true;
        $notes = $tasks = $documents = [];
    }
} else {
    $notes = $tasks = $documents = [];
}

// Page Title
$title = "CA LAB";
// exit('ei');
?>

<?php
// Check pincode before allowing data load
if (!isPincodeValid()) 
    {
        // exit('exi');
    echo <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <title>Secure Access - Enter Pincode</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
                <style>
                    body, html {
                        height: 100%;
                        background-color: #f8f9fa;
                    }
                    .login-container {
                        max-width: 400px;
                        margin: auto;
                        padding: 2rem;
                        background: white;
                        border-radius: 0.5rem;
                        box-shadow: 0 0 15px rgba(0,0,0,0.1);
                        margin-top: 10vh;
                    }
                    #msg {
                        min-height: 1.5rem;
                    }
                </style>
            </head>
            <body>
                <div class="login-container shadow-sm">
                    <h3 class="mb-4 text-center">Please Enter Pincode</h3>
                    <form id="pincodeForm" onsubmit="return false;">
                        <div class="mb-3">
                            <label for="pincodeInput" class="form-label visually-hidden">Pincode</label>
                            <input
                                type="password"
                                class="form-control form-control-lg"
                                id="pincodeInput"
                                placeholder="Enter your pincode"
                                autofocus
                                autocomplete="one-time-code"
                                required
                            />
                        </div>
                        <div id="msg" class="text-danger mb-3"></div>
                        <button type="submit" class="btn btn-primary w-100" onclick="submitPincode()">
                            Submit
                        </button>
                    </form>
                </div>

                <script>
                async function submitPincode() {
                    const input = document.getElementById('pincodeInput');
                    const msg = document.getElementById('msg');
                    const pincode = input.value.trim();
                    msg.textContent = '';
                    if (!pincode) {
                        msg.textContent = 'Please enter your pincode.';
                        input.focus();
                        return;
                    }

                    try {
                        const res = await fetch('', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ type: 'submit_pincode', pincode })
                        });

                        const data = await res.json();

                        if (data.status === 'success') {
                            location.reload();
                        } else {
                            msg.textContent = data.error || 'Invalid pincode.';
                            input.select();
                        }
                    } catch (error) {
                        msg.textContent = 'Network error. Please try again.';
                    }
                }

                // Autofill pincode from cookie (optional)
                const cookieMatch = document.cookie.match(/(?:^|;\s*)pincode=([^;]*)/);
                if (cookieMatch) {
                    document.getElementById('pincodeInput').value = decodeURIComponent(cookieMatch[1]);
                }
                </script>
            </body>
            </html>
            HTML;
    // exit('exi');
}


?>
<?php
// Check pincode before allowing data load
if (isPincodeValid()) {
    // exit('exi');
?>
<head>
    <script>
        window.dbAvailable = <?php echo $db_error ? 'false' : 'true'; ?>;
        window.csrfToken = <?php echo json_encode($_SESSION['csrf_token']); ?>;
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="https://i.ibb.co/My0DxJ9B/logo.png" type="image/x-icon">
    <title><?= htmlspecialchars($title); ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />

    <!-- CKEditor -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>


    <style>
        :root {
            --primary: #007bff;
            --dark: #343a40;
            --light: #f8f9fa;
            --white: #ffffff;
            --accent: #4caf50;
            --danger: #e53935;
            --info: #03a9f4;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light);
            color: #222;
            transition: background 0.3s, color 0.3s;
        }

        body.dark-mode {
            background-color: #121212;
            color: #eee;
        }

        header {
            background-color: var(--dark);
            color: var(--white);
            padding: 20px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        nav {
            background: #222;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 25px;
            padding: 15px;
        }

        nav a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: background-color 0.3s ease, color 0.3s ease;
            margin: 0 10px;
        }

        nav a:hover {
            background: var(--primary);
        }

        nav a.active,
        .nav-link.active {
            background-color: var(--primary);
            color: white;
        }

        nav a.nav-link.active {
            background-color: white !important;
            color: black !important;
        }

        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                align-items: center;
            }
        }

        section {
            display: none;
            padding: 30px;
            animation: fadeIn 0.5s ease-in-out;
        }

        section.active {
            display: block;
            background-color: #fdfdfd;
            border-radius: 10px;
            margin: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        body.dark-mode section.active {
            background-color: #1a1a1a;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        textarea,
        input[type="text"],
        input[type="date"],
        input[type="url"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            background: transparent;
            color: inherit;
        }

        button {
            padding: 12px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #0056b3;
        }

        .note,
        .task,
        .card {
            background-color: inherit;
            border: 1px solid #ccc;
            padding: 15px;
            margin: 12px 0;
            border-left: 5px solid var(--dark);
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: background 0.3s;
        }

        .btn#quickAdd {
            position: fixed;
            /* bottom: 20px; */
            /* left: 20px; */
            z-index: 1100;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .quick-add-widget {
            position: fixed;
            bottom: 80px;
            left: 20px;
            width: 300px;
            height: 400px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 1050;
        }

        .quick-add-widget.show {
            opacity: 1;
            pointer-events: auto;
        }

        .quick-add-widget .card-header {
            background: #0d6efd;
            color: white;
            padding: 0.5rem;
            text-align: center;
            font-weight: bold;
        }

        .quick-add-widget .card-body {
            flex: 1;
            padding: 0.5rem;
            overflow-y: auto;
        }

        .quick-add-widget .card-footer {
            padding: 0.5rem;
            border-top: 1px solid #ddd;
        }

        body.dark-mode .note,
        body.dark-mode .task,
        body.dark-mode .card {
            background-color: #1e1e1e;
            border-left-color: var(--primary);
            border-color: #333;
        }

        body.dark-mode .note-summary,
        body.dark-mode .card-body {
            color: white;
        }

        .progress-container {
            margin: 30px auto;
            background: #e9ecef;
            border-radius: 30px;
            overflow: hidden;
            height: 30px;
            max-width: 600px;
        }

        .progress-bar {
            height: 100%;
            background-color: var(--accent);
            width: 0%;
            transition: width 0.5s ease-in-out;
            text-align: center;
            color: white;
            font-weight: bold;
            line-height: 30px;
        }

        .progress-text {
            text-align: center;
            font-size: 17px;
            margin-top: 10px;
        }

        .quick-add-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: var(--primary);
            border: none;
            color: white;
            font-size: 28px;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .quick-add-btn:hover {
            background: #0056b3;
            transform: scale(1.1);
        }

        .quick-add-btn::after {
            content: attr(title);
            position: absolute;
            bottom: 70px;
            right: 0;
            background: #333;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            white-space: nowrap;
            opacity: 0;
            transform: translateY(5px);
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .quick-add-btn:hover::after {
            opacity: 1;
            transform: translateY(0);
        }

        /* Toggle switch */
        .toggle-switch {
            position: absolute;
            top: 15px;
            right: 20px;
        }

        .toggle-switch input[type="checkbox"] {
            display: none;
        }

        .toggle-slider {
            width: 40px;
            height: 20px;
            background: #ccc;
            border-radius: 20px;
            display: inline-block;
            position: relative;
            cursor: pointer;
        }

        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            top: 1px;
            left: 1px;
            transition: 0.3s;
        }

        input[type="checkbox"]:checked+.toggle-slider::before {
            transform: translateX(20px);
        }

        input[type="checkbox"]:checked+.toggle-slider {
            background: var(--primary);
        }
    </style>
</head>

<body>
    <div class="form-check form-switch position-absolute top-0 end-0 m-3">
        <input class="form-check-input" type="checkbox" id="darkModeToggle" />
        <label class="form-check-label" for="darkModeToggle">Dark Mode</label>
    </div>

    <header>
        <h1>CyberAfridi Lab</h1>
    </header>

    <nav class="nav justify-content-center bg-dark text-white text-center py-4 mb-3">
        <a id="notes_nav" href="#" class="nav-link text-white bg-primary rounded mx-1 active" data-target="notes">Notes</a>
        <a id="tasks_nav" href="#" class="nav-link text-white bg-primary rounded mx-1" data-target="tasks">Tasks</a>
        <a id="docs_nav" href="#" class="nav-link text-white bg-primary rounded mx-1" data-target="documents">Documents</a>
        <a id="python_nav" href="#" class="nav-link text-white bg-primary rounded mx-1" data-target="python">My Assisstant</a>
    </nav>

    <div class="container">
        <section id="notes" class="d-none">
            <h2>Notes</h2>
            <div id="notesList"></div>
            <textarea id="noteContent"></textarea>
            <button onclick="addNote()">Add Note</button>
        </section>

        <section id="tasks" class="d-none">
            <input id="taskTitle" type="text" class="form-control mb-2" placeholder="Task title" />
            <input id="taskLink" type="url" class="form-control mb-2" placeholder="Optional: Task Link (https://...)" />
            <input id="taskDate" type="date" class="form-control mb-2" />
            <textarea id="taskDetails" class="form-control mb-2" placeholder="Task details..."></textarea>
            <button class="btn btn-primary mb-3" onclick="addTask()">Add Task</button>

            <div class="progress mb-3">
                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>

            <div id="tasksList">
                <h2>My Tasks</h2>
                <?php if ($tasks): ?>
                    <?php foreach ($tasks as $t): ?>
                        <div class="card mb-2">
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox"
                                        onchange="toggleTask(<?= $t['id']; ?>, this.checked)"
                                        <?= $t['done'] ? 'checked' : '' ?>>
                                    <label class="form-check-label">
                                        <strong><?= htmlspecialchars($t['title']); ?></strong> [<?= htmlspecialchars($t['date']); ?>]
                                    </label>
                                </div>
                                <?php if ($t['link']): ?>
                                    <a href="<?= htmlspecialchars($t['link']); ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($t['link']); ?></a>
                                <?php endif; ?>
                                <div class="mt-2 mb-2"><?= nl2br(htmlspecialchars($t['details'])); ?></div>
                                <button class="btn btn-sm btn-danger p-1 ms-2" onclick="deleteTask(<?= $t['id']; ?>, this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-danger">No tasks available.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="documents" class="d-none">
            <input id="docTitle" type="text" class="form-control mb-2" placeholder="Title">
            <input id="docURL" type="url" class="form-control mb-2" placeholder="URL">
            <button class="btn btn-primary mb-3" onclick="addDocument()">Add Document</button>
            <div id="documentsList">
                <h2>My Documents</h2>
                <?php if ($documents): ?>
                    <?php foreach ($documents as $d): ?>
                        <div class="card mb-2">
                            <div class="card-body">
                                <a href="<?= htmlspecialchars($d['url']); ?>" target="_blank" rel="noopener noreferrer" class="d-block mb-2">
                                    <?= htmlspecialchars($d['title']); ?>
                                </a>
                                <button class="btn btn-sm btn-danger p-1" onclick="deleteDocument(<?= $d['id']; ?>, this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-danger">No docs available.</p>
                    <?php endif; ?>
                        </div>
        </section>

        <section id="python" class="d-none">
            <?php
            $pythonBin = 'C:/wamp64/www/me/exam/venv/Scripts/python.exe';
            $scriptPath = 'C:\wamp64\www\me\exam\test.py';
            // Prepare the command
            // $command = escapeshellcmd($pythonBin . ' ' . $scriptPath);
            $command = escapeshellcmd("\"$pythonBin\" \"$scriptPath\"");

            // Prepare environment variables
            $env = ['MY_PYTHON_ENV' => 'This is passed from PHP',];

            // Create the descriptors
            $descriptorspec = [
                0 => ["pipe", "r"], // stdin
                1 => ["pipe", "w"], // stdout
                2 => ["pipe", "w"]  // stderr
            ];

            // Start the process
            $process = proc_open($command, $descriptorspec, $pipes, null, $env);


            if (is_resource($process)) {
                // If you want to send data via stdin
                // fwrite($pipes[0], "Hello Python script!\n");
                // fclose($pipes[0]);

                // Get stdout
                $output = stream_get_contents($pipes[1]);
                fclose($pipes[1]);

                // Get stderr
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[2]);

                // Close the process
                $return_value = proc_close($process);

                // Output results
                echo "<pre>";
                echo "STDOUT:\n" . htmlspecialchars($output) . "\n";
                if (!empty($errors)) {
                    echo "STDERR:\n" . htmlspecialchars($errors) . "\n";
                    echo "Return code: $return_value";
                }
                echo "</pre>";
            } else {
                echo "Failed to start the process.";
            }

            // $output = shell_exec('python3 ./test.py');
            // $output = shell_exec('C:/Users/devso/AppData/Local/Programs/Python/Python313/python.exe c:/wamp64/www/me/exam/test.py');

            // echo $output;
            ?>

        </section>
    </div>

    <button id="quickAdd" class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-4"
        title="Quick Add" style="width:60px; height:60px; font-size:24px;">
        <i class="bi bi-plus"></i>
    </button>

    <!-- Note Detail Modal -->
    <div class="modal fade" id="noteDetailModal" tabindex="-1" aria-labelledby="noteDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable"> <!-- scrollable if content is long -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="noteDetailModalLabel">Note Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="noteDetailContent" style="white-space: pre-wrap;">
                    <!-- Note content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add Note Modal -->
    <!-- <div class="modal fade" id="quickAddNoteModal" tabindex="-1" aria-labelledby="quickAddNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickAddNoteModalLabel">Quick Add Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea id="quickNoteContent" class="form-control" rows="4" placeholder="Write your note..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="quickAddNote()">Add Note</button>
                </div>
            </div>
        </div>
    </div> -->
    <!-- Quick Add Note Widget -->
    <div id="quickAddNoteModal" class="quick-add-widget" style="display: none;" tabindex="-1" aria-labelledby="quickAddNoteModalLabel" aria-hidden="true">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <!-- <span>Quick Add Note</span> -->
                <h5 class="modal-title" id="quickAddNoteModalLabel">Quick Add Note</h5>
                <!-- <button id="quickAddClose" class="btn btn-sm btn-secondary">&times;</button> -->
                <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
            </div>
            <div class="card-body">
                <textarea id="quickNoteContent" class="form-control" rows="4" placeholder="Write your note..."></textarea>
            </div>
            <div class="card-footer text-end">
                <!-- <button id="quickAddCancel" class="btn btn-secondary me-2">Cancel</button> -->
                <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button> -->
                <!-- <button id="quickAddSave" class="btn btn-primary">Add Note</button> -->
                <button type="button" class="btn btn-primary" onclick="quickAddNote()">Add Note</button>

            </div>
        </div>
    </div>


    <!-- Bootstrap Bundle JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.7/js/bootstrap.bundle.min.js"></script>

    <script>
        const notesData = <?= json_encode(array_column($notes, 'content', 'id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        function showNoteDetails(id) {
            const content = notesData[id];
            if (!content) return alert('Note content not found.');

            const modalBody = document.getElementById('noteDetailContent');
            // Use textContent to avoid HTML injection, but preserve formatting
            // modalBody.textContent = content;
            modalBody.innerHTML = content;

            // Show the modal using Bootstrap's JS API
            const noteModal = new bootstrap.Modal(document.getElementById('noteDetailModal'));
            noteModal.show();

        }

        let noteEditor;
        ClassicEditor
            .create(document.querySelector('#noteContent'))
            .then(editor => {
                noteEditor = editor;
            })
            .catch(error => {
                console.error(error);
            });

        // Ajax helper
        function post(data, cb) {
            if (!window.dbAvailable) {
                cb({
                    error: 'DB unavailable'
                });
                return;
            }
            // Add CSRF token to data before sending
            data.csrf_token = window.csrfToken;
            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(cb)
                .catch(() => cb({
                    error: 'Network error'
                }));
        }

        // Add Note
        function addNote() {
            const content = noteEditor.getData().replace(/<[^>]+>/g, '').trim();
            if (!content) return;
            post({
                type: 'add_note',
                content
            }, res => {
                if (res.status === 'success') {
                    renderNote(content, res.id);
                    noteEditor.setData(''); // Clear editor
                } else if (res.error) {
                    alert('Error: ' + res.error);
                } else {
                    alert(res.error || 'Unknown error');
                }
            });
        }

        // function renderNote(content, id) {
        //     const note = document.createElement('div');
        //     note.className = 'card mb-2';
        //     note.innerHTML = `
        //         <div class="card-body">
        //             <div class="card-text">${escapeHtml(content)}</div>
        //             <button class="btn btn-sm btn-danger" onclick="deleteNote(${id}, this)">Delete</button>
        //         </div>
        //     `;
        //     document.getElementById('notesList').prepend(note);
        // }
        function renderNote(content, id) {
            const note = document.createElement('div');
            note.className = 'card mb-2';
            note.innerHTML = `
        <div class="card-body">
            <div class="card-text note-summary" style="cursor:pointer; user-select:none;" onclick="showNoteDetails(${id})" tabindex="0">
                ${escapeHtml(content.length > 50 ? content.substring(0, 50) + '...' : content)}
            </div>
                <button class="btn btn-sm btn-danger p-1 ms-2" onclick="deleteNote(${id}, this)">
                <i class="bi bi-trash"></i>
                </button>
            </div>
    `;
            document.getElementById('notesList').prepend(note);
        }


        function deleteNote(id, btn) {
            if (!confirm('Delete note?')) return;
            post({
                type: 'delete_note',
                id
            }, res => {
                if (res.status === 'deleted') {
                    btn.closest('.card').remove();
                } else if (res.error) {
                    alert('Error: ' + res.error);
                }
            });
        }

        // Add Task
        function addTask() {
            const t = document.getElementById('taskTitle').value.trim();
            const l = document.getElementById('taskLink').value.trim();
            const d = document.getElementById('taskDate').value;
            const dt = document.getElementById('taskDetails').value.trim();
            if (!(t && d && dt)) return alert('Please fill all required fields (title, date, details).');
            post({
                type: 'add_task',
                title: t,
                link: l,
                date: d,
                details: dt
            }, res => {
                if (res.status === 'success') {
                    renderTask({
                        id: res.id,
                        title: t,
                        link: l,
                        date: d,
                        details: dt,
                        done: 0
                    });
                    clearTaskInputs();
                    updateProgress();
                } else if (res.error) {
                    alert('Error: ' + res.error);
                }
            });
        }

        function renderTask(task) {
            const card = document.createElement('div');
            card.className = 'card mb-2';
            card.innerHTML = `
            <div class="card-body">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" onchange="toggleTask(${task.id}, this.checked)" ${task.done ? 'checked' : ''}>
                    <label class="form-check-label"><strong>${escapeHtml(task.title)}</strong> [${escapeHtml(task.date)}]</label>
                </div>
                ${task.link ? `<a href="${escapeHtml(task.link)}" target="_blank" rel="noopener noreferrer">${escapeHtml(task.link)}</a>` : ''}
                <textarea class="mt-2 mb-2" readonly>${escapeHtml(task.details)}</textarea>
                <button class="btn btn-sm btn-danger p-1 ms-2" onclick="deleteTask(${task.id}, this)">
                <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
            document.getElementById('tasksList').prepend(card);
        }

        function clearTaskInputs() {
            document.getElementById('taskTitle').value = '';
            document.getElementById('taskLink').value = '';
            document.getElementById('taskDate').value = '';
            document.getElementById('taskDetails').value = '';
        }

        function deleteTask(id, btn) {
            if (!confirm('Delete task?')) return;
            post({
                type: 'delete_task',
                id
            }, res => {
                if (res.status === 'deleted') {
                    btn.closest('.card').remove();
                    updateProgress();
                } else if (res.error) {
                    alert('Error: ' + res.error);
                }
            });
        }

        function toggleTask(id, done) {
            post({
                type: 'toggle_task',
                id,
                done
            }, res => {
                if (res.status === 'updated') {
                    updateProgress();
                } else if (res.error) {
                    alert('Error: ' + res.error);
                }
            });
        }

        // Add Document
        function addDocument() {
            const t = document.getElementById('docTitle').value.trim();
            const u = document.getElementById('docURL').value.trim();
            if (!(t && u)) return alert('Please fill both Title and URL.');
            post({
                type: 'add_document',
                title: t,
                url: u
            }, res => {
                if (res.status === 'success') {
                    renderDocument({
                        id: res.id,
                        title: t,
                        url: u
                    });
                    clearDocumentInputs();
                } else if (res.error) {
                    alert('Error: ' + res.error);
                }
            });
        }

        function renderDocument(doc) {
            const card = document.createElement('div');
            card.className = 'card mb-2';
            card.innerHTML = `
            <div class="card-body">
                <a href="${escapeHtml(doc.url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(doc.title)}</a>
        <!-- <button class="btn btn-sm btn-danger mt-2" onclick="deleteDocument(${doc.id}, this)">Delete</button> -->
            <button class="btn btn-sm btn-danger mt-2" onclick="deleteDocument(${doc.id}, this)">
            <i class="bi bi-trash"></i>
            </button>
            </div>
        `;
            document.getElementById('documentsList').prepend(card);
        }

        function clearDocumentInputs() {
            document.getElementById('docTitle').value = '';
            document.getElementById('docURL').value = '';
        }

        function deleteDocument(id, btn) {
            if (!confirm('Delete document?')) return;
            post({
                type: 'delete_document',
                id
            }, res => {
                if (res.status === 'deleted') {
                    btn.closest('.card').remove();
                } else if (res.error) {
                    alert('Error: ' + res.error);
                }
            });
        }

        // Section switching
        const navLinks = document.querySelectorAll('nav a');
        const sections = document.querySelectorAll('section');

        function setActiveSection(sectionId) {
            sections.forEach(sec => {
                sec.classList.remove('d-block', 'active');
                sec.classList.add('d-none');
            });
            navLinks.forEach(nav => nav.classList.remove('active'));
            document.getElementById(sectionId).classList.remove('d-none');
            document.getElementById(sectionId).classList.add('d-block', 'active');
            document.querySelector(`nav a[data-target="${sectionId}"]`).classList.add('active');
        }
        navLinks.forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                setActiveSection(link.dataset.target);
            });
        });

        // // Floating Button scroll to Notes
        // document.getElementById('quickAdd').addEventListener('click', () => {
        //     setActiveSection('notes');
        //     document.getElementById('noteContent').focus();
        // });
        document.getElementById('quickAdd').addEventListener('click', () => {
            const modal = new bootstrap.Modal(document.getElementById('quickAddNoteModal'));
            modal.show();
        });

        function quickAddNote() {
            const content = document.getElementById('quickNoteContent').value.trim();
            if (!content) return alert('Note cannot be empty.');
            post({
                type: 'add_note',
                content
            }, res => {
                if (res.status === 'success') {
                    renderNote(content, res.id);
                    document.getElementById('quickNoteContent').value = '';
                    // bootstrap.Modal.getInstance(document.getElementById('quickAddNoteModal')).hide();
                    // bootstrap.Modal.getInstance(document.getElementById('noteDetailModal'))?.hide();
                    bootstrap.Modal.getInstance(document.getElementById('quickAddNoteModal'))?.hide();
                    bootstrap.Modal.getInstance(document.getElementById('quickAddModal'))?.hide();


                } else {
                    alert('Error: ' + (res.error || 'Unknown error'));
                }
            });
        }


        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        darkModeToggle.addEventListener('change', () => {
            document.body.classList.toggle('dark-mode', darkModeToggle.checked);
            localStorage.setItem('darkMode', darkModeToggle.checked);
        });
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            darkModeToggle.checked = true;
        }

        // Progress bar update
        function updateProgress() {
            const checkboxes = document.querySelectorAll('#tasksList input[type="checkbox"]');
            const done = Array.from(checkboxes).filter(c => c.checked).length;
            const total = checkboxes.length;
            const percent = total ? Math.round((done / total) * 100) : 0;
            const bar = document.querySelector('.progress-bar');
            bar.style.width = `${percent}%`;
            bar.textContent = `${percent}%`;
        }
        updateProgress();

        document.getElementById('tasksList').addEventListener('change', updateProgress);

        const default_sec = 'tasks';
        // On load, set default section
        setActiveSection(default_sec);

        document.getElementById("quickAdd").tabIndex = "5";
        document.getElementById("python_nav").tabIndex = "4";
        document.getElementById("python_nav").tabIndex = "3";
        document.getElementById("notes_nav").tabIndex = "2";
        document.getElementById("tasks_nav").tabIndex = "1";
        document.getElementById("tasks_nav").focus();
        // Simple escape to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        const quickNoteContent = document.getElementById('quickNoteContent');

        function quickAddNote() {
            if (!quickNoteContent) return;
            const content = quickNoteContent.value.trim();
            if (!content) {
                alert('Note cannot be empty.');
                return;
            }
            post({
                type: 'add_note',
                content
            }, res => {
                if (res.status === 'success') {
                    renderNote(content, res.id);
                    quickNoteContent.value = '';
                    // Hide the modal after adding note (Bootstrap 5)
                    const modalElement = document.getElementById('quickAddNoteModal');
                    const modalInstance = bootstrap.Modal?.getInstance(modalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    } else if (modalElement) {
                        modalElement.style.display = 'none';
                    }
                } else {
                    alert('Error: ' + (res.error || 'Unknown error'));
                }
            });
        }
    </script>
</body>

<?php
}
?>