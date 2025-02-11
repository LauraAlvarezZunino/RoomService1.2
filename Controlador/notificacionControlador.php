<?php
require_once 'Modelo/notificacion.php';
class NotificacionControlador
{
    private $archivoNotificaciones;

    public function __construct($archivoNotificaciones = 'notificaciones.json')
    {
        $this->archivoNotificaciones = $archivoNotificaciones;
    }


    public function cargarNotificaciones()
    {
        if (!file_exists($this->archivoNotificaciones)) {
            return [];
        }

        $contenido = file_get_contents($this->archivoNotificaciones);
        $notificaciones = json_decode($contenido, true);

        return is_array($notificaciones) ? $notificaciones : [];
    }

    public function guardarNotificacion(Notificacion $notificacion)
    {
        $notificaciones = $this->cargarNotificaciones();
        $notificaciones[] = $notificacion->toArray();

        file_put_contents($this->archivoNotificaciones, json_encode($notificaciones, JSON_PRETTY_PRINT));
    }


    public function mostrarNotificaciones($reservaId)
    {
        $notificaciones = $this->cargarNotificaciones();
        $notificacionesReserva = array_filter($notificaciones, function ($notificacion) use ($reservaId) {
            return isset($notificacion['reserva_id']) && $notificacion['reserva_id'] == $reservaId;
        });

        return array_values($notificacionesReserva); // Reindexamos el array
    }



    public function mostrarNotificacionesPorDni($dni)
    {
        $notificaciones = $this->cargarNotificaciones();
        $notificacionesUsuario = array_filter($notificaciones, function ($notificacion) use ($dni) {
            return isset($notificacion['usuario_dni']) && $notificacion['usuario_dni'] == $dni;
        });

        
        if (empty($notificacionesUsuario)) {
            return "No hay notificaciones para su usuario";
        }

        return array_values($notificacionesUsuario); 
    }


    public function eliminarNotificacionesPorDni($dni)
    {
        $notificaciones = $this->cargarNotificaciones();
        $notificaciones = array_filter($notificaciones, function ($notificacion) use ($dni) {
            return isset($notificacion['usuario_dni']) && $notificacion['usuario_dni'] !== $dni;
        });

        file_put_contents($this->archivoNotificaciones, json_encode(array_values($notificaciones), JSON_PRETTY_PRINT));
    }
}
