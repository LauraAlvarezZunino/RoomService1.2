 <?php

    require_once 'Controlador/usuarioControlador.php';
    require_once 'Controlador/habitacionControlador.php';
    require_once 'Controlador/reservaControlador.php';
    require_once 'Controlador/menuControlador.php';
    require_once 'Controlador/menuUsuarioControlador.php';
    require_once 'Controlador/menuAdminControlador.php';
    require_once 'Controlador/NotificacionControlador.php';
    require_once 'Vista/vistaUsuario.php';
    require_once 'Vista/vistaAdmin.php';


    while (true) {
        $clave = 111;
        echo "===Bienvenido===\n";
        echo "1. Administrador\n";
        echo "2. Usuario\n";
        echo "3. Salir\n";

        $opcion = trim(fgets(STDIN));

        switch ($opcion) {
            case 1:
                echo 'Ingrese la clave: ';
                $claveAdmin = trim(fgets(STDIN));
                if ($clave == $claveAdmin) {
                    menuAdmin();
                } else {
                    echo "Clave Erronea.\n";
                }
                break;
            case 2:
                menuUsuario();
                break;
            case 3:
                echo "Saliendo del sistema...\n";
                exit;
            default:
                echo "Opción no válida. Inténtelo de nuevo.\n";
                break;
        }
    }
