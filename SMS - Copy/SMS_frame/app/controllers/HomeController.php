<?php
/**
 * Home Controller
 * 
 * Handles the main homepage and about page
 */

namespace App\Controllers;

use System\Core\Controller;

class HomeController extends Controller
{
    /**
     * Display the homepage
     */
    public function index()
    {
        // Get features for the homepage
        $features = $this->getFeatures();
        
        // Get other data for homepage sections
        $data = [
            'features' => $features,
            'page_title' => 'Home - ' . APP_NAME
        ];
        
        $this->renderWithLayout('home/index', $data);
    }

    /**
     * Display the about page
     */
    public function about()
    {
        $data = [
            'page_title' => 'About - ' . APP_NAME
        ];
        
        $this->renderWithLayout('home/about', $data);
    }

    /**
     * Get features for the homepage
     * 
     * @return array
     */
    private function getFeatures()
    {
        $sql = "SELECT * FROM features_sms ORDER BY id ASC";
        return $this->db->fetchAll($sql);
    }
} 