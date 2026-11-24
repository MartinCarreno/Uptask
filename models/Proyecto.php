<?php

namespace Model;

class Proyecto extends ActiveRecord
{
    protected static $tabla = 'proyectos';
    protected static $columnasDB = ['id', 'proyecto', 'cliente', 'descripcion', 'url', 'propietarioId'];

    public $id;
    public $proyecto;
    public $cliente;
    public $descripcion;
    public $url;
    public $propietarioId;
    public $creado_en;
    public $actualizado_en;
    public $rolId;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->proyecto = trim($args['proyecto'] ?? '');
        $this->cliente = trim($args['cliente'] ?? '');
        $this->descripcion = isset($args['descripcion']) && trim($args['descripcion']) !== ''
            ? trim($args['descripcion'])
            : null;
        $this->url = $args['url'] ?? '';
        $this->propietarioId = $args['propietarioId'] ?? null;
        $this->creado_en = $args['creado_en'] ?? null;
        $this->actualizado_en = $args['actualizado_en'] ?? null;
        $this->rolId = $args['rolId'] ?? null;
    }

    public function validarProyecto(): array
    {
        if (!$this->proyecto) {
            self::$alertas['error'][] = 'El nombre del Proyecto es Obligatorio';
        }
        if (strlen($this->proyecto) > 80) {
            self::$alertas['error'][] = 'El nombre del Proyecto debe tener menos de 80 caracteres';
        }
        if (!$this->cliente) {
            self::$alertas['error'][] = 'El nombre del Cliente es Obligatorio';
        }
        if (strlen($this->cliente) > 80) {
            self::$alertas['error'][] = 'El nombre del Cliente debe tener menos de 80 caracteres';
        }

        return self::$alertas;
    }
}
