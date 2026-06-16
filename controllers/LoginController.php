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
            'titulo' => 'Olvide Contraseña'
        ]);
    }

    public static function restablecer(Router $router) {
        
        if($_SERVER['REQUEST_METHOD'] === 'POST') {

        }

         //render para la vista
        $router->render('auth/restablecer', [
            'titulo' => 'Restablecer Contraseña'
        ]);
    }
    public static function mensaje(Router $router) {
        $router->render('auth/mensaje', [
            'titulo' => 'Cuenta Creada Exitosamente'
        ]);
    }

    public static function confirmar(Router $router) {
        $router->render('auth/confirmar', [
            'titulo' => 'Confirma tu Cuenta'
        ]);
    }
}