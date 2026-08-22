<div class="page-title-box">
    <div class="clearfix">
        <h6 class="page-title float-end">Cuadre de Inventario</h6>
        <ol class="breadcrumb m-0 float-start">
            <li class="breadcrumb-item"><a href="javascript: void(0);">Almacén</a></li>
            <li class="breadcrumb-item active" aria-current="page">Cuadre de Inventario</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card" style="border-radius:20px;box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06)">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="card-title mb-0">Últimos cuadres de inventario</h4>
                        <p class="text-muted fs-7 mb-0">Solo ajustes manuales (carga inicial, cambios, devueltos,
                            pérdidas, préstamos). Las ventas y compras se ven en el <a href="<?= DOMINIO ?>almacen/kardex" class="button-link">Kardex</a>.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 1): ?>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMovimiento">
                            <i class="fa fa-plus"></i> Registrar cuadre
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaMovs" class="table table-bordered text-center table-sm"
                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Mov.</th>
                                <th>Motivo</th>
                                <th>Cantidad</th>
                                <th>Stock Anterior</th>
                                <th>Stock Actual</th>
                                <th>Estado</th>
                                <th>Usuario</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Registrar movimiento + CRUD de motivos -->
<div class="modal fade" id="modalMovimiento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Registrar cuadre de inventario</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <ul class="nav nav-tabs" id="tabsMovimiento" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-registrar" data-bs-toggle="tab"
                            data-bs-target="#pane-registrar" type="button" role="tab">
                            <i class="fa fa-exchange-alt"></i> Registrar cuadre
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-motivos" data-bs-toggle="tab"
                            data-bs-target="#pane-motivos" type="button" role="tab">
                            <i class="fa fa-tags"></i> Motivos
                        </button>
                    </li>
                </ul>

                <div class="tab-content pt-3">

                    <!-- TAB 1: registrar cuadre -->
                    <div class="tab-pane fade show active" id="pane-registrar" role="tabpanel">
                        <p class="text-muted fs-7">Aquí se registran ajustes de cuadre: carga inicial,
                            cambios de productos, devueltos, pérdidas, préstamos. Las ventas y compras
                            las registra el sistema automáticamente en el Kardex.</p>

                        <div class="mb-2 position-relative">
                            <label class="form-label form-label-sm">Producto</label>
                            <input type="text" id="buscarProducto" class="form-control form-control-sm"
                                placeholder="Escriba código o descripción (mín. 2 letras)" autocomplete="off">
                            <input type="hidden" id="idProductoSel" value="">
                            <div id="resultadosProducto" class="list-group position-absolute w-100" style="z-index:2000; max-height:260px; overflow:auto;"></div>
                            <div class="mt-1">
                                <span id="stockActual" class="badge bg-dark" style="display:none;"></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label form-label-sm">Tipo de movimiento</label>
                                <select id="tipoMovimiento" class="form-select form-select-sm">
                                    <option value="i">INGRESO (suma stock)</option>
                                    <option value="e">SALIDA (resta stock)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label form-label-sm">Motivo</label>
                                <select id="motivoMovimiento" class="form-select form-select-sm"></select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label form-label-sm">Cantidad (en unidades)</label>
                            <input type="number" id="cantidadMovimiento" class="form-control form-control-sm" min="0.01" step="0.01" value="">
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-sm">Observación</label>
                            <input type="text" id="obsMovimiento" class="form-control form-control-sm" maxlength="250"
                                placeholder="Opcional: detalle del ajuste">
                        </div>

                        <button type="button" class="btn btn-primary btn-sm w-100" id="btnRegistrar">
                            <i class="fa fa-save"></i> Registrar cuadre
                        </button>
                    </div>

                    <!-- TAB 2: CRUD de motivos -->
                    <div class="tab-pane fade" id="pane-motivos" role="tabpanel">
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-5">
                                <label class="form-label form-label-sm">Nuevo motivo</label>
                                <input type="text" id="nuevoMotivoNombre" class="form-control form-control-sm"
                                    maxlength="100" placeholder="Nombre del motivo">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label form-label-sm">Tipo</label>
                                <select id="nuevoMotivoTipo" class="form-select form-select-sm">
                                    <option value="i">INGRESO</option>
                                    <option value="e">SALIDA</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="button" class="btn btn-success btn-sm" id="btnCrearMotivo">
                                    <i class="fa fa-plus"></i> Agregar
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered text-center table-sm" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>Motivo</th>
                                        <th>Tipo</th>
                                        <th>Origen</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyMotivos"></tbody>
                            </table>
                        </div>
                        <p class="text-muted fs-7 mb-0">Los motivos de <strong>sistema</strong> (Venta, Recepción de compra,
                            Devolución, etc.) los genera el sistema automáticamente y no se pueden editar ni eliminar.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // 'var' (no let/const) a nivel de <script>: el fragmento se reinyecta al navegar por AJAX y un 'let' global no puede redeclararse.
    var motivosAlmacen = [];
    var esAdminAlmacen = <?= (isset($_SESSION['rol']) && $_SESSION['rol'] == 1) ? 1 : 0 ?>;

    function llenarMotivos() {
        const tipo = $("#tipoMovimiento").val();
        let html = '<option value="">Elija un motivo</option>';
        motivosAlmacen.filter(m => m.tipo === tipo).forEach(function (m) {
            html += `<option value="${m.motivo_id}">${m.nombre}</option>`;
        });
        $("#motivoMovimiento").html(html);
    }

    function cargarMotivosManuales() {
        $.ajax({
            url: _URL + "/ajs/almacen/motivos",
            method: "POST",
            success: function (resp) {
                motivosAlmacen = JSON.parse(resp);
                llenarMotivos();
            }
        });
    }

    function cargarMotivosCrud() {
        $.ajax({
            url: _URL + "/ajs/almacen/motivos/todos",
            method: "POST",
            success: function (resp) {
                const lista = JSON.parse(resp);
                let html = '';
                lista.forEach(function (m) {
                    const tipoBadge = m.tipo === 'i'
                        ? '<span class="badge bg-success">INGRESO</span>'
                        : '<span class="badge bg-danger">SALIDA</span>';
                    const origen = m.fijo === '1'
                        ? '<span class="badge bg-secondary"><i class="fa fa-lock"></i> Sistema</span>'
                        : '<span class="badge bg-info">Manual</span>';
                    const acciones = m.fijo === '1'
                        ? '-'
                        : `<div class="btn-group">
                            <button class="btn btn-warning btn-sm btnEditarMotivo" data-id="${m.motivo_id}" data-nombre="${m.nombre}" title="Editar"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-danger btn-sm btnEliminarMotivo" data-id="${m.motivo_id}" data-nombre="${m.nombre}" title="Eliminar"><i class="fa fa-trash"></i></button>
                           </div>`;
                    html += `<tr>
                        <td class="text-start">${m.nombre}</td>
                        <td>${tipoBadge}</td>
                        <td>${origen}</td>
                        <td>${acciones}</td>
                    </tr>`;
                });
                $("#tbodyMotivos").html(html);
            }
        });
    }

    function cargarMovimientos() {
        $.ajax({
            url: _URL + "/ajs/almacen/cuadres",
            method: "POST",
            success: function (resp) {
                const data = JSON.parse(resp);
                $("#tablaMovs").DataTable({
                    paging: true,
                    searching: true,
                    ordering: false,
                    destroy: true,
                    deferRender: true,
                    data: data,
                    language: { url: "ServerSide/Spanish.json" },
                    columns: [
                        { data: "fecha", class: "text-center" },
                        {
                            data: null,
                            class: "text-start",
                            render: function (d, t, row) {
                                return `${row.codigo ?? ''} — ${row.descripcion ?? ''}`;
                            }
                        },
                        {
                            data: "tipo",
                            class: "text-center",
                            render: function (data) {
                                return data === 'i'
                                    ? '<span class="badge bg-success">ING</span>'
                                    : '<span class="badge bg-danger">SAL</span>';
                            }
                        },
                        { data: "motivo", class: "text-center" },
                        {
                            data: null,
                            class: "text-center",
                            render: function (d, t, row) {
                                let signo = row.tipo === 'i' ? '+' : '-';
                                let color = row.tipo === 'i' ? 'text-success' : 'text-danger';
                                return `<span class="fw-bold ${color}">${signo}${parseFloat(row.cantidad).toFixed(2)}</span>`;
                            }
                        },
                        {
                            data: "saldo_anterior",
                            class: "text-center",
                            render: function (data) {
                                return data === null ? '-' : parseFloat(data).toFixed(2);
                            }
                        },
                        {
                            data: "saldo_resultante",
                            class: "text-center",
                            render: function (data) {
                                return data === null ? '-' : `<span class="fw-bold">${parseFloat(data).toFixed(2)}</span>`;
                            }
                        },
                        {
                            data: "estado",
                            class: "text-center",
                            render: function (data) {
                                return data === '0'
                                    ? '<span class="badge bg-danger">ANULADO</span>'
                                    : '<span class="badge bg-primary">VIGENTE</span>';
                            }
                        },
                        { data: "usuario", class: "text-center" },
                        {
                            data: null,
                            class: "text-center",
                            render: function (d, t, row) {
                                // Solo admin, solo cuadres manuales vigentes (los de sistema y los anulados no)
                                if (esAdminAlmacen != 1 || row.estado === '0' || row.fijo === '1') {
                                    return '-';
                                }
                                return `<div class="btn-group">
                                    <button class="btn btn-warning btn-sm btnEditarCuadre" data-id="${row.kardex_id}" data-cantidad="${row.cantidad}" title="Editar cantidad"><i class="fa fa-edit"></i></button>
                                    <button class="btn btn-danger btn-sm btnAnularCuadre" data-id="${row.kardex_id}" title="Anular (revierte stock)"><i class="fa fa-ban"></i></button>
                                </div>`;
                            }
                        }
                    ]
                });
            }
        });
    }

    $(document).ready(function () {
        cargarMotivosManuales();
        cargarMovimientos();
        cargarMotivosCrud();
        $("#tipoMovimiento").on("change", llenarMotivos);

        // ---------- Buscador de producto ----------
        let timerBusqueda = null;
        $("#buscarProducto").on("input", function () {
            const q = $(this).val().trim();
            $("#idProductoSel").val('');
            $("#stockActual").hide();
            clearTimeout(timerBusqueda);
            if (q.length < 2) {
                $("#resultadosProducto").empty();
                return;
            }
            timerBusqueda = setTimeout(function () {
                $.ajax({
                    url: _URL + "/ajs/almacen/producto/buscar",
                    method: "POST",
                    data: { q: q },
                    success: function (resp) {
                        const lista = JSON.parse(resp);
                        let html = '';
                        lista.forEach(function (p) {
                            html += `<button type="button" class="list-group-item list-group-item-action item-producto"
                                data-id="${p.id_producto}" data-codigo="${p.codigo}" data-stock="${p.cantidad}"
                                data-descripcion="${p.descripcion}">
                                <strong>${p.codigo}</strong> — ${p.descripcion} <span class="float-end text-muted">stock: ${p.cantidad}</span>
                            </button>`;
                        });
                        $("#resultadosProducto").html(html);
                    }
                });
            }, 300);
        });

        $("#resultadosProducto").on("click", ".item-producto", function () {
            $("#idProductoSel").val($(this).data("id"));
            $("#buscarProducto").val($(this).data("codigo") + " — " + $(this).data("descripcion"));
            $("#stockActual").text("Stock actual: " + $(this).data("stock")).show();
            $("#resultadosProducto").empty();
        });

        // ---------- Registrar movimiento ----------
        $("#btnRegistrar").on("click", function () {
            const idProducto = $("#idProductoSel").val();
            const tipo = $("#tipoMovimiento").val();
            const motivoId = $("#motivoMovimiento").val();
            const cantidad = parseFloat($("#cantidadMovimiento").val());
            const obs = $("#obsMovimiento").val();

            if (!idProducto) {
                Swal.fire({ title: 'Elija un producto', icon: 'error' });
                return;
            }
            if (!motivoId) {
                Swal.fire({ title: 'Elija un motivo', icon: 'error' });
                return;
            }
            if (isNaN(cantidad) || cantidad <= 0) {
                Swal.fire({ title: 'Ingrese una cantidad válida', icon: 'error' });
                return;
            }

            const nombreTipo = tipo === 'i' ? 'INGRESO' : 'SALIDA';
            Swal.fire({
                title: `¿Registrar ${nombreTipo} de ${cantidad} unidades?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, registrar'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $("#loader-menor").show();
                $.ajax({
                    url: _URL + "/ajs/almacen/movimiento/registrar",
                    method: "POST",
                    data: {
                        id_producto: idProducto,
                        tipo: tipo,
                        motivo_id: motivoId,
                        cantidad: cantidad,
                        observacion: obs
                    },
                    success: function (resp) {
                        $("#loader-menor").hide();
                        const data = JSON.parse(resp);
                        if (data.res) {
                            Swal.fire({ title: 'Movimiento registrado', icon: 'success', timer: 1500, showConfirmButton: false });
                            $("#cantidadMovimiento").val('');
                            $("#obsMovimiento").val('');
                            $("#idProductoSel").val('');
                            $("#buscarProducto").val('');
                            $("#stockActual").hide();
                            $("#modalMovimiento").modal('hide');
                            cargarMovimientos();
                        } else {
                            Swal.fire({ title: 'Error', text: data.msg || 'No se pudo registrar', icon: 'error' });
                        }
                    },
                    error: function () {
                        $("#loader-menor").hide();
                        Swal.fire({ title: 'Error en el servidor', icon: 'error' });
                    }
                });
            });
        });

        // ---------- Acciones sobre cuadres: editar / anular ----------
        $("#tablaMovs").on("click", ".btnAnularCuadre", function () {
            const id = $(this).data("id");
            Swal.fire({
                title: `¿Anular el cuadre #${id}?`,
                text: 'El stock se revertirá y el registro quedará marcado como ANULADO (no se borra).',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, anular'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $("#loader-menor").show();
                $.ajax({
                    url: _URL + "/ajs/almacen/cuadre/anular",
                    method: "POST",
                    data: { kardex_id: id },
                    success: function (resp) {
                        $("#loader-menor").hide();
                        const data = JSON.parse(resp);
                        if (data.res) {
                            Swal.fire({ title: data.msg, icon: 'success', timer: 1500, showConfirmButton: false });
                            cargarMovimientos();
                        } else {
                            Swal.fire({ title: 'Error', text: data.msg, icon: 'error' });
                        }
                    },
                    error: function () {
                        $("#loader-menor").hide();
                        Swal.fire({ title: 'Error en el servidor', icon: 'error' });
                    }
                });
            });
        });

        $("#tablaMovs").on("click", ".btnEditarCuadre", function () {
            const id = $(this).data("id");
            const cantidadActual = $(this).data("cantidad");
            Swal.fire({
                title: `Editar cantidad del cuadre #${id}`,
                text: 'Se anulará el cuadre original (revierte stock) y se registrará uno nuevo con la cantidad correcta.',
                input: 'number',
                inputValue: cantidadActual,
                inputAttributes: { min: '0.01', step: '0.01' },
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value || parseFloat(value) <= 0) return 'Ingrese una cantidad válida';
                }
            }).then((result) => {
                if (!result.isConfirmed) return;
                $("#loader-menor").show();
                $.ajax({
                    url: _URL + "/ajs/almacen/cuadre/editar",
                    method: "POST",
                    data: { kardex_id: id, cantidad: result.value },
                    success: function (resp) {
                        $("#loader-menor").hide();
                        const data = JSON.parse(resp);
                        if (data.res) {
                            Swal.fire({ title: 'Cuadre corregido', icon: 'success', timer: 1500, showConfirmButton: false });
                            cargarMovimientos();
                        } else {
                            Swal.fire({ title: 'Error', text: data.msg, icon: 'error' });
                        }
                    },
                    error: function () {
                        $("#loader-menor").hide();
                        Swal.fire({ title: 'Error en el servidor', icon: 'error' });
                    }
                });
            });
        });

        // ---------- CRUD de motivos ----------
        $("#btnCrearMotivo").on("click", function () {
            const nombre = $("#nuevoMotivoNombre").val().trim();
            const tipo = $("#nuevoMotivoTipo").val();
            if (!nombre) {
                Swal.fire({ title: 'Escriba el nombre del motivo', icon: 'error' });
                return;
            }
            $.ajax({
                url: _URL + "/ajs/almacen/motivo/crear",
                method: "POST",
                data: { nombre: nombre, tipo: tipo },
                success: function (resp) {
                    const data = JSON.parse(resp);
                    if (data.res) {
                        $("#nuevoMotivoNombre").val('');
                        Swal.fire({ title: data.msg, icon: 'success', timer: 1200, showConfirmButton: false });
                        cargarMotivosCrud();
                        cargarMotivosManuales();
                    } else {
                        Swal.fire({ title: 'Error', text: data.msg, icon: 'error' });
                    }
                }
            });
        });

        $("#tbodyMotivos").on("click", ".btnEditarMotivo", function () {
            const id = $(this).data("id");
            const nombreActual = $(this).data("nombre");
            Swal.fire({
                title: 'Editar motivo',
                input: 'text',
                inputValue: nombreActual,
                inputAttributes: { maxlength: 100 },
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value || !value.trim()) return 'Escriba un nombre';
                }
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: _URL + "/ajs/almacen/motivo/editar",
                    method: "POST",
                    data: { motivo_id: id, nombre: result.value.trim() },
                    success: function (resp) {
                        const data = JSON.parse(resp);
                        if (data.res) {
                            Swal.fire({ title: data.msg, icon: 'success', timer: 1200, showConfirmButton: false });
                            cargarMotivosCrud();
                            cargarMotivosManuales();
                        } else {
                            Swal.fire({ title: 'Error', text: data.msg, icon: 'error' });
                        }
                    }
                });
            });
        });

        $("#tbodyMotivos").on("click", ".btnEliminarMotivo", function () {
            const id = $(this).data("id");
            const nombre = $(this).data("nombre");
            Swal.fire({
                title: `¿Eliminar el motivo "${nombre}"?`,
                text: 'El historial del kardex que ya lo usó se conserva.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: _URL + "/ajs/almacen/motivo/eliminar",
                    method: "POST",
                    data: { motivo_id: id },
                    success: function (resp) {
                        const data = JSON.parse(resp);
                        if (data.res) {
                            Swal.fire({ title: data.msg, icon: 'success', timer: 1200, showConfirmButton: false });
                            cargarMotivosCrud();
                            cargarMotivosManuales();
                        } else {
                            Swal.fire({ title: 'Error', text: data.msg, icon: 'error' });
                        }
                    }
                });
            });
        });
    });
</script>
