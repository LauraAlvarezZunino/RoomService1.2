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
    while (true) {
        echo 'Ingrese el número de la habitación que desea modificar: ';
        $numero = trim(fgets(STDIN));

        // Validar que el número de habitación sea solo dígitos
        if (!preg_match('/^\d+$/', $numero)) {
            echo "Error: El número de habitación debe ser un número entero.\n";
            continue; // Vuelve a solicitar el número
        }
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
}

function eliminaHabitacion($habitacionesGestor)
{
    while (true) {
        echo 'Ingrese el número de la habitación que desea eliminar: ';
        $numero = trim(fgets(STDIN));

       
        if (!preg_match('/^\d+$/', $numero)) {
            echo "Error: El número de habitación debe ser un número entero.\n";
            continue; 
        }

       
        $resultado = $habitacionesGestor->eliminarHabitacion($numero);
        echo $resultado . "\n"; 

        break; 
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

function eliminaUsuario($usuariosGestor, $reservaControlador)
{
    echo 'Ingrese el ID del usuario a eliminar: ';
    $idEliminado = trim(fgets(STDIN)); 

    $usuario = $usuariosGestor->obtenerUsuarioPorId($idEliminado);

    if (!$usuario) {
        echo "El usuario con ID {$idEliminado} no existe.\n";
        return; 
    }

    $dniUsuario = $usuario->getDni();

    $reservas = $reservaControlador->obtenerReservas();
//primero borramos la reserva
    foreach ($reservas as $reserva) {
        if ($reserva->getUsuarioDni() == $dniUsuario) {
            $reservaControlador->eliminarReserva($reserva->getId());
        }
    }

    // desp eliminamos al usuario
    if ($usuariosGestor->eliminarUsuario($idEliminado)) {
        echo "Usuario con ID {$idEliminado} y sus reservas han sido eliminados correctamente.\n";
    } else {
        echo "No se pudo eliminar el usuario con ID {$idEliminado}.\n";
    }
}
