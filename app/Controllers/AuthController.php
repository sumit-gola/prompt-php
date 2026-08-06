<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

final class AuthController extends Controller
{
    public function showRegister(Request $request): Response
    {
        return $this->authView('auth/register', ['title' => 'Create account']);
    }

    public function register(Request $request): Response
    {
        $data = $request->post();
        $errors = [];

        Validator::required($errors, 'name', $data['name'] ?? '', 'Name');
        Validator::required($errors, 'email', $data['email'] ?? '', 'Email');
        Validator::email($errors, 'email', $data['email'] ?? '');
        Validator::required($errors, 'password', $data['password'] ?? '', 'Password');
        Validator::max($errors, 'name', $data['name'] ?? '', 120, 'Name');

        if (mb_strlen((string) ($data['password'] ?? '')) < 10) {
            $errors['password'] = 'Password must be at least 10 characters.';
        }

        if (! isset($errors['email']) && User::findByEmail((string) $data['email'])) {
            $errors['email'] = 'An account already exists for this email.';
        }

        if ($errors !== []) {
            return $this->backWithErrors($errors, ['name' => $data['name'] ?? '', 'email' => $data['email'] ?? '']);
        }

        $user = User::create((string) $data['name'], (string) $data['email'], (string) $data['password'], false);
        Auth::login($user);
        Session::setFlash('success', 'Account created.');

        return $this->redirect('/');
    }

    public function showLogin(Request $request): Response
    {
        return $this->authView('auth/login', ['title' => 'Sign in']);
    }

    public function login(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if (! Auth::attempt($email, $password)) {
            return $this->backWithErrors(['email' => 'The email or password is incorrect.'], ['email' => $email]);
        }

        Session::setFlash('success', 'Signed in.');
        $user = Auth::user();

        return $this->redirect((int) ($user['is_admin'] ?? 0) === 1 ? '/admin' : '/');
    }

    public function logout(Request $request): Response
    {
        Auth::logout();

        return Response::redirect('/');
    }
}

