<?php
/**
 * Esta línea pregunta: "¿Alguien envió datos a través de un botón o formulario?"
 * "POST" es el método estándar para enviar información privada o importante.
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. CAPTURA DE DATOS (Recibir el paquete) ---
    // $_POST es como una caja donde llegan todos los datos del formulario.
    // Aquí sacamos cada dato de la caja y lo guardamos en "variables" (contenedores con nombre).

    $nombres = $_POST['nombres']; // Guardamos el nombre.
    $edad    = $_POST['edad'];    // Guardamos la edad.
    $trabaja = $_POST['trabaja']; // Guardamos si trabaja o no.
    $activo  = $_POST['activo'];  // Guardamos si es estudiante activo.
    $indice  = $_POST['indice'];  // Guardamos su promedio o récord académico.

    /**
     * Creamos una lista vacía llamada 'errores'. 
     * Si algo sale mal, iremos anotando las quejas aquí.
     */
    $errores = [];

    // --- 2. VALIDACIÓN DE SEGURIDAD (El filtro) ---
    // Aunque JavaScript ya revisó esto en el navegador, PHP vuelve a revisar 
    // en el servidor para evitar que alguien malintencionado salte las reglas.

    // Si la edad es menor a 18, anotamos el error.
    if ($edad < 18) {
        $errores[] = "No cumple con la edad mínima.";
    }

    // Si marcó que "No" está activo, anotamos el error.
    if ($activo == "No") {
        $errores[] = "No es un estudiante activo.";
    }

    // Si su promedio (índice) es menor a 12, anotamos el error.
    if ($indice < 12) {
        $errores[] = "Récord académico no apto.";
    }

    // --- 3. TOMA DE DECISIONES ---

    /**
     * Preguntamos: "¿Nuestra lista de errores tiene algo escrito?"
     * count($errores) cuenta cuántas quejas anotamos.
     */
    if (count($errores) > 0) {
        
        // CASO A: SI HAY ERRORES
        echo "<h1>Formulario Incompleto o No Apto</h1>";
        echo "<ul>";
        
        // El 'foreach' es como un empleado que lee la lista de errores uno por uno
        // y los escribe en la pantalla como una lista con puntos.
        foreach ($errores as $error) {
            echo "<li>$error</li>";
        }
        
        echo "</ul>";
        // Ponemos un enlace para que el usuario pueda volver atrás y corregir.
        echo "<a href='index.php'>Volver a intentar</a>";

    } else {
        
        // CASO B: TODO ESTÁ PERFECTO
        // Si no hubo errores, procedemos a mostrar el resumen final.
        echo "<h1>Resumen de Datos (Verificación)</h1>";
        
        // Mostramos lo que guardamos en las variables al principio.
        echo "<p><strong>Nombre:</strong> $nombres</p>";
        echo "<p><strong>Edad:</strong> $edad</p>";
        echo "<p><strong>Trabaja:</strong> $trabaja</p>";
        echo "<p><strong>Activo:</strong> $activo</p>";
        echo "<p><strong>Índice:</strong> $indice</p>";
        
        // Este botón usa un poquito de JavaScript ('window.print()') 
        // para abrir la ventana de impresión de la computadora.
        echo "<button onclick='window.print()'>Confirmar y Guardar</button>";
    }
}
?>