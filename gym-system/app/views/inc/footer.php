        </div> <!-- /container-fluid padding -->
        
        <footer class="bg-white border-top text-center py-3 mt-auto shadow-sm text-secondary rounded">
            <small class="fw-semibold">&copy; <?= date('Y') ?> <?= $config['nombre_sistema'] ?>. Todos los derechos reservados.</small>
        </footer>
    </div> <!-- /#page-content-wrapper -->
</div> <!-- /#wrapper -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Export Libraries -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<script>
    $(document).ready(function() {
        // Lógica del menú lateral
        function checkMobileMenu() {
            if ($(window).width() <= 768) {
                if(!$("#wrapper").hasClass("toggled_by_user")) {
                    $("#wrapper").addClass("toggled");
                }
            } else {
                $("#wrapper").removeClass("toggled");
                $("#wrapper").removeClass("toggled_by_user");
            }
        }

        // Ejecutar en inicio y al redimensionar (con debounce)
        checkMobileMenu();
        var _resizeT = null;
        $(window).on('resize', function() {
            clearTimeout(_resizeT);
            _resizeT = setTimeout(checkMobileMenu, 150);
        });
        
        // Al darle clic al botón de menú
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
            $("#wrapper").addClass("toggled_by_user");
        });

        // Al darle clic al botón "x" dentro del menú en móvil
        $("#close-menu-btn").click(function(e) {
            e.preventDefault();
            $("#wrapper").addClass("toggled");
        });

        // 1. Inicializar DataTables (skip tables with data-no-datatable)
        $('.table-data:not([data-no-datatable])').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            "pageLength": 10,
            "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            "drawCallback": function(settings) {
                if (!$(settings.nTable).parent().hasClass('table-responsive')) {
                    $(settings.nTable).wrap('<div class="table-responsive"></div>');
                }
            }
        });

        // ── Global ExportHelper ──────────────────────────────────────────
        window.ExportHelper = {
            /**
             * Export an HTML table to a real .xlsx file using SheetJS
             * @param {string} tableId  - id of the <table> element
             * @param {string} filename - output filename (without extension)
             * @param {string} title    - Sheet title row
             */
            toExcel: function(tableId, filename, title) {
                var table = document.getElementById(tableId);
                if (!table) { console.warn('ExportHelper: table #' + tableId + ' not found'); return; }
                var wb = XLSX.utils.book_new();
                // Build array: title row + table rows
                var ws_data = [];
                if (title) ws_data.push([title]);
                var ws = XLSX.utils.table_to_sheet(table, {raw: false});
                // If we added a title row, shift existing data down
                if (title) {
                    var range = XLSX.utils.decode_range(ws['!ref']);
                    var newWs = {};
                    newWs['A1'] = { v: title, t: 's' };
                    // Adjust merges
                    for (var addr in ws) {
                        if (addr[0] === '!') { newWs[addr] = ws[addr]; continue; }
                        var cell_addr = XLSX.utils.decode_cell(addr);
                        var new_addr = XLSX.utils.encode_cell({ r: cell_addr.r + 1, c: cell_addr.c });
                        newWs[new_addr] = ws[addr];
                    }
                    newWs['!ref'] = XLSX.utils.encode_range({ s:{r:0,c:0}, e:{r:range.e.r+1, c:range.e.c}});
                    ws = newWs;
                }
                XLSX.utils.book_append_sheet(wb, ws, 'Reporte');
                XLSX.writeFile(wb, filename + '.xlsx');
            },

            /**
             * Export an HTML table to PDF using jsPDF + autoTable
             * @param {string} tableId  - id of the <table> element
             * @param {string} filename - output filename (without extension)
             * @param {string} title    - Document title
             */
            toPDF: function(tableId, filename, title) {
                var table = document.getElementById(tableId);
                if (!table) { console.warn('ExportHelper: table #' + tableId + ' not found'); return; }
                var { jsPDF } = window.jspdf;
                var doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

                // Header
                doc.setFontSize(16);
                doc.setTextColor(99, 102, 241);
                doc.text(title || filename, 14, 16);
                doc.setFontSize(9);
                doc.setTextColor(100, 100, 100);
                doc.text('Generado: ' + new Date().toLocaleDateString('es', {day:'2-digit',month:'2-digit',year:'numeric'}), 14, 22);
                doc.line(14, 24, 283, 24);

                doc.autoTable({
                    html: '#' + tableId,
                    startY: 28,
                    styles: { fontSize: 9, cellPadding: 3 },
                    headStyles: { fillColor: [99, 102, 241], textColor: 255, fontStyle: 'bold' },
                    alternateRowStyles: { fillColor: [248, 248, 255] },
                    didParseCell: function(data) {
                        // Color negative amounts red
                        if (data.section === 'body' && data.cell.raw &&
                            typeof data.cell.raw === 'string' && data.cell.raw.includes('-')) {
                            data.cell.styles.textColor = [239, 68, 68];
                        }
                    }
                });
                doc.save(filename + '.pdf');
            },

            /**
             * Print a specific table in a clean print window
             * @param {string} tableId - id of the <table> element
             * @param {string} title   - Print page title
             */
            print: function(tableId, title) {
                var table = document.getElementById(tableId);
                if (!table) { console.warn('ExportHelper: table #' + tableId + ' not found'); return; }
                var printWin = window.open('', '_blank', 'width=900,height=600');
                printWin.document.write('\
                    <html><head><title>' + (title || 'Reporte') + '</title>\
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">\
                    <style>\
                        body { font-family: Arial, sans-serif; padding: 20px; }\
                        h4 { color: #6366F1; margin-bottom: 4px; }\
                        small { color: #6B7280; }\
                        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 12px; }\
                        th { background: #6366F1 !important; color: white !important; padding: 8px; text-align: left; -webkit-print-color-adjust: exact; }\
                        td { padding: 7px 8px; border-bottom: 1px solid #E5E7EB; }\
                        tr:nth-child(even) td { background: #F8F8FF; }\
                        @media print { body { padding: 0; } }\
                    </style></head><body>\
                    <h4>' + (title || 'Reporte') + '</h4>\
                    <small>Generado: ' + new Date().toLocaleDateString('es') + '</small>\
                    ' + table.outerHTML + '\
                    <script>window.onload=function(){window.print();window.close()}<\/script>\
                    </body></html>\
                ');
                printWin.document.close();
            }
        };
        // ────────────────────────────────────────────────────────────────

        // Activar link del menú correspondiente a la URL actual y hacer scroll al ítem activo
        var currentPath = window.location.pathname;
        var $activeItem = null;
        $("#sidebar-wrapper .list-group-item").each(function() {
            if($(this).attr('href') === currentPath) {
                $(this).addClass('active bg-primary font-weight-bold').removeClass('text-white');
                $activeItem = $(this);
            }
        });
        // Desplazar el menú para que el ítem activo quede visible
        if ($activeItem && $activeItem.length) {
            var scrollEl = $('#sidebar-nav-scroll')[0];
            var activeEl = $activeItem[0];
            // Centrar el elemento en el contenedor
            scrollEl.scrollTop = activeEl.offsetTop - (scrollEl.clientHeight / 2) + (activeEl.clientHeight / 2);
        }

        // 2. Lógica para SweetAlert al Eliminar/Desactivar
        $('.btn-confirm').on('click', function(e) {
            e.preventDefault(); // Detener el enlace
            const href = $(this).attr('href'); // Obtener la ruta
            const title = $(this).data('title') || '¿Estás seguro?';

            Swal.fire({
                title: title,
                text: "Esta acción cambiará el estado del registro.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href; // Redirigir si confirma
                }
            })
        });
    });
</script>
</body>
</html>