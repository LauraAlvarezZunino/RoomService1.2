<?php

function crearReserva($dniGuardado, $habitacionesGestor, $reservasGestor)
{
    global $dniGuardado; // Declarar la variable global

    $tipoHabitacion = solicitarTipoHabitacion();
    $habitacionesDisponibles = $habitacionesGestor->buscarPorTipo($tipoHabitacion);

    if (empty($habitacionesDisponibles)) {
        echo "No se encontraron habitaciones disponibles del tipo solicitado.\n";
        return; // Salir si no hay habitaciones disponibles
    }

    mostrarHabitacionesDisponibles($habitacionesDisponibles);
    $habitacionSeleccionada = null;

    while (!$habitacionSeleccionada) {
        // Permitir al usuario seleccionar una habitación
        echo "Ingrese el número de habitación: ";
        $numeroHabitacion = trim(fgets(STDIN));
        $habitacionSeleccionada = $habitacionesGestor->buscarHabitacionPorNumero($numeroHabitacion);

        if (!$habitacionSeleccionada || strtolower($habitacionSeleccionada->getTipo()) !== strtolower($tipoHabitacion)) {
            echo "Número de habitación no válido o no coincide con el tipo seleccionado. Intente nuevamente.\n";
            $habitacionSeleccionada = null; // Reiniciar para volver a pedir
        }
    }

    while (true) {
        // Solicitar fechas de reserva
        [$fechaInicio, $fechaFin] = solicitarFechasReserva();
        $costo = calcularCostoReserva($fechaInicio, $fechaFin, $habitacionSeleccionada->getPrecio());
        $reservaId = $reservasGestor->generarNuevoId();
        $reserva = new Reserva($reservaId, $fechaInicio, $fechaFin, $habitacionSeleccionada, $costo, $dniGuardado);

        // Intentar agregar la reserva
        $reservaExitosa = $reservasGestor->agregarReserva($reserva);
        if ($reservaExitosa) {
            echo "Reserva realizada con éxito.\n";
            return; // Salir tras el éxito
        } else {
            echo "La habitación seleccionada no está disponible en las fechas indicadas. Por favor, ingrese un nuevo número de habitación.\n";
            $habitacionSeleccionada = null; // Reiniciar la selección de habitación

            // Permitir al usuario seleccionar otra habitación
            while (!$habitacionSeleccionada) {
                echo "Ingrese el número de habitación: ";
                $numeroHabitacion = trim(fgets(STDIN));
                $habitacionSeleccionada = $habitacionesGestor->buscarHabitacionPorNumero($numeroHabitacion);

                if (!$habitacionSeleccionada || strtolower($habitacionSeleccionada->getTipo()) !== strtolower($tipoHabitacion)) {
                    echo "Número de habitación no válido o no coincide con el tipo seleccionado. Intente nuevamente.\n";
                    $habitacionSeleccionada = null;
                }
            }
        }
    }
}

function calcularCostoReserva($fechaInicio, $fechaFin, $precioPorNoche)
{
    $fechaInicio = new DateTime($fechaInicio);// datetime clase de php 
    $fechaFin = new DateTime($fechaFin);
    $diferencia = $fechaInicio->diff($fechaFin);

    return $diferencia->days * $precioPorNoche; 
}
function solicitarTipoHabitacion()
{
    echo 'Ingrese el tipo de habitación para la reserva (simple - doble - familiar): ';

    return trim(fgets(STDIN));
}

function seleccionarHabitacion($habitaciones)
{
    echo 'Seleccione una habitación (número): ';
    $eleccionHabitacion = trim(fgets(STDIN));

    foreach ($habitaciones as $habitacion) {
        if ($habitacion->getNumero() == $eleccionHabitacion) {
            return $habitacion;
        }
    }
    echo "No se encontró una habitación con ese número.\n";

    return null;
}

function solicitarFechasReserva()
{
    $fechaInicio = '';
    $fechaFin = '';


    while (true) {
        echo 'Ingrese la fecha de inicio (YYYY-MM-DD): ';
        $fechaInicio = trim(fgets(STDIN));
        $fechaActual = date('Y-m-d');

    
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio) && strtotime($fechaInicio) > strtotime($fechaActual)) {
            break;
        } else {
            echo "La fecha de inicio debe tener el formato YYYY-MM-DD y ser posterior a la fecha actual. Por favor, ingrese una fecha válida.\n";
        }
    }

    while (true) {
        echo 'Ingrese la fecha de fin (YYYY-MM-DD): ';
        $fechaFin = trim(fgets(STDIN));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin) && strtotime($fechaFin) > strtotime($fechaInicio)) {
            break;
        } else {
            echo "La fecha de fin debe tener el formato YYYY-MM-DD y ser posterior a la fecha de inicio. Por favor, ingrese una fecha válida.\n";
        }
    }

    return [$fechaInicio, $fechaFin];
}

//usuario
function mostrarDatosUsuario()
{
    global $dniGuardado;

    $usuarioControlador = new UsuarioControlador;
    $usuario = $usuarioControlador->obtenerUsuarioPorDni($dniGuardado);

    if ($usuario) {
        echo "-------------------------\n";
        echo 'DNI: ' . $usuario->getDni() . "\n";
        echo 'Nombre: ' . $usuario->getNombreApellido() . "\n";
        echo 'Correo electrónico: ' . $usuario->getEmail() . "\n";
        echo 'Teléfono: ' . $usuario->getTelefono() . "\n";
        echo "-------------------------\n";
    } else {
        echo "No se encontraron datos para el usuario con el DNI proporcionado.\n";
    }
}

function registrarse($usuariosGestor) 
{
    echo "=== Registro de Usuario ===\n";
    
    while (true) {
        echo 'Ingrese el nombre y apellido del usuario: ';
        $nombreApellido = trim(fgets(STDIN));
        if (preg_match("/^[a-zA-Z\s]+$/", $nombreApellido)) { // \s espacios
            break;
        } else {
            echo "Por favor, ingrese solo letras y espacios para el nombre y apellido.\n";
        }
    }

    while (true) {
        echo 'Ingrese el DNI del usuario sin puntos: ';
        $dni = trim(fgets(STDIN));
        if (preg_match("/^\d{7,8}$/", $dni)) {
            if ($usuariosGestor->obtenerUsuarioPorDni($dni)) {
                echo "El DNI ingresado ya está registrado. Intente nuevamente con otro DNI.\n";
                return;
            } else {
                break; // DNI valido y no registrado
            }
        } else {
            echo "El DNI debe contener entre 7 y 8 dígitos. Por favor, intente nuevamente.\n";
        }
    }

    while (true) {
        echo 'Ingrese el email del usuario: ';
        $email = trim(fgets(STDIN));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            break; 
        } else {
            echo "Por favor, ingrese un email válido.\n";
        }
    }

    while (true) {
        echo 'Ingrese el teléfono del usuario: ';
        $telefono = trim(fgets(STDIN));
        if (preg_match("/^\d+$/", $telefono)) {
            break; 
        } else {
            echo "El teléfono debe contener solo números. Por favor, intente nuevamente.\n";
        }
    }

    while (true) {
        echo 'Ingrese la clave del usuario: ';
        $direccion = trim(fgets(STDIN));
        if (preg_match("/^[a-zA-Z0-9]+$/", $direccion)) { 
            break; 
        } else {
            echo "La clave debe contener solo letras, números. Por favor, intente nuevamente.\n";
        }
    }

    $usuariosGestor->crearUsuario($nombreApellido, $dni, $email, $telefono, $direccion);
    echo "Usuario agregado exitosamente.\n";

    menuUsuario(); // vuelve al menú principal
}

