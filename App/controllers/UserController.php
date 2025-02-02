<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Validation;
use Framework\Session;

class UserController
{
    protected $db;

    public function __construct()
    {
        $config = require basePath('config/db.php');
        $this->db = new Database($config);
    }

    /**
     * Show the login page
     *
     * @return void
     */
    public function login()
    {
        loadView('users/login');
    }

    /**
     * Show the register page
     *
     * @return void
     */
    public function create()
    {
        loadView('users/create');
    }

    /**
     * store a new user in database
     *
     * @return void
     */
    public function store()
    {
        $name = $_POST['name'] ?? null;
        $email = $_POST['email'] ?? null;
        $city = $_POST['city'] ?? null;
        $state = $_POST['state'] ?? null;
        $password = $_POST['password'] ?? null;
        $password_confirmation = $_POST['password_confirmation'] ?? null;

        $errors = [];

        //Validation
        if (!Validation::email($email)) {
            $errors['email'] = 'Invalid email address';
        }

        if (!Validation::string($name, 2, 50)) {
            $errors['name'] = 'Invalid name, must be between 2 and 50 characters';
        }

        if (!Validation::string($password, 6, 50)) {
            $errors['password'] = 'Invalid password, must be at least 6 characters';
        }

        if (!Validation::match($password, $password_confirmation)) {
            $errors['password_confirmation'] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            loadView('users/create', [
                'errors' => $errors,
                'user' => [
                    'name' => $name,
                    'email' => $email,
                    'city' => $city,
                    'state' => $state,
                ]
            ]);
            exit;
        }

        // Check if email exists
        $params = [
            'email' => $email
        ];

        $user = $this->db->query('SELECT * FROM users WHERE email = :email', $params)->fetch();

        if ($user) {
            $errors['email'] = 'Email already exists';
            loadView('users/create', [
                'errors' => $errors,
            ]);
            exit;
        }

        // Create user account

        $params = [
            'name' => $name,
            'email' => $email,
            'city' => $city,
            'state' => $state,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ];

        $this->db->query('INSERT INTO users (name, email, city, state, password) VALUES (:name, :email, :city, :state, :password)', $params);

        // Get new user ID
        $user_id = $this->db->conn->lastInsertId();

        // Set user session
        Session::set('user', [
            'id' => $user_id,
            'name' => $name,
            'email' => $email,
            'city' => $city,
            'state' => $state,
        ]);

        redirect('/');
    }

    /**
     * Logout the user and kill the session
     *
     * @return void
     */
    public function logout()
    {
        Session::clearAll('user');

        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 86400, $params['path'], $params['domain'], $params['secure']);
        redirect('/');
    }


    /**
     * Authenticate the user with email and password
     *
     * @return void
     */
    public function authenticate()
    {
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;

        $errors = [];

        //Validation
        if (!Validation::email($email)) {
            $errors['email'] = 'Invalid email address';
        }

        if (!Validation::string($password, 6, 50)) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        // Check for errors
        if (!empty($errors)) {
            loadView('users/login', [
                'errors' => $errors,
            ]);
            exit;
        }

        // Check if email exists
        $params = [
            'email' => $email
        ];

        $user = $this->db->query('SELECT * FROM users WHERE email = :email', $params)->fetch();

        if (!$user) {
            $errors['email'] = 'Email not found';
            loadView('users/login', [
                'errors' => $errors,
            ]);
            exit;
        }

        // Check if password is correct
        if (!password_verify($password, $user->password)) {
            $errors['password'] = 'Invalid password';
            loadView('users/login', [
                'errors' => $errors,
            ]);
            exit;
        }

        // Set user session
        Session::set('user', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'city' => $user->city,
            'state' => $user->state,
        ]);

        redirect('/');
    }
}
