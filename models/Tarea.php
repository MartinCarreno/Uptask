<?php

namespace Model;

class Tarea extends ActiveRecord
{
    protected static $tabla = 'tareas';
    protected static $columnasDB = ['id', 'nombre', 'estado', 'proyectoId', 'asignadoId'];

    public $id;
    public $nombre;
    public $estado;
    public $proyectoId;
    public $asignadoId;
    public $creado_en;
    public $actualizado_en;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->estado = $args['estado'] ?? 0;
        $this->proyectoId = $args['proyectoId'] ?? null;
        $this->asignadoId = !empty($args['asignadoId']) ? (int) $args['asignadoId'] : null;
        $this->creado_en = $args['creado_en'] ?? null;
        $this->actualizado_en = $args['actualizado_en'] ?? null;
    }
}
