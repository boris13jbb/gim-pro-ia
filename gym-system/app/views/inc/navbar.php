<?php
// 1. Cargar el modelo de Configuración de forma segura
require_once __DIR__ . '/../../models/Configuracion.php';
if (file_exists(__DIR__ . '/../../lib/Auth.php')) require_once __DIR__ . '/../../lib/Auth.php';

// 2. Obtener los datos de la empresa
$config = Configuracion::getInfo();
$_rolActual = $_SESSION['user_rol'] ?? '';

// 3. Helper para clase activa
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
$is_active = function($path) use ($current_uri) {
    // Check if the current URI starts with the path (e.g., /socios matches /socios/index and /socios/crear)
    if ($path === '/home/index') {
        return ($current_uri === '/' || strpos($current_uri, '/home') === 0) ? 'active' : '';
    }
    return strpos($current_uri, $path) === 0 ? 'active' : '';
};
?>

<link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
<style>
  /* ============================================================
     SIDEBAR — Tema "Gym Performance" (carbón oscuro + acento rojo)
     ============================================================ */
  /* === SIDEBAR FIJO: siempre ocupa todo el viewport === */
  #sidebar-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    width: 260px;
    height: 100vh;
    z-index: 1030;
    background: linear-gradient(180deg, #0F172A 0%, #1E293B 60%, #111827 100%) !important;
    color: #E5E7EB;
    box-shadow: 4px 0 20px rgba(0,0,0,0.25);
    overflow-x: hidden;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    transition: margin .25s ease-out, width .25s ease-out;
  }
  /* Contenedor del scroll interno */
  #sidebar-wrapper #sidebar-nav-scroll {
    flex: 1 1 auto;
    overflow-y: auto;
  }
  /* El contenido recibe margen izquierdo igual al ancho del sidebar */
  #page-content-wrapper {
    margin-left: 260px;
    min-height: 100vh;
  }
  /* Línea de acento roja vertical (vibra deportiva) */
  #sidebar-wrapper::before {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 3px; height: 100%;
    background: linear-gradient(180deg, #EF4444 0%, #F97316 50%, #EF4444 100%);
    opacity: 0.9;
  }

  /* Brand / encabezado del sidebar — fondo oscuro con acento */
  #sidebar-wrapper .sidebar-heading {
    background: linear-gradient(135deg, #0B1120 0%, #1E293B 100%) !important;
    border-bottom: 2px solid #EF4444;
    padding: 1.1rem 1rem !important;
    box-shadow: inset 0 -8px 12px -8px rgba(239,68,68,0.25);
  }
  #sidebar-wrapper .sidebar-heading span {
    color: #F9FAFB !important;
    letter-spacing: 0.06em;
    text-shadow: 0 1px 4px rgba(0,0,0,0.4);
    font-weight: 700 !important;
    font-size: 1.05rem !important;
  }
  /* Logo del gimnasio: bordeado para destacar sobre fondo oscuro */
  #sidebar-wrapper .sidebar-heading img {
    border: 2px solid #EF4444;
    background: #fff;
    padding: 2px;
    box-shadow: 0 0 0 3px rgba(239,68,68,0.15);
  }

  /* Avatar / nombre del usuario */
  #sidebar-wrapper h6 { color: #F9FAFB !important; }
  #sidebar-wrapper p   { color: #94A3B8 !important; }
  #sidebar-wrapper .bg-light {
    background: rgba(255,255,255,0.05) !important;
    border-color: rgba(99,102,241,0.4) !important;
  }
  #sidebar-wrapper .bg-light i { color: #CBD5E1 !important; }

  /* Etiquetas de sección (Inicio / Gestión / Administración) */
  #sidebar-wrapper p.text-dark {
    color: #94A3B8 !important;
    text-transform: uppercase;
    font-size: 0.72rem !important;
    letter-spacing: 0.12em;
    border-left: 3px solid #EF4444;
    padding-left: 0.6rem !important;
  }

  /* Items del menú */
  #sidebar-wrapper .list-group {
    background: transparent !important;
  }
  #sidebar-wrapper .list-group-item {
    background-color: transparent !important;
    color: #CBD5E1 !important;
    border: none !important;
    border-radius: 10px !important;
    padding: 0.65rem 0.9rem !important;
    font-weight: 500;
    transition: all .18s ease;
    position: relative;
  }
  #sidebar-wrapper .list-group-item i {
    color: #94A3B8;
    transition: color .18s ease, transform .18s ease;
    width: 22px;
    text-align: center;
  }
  #sidebar-wrapper .list-group-item:hover {
    background: linear-gradient(90deg, rgba(239,68,68,0.15), rgba(99,102,241,0.10)) !important;
    color: #FFFFFF !important;
    transform: translateX(2px);
  }
  #sidebar-wrapper .list-group-item:hover i {
    color: #F97316;
    transform: scale(1.1);
  }
  #sidebar-wrapper .list-group-item.active {
    background: linear-gradient(90deg, #EF4444 0%, #B91C1C 100%) !important;
    color: #FFFFFF !important;
    font-weight: 700;
    box-shadow: 0 6px 14px rgba(239,68,68,0.35);
  }
  #sidebar-wrapper .list-group-item.active i {
    color: #FFFFFF;
  }
  #sidebar-wrapper .list-group-item.text-danger {
    color: #FCA5A5 !important;
  }
  #sidebar-wrapper .list-group-item.text-danger:hover {
    background: rgba(239,68,68,0.18) !important;
    color: #FFFFFF !important;
  }

  /* Botón cerrar (móvil) */
  #sidebar-wrapper #close-menu-btn {
    color: #E5E7EB !important;
  }

  /* Scrollbar estilizado */
  #sidebar-wrapper::-webkit-scrollbar,
  #sidebar-nav-scroll::-webkit-scrollbar { width: 6px; }
  #sidebar-wrapper::-webkit-scrollbar-thumb,
  #sidebar-nav-scroll::-webkit-scrollbar-thumb {
    background: rgba(239,68,68,0.35);
    border-radius: 3px;
  }

  /* ============================================================
     RESPONSIVIDAD GLOBAL — Desktop / Tablet / Móvil
     ============================================================ */
  #wrapper {
    min-height: 100vh;
    display: flex;
    align-items: stretch;       /* fuerza que sidebar y contenido tengan el mismo alto */
  }

  /* Page wrapper general */
  #page-content-wrapper {
    width: 100%;
    min-width: 0;
    transition: all 0.2s ease-out;
  }

  /* === DESKTOP grande (>= 1200px) === */
  @media (min-width: 1200px) {
    #sidebar-wrapper { width: 260px; }
    #page-content-wrapper { margin-left: 260px; }
  }

  /* === DESKTOP estándar (992px – 1199px) === */
  @media (min-width: 992px) and (max-width: 1199px) {
    #sidebar-wrapper { width: 230px; }
    #page-content-wrapper { margin-left: 230px; }
    #sidebar-wrapper .list-group-item { font-size: 0.88rem; padding: 0.55rem 0.7rem !important; }
  }

  /* === TABLET (768px – 991px): sidebar compacto, siempre visible === */
  @media (min-width: 768px) and (max-width: 991px) {
    #sidebar-wrapper {
      width: 78px !important;
      overflow-x: hidden;
    }
    #page-content-wrapper { margin-left: 78px; }
    #sidebar-wrapper .sidebar-heading span,
    #sidebar-wrapper .sidebar-heading #close-menu-btn,
    #sidebar-wrapper .list-group-item span,
    #sidebar-wrapper p,
    #sidebar-wrapper h6,
    #sidebar-wrapper .kpi-subtitle,
    #sidebar-wrapper .text-dark.fw-bold { display: none !important; }
    #sidebar-wrapper .sidebar-heading { padding: 0.8rem 0.5rem !important; justify-content: center !important; }
    #sidebar-wrapper .list-group-item {
      justify-content: center;
      padding: 0.7rem !important;
      text-align: center;
    }
    #sidebar-wrapper .list-group-item i {
      font-size: 1.25rem;
      margin: 0 !important;
    }
    /* Avatar/info usuario en tablet: solo avatar */
    #sidebar-wrapper .text-center.mt-3.mb-4 > div:not(:first-child) { display: none; }
    /* Bubbles ocultas */
    #sidebar-wrapper .d-flex.justify-content-center.gap-3 { display: none !important; }
    /* Sección con borde rojo a izquierda: ocultar texto, dejar línea como separador */
    #sidebar-wrapper p.text-dark { display: block !important; height: 1px; padding: 0 !important; margin: 0.6rem 0.5rem !important; border-left: 0 !important; border-top: 1px solid rgba(239,68,68,0.4); }
  }

  /* === MÓVIL (< 768px): sidebar overlay deslizable === */
  @media (max-width: 767.98px) {
    #sidebar-wrapper {
      transform: translateX(-260px);
      z-index: 1050;
      width: 240px;
    }
    #wrapper.toggled #sidebar-wrapper {
      transform: translateX(0);
    }
    /* El contenido NO tiene margen izquierdo en móvil */
    #page-content-wrapper { margin-left: 0 !important; }
    #wrapper.toggled::after {
      content: '';
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.55);
      z-index: 1040;
      backdrop-filter: blur(2px);
    }
    /* Padding general más compacto */
    #page-content-wrapper .container-fluid { padding: 0.75rem !important; }
  }

  /* === Tablas: scroll horizontal en pantallas chicas === */
  @media (max-width: 991px) {
    .table-responsive { -webkit-overflow-scrolling: touch; }
    .table { font-size: 0.85rem; }
    .table th, .table td { white-space: nowrap; }
  }

  /* === Botones de acción más cómodos en móvil === */
  @media (max-width: 575.98px) {
    .btn { padding: 0.45rem 0.7rem; font-size: 0.85rem; }
    .btn-sm { padding: 0.3rem 0.5rem; }
    h1, h2, h3 { font-size: 1.25rem !important; }
    h4 { font-size: 1.05rem !important; }
    .kpi-value { font-size: 1.4rem !important; }
    /* Acciones en línea: que se envuelvan */
    .d-flex.gap-2 { flex-wrap: wrap; }
  }

  /* === Inputs y selects más altos en móvil para mejor toque === */
  @media (max-width: 767.98px) {
    .form-control, .form-select { font-size: 16px; /* evita zoom en iOS */ }
  }
</style>

<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="shadow-sm" id="sidebar-wrapper">
        <!-- Sidebar Brand / Logo -->
        <div class="sidebar-heading p-4 d-flex align-items-center justify-content-center">
            <?php if(!empty($config['logo'])): ?>
                <img src="/img/<?= $config['logo'] ?>?v=<?= time() ?>" 
                     alt="Logo" width="30" height="30" 
                     class="d-inline-block align-text-top rounded-circle me-2">
            <?php endif; ?>
            <span class="fs-5 fw-bold text-dark tracking-wider"><?= $config['nombre_sistema'] ?></span>
            <button class="btn btn-sm text-dark ms-auto d-md-none" id="close-menu-btn"><i class="fas fa-times fs-4"></i></button>
        </div>

        <!-- User Profile Area -->
        <div class="text-center mt-3 mb-4 px-4">
            <div class="position-relative d-inline-block mb-3">
                <div class="bg-light rounded-circle shadow-sm d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; border: 3px solid #E0E7FF;">
                    <i class="fas fa-user text-muted" style="font-size: 2.5rem;"></i>
                </div>
                <!-- Online status dot -->
                <span class="position-absolute bottom-0 end-0 p-2 bg-success border border-light rounded-circle" style="transform: translate(-10px, -5px);"></span>
            </div>
            <h6 class="fw-bold text-dark mb-1"><?= $_SESSION['user_name'] ?? 'Usuario' ?></h6>
            <p class="text-muted small mb-0"><?= ucfirst($_SESSION['user_rol'] ?? 'Invitado') ?></p>
        </div>

        <!-- Action Bubbles -->
        <div class="d-flex justify-content-center gap-3 mb-4 px-3">
            <a href="/notificaciones/index" class="btn btn-primary rounded-circle shadow p-0 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: linear-gradient(135deg, #8B5CF6, #6D28D9); border:none;">
                <i class="fas fa-envelope text-white"></i>
            </a>
            <a href="/asistencia/index" class="btn rounded-circle shadow p-0 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: linear-gradient(135deg, #EC4899, #BE185D); border:none;">
                <i class="fas fa-bell text-white"></i>
            </a>
            <a href="/configuracion/index" class="btn rounded-circle shadow p-0 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: linear-gradient(135deg, #F97316, #D97706); border:none;">
                <i class="fas fa-cog text-white"></i>
            </a>
        </div>
        
        <div id="sidebar-nav-scroll">
            <div class="list-group list-group-flush mt-2 px-2">
                
                <p class="text-dark fw-bold ms-3 mt-2 mb-2 fs-6">Inicio</p>
                <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/home') ?>" href="/home/index">
                    <i class="fas fa-home w-20px text-center"></i> Dashboard
                </a>
                <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/asistencia') ?>" href="/asistencia/index">
                    <i class="fas fa-clock w-20px text-center"></i> Asistencia
                </a>
                
                <p class="text-dark fw-bold ms-3 mt-4 mb-2 fs-6">Gestión</p>
                <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/socios') ?>" href="/socios/index">
                    <i class="fas fa-users w-20px text-center"></i> Socios
                </a>

                <?php if($_rolActual === 'admin' || $_rolActual === 'recepcionista'): ?>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/caja') ?>" href="/caja/index">
                        <i class="fas fa-cash-register w-20px text-center"></i> Caja
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/suscripciones') ?>" href="/suscripciones/index">
                        <i class="fas fa-file-invoice-dollar w-20px text-center"></i> Suscripciones
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/pos') ?>" href="/pos/index">
                        <i class="fas fa-shopping-cart w-20px text-center"></i> Punto de Venta
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/notificaciones') ?>" href="/notificaciones/index">
                        <i class="fab fa-whatsapp w-20px text-center"></i> Notificaciones
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/pos/historial') ?>" href="/pos/historial">
                        <i class="fas fa-history w-20px text-center"></i> Historial de Ventas
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/facturacionelectronica/index') ?>" href="/facturacionelectronica/index">
                        <i class="fas fa-file-invoice w-20px text-center"></i> Facturación Electrónica
                    </a>
                <?php endif; ?>

                <?php if($_rolActual === 'admin'): ?>
                    <p class="text-dark fw-bold ms-3 mt-4 mb-2 fs-6">Administración</p>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/planes') ?>" href="/planes/index">
                        <i class="fas fa-tags w-20px text-center"></i> Planes
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/gastos') ?>" href="/gastos/index">
                        <i class="fas fa-money-bill-wave w-20px text-center"></i> Gastos
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/inventario/productos') ?>" href="/inventario/productos">
                        <i class="fas fa-boxes w-20px text-center"></i> Inventario
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/inventario/categorias') ?>" href="/inventario/categorias">
                        <i class="fas fa-layer-group w-20px text-center"></i> Categorías
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/reportes') ?>" href="/reportes/index">
                        <i class="fas fa-chart-line w-20px text-center"></i> Reportes
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/asistencia/reporte') ?>" href="/asistencia/reporte">
                        <i class="fas fa-clipboard-list w-20px text-center"></i> Rep. Asistencias
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/usuarios') ?>" href="/usuarios/index">
                        <i class="fas fa-user-shield w-20px text-center"></i> Usuarios
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/mantenimiento') ?>" href="/mantenimiento/index">
                        <i class="fas fa-tools w-20px text-center"></i> Mantenimiento
                    </a>
                    <a class="list-group-item list-group-item-action rounded mb-1 <?= $is_active('/configuracion/sri') ?>" href="/configuracion/sri">
                        <i class="fas fa-receipt w-20px text-center"></i> Config. SRI
                    </a>
                <?php endif; ?>
                <p class="text-dark fw-bold ms-3 mt-4 mb-2 fs-6">Sistema</p>
                <a class="list-group-item list-group-item-action rounded mb-1 text-danger" href="/auth/logout">
                    <i class="fas fa-sign-out-alt w-20px text-center"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
    <!-- /#sidebar-wrapper -->

    <!-- Save Navbar Scroll Position Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var sidebar = document.getElementById('sidebar-wrapper');
            var navScroll = document.getElementById('sidebar-nav-scroll');
            
            // Restore scroll position
            var scrollPos = localStorage.getItem('gymSidebarScrollPos');
            if (scrollPos) {
                if(sidebar) sidebar.scrollTop = parseInt(scrollPos, 10);
                if(navScroll) navScroll.scrollTop = parseInt(scrollPos, 10);
            }

            // Save scroll position
            window.addEventListener('beforeunload', function() {
                var currentScroll = (navScroll && navScroll.scrollTop > 0) ? navScroll.scrollTop : (sidebar ? sidebar.scrollTop : 0);
                localStorage.setItem('gymSidebarScrollPos', currentScroll);
            });
        });
    </script>

    <!-- Page Content -->
    <div id="page-content-wrapper" class="d-flex flex-column" style="min-height: 100vh; background-color: var(--gym-bg-light);">
        
        <!-- Mobile Navbar (Only visible on small screens) -->
        <nav class="navbar px-3 py-2 d-md-none shadow-sm" id="mobileNavbar"
             style="background: linear-gradient(135deg, #0B1120 0%, #1E293B 100%); border-bottom: 2px solid #EF4444;">
            <button class="btn btn-sm shadow-sm rounded-3" id="menu-toggle"
                    style="background: rgba(255,255,255,0.08); color:#fff; border:1px solid rgba(255,255,255,0.15);">
                <i class="fas fa-bars"></i>
            </button>
            <span class="d-flex align-items-center gap-2">
                <?php if(!empty($config['logo'])): ?>
                    <img src="/img/<?= $config['logo'] ?>?v=<?= time() ?>" width="26" height="26"
                         class="rounded-circle" style="border:1.5px solid #EF4444; background:#fff;">
                <?php endif; ?>
                <span class="fw-bold text-white"><?= htmlspecialchars($config['nombre_sistema']) ?></span>
            </span>
            <a href="/configuracion/index" class="text-white" style="font-size:1.2rem;"><i class="fas fa-cog"></i></a>
        </nav>

        <?php if (!empty($_SESSION['acceso_denegado'])): ?>
        <div class="alert alert-warning alert-dismissible border-0 m-3 shadow-sm" role="alert">
            <i class="fas fa-shield-alt me-2"></i>
            <?= htmlspecialchars($_SESSION['acceso_denegado']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['acceso_denegado']); endif; ?>

        <!-- Main content container -->
        <div class="container-fluid p-4 flex-grow-1">