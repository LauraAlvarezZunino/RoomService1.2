<?php 
include_once 'Modelo/notificacion.php';

class NotificacionControlador
{
    private $archivoNotificaciones;

    public function __construct($archivoNotificaciones = 'notificaciones.json')
    {
        $this->archivoNotificaciones = $archivoNotificaciones;
    }

    // Cargar notificaciones desde el archivo JSON
    public function cargarNotificaciones()
    {
        if (!file_exists($this->archivoNotificaciones)) {
            return [];
        }

        $contenido = file_get_contents($this->archivoNotificaciones);
        $notificaciones = json_decode($contenido, true);

        return is_array($notificaciones) ? $notificaciones : [];
    }

    // Guardar una notificación en el archivo JSON
    public function guardarNotificacion(Notificacion $notificacion)
    {
        $notificaciones = $this->cargarNotificaciones();
        $notificaciones[] = $notificacion->toArray();

        file_put_contents($this->archivoNotificaciones, json_encode($notificaciones, JSON_PRETTY_PRINT));
    }

    // Mostrar todas las notificaciones de una reserva específica
    public function mostrarNotificaciones($reservaId)
    {
        $notificaciones = $this->cargarNotificaciones();
        $notificacionesReserva = array_filter($notificaciones, function($notificacion) use ($reservaId) {
            return isset($notificacion['reserva_id']) && $notificacion['reserva_id'] == $reservaId;
        });

        return array_values($notificacionesReserva); // Reindexa el array
    }

    // Método para eliminar las notificaciones de un usuario
    public function eliminarNotificacionesPorDni($dni)
    {
        $notificaciones = $this->cargarNotificaciones();

        // Filtrar las notificaciones que no pertenecen al usuario con el DNI dado
        $notificaciones = array_filter($notificaciones, function($notificacion) use ($dni) {
            return isset($notificacion['usuario_dni']) && $notificacion['usuario_dni'] !== $dni;
        });

        // Guardamos nuevamente las notificaciones restantes
        file_put_contents($this->archivoNotificaciones, json_encode(array_values($notificaciones), JSON_PRETTY_PRINT));
    }
}



