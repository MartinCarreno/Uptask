<?php

namespace Controllers;

use Classes\Email;
use Model\Usuario;
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
        $alertas = [];
        $usuario = new Usuario;

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();

            if(empty($alertas)) {
                $existeUsuario = Usuario::where('email', $usuario->email);
                if($existeUsuario) {
                    Usuario::setAlerta('error', 'El Usuario ya esta registrado');
                    $alertas = Usuario::getAlertas();
                } else {
                    //hashear password
                    $usuario->hashPassword();

                    //eliminar password2 (password temporal para validar)
                    unset($usuario->password2);

                    //Generar el token
                    $usuario->crearToken();
                    
                    //Crear nuevo usuario
                    $resultado = $usuario->guardar();

                    //enviar email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarConfirmacion();

                    if($resultado) {
                        header('Location: /mensaje');
                    }
                }
            }
        }
        
        //render para la vista
        $router->render('auth/crear', [
            'titulo' => 'Crea tu cuenta en UpTask',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function olvide(Router $router) {
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();

            if(empty($alertas)) {
                //buscar el usuario
                $usuario = Usuario::where('email', $usuario->email);

                if($usuario && $usuario->confirmado){
                    //generar nuevo token
                    $usuario->crearToken();
                    unset($usuario->password2);

                    //Actualizar el usuario
                    $usuario->guardar();

                    //enviar email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();
                    //imprimir alerta
                    Usuario::setAlerta('exito', 'Hemos enviado las instrucciones a tu email');
                    
                } else {
                    Usuario::setAlerta('error', 'El Usuario no existe o no esta confirmado');
                    
                }
            }
        }

        $alertas = Usuario::getAlertas();

        //render para la vista
        $router->render('auth/olvide', [
            'titulo' => 'Olvide Contraseña',
            'alertas' => $alertas
        ]);
    }

    public static function restablecer(Router $router) {
        
        $token = s($_GET['token']);
        $mostrar = true;

        if(!$token) header('Location: /');

        //identificar usuario con el token
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)) {
            Usuario::setAlerta('error', 'Token no Valido');
            $mostrar = false;
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Añadir nuevo password
            $usuario->sincronizar($_POST);

            //validar password
            $alertas = $usuario->validarPassword();

            if(empty($alertas)) {
                //hashear el nuevo password
                $usuario->hashPassword();

                //eliminar token
                $usuario->token = null;

                //guardar usuario en DB
                $resultado = $usuario->guardar();

                //redireccionar
                if($resultado) {
                    header('Location: /');
                }
                
            }
            
        }

        $alertas = Usuario::getAlertas();
         //render para la vista
        $router->render('auth/restablecer', [
            'titulo' => 'Restablecer Contraseña',
            'alertas' => $alertas,
            'mostrar' => $mostrar
        ]);
    }
    public static function mensaje(Router $router) {
        $router->render('auth/mensaje', [
            'titulo' => 'Cuenta Creada Exitosamente'
        ]);
    }

    public static function confirmar(Router $router) {

        $token = s($_GET['token']);

        if(!$token) header('Location: /');

        //encontrar usuario con este token
        $usuario = Usuario::where('token', $token);
        if(empty($usuario)) {
            //no se encontro el usuario con ese token
            Usuario::setAlerta('error', 'Token no valido');
        } else {
            //confirmar cuenta
            $usuario->confirmado = 1;
            //eliminar token
            $usuario->token = null;
            //eliminar password2
            unset($usuario->password2);
            
            //guardar en la base de datos
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