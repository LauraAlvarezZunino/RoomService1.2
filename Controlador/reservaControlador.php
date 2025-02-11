<?php

require_once 'Modelo/reserva.php';
require_once 'usuarioControlador.php';
require_once 'habitacionControlador.php';

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
            if (
                $existeReserva->getHabitacion()->getNumero() == $habitacion->getNumero() &&
                !($reserva->getFechaFin() < $existeReserva->getFechaInicio() ||
                    $reserva->getFechaInicio() > $existeReserva->getFechaFin())
            ) {

                echo "La habitación número {$habitacion->getNumero()} ya está reservada en las fechas solicitadas ({$reserva->getFechaInicio()} a {$reserva->getFechaFin()}).\n";

                $habitacionesAlternativas = $this->sugerirHabitacionesAlternativas($habitacion->getTipo(), $reserva->getFechaInicio(), $reserva->getFechaFin());
                if (!empty($habitacionesAlternativas)) {
                    echo "Habitaciones alternativas disponibles para las fechas solicitadas:\n";
                    foreach ($habitacionesAlternativas as $altHabitacion) {
                        echo "- Habitación Número: " . $altHabitacion->getNumero() . ", Tipo: " . $altHabitacion->getTipo() . ", Precio: " . $altHabitacion->getPrecio() . "\n";
                    }
                } else {
                    $habitacionesCercanas = $this->sugerirHabitacionesCercanas($habitacion->getTipo(), $reserva->getFechaInicio(), $reserva->getFechaFin());
                    if (!empty($habitacionesCercanas)) {
                        echo "No hay habitaciones disponibles para las fechas solicitadas.\n", "Habitaciones disponibles en fechas cercanas:\n";
                        foreach ($habitacionesCercanas as $cercana) {
                            echo "- Habitación Número: " . $cercana['habitacion']->getNumero() . ", Tipo: " . $cercana['habitacion']->getTipo() . ", Precio: " . $cercana['habitacion']->getPrecio() . ", Disponible desde: " . $cercana['fechaInicio'] . "\n";
                        }
                    } else {
                        echo "No se encontraron habitaciones disponibles en fechas cercanas.\n";
                    }
                }

                return false;
            }
        }

        $this->reservas[] = $reserva;
        $this->guardarEnJSON();
        return true;
    }

    public function sugerirHabitacionesAlternativas($tipo, $fechaInicio, $fechaFin)
    {
        $habitacionesDisponibles = [];

        foreach ($this->habitacionesGestor->buscarPorTipo($tipo) as $habitacion) {
            $disponible = true;

            foreach ($this->reservas as $reserva) {
                if (
                    $reserva->getHabitacion()->getNumero() == $habitacion->getNumero() &&
                    !($fechaFin < $reserva->getFechaInicio() || $fechaInicio > $reserva->getFechaFin())
                ) {
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

    public function sugerirHabitacionesCercanas($tipo, $fechaInicio, $fechaFin)
    {
        $habitacionesCercanas = [];

        foreach ($this->habitacionesGestor->buscarPorTipo($tipo) as $habitacion) {
            $fechasOcupadas = [];

            foreach ($this->reservas as $reserva) {
                if ($reserva->getHabitacion()->getNumero() == $habitacion->getNumero()) {
                    $fechasOcupadas[] = [
                        'inicio' => $reserva->getFechaInicio(),
                        'fin' => $reserva->getFechaFin()
                    ];
                }
            }

            usort($fechasOcupadas, function ($a, $b) {
                return strtotime($a['inicio']) - strtotime($b['inicio']);
            });

            $fechaInicioDisponible = $fechaInicio;
            $fechaFinDisponible = $fechaFin;

            foreach ($fechasOcupadas as $rango) {
                if (strtotime($fechaInicioDisponible) < strtotime($rango['inicio'])) {
                    $fechaFinDisponible = date('Y-m-d', strtotime($rango['inicio'] . ' -1 day'));
                    break;
                } elseif (strtotime($fechaInicioDisponible) <= strtotime($rango['fin'])) {
                    $fechaInicioDisponible = date('Y-m-d', strtotime($rango['fin'] . ' +1 day'));
                }
            }

            if (strtotime($fechaInicioDisponible) <= strtotime($fechaFin)) {
                $habitacionesCercanas[] = [
                    'habitacion' => $habitacion,
                    'fechaInicio' => $fechaInicioDisponible,
                    'fechaFin' => $fechaFinDisponible
                ];
            }
        }

        return $habitacionesCercanas;
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

    public function mostrarReservasPorHabitacion($habitacionId)
    {
        $reservasAsociadas = [];

        foreach ($this->reservas as $reserva) {
            if ($reserva->getHabitacion()->getNumero() == $habitacionId) {
                $reservasAsociadas[] = [
                    'id' => $reserva->getId(),
                    'fechaInicio' => $reserva->getFechaInicio(),
                    'fechaFin' => $reserva->getFechaFin(),
                    'costo' => $reserva->getCosto(),
                    'usuarioDni' => $reserva->getUsuarioDni()
                ];
            }
        }

        return $reservasAsociadas;
    }

    function verificarDisponibilidad($numeroHabitacion, $fechaInicio, $fechaFin, $excluirReservaId = null)
    {
        foreach ($this->reservas as $reserva) {
            if ($reserva->getHabitacion()->getNumero() == $numeroHabitacion && $reserva->getId() != $excluirReservaId) {
                $solapa = ($fechaInicio < $reserva->getFechaFin() && $fechaFin > $reserva->getFechaInicio());
                if ($solapa) {
                    return true; // Habitación ocupada
                }
            }
        }
        return false; // Habitación disponible
    }


    public function guardarEnJSON()
    {
        $reservasArray = [];

        foreach ($this->reservas as $reserva) {
            $reservasArray[] = [
                'id' => $reserva->getId(),
                'fechaInicio' => $reserva->getFechaInicio(),
                'fechaFin' => $reserva->getFechaFin(),
                'habitacion' => $reserva->getHabitacion()->getNumero(),
                'costo' => $reserva->getCosto(),
                'usuarioDni' => $reserva->getUsuarioDni()
            ];
        }

        $datosNuevos = ['reservas' => $reservasArray];
        file_put_contents($this->reservaJson, json_encode($datosNuevos, JSON_PRETTY_PRINT));
    }

    public function cargarDesdeJSON()
    {
        if (file_exists($this->reservaJson)) {
            $json = file_get_contents($this->reservaJson);
            $data = json_decode($json, true);

            if (isset($data['reservas'])) {
                $reservasArray = $data['reservas'];
            } else {
                $reservasArray = [];
            }

            foreach ($reservasArray as $reservaData) {
                $usuarioDni = isset($reservaData['usuarioDni']) ? $reservaData['usuarioDni'] : null;
                $habitacion = $this->habitacionesGestor->buscarHabitacionPorNumero($reservaData['habitacion']);

                if (null === $habitacion) {
                    echo "Advertencia: La habitación número {$reservaData['habitacion']} no fue encontrada. Se omitirá esta reserva.\n";
                    continue;
                }

                $notificaciones = isset($reservaData['notificaciones']) ? $reservaData['notificaciones'] : [];

                $reserva = new Reserva(
                    $reservaData['id'],
                    $reservaData['fechaInicio'],
                    $reservaData['fechaFin'],
                    $habitacion,
                    $reservaData['costo'],
                    $usuarioDni
                );



                $this->reservas[] = $reserva;

                if ($this->id < $reserva->getId() + 1) {
                    $this->id = $reserva->getId() + 1;
                }
            }
        }
    }
}
