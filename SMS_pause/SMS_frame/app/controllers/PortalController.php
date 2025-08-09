<?php
/**
 * Portal Controller
 * 
 * Handles different user portal pages
 */

namespace App\Controllers;

use System\Core\Controller;

class PortalController extends Controller
{
    /**
     * Teacher portal
     */
    public function teacher()
    {
        $this->requireAuth('teacher');
        
        $username = $this->getUsername();
        $teacherInfo = $this->getTeacherInfo($username);
        $attendance = $this->getTeacherAttendance($username);
        $timetable = $this->getTeacherTimetable($username);
        $notices = $this->getNoticesByRole('teacher');
        
        $data = [
            'teacher_info' => $teacherInfo,
            'attendance' => $attendance,
            'timetable' => $timetable,
            'notices' => $notices,
            'page_title' => 'Teacher Portal - ' . APP_NAME
        ];
        
        $this->renderWithLayout('portals/tportal/index', $data);
    }

    /**
     * Student portal
     */
    public function student()
    {
        $this->requireAuth('student');
        
        $username = $this->getUsername();
        $studentInfo = $this->getStudentInfo($username);
        $attendance = $this->getStudentAttendance($username);
        $notices = $this->getNoticesByRole('student');
        
        $data = [
            'student_info' => $studentInfo,
            'attendance' => $attendance,
            'notices' => $notices,
            'page_title' => 'Student Portal - ' . APP_NAME
        ];
        
        $this->renderWithLayout('portals/stportal/index', $data);
    }

    /**
     * Parent portal
     */
    public function parent()
    {
        $this->requireAuth('parent');
        
        $username = $this->getUsername();
        $parentInfo = $this->getParentInfo($username);
        $childAttendance = $this->getChildAttendance($parentInfo['child_user']);
        $notices = $this->getNoticesByRole('parent');
        
        $data = [
            'parent_info' => $parentInfo,
            'child_attendance' => $childAttendance,
            'notices' => $notices,
            'page_title' => 'Parent Portal - ' . APP_NAME
        ];
        
        $this->renderWithLayout('portals/pportal/index', $data);
    }

    /**
     * Staff portal
     */
    public function staff()
    {
        $this->requireAuth('staff');
        
        $username = $this->getUsername();
        $staffInfo = $this->getStaffInfo($username);
        $attendance = $this->getStaffAttendance($username);
        $notices = $this->getNoticesByRole('staff');
        
        $data = [
            'staff_info' => $staffInfo,
            'attendance' => $attendance,
            'notices' => $notices,
            'page_title' => 'Staff Portal - ' . APP_NAME
        ];
        
        $this->renderWithLayout('portals/sportal/index', $data);
    }

    /**
     * Admin portal
     */
    public function admin()
    {
        $this->requireAuth('admin');
        
        $data = [
            'page_title' => 'Admin Portal - ' . APP_NAME
        ];
        
        $this->renderWithLayout('portals/sportal/admin/index', $data);
    }

    // Helper methods for data retrieval
    private function getTeacherInfo($username)
    {
        $sql = "SELECT * FROM user_info WHERE username = :username";
        return $this->db->fetch($sql, ['username' => $username]);
    }

    private function getStudentInfo($username)
    {
        $sql = "SELECT * FROM user_info WHERE username = :username";
        return $this->db->fetch($sql, ['username' => $username]);
    }

    private function getParentInfo($username)
    {
        $sql = "SELECT * FROM user_info WHERE username = :username";
        return $this->db->fetch($sql, ['username' => $username]);
    }

    private function getStaffInfo($username)
    {
        $sql = "SELECT * FROM user_info WHERE username = :username";
        return $this->db->fetch($sql, ['username' => $username]);
    }

    private function getTeacherAttendance($username)
    {
        $sql = "SELECT year, month, present_days, absent_days, late_days FROM attendance WHERE username = :username ORDER BY year DESC, month DESC";
        return $this->db->fetchAll($sql, ['username' => $username]);
    }

    private function getStudentAttendance($username)
    {
        $sql = "SELECT year, month, present_days, absent_days, late_days FROM attendance WHERE username = :username ORDER BY year DESC, month DESC";
        return $this->db->fetchAll($sql, ['username' => $username]);
    }

    private function getStaffAttendance($username)
    {
        $sql = "SELECT year, month, present_days, absent_days, late_days FROM attendance WHERE username = :username ORDER BY year DESC, month DESC";
        return $this->db->fetchAll($sql, ['username' => $username]);
    }

    private function getChildAttendance($childUsername)
    {
        $sql = "SELECT year, month, present_days, absent_days, late_days FROM attendance WHERE username = :username ORDER BY year DESC, month DESC";
        return $this->db->fetchAll($sql, ['username' => $childUsername]);
    }

    private function getTeacherTimetable($username)
    {
        $sql = "SELECT * FROM timetable WHERE username = :username ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
        return $this->db->fetchAll($sql, ['username' => $username]);
    }

    private function getNoticesByRole($role)
    {
        $sql = "SELECT * FROM notice_board WHERE role = :role OR role = 'all' ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, ['role' => $role]);
    }
} 