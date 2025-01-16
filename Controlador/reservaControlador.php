<?php

include_once 'Modelo/reserva.php';
include_once 'usuarioControlador.php';
include_once 'habitacionControlador.php';

class ReservaControlador
{
    private $reservas = [];

    private $reservaJson = 'reservas.json';

    private $id = 1; 

    private $habitacionesGestor;

    public function __construct($habitacionesGestor)
    {
        $this->habitacionesGestor = $habitacionesGestor;
        $this->cargarDesdeJSON();
    }

    public function generarNuevoId()
    {
        return $this->id++;
    }

    public function agregarReserva(Reserva $reserva)
    {
        $habitacion = $this->habitacionesGestor->buscarHabitacionPorNumero($reserva->getHabitacion()->getNumero());

        if (!$habitacion) {
            echo "La habitación seleccionada no existe.\n";
            return false;
        }

        foreach ($this->reservas as $existeReserva) {
            if ($existeReserva->getHabitacion()->getNumero() == $habitacion->getNumero() &&
                !($reserva->getFechaFin() < $existeReserva->getFechaInicio() ||
                  $reserva->getFechaInicio() > $existeReserva->getFechaFin())) {

                echo "La habitación número {$habitacion->getNumero()} ya está reservada en las fechas solicitadas ({$reserva->getFechaInicio()} a {$reserva->getFechaFin()}).\n";

                $habitacionesAlternativas = $this->sugerirHabitacionesAlternativas($habitacion->getTipo(), $reserva->getFechaInicio(), $reserva->getFechaFin());
                if (!empty($habitacionesAlternativas)) {
                    echo "Habitaciones alternativas disponibles para las fechas solicitadas:\n";
                    foreach ($habitacionesAlternativas as $altHabitacion) {
                        echo "- Habitación Número: " . $altHabitacion->getNumero() . ", Tipo: " . $altHabitacion->getTipo() . ", Precio: " . $altHabitacion->getPrecio() . "\n";
                    }
                } else {
                    echo "No se encontraron habitaciones alternativas para las fechas solicitadas.\n";
                }

                return false; // No se creó la reserva
            }
        }

        // Crear la reserva si no hay conflictos
        //echo "Reserva creada con éxito.\n";
        $this->reservas[] = $reserva;
        $this->guardarEnJSON();
        return true;
    }

    public function obtenerReservas()
    {
        return $this->reservas;
    }

    public function modificarReserva($id, $nuevaFechaInicio, $nuevaFechaFin, $nuevaHabitacion, $nuevoCosto)
    {
        $reserva = $this->buscarReservaPorId($id); 
        if ($reserva) {
            $reserva->setFechaInicio($nuevaFechaInicio);
            $reserva->setFechaFin($nuevaFechaFin);
            $reserva->setHabitacion($nuevaHabitacion);
            $reserva->setCosto($nuevoCosto);
            $this->guardarEnJSON();
        } else {
            echo "Reserva no encontrada.\n";
        }
    }

    public function eliminarReserva($id)
    {
        foreach ($this->reservas as $indice => $reserva) {
            if ($reserva->getId() == $id) {
                unset($this->reservas[$indice]);
                $this->reservas = array_values($this->reservas); // reposicionamos el array para que no quede un lugar vacio
                $this->guardarEnJSON();

                return true;
            }
        }

        return false;
    }

    public function buscarReservaPorId($id)
    {
        foreach ($this->reservas as $reserva) {
            if ($reserva->getId() == $id) {
                return $reserva;
            }
        }

        return null;
    }

    // Guardar reservas en el archivo JSON
    public function guardarEnJSON()
    {
        $reservasArray = [];

        foreach ($this->reservas as $reserva) {
            $reservasArray[] = [
                'id' => $reserva->getId(),
                'fechaInicio' => $reserva->getFechaInicio(),
                'fechaFin' => $reserva->getFechaFin(),
                'habitacion' => $reserva->getHabitacion()->getNumero(), // guardamos solo el número de la habitación
                'costo' => $reserva->getCosto(),
                'usuarioDni' => $reserva->getUsuarioDni(), 
                "notificaciones" => $reserva->getNotificaciones()
            ];
        }
        
        $datosNuevos = ['reservas' => $reservasArray]; //creo arreglo asociativo para guardar las reservas
        file_put_contents($this->reservaJson, json_encode($datosNuevos, JSON_PRETTY_PRINT)); //lo convierto a json y guardo

    }

    public function cargarDesdeJSON()
    {
        if (file_exists($this->reservaJson)) {
            $json = file_get_contents($this->reservaJson); // Lee contenido del archivo
            $data = json_decode($json, true); // Convierte el JSON en un array
    
            if (isset($data['reservas'])) {
                $reservasArray = $data['reservas'];
            } else {
                $reservasArray = []; // Inicializa como un array vacío si no existe
            }
    
            foreach ($reservasArray as $reservaData) {
                $usuarioDni = isset($reservaData['usuarioDni']) ? $reservaData['usuarioDni'] : null; // Asigna usuarioDni si existe, de lo contrario null
                $habitacion = $this->habitacionesGestor->buscarHabitacionPorNumero($reservaData['habitacion']);
                
                if (null === $habitacion) {
                    echo "Advertencia: La habitación número {$reservaData['habitacion']} no fue encontrada. Se omitirá esta reserva.\n";
                    continue; // Omite la reserva que no tiene habitación y continúa con la siguiente
                }
    
                $notificaciones = isset($reservaData['notificaciones']) ? $reservaData['notificaciones'] : []; // Inicializa notificaciones si no existen
    
                $reserva = new Reserva(
                    $reservaData['id'],
                    $reservaData['fechaInicio'],
                    $reservaData['fechaFin'],
                    $habitacion,
                    $reservaData['costo'],
                    $usuarioDni
                );
    
                // Agregar notificaciones a la reserva
                foreach ($notificaciones as $notificacion) {
                    $reserva->setNotificacion($notificacion); // Asumiendo que existe un método agregarNotificacion
                }
    
                $this->reservas[] = $reserva;
    
                // Asegurar que el ID esté actualizado
                if ($this->id < $reserva->getId() + 1) {
                    $this->id = $reserva->getId() + 1;
                }
            }
        }
    }

    public function sugerirHabitacionesAlternativas($tipo, $fechaInicio, $fechaFin)
    {
        $habitacionesDisponibles = [];

        foreach ($this->habitacionesGestor->buscarPorTipo($tipo) as $habitacion) {
            $disponible = true;

            foreach ($this->reservas as $reserva) {
                if ($reserva->getHabitacion()->getNumero() == $habitacion->getNumero() &&
                    !($fechaFin < $reserva->getFechaInicio() || $fechaInicio > $reserva->getFechaFin())) {
                    $disponible = false;
                    break;
                }
            }

            if ($disponible) {
                $habitacionesDisponibles[] = $habitacion;
            }
        }

        return $habitacionesDisponibles;
    }
}