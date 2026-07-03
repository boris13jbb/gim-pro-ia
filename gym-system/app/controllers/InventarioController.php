<?php
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../lib/Auth.php';

class InventarioController {
    private $categoriaModel;
    private $productoModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        Auth::requerirRol(['admin']);
        $this->categoriaModel = new Categoria();
        $this->productoModel = new Producto();
    }

    /* ==============================================
             GESTIÓN DE CATEGORÍAS
       ============================================== */
    public function categorias() {
        $categorias = $this->categoriaModel->obtenerTodos();
        require_once __DIR__ . '/../views/inventario/categorias.php';
    }

    public function guardarCategoria() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nombre = trim($_POST['nombre']);
            
            if(empty($nombre)) {
                // Manejo de error básico
                header('Location: /inventario/categorias?error=vacío');
                exit();
            }

            if(isset($_POST['id']) && !empty($_POST['id'])) {
                // Editar
                $this->categoriaModel->actualizar($_POST['id'], $nombre);
            } else {
                // Nuevo
                $this->categoriaModel->agregar($nombre);
            }
            
            header('Location: /inventario/categorias?success=1');
            exit();
        }
    }

    public function cambiarEstadoCategoria($id, $estado = 'inactivo') {
        $this->categoriaModel->cambiarEstado($id, $estado);
        header('Location: /inventario/categorias');
        exit();
    }

    /* ==============================================
             GESTIÓN DE PRODUCTOS
       ============================================== */
    public function productos() {
        $productos = $this->productoModel->obtenerTodos();
        $categorias = $this->categoriaModel->obtenerActivos();
        require_once __DIR__ . '/../views/inventario/productos.php';
    }

    public function guardarProducto() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos = [
                'id' => $_POST['id'] ?? null,
                'categoria_id' => $_POST['categoria_id'],
                'codigo' => trim($_POST['codigo']),
                'nombre' => trim($_POST['nombre']),
                'precio_compra' => $_POST['precio_compra'],
                'precio_venta' => $_POST['precio_venta'],
                'stock' => $_POST['stock'] ?? 0,
                'foto' => ''
            ];

            // Manejo de la imagen (Foto)
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['foto']['tmp_name'];
                $fileName = $_FILES['foto']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($fileExtension, $allowedfileExtensions)) {
                    // Generar un nombre único
                    $newFileName = 'prod_' . time() . '_' . rand(100, 999) . '.' . $fileExtension;
                    // Ruta de subida
                    $uploadFileDir = __DIR__ . '/../../public/img/productos/';
                    
                    // Asegurar que el directorio existe
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0755, true);
                    }

                    $dest_path = $uploadFileDir . $newFileName;

                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        $datos['foto'] = $newFileName;
                    }
                }
            }

            if(!empty($datos['id'])) {
                // Editar
                $this->productoModel->actualizar($datos);
            } else {
                // Nuevo
                // Si no se subió foto, ponemos una por defecto vacía o manejable en la vista
                $this->productoModel->agregar($datos);
            }
            
            header('Location: /inventario/productos?success=1');
            exit();
        }
    }

    public function cambiarEstadoProducto($id, $estado = 'inactivo') {
        $this->productoModel->cambiarEstado($id, $estado);
        header('Location: /inventario/productos');
        exit();
    }

    public function ajusteStock() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['producto_id'];
            $cantidad = $_POST['cantidad'];
            $operacion = $_POST['operacion']; // sumar o restar
            
            $this->productoModel->actualizarStock($id, $cantidad, $operacion);
            header('Location: /inventario/productos?success=stock');
            exit();
        }
    }
}
