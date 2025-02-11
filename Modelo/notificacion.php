<?php
class Notificacion
{
    private $reservaId;
    private $mensaje;
    private $usuarioDni;  

    public function __construct($reservaId, $mensaje, $usuarioDni)
    {
        $this->reservaId = $reservaId;
        $this->mensaje = $mensaje;
        $this->usuarioDni = $usuarioDni;  
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


    public function toArray()
    {
        return [
            'reserva_id' => $this->reservaId,
            'notificacion' => $this->mensaje,
            'usuario_dni' => $this->usuarioDni,
        ];
    }
}
