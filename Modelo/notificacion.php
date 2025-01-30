<?php
class Notificacion
{
    private $reservaId;
    private $mensaje;
    private $usuarioDni;  // Agregamos el campo para el DNI del usuario

    public function __construct($reservaId, $mensaje, $usuarioDni)
    {
        $this->reservaId = $reservaId;
        $this->mensaje = $mensaje;
        $this->usuarioDni = $usuarioDni;  // Inicializamos el DNI del usuario
    }

    public function getReservaId()
    {
        return $this->reservaId;
    }

    public function getMensaje()
    {
        return $this->mensaje;
    }



    public function getUsuarioDni()
    {
        return $this->usuarioDni;
    }

    // Método para convertir el objeto a un array asociativo para guardarlo en JSON
    public function toArray()
    {
        return [
            'reserva_id' => $this->reservaId,
            'notificacion' => $this->mensaje,
            'usuario_dni' => $this->usuarioDni,  
        ];
    }
}
