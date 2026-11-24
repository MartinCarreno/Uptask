<?php

namespace Model;

class ActiveRecord
{
    // Base de datos
    protected static $db;
    protected static $tabla = '';
    protected static $columnasDB = [];

    // Alertas y mensajes
    protected static $alertas = [];

    public static function setDB($database)
    {
        self::$db = $database;
    }

    public static function iniciarTransaccion()
    {
        self::$db->begin_transaction();
    }

    public static function confirmarTransaccion()
    {
        self::$db->commit();
    }

    public static function revertirTransaccion()
    {
        self::$db->rollback();
    }

    public static function setAlerta($tipo, $mensaje)
    {
        static::$alertas[$tipo][] = $mensaje;
    }

    public static function getAlertas()
    {
        return static::$alertas;
    }

    public function validar()
    {
        static::$alertas = [];
        return static::$alertas;
    }

    public function guardar()
    {
        if (!is_null($this->id)) {
            return $this->actualizar();
        }
        return $this->crear();
    }

    public static function all()
    {
        $query = "SELECT * FROM " . static::$tabla;
        return self::consultarSQL($query);
    }

    public static function find($id)
    {
        $id = self::$db->escape_string((string) $id);
        $query = "SELECT * FROM " . static::$tabla . " WHERE id = '{$id}' LIMIT 1";
        $resultado = self::consultarSQL($query);
        return array_shift($resultado);
    }

    public static function get($limite)
    {
        $limite = (int) $limite;
        $query = "SELECT * FROM " . static::$tabla . " LIMIT {$limite}";
        $resultado = self::consultarSQL($query);
        return array_shift($resultado);
    }

    public static function where($columna, $valor)
    {
        $valor = self::$db->escape_string((string) $valor);
        $query = "SELECT * FROM " . static::$tabla . " WHERE {$columna} = '{$valor}' LIMIT 1";
        $resultado = self::consultarSQL($query);
        return array_shift($resultado);
    }

    public static function whereArray(array $condiciones)
    {
        $partes = [];
        foreach ($condiciones as $columna => $valor) {
            $valorEscapado = self::$db->escape_string((string) $valor);
            $partes[] = "{$columna} = '{$valorEscapado}'";
        }
        $query = "SELECT * FROM " . static::$tabla . " WHERE " . join(' AND ', $partes) . " LIMIT 1";
        $resultado = self::consultarSQL($query);
        return array_shift($resultado);
    }

    public static function belongsTo($columna, $valor)
    {
        $valor = self::$db->escape_string((string) $valor);
        $query = "SELECT * FROM " . static::$tabla . " WHERE {$columna} = '{$valor}'";
        return self::consultarSQL($query);
    }

    public static function SQL($consulta)
    {
        return self::consultarSQL($consulta);
    }

    public function crear()
    {
        $atributos = $this->sanitizarAtributos();

        $columnas = array_keys($atributos);
        $valores = [];
        foreach ($atributos as $value) {
            $valores[] = is_null($value) ? "NULL" : "'{$value}'";
        }

        $query = "INSERT INTO " . static::$tabla . " (";
        $query .= join(', ', $columnas);
        $query .= ") VALUES (";
        $query .= join(', ', $valores);
        $query .= ")";

        $resultado = self::$db->query($query);

        return [
            'resultado' => $resultado,
            'id' => self::$db->insert_id
        ];
    }

    public function actualizar()
    {
        $atributos = $this->sanitizarAtributos();

        $valores = [];
        foreach ($atributos as $key => $value) {
            $valores[] = is_null($value) ? "{$key}=NULL" : "{$key}='{$value}'";
        }

        $query = "UPDATE " . static::$tabla . " SET ";
        $query .= join(', ', $valores);
        $query .= " WHERE id = '" . self::$db->escape_string((string) $this->id) . "' LIMIT 1";

        return self::$db->query($query);
    }

    public function eliminar()
    {
        $query = "DELETE FROM " . static::$tabla . " WHERE id = '" . self::$db->escape_string((string) $this->id) . "' LIMIT 1";
        return self::$db->query($query);
    }

    public static function consultarSQL($query)
    {
        $resultado = self::$db->query($query);

        $array = [];
        while ($registro = $resultado->fetch_assoc()) {
            $array[] = static::crearObjeto($registro);
        }

        $resultado->free();
        return $array;
    }

    protected static function crearObjeto($registro)
    {
        $objeto = new static;

        foreach ($registro as $key => $value) {
            if (property_exists($objeto, $key)) {
                $objeto->$key = $value;
            }
        }

        return $objeto;
    }

    public function atributos()
    {
        $atributos = [];
        foreach (static::$columnasDB as $columna) {
            if ($columna === 'id') continue;
            $atributos[$columna] = $this->$columna;
        }
        return $atributos;
    }

    public function sanitizarAtributos()
    {
        $atributos = $this->atributos();
        $sanitizado = [];
        foreach ($atributos as $key => $value) {
            $sanitizado[$key] = is_null($value) ? null : self::$db->escape_string((string) $value);
        }
        return $sanitizado;
    }

    public function sincronizar($args = [])
    {
        foreach ($args as $key => $value) {
            if (property_exists($this, $key) && !is_null($value)) {
                $this->$key = $value;
            }
        }
    }
}
