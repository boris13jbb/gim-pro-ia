<?php
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Caja.php';

class PosController {
    private $productoModel;
    private $categoriaModel;
    private $ventaModel;
    private $socioModel;
    private $cajaModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] == 'entrenador') {
            header('Location: /auth/index');
            exit();
        }
        $this->productoModel = new Producto();
        $this->categoriaModel = new Categoria();
        $this->ventaModel = new Venta();
        $this->socioModel = new Socio();
        $this->cajaModel = new Caja();

        // Inicializar carrito si no existe
        if (!isset($_SESSION['pos_cart'])) {
            $_SESSION['pos_cart'] = [];
        }
    }

    // Mostrar interfaz POS
    public function index() {
        // Verificar si hay caja abierta
        $cajaAbierta = $this->cajaModel->obtenerCajaAbierta($_SESSION['user_id']);
        if (!$cajaAbierta) {
            $_SESSION['error_message'] = "Debes abrir caja antes de realizar ventas.";
            header('Location: /caja/index');
            exit();
        }

        $productos = $this->productoModel->obtenerActivos();
        $categorias = $this->categoriaModel->obtenerActivos();
        $socios = $this->socioModel->obtenerTodos()->fetchAll(PDO::FETCH_ASSOC);
        
        $carrito = $_SESSION['pos_cart'];
        $totalCarrito = $this->calcularTotalCarrito();

        require_once __DIR__ . '/../views/pos/index.php';
    }

    // Operaciones AJAX para el Carrito
    public function agregarAlCarrito() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $producto = $this->productoModel->obtenerPorId($id);

            if ($producto && $producto['stock'] > 0) {
                if (isset($_SESSION['pos_cart'][$id])) {
                    // Verificamos si no excede el stock
                    if($_SESSION['pos_cart'][$id]['cantidad'] < $producto['stock']) {
                        $_SESSION['pos_cart'][$id]['cantidad']++;
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Stock insuficiente']);
                        exit;
                    }
                } else {
                    $_SESSION['pos_cart'][$id] = [
                        'id' => $producto['id'],
                        'nombre' => $producto['nombre'],
                        'precio' => $producto['precio_venta'],
                        'cantidad' => 1,
                        'foto' => $producto['foto']
                    ];
                }
                echo json_encode(['status' => 'success', 'carrito' => $_SESSION['pos_cart'], 'total' => $this->calcularTotalCarrito()]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Producto agotado o no encontrado']);
            }
        }
    }

    public function eliminarDelCarrito() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
            $id = $_POST['id'];
            if (isset($_SESSION['pos_cart'][$id])) {
                unset($_SESSION['pos_cart'][$id]);
                echo json_encode(['status' => 'success', 'carrito' => $_SESSION['pos_cart'], 'total' => $this->calcularTotalCarrito()]);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }
    }

    public function vaciarCarrito() {
        $_SESSION['pos_cart'] = [];
        echo json_encode(['status' => 'success']);
        if(!isset($_POST['ajax'])) {
            header('Location: /pos/index');
        }
    }

    public function actualizarCantidad() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'];
            $cantidad = (int) $_POST['cantidad'];
            
            if($cantidad <= 0) {
                unset($_SESSION['pos_cart'][$id]);
            } else {
                $producto = $this->productoModel->obtenerPorId($id);
                if($cantidad <= $producto['stock']) {
                    $_SESSION['pos_cart'][$id]['cantidad'] = $cantidad;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Stock insuficiente']);
                    exit;
                }
            }
            echo json_encode(['status' => 'success', 'carrito' => $_SESSION['pos_cart'], 'total' => $this->calcularTotalCarrito()]);
        }
    }

    private function calcularTotalCarrito() {
        $total = 0;
        foreach ($_SESSION['pos_cart'] as $item) {
            $total += $item['precio'] * $item['cantidad'];
        }
        return $total;
    }

    // Procesar la venta final (Checkout)
    public function procesarVenta() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            if (empty($_SESSION['pos_cart'])) {
                header('Location: /pos/index?error=carrito_vacio');
                exit();
            }

            $cajaAbierta = $this->cajaModel->obtenerCajaAbierta($_SESSION['user_id']);
            if (!$cajaAbierta) {
                header('Location: /caja/index?error=caja_cerrada');
                exit();
            }

            $socio_id = !empty($_POST['socio_id']) ? $_POST['socio_id'] : null;
            $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
            $descuento   = floatval($_POST['descuento'] ?? 0);
            $totalBruto  = $this->calcularTotalCarrito();
            $total       = max(0, $totalBruto - $descuento);

            // Datos del receptor para SUNAT
            $tipoComp   = $_POST['tipo_comprobante']  ?? 'boleta';
            $cliTipoDoc = $_POST['cliente_tipo_doc']  ?? null;
            $cliNumDoc  = trim($_POST['cliente_num_doc'] ?? '');
            $cliRazon   = trim($_POST['cliente_razon']   ?? '');
            $cliDir     = trim($_POST['cliente_direccion'] ?? '');

            // Llamar al modelo pasando la caja actual, socio, total y el carrito
            $venta_id = $this->ventaModel->registrarVenta($cajaAbierta['id'], $socio_id, $total, $metodo_pago, $_SESSION['pos_cart'], $descuento);

            if ($venta_id) {
                // Guardar datos del receptor en la venta
                $db = (new Database())->getConnection();
                $up = $db->prepare("UPDATE ventas SET tipo_comprobante=:tc, cliente_tipo_doc=:td,
                                    cliente_num_doc=:nd, cliente_razon=:rz, cliente_direccion=:dir
                                    WHERE id=:id");
                $up->execute([
                    ':tc'=>$tipoComp, ':td'=>$cliTipoDoc, ':nd'=>$cliNumDoc,
                    ':rz'=>$cliRazon, ':dir'=>$cliDir, ':id'=>$venta_id
                ]);

                // Venta exitosa, limpiar carrito
                $_SESSION['pos_cart'] = [];

                // Si pidieron CPE, emitir directamente
                if ($tipoComp === 'factura' || $tipoComp === 'boleta') {
                    $tipoDocSunat = $tipoComp === 'factura' ? '01' : '03';
                    header('Location: /facturacionelectronica/emitirVenta/' . $venta_id . '?tipo=' . $tipoDocSunat);
                    exit();
                }
                header('Location: /ticket/ver/' . $venta_id);
                exit();
            } else {
                header('Location: /pos/index?error=error_procesar');
                exit();
            }
        }
    }

    // Vista de Historial de Ventas
    public function historial() {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $busqueda = trim($_GET['busqueda'] ?? '');

        $ventas = $this->ventaModel->obtenerHistorialFiltrado($desde, $hasta, $busqueda);

        // KPIs rápidos
        $totalVentas  = array_sum(array_column($ventas, 'total'));
        $cantVentas   = count($ventas);
        $ticketProm   = $cantVentas > 0 ? $totalVentas / $cantVentas : 0;

        require_once __DIR__ . '/../views/pos/historial.php';
    }

    // AJAX: detalle de una venta
    public function detalleVenta($venta_id) {
        header('Content-Type: application/json');
        $detalle = $this->ventaModel->obtenerDetalle($venta_id);
        echo json_encode($detalle);
        exit;
    }
}
