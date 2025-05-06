<?php

    function conectar()
    {
        //Dato 
        $host = "sql309.infinityfree.com";
        $basededatos = "if0_38716721_agricultura";
        $usuariodb = "if0_38716721";
        $clavedb = "bpQQS0Rmdb7l5";
        
        // Conexion.
        $conexion = mysqli_connect($host, $usuariodb, $clavedb, $basededatos) 
                or die("No se puede conectar con el servidor");
        return $conexion;
    }

?>

