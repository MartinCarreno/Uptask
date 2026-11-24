<?php

namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

class LoginController
{
    public static function login(Router $router)
    {
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarLogin();

            if (empty($alertas)) {
                $usuario = Usuario::where('email', $usuario->email);

                if (!$usuario || !$usuario->confirmado) {
                    Usuario::setAlerta('error', 'El Usuario no existe o no esta confirmado');
                } else {
                    if (password_verify($_POST['password'], $usuario->password)) {
                        session_start();
                        session_regenerate_id(true);
                        $_SESSION['id'] = (int) $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        header('Location: /dashboard');
                        exit;
                    } else {
                        Usuario::setAlerta('error', 'Password Incorrecto');
                    }
                }
            }
        }

        $alertas = Usuario::getAlertas();
        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesion',
            'alertas' => $alertas
        ]);
    }

    public static function logout()
    {
        session_start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        header('Location: /');
        exit;
    }

    public static function crear(Router $router)
    {
        $alertas = [];
        $usuario = new Usuario;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();

            if (empty($alertas)) {
                $existeUsuario = Usuario::where('email', $usuario->email);
                if ($existeUsuario) {
                    Usuario::setAlerta('error', 'El Usuario ya esta registrado');
                    $alertas = Usuario::getAlertas();
                } else {
                    $usuario->hashPassword();
                    unset($usuario->password2);
                    $usuario->crearToken();

                    $resultado = $usuario->guardar();

                    if ($resultado['resultado']) {
                        try {
                            $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                            $email->enviarConfirmacion();
                        } catch (\Throwable $e) {
                            error_log('Error SMTP confirmacion: ' . $e->getMessage());
                        }

                        header('Location: /mensaje');
                        exit;
                    }
                }
            }
        }

        $router->render('auth/crear', [
            'titulo' => 'Crea tu cuenta en UpTask',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function olvide(Router $router)
    {
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();

            if (empty($alertas)) {
                $usuario = Usuario::where('email', $usuario->email);

                if ($usuario && $usuario->confirmado) {
                    $usuario->crearToken();
                    unset($usuario->password2);
                    $usuario->guardar();

                    try {
                        $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                        $email->enviarInstrucciones();
                        Usuario::setAlerta('exito', 'Hemos enviado las instrucciones a tu email');
                    } catch (\Throwable $e) {
                        error_log('Error SMTP recuperacion: ' . $e->getMessage());
                        Usuario::setAlerta('error', 'No se pudo enviar el correo en este momento. Intenta nuevamente mas tarde.');
                    }
                } else {
                    Usuario::setAlerta('error', 'El Usuario no existe o no esta confirmado');
                }
            }
        }

        $alertas = Usuario::getAlertas();
        $router->render('auth/olvide', [
            'titulo' => 'Olvide Contraseña',
            'alertas' => $alertas
        ]);
    }

    public static function restablecer(Router $router)
    {
        $token = s($_GET['token'] ?? '');
        $mostrar = true;

        if (!$token) {
            header('Location: /');
            exit;
        }

        $usuario = Usuario::where('token', $token);

        if (empty($usuario) || $usuario->tokenExpirado()) {
            Usuario::setAlerta('error', 'Token no valido o expirado. Solicita un nuevo enlace de recuperacion.');
            $mostrar = false;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mostrar) {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarPassword();

            if (empty($alertas)) {
                $usuario->hashPassword();
                $usuario->token = null;
                $usuario->token_expira = null;

                $resultado = $usuario->guardar();

                if ($resultado) {
                    header('Location: /');
                    exit;
                }
            }
        }

        $alertas = Usuario::getAlertas();
        $router->render('auth/restablecer', [
            'titulo' => 'Restablecer Contraseña',
            'alertas' => $alertas,
            'mostrar' => $mostrar
        ]);
    }

    public static function mensaje(Router $router)
    {
        $router->render('auth/mensaje', [
            'titulo' => 'Cuenta Creada Exitosamente'
        ]);
    }

    public static function confirmar(Router $router)
    {
        $token = s($_GET['token'] ?? '');

        if (!$token) {
            header('Location: /');
            exit;
        }

        $usuario = Usuario::where('token', $token);

        if (empty($usuario) || $usuario->tokenExpirado()) {
            Usuario::setAlerta('error', 'Token no valido o expirado. Solicita un nuevo enlace.');
        } else {
            $usuario->confirmado = 1;
            $usuario->token = null;
            $usuario->token_expira = null;
            unset($usuario->password2);

            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta Comprobada Correctamente');
        }

        $alertas = Usuario::getAlertas();
        $router->render('auth/confirmar', [
            'titulo' => 'Confirma tu Cuenta',
            'alertas' => $alertas
        ]);
    }
}
