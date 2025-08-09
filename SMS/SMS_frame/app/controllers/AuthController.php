<?php
/**
 * Auth Controller
 * 
 * Handles user authentication (login, logout, register)
 */

namespace App\Controllers;

use System\Core\Controller;

class AuthController extends Controller
{
    /**
     * Display login form
     */
    public function login()
    {
        // Check if user is already logged in
        if ($this->isLoggedIn()) {
            $this->redirectToPortal();
        }
        
        $data = [
            'page_title' => 'Login - ' . APP_NAME
        ];
        
        $this->renderWithLayout('auth/login', $data);
    }

    /**
     * Handle login form submission
     */
    public function loginPost()
    {
        $username = $this->getPost('username');
        $password = $this->getPost('password');
        $role = $this->getPost('role');
        
        // Validate input
        if (empty($username) || empty($password) || empty($role)) {
            $this->setSession('error', 'All fields are required');
            $this->redirect('/auth/login');
        }
        
        // Authenticate user
        $user = $this->authenticateUser($username, $password, $role);
        
        if ($user) {
            // Set session data
            $this->setSession('user', $username);
            $this->setSession('role', $role);
            $this->setSession('logged_in', true);
            $this->setSession('user_id', $user['id']);
            
            // Redirect to appropriate portal
            $this->redirectToPortal();
        } else {
            $this->setSession('error', 'Invalid credentials');
            $this->redirect('/auth/login');
        }
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        // Clear session
        $this->request->destroySession();
        
        // Redirect to home
        $this->redirect('/');
    }

    /**
     * Display registration form
     */
    public function register()
    {
        $data = [
            'page_title' => 'Register - ' . APP_NAME
        ];
        
        $this->renderWithLayout('auth/register', $data);
    }

    /**
     * Handle registration form submission
     */
    public function registerPost()
    {
        $username = $this->getPost('username');
        $password = $this->getPost('password');
        $confirmPassword = $this->getPost('confirm_password');
        $role = $this->getPost('role');
        $email = $this->getPost('email');
        
        // Validate input
        if (empty($username) || empty($password) || empty($role) || empty($email)) {
            $this->setSession('error', 'All fields are required');
            $this->redirect('/auth/register');
        }
        
        if ($password !== $confirmPassword) {
            $this->setSession('error', 'Passwords do not match');
            $this->redirect('/auth/register');
        }
        
        // Check if username already exists
        if ($this->userExists($username)) {
            $this->setSession('error', 'Username already exists');
            $this->redirect('/auth/register');
        }
        
        // Create user
        $userId = $this->createUser([
            'username' => $username,
            'password' => $password, // In production, hash the password
            'role' => $role,
            'email' => $email
        ]);
        
        if ($userId) {
            $this->setSession('success', 'Registration successful. Please login.');
            $this->redirect('/auth/login');
        } else {
            $this->setSession('error', 'Registration failed');
            $this->redirect('/auth/register');
        }
    }

    /**
     * Authenticate user
     * 
     * @param string $username
     * @param string $password
     * @param string $role
     * @return array|false
     */
    private function authenticateUser($username, $password, $role)
    {
        $sql = "SELECT * FROM user_info WHERE username = :username AND role = :role";
        $user = $this->db->fetch($sql, [
            'username' => $username,
            'role' => $role
        ]);
        
        if ($user && $user['password'] === $password) { // In production, use password_verify()
            return $user;
        }
        
        return false;
    }

    /**
     * Check if user exists
     * 
     * @param string $username
     * @return bool
     */
    private function userExists($username)
    {
        $sql = "SELECT COUNT(*) as count FROM user_info WHERE username = :username";
        $result = $this->db->fetch($sql, ['username' => $username]);
        return $result['count'] > 0;
    }

    /**
     * Create a new user
     * 
     * @param array $data
     * @return int|false
     */
    private function createUser($data)
    {
        try {
            return $this->db->insert('user_info', $data);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Redirect to appropriate portal based on user role
     */
    private function redirectToPortal()
    {
        $role = $this->getUserRole();
        
        switch ($role) {
            case 'teacher':
                $this->redirect('/portal/teacher');
                break;
            case 'student':
                $this->redirect('/portal/student');
                break;
            case 'parent':
                $this->redirect('/portal/parent');
                break;
            case 'staff':
                $this->redirect('/portal/staff');
                break;
            case 'admin':
                $this->redirect('/portal/admin');
                break;
            default:
                $this->redirect('/');
        }
    }
} 