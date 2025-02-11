<?php

//RESERVAS
function modificarReserva($reservasGestor, $habitacionesGestor, $esAdmin = false, $usuario = null)
{
    global $dniGuardado;
    $notificacionControlador = new NotificacionControlador();

    echo 'Ingrese el ID de la reserva que desea modificar: ';
    $id = trim(fgets(STDIN));
    $reserva = $reservasGestor->buscarReservaPorId($id);

    // Verificar si la reserva existe y si el usuario es el dueño, a menos que sea un administrador
    if (!$reserva || (!$esAdmin && $reserva->getUsuarioDni() !== $dniGuardado)) {
        echo "Reserva no encontrada o no tiene permisos para modificar esta reserva.\n";
        return;
    }

    echo "Modificando Reserva ID: {$reserva->getId()}\n";
    echo 'Fecha Inicio actual: ' . $reserva->getFechaInicio() . "\n";
    echo 'Fecha Fin actual: ' . $reserva->getFechaFin() . "\n";
    echo 'Habitación actual: ' . $reserva->getHabitacion()->getNumero() . "\n";
    echo 'Costo actual: $' . $reserva->getCosto() . "\n";

    
    $nuevaFechaInicio = '';
    while (true) {
        echo 'Ingrese la nueva fecha de inicio (YYYY-MM-DD) o deje vacío para mantener la actual: ';
        $nuevaFechaInicio = trim(fgets(STDIN));
        $fechaActual = date('Y-m-d');

        if (empty($nuevaFechaInicio)) {
            $nuevaFechaInicio = $reserva->getFechaInicio();
            break;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $nuevaFechaInicio) && strtotime($nuevaFechaInicio) > strtotime($fechaActual)) {
            break;
        } else {
            echo "La fecha de inicio debe tener el formato YYYY-MM-DD y ser posterior a la fecha actual. Por favor, ingrese una fecha válida.\n";
        }
    }


    $nuevaFechaFin = '';
    while (true) {
        echo 'Ingrese la nueva fecha de fin (YYYY-MM-DD) o deje vacío para mantener la actual: ';
        $nuevaFechaFin = trim(fgets(STDIN));

        if (empty($nuevaFechaFin)) {
            $nuevaFechaFin = $reserva->getFechaFin();
            break;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $nuevaFechaFin) && strtotime($nuevaFechaFin) > strtotime($nuevaFechaInicio)) {
            break;
        } else {
            echo "La fecha de fin debe tener el formato YYYY-MM-DD y ser posterior a la fecha de inicio. Por favor, ingrese una fecha válida.\n";
        }
    }


    $nuevaHabitacion = null;
    while (true) {
        echo 'Ingrese el nuevo número de habitación o deje vacío para mantener la actual: ';
        $nuevoNumeroHabitacion = trim(fgets(STDIN));

        if (empty($nuevoNumeroHabitacion)) {
            $nuevaHabitacion = $reserva->getHabitacion();
            break;
        }

        $nuevaHabitacion = $habitacionesGestor->buscarHabitacionPorNumero($nuevoNumeroHabitacion);
        if (!$nuevaHabitacion) {
            echo "Habitación no encontrada.\n";
            continue;
        }

        // Verificar si la habitación está disponible en las nuevas fechas
        $habitacionOcupada = $reservasGestor->verificarDisponibilidad($nuevaHabitacion->getNumero(), $nuevaFechaInicio, $nuevaFechaFin, $reserva->getId());
        if ($habitacionOcupada) {
            echo "La habitación seleccionada no está disponible en las fechas indicadas.\n";
        } else {
            break;
        }
    }

    // Calcular el nuevo costo
    $nuevoCosto = calcularCostoReserva($nuevaFechaInicio, $nuevaFechaFin, $nuevaHabitacion->getPrecio());

    // Actualizar la reserva con los nuevos valores
    $reserva->setFechaInicio($nuevaFechaInicio);
    $reserva->setFechaFin($nuevaFechaFin);
    $reserva->setHabitacion($nuevaHabitacion);
    $reserva->setCosto($nuevoCosto);

    // Agregar notificación si es un administrador
    if ($esAdmin) {
        $mensaje = "Tu reserva (ID: {$reserva->getId()}) fue modificada por un administrador.";
        $usuarioDni = $usuario ? $usuario->getDni() : $reserva->getUsuarioDni(); 
        $notificacion = new Notificacion($reserva->getId(), $mensaje, $usuarioDni);
        $notificacionControlador->guardarNotificacion($notificacion);
    }

    $reservasGestor->guardarEnJSON();
    echo 'Reserva actualizada correctamente. Nuevo costo: $' . $nuevoCosto . "\n";
}


function mostrarReservas($reservasGestor, $esAdmin = false, $usuario = null)
{
    global $dniGuardado;
    $reservas = $reservasGestor->obtenerReservas();
    $tieneReservas = false;

    $notificacionControlador = new NotificacionControlador();

    foreach ($reservas as $reserva) {
        if ($esAdmin || ($usuario && $reserva->getUsuarioDni() === $dniGuardado)) {
            echo "-------------------------\n";
            echo 'ID: ' . $reserva->getId() . "\n";
            echo 'Fecha Inicio: ' . $reserva->getFechaInicio() . "\n";
            echo 'Fecha Fin: ' . $reserva->getFechaFin() . "\n";
            echo 'Habitación: ' . $reserva->getHabitacion()->getNumero() . ' (' . $reserva->getHabitacion()->getTipo() . ")\n";
            echo 'Costo Total: $' . $reserva->getCosto() . "\n";

            $notificacionesReserva = $notificacionControlador->mostrarNotificaciones($reserva->getId());

            // Filtrar y eliminar duplicados de notificaciones
            $notificacionesReserva = array_unique(array_column($notificacionesReserva, 'notificacion'));

            if (!empty($notificacionesReserva)) {
                echo "Notificaciones:\n";
                foreach ($notificacionesReserva as $notificacion) {
                    echo "- " . trim($notificacion) . "\n";
                }
            }

            echo "-------------------------\n";
            $tieneReservas = true;
        }
    }

    if (!$tieneReservas) {
        echo $esAdmin ? "No hay reservas registradas.\n" : "No tienes reservas registradas.\n";
    }
}


function eliminarReserva($reservasGestor, $usuario = null, $esAdmin = false)
{
    echo 'Ingrese el ID de la reserva que desea eliminar: ';
    $idEliminar = trim(fgets(STDIN));
    $reserva = $reservasGestor->buscarReservaPorId($idEliminar);

    // Si no se encuentra la reserva o el usuario no tiene permisos para eliminarla
    if (!$reserva || (!$esAdmin && (!$usuario || $reserva->getUsuarioDni() !== $usuario->getDni()))) {
        echo "Reserva no encontrada o no pertenece a este usuario.\n";
        return;
    }

    
    if ($esAdmin) {
        $notificacionControlador = new NotificacionControlador();
        $mensaje = "Tu reserva (ID: {$reserva->getId()}) fue eliminada por un administrador.";

        // Si $usuario es null, usa el DNI del dueño de la reserva
        $usuarioDni = $usuario ? $usuario->getDni() : $reserva->getUsuarioDni();

        $notificacion = new Notificacion($reserva->getId(), $mensaje, $usuarioDni);
        $notificacionControlador->guardarNotificacion($notificacion);
    }

   
    $reservasGestor->eliminarReserva($idEliminar);
    echo "Reserva eliminada con éxito.\n";
}



//USUARIOS
function modificarUsuario($usuario, $esAdministrador = false)
{
    global $dniGuardado;
    $usuariosGestor = new UsuarioControlador;

  
    if (!$esAdministrador) {
        $usuario = $usuariosGestor->obtenerUsuarioPorDni($dniGuardado);
        if (!$usuario) {
            echo "Usuario no encontrado o no autorizado.\n";
            return false;
        }
        $id = $usuario->getId();
    } else {
        echo 'Ingrese el ID del usuario que quiere modificar: ';
        $id = trim(fgets(STDIN));
    }

    $usuario = $usuariosGestor->obtenerUsuarioPorId($id);

    if (!$usuario) {
        echo "Usuario no encontrado.\n";
        return false;
    }

    echo "Modificando al usuario con ID: {$usuario->getId()}\n";
    echo "Nombre actual: {$usuario->getNombreApellido()}\n";
    echo "DNI actual: {$usuario->getDni()}\n";
    echo "Email actual: {$usuario->getEmail()}\n";
    echo "Teléfono actual: {$usuario->getTelefono()}\n";

   
    while (true) {
        echo 'Introduce el nuevo nombre (deja vacío para mantener el actual): ';
        $nombreApellido = trim(fgets(STDIN));

        if ($nombreApellido === "" || preg_match("/^[a-zA-Z\s]{3,}$/", $nombreApellido)) {
            break;
        } else {
            echo "Por favor, ingrese solo letras y espacios para el nombre y apellido con un minimo de 3 caracteres.\n";
        }
    }

    while (true) {
        echo 'Introduce el nuevo email (deja vacío para mantener el actual): ';
        $email = trim(fgets(STDIN));

        if ($email === "" || filter_var($email, FILTER_VALIDATE_EMAIL)) {
            break;
        } else {
            echo "Por favor, ingrese un email válido.\n";
        }
    }

    while (true) {
        echo 'Introduce el nuevo teléfono (deja vacío para mantener el actual): ';
        $telefono = trim(fgets(STDIN));

        if ($telefono === "" || preg_match("/^\d{10,11}$/", $telefono)) {
            break;
        } else {
            echo "El teléfono debe contener solo números (10-11 dígitos).\n";
        }
    }

    while (true) {
        echo 'Introduce la nueva clave (deja vacío para mantener la actual): ';
        $clave = trim(fgets(STDIN));
        if ($clave === "" || preg_match("/^[a-zA-Z0-9]{4,8}$/", $clave)) {
            break;
        } else {
            echo "La clave debe contener solo letras y/o números y tener entre 4 y 8 caracteres. Por favor, intente nuevamente.\n";
        }
    }


    // Actualizar datos, solo si fueron modificados
    $nuevosDatos = [
        'nombre' => $nombreApellido ?: $usuario->getNombreApellido(),
        'email' => $email ?: $usuario->getEmail(),
        'telefono' => $telefono ?: $usuario->getTelefono(),
        'clave' => $clave ?: null,
    ];

    if ($usuariosGestor->actualizarUsuario($id, $nuevosDatos)) {
        echo "Usuario actualizado correctamente.\n";
    } else {
        echo "No se pudo actualizar el usuario.\n";
    }
}


//HABITACIONES

function verHabitaciones()
{
    $habitacionesGestor = new HabitacionControlador;
    $habitacionesGestor->cargarDesdeJSON();
    $habitaciones = $habitacionesGestor->obtenerHabitaciones();
    foreach ($habitaciones as $habitacion) {
        echo $habitacion . "\n";
    }
}
function mostrarHabitacionesDisponibles($habitaciones)
{
    echo "Habitaciones disponibles:\n";
    foreach ($habitaciones as $index => $habitacion) {
        echo $index . '. Número: ' . $habitacion->getNumero() . ' - Precio por noche: ' . $habitacion->getPrecio() . "\n";
    }
}
