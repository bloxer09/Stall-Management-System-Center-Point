<?php

namespace App\Core;

use App\Config\App;

class Auth {
    public static function isLoggedIn() {
        return Session::get('user_id') !== null;
    }

    public static function user() {
        return Session::get('user');
    }

    public static function login($user) {
        Session::regenerate(true);

        $sessionUser = $user;
        unset($sessionUser['password']);

        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role']);
        Session::set('user', $sessionUser);
    }

    public static function logout() {
        Session::unset('user_id');
        Session::unset('user_role');
        Session::unset('user');
        Session::destroy();
        Session::start();
        Session::regenerate(true);
    }

    public static function isAdmin() {
        return self::isLoggedIn() && Session::get('user_role') === 'admin';
    }

    public static function isStaff() {
        return self::isLoggedIn() && Session::get('user_role') === 'staff';
    }

    public static function isUser() {
        return self::isLoggedIn() && Session::get('user_role') === 'user';
    }

    public static function isStaffOrAdmin() {
        return self::isAdmin() || self::isStaff();
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            Session::setFlash('error', 'You must be logged in to access this page.');
            header("Location: " . App::path('/?login=1'));
            exit();
        }
    }

    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            Session::setFlash('error', 'Access denied. Admins only.');
            header("Location: " . App::path(self::isUser() ? '/user/dashboard' : '/admin/dashboard'));
            exit();
        }
    }

    public static function requireStaffOrAdmin() {
        self::requireLogin();
        if (!self::isStaffOrAdmin()) {
            Session::setFlash('error', 'Access denied. Staff or Admins only.');
            header("Location: " . App::path('/user/dashboard'));
            exit();
        }
    }

    public static function requireUser() {
        self::requireLogin();
        if (!self::isUser()) {
            Session::setFlash('error', 'Access denied. Users only.');
            header("Location: " . App::path('/admin/dashboard'));
            exit();
        }
    }
}
