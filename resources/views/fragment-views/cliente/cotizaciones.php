<div class="page-title-box">
    <div class="row align-items-center">
        <div class="clearfix">
            <h6 class="page-title float-end">Pedidos</h6>
            <ol class="breadcrumb m-0 float-start">
                <li class="breadcrumb-item"><a href="javascript: void(0);">Facturación</a></li>
                <li class="breadcrumb-item"><a href="/ventas" class="button-link">PEDIDOS</a></li>
                <li class="breadcrumb-item active" aria-current="page">Productos</li>
            </ol>
        </div>
        <div class="col-md-4">
            <div class="float-end d-none d-md-block">
                <!-- Puedes agregar más botones aquí si es necesario -->
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card"
            style="border-radius:20px;box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06)">
            <div class="card-body">
                <h4 class="card-title"></h4>
                <div class="card-title-desc text-end">
                    <?php if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 7): // el repartidor no crea pedidos, solo los convierte a venta ?>
                    <a href="/cotizaciones/add" id="folder_btn_nuevo_folder" class="btn btn-primary button-link">
                        <i class="fa fa-plus "></i> Nuevo Pedido
                    </a>
                    <?php endif; ?>
                    <?php if ($_SESSION["rol"] == 1): // la conversion masiva es solo del administrador ?>
                        <button id="btn-venta-masiva" class="btn btn-success"><i class="fa fa-bolt"></i> Vender Masivo</button>
                    <?php endif; ?>
                    <?php if ($_SESSION["rol"] == 1): ?>
                        <button id="ventas-reporte" class="btn btn-info"><i class="fa fa-file-pdf-o"></i> Exportar Reporte de Vendedores</button>
                        <button id="imprimir" class="btn btn-success"><i class="fa fa-print"></i> Imprimir PDFs</button>
                        <button id="imprimir-logistico-btn" class="btn btn-warning"><i class="fa fa-truck"></i> Cons. Logístico</button>
                    <?php endif; ?>
                </div>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 7): // filtros de reparto (solo rol REPARTIDOR)
                    // Un día de reparto entrega los pedidos tomados en los 2 días hábiles anteriores
                    // (se salta el domingo): p. ej. reparto miércoles => pedidos de lunes y martes;
                    // reparto lunes => pedidos de viernes y sábado.
                    $diasHabiles = [];
                    $cursor = new DateTime('today');
                    while (count($diasHabiles) < 2) {
                        $cursor->modify('-1 day');
                        if ($cursor->format('N') != 7) { // 7 = domingo
                            $diasHabiles[] = $cursor->format('Y-m-d');
                        }
                    }
                    $fechaHasta = $diasHabiles[0]; // día hábil anterior
                    $fechaDesde = $diasHabiles[1]; // el anterior a ese
                ?>
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-6 col-md-2">
                        <label for="f_desde" class="form-label form-label-sm fs-7">Pedidos desde</label>
                        <input type="date" id="f_desde" class="form-control form-control-sm" value="">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_hasta" class="form-label form-label-sm fs-7">Hasta</label>
                        <input type="date" id="f_hasta" class="form-control form-control-sm" value="">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_camion" class="form-label form-label-sm fs-7">Camión</label>
                        <select id="f_camion" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="1">Camión 1</option>
                            <option value="2">Camión 2</option>
                            <option value="3">Camión 3</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_dia" class="form-label form-label-sm fs-7">Día de visita</label>
                        <select id="f_dia" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="lunes">Lunes</option>
                            <option value="martes">Martes</option>
                            <option value="miercoles">Miércoles</option>
                            <option value="jueves">Jueves</option>
                            <option value="viernes">Viernes</option>
                            <option value="sabado">Sábado</option>
                            <option value="domingo">Domingo</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_ruta" class="form-label form-label-sm fs-7">Ruta</label>
                        <select id="f_ruta" class="form-select form-select-sm">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="f_mercado" class="form-label form-label-sm fs-7">Mercado</label>
                        <select id="f_mercado" class="form-select form-select-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-12 d-flex gap-2 justify-content-end">
                        <button type="button" id="f_reparto_hoy" class="btn btn-primary btn-sm" title="Pedidos de los 2 días hábiles previos"><i class="fa fa-truck"></i> Reparto de hoy</button>
                        <button type="button" id="f_limpiar" class="btn btn-secondary btn-sm"><i class="fa fa-eraser"></i> Limpiar</button>
                    </div>
                </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table id="datatable-c" class="table nowrap table-sm table-bordered text-center">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Sub. Total</th>
                                <th>IGV</th>
                                <th>Total</th>
                                <th>Vendedor</th>
                                <th>Estado</th>
                                <th>Vender</th>
                                <th>Guía Remisión</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Conversión masiva: convierte en venta todos los pedidos del rango que aún no tengan venta -->
<div class="modal fade" id="modal-venta-masiva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vender Masivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label for="vm_desde" class="form-label">Fecha inicio</label>
                        <input type="date" id="vm_desde" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="vm_hasta" class="form-label">Fecha fin</label>
                        <input type="date" id="vm_hasta" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="vm_dia" class="form-label">Día de visita</label>
                        <select id="vm_dia" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="lunes">Lunes</option>
                            <option value="martes">Martes</option>
                            <option value="miercoles">Miércoles</option>
                            <option value="jueves">Jueves</option>
                            <option value="viernes">Viernes</option>
                            <option value="sabado">Sábado</option>
                            <option value="domingo">Domingo</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="vm_vehiculo" class="form-label">Vehículo</label>
                        <select id="vm_vehiculo" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="1">Camión 1</option>
                            <option value="2">Camión 2</option>
                            <option value="3">Camión 3</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button type="button" class="btn btn-primary btn-sm" id="vm_buscar"><i class="fa fa-search"></i> Buscar pedidos</button>
                    </div>
                    <div class="col-12 col-md-2 text-md-end">
                        <span id="vm_contador" class="badge bg-secondary">0 seleccionados</span>
                    </div>
                </div>

                <div id="vm_resultado" class="mt-3"></div>

                <div class="table-responsive mt-2">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:38px;" class="text-center"><input type="checkbox" id="vm_todos" title="Marcar todos"></th>
                                <th>Pedido</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Día visita</th>
                                <th class="text-center">Ruta</th>
                                <th class="text-center">Items</th>
                                <th class="text-end">Total</th>
                                <th>Vendedor</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="vm_lista">
                            <tr><td colspan="10" class="text-muted text-center">Elija el rango de fechas y pulse "Buscar pedidos".</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="vm_convertir" disabled><i class="fa fa-bolt"></i> Convertir seleccionados</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="imprimir-pdfs-bs">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Imprimir Reportes</h5>
                <button type="button" style="color: red; border-radius: 5px; border: solid 1px red ;" class="close"
                    id="btnCerrarModalX" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="scroll-horizontal overflow-auto p-2">
                    <button id="btnImprimirPorNumeros" class="btn btn-custom">Por Números</button>
                    <button id="btnImprimirPorFechas" class="btn btn-custom">Por Fechas</button>
                    <button id="btnImprimirVisitas" class="btn btn-custom">Por Días de Visita</button>
                    <button id="btnImprimirPorCamion" class="btn btn-custom">Por Camión</button>
                    <button id="btnImprimirPorRuta" class="btn btn-custom">Por Ruta</button>
                    <button id="btnImprimirPorMercado" class="btn btn-custom">Por Mercado</button>
                    <button id="btnImprimirPorMedida" class="btn btn-custom">Por Medida</button>
                </div>
                <div id="imprimirPorNumeros" class="mt-3">
                    <label for="numeroInicio">Número de Documento Inicio:</label>
                    <input type="number" id="numeroInicio" class="form-control">
                    <label for="numeroFin">Número de Documento Fin:</label>
                    <input type="number" id="numeroFin" class="form-control">
                </div>
                <div id="imprimirPorFechas" class="mt-3" style="display:none;">
                    <label for="fechaSeleccionada">Fecha:</label>
                    <input type="date" id="fechaSeleccionada" class="form-control">
                    <label for="fechaFinSeleccionada">Fecha Fin:</label>
                    <input type="date" id="fechaFinSeleccionada" class="form-control">
                </div>

                <div id="imprimirPorVisitas" class="mt-3" style="display:none;">
                    <label for="diasVisita">Día de visita:</label>
                    <select class="form-control" id="diasVisita">
                        <option value=""></option>
                        <option value="lunes">Lunes</option>
                        <option value="martes">Martes</option>
                        <option value="miercoles">Miércoles</option>
                        <option value="jueves">Jueves</option>
                        <option value="viernes">Viernes</option>
                        <option value="sabado">Sábado</option>
                        <option value="domingo">Domingo</option>
                    </select>
                </div>

                <div id="imprimirPorCamion" class="mt-3" style="display:none;">
                    <label for="camion">Camión:</label>
                    <input type="hidden" id="camionconsolodidado" class="form-control">
                    <select class="form-control" id="camion">
                        <option value=""></option>
                        <option value="0">::Todos::</option>
                        <option value="1">Camión 1</option>
                        <option value="2">Camión 2</option>
                        <option value="3">Camión 3</option>
                    </select>
                    <label for="filtro-horario-camion" class="mt-2">Filtrar por horario:</label>
                    <select id="filtro-horario-camion" class="form-control">
                        <option value="todos">Todos</option>
                        <option value="primer_corte">PRIMER CORTE — Pedidos base (00:00 - 08:00 día carga)</option>
                        <option value="segundo_corte">SEGUNDO CORTE — Aumentos (08:00 - 15:00 día carga)</option>
                        <option value="tercer_corte">TERCER CORTE — Aumentos (15:00 - 23:59 día carga)</option>
                    </select>
                </div>
                <div id="imprimirPorRuta" class="mt-3" style="display:none;">
                    <label for="ruta">Rutas:</label>
                    <select class="form-control" id="ruta">
                    </select>
                </div>
                <div id="imprimirPorMercado" class="mt-3" style="display:none;">
                    <label for="mercado">Mercado:</label>
                    <select class="form-control" id="mercado">
                    </select>
                    <label for="filtro-horario" class="mt-2">Filtrar por horario:</label>
                    <select id="filtro-horario" class="form-control">
                        <option value="todos">Todos</option>
                        <option value="primer_corte">PRIMER CORTE — Pedidos base (00:00 - 08:00 día carga)</option>
                        <option value="segundo_corte">SEGUNDO CORTE — Aumentos (08:00 - 15:00 día carga)</option>
                        <option value="tercer_corte">TERCER CORTE — Aumentos (15:00 - 23:59 día carga)</option>
                    </select>
                </div>
                <div id="imprimirPorMedida" class="mt-3" style="display:none;">
                    <label for="medida">Medida:</label>
                    <select class="form-control" id="medida">
                    </select>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnPorCliente" style="display:none;">Cliente</button>
                <button type="button" class="btn btn-primary" id="btnConsolidado"
                    style="display:none;">Consolidado</button>
                <button type="button" class="btn btn-primary" id="btnImprimir">Imprimir</button>
                <button type="button" class="btn btn-secondary" id="btnCerrarModal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="imprimir-logistico-bs">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #ffc107; color: black;">
                <h5 class="modal-title">Imprimir Consolidado Logístico</h5>
                <button type="button" style="color: red; border-radius: 5px; border: solid 1px red ;" class="close" id="btnCerrarModalLogisticoX" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mt-2">
                    <label for="fechaSeleccionadaLog">Fecha:</label>
                    <input type="date" id="fechaSeleccionadaLog" class="form-control">
                    <label for="fechaFinSeleccionadaLog">Fecha Fin:</label>
                    <input type="date" id="fechaFinSeleccionadaLog" class="form-control">
                </div>
                <div class="mt-3">
                    <label for="camionLog">Camión:</label>
                    <select class="form-control" id="camionLog">
                        <option value=""></option>
                        <option value="0">::Todos::</option>
                        <option value="1">Camión 1</option>
                        <option value="2">Camión 2</option>
                        <option value="3">Camión 3</option>
                    </select>
                </div>
                <div class="mt-3">
                    <label for="diasVisitaLog">Día de visita (Opcional):</label>
                    <select class="form-control" id="diasVisitaLog">
                        <option value=""></option>
                        <option value="lunes">Lunes</option>
                        <option value="martes">Martes</option>
                        <option value="miercoles">Miércoles</option>
                        <option value="jueves">Jueves</option>
                        <option value="viernes">Viernes</option>
                        <option value="sabado">Sábado</option>
                        <option value="domingo">Domingo</option>
                    </select>
                </div>
                <div class="mt-3">
                    <label for="horarioLog">Corte de horario (Opcional):</label>
                    <select id="horarioLog" class="form-control">
                        <option value="todos">Todos</option>
                        <option value="primer_corte">PRIMER CORTE — Pedidos base (00:00 - 08:00 día carga)</option>
                        <option value="segundo_corte">SEGUNDO CORTE — Aumentos (08:00 - 15:00 día carga)</option>
                        <option value="tercer_corte">TERCER CORTE — Aumentos (15:00 - 23:59 día carga)</option>
                    </select>
                </div>
                <div class="mt-3">
                    <label for="medidaLog">Filtrar por Medida (Opcional):</label>
                    <select class="form-control" id="medidaLog">
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" id="btnImprimirLogisticoFinal">Generar Reporte Logístico</button>
                <button type="button" class="btn btn-secondary" id="btnCerrarModalLogistico">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos CSS personalizados -->
<style>
    /* Estilo del modal */
    .modal-content {
        border-radius: 10px;
        background-color: #f9f9f9;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    /* Título del modal */
    .modal-header {
        background-color: #007bff;
        color: white;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }

    .close {
        color: white;
        font-size: 1.5rem;
    }

    .btn-custom {
        border-radius: 5px;
        border: 2px solid transparent;
        background-color: #e0e0e0;
        color: #333;
        padding: 10px 15px;
        font-weight: bold;
        transition: all 0.3s ease;
    }

    .btn-custom:hover {
        background-color: #d4d4d4;
    }

    .btn-custom.active {
        border-color: #007bff;
        background-color: #007bff;
        color: white;
    }

    .mt-3 {
        margin-top: 1.5rem !important;
    }

    .form-control {
        margin-top: 10px;
    }

    /* Ancho de la barra de desplazamiento */
    .scroll-horizontal::-webkit-scrollbar {
        width: 5px;
        height: 8px;
        /* para scroll horizontal */
    }

    /* Color de la pista (fondo de la barra de desplazamiento) */
    .scroll-horizontal::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    /* Color del "thumb" (barra de desplazamiento) */
    .scroll-horizontal::-webkit-scrollbar-thumb {
        background-color: #888;
        border-radius: 6px;
        border: 2px solid #f1f1f1;
        /* Espacio alrededor del thumb */
    }

    /* Color del "thumb" al pasar el ratón (hover) */
    .scroll-horizontal::-webkit-scrollbar-thumb:hover {
        background-color: #555;
    }

    .scroll-horizontal {
        white-space: nowrap;
        overflow-x: auto;
    }

    .scroll-horizontal div {
        display: inline-block;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        margin-right: 10px;
        text-align: center;
        line-height: 100px;
    }
</style>

<!-- Script JavaScript para cambiar el estilo de los botones al hacer clic -->
<script>
    // Evitar conflictos de variables en navegación AJAX
    // Se re-consulta en cada carga del fragmento: los botones son nodos nuevos cada vez.
    document.querySelectorAll('.btn-custom').forEach(boton => {
        boton.addEventListener('click', () => {
            document.querySelectorAll('.btn-custom').forEach(b => b.classList.remove('active'));
            boton.classList.add('active');
        });
    });
</script>

<iframe id="printFrame" style="display:none;"></iframe>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>

<script>
    // Evitar conflictos de variables en navegación AJAX
    if (typeof window.rol_usuario_cotizaciones === 'undefined') {
        window.rol_usuario_cotizaciones = <?php echo $_SESSION["rol"]; ?>;
    }
    // 'var' y no 'let': este fragmento se vuelve a inyectar al navegar por AJAX; un 'let' global
    // lanzaria \"Identifier 'rol_usuario' has already been declared\" y el script entero dejaria de ejecutarse.
    var rol_usuario = window.rol_usuario_cotizaciones;
    $(document).ready(function () {
        var tabla = $("#datatable-c").DataTable({
            "processing": true,
            "serverSide": true,
            "sAjaxSource": _URL + "/data/cotizaciones/lista/ss",
            // Filtros de reparto: se envían en cada petición (solo existen para el rol REPARTIDOR)
            "fnServerParams": function (aoData) {
                if ($("#f_desde").length) {
                    aoData.push({ name: "f_desde", value: $("#f_desde").val() });
                    aoData.push({ name: "f_hasta", value: $("#f_hasta").val() });
                    aoData.push({ name: "f_camion", value: $("#f_camion").val() });
                    aoData.push({ name: "f_dia", value: $("#f_dia").val() });
                    aoData.push({ name: "f_ruta", value: $("#f_ruta").val() });
                    aoData.push({ name: "f_mercado", value: $("#f_mercado").val() });
                }
            },
            order: [
                [0, "desc"]
            ],
            columnDefs: [
                <?php if ($_SESSION["rol"] != 3): ?>
                    {
                        targets: 8,
                        render(data, type, row) {
                            // Usar row[10] que es cotizacion_id
                            var cotizacionId = row[10];
                            // row[7] = estado: '1' significa que el pedido YA se convirtió en venta.
                            // Vuelve a habilitarse solo si esa venta se anula.
                            if (row[7] == '1') {
                                return `<button type="button" class="btn btn-secondary btn-sm" disabled style="opacity:0.5;cursor:not-allowed;" title="Pedido ya convertido en venta. Anule la venta para volver a convertirlo"><i class="fa fa-lock"></i></button>`;
                            }
                            return `<a href="/ventas/productos?coti=${cotizacionId}" class="btn btn-success btn-sm button-link"><i class="fa fa-align-justify"></i></a>`;
                        }
                    },
                <?php else: ?>
                    {
                        targets: 8,
                        render(data) {
                            return ``;
                        }
                    },
                <?php endif; ?>
            {
                    targets: 7,
                    render: function (data) {
                        return data == '1'
                            ? '<span class="badge rounded-pill bg-success">Vendido</span>'
                            : '<span class="badge rounded-pill bg-danger">No Vendido</span>';
                    }
                },
                {
                    targets: 10,
                    render: function (data, type, row) {
                        // Usar row[10] que es cotizacion_id
                        var cotizacionId = row[10];
                        // row[11] es fecha_registro
                        var fechaRegistro = row[11];

                        // Calcular si han pasado 40 horas (solo para vendedores - rol 3)
                        var disabled = false;
                        if (rol_usuario == 3) {
                            var fechaCreacion = new Date(fechaRegistro);
                            var fechaActual = new Date();
                            var diferenciaMs = fechaActual - fechaCreacion;
                            var horasTranscurridas = diferenciaMs / (1000 * 60 * 60);

                            // Deshabilitar botones si pasaron 40 horas o más
                            disabled = horasTranscurridas >= 40;
                        }

                        var disabledAttr = disabled ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '';
                        var disabledClass = disabled ? 'disabled' : '';

                        <?php if ($_SESSION["rol"] == 7): // REPARTIDOR: solo convierte pedidos a venta, no edita ni elimina ?>
                        return `
                        <a target="_blank" href="${'./cotizaciones/reporteCuotas/' + cotizacionId}" class="btn btn-sm btn-light"><i class="bi bi-filetype-pdf"></i></a>
                        <a href="${_URL + '/r/cotizaciones/reporte/' + cotizacionId}" target="_blank" class="btn btn-sm btn-info"><i class="fa fa-file"></i></a>
                        <a href="${_URL + '/r/cotizaciones/reporteA4/' + cotizacionId}" target="_blank" class="btn btn-sm btn-warning"><i class="fa fa-file"></i></a>
                    `;
                        <?php else: ?>
                        return `
                        <a target="_blank" href="${'./cotizaciones/reporteCuotas/' + cotizacionId}" class="btn btn-sm btn-light"><i class="bi bi-filetype-pdf"></i></a>
                        <a href="${disabled ? 'javascript:void(0)' : '/cotizaciones/edt/' + cotizacionId}" class="button-link btn btn-sm btn-primary ${disabledClass}" ${disabledAttr}><i class="fa fa-edit"></i></a>
                        <a href="${_URL + '/r/cotizaciones/reporte/' + cotizacionId}" target="_blank" class="btn btn-sm btn-info"><i class="fa fa-file"></i></a>
                        <a href="${_URL + '/r/cotizaciones/reporteA4/' + cotizacionId}" target="_blank" class="btn btn-sm btn-warning"><i class="fa fa-file"></i></a>
                        <button onclick="${disabled ? 'return false' : 'eliminarCotizacion(' + cotizacionId + ')'}" type="button" class="btn-del btn btn-danger btn-sm" ${disabledAttr}><i class="fa fa-times"></i></button>
                    `;
                        <?php endif; ?>
                    }
                },
                <?php if ($_SESSION["rol"] != 3 && $_SESSION["rol"] != 7): // el repartidor tampoco genera guías de remisión ?>
                    {
                        targets: 9,
                        render(data, type, row) {
                            // Usar row[10] que es cotizacion_id
                            var cotizacionId = row[10];
                            return `<a href="/guia/remision/registrar?coti=${cotizacionId}" class="btn btn-success btn-sm button-link"><i class="fa fa-clipboard"></i></a>`;
                        }
                    }
            <?php elseif ($_SESSION["rol"] == 7): ?>
                    {
                        // Repartidor: la columna Guía Remisión se oculta por completo (encabezado incluido).
                        // Se oculta en vez de quitar el <th> para no desplazar los índices de las demás columnas.
                        targets: 9,
                        visible: false
                    }
            <?php else: ?>
                    {
                        targets: 9,
                        render(data) {
                            return ``;
                        }
                    }
            <?php endif; ?>
            ]
        });

    // Filtros de reparto: al cambiar cualquiera se recarga la tabla desde el servidor
    $("#f_desde, #f_hasta, #f_camion, #f_dia, #f_ruta, #f_mercado").on("change", function () {
        tabla.ajax.reload();
    });

    // Rutas y mercados de los filtros de reparto
    if ($("#f_ruta").length) {
        $.ajax({
            url: _URL + '/ajs/admin/cliente/rutas',
            method: 'GET',
            success: function (response) {
                try { if (typeof response === "string") response = JSON.parse(response); } catch (e) { return; }
                var lista = [];
                $.each(response, function (idx, res) { if (res.id_ruta !== '' && res.id_ruta !== null) lista.push(res.id_ruta); });
                lista.sort(function (a, b) { return parseFloat(a) - parseFloat(b); });
                var options = '<option value="">Todas</option>';
                $.each(lista, function (idx, r) { options += '<option value="' + r + '">Ruta ' + r + '</option>'; });
                $("#f_ruta").html(options);
            }
        });
        $.ajax({
            url: _URL + '/ajs/admin/cliente/mercados',
            method: 'GET',
            success: function (response) {
                try { if (typeof response === "string") response = JSON.parse(response); } catch (e) { return; }
                var lista = [];
                $.each(response, function (idx, res) { if (res.mercado !== '' && res.mercado !== null) lista.push(res.mercado); });
                lista.sort(function (a, b) { return parseFloat(a) - parseFloat(b); });
                var options = '<option value="">Todos</option>';
                $.each(lista, function (idx, m) { options += '<option value="' + m + '">Mercado ' + m + '</option>'; });
                $("#f_mercado").html(options);
            }
        });
    }
    // Vuelve al rango por defecto: los 2 días hábiles anteriores (sin contar domingo)
    $("#f_reparto_hoy").on("click", function () {
        $("#f_desde").val("<?= isset($fechaDesde) ? $fechaDesde : '' ?>");
        $("#f_hasta").val("<?= isset($fechaHasta) ? $fechaHasta : '' ?>");
        tabla.ajax.reload();
    });
    $("#f_limpiar").on("click", function () {
        $("#f_desde").val("");
        $("#f_hasta").val("");
        $("#f_camion").val("");
        $("#f_dia").val("");
        $("#f_ruta").val("");
        $("#f_mercado").val("");
        tabla.ajax.reload();
    });

    // Conversión masiva de pedidos a venta
    $("#btn-venta-masiva").on("click", function () {
        $("#vm_resultado").html("");
        // Si están los filtros de reparto, se proponen sus fechas
        if ($("#f_desde").length) {
            $("#vm_desde").val($("#f_desde").val());
            $("#vm_hasta").val($("#f_hasta").val());
        }
        $("#modal-venta-masiva").modal("show");
    });

    // Al cambiar día o vehículo se vuelve a buscar (solo si ya se hizo una búsqueda)
    $("#vm_dia, #vm_vehiculo").on("change", function () {
        if ($("#vm_desde").val() && $("#vm_hasta").val() && $(".vm-chk").length) {
            $("#vm_buscar").trigger("click");
        }
    });

    function vmActualizarContador() {
        var n = $(".vm-chk:checked").length;
        $("#vm_contador").text(n + " seleccionado" + (n === 1 ? "" : "s"));
        $("#vm_convertir").prop("disabled", n === 0);
    }

    // Paso 1: listar los pedidos del rango
    $("#vm_buscar").on("click", function () {
        var desde = $("#vm_desde").val();
        var hasta = $("#vm_hasta").val();
        if (!desde || !hasta) {
            alertAdvertencia("Indique la fecha de inicio y la fecha fin");
            return;
        }
        $("#vm_resultado").html("");
        $("#vm_todos").prop("checked", false);
        $("#vm_lista").html('<tr><td colspan="10" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Buscando...</td></tr>');
        _ajax("/ajs/ventas/masivo/listar", "POST", { desde: desde, hasta: hasta, dia: $("#vm_dia").val(), vehiculo: $("#vm_vehiculo").val() }, function (resp) {
            if (!resp.res) {
                $("#vm_lista").html('<tr><td colspan="10" class="text-danger text-center">' + (resp.msj || 'Error') + '</td></tr>');
                vmActualizarContador();
                return;
            }
            if (!resp.pedidos.length) {
                $("#vm_lista").html('<tr><td colspan="10" class="text-muted text-center">No hay pedidos en ese rango</td></tr>');
                vmActualizarContador();
                return;
            }
            var html = '';
            resp.pedidos.forEach(function (p) {
                var vendido = p.venta ? true : false;
                var sinItems = parseInt(p.items) === 0;
                var bloquea = vendido || sinItems;
                var estado = vendido
                    ? '<span class="badge bg-secondary" title="Ya tiene venta vigente"><i class="fa fa-lock"></i> ' + p.venta + '</span>'
                    : (sinItems ? '<span class="badge bg-warning text-dark">Sin productos</span>'
                                : '<span class="badge bg-success">Por vender</span>');
                html += '<tr class="' + (bloquea ? 'text-muted' : '') + '">'
                    + '<td class="text-center">' + (bloquea ? '' : '<input type="checkbox" class="vm-chk" value="' + p.cotizacion_id + '">') + '</td>'
                    + '<td>#' + p.numero + '</td>'
                    + '<td>' + p.fecha + '</td>'
                    + '<td>' + p.cliente + '</td>'
                    + '<td>' + (p.dias_visitas || '-') + '</td>'
                    + '<td class="text-center">' + (p.ruta !== '' && p.ruta != null ? 'Ruta ' + p.ruta : '-') + '</td>'
                    + '<td class="text-center">' + p.items + '</td>'
                    + '<td class="text-end">' + parseFloat(p.total).toFixed(2) + '</td>'
                    + '<td>' + p.vendedor + '</td>'
                    + '<td>' + estado + '</td>'
                    + '</tr>';
            });
            $("#vm_lista").html(html);
            vmActualizarContador();
        });
    });

    $("#vm_todos").on("change", function () {
        $(".vm-chk").prop("checked", $(this).is(":checked"));
        vmActualizarContador();
    });
    $("#vm_lista").on("change", ".vm-chk", vmActualizarContador);

    // Paso 2: convertir solo los marcados
    $("#vm_convertir").on("click", function () {
        var ids = $(".vm-chk:checked").map(function () { return this.value; }).get();
        if (!ids.length) {
            alertAdvertencia("Marque al menos un pedido");
            return;
        }
        Swal.fire({
            title: '¿Convertir ' + ids.length + ' pedido(s) en venta?',
            text: 'Esta acción no se puede deshacer en bloque.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, convertir'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            var $btn = $("#vm_convertir").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Convirtiendo...');
            _ajax("/ajs/ventas/masivo", "POST", { ids: ids.join(',') }, function (resp) {
                $btn.html('<i class="fa fa-bolt"></i> Convertir seleccionados');
                if (!resp.res) {
                    alertError('ERR', resp.msj || 'No se pudo convertir');
                    vmActualizarContador();
                    return;
                }
                var html = '<div class="alert alert-success mb-2">' + resp.msj + '</div>';
                if (resp.omitidos && resp.omitidos.length) {
                    html += '<div class="alert alert-warning mb-2">Ya tenían venta (' + resp.omitidos.length + '): #' + resp.omitidos.join(', #') + '</div>';
                }
                if (resp.errores && resp.errores.length) {
                    html += '<div class="alert alert-danger mb-0">Con problemas:<br>' + resp.errores.join('<br>') + '</div>';
                }
                $("#vm_resultado").html(html);
                tabla.ajax.reload(null, false);
                $("#vm_buscar").trigger("click"); // refresca la lista con los estados actualizados
            });
        });
    });

    // Mostrar el modal para imprimir logistico
    $("#imprimir-logistico-btn").on('click', function() {
        $('#imprimir-logistico-bs').modal('show');
    });

    // Cerrar el modal logistico
    $("#btnCerrarModalLogistico, #btnCerrarModalLogisticoX").on('click', function() {
        $('#imprimir-logistico-bs').modal('hide');
    });

    // Mostrar el modal para imprimir
    $("#imprimir").on('click', function() {
        $('#imprimir-pdfs-bs').modal('show');
    });

    // Cerrar el modal
    $("#btnCerrarModal, #btnCerrarModalX").on('click', function() {
        $('#imprimir-pdfs-bs').modal('hide');
    });

        // Manejo de selección de impresión
        $("#btnImprimirPorNumeros").on('click', function () {
            $("#imprimirPorNumeros").show();
            $("#imprimirPorFechas").hide();
            $("#imprimirPorVisitas").hide();
            $("#imprimirPorCamion").hide();
            $("#btnConsolidado").hide();
            $("#btnPorCliente").hide();
            $("#imprimirPorRuta").hide();
            $("#imprimirPorMercado").hide();
            $("#imprimirPorMedida").hide();

            // Limpiar campos de fecha
            $("#fechaInicio").val('');
            $("#fechaFin").val('');
            $("#diasVisita").val('');
            $("#camion").val('');
            $("#ruta").val('');
            $("#mercado").val('');
            $('#camionconsolodidado').val('');
            // $('#filtro-horario').val('');


        });
        $("#btnImprimirPorFechas").on('click', function () {
            $("#imprimirPorFechas").show();
            $("#imprimirPorNumeros").hide();
            $("#imprimirPorVisitas").hide();
            $("#imprimirPorCamion").hide();
            $("#imprimirPorRuta").hide();
            $("#imprimirPorMercado").hide();
            $("#imprimirPorMedida").hide();
            // Limpiar campos de número
            $("#numeroInicio").val('');
            $("#numeroFin").val('');
            // $("#diasVisita").val('');
            $('#camionconsolodidado').val('');
            // $('#filtro-horario').val('');


        });
        $("#btnImprimirVisitas").on('click', function () {
            $("#imprimirPorVisitas").show();
            $("#imprimirPorFechas").hide();
            $("#imprimirPorNumeros").hide();
            $("#imprimirPorCamion").hide();
            $("#imprimirPorRuta").hide();
            $("#imprimirPorMercado").hide();
            $("#imprimirPorMedida").hide();
            // Limpiar campos de número
            $("#numeroInicio").val('');
            $("#numeroFin").val('');
            $('#camionconsolodidado').val('');
            // $('#filtro-horario').val('');


        });

        $("#btnImprimirPorCamion").on('click', function () {
            $("#imprimirPorCamion").show();
            $("#imprimirPorFechas").hide();
            $("#imprimirPorNumeros").hide();
            $("#imprimirPorVisitas").hide();
            $("#imprimirPorMercado").hide();
            $("#imprimirPorRuta").hide();
            $("#imprimirPorMedida").hide();
            // Limpiar campos de número
            $("#numeroInicio").val('');
            $("#numeroFin").val('');
            $('#camionconsolodidado').val('porCamionConsolidado');

        });
        $("#btnImprimirPorRuta").on('click', function () {
            $("#imprimirPorRuta").show();
            $("#imprimirPorFechas").hide();
            $("#imprimirPorNumeros").hide();
            $("#imprimirPorVisitas").hide();
            $("#imprimirPorCamion").hide();
            $("#imprimirPorMercado").hide();
            $("#imprimirPorMedida").hide();
            // Limpiar campos de número
            $("#numeroInicio").val('');
            $("#numeroFin").val('');
            $('#camionconsolodidado').val('');
            // $('#filtro-horario').val('');

        });
        $("#btnImprimirPorMercado").on('click', function () {
            $("#imprimirPorMercado").show();
            $("#imprimirPorFechas").hide();
            $("#imprimirPorNumeros").hide();
            $("#imprimirPorVisitas").hide();
            $("#imprimirPorCamion").hide();
            $("#imprimirPorRuta").hide();
            $("#imprimirPorMedida").hide();
            // Limpiar campos de número
            $("#numeroInicio").val('');
            $("#numeroFin").val('');
            $('#camionconsolodidado').val('');
            // $('#filtro-horario').val('');

        });
        $("#btnImprimirPorMedida").on('click', function () {
            $("#imprimirPorMercado").hide();
            //AGREGANDO
            $("#imprimirPorMedida").show();
            $("#imprimirPorFechas").hide();
            $("#imprimirPorNumeros").hide();
            $("#imprimirPorVisitas").hide();
            $("#imprimirPorCamion").hide();
            $("#imprimirPorRuta").hide();
            // Limpiar campos de número
            $("#numeroInicio").val('');
            $("#numeroFin").val('');
            $('#camionconsolodidado').val('');
        });

        $("#btnImprimir").on('click', async function () {
            var numeroInicio = parseInt($("#numeroInicio").val());
            var numeroFin = parseInt($("#numeroFin").val());
            var fechaSeleccionada = $("#fechaSeleccionada").val(); // Cambiado para obtener solo una fecha
            var fechaFinSeleccionada = $("#fechaFinSeleccionada").val(); // Cambiado para obtener solo una fecha
            var diasVisita = $("#diasVisita").val(); // Obtener el valor del select
            var camion = $("#camion").val(); // Obtener el valor del select
            var ruta = $("#ruta").val(); // Obtener el valor del select
            var mercado = $("#mercado").val(); // Obtener el valor del select
            const batchSize = 5;

            // Validaciones
            if (!isNaN(numeroInicio) && !isNaN(numeroFin)) {
                await imprimirPorNumeros(numeroInicio, numeroFin, batchSize);
            } else if (camion && fechaSeleccionada && fechaFinSeleccionada) {
                await imprimirPorCamion(camion, fechaSeleccionada, fechaFinSeleccionada, diasVisita, ruta, mercado);
            } else if (fechaSeleccionada) { // Verifica si se ha seleccionado una fecha
                await imprimirPorFecha(fechaSeleccionada, fechaFinSeleccionada); //
                document.getElementById("fechaFinSeleccionada").value = "";
                document.getElementById("fechaSeleccionada").value = "";
            } else if (diasVisita) { // Verifica que se haya seleccionado un día
                const diasMap = {
                    "lunes": "lunes",
                    "martes": "martes",
                    "miercoles": "miercoles",
                    "jueves": "jueves",
                    "viernes": "viernes",
                    "sabado": "sabado",
                    "domingo": "domingo"
                };
                const diasVisitaNum = diasMap[diasVisita.toLowerCase()];

                await imprimirPorVisitas(diasVisitaNum, batchSize);
            } else {
                alert("Ingrese un rango de números, una fecha válida o días de visita.");
            }
        });

        // Lógica para Reporte Logístico
        $("#btnImprimirLogisticoFinal").on('click', async function () {
            let fechaInicio = $("#fechaSeleccionadaLog").val();
            let fechaFin = $("#fechaFinSeleccionadaLog").val();
            let camion = $("#camionLog").val();
            let medida = $("#medidaLog").val();
            let diasVisita = $("#diasVisitaLog").val();
            let horario = $("#horarioLog").val();

            if (!fechaInicio || !fechaFin || (camion === "" && diasVisita === "")) {
                alert("Por favor complete los campos obligatorios (Fecha Inicio, Fecha Fin y Camión o Día de Visita).");
                return;
            }

            const { PDFDocument } = PDFLib;
            const combinedPdf = await PDFDocument.create();
            let spinner = $("<div class='spinner-border' role='status'><span class='visually-hidden'>Cargando...</span></div>");
            $("body").append(spinner);

            try {
                let params = new URLSearchParams({ fechaInicio, fechaFin, camion, medida, diasVisita, horario }).toString();
                const response = await fetch(`/r/pedido/reporte/logistico?${params}`);
                if (!response.ok) throw new Error("Error al generar reporte.");
                
                const pdfBuffer = await response.arrayBuffer();
                const pdf = await PDFDocument.load(pdfBuffer);
                const copiedPages = await combinedPdf.copyPages(pdf, pdf.getPageIndices());
                copiedPages.forEach(page => combinedPdf.addPage(page));

                const combinedPdfBytes = await combinedPdf.save();
                var blob = new Blob([combinedPdfBytes], { type: "application/pdf" });
                var url = URL.createObjectURL(blob);
                var iframe = document.getElementById('printFrame');
                iframe.src = url;
                iframe.onload = function () {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                };
            } catch (e) {
                alert(e.message);
            } finally {
                spinner.remove();
            }
        });

        async function imprimirPorVisitas(diasVisitaNum) {
            const { PDFDocument } = PDFLib;
            const combinedPdf = await PDFDocument.create();

            let spinner = $("<div class='spinner-border' role='status'><span class='visually-hidden'>Cargando...</span></div>");
            $("body").append(spinner);

            try {
                // Lógica para obtener todos los registros del día de visita
                const response = await fetch(`/r/pedido/reporte/dias/${diasVisitaNum}`);
                const contentType = response.headers.get("content-type");

                // Verifica si la respuesta es válida
                if (!response.ok) {
                    throw new Error(`Error al obtener registros: ${response.status} ${response.statusText}`);
                }

                if (!contentType || !contentType.includes("application/pdf")) {
                    const responseText = await response.text(); // Captura el texto de la respuesta
                    console.error(`Error: ${responseText}`);
                    throw new Error("El contenido devuelto no es un PDF.");
                }

                // Obtén el buffer del PDF
                const pdfBuffer = await response.arrayBuffer();
                const pdf = await PDFDocument.load(pdfBuffer);
                const copiedPages = await combinedPdf.copyPages(pdf, pdf.getPageIndices());

                copiedPages.forEach(page => combinedPdf.addPage(page));

                const combinedPdfBytes = await combinedPdf.save();
                var blob = new Blob([combinedPdfBytes], { type: "application/pdf" });
                var url = URL.createObjectURL(blob);

                var iframe = document.getElementById('printFrame');
                iframe.src = url;
                iframe.onload = function () {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                };
            } catch (error) {
                alert(`Error al procesar los PDFs: ${error.message}`);
                console.error(`Error detallado: ${error}`);
            } finally {
                spinner.remove();
            }
        }

        async function imprimirPorNumeros(numeroInicio, numeroFin, batchSize) {
            const { PDFDocument } = PDFLib;
            const combinedPdf = await PDFDocument.create();

            let spinner = $("<div class='spinner-border' role='status'><span class='visually-hidden'>Cargando...</span></div>");
            $("body").append(spinner);

            try {
                for (let i = numeroInicio; i <= numeroFin; i += batchSize) {
                    let promises = [];
                    for (let j = i; j < i + batchSize && j <= numeroFin; j++) {
                        promises.push(fetch(`/r/pedido/reporte/${j}`).then(response => {
                            if (!response.ok) {
                                throw new Error(`Error al obtener PDF para el número ${j}`);
                            }
                            return response.arrayBuffer();
                        }));
                    }

                    let results = await Promise.all(promises);
                    for (let buffer of results) {
                        let pdf = await PDFDocument.load(buffer);
                        let pages = await combinedPdf.copyPages(pdf, pdf.getPageIndices());
                        pages.forEach(page => combinedPdf.addPage(page));
                    }
                }

                const combinedPdfBytes = await combinedPdf.save();
                var blob = new Blob([combinedPdfBytes], { type: "application/pdf" });
                var url = URL.createObjectURL(blob);

                var iframe = document.getElementById('printFrame');
                iframe.src = url;
                iframe.onload = function () {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                };
            } catch (error) {
                alert(`Error al procesar los PDFs: ${error.message}`);
                console.error(`Error detallado: ${error}`);
            } finally {
                spinner.remove();
            }
        }

        async function imprimirPorFecha(fechaSeleccionada, fechaFinSeleccionada) {
            const { PDFDocument } = PDFLib;
            const combinedPdf = await PDFDocument.create();

            let spinner = $("<div class='spinner-border' role='status'><span class='visually-hidden'>Cargando...</span></div>");
            $("body").append(spinner);

            try {
                const response = await fetch(`/r/pedido/reporte/fecha/${fechaSeleccionada}/${fechaFinSeleccionada}`);
                const contentType = response.headers.get("content-type");

                // Verifica si la respuesta es válida
                if (!response.ok) {
                    throw new Error(`Error al obtener registros: ${response.status} ${response.statusText}`);
                }

                if (!contentType || !contentType.includes("application/pdf")) {
                    const responseText = await response.text();
                    console.error(`Error: ${responseText}`);
                    throw new Error("El contenido devuelto no es un PDF.");
                }

                const pdfBuffer = await response.arrayBuffer();
                const pdf = await PDFDocument.load(pdfBuffer);
                const copiedPages = await combinedPdf.copyPages(pdf, pdf.getPageIndices());

                copiedPages.forEach(page => combinedPdf.addPage(page));

                const combinedPdfBytes = await combinedPdf.save();
                var blob = new Blob([combinedPdfBytes], { type: "application/pdf" });
                var url = URL.createObjectURL(blob);

                var iframe = document.getElementById('printFrame');
                iframe.src = url;
                iframe.onload = function () {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                };
            } catch (error) {
                alert(`Error al procesar los PDFs: ${error.message}`);
                console.error(`Error detallado: ${error}`);
            } finally {
                spinner.remove();
            }
        }


        async function imprimirPorCamion(camion, fechaSeleccionada, fechaFinSeleccionada, diasVisita, ruta, mercado) {
            const { PDFDocument } = PDFLib;
            const combinedPdf = await PDFDocument.create();

            let spinner = $("<div class='spinner-border' role='status'><span class='visually-hidden'>Cargando...</span></div>");
            $("body").append(spinner);

            try {
                // Lógica para obtener todos los registros del día de visita
                let data = new FormData();
                data.append('camion', camion);
                data.append('fechaSeleccionada', fechaSeleccionada);
                data.append('fechaFinSeleccionada', fechaFinSeleccionada);
                data.append('diasVisita', diasVisita);
                data.append('ruta', ruta);
                data.append('mercado', mercado);
                let params = new URLSearchParams(data);
                params = params.toString();
                const response = await fetch(`/r/pedido/reporte/camion?${params}`);
                const contentType = response.headers.get("content-type");

                // Verifica si la respuesta es válida
                if (!response.ok) {
                    throw new Error(`Error al obtener registros: ${response.status} ${response.statusText}`);
                }

                if (!contentType || !contentType.includes("application/pdf")) {
                    const responseText = await response.text(); // Captura el texto de la respuesta
                    console.error(`Error: ${responseText}`);
                    throw new Error("El contenido devuelto no es un PDF.");
                }

                // Obtén el buffer del PDF
                const pdfBuffer = await response.arrayBuffer();
                const pdf = await PDFDocument.load(pdfBuffer);
                const copiedPages = await combinedPdf.copyPages(pdf, pdf.getPageIndices());

                copiedPages.forEach(page => combinedPdf.addPage(page));

                const combinedPdfBytes = await combinedPdf.save();
                var blob = new Blob([combinedPdfBytes], { type: "application/pdf" });
                var url = URL.createObjectURL(blob);

                var iframe = document.getElementById('printFrame');
                iframe.src = url;
                iframe.onload = function () {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                };
            } catch (error) {
                alert(`Error al procesar los PDFs: ${error.message}`);
                console.error(`Error detallado: ${error}`);
            } finally {
                spinner.remove();
            }
        }
        listarMedidas();
        listarRutas();
        listarMercados();
        function listarRutas() {
            $.ajax({
                url: '/ajs/admin/cliente/rutas',
                method: 'GET',
                success: function (response) {
                    response = JSON.parse(response);
                    console.log(response);
                    let options = `<option value=""></option>`;
                    $.each(response, function (idx, res) {
                        options += `<option value="${res.id_ruta}">${res.id_ruta}</option>`;
                    });
                    $("#ruta").html(options);
                }
            });
        }
        function listarMedidas() {
            $.ajax({
                url: '/ajs/admin/cliente/medidas',
                method: 'GET',
                success: function (response) {
                    response = JSON.parse(response);
                    console.log(response);
                    let options = `<option value=""></option>`;
                    $.each(response, function (idx, res) {
                        options += `<option value="${res.medida}">${res.medida}</option>`;
                    });
                    $("#medida").html(options);
                    $("#medidaLog").html(options);
                }
            });
        }
        function listarMercados() {
            $.ajax({
                url: '/ajs/admin/cliente/mercados',
                method: 'GET',
                success: function (response) {
                    response = JSON.parse(response);
                    console.log(response);
                    let options = `<option value=""></option>`;
                    $.each(response, function (idx, res) {
                        options += `<option value="${res.mercado}">${res.mercado}</option>`;
                    });
                    $("#mercado").html(options);
                }
            });
        }

        $(document).on('change', '#camion', function (e) {
            if (e.target.value != "") {
                $("#btnConsolidado").show();
                $("#btnPorCliente").show();
            } else {
                $("#btnConsolidado").hide();
                $("#btnPorCliente").hide();
            }
        });

        $(document).on('click', '#btnConsolidado', async function () {
            let fechaSeleccionada = $("#fechaSeleccionada").val();
            let fechaFinSeleccionada = $("#fechaFinSeleccionada").val();
            let diasVisita = $("#diasVisita").val();
            let camion = $("#camion").val();
            let ruta = $("#ruta").val();
            let mercado = $("#mercado").val();
            let medida = $("#medida").val();
            let tipo = $('#camionconsolodidado').val();
            // El corte de horario se puede elegir tanto en la pestaña Por Mercado como en Por Camión:
            // se toma el de la pestaña visible.
            let horario = $("#imprimirPorCamion").is(":visible")
                ? $("#filtro-horario-camion").val()
                : $("#filtro-horario").val();
            const { PDFDocument } = PDFLib;
            const combinedPdf = await PDFDocument.create();

            let spinner = $("<div class='spinner-border' role='status'><span class='visually-hidden'>Cargando...</span></div>");
            $("body").append(spinner);

            try {
                // Lógica para obtener todos los registros del día de visita
                let data = new FormData();
                data.append('camion', camion);
                data.append('fechaSeleccionada', fechaSeleccionada);
                data.append('fechaFinSeleccionada', fechaFinSeleccionada);
                data.append('diasVisita', diasVisita);
                data.append('ruta', ruta);
                data.append('mercado', mercado);
                data.append('medida', medida);
                data.append('tipo', tipo);
                data.append('horario', horario);
                let params = new URLSearchParams(data);
                params = params.toString();
                let url_r = (camion == 0) ? `r/pedido/reporte/camion/consolidado-total?${params}` : `r/pedido/reporte/camion/consolidado?${params}`;
                const response = await fetch(url_r);
                const contentType = response.headers.get("content-type");

                // Verifica si la respuesta es válida
                if (!response.ok) {
                    throw new Error(`Error al obtener registros: ${response.status} ${response.statusText}`);
                }

                if (!contentType || !contentType.includes("application/pdf")) {
                    const responseText = await response.text(); // Captura el texto de la respuesta
                    console.error(`Error: ${responseText}`);
                    throw new Error("El contenido devuelto no es un PDF.");
                }

                // Obtén el buffer del PDF
                const pdfBuffer = await response.arrayBuffer();
                const pdf = await PDFDocument.load(pdfBuffer);
                const copiedPages = await combinedPdf.copyPages(pdf, pdf.getPageIndices());

                copiedPages.forEach(page => combinedPdf.addPage(page));

                const combinedPdfBytes = await combinedPdf.save();
                var blob = new Blob([combinedPdfBytes], { type: "application/pdf" });
                var url = URL.createObjectURL(blob);

                var iframe = document.getElementById('printFrame');
                iframe.src = url;
                iframe.onload = function () {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                };
            } catch (error) {
                alert(`Error al procesar los PDFs: ${error.message}`);
                console.error(`Error detallado: ${error}`);
            } finally {
                spinner.remove();
            }
        });


        $(document).on('click', '#btnPorCliente', async function () {
            let fechaSeleccionada = $("#fechaSeleccionada").val();
            let fechaFinSeleccionada = $("#fechaFinSeleccionada").val();
            let diasVisita = $("#diasVisita").val();
            let camion = $("#camion").val();
            let ruta = $("#ruta").val();
            let mercado = $("#mercado").val();
            let medida = $("#medida").val();
            const { PDFDocument } = PDFLib;
            const combinedPdf = await PDFDocument.create();

            let spinner = $("<div class='spinner-border' role='status'><span class='visually-hidden'>Cargando...</span></div>");
            $("body").append(spinner);

            try {
                // Lógica para obtener todos los registros del día de visita
                let data = new FormData();
                data.append('camion', camion);
                data.append('fechaSeleccionada', fechaSeleccionada);
                data.append('fechaFinSeleccionada', fechaFinSeleccionada);
                data.append('diasVisita', diasVisita);
                data.append('ruta', ruta);
                data.append('mercado', mercado);
                data.append('medida', medida);
                let params = new URLSearchParams(data);
                params = params.toString();
                const response = await fetch(`/r/pedido/reporte/clientes?${params}`);
                const contentType = response.headers.get("content-type");

                // Verifica si la respuesta es válida
                if (!response.ok) {
                    throw new Error(`Error al obtener registros: ${response.status} ${response.statusText}`);
                }

                if (!contentType || !contentType.includes("application/pdf")) {
                    const responseText = await response.text(); // Captura el texto de la respuesta
                    console.error(`Error: ${responseText}`);
                    throw new Error("El contenido devuelto no es un PDF.");
                }

                // Obtén el buffer del PDF
                const pdfBuffer = await response.arrayBuffer();
                const pdf = await PDFDocument.load(pdfBuffer);
                const copiedPages = await combinedPdf.copyPages(pdf, pdf.getPageIndices());

                copiedPages.forEach(page => combinedPdf.addPage(page));

                const combinedPdfBytes = await combinedPdf.save();
                var blob = new Blob([combinedPdfBytes], { type: "application/pdf" });
                var url = URL.createObjectURL(blob);

                var iframe = document.getElementById('printFrame');
                iframe.src = url;
                iframe.onload = function () {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                };
            } catch (error) {
                alert(`Error al procesar los PDFs: ${error.message}`);
                console.error(`Error detallado: ${error}`);
            } finally {
                spinner.remove();
            }
        });

        eliminarCotizacion = function (cod) {
            console.log(cod);
            Swal.fire({
                title: '¿Desea eliminar el pedido? ',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Si'
            }).then((result) => {
                if (result.isConfirmed) {
                    _ajax("/ajs/cotizaciones/del", "POST", { cod }, function (resp) {
                        tabla.ajax.reload();
                    });
                }
            })
        }

    });
</script>