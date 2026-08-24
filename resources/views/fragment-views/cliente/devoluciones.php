<div class="page-title-box">
    <div class="row align-items-center">
        <div class="clearfix">
            <h6 class="page-title float-end">Ventas</h6>
            <ol class="breadcrumb m-0 float-start">
                <li class="breadcrumb-item"><a href="javascript: void(0);">Facturación</a></li>
                <li class="breadcrumb-item"><a href="/ventas" class="button-link">Ventas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Devoluciones</li>
            </ol>
        </div>
        <div class="col-md-4">
            <div class="float-end d-none d-md-block">

            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card" style="border-radius:20px;box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06)">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-8 d-flex align-items-center gap-3">
                        <h4 class="card-title">Lista de Devoluciones</h4>
                    </div>
                    <!-- Botón de exportar -->
                    <div class="col-md-4 text-end">
                        <a href="r/devoluciones/reporte/xls" class="btn btn-success">
                            <i class="fa fa-file-excel"></i> Exportar
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">

                <div class="table-responsive">

                    <!-- Una fila por DOCUMENTO; los productos se confirman en el modal -->
                    <table id="datatable" class="table table-bordered dt-responsive nowrap text-center table-sm" style="border-collapse: collapse; border-spacing: 0; width: 100%;">

                        <thead>
                        <tr>
                            <th style="text-align: center;">Id</th>
                            <th style="text-align: center;">Documento</th>
                            <th style="text-align: center;">Pedido</th>
                            <th style="text-align: center;">F. Emisión</th>
                            <th style="text-align: center;">Cliente</th>
                            <th style="text-align: center;">Productos</th>
                            <th style="text-align: center;">Estado</th>
                            <th style="text-align: center;">Acción</th>
                        </tr>
                        </thead>

                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación: todos los productos devueltos del documento -->
<div class="modal fade" id="modal-devoluciones-detalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modalDevolucionesTitulo">Devoluciones</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5 id="modalDevolucionesCliente" class="mb-3"></h5>
                <div class="table-responsive">
                    <table class="table table-bordered dt-responsive nowrap text-center table-sm" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                        <tr>
                            <th style="text-align: center;">Código</th>
                            <th style="text-align: center;">Producto</th>
                            <th style="text-align: center;">Cantidad</th>
                            <th style="text-align: center;">Usuario</th>
                            <th style="text-align: center;">Fecha</th>
                            <th style="text-align: center;">Destino</th>
                            <th style="text-align: center;">Acción</th>
                        </tr>
                        </thead>
                        <tbody id="modalDevolucionesBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // 'var' (no let/const) a nivel de <script>: el fragmento se reinyecta al navegar por AJAX y un 'let' global no puede redeclararse.
    var id_rol_devol = <?= isset($_SESSION['rol']) ? intval($_SESSION['rol']) : 0 ?>;
    var devVentaActual = 0;

    $(document).ready(function() {

        datatable = $("#datatable").DataTable({
            paging: true,
            bFilter: true,
            ordering: true,
            searching: true,
            destroy: true,
            order: [[ 0, "desc" ]],
            ajax: {
                url: _URL + "/ajas/devolucones/render",
                method: "POST",
                dataSrc: "",
            },
            language: {
                url: "ServerSide/Spanish.json",
            },
            columns: [
                {
                    data: "id_venta",
                    class: "text-center",
                },
                {
                    data: "factura",
                    class: "text-center",
                },
                {
                    data: "pedido",
                    class: "text-center",
                    render: function(data) {
                        // Pedido (cotización) del que salió la venta
                        return data ? '<span class="badge bg-info">#' + data + '</span>' : '<span class="text-muted">-</span>';
                    }
                },
                {
                    data: "fecha_emision",
                    class: "text-center",
                },
                {
                    data: "cliente",
                    class: "text-center",
                },
                {
                    data: "total_items",
                    class: "text-center",
                    render: function(data) {
                        return '<span class="badge bg-secondary">' + data + '</span>';
                    }
                },
                {
                    data: "pendientes",
                    class: "text-center",
                    render: function(data) {
                        return parseInt(data) > 0
                            ? '<span class="badge bg-warning text-dark">' + data + ' pendiente(s)</span>'
                            : '<span class="badge bg-success">Confirmado</span>';
                    }
                },
                {
                    data: null,
                    class: "text-center",
                    render: function(data, type, row) {
                        return `<button data-id="${row.id_venta}" data-factura="${row.factura}" data-cliente="${row.cliente}" class="btn btn-primary btn-sm btnVerDetalle" title="Confirmar devoluciones"><i class="fa fa-clipboard-check"></i></button>`;
                    }
                },
            ],
        });

        function pintarDetalleDevolucion(lista) {
            var html = '';
            if (!lista.length) {
                html = '<tr><td colspan="7">Ningún dato disponible</td></tr>';
            }
            lista.forEach(function(row) {
                var destino = '<span class="badge bg-warning text-dark">Pendiente</span>';
                if (row.destino === 'a') destino = '<span class="badge bg-success">ALMACÉN</span>';
                if (row.destino === 'p') destino = '<span class="badge bg-danger">PÉRDIDA</span>';
                var accion = '-';
                // Solo admin decide, y solo si aún está pendiente
                if (id_rol_devol == 1 && row.destino !== 'a' && row.destino !== 'p') {
                    accion = `<div class="btn-group">
                        <button data-id="${row.id_devolucion}" data-destino="a" class="btn btn-success btn-sm btnDestino" title="Regresó al almacén"><i class="fa fa-warehouse"></i></button>
                        <button data-id="${row.id_devolucion}" data-destino="p" class="btn btn-danger btn-sm btnDestino" title="Pérdida (producto malogrado)"><i class="fa fa-trash"></i></button>
                    </div>`;
                }
                html += `<tr>
                    <td>${row.codigo || ''}</td>
                    <td class="text-start">${row.descripcion}</td>
                    <td>${row.cantidad}</td>
                    <td>${row.usuario || '-'}</td>
                    <td>${row.fecha || ''}</td>
                    <td>${destino}</td>
                    <td>${accion}</td>
                </tr>`;
            });
            $("#modalDevolucionesBody").html(html);
        }

        function cargarDetalleDevolucion(idVenta) {
            $("#loader-menor").show();
            $.ajax({
                url: _URL + "/ajs/devoluciones/detalle",
                method: "POST",
                data: { id_venta: idVenta },
                success: function(resp) {
                    $("#loader-menor").hide();
                    pintarDetalleDevolucion(JSON.parse(resp));
                },
                error: function() {
                    $("#loader-menor").hide();
                    Swal.fire({ title: 'Error al cargar el detalle', icon: 'error' });
                }
            });
        }

        $("#datatable").on("click", ".btnVerDetalle", function() {
            devVentaActual = $(this).data("id");
            $("#modalDevolucionesTitulo").text("Devoluciones — " + $(this).data("factura"));
            $("#modalDevolucionesCliente").text("Cliente: " + $(this).data("cliente"));
            $("#modalDevolucionesBody").html('<tr><td colspan="7">Cargando...</td></tr>');
            $("#modal-devoluciones-detalle").modal("show");
            cargarDetalleDevolucion(devVentaActual);
        });

        $("#modalDevolucionesBody").on("click", ".btnDestino", function() {
            const id = $(this).data("id");
            const destino = $(this).data("destino");
            const esAlmacen = destino === 'a';
            Swal.fire({
                title: esAlmacen
                    ? '¿Confirmar que el producto regresó al almacén?'
                    : '¿Registrar como PÉRDIDA (producto malogrado)?',
                text: esAlmacen
                    ? 'El stock se mantiene; solo se deja constancia.'
                    : 'Se descontará del stock y quedará registrado en el Kardex.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: esAlmacen ? '#28a745' : '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, confirmar'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $("#loader-menor").show();
                $.ajax({
                    url: _URL + "/ajs/devoluciones/destino",
                    method: "POST",
                    data: { id_devolucion: id, destino: destino },
                    success: function(resp) {
                        $("#loader-menor").hide();
                        const data = JSON.parse(resp);
                        if (data.res) {
                            Swal.fire({ title: data.msg, icon: 'success', timer: 1500, showConfirmButton: false });
                            cargarDetalleDevolucion(devVentaActual);
                            datatable.ajax.reload(null, false);
                        } else {
                            Swal.fire({ title: 'Error', text: data.msg, icon: 'error' });
                        }
                    },
                    error: function() {
                        $("#loader-menor").hide();
                        Swal.fire({ title: 'Error en el servidor', icon: 'error' });
                    }
                });
            });
        });

    });
</script>
