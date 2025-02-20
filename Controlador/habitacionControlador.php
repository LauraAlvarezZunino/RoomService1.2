<?php

require_once 'Modelo/habitacion.php';
require_once 'Controlador/reservaControlador.php'; 

class HabitacionControlador
{
    private $habitaciones = [];

    private $archivoJson = 'habitacion.json';

    private $reservasControlador; 

    public function __construct()
    {
        $this->cargarDesdeJSON();
        $this->reservasControlador = new ReservaControlador($this);
    }

    // CRUD

    public function agregarHabitacion($habitacion)
    {
        $this->habitaciones[] = $habitacion;
        $this->guardarEnJSON();
    }

    public function obtenerHabitaciones()
    {
        return $this->habitaciones;
    }

    public function buscarHabitacionPorNumero($numero)
    {
        foreach ($this->habitaciones as $habitacion) {
            if ($habitacion->getNumero() == $numero) {
                return $habitacion;
            }
        }

        return null; // si no se encuentra la habitación
    }

    public function buscarPorTipo($tipo)
    {
        $resultados = [];
        $tipo = strtolower($tipo); // starlower pasa a minuscula

        foreach ($this->habitaciones as $habitacion) {
            if (strtolower($habitacion->getTipo()) == $tipo) {
                $resultados[] = $habitacion;
            }
        }

        return $resultados;
    }

    public function actualizarHabitacion($numero, $nuevosDatos)
    {
        foreach ($this->habitaciones as &$habitacion) {
            if ($habitacion->getNumero() == $numero) {
                if (isset($nuevosDatos['tipo'])) { //isset chequea que no es nulo
                    $habitacion->setTipo($nuevosDatos['tipo']);
                }

                if (isset($nuevosDatos['precio'])) {
                    $habitacion->setPrecio($nuevosDatos['precio']);
                }

                $this->guardarEnJSON();
                return true;
            }
        }

        return false;
    }

    public function eliminarHabitacion($habitacionId)
    {
        // Verificar si la habitación existe
        $habitacionExistente = $this->buscarHabitacionPorNumero($habitacionId);

        if (!$habitacionExistente) {
            return "Error: No existe una habitación con el número $habitacionId.";
        }

        // Primero, buscar todas las reservas asociadas a la habitación
        $reservasAsociadas = $this->reservasControlador->mostrarReservasPorHabitacion($habitacionId);

        // Crear notificaciones y eliminar las reservas asociadas
        foreach ($reservasAsociadas as $reserva) {
            // Crear notificación para el usuario
            $notificacionControlador = new NotificacionControlador();
            $mensaje = "Tu reserva (ID: {$reserva['id']}) para la habitación {$habitacionId} fue cancelada porque la habitación fue eliminada.";

            // Crear la notificación
            $notificacion = new Notificacion($reserva['id'], $mensaje, $reserva['usuarioDni']);
            $notificacionControlador->guardarNotificacion($notificacion);

            // Eliminar la reserva
            $this->reservasControlador->eliminarReserva($reserva['id']);
        }

        // Ahora eliminar la habitación
        $habitacionesFiltradas = array_filter($this->habitaciones, function ($habitacion) use ($habitacionId) {
            return $habitacion->getNumero() !== $habitacionId; // Filtrar la habitación a eliminar
        });

        // Verificar si la habitación fue eliminada
        if (count($habitacionesFiltradas) < count($this->habitaciones)) {
            // Guardar las habitaciones restantes
            $this->habitaciones = array_values($habitacionesFiltradas); // Reindexar el array
            $this->guardarEnJSON(); // Guardar cambios en el archivo JSON

            return "Habitación y reservas asociadas eliminadas exitosamente.";
        } else {
            return "Error: No se pudo eliminar la habitación con el número $habitacionId.";
        }
    }
    // Json

    public function guardarEnJSON()
    {
        $habitacionesArray = [];

        foreach ($this->habitaciones as $habitacion) {
            $habitacionesArray[] = $this->habitacionToArray($habitacion);
        }

        $jsonHabitacion = json_encode(['habitacion' => $habitacionesArray], JSON_PRETTY_PRINT);
        file_put_contents($this->archivoJson, $jsonHabitacion);
    }

    public function habitacionToArray($habitacion)
    {
        return [
            'numero' => $habitacion->getNumero(),
            'tipo' => $habitacion->getTipo(),
            'precio' => $habitacion->getPrecio(),
        ];
    }

    public function cargarDesdeJSON()
    {
        if (file_exists($this->archivoJson)) { //existe?
            $jsonHabitacion = file_get_contents($this->archivoJson); //lo lee y lo guarda
            $habitacionesArray = json_decode($jsonHabitacion, true)['habitacion'];
            $this->habitaciones = []; // Asegura que se vacie el array antes de cargar los datos

            foreach ($habitacionesArray as $habitacionData) {
                $habitacion = new Habitacion();
                $habitacion->setNumero($habitacionData['numero']);
                $habitacion->setTipo($habitacionData['tipo']);
                $habitacion->setPrecio($habitacionData['precio']);
                $this->habitaciones[] = $habitacion;
            }
        }
    }
}
