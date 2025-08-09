# SMS Framework

A modern PHP MVC framework for School Management Systems.

## Features
- MVC Architecture
- PSR-4 Autoloading
- Database Abstraction Layer
- Authentication System
- RESTful API Support
- Bootstrap 5 UI

## Installation
1. Set up database using `database/casms.sql`
2. Configure `config/database.php`
3. Access via web browser

## Usage
See documentation in code comments and examples.

## Directory Structure

```
SMS_frame/
├── app/                    # Application code
│   ├── controllers/        # Controller classes
│   ├── models/            # Model classes
│   ├── views/             # View templates
│   ├── services/          # Service classes
│   └── middleware/        # Middleware classes
├── config/                # Configuration files
├── database/              # Database files
├── public/                # Public assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   ├── images/           # Images
│   └── uploads/          # Uploaded files
├── system/                # Framework core
│   ├── core/             # Core classes
│   ├── database/         # Database classes
│   └── helpers/          # Helper functions
├── .htaccess             # URL rewriting rules
├── index.php             # Front controller
└── README.md             # This file
```

## Usage

### Creating a Controller

```php
<?php
namespace App\Controllers;

use System\Core\Controller;

class ExampleController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Example Page',
            'content' => 'Hello World!'
        ];
        
        $this->renderWithLayout('example/index', $data);
    }
}
```

### Creating a Model

```php
<?php
namespace App\Models;

use System\Core\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
    
    public function findByEmail($email)
    {
        return $this->findOne('email = :email', ['email' => $email]);
    }
}
```

### Creating a View

```php
<!-- app/views/example/index.php -->
<div class="container">
    <h1><?= $title ?></h1>
    <p><?= $content ?></p>
</div>
```

### Adding Routes

Edit `system/core/Router.php` and add routes to the `loadRoutes()` method:

```php
'GET' => [
    '/example' => ['ExampleController', 'index'],
],
'POST' => [
    '/example/create' => ['ExampleController', 'create'],
]
```

## Database Operations

### Using the Database Class

```php
// Fetch a single record
$user = $this->db->fetch("SELECT * FROM users WHERE id = :id", ['id' => 1]);

// Fetch multiple records
$users = $this->db->fetchAll("SELECT * FROM users WHERE active = :active", ['active' => 1]);

// Insert a record
$userId = $this->db->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Update a record
$affected = $this->db->update('users', 
    ['name' => 'Jane Doe'], 
    'id = :id', 
    ['id' => 1]
);

// Delete a record
$affected = $this->db->delete('users', 'id = :id', ['id' => 1]);
```

## Authentication

### Protecting Routes

```php
// Require any authentication
$this->requireAuth();

// Require specific role
$this->requireAuth('admin');
```

### Session Management

```php
// Set session data
$this->setSession('user_id', 123);

// Get session data
$userId = $this->getSession('user_id');

// Check if logged in
if ($this->isLoggedIn()) {
    // User is authenticated
}
```

## API Endpoints

The framework includes built-in API support for AJAX requests:

- `POST /api/attendance` - Get attendance data
- `POST /api/notices` - Get notices
- `POST /api/timetable` - Get timetable

## Security Features

- **CSRF Protection**: Built-in CSRF token validation
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Output escaping in views
- **Session Security**: Secure session configuration
- **Input Validation**: Form validation helpers

## Customization

### Adding Custom CSS/JS

Add custom stylesheets or scripts to specific pages:

```php
$data = [
    'additional_css' => [
        APP_CSS_URL . '/custom.css'
    ],
    'additional_js' => [
        APP_JS_URL . '/custom.js'
    ]
];
```

### Creating Custom Helpers

Create helper functions in `system/helpers/` and include them in `index.php`.

## Troubleshooting

### Common Issues

1. **404 Errors**: Make sure mod_rewrite is enabled and .htaccess is working
2. **Database Connection**: Verify database credentials in `config/database.php`
3. **Class Not Found**: Check namespace declarations and autoloader paths
4. **Permission Errors**: Ensure proper file permissions on upload directories

### Debug Mode

Enable debug mode in `config/config.php`:

```php
define('APP_ENV', 'development');
define('DEBUG', true);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support and questions, please create an issue in the repository or contact the development team. 