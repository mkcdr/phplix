<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Demo controller of how the framework 
 * could be applied in a real world application.
 * Note: This Demo DO NOT provide a real authentication
 * system and SHOULD NOT be used in a real application.
 */
class UserController extends Controller
{
    /**
     * Logout
     */
    public function logout()
    {
        unset($_SESSION['usr']);
        $this->redirect(url('home'));
    }

    /**
     * Login
     */
    public function login()
    {
        if (isset($_SESSION['usr']))
        {
            $this->redirect(url('user.settings.profile'));
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') 
        {
            $usr = trim(filter_input(INPUT_POST, 'usr'));

            if (empty($usr)) {
                $errors['usr'] = 'Empty or Invalid Username.';
            }

            if (empty($errors)) {
                $_SESSION['usr'] = $usr;
                $this->redirect(url('user.settings.profile'));
            }
        }

        $html = '<h1>Log In</h1>';
        $html .= '<form method="post">';
        $html .= '<label for="usr">Username: </label><br>';
        $html .= '<input type="text" name="usr" id="usr"><br>';
        $html .= '<input type="hidden" name="'.csrf_token_name().'" value="'.csrf_token_value().'">';
        $html .= '<button type="submit">Login</button>';
        $html .= '</form>';

        if (!empty($errors['usr'])) {
            $html .= '<div style="background: #ffc; padding: 1rem;">'.$errors['usr'].'</div>';
        }

        return $html;
    }

    /**
     * Profile
     */
    public function profile()
    {
        $msg = '';
        $errors = [];
        $usr = $_SESSION['usr'];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') 
        {
            $usr = trim(filter_input(INPUT_POST, 'usr'));

            if (empty($usr)) {
                $errors['usr'] = 'Please enter your new username.';
            }

            if (empty($errors)) {
                $_SESSION['usr'] = $usr;
                $msg = 'Congratulations! Your username have been updated.';
            }
        }


        $html = '[ <a href="'.url('user.logout').'">Logout</a> ]';
        $html .= '<h1>Profile</h1>';
        $html .= '<form method="post">';
        $html .= '<label for="usr">Username: </label><br>';
        $html .= '<input type="text" name="usr" id="usr" value="'.htmlentities($usr).'">';
        $html .= '<span style="color: #f00"> *'. (!empty($errors['usr']) ? $errors['usr']: '') .'</span><br>';
        $html .= '<input type="hidden" name="'.csrf_token_name().'" value="'.csrf_token_value().'">';
        $html .= '<button type="submit">Submit</button>';
        $html .= '</form>';
        
        if ($msg) {
            $html .= '<div style="background: #ffc; padding: 1rem;">'.$msg.'</div>';
        }

        return $html;
    }
}