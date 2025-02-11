<?php

$dniGuardado = null;
function menuUsuario()
{
    global $dniGuardado;

    $usuariosGestor = new UsuarioControlador;
    $habitacionesGestor = new HabitacionControlador;
    $reservasGestor = new ReservaControlador($habitacionesGestor);
    $notificacionControlador = new NotificacionControlador();

    echo "=== Menú Usuario ===\n";
    echo "1. Registrarme\n";
    echo "2. Soy Usuario\n";
    echo 'Seleccione una opción: ';


    $opcion = trim(fgets(STDIN));

    switch ($opcion) {
        case 1:
            registrarse($usuariosGestor);
            break;

        case 2:
            echo 'Ingrese su DNI para continuar: ';
            $dni = trim(fgets(STDIN));
            $dniGuardado = $dni;
            echo 'Ingrese su clave para continuar: ';
            $clave = trim(fgets(STDIN));

            $usuario = $usuariosGestor->obtenerUsuarioPorDni($dni);

            if ($usuario && $usuario->getClave() === $clave) {
                menuUsuarioRegistrado($usuario, $habitacionesGestor, $reservasGestor, $usuariosGestor, $notificacionControlador);
            } else {
                echo "DNI o clave incorrectos. Inténtelo de nuevo.\n";
                menuUsuario();
            }


        default:
            echo "Opción no válida. Inténtelo de nuevo.\n";
            menuUsuario();

            break;
    }
}
function menuUsuarioRegistrado($usuario, $habitacionesGestor, $reservasGestor, $usuariosGestor, $notificacionControlador)
{
    global $dniGuardado;
    while (true) {
        echo "\n=== Menú Usuario Registrado ===\n";
        echo "1. Ver Habitaciones\n";
        echo "2. Crear Reserva\n";
        echo "3. Mostrar Reservas\n";
        echo "4. Modificar Reserva\n";
        echo "5. Eliminar Reserva\n";
        echo "6. Ver mis datos\n";
        echo "7. Modificar mis datos\n";
        echo "8. Ver mis notificaciones\n";
        echo "9. Marcar notificaciones como leidas\n";
        echo "0. Salir\n";
        echo 'Seleccione una opción: ';

        $opcion = trim(fgets(STDIN));

        switch ($opcion) {
            case 1:
                verHabitaciones();
                break;
            case 2:
                crearReserva($usuario, $habitacionesGestor, $reservasGestor);
                break;
            case 3:
                mostrarReservas($reservasGestor, false, $usuario);
                break;
            case 4:
                modificarReserva($reservasGestor, $habitacionesGestor, false, $usuario);
                break;
            case 5:
                eliminarReserva($reservasGestor, $usuario);
                break;
            case 6:
                mostrarDatosUsuario($usuario);
                break;
            case 7:
                modificarUsuario($usuario);
                break;
            case 8:
                $resultado = $notificacionControlador->mostrarNotificacionesPorDni($dniGuardado);
                if (is_array($resultado)) {
                    foreach ($resultado as $notificacion) {
                        echo  $notificacion['notificacion'] . "\n";
                    }
                } else {
                    echo $resultado;
                }

                break;
            case 9:
                $notificacionControlador->eliminarNotificacionesPorDni($dniGuardado);
                echo "Notificaciones marcadas como leídas y eliminadas.\n";
                break;
            case 0:
                echo "Saliendo del sistema...\n";
                return;
            default:
                echo "Opción no válida. Inténtelo de nuevo.\n";
                break;
        }
    }
}
