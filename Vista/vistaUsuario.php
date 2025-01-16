<?php

$dniGuardado = null; // variable global
function menuUsuario()
{
    global $dniGuardado;

    // Inicializar los gestores necesarios
    $usuariosGestor = new UsuarioControlador;
    $habitacionesGestor = new HabitacionControlador;
    $reservasGestor = new ReservaControlador($habitacionesGestor);


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

            // Busca al usuario por DNI y valida que coincida la clave
            $usuario = $usuariosGestor->obtenerUsuarioPorDni($dni);

            if ($usuario && $usuario->getClave() === $clave) {
                // Si se encuentra un usuario y la clave coincide, accede al menú
                menuUsuarioRegistrado($usuario, $habitacionesGestor, $reservasGestor, $usuariosGestor);
            } else {
                // Si no se encuentra o la clave no coincide, muestra un mensaje de error
                echo "DNI o clave incorrectos. Inténtelo de nuevo.\n";
                menuUsuario(); // Redirige al menú principal
            }


        default:
            echo "Opción no válida. Inténtelo de nuevo.\n";
            menuUsuario();

            break;
    }
}
function menuUsuarioRegistrado($usuario, $habitacionesGestor, $reservasGestor, $usuariosGestor)
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
        echo "8. Marcar notificaciones como leidas\n";
        echo "9. Salir\n";
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
                $reservasGestor->limpiarNotificacionesPorUsuarioDni($dniGuardado);
                break;
            case 9:
                echo "Saliendo del sistema...\n";
                return;
            default:
                echo "Opción no válida. Inténtelo de nuevo.\n";
                break;
        }
    }
}
