<?php

namespace Controllers;

use Model\ActiveRecord;
use Model\Proyecto;
use Model\ProyectoUsuario;
use Model\Usuario;
use MVC\Router;

class DashboardController
{
    public static function index(Router $router)
    {
        session_start();
        isAuth();

        $id = (int) $_SESSION['id'];

        // Lista proyectos donde el usuario es propietario o miembro (HU-07)
        $query = "SELECT p.*, COALESCE(pu.rolId, IF(p.propietarioId = {$id}, 1, 2)) AS rolId
                  FROM proyectos p
                  LEFT JOIN proyecto_usuarios pu
                      ON p.id = pu.proyectoId AND pu.usuarioId = {$id}
                  WHERE p.propietarioId = {$id} OR pu.usuarioId = {$id}
                  ORDER BY p.creado_en DESC";

        $proyectos = Proyecto::SQL($query);

        $router->render('dashboard/index', [
            'titulo' => 'Proyectos',
            'proyectos' => $proyectos
        ]);
    }

    public static function crear_proyecto(Router $router)
    {
        session_start();
        isAuth();
        $alertas = [];
        $proyecto = new Proyecto;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proyecto = new Proyecto($_POST);
            $alertas = $proyecto->validarProyecto();

            if (empty($alertas)) {
                $proyecto->url = md5(uniqid('', true));
                $proyecto->propietarioId = (int) $_SESSION['id'];

                try {
                    ActiveRecord::iniciarTransaccion();

                    $resultado = $proyecto->guardar();
                    if (!$resultado['resultado']) {
                        throw new \RuntimeException('No se pudo crear el proyecto');
                    }

                    $membresia = new ProyectoUsuario([
                        'proyectoId' => $resultado['id'],
                        'usuarioId' => (int) $_SESSION['id'],
                        'rolId' => ProyectoUsuario::ROL_ADMIN
                    ]);

                    $resMembresia = $membresia->guardar();
                    if (!$resMembresia['resultado']) {
                        throw new \RuntimeException('No se pudo asignar el rol de administrador');
                    }

                    ActiveRecord::confirmarTransaccion();

                    header('Location: /proyecto?id=' . $proyecto->url);
                    exit;
                } catch (\Throwable $e) {
                    ActiveRecord::revertirTransaccion();
                    Proyecto::setAlerta('error', 'Ocurrio un error al guardar el proyecto');
                    $alertas = Proyecto::getAlertas();
                }
            }
        }

        $router->render('dashboard/crear-proyecto', [
            'alertas' => $alertas,
            'titulo' => 'Crear Proyecto',
            'proyecto' => $proyecto
        ]);
    }

    public static function editar_proyecto(Router $router)
    {
        session_start();
        isAuth();

        $token = $_GET['id'] ?? '';
        if (!$token) {
            header('Location: /dashboard');
            exit;
        }

        $proyecto = Proyecto::where('url', $token);
        if (!$proyecto) {
            http_response_code(404);
            header('Location: /dashboard');
            exit;
        }

        $rol = ProyectoUsuario::obtenerRolEnProyecto($proyecto, (int) $_SESSION['id']);
        if ($rol !== ProyectoUsuario::ROL_ADMIN) {
            http_response_code(403);
            echo '403 Forbidden - No tienes permisos para editar este proyecto';
            return;
        }

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proyecto->proyecto = trim($_POST['proyecto'] ?? '');
            $proyecto->cliente = trim($_POST['cliente'] ?? '');
            $proyecto->descripcion = isset($_POST['descripcion']) && trim($_POST['descripcion']) !== ''
                ? trim($_POST['descripcion'])
                : null;

            $alertas = $proyecto->validarProyecto();

            if (empty($alertas)) {
                $proyecto->guardar();
                Proyecto::setAlerta('exito', 'Proyecto actualizado correctamente');
                $alertas = Proyecto::getAlertas();
            }
        }

        $router->render('dashboard/editar-proyecto', [
            'titulo' => 'Editar Proyecto',
            'proyecto' => $proyecto,
            'alertas' => $alertas
        ]);
    }

    public static function eliminar_proyecto()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard');
            exit;
        }

        session_start();
        isAuth();

        $token = $_POST['url'] ?? '';
        $proyecto = Proyecto::where('url', $token);

        if (!$proyecto) {
            http_response_code(404);
            header('Location: /dashboard');
            exit;
        }

        $rol = ProyectoUsuario::obtenerRolEnProyecto($proyecto, (int) $_SESSION['id']);
        if ($rol !== ProyectoUsuario::ROL_ADMIN) {
            http_response_code(403);
            echo '403 Forbidden - No tienes permisos para eliminar este proyecto';
            return;
        }

        // MySQL aplica ON DELETE CASCADE sobre tareas y proyecto_usuarios (HU-08)
        $proyecto->eliminar();

        header('Location: /dashboard');
        exit;
    }

    public static function proyecto(Router $router)
    {
        session_start();
        isAuth();

        $token = $_GET['id'] ?? '';
        if (!$token) {
            header('Location: /dashboard');
            exit;
        }

        $proyecto = Proyecto::where('url', $token);
        if (!$proyecto) {
            header('Location: /dashboard');
            exit;
        }

        $rol = ProyectoUsuario::obtenerRolEnProyecto($proyecto, (int) $_SESSION['id']);
        if (is_null($rol)) {
            http_response_code(403);
            echo '403 Forbidden - No tienes acceso a este proyecto';
            return;
        }

        $miembros = ProyectoUsuario::obtenerMiembros((int) $proyecto->id);

        $router->render('dashboard/proyecto', [
            'titulo' => $proyecto->proyecto,
            'proyecto' => $proyecto,
            'esAdmin' => $rol === ProyectoUsuario::ROL_ADMIN,
            'miembros' => $miembros
        ]);
    }

    public static function agregar_miembro()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        session_start();
        header('Content-Type: application/json');

        if (!isset($_SESSION['id'])) {
            http_response_code(403);
            echo json_encode(['tipo' => 'error', 'mensaje' => 'No autenticado']);
            return;
        }

        $proyecto = Proyecto::where('url', $_POST['proyectoId'] ?? '');
        if (!$proyecto || ProyectoUsuario::obtenerRolEnProyecto($proyecto, (int) $_SESSION['id']) !== ProyectoUsuario::ROL_ADMIN) {
            http_response_code(403);
            echo json_encode(['tipo' => 'error', 'mensaje' => 'No tienes permisos para agregar miembros']);
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $usuario = Usuario::where('email', $email);

        if (!$usuario || !$usuario->confirmado) {
            http_response_code(404);
            echo json_encode(['tipo' => 'error', 'mensaje' => 'Usuario no encontrado o no confirmado']);
            return;
        }

        $yaEsMiembro = ProyectoUsuario::obtenerRolEnProyecto($proyecto, (int) $usuario->id);
        if (!is_null($yaEsMiembro)) {
            http_response_code(409);
            echo json_encode(['tipo' => 'error', 'mensaje' => 'El usuario ya participa en este proyecto']);
            return;
        }

        $membresia = new ProyectoUsuario([
            'proyectoId' => $proyecto->id,
            'usuarioId' => $usuario->id,
            'rolId' => ProyectoUsuario::ROL_MIEMBRO
        ]);
        $membresia->guardar();

        echo json_encode([
            'tipo' => 'exito',
            'mensaje' => 'Miembro agregado correctamente',
            'miembro' => [
                'id' => (int) $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rolId' => ProyectoUsuario::ROL_MIEMBRO
            ]
        ]);
    }

    public static function perfil(Router $router)
    {
        session_start();
        isAuth();
        $alertas = [];
        $usuario = Usuario::find($_SESSION['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validar_perfil();

            if (empty($alertas)) {
                $existeUsuario = Usuario::where('email', $usuario->email);

                if ($existeUsuario && (int) $existeUsuario->id !== (int) $usuario->id) {
                    Usuario::setAlerta('error', 'Email no valido, ya pertenece a otro usuario');
                    $alertas = Usuario::getAlertas();
                } else {
                    $usuario->guardar();
                    Usuario::setAlerta('exito', 'Guardado Correctamente');
                    $alertas = Usuario::getAlertas();
                    $_SESSION['nombre'] = $usuario->nombre;
                }
            }
        }

        $router->render('dashboard/perfil', [
            'titulo' => 'Perfil',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function cambiar_password(Router $router)
    {
        session_start();
        isAuth();
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = Usuario::find($_SESSION['id']);
            $usuario->sincronizar($_POST);
            $alertas = $usuario->nuevo_password();

            if (empty($alertas)) {
                $resultado = $usuario->comprobar_password();

                if ($resultado) {
                    $usuario->password = $usuario->password_nuevo;
                    unset($usuario->password_actual);
                    unset($usuario->password_nuevo);

                    $usuario->hashPassword();
                    $resultado = $usuario->guardar();

                    if ($resultado) {
                        Usuario::setAlerta('exito', 'Password Guardado Correctamente');
                        $alertas = Usuario::getAlertas();
                    }
                } else {
                    Usuario::setAlerta('error', 'Password Incorrecto');
                    $alertas = Usuario::getAlertas();
                }
            }
        }

        $router->render('dashboard/cambiar-password', [
            'titulo' => 'Cambiar Password',
            'alertas' => $alertas
        ]);
    }
}
