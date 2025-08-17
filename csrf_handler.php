<?php

 // La sesión ya se inicia en config.php, no es necesario aquí.

 /**
  * Genera un token CSRF, lo almacena en la sesión y lo devuelve.
  * Si ya existe un token en la sesión, lo devuelve sin generar uno nuevo.
  * @return string El token CSRF.
  */
 function generate_csrf_token() {
     if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
     }
     return $_SESSION['csrf_token'];
 }

 /**
  * Valida el token CSRF proporcionado contra el que está en la sesión.
  * Usa hash_equals para prevenir ataques de temporización.
  * @param string|null $token El token enviado desde el formulario/cliente.
  * @return bool True si el token es válido, false en caso contrario.
  */
 function validate_csrf_token($token) {
     if ($token === null) {
         return false;
     }

     if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
         // Opcional pero recomendado: Un token solo debe usarse una vez.
         // Lo eliminamos después de una validación exitosa.
         unset($_SESSION['csrf_token']);
         return true;
     }

     return false;
 }

 /**
  * Valida un token CSRF de una petición y termina la ejecución si es inválido.
  * Busca el token en POST, cabeceras HTTP o cuerpos JSON.
  */
 function verify_csrf_and_exit() {
     $token = null;

     if (isset($_POST['csrf_token'])) {
         $token = $_POST['csrf_token'];
     } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
         $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
     } else {
         $data = json_decode(file_get_contents('php://input'), true);
         if (isset($data['csrf_token'])) {
             $token = $data['csrf_token'];
         }
     }

     if (!validate_csrf_token($token)) {
         header('Content-Type: application/json');
         http_response_code(403); // Forbidden
         echo json_encode(['success' => false, 'message' => 'Error de seguridad: Token CSRF
 inválido o ausente. Recargue la página e intente de nuevo.']);
         exit();
     }
 }