<div class="page-title-box">
    <div class="row align-items-center">
        <!-- <div class="col-md-8">
            <h6 class="page-title">Compras</h6>
            <ol class="breadcrumb m-0">
                <li class="breadcrumb-item"><a href="javascript: void(0);">Facturacion</a></li>
                <li class="breadcrumb-item"><a href="/ventas" class="button-link">Ventas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Productos</li>
            </ol>
        </div> -->
        <div class="clearfix">
            <h6 class="page-title float-end">Compras</h6>
            <ol class="breadcrumb m-0 float-start">
                <li class="breadcrumb-item"><a href="javascript: void(0);">Facturaci��n</a></li>
                <li class="breadcrumb-item"><a href="/ventas" class="button-link">Ventas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Productos</li>
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
        <div class="card"  style="border-radius:20px;box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06)">
            <div class="card-body">



                <div class="card-title-desc text-end">
                    <a href="/compras/add" class="btn btn-primary button-link">
                        <i class="fa fa-plus "></i> Agregar Compra
                    </a>
                    <a target="_blank" class="btn btn-info" href="<?= URL::to('/reporte/compras') ?>"><i class="fa fa-file"></i> Exportar Reporte</a>
                   
                </div>


                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered dt-responsive nowrap text-center table-sm" style="border-collapse: collapse; border-spacing: 0; width: 100%;">

                        <thead>
                        <tr>
                            <th style="text-align: center;">Id</th>
                            <th style="text-align: center;">F. Emision</th>
                            <th style="text-align: center;">F. Vencimiento</th>
                            <th style="text-align: center;">Serie</th>
                            <th style="text-align: center;">Numero</th>
<th style="text-align: center;" width="50%">Razon Social</th>
                             <th style="text-align: center;">Total</th>
                             <th style="text-align: center;">Recepción</th>
                             <th style="text-align: center;">Recepcionar</th>
                             <th style="text-align: center;">Editar</th>
                            <th style="text-align: center;">Detalles</th>
                            <th style="text-align: center;">Reporte</th>
                        </tr>
                        </thead>

                    </table>
                </div>

                <!-- Modal de RECEPCIÓN de mercadería -->
                <div class="modal fade" id="modalRecepcion" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="tituloRecepcion">Recepcionar mercadería</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">

                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="tab-recepcionar" data-bs-toggle="tab"
                                            data-bs-target="#pane-recepcionar" type="button" role="tab">
                                            <i class="fa fa-truck-loading"></i> Recepcionar
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="tab-historial" data-bs-toggle="tab"
                                            data-bs-target="#pane-historial" type="button" role="tab">
                                            <i class="fa fa-history"></i> Historial de recepciones
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content pt-3">

                                    <!-- TAB 1: registrar recepción -->
                                    <div class="tab-pane fade show active" id="pane-recepcionar" role="tabpanel">
                                        <p class="text-muted fs-7">Indique cuánto llegó de cada producto en esta entrega.
                                            Lo <strong>recibido</strong> suma al stock (y queda en el Kardex).
                                            Lo <strong>rechazado</strong> (ej. por vencer, dañado) no entra al stock y queda
                                            registrado con su motivo. Puede recepcionar en varias entregas (parciales).</p>
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-center table-sm" style="width:100%;">
                                                <thead>
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th>Pedido</th>
                                                        <th>Ya recibido</th>
                                                        <th>Ya rechazado</th>
                                                        <th>Pendiente</th>
                                                        <th style="width:110px;">Recibir ahora</th>
                                                        <th style="width:110px;">Rechazar ahora</th>
                                                        <th>Motivo rechazo</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodyRecepcion"></tbody>
                                            </table>
                                        </div>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-primary" id="btnConfirmarRecepcion">
                                                <i class="fa fa-check"></i> Confirmar recepción
                                            </button>
                                        </div>
                                    </div>

                                    <!-- TAB 2: historial de recepciones -->
                                    <div class="tab-pane fade" id="pane-historial" role="tabpanel">
                                        <p class="text-muted fs-7">Cada entrega parcial queda registrada aquí. Las
                                            recepciones anuladas permanecen visibles (nunca se borran).</p>
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-center table-sm" style="width:100%;">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Fecha</th>
                                                        <th>Producto</th>
                                                        <th>Recibido</th>
                                                        <th>Rechazado</th>
                                                        <th>Motivo rechazo</th>
                                                        <th>Usuario</th>
                                                        <th>Estado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodyHistorialRecepcion"></tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalDetalle" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 900px;">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Detalles de Productos</h5>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3" id="infoCards">
                                    <div class="col-md-4">
                                        <div class="card text-white bg-primary">
                                            <div class="card-body text-center py-3">
                                                <h6 class="card-title m-0">Total Productos</h6>
                                                <h3 class="m-0" id="totalProductos">0</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card text-white bg-success">
                                            <div class="card-body text-center py-3">
                                                <h6 class="card-title m-0">Cantidad Total</h6>
                                                <h3 class="m-0" id="cantidadTotal">0</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card text-white bg-info">
                                            <div class="card-body text-center py-3">
                                                <h6 class="card-title m-0">Total</h6>
                                                <h3 class="m-0" id="totalMonto">S/ 0.00</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <table id="datatableProductoDetalle" class="table table-bordered dt-responsive text-center table-sm" style="border-collapse: collapse; border-spacing: 0; width: 100%;">

                                    <thead>
                                    <tr>
                                        <th style="text-align: center;">Id</th>
                                        <th style="text-align: center;" >Producto</th>
                                        <th style="text-align: center;">Cantidad</th>
                                        <th style="text-align: center;">Precio</th>

                                    </tr>
                                    </thead>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // 'var' (no let/const) a nivel de <script>: el fragmento se reinyecta al navegar por AJAX y un 'let' global no puede redeclararse.
    var idCompraRecepcion = null;
    var esAdminCompras = <?= (isset($_SESSION['rol']) && $_SESSION['rol'] == 1) ? 1 : 0 ?>;

    function cargarRecepcion(id, mostrarModal) {
        $("#loader-menor").show();
        $.ajax({
            type: 'POST',
            url: _URL + '/ajs/compras/recepcion/estado',
            data: { id: id },
            success: function(resp) {
                $("#loader-menor").hide();
                const data = JSON.parse(resp);

                // Tab 1: formulario de recepción
                let html = '';
                data.productos.forEach(function(p) {
                    const pendiente = parseFloat(p.pendiente);
                    const sinPendiente = pendiente <= 0;
                    html += `<tr data-producto="${p.id_producto}" data-pendiente="${pendiente}">
                        <td class="text-start">${p.codigo} — ${p.descripcion}</td>
                        <td>${parseFloat(p.pedida).toFixed(2)}</td>
                        <td class="text-success fw-bold">${parseFloat(p.recibida).toFixed(2)}</td>
                        <td class="text-danger fw-bold">${parseFloat(p.rechazada).toFixed(2)}</td>
                        <td class="fw-bold">${pendiente.toFixed(2)}</td>
                        <td><input type="number" class="form-control form-control-sm inpRecibir" min="0" step="0.01" value="" ${sinPendiente ? 'disabled' : ''}></td>
                        <td><input type="number" class="form-control form-control-sm inpRechazar" min="0" step="0.01" value="" ${sinPendiente ? 'disabled' : ''}></td>
                        <td><input type="text" class="form-control form-control-sm inpMotivo" maxlength="250" placeholder="Ej: por vencer" ${sinPendiente ? 'disabled' : ''}></td>
                    </tr>`;
                });
                $("#tbodyRecepcion").html(html);

                // Tab 2: historial de recepciones
                let htmlHist = '';
                if (!data.historial || data.historial.length === 0) {
                    htmlHist = '<tr><td colspan="9" class="text-muted">Aún no hay recepciones registradas</td></tr>';
                } else {
                    data.historial.forEach(function(r) {
                        const anulada = r.estado === '0';
                        const badge = anulada
                            ? '<span class="badge bg-secondary">ANULADA</span>'
                            : '<span class="badge bg-primary">VIGENTE</span>';
                        let acciones = '-';
                        if (esAdminCompras == 1 && !anulada) {
                            acciones = `<div class="btn-group">
                                <button class="btn btn-warning btn-sm btnEditarRecepcion" data-id="${r.recepcion_id}"
                                    data-recibida="${r.cantidad_recibida}" data-rechazada="${r.cantidad_rechazada}"
                                    data-motivo="${r.motivo_rechazo ?? ''}" title="Editar"><i class="fa fa-edit"></i></button>
                                <button class="btn btn-danger btn-sm btnAnularRecepcion" data-id="${r.recepcion_id}" title="Anular (revierte stock)"><i class="fa fa-ban"></i></button>
                            </div>`;
                        }
                        htmlHist += `<tr ${anulada ? 'style="opacity:0.6"' : ''}>
                            <td>${r.recepcion_id}</td>
                            <td>${r.fecha}</td>
                            <td class="text-start">${r.codigo} — ${r.descripcion}</td>
                            <td class="text-success fw-bold">${parseFloat(r.cantidad_recibida).toFixed(2)}</td>
                            <td class="text-danger fw-bold">${parseFloat(r.cantidad_rechazada).toFixed(2)}</td>
                            <td>${r.motivo_rechazo ?? '-'}</td>
                            <td>${r.usuario}</td>
                            <td>${badge}</td>
                            <td>${acciones}</td>
                        </tr>`;
                    });
                }
                $("#tbodyHistorialRecepcion").html(htmlHist);

                if (mostrarModal) {
                    $("#modalRecepcion").modal("show");
                }
            },
            error: function() { $("#loader-menor").hide(); }
        });
    }

    $(document).ready(function() {

        // ---------- RECEPCIÓN DE MERCADERÍA ----------
        $("#datatable").on("click", ".btnRecepcionar", function() {
            idCompraRecepcion = $(this).data("id");
            $("#tituloRecepcion").text("Recepciones — Compra N° " + idCompraRecepcion);
            cargarRecepcion(idCompraRecepcion, true);
        });

        // Anular recepción
        $("#tbodyHistorialRecepcion").on("click", ".btnAnularRecepcion", function() {
            const id = $(this).data("id");
            Swal.fire({
                title: `¿Anular la recepción #${id}?`,
                text: 'El stock recibido se revertirá. El registro quedará como ANULADA (no se borra).',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, anular'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $("#loader-menor").show();
                $.ajax({
                    type: 'POST',
                    url: _URL + '/ajs/compras/recepcion/anular',
                    data: { recepcion_id: id },
                    success: function(resp) {
                        $("#loader-menor").hide();
                        const data = JSON.parse(resp);
                        if (data.res) {
                            Swal.fire({ title: data.msg, icon: 'success', timer: 1500, showConfirmButton: false });
                            cargarRecepcion(idCompraRecepcion, false);
                            datatable.ajax.reload(null, false);
                        } else {
                            Swal.fire({ title: 'Error', text: data.msg, icon: 'error' });
                        }
                    },
                    error: function() { $("#loader-menor").hide(); }
                });
            });
        });

        // Editar recepción
        $("#tbodyHistorialRecepcion").on("click", ".btnEditarRecepcion", function() {
            const id = $(this).data("id");
            const recibida = $(this).data("recibida");
            const rechazada = $(this).data("rechazada");
            const motivo = $(this).data("motivo");
            Swal.fire({
                title: `Editar recepción #${id}`,
                html: `
                    <div class="text-start">
                        <label class="form-label">Cantidad recibida</label>
                        <input type="number" id="swalRecibida" class="form-control" min="0" step="0.01" value="${recibida}">
                        <label class="form-label mt-2">Cantidad rechazada</label>
                        <input type="number" id="swalRechazada" class="form-control" min="0" step="0.01" value="${rechazada}">
                        <label class="form-label mt-2">Motivo del rechazo</label>
                        <input type="text" id="swalMotivo" class="form-control" maxlength="250" value="${motivo}">
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const rec = parseFloat(document.getElementById('swalRecibida').value) || 0;
                    const rech = parseFloat(document.getElementById('swalRechazada').value) || 0;
                    const mot = document.getElementById('swalMotivo').value.trim();
                    if (rec <= 0 && rech <= 0) {
                        Swal.showValidationMessage('Ingrese al menos una cantidad');
                        return false;
                    }
                    if (rech > 0 && !mot) {
                        Swal.showValidationMessage('Indique el motivo del rechazo');
                        return false;
                    }
                    return { recibida: rec, rechazada: rech, motivo: mot };
                }
            }).then((result) => {
                if (!result.isConfirmed) return;
                $("#loader-menor").show();
                $.ajax({
                    type: 'POST',
                    url: _URL + '/ajs/compras/recepcion/editar',
                    data: {
                        recepcion_id: id,
                        recibida: result.value.recibida,
                        rechazada: result.value.rechazada,
                        motivo: result.value.motivo
                    },
                    success: function(resp) {
                        $("#loader-menor").hide();
                        const data = JSON.parse(resp);
                        if (data.res) {
                            Swal.fire({ title: data.msg, icon: 'success', timer: 1500, showConfirmButton: false });
                            cargarRecepcion(idCompraRecepcion, false);
                            datatable.ajax.reload(null, false);
                        } else {
                            Swal.fire({ title: 'Error', text: data.msg, icon: 'error' });
                        }
                    },
                    error: function() { $("#loader-menor").hide(); }
                });
            });
        });

        $("#btnConfirmarRecepcion").on("click", function() {
            const items = [];
            let errorFila = null;
            $("#tbodyRecepcion tr").each(function() {
                const fila = $(this);
                const pendiente = parseFloat(fila.data("pendiente"));
                const recibida = parseFloat(fila.find(".inpRecibir").val()) || 0;
                const rechazada = parseFloat(fila.find(".inpRechazar").val()) || 0;
                const motivo = fila.find(".inpMotivo").val() || '';
                if (recibida < 0 || rechazada < 0) return;
                if (recibida + rechazada > pendiente + 0.001) {
                    errorFila = fila.find("td:first").text();
                    return false;
                }
                if (rechazada > 0 && !motivo.trim()) {
                    errorFila = fila.find("td:first").text() + ' (falta el motivo del rechazo)';
                    return false;
                }
                if (recibida > 0 || rechazada > 0) {
                    items.push({
                        id_producto: fila.data("producto"),
                        recibida: recibida,
                        rechazada: rechazada,
                        motivo: motivo.trim()
                    });
                }
            });

            if (errorFila) {
                Swal.fire({ title: 'Revise la fila', text: errorFila, icon: 'error' });
                return;
            }
            if (items.length === 0) {
                Swal.fire({ title: 'Ingrese al menos una cantidad a recibir o rechazar', icon: 'error' });
                return;
            }

            Swal.fire({
                title: '¿Confirmar esta recepción?',
                text: 'Lo recibido sumará al stock; lo rechazado quedará registrado.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, confirmar'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $("#loader-menor").show();
                $.ajax({
                    type: 'POST',
                    url: _URL + '/ajs/compras/recepcion/registrar',
                    data: {
                        id_compra: idCompraRecepcion,
                        items: JSON.stringify(items)
                    },
                    success: function(resp) {
                        $("#loader-menor").hide();
                        const data = JSON.parse(resp);
                        if (data.res) {
                            Swal.fire({ title: 'Recepción registrada', icon: 'success', timer: 1500, showConfirmButton: false });
                            cargarRecepcion(idCompraRecepcion, false);
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

        



        datatable = $("#datatable").DataTable({
            order: [[ 0, "desc" ]],
            paging: true,
            bFilter: true,
            ordering: true,
            searching: true,
            destroy: true,
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 6): ?>
            // ALMACEN (rol 6): solo consulta. Se ocultan las columnas Recepcionar (8) y Editar (9);
            // se ocultan en vez de quitar los <th> para no desplazar los índices del resto.
            columnDefs: [{ targets: [8, 9], visible: false }],
            <?php endif; ?>
            ajax: {
                url: _URL + "/ajs/prodcutos/compras/render",
                method: "POST",
                dataSrc: "",
            },
            language: {
                url: "ServerSide/Spanish.json",
            },
            columns: [{
                data: "id_compra",
                class: "text-center",
            },
                {
                    data: "fecha_emision",
                    class: "text-center",
                },
                {
                    data: "fecha_vencimiento",
                    class: "text-center",
                },
                {
                    data: "serie",
                    class: "text-center",
                },
                {
                    data: "numero",
                    class: "text-center",
                },
                {
                    data: "razon_social",
                    class: "text-center",
                },
                {
                    data: "total",
                    class: "text-center",
                    render: function(data, type, row) {
                        return "S/ " + parseFloat(data).toFixed(2);
                    },
                },
                {
                    data: "estado_recepcion",
                    class: "text-center",
                    render: function(data) {
                        if (data === 'c') return '<span class="badge bg-success">RECEPCIONADA</span>';
                        if (data === 'x') return '<span class="badge bg-info">PARCIAL</span>';
                        return '<span class="badge bg-warning">PENDIENTE</span>';
                    },
                },
                {
                    data: null,
                    class: "text-center",
                    render: function(data, type, row) {
                        return `<div class="text-center">
              <div class="btn-group"><button data-id="${Number(row.id_compra)}" class="btn btn-sm btn-primary btnRecepcionar" title="Recepciones de la compra"><i class="fa fa-truck-loading"></i> </button></div></div>`;
                    },
                },
                {
                    data: null,
                    class: "text-center",
                    render: function(data, type, row) {
                        return `<div class="text-center">
              <div class="btn-group"><a href="/compras/add?edit=${row.id_compra}" class="btn btn-sm btn-warning button-link"><i class="fa fa-edit"></i> </a></div></div>`;
                    },
                },
                {
                    data: null,
                    class: "text-center",
                    render: function(data, type, row) {
                        return `<div class="text-center">
              <div class="btn-group"><button  data-id="${Number(
                            row.id_compra
                        )}" class="btn  btn-sm btn-success btnDetalle"><i class="fa fa-eye"></i> </button></div></div>`;
                    },
                },
                {
                    data: null,
                    class: "text-center",
                    render: function(data, type, row) {
                        return `<div class="text-center">
              <div class="btn-group"><a target="_blank" class="btn btn-sm btn-info" href="${_URL}/reporte/compras/pdf/${row.id_compra}" ><i class="fa fa-file"></i> </a></div></div>`;
                    },
                },
            ],
        });


        
        $("#datatable").on("click", ".btnDetalle ", function(event) {
            $("#loader-menor").show()
            var table = $("#tabla_clientes").DataTable();
            var trid = $(this).closest("tr").attr("id");
            var id = $(this).data("id");
            $("#modalDetalle").modal("show");
            $("#modalDetalle")
                .find(".modal-title")
                .text("Detelle compra N��" + id);
            $.ajax({
                type: 'POST',
                url: _URL + '/ajas/compra/detalle',
                data: {
                    id: id
                },
                success: function(resp) {
                    $("#loader-menor").hide()
                    let data = JSON.parse(resp)

                    let totalProductos = data.length;
                    let cantidadTotal = data.reduce((sum, item) => sum + Number(item.cantidad), 0);
                    let totalMonto = data.reduce((sum, item) => sum + (Number(item.cantidad) * Number(item.precio)), 0);

                    $("#totalProductos").text(totalProductos);
                    $("#cantidadTotal").text(cantidadTotal);
                    $("#totalMonto").text("S/ " + totalMonto.toFixed(2));

                    if ($.fn.DataTable.isDataTable("#datatableProductoDetalle")) {
                        $("#datatableProductoDetalle").DataTable().destroy();
                    }
                    datatableProductoDetalle = $("#datatableProductoDetalle").DataTable({

                        paging: true,
                        bFilter: true,
                        ordering: true,
                        searching: true,
                        destroy: true,

                        language: {
                            url: "ServerSide/Spanish.json",
                        },
                        data: data,
                        columns: [{
                            data: "id_producto_venta",
                            class: "text-center",
                        },
                            {
                                data: "descripcion",
                                class: "text-center",
                            },
                            {
                                data: "cantidad",
                                class: "text-center",
                            },
                            {
                                data: "precio",
                                class: "text-center",
                            },
                        ],
                    });
                }
            });

        });
    })
</script>