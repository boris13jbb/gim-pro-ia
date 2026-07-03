<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Configuraciones de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');
$options->set('chroot', __DIR__); // Permitir acceso a imágenes locales

$dompdf = new Dompdf($options);

// Función auxiliar para convertir imágenes a base64 (mejor soporte en Dompdf)
function imgToBase64($path) {
    if (!file_exists($path)) return '';
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    return 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// Rutas de imágenes generadas por el asistente
$bannerPath = 'C:\Users\victo\.gemini\antigravity\brain\76a88c12-103a-4e62-986b-efc137341b0c\gym_system_banner_1773630206918.png';
$diagramPath = 'C:\Users\victo\.gemini\antigravity\brain\76a88c12-103a-4e62-986b-efc137341b0c\deployment_diagram_1773630248832.png';

$banner64 = imgToBase64($bannerPath);
$diagram64 = imgToBase64($diagramPath);

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manual Técnico - Gym System</title>
    <style>
        @page { margin: 0px; }
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; margin: 0; padding: 0; line-height: 1.6; }
        
        /* Portada */
        .cover { height: 100vh; background-color: #1e1b4b; color: white; text-align: center; position: relative; }
        .cover-img { width: 100%; max-height: 400px; object-fit: cover; }
        .cover-content { padding: 50px; margin-top: 50px; }
        .cover h1 { font-size: 48px; margin-bottom: 10px; color: #818cf8; letter-spacing: 2px; }
        .cover h2 { font-size: 24px; font-weight: normal; color: #cbd5e1; margin-bottom: 50px; }
        .cover .footer { position: absolute; bottom: 50px; width: 100%; font-size: 14px; color: #94a3b8; }

        /* Contenido General */
        .page { padding: 50px 60px; page-break-after: always; }
        h1.section-title { color: #4338ca; font-size: 28px; border-bottom: 2px solid #e0e7ff; padding-bottom: 10px; margin-top: 0; margin-bottom: 30px; }
        h2 { color: #312e81; font-size: 20px; margin-top: 30px; margin-bottom: 15px; }
        h3 { color: #4f46e5; font-size: 16px; margin-bottom: 10px; }
        p, li { font-size: 14px; color: #475569; }
        ul { margin-bottom: 20px; }
        li { margin-bottom: 8px; }
        
        /* Cajas de código y terminal */
        .code-block { background-color: #1e293b; color: #10b981; padding: 15px; border-radius: 8px; font-family: "Courier New", Courier, monospace; font-size: 13px; margin: 20px 0; overflow-x: auto; break-inside: avoid; }
        
        /* Credenciales resaltadas */
        .credentials { background-color: #ecfdf5; border-left: 5px solid #10b981; padding: 20px; border-radius: 4px; margin: 20px 0; break-inside: avoid; }
        .credentials strong { color: #065f46; }

        /* Diagrama */
        .diagram-container { text-align: center; margin: 40px 0; padding: 20px; background: #f8fafc; border-radius: 12px; break-inside: avoid; }
        .diagram-container img { max-width: 100%; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

        /* Footer en páginas */
        #footer { position: fixed; bottom: 20px; width: 100%; text-align: center; font-size: 11px; color: #94a3b8; }
        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>

    <!-- PORTADA -->
    <div class="cover">
        ' . ($banner64 ? '<img src="'.$banner64.'" class="cover-img">' : '<div style="height:350px; background:linear-gradient(135deg, #1e1b4b, #4338ca);"></div>') . '
        <div class="cover-content">
            <h1>GYM SYSTEM</h1>
            <h2>Manual Técnico y de Usuario</h2>
            <div style="width: 100px; height: 5px; background-color: #6366f1; margin: 0 auto;"></div>
            <p style="margin-top: 40px; font-size: 18px; color: #cbd5e1;">Documentación oficial del sistema</p>
        </div>
        <div class="footer">
            Versión 1.0.0 &bull; '.date('Y').' &bull; Confidencial
        </div>
    </div>

    <div id="footer">Gym System - Página <span class="pagenum"></span></div>

    <!-- PÁGINA 1: TECNOLOGÍAS Y FUNCIONALIDADES -->
    <div class="page">
        <h1 class="section-title"><img src="data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4338ca" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>').'" style="width:24px; vertical-align:middle; margin-right:10px;"> 1. Tecnologías Utilizadas</h1>
        
        <p>El sistema <strong>Gym System</strong> ha sido desarrollado utilizando un stack tecnológico moderno, robusto y 100% nativo para garantizar el máximo rendimiento sin dependencias de frameworks pesados de backend:</p>
        
        <ul>
            <li><strong>Backend:</strong> PHP 8.x (Patrón de diseño MVC puro, programación orientada a objetos).</li>
            <li><strong>Base de Datos:</strong> MySQL 8 (Utilizando PDO para alta seguridad contra inyecciones SQL).</li>
            <li><strong>Frontend Interfaz:</strong> HTML5, CSS3, Vanilla JavaScript nativo.</li>
            <li><strong>Framework UI:</strong> Bootstrap 5.3 (Para un diseño responsive, sistema de grillas y componentes premium).</li>
            <li><strong>Iconos y Gráficos:</strong> FontAwesome 6, Chart.js (para gráficos de ingresos y progresos).</li>
            <li><strong>Generación PDF:</strong> Biblioteca FPDF y Dompdf.</li>
            <li><strong>Notificaciones:</strong> Integración con CallMeBot API para WhatsApp.</li>
        </ul>

        <h1 class="section-title" style="margin-top: 40px;"><img src="data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4338ca" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>').'" style="width:24px; vertical-align:middle; margin-right:10px;"> 2. Módulos y Funcionalidades</h1>
        
        <h2>2.1. Panel Principal (Dashboard)</h2>
        <p>Resumen gerencial en tiempo real. Muestra 4 KPI fundamentales: Ingresos mensuales, Socios activos, Subscripciones e Inventario. Incluye gráficos de barras de ventas y alertas de suscripciones próximas a vencer.</p>

        <h2>2.2. Gestión de Socios y Progreso</h2>
        <p>Control total del expediente del cliente. Búsqueda en tiempo real por correo o DNI, generación de Carnet Digital PDF único por socio. Incluye un submódulo de <strong>Progreso</strong> para registrar peso, % de grasa y medidas musculares, visualizándolas en gráficas interactivas; además de asignación de rutinas de entrenamiento.</p>

        <h2>2.3. Control de Asistencias</h2>
        <p>Interfaz rápida para que recepción valide el ingreso con el DNI. Muestra foto del socio, estado de su pago, días restantes y arroja alertas visuales si están vencidos. Incluye un reporte histórico filtrable exportable a PDF con ranking de los socios más frecuentes.</p>

        <h2>2.4. Suscripciones y Planes</h2>
        <p>Creación de planes a medida (mensual, trimestral, anual). Emite recibos PDF de pago. Control estricto de fechas de inicio y vencimiento con actualización automática de estados.</p>

        <h2>2.5. Punto de Venta (POS) e Inventario</h2>
        <p>Un TPV estilo supermercado para venta rápida de suplementos. Carrito dinámico, calculadora de vuelto, multimetodo de pago (Efectivo/Tarjeta/Transferencia) y módulo de Descuentos. Control automático de stock. Cuenta con vista de <strong>Historial de Ventas</strong> con filtros y detalles exactos de cada ticket.</p>
        
        <h2>2.6. Reportes Financieros y Alertas WhatsApp</h2>
        <p>Generación de reportes de rentabilidad (Cálculo de Ingresos - Gastos = Beneficio Neto) en Excel y PDF. Módulo de envío masivo/individual de recordatorios de cobro vía WhatsApp usando API automática.</p>
    </div>

    <!-- PÁGINA 2: INSTALACIÓN Y EJECUCIÓN -->
    <div class="page">
        <h1 class="section-title"><img src="data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4338ca" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>').'" style="width:24px; vertical-align:middle; margin-right:10px;"> 3. Instalación Local y Ejecución</h1>
        
        <h3>Prerrequisitos en la PC nueva:</h3>
        <ul>
            <li><strong>XAMPP o Laragon:</strong> Para contar con Apache/Nginx, PHP (8.x) y MySQL.</li>
            <li><strong>Composer:</strong> Gestor de dependencias de PHP.</li>
            <li><strong>Git (Opcional):</strong> Para clonar el repositorio.</li>
        </ul>

        <h3>Paso a Paso de Instalación:</h3>
        <p>1. Copiar la carpeta del proyecto <code>gym-system</code> dentro del directorio del servidor local (ej: <code>c:\laragon\www\</code> o <code>htdocs</code>).</p>
        <p>2. Importar la base de datos: Entrar a phpMyAdmin o consola MySQL, crear una base de datos llamada <code>gym_system</code>, e importar el archivo <code>database.sql</code> (o generar las tablas acorde al esquema provisto).</p>
        <p>3. Configurar conexión: Editar el archivo <code>app/config/Database.php</code> y poner credenciales (generalmente user: root, password vacío en local).</p>
        <p>4. Instalar dependencias usando la consola (CMD/Powershell) en la raíz del proyecto:</p>
        
        <div class="code-block">
C:\laragon\www\gym-system> composer install<br>
C:\laragon\www\gym-system> composer dump-autoload
        </div>

        <h3>Cómo ejecutar el sistema:</h3>
        <p>Existen dos formas de ejecutar el sistema localmente:</p>
        
        <strong>Opción A: Usando el servidor integrado de PHP (Desde CMD)</strong>
        <div class="code-block">
C:\laragon\www\gym-system> php -S localhost:8000 -t public
        </div>
        <p>Luego abrir el navegador en: <code>http://localhost:8000</code></p>

        <strong>Opción B: Usando Laragon/XAMPP</strong><br>
        <p>Encender Apache y MySQL desde el panel de control. El sistema estará disponible en <code>http://gym-system.test</code> o <code>http://localhost/gym-system/public</code> dependiendo del entorno.</p>

        <h1 class="section-title" style="margin-top: 40px;"><img src="data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4338ca" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>').'" style="width:24px; vertical-align:middle; margin-right:10px;"> 4. Credenciales de Administrador</h1>
        
        <div class="credentials">
            <p style="margin:0; font-size:16px;">Para acceder por primera vez y tener control total del sistema, utilice las siguientes credenciales maestras:</p>
            <table style="width:100%; margin-top:15px; font-size:16px;">
                <tr>
                    <td style="width:120px; padding:5px 0;"><strong>Usuario/Email:</strong></td>
                    <td style="font-family:monospace; font-size:18px;">admin@gym.com</td>
                </tr>
                <tr>
                    <td style="padding:5px 0;"><strong>Contraseña:</strong></td>
                    <td style="font-family:monospace; font-size:18px;">123456</td>
                </tr>
                <tr>
                    <td style="padding:5px 0;"><strong>Rol Nivel:</strong></td>
                    <td>Administrador Absoluto</td>
                </tr>
            </table>
        </div>
        <p><small>* Se recomienda vehementemente cambiar esta contraseña en el submódulo <strong>Sistema > Usuarios</strong> luego de la primera autenticación en entornos de producción.</small></p>
    </div>

    <!-- PÁGINA 3: HOSTING Y DESPLIEGUE -->
    <div class="page">
        <h1 class="section-title"><img src="data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4338ca" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>').'" style="width:24px; vertical-align:middle; margin-right:10px;"> 5. Guía de Hosting y Despliegue</h1>
        
        <h3>Recomendación de Servidores (Hosting)</h3>
        <p>Dado que el sistema está fabricado en PHP puro (MVC) y MySQL, es altamente compatible con el 99% del mercado de Hostings compartidos. <strong>No requiere VPS costosos ni contenedores Docker (a menos que escale masivamente).</strong></p>
        
        <ul>
            <li>🥇 <strong>Hostinger (Premium o Business Hosting):</strong> La mejor opción calidad-precio. Interfaz cPanel propia (hPanel), discos NVMe muy rápidos y soporte PHP 8.x nativo. Ideal para este software. Costo aprox: $3 USD/mes.</li>
            <li>🥈 <strong>SiteGround (StartUp):</strong> Excelente soporte y seguridad extremas, pero con un costo de renovación un poco superior.</li>
            <li>🥉 <strong>Bluehost / GoDaddy:</strong> Estándar de la industria, funcionan sin problema pero la velocidad puede ser ligeramente inferior a Hostinger en planes básicos.</li>
        </ul>

        ';
        
        if ($diagram64) {
            $html .= '
            <div class="diagram-container">
                <h4 style="margin-top:0; color:#475569;">Esquema de Despliegue en la Nube</h4>
                <img src="'.$diagram64.'" alt="Diagrama de Despliegue">
            </div>';
        }

        $html .= '

        <h3>Paso a Paso Definitivo para subir la Web a Hostinger</h3>
        
        <p><strong>Paso 1: Comprar el Hosting y Dominio</strong><br>
        Adquiera el plan "Premium Web Hosting" en Hostinger. Registre su dominio (ej: <i>migimnasio.com</i>).</p>

        <p><strong>Paso 2: Comprimir el código fuente</strong><br>
        En su PC, vaya a la carpeta <code>gym-system</code>. Es muy importante que comprima en formato <strong>.zip</strong> el contenido exacto, <em>no la carpeta padre</em>, sino los archivos sueltos (`app/`, `public/`, `vendor/`, etc).</p>
        
        <p><strong>Paso 3: Subir archivos usando el Gestor de Archivos (hPanel)</strong><br>
        Entre a Hostinger -> "Administrar sitio web" -> "Gestor de Archivos". Vaya a la ruta <code>public_html</code>. Suba el archivo `.zip` que preparó y extráigalo ahí mismo. <br>
        <em>*Nota crucial de seguridad: En entornos web reales, es ideal que el Document Root apunte solo a la carpeta <code>public</code>. Hostinger permite cambiar el directorio raíz en la sección de Dominios.</em></p>

        <p><strong>Paso 4: Crear e Importar Base de Datos</strong><br>
        En hPanel, vaya a "Bases de Datos -> Gestión de MySQL". Cree una base de datos nueva (ej: `u123_gym_db`) anotando nombre, usuario y contraseña. Luego, abra <strong>phpMyAdmin</strong> desde allí e importe la estructura desde la pestaña "Importar".</p>

        <p><strong>Paso 5: Enlazar BD con la aplicación</strong><br>
        En el Gestor de Archivos de Hostinger, navegue hasta <code>app/config/Database.php</code>, edítelo y coloque la información exacta del Paso 4 (Usuario, Contraseña y Nombre de BD de Hostinger).</p>

        <p><strong>Paso 6: Certificado SSL y Listo</strong><br>
        Asegúrese de activar el certificado SSL gratuito que da Hostinger para que su URL comience con <code>https://</code>. El sistema ya estará operativo en la nube accesible desde cualquier celular o PC.</p>

        <div style="margin-top:50px; text-align:center; padding:30px; border:2px dashed #e2e8f0; border-radius:10px;">
            <h3 style="color:#10b981; margin:0;">¡Felicidades!</h3>
            <p style="margin:5px 0 0 0;">El software Gym System está listo para operar comercialmente.</p>
        </div>
    </div>
</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filepath = __DIR__ . '/Manual_Instalacion_GymSystem.pdf';
file_put_contents($filepath, $dompdf->output());

echo "PDF Generado con exito en: " . $filepath;
