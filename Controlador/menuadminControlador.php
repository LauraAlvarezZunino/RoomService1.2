<?php

//admin habitacion

function validarTipoHabitacion($tipo)
{
    return preg_match('/^(simple|doble|familiar)$/i', $tipo);
}

function validarPrecio($precio)
{
    return preg_match('/^\d+$/', $precio);
}

function agregarHabitacion($habitacionesGestor)
{
    while (true) {
        echo 'Ingrese el número de la habitación: ';
        $numero = trim(fgets(STDIN));

        if (!preg_match('/^\d+$/', $numero)) {
            echo "El número de habitación debe ser un valor numérico.\n";
            continue;
        }

        $habitacionExistente = false;
        foreach ($habitacionesGestor->obtenerHabitaciones() as $h) {
            if ($h->getNumero() == $numero) {
                $habitacionExistente = true;
                break;
            }
        }

        if ($habitacionExistente) {
            echo "La habitación con el número $numero ya existe. No se puede duplicar.\n";
            continue;
        }


        while (true) {
            echo 'Ingrese el tipo de habitación: ';
            $tipo = trim(fgets(STDIN));

            if (validarTipoHabitacion($tipo)) {
                break;
            } else {
                echo "El tipo de habitación debe ser uno de los siguientes: simple, doble, o familiar.\n";
            }
        }

        while (true) {
            echo 'Ingrese el precio por noche: ';
            $precio = trim(fgets(STDIN));

            if (validarPrecio($precio)) {
                break;
            } else {
                echo "El precio debe ser un número entero válido.\n";
            }
        }

        $habitacionesGestor->agregarHabitacion(new Habitacion($numero, $tipo, $precio));
        echo "Habitación agregada exitosamente.\n";
        break;
    }
}

function modificarHabitacion($habitacionesGestor)
{
    echo 'Ingrese el número de la habitación que desea modificar: ';
    $numero = trim(fgets(STDIN));

    $habitacion = null;
    foreach ($habitacionesGestor->obtenerHabitaciones() as $h) {
        if ($h->getNumero() == $numero) {
            $habitacion = $h;
            break;
        }
    }

    if ($habitacion) {
        echo "Modificando habitación número: $numero\n";

        while (true) {
            echo "Ingrese el nuevo tipo de habitación (deje vacío para mantener el actual: {$habitacion->getTipo()}): ";
            $nuevoTipo = trim(fgets(STDIN));

            if ($nuevoTipo === '' || validarTipoHabitacion($nuevoTipo)) {
                $nuevoTipo = $nuevoTipo ?: $habitacion->getTipo();
                break;
            } else {
                echo "El tipo de habitación debe ser uno de los siguientes: simple, doble, o familiar.\n";
            }
        }

        while (true) {
            echo "Ingrese el nuevo precio (deje vacío para mantener el actual: {$habitacion->getPrecio()}): ";
            $nuevoPrecio = trim(fgets(STDIN));

            // Si no se ingresó nada, mantiene el precio actual
            if ($nuevoPrecio === '' || validarPrecio($nuevoPrecio)) {
                $nuevoPrecio = $nuevoPrecio ?: $habitacion->getPrecio();
                break;
            } else {
                echo "El precio debe ser un número entero válido.\n";
            }
        }

        $nuevosDatos = [
            'tipo' => $nuevoTipo,
            'precio' => $nuevoPrecio,
        ];

        if ($habitacionesGestor->actualizarHabitacion($numero, $nuevosDatos)) {
            echo "Habitación actualizada correctamente.\n";
        } else {
            echo "Error al actualizar la habitación.\n";
        }
    } else {
        echo "La habitación con número $numero no existe.\n";
    }
}


function eliminaHabitacion($habitacionesGestor)
{
    echo 'Ingrese el número de la habitación que desea eliminar: ';
    $numero = trim(fgets(STDIN));

    if ($habitacionesGestor->eliminarHabitacion($numero)) {
        echo "Habitación eliminada correctamente.\n";
    } else {
        echo "Error al eliminar la habitación.\n";
    }
}

//admin usuarios
function mostrarUsuarios($usuariosGestor)
{
    $usuarios = $usuariosGestor->obtenerUsuarios();
    foreach ($usuarios as $usuario) {
        echo $usuario . "\n";
    }
}

function eliminaUsuario($usuariosGestor, $reservasGestor)
{
    echo 'Ingrese el ID del usuario a eliminar: ';
    $idEliminado = trim(fgets(STDIN)); // Captura el ID del usuario a eliminar

    // Busca el usuario por su ID
    $usuario = $usuariosGestor->obtenerUsuarioPorId($idEliminado);

    if ($usuario === null) {
        echo "No se encontró un usuario con ID {$idEliminado}.\n";
        return;
    }

    $dniUsuario = $usuario->getDni(); // Obtén el DNI del usuario

    // Intenta eliminar el usuario
    if ($usuariosGestor->eliminarUsuario($idEliminado)) {
        echo "Usuario {$idEliminado} eliminado correctamente.\n";

        // Elimina todas las reservas asociadas al DNI del usuario
        $reservasEliminadas = 0;
        foreach ($reservasGestor->obtenerReservas() as $reserva) {
            if ($reserva->getUsuarioDni() == $dniUsuario) {
                if ($reservasGestor->eliminarReserva($reserva->getId())) {
                    $reservasEliminadas++;
                }
            }
        }

        echo "Se eliminaron {$reservasEliminadas} reservas asociadas al usuario con DNI {$dniUsuario}.\n";
    } else {
        echo "No se pudo eliminar el usuario {$idEliminado}. Puede que no exista.\n";
    }
}
