<?php
header('Content-Type: application/json');
require __DIR__ . '/conexion.php';

// Obtener método y datos
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Funciones útiles
function enviarError($mensaje, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode(['error' => $mensaje]);
    exit;
}

function obtenerDatos() {
    return json_decode(file_get_contents('php://input'), true);
}

// Procesar solicitud
switch ($method) {
    case 'GET':
        // Obtener producto(s)
        if ($id > 0) {
            $query = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
            $query->bind_param("i", $id);
        } else {
            $query = $conexion->prepare("SELECT * FROM productos");
        }
        
        $query->execute();
        $resultado = $query->get_result();
        
        if ($id > 0) {
            echo json_encode($resultado->fetch_assoc() ?: []);
        } else {
            echo json_encode($resultado->fetch_all(MYSQLI_ASSOC));
        }
        break;

    case 'POST':
        // Crear nuevo producto
        $datos = obtenerDatos();
        
        if (empty($datos['name']) || !isset($datos['price']) || 
            empty($datos['email']) || !isset($datos['NumCC'])) {
            enviarError('Faltan datos obligatorios: name, price, email, NumCC');
        }
        
        $query = $conexion->prepare("INSERT INTO productos (name, price, email, NumCC) VALUES (?, ?, ?, ?)");
        $query->bind_param("sdsi", $datos['name'], $datos['price'], $datos['email'], $datos['NumCC']);
        
        if ($query->execute()) {
            echo json_encode(['id' => $conexion->insert_id]);
        } else {
            enviarError('Error al crear producto', 500);
        }
        break;

    case 'PUT':
        // Actualizar producto
        if ($id <= 0) enviarError('Se requiere ID válido');
        
        $datos = obtenerDatos();
        $query = $conexion->prepare("UPDATE productos SET name=?, price=?, email=?, NumCC=? WHERE id=?");
        $query->bind_param("sdsii", 
            $datos['name'] ?? '', 
            $datos['price'] ?? 0, 
            $datos['email'] ?? '', 
            $datos['NumCC'] ?? 0, 
            $id
        );
        
        $query->execute();
        echo json_encode(['actualizado' => $query->affected_rows]);
        break;

    case 'DELETE':
        // Eliminar producto
        if ($id <= 0) enviarError('Se requiere ID válido');
        
        $query = $conexion->prepare("DELETE FROM productos WHERE id = ?");
        $query->bind_param("i", $id);
        $query->execute();
        echo json_encode(['eliminado' => $query->affected_rows]);
        break;

    default:
        enviarError('Método no permitido', 405);
}
?>