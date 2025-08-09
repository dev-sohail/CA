<?php
/**
 * API Controller
 * 
 * Handles API endpoints for AJAX requests
 */

namespace App\Controllers;

use System\Core\Controller;

class ApiController extends Controller
{
    /**
     * Get attendance data
     */
    public function attendance()
    {
        $this->requireAuth();
        
        $username = $this->getUsername();
        $month = $this->getPost('month', date('n'));
        $year = $this->getPost('year', date('Y'));
        
        $sql = "SELECT year, month, present_days, absent_days, late_days FROM attendance WHERE username = :username AND month = :month AND year = :year";
        $attendance = $this->db->fetch($sql, [
            'username' => $username,
            'month' => $month,
            'year' => $year
        ]);
        
        if ($attendance) {
            $this->json([
                'success' => true,
                'data' => $attendance
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'No attendance data found'
            ], 404);
        }
    }

    /**
     * Get notices
     */
    public function notices()
    {
        $this->requireAuth();
        
        $role = $this->getUserRole();
        
        $sql = "SELECT * FROM notice_board WHERE role = :role OR role = 'all' ORDER BY created_at DESC LIMIT 10";
        $notices = $this->db->fetchAll($sql, ['role' => $role]);
        
        $this->json([
            'success' => true,
            'data' => $notices
        ]);
    }

    /**
     * Get timetable
     */
    public function timetable()
    {
        $this->requireAuth();
        
        $username = $this->getUsername();
        
        $sql = "SELECT * FROM timetable WHERE username = :username ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
        $timetable = $this->db->fetchAll($sql, ['username' => $username]);
        
        $this->json([
            'success' => true,
            'data' => $timetable
        ]);
    }

    /**
     * Update attendance
     */
    public function updateAttendance()
    {
        $this->requireAuth();
        
        $username = $this->getUsername();
        $date = $this->getPost('date');
        $status = $this->getPost('status'); // present, absent, late
        $method = $this->getPost('method', 'manual');
        
        if (!$date || !$status) {
            $this->json([
                'success' => false,
                'message' => 'Date and status are required'
            ], 400);
        }
        
        try {
            // Insert daily attendance
            $dailyId = $this->db->insert('daily_attendance', [
                'username' => $username,
                'date' => $date,
                'attendance_status' => $status,
                'methods' => $method
            ]);
            
            $this->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'data' => ['id' => $dailyId]
            ]);
            
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to update attendance'
            ], 500);
        }
    }
} 