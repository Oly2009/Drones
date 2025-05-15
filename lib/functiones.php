<?php

function conectar()
{
    // Detectar si estamos en local o en servidor
    if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        // Entorno local
        $host = "localhost";
        $basededatos = "agricultura";
        $usuariodb = "oly";
        $clavedb = "";
    } else {
        // Entorno de producción (InfinityFree u otro)
        $host = "sql309.infinityfree.com";
        $basededatos = "if0_38716721_agricultura";
        $usuariodb = "if0_38716721";
        $clavedb = "bpQQS0Rmdb7l5";
    }

    // Conexión
    $conexion = mysqli_connect($host, $usuariodb, $clavedb, $basededatos)
        or die("❌ Error: No se puede conectar con el servidor");

    // Establecer codificación UTF-8
    mysqli_set_charset($conexion, "utf8");

    return $conexion;
}

?>
