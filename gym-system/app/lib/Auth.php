<?php
/**
 * Auth - Centralized access/role control helper
 */
class Auth {

    /**
     * Requires the user to be logged in AND have one of the given roles.
     * Redirects to login or home with a flash message if denied.
     *
     * @param array $rolesPermitidos  e.g. ['admin', 'recepcionista']
     * @param string $redirectTo      Where to send unauthorized users (default /home/index)
     */
    public static function requerirRol(array $rolesPermitidos, string $redirectTo = '/home/index'): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }

        $rolActual = $_SESSION['user_rol'] ?? '';
        if (!in_array($rolActual, $rolesPermitidos)) {
            $_SESSION['acceso_denegado'] = 'No tienes permisos para acceder a esta sección.';
            header('Location: ' . $redirectTo);
            exit;
        }
    }

    /** Requires the user to be logged in (any role) */
    public static function requerirLogin(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }
    }

    public static function esAdmin(): bool {
        return ($_SESSION['user_rol'] ?? '') === 'admin';
    }

    public static function esRecepcionista(): bool {
        return ($_SESSION['user_rol'] ?? '') === 'recepcionista';
    }

    public static function esEntrenador(): bool {
        return ($_SESSION['user_rol'] ?? '') === 'entrenador';
    }

    public static function rolActual(): string {
        return $_SESSION['user_rol'] ?? '';
    }
}
