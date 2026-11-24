<?php

namespace Model;

class ProyectoUsuario extends ActiveRecord
{
    protected static $tabla = 'proyecto_usuarios';
    protected static $columnasDB = ['id', 'proyectoId', 'usuarioId', 'rolId'];

    public const ROL_ADMIN = 1;
    public const ROL_MIEMBRO = 2;

    public $id;
    public $proyectoId;
    public $usuarioId;
    public $rolId;
    public $agregado_en;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->proyectoId = $args['proyectoId'] ?? null;
        $this->usuarioId = $args['usuarioId'] ?? null;
        $this->rolId = $args['rolId'] ?? self::ROL_MIEMBRO;
        $this->agregado_en = $args['agregado_en'] ?? null;
    }

    public static function obtenerRolEnProyecto(Proyecto $proyecto, int $usuarioId): ?int
    {
        if ((int) $proyecto->propietarioId === $usuarioId) {
            return self::ROL_ADMIN;
        }

        $membresia = self::whereArray([
            'proyectoId' => $proyecto->id,
            'usuarioId' => $usuarioId
        ]);

        return $membresia ? (int) $membresia->rolId : null;
    }

    public static function obtenerMiembros(int $proyectoId): array
    {
        $query = "SELECT u.id, u.nombre, u.email, pu.rolId
                  FROM proyecto_usuarios pu
                  INNER JOIN usuarios u ON u.id = pu.usuarioId
                  WHERE pu.proyectoId = {$proyectoId}
                  ORDER BY pu.rolId ASC, u.nombre ASC";

        $resultado = self::$db->query($query);
        $miembros = [];
        while ($fila = $resultado->fetch_assoc()) {
            $miembros[] = $fila;
        }
        $resultado->free();

        return $miembros;
    }
}
