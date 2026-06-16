<?php

namespace Controllers;

use MVC\Router;

class LoginController
{
    public static function login(Router $router) {

        if($_SERVER['REQUEST_METHOD'] === 'POST') {

        }

        //render para la vista
        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesion'
        ]);
    }

    public static function logout() {
        echo "Desde Logout";
    }

    public static function crear(Router $router) {

        if($_SERVER['REQUEST_METHOD'] === 'POST') {

        }
        
        //render para la vista
        $router->render('auth/crear', [
            'titulo' => 'Crea tu cuenta en UpTask'
        ]);
    }

    public static function olvide(Router $router) {

        if($_SERVER['REQUEST_METHOD'] === 'POST') {

        }
         //render para la vista
        $router->render('auth/olvide', [
            'titulo' => 'Restablecer Contraseña'
        ]);
    }

    public static function restablecer() {
        echo "Desde restablecer";

        if($_SERVER['REQUEST_METHOD'] === 'POST') {

        }
    }
    public static function mensaje() {
        echo "Desde mensaje";
    }

    public static function confirmar() {
        echo "Desde confirmar";
    }
}