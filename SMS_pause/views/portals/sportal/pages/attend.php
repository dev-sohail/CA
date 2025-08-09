<?php
$filePath = __DIR__ . '/../../../../config/config.php';
if (file_exists($filePath)) {
    include_once $filePath;
} else {
    die("File not found: $filePath");
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'staff') {
    header('Location: ' . APP_AUTH_URL . '/login.php');
    exit();
}



$staff_username = $_SESSION['user'];
$today = date('Y-m-d');
$success_message = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method_select'] ?? '';
    $who = $staff_username;
    $date = $today;
    $time = date('H:i:s');
    $value = null;
    try {
        switch ($method) {
            case 'rfid':
                $rfid = trim($_POST['rfid'] ?? '');
                if (!$rfid) throw new Exception("RFID is required.");
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND rfid = ?");
                $stmt->execute([$who, $rfid]);
                if (!$stmt->fetch()) throw new Exception("Invalid RFID.");
                $value = $rfid;
                break;
            case 'fingerprint':
                $fingerprint = trim($_POST['fingerprint'] ?? '');
                if (!$fingerprint) throw new Exception("Fingerprint ID is required.");
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND fingerprint_id = ?");
                $stmt->execute([$who, $fingerprint]);
                if (!$stmt->fetch()) throw new Exception("Invalid fingerprint.");
                $value = $fingerprint;
                break;
            case 'geo':
                $geo = trim($_POST['geo'] ?? '');
                if (!$geo || strpos($geo, ',') === false) throw new Exception("Invalid location format.");
                [$lat, $lng] = array_map('floatval', explode(',', $geo));
                $allowedLat = 33.726742;
                $allowedLng = 73.101732;
                $radius = 0.0005;
                if (abs($lat - $allowedLat) > $radius || abs($lng - $allowedLng) > $radius) {
                    throw new Exception("You are outside the allowed area.");
                }
                $value = "$lat,$lng";
                break;
            case 'otp':
                $otp = trim($_POST['otp'] ?? '');
                if (!$otp) throw new Exception("OTP is required.");
                $stmt = $pdo->prepare("SELECT * FROM otp_sessions WHERE username = ? AND otp = ? AND expires_at > NOW()");
                $stmt->execute([$who, $otp]);
                if (!$stmt->fetch()) throw new Exception("Invalid or expired OTP.");
                $value = $otp;
                break;
            case 'manual':
                $passkey = trim($_POST['manual_note'] ?? '');
                if (!$passkey) throw new Exception("Passkey is required.");
                $stmt = $pdo->prepare("SELECT * FROM manual_passkeys WHERE passkey = ?");
                $stmt->execute([$passkey]);
                if (!$stmt->fetch()) throw new Exception("Invalid passkey.");
                $value = $passkey;
                break;
            default:
                throw new Exception("Unknown method.");
        }
        $stmt = $pdo->prepare("INSERT INTO daily_attendance (username, method, value, date, time) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$who, $method, $value, $date, $time]);
        $success_message = ucfirst($method) . " attendance submitted.";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Include the header
include_once APP_HEADER_FILE;
include_once APP_SPORTAL_MENU;
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <section class="card p-4">
                <h2 class="mb-3">Mark Attendance</h2>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>
                <form method="POST" autocomplete="off" id="attendance_form">
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" class="form-control" name="staff_username" value="<?= htmlspecialchars($staff_username) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Method:</label>
                        <select class="form-control" id="method_select" name="method_select" required>
                            <option value="rfid">RFID</option>
                            <option value="fingerprint">Fingerprint</option>
                            <option value="geo">Geo Location</option>
                            <option value="otp">OTP</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                    <div class="form-group method-field" id="rfid_field">
                        <label>RFID:</label>
                        <input type="text" class="form-control" name="rfid" placeholder="Scan RFID">
                    </div>
                    <div class="form-group method-field" id="fingerprint_field" style="display:none;">
                        <label>Fingerprint ID:</label>
                        <input type="text" class="form-control" name="fingerprint" placeholder="Enter fingerprint ID">
                    </div>
                    <div class="form-group method-field" id="geo_field" style="display:none;">
                        <label>Geo Location:</label>
                        <input type="text" class="form-control" id="geo" name="geo" readonly>
                    </div>
                    <div class="form-group method-field" id="otp_field" style="display:none;">
                        <label>OTP:</label>
                        <input type="text" class="form-control" name="otp" placeholder="Enter OTP">
                    </div>
                    <div class="form-group method-field" id="manual_field" style="display:none;">
                        <label>Passkey:</label>
                        <textarea class="form-control" name="manual_note" placeholder="Enter passkey"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Submit Attendance</button>
                </form>
            </section>
        </div>
        <div class="col-md-6">
            <section class="card p-4">
                <h3>Monthly Attendance</h3>
                <canvas id="MyAttendanceChart" title="My Attendance"></canvas>
            </section>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function updateMethodFields() {
        let method = document.getElementById('method_select').value;
        ['rfid', 'fingerprint', 'geo', 'otp', 'manual'].forEach(function(m) {
            document.getElementById(m + '_field').style.display = (m === method) ? '' : 'none';
        });
        if (method === 'geo') {
            const geoInput = document.getElementById('geo');
            geoInput.value = 'Locating...';
            navigator.geolocation.getCurrentPosition(function(pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                geoInput.value = lat + ',' + lng;
            }, function() {
                geoInput.value = 'Unable to locate';
            });
        }
    }
    document.getElementById('method_select').addEventListener('change', updateMethodFields);
    document.addEventListener('DOMContentLoaded', updateMethodFields);

    // Chart.js for monthly attendance
    const ctx = document.getElementById('myAttendanceChart').getContext('2d');
    const months = <?= json_encode($months) ?>;
    const counts = <?= json_encode($counts) ?>;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Attendance Count',
                data: counts,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
<?php include_once APP_FOOTER_FILE; ?>
