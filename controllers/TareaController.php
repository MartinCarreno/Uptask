<?php

namespace Controllers;

use Model\Proyecto;
use Model\ProyectoUsuario;
use Model\Tarea;

class TareaController
{
    private static function responderForbidden(string $mensaje = 'No tienes permisos para realizar esta accion'): void
    {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'tipo' => 'error',
            'mensaje' => $mensaje
        ]);
    }

    public static function index()
    {
        session_start();
        header('Content-Type: application/json');

        $proyectoUrl = $_GET['id'] ?? '';
        if (!$proyectoUrl || !isset($_SESSION['id'])) {
            self::responderForbidden('Acceso no autorizado');
            return;
        }

        $proyecto = Proyecto::where('url', $proyectoUrl);
        if (!$proyecto) {
            self::responderForbidden('Proyecto no encontrado o sin permisos');
            return;
        }

        $rol = ProyectoUsuario::obtenerRolEnProyecto($proyecto, (int) $_SESSION['id']);
        if (is_null($rol)) {
            self::responderForbidden('No perteneces a este proyecto');
            return;
        }

        $tareas = Tarea::belongsTo('proyectoId', $proyecto->id);

        echo json_encode([
            'tareas' => $tareas,
            'rolId' => $rol
        ]);
    }

    public static function crear()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        session_start();
        header('Content-Type: application/json');

        if (!isset($_SESSION['id'])) {
            self::responderForbidden('Sesion invalida');
            return;
        }

        $proyecto = Proyecto::where('url', $_POST['proyectoId'] ?? '');
        if (!$proyecto || ProyectoUsuario::obtenerRolEnProyecto($proyecto, (int) $_SESSION['id']) !== ProyectoUsuario::ROL_ADMIN) {
            self::responderForbidden('Solo el administrador del proyecto puede crear tareas');
            return;
        }

        $tarea = new Tarea($_POST);
        $tarea->proyectoId = $proyecto->id;
        $resultado = $tarea->guardar();

        echo json_encode([
            'tipo' => 'exito',
            'resultado' => $resultado['id'],
            'mensaje' => 'Tarea Creada Correctamente',
            'proyectoId' => $proyecto->id,
            'asignadoId' => $tarea->asignadoId
        ]);
    }

    public static function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        session_start();
        header('Content-Type: application/json');

        if (!isset($_SESSION['id'])) {
            self::responderForbidden('Sesion invalida');
            return;
        }

        $proyecto = Proyecto::where('url', $_POST['proyectoId'] ?? '');
        if (!$proyecto) {
            self::responderForbidden('Proyecto no valido');
            return;
        }

        $rol = ProyectoUsuario::obtenerRolEnProyecto($proyecto, (int) $_SESSION['id']);
        if (is_null($rol)) {
            self::responderForbidden('No tienes permisos sobre este proyecto');
            return;
        }

        $tareaExistente = Tarea::find($_POST['id'] ?? 0);
        if (!$tareaExistente || (int) $tareaExistente->proyectoId !== (int) $proyecto->id) {
            self::responderForbidden('La tarea no pertenece a este proyecto');
            return;
        }

        // El Miembro de Equipo solo puede cambiar el estado (pendiente/completado)
        if ($rol === ProyectoUsuario::ROL_MIEMBRO) {
            $tareaExistente->estado = (int) ($_POST['estado'] ?? $tareaExistente->estado);
        } else {
            $tareaExistente->nombre = $_POST['nombre'] ?? $tareaExistente->nombre;
            $tareaExistente->estado = (int) ($_POST['estado'] ?? $tareaExistente->estado);
            if (array_key_exists('asignadoId', $_POST)) {
                $tareaExistente->asignadoId = !empty($_POST['asignadoId']) ? (int) $_POST['asignadoId'] : null;
            }
        }

        $resultado = $tareaExistente->guardar();
        if ($resultado) {
            $respuesta = [
                'tipo' => 'exito',
                'id' => $tareaExistente->id,
                'proyectoId' => $proyecto->id,
                'mensaje' => 'Actualizado Correctamente'
            ];
            echo json_encode(['respuesta' => $respuesta]);
        }
    }

    public static function eliminar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        session_start();
        header('Content-Type: application/json');

        if (!isset($_SESSION['id'])) {
            self::responderForbidden('Sesion invalida');
            return;
        }

        $proyecto = Proyecto::where('url', $_POST['proyectoId'] ?? '');
        if (!$proyecto || ProyectoUsuario::obtenerRolEnProyecto($proyecto, (int) $_SESSION['id']) !== ProyectoUsuario::ROL_ADMIN) {
            self::responderForbidden('Solo el administrador del proyecto puede eliminar tareas');
            return;
        }

        $tareaExistente = Tarea::find($_POST['id'] ?? 0);
        if (!$tareaExistente || (int) $tareaExistente->proyectoId !== (int) $proyecto->id) {
            self::responderForbidden('La tarea no pertenece a este proyecto');
            return;
        }

        $resultado = $tareaExistente->eliminar();

        echo json_encode([
            'resultado' => $resultado,
            'mensaje' => 'Eliminado Correctamente',
            'tipo' => 'exito'
        ]);
    }
}
