<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $conn = new mysqli("localhost", "root", "", "formulario_medico");
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Campos comunes
    $plan = $conn->real_escape_string($_POST['plan_contratado'] ?? '');
    $nombre = $conn->real_escape_string($_POST['nombre'] ?? '');
    $apellido_paterno = $conn->real_escape_string($_POST['apellido_paterno'] ?? '');
    $apellido_materno = $conn->real_escape_string($_POST['apellido_materno'] ?? '');
    $especialidad = $_POST['especialidad'] === "Otra" ? $conn->real_escape_string($_POST['especialidad_otro'] ?? '') : $conn->real_escape_string($_POST['especialidad'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $telefono = $conn->real_escape_string($_POST['telefono_mensajes'] ?? '');
    $estado = $conn->real_escape_string($_POST['estado'] ?? '');
    $municipio = $conn->real_escape_string($_POST['municipio'] ?? '');
    $cp = $conn->real_escape_string($_POST['cp'] ?? '');
    $calle = $conn->real_escape_string($_POST['calle'] ?? '');
    $num_ext = $conn->real_escape_string($_POST['numero_exterior'] ?? '');
    $num_int = $conn->real_escape_string($_POST['numero_interior'] ?? '');
    $fracc = $_POST['fraccionamiento'] === "Otro" ? $conn->real_escape_string($_POST['fraccionamiento_otro'] ?? '') : $conn->real_escape_string($_POST['fraccionamiento'] ?? '');
    $cedula = $conn->real_escape_string($_POST['cedula_profesional'] ?? '');
    $tipo_personas = $conn->real_escape_string($_POST['tipo_personas'] ?? '');
    $servicios = $conn->real_escape_string($_POST['servicios_principales'] ?? '');

    // Imagen
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $nombreImagen = $_FILES['imagen_perfil']['name'] ?? '';
    $rutaDestino = '';
    if (!empty($nombreImagen)) {
        $temporal = $_FILES['imagen_perfil']['tmp_name'];
        $nombreFinal = uniqid() . '_' . basename($nombreImagen);
        $rutaDestino = $uploadDir . $nombreFinal;
        move_uploaded_file($temporal, $rutaDestino);
    }

    // Si es GRATUITO, solo guarda en tabla básica
    if ($plan === 'Gratuito') {
        $stmt = $conn->prepare("INSERT INTO consultorios 
        (nombre, apellido_paterno, apellido_materno, especialidad, email, telefono, estado, municipio, cp, calle, numero_exterior, numero_interior, fraccionamiento, cedula, tipo_personas, servicios, imagen, plan) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssssssssssssssss", 
            $nombre, $apellido_paterno, $apellido_materno, $especialidad, $email, $telefono, $estado, $municipio, $cp, $calle,
            $num_ext, $num_int, $fracc, $cedula, $tipo_personas, $servicios, $rutaDestino, $plan);
    }

    // Si es MEJORADO, guarda también campos extendidos
    else if ($plan === 'Mejorado') {
        $rfc = $conn->real_escape_string($_POST['rfc'] ?? '');
        $telefonos_fijos = $conn->real_escape_string($_POST['telefonos_fijos'] ?? '');
        $telefono_emergencias = $conn->real_escape_string($_POST['telefono_emergencias'] ?? '');
        $fecha_inicio = $conn->real_escape_string($_POST['fecha_inicio'] ?? '');
        $cedula_especialidad = $conn->real_escape_string($_POST['cedula_especialidad'] ?? '');
        $horario = $conn->real_escape_string($_POST['horario'] ?? '');
        $costo_primera = $conn->real_escape_string($_POST['costo_primera'] ?? '');
        $costo_subsecuente = $conn->real_escape_string($_POST['costo_subsecuente'] ?? '');
        $otro_costo = $conn->real_escape_string($_POST['otro_costo'] ?? '');
        $pago_efectivo = isset($_POST['pago_efectivo']) ? 1 : 0;
        $pago_transferencia = isset($_POST['pago_transferencia']) ? 1 : 0;
        $pago_tarjeta = isset($_POST['pago_tarjeta']) ? 1 : 0;
        $aseguradoras = $conn->real_escape_string($_POST['aseguradoras'] ?? '');
        $otras_ubicaciones = $conn->real_escape_string($_POST['otras_ubicaciones'] ?? '');
        $formacion_profesional = $conn->real_escape_string($_POST['formacion_profesional'] ?? '');
        $facebook = $conn->real_escape_string($_POST['facebook'] ?? '');
        $sitio_web = $conn->real_escape_string($_POST['sitio_web'] ?? '');
        $gmb = $conn->real_escape_string($_POST['gmb'] ?? '');
        $gmail = $conn->real_escape_string($_POST['gmail'] ?? '');
        $url_agsmedico = $conn->real_escape_string($_POST['url_agsmedico'] ?? '');

        // Firma Asesor
        $firma_asesor = '';
        if (!empty($_POST['firma_asesor_data'])) {
            $firma_asesor = $uploadDir . uniqid() . '_asesor.png';
            file_put_contents($firma_asesor, base64_decode(preg_replace('/^data:image\/png;base64,/', '', $_POST['firma_asesor_data'])));
        }

        // Firma Contratante
        $firma_contratante = '';
        if (!empty($_POST['firma_contratante_data'])) {
            $firma_contratante = $uploadDir . uniqid() . '_contratante.png';
            file_put_contents($firma_contratante, base64_decode(preg_replace('/^data:image\/png;base64,/', '', $_POST['firma_contratante_data'])));
        }

        // Archivos múltiples
        $archivos = '';
        if (!empty($_FILES['archivos']['name'][0])) {
            $filePaths = [];
            foreach ($_FILES['archivos']['tmp_name'] as $key => $tmp_name) {
                $fileName = basename($_FILES['archivos']['name'][$key]);
                $targetPath = $uploadDir . uniqid() . '_' . $fileName;
                if (move_uploaded_file($tmp_name, $targetPath)) {
                    $filePaths[] = $targetPath;
                }
            }
            $archivos = implode(',', $filePaths);
        }

        // Insertar en tabla extendida
        $stmt = $conn->prepare("INSERT INTO formulario_medico 
        (nombre, rfc, especialidad, fraccionamiento, email, cp, telefonos_fijos, telefono_emergencias, telefono_mensajes, num_ext, num_int, estado, municipio, fecha_inicio, cedula_profesional, cedula_especialidad, tipo_personas, horario, costo_primera, costo_subsecuente, otro_costo, pago_efectivo, pago_transferencia, pago_tarjeta, aseguradoras, otras_ubicaciones, servicios_principales, plan_contratado, formacion_profesional, facebook, sitio_web, gmb, gmail, url_agsmedico, archivos, firma_asesor, firma_contratante, apellido_paterno, apellido_materno, calle, imagen)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssssssssssssssssssiiissssssssssssssssssss", 
            $nombre, $rfc, $especialidad, $fracc, $email, $cp, $telefonos_fijos, $telefono_emergencias, $telefono, $num_ext, $num_int,
            $estado, $municipio, $fecha_inicio, $cedula, $cedula_especialidad, $tipo_personas, $horario, $costo_primera, $costo_subsecuente, $otro_costo,
            $pago_efectivo, $pago_transferencia, $pago_tarjeta, $aseguradoras, $otras_ubicaciones, $servicios, $plan,
            $formacion_profesional, $facebook, $sitio_web, $gmb, $gmail, $url_agsmedico, $archivos, $firma_asesor, $firma_contratante,
            $apellido_paterno, $apellido_materno, $calle, $rutaDestino
        );
    }

    // Ejecutar
    if ($stmt->execute()) {
        echo " Datos guardados correctamente.";
    } else {
        echo " Error al guardar datos: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Método no permitido.";
}
?>
