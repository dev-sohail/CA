<?php
$scan_file = __DIR__ . '/latest_scan.txt';

// Handle POST from Arduino (or test with curl/Postman)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Expecting: POST rfid=xxxx or fingerprint=xxxx
    $rfid = $_POST['rfid'] ?? null;
    $fingerprint = $_POST['fingerprint'] ?? null;
    $who = $_POST['who'] ?? 'Unknown';
    $time = date('Y-m-d H:i:s');
    $type = $rfid ? 'RFID' : ($fingerprint ? 'Fingerprint' : 'Unknown');
    $value = $rfid ?: $fingerprint;

    if ($value) {
        $data = [
            'who' => $who,
            'type' => $type,
            'value' => $value,
            'time' => $time
        ];
        file_put_contents($scan_file, json_encode($data));
        echo "OK";
    } else {
        http_response_code(400);
        echo "No RFID or fingerprint data";
    }
    exit;
}

// Handle AJAX GET for live preview
if (isset($_GET['live'])) {
    header('Content-Type: application/json');
    if (file_exists($scan_file)) {
        echo file_get_contents($scan_file);
    } else {
        echo json_encode(['who'=>'', 'type'=>'', 'value'=>'', 'time'=>'', 'msg'=>'No scan yet']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Live Attendance Preview</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2>Live Attendance Preview (RFID/Fingerprint)</h2>
    <div id="attendance_preview" class="alert alert-info">Waiting for scan...</div>
    <script>
    function fetchAttendancePreview() {
        fetch('attend_live.php?live=1')
            .then(response => response.json())
            .then(data => {
                if (data.value) {
                    document.getElementById('attendance_preview').innerHTML =
                        `<b>${data.type}:</b> ${data.value}<br>` +
                        `<b>User:</b> ${data.who}<br>` +
                        `<b>Time:</b> ${data.time}`;
                } else {
                    document.getElementById('attendance_preview').innerHTML = 'Waiting for scan...';
                }
            })
            .catch(() => {
                document.getElementById('attendance_preview').innerHTML = 'Error connecting to server.';
            });
    }
    setInterval(fetchAttendancePreview, 2000);
    fetchAttendancePreview();
    </script>
    <hr>
    <p>
        <b>How to use with Arduino:</b><br>
        Send a POST request to this file with <code>rfid=xxxx</code> or <code>fingerprint=xxxx</code> and <code>who=staffname</code>.<br>
        Example Arduino code (Ethernet/WiFi):<br>
        <pre>
        // Pseudocode for Arduino
        // Use HTTP POST to http://your-server/views/portals/sportal/pages/attend_live.php
        // POST data: rfid=12345678&who=JohnDoe
        </pre>
    </p>
</body>
</html> 