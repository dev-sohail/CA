<?php
session_start();
header('Content-Type: application/json');

$statuses = ['Present', 'Absent', 'Late', 'No scan detected'];
$status = $statuses[array_rand($statuses)];

echo json_encode(['status' => $status]);