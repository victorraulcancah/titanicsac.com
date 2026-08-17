<div class="page-title-box">
    <div class="clearfix">
        <h6 class="page-title float-end">Kardex de Almacén</h6>
        <ol class="breadcrumb m-0 float-start">
            <li class="breadcrumb-item"><a href="javascript: void(0);">Almacén</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kardex</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card" style="border-radius:20px;box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06)">
            <div class="card-header">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4 position-relative">
                        <label class="form-label form-label-sm fs-7">Producto</label>
                        <input type="text" id="buscarProducto" class="form-control form-control-sm"
                            placeholder="Escriba código o descripción (mín. 2 letras)" autocomplete="off">
                        <input type="hidden" id="idProductoSel" value="">
                        <div id="resultadosProducto" class="list-group position-absolute w-100" style="z-index:1050; max-height:260px; overflow:auto;"></div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fs-7">Fecha inicio</label>
                        <input type="date" id="fecha_inicio" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fs-7">Fecha fin</label>
                        <input type="date" id="fecha_fin" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="button" class="btn btn-sm btn-primary" onclick="buscarKardex()">
                            <i class="fa fa-search"></i> Buscar
                        </button>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="limpiarKardex()">
                            <i class="fa fa-broom"></i> Ver todo
                        </button>
                    </div>
                </div>
                <div class="mt-2">
                    <span id="productoSeleccionado" class="badge bg-info" style="display:none;"></span>
                    <span id="stockActual" class="badge bg-dark" style="display:none;"></span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaKardex" class="table table-bordered text-center table-sm"
                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Movimiento</th>
                                <th>Motivo</th>
                                <th>Cantidad</th>
                                <th>Stock Anterior</th>
                                <th>Stock Actual</th>
                                <th>Estado</th>
                                <th>Referencia</th>
                                <th>Usuario</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let tablaKardex = null;

    function pintarKardex(data) {
        tablaKardex = $("#tablaKardex").DataTable({
            paging: true,
            searching: true,
            ordering: false,
            destroy: true,
            deferRender: true,
            data: data,
            language: { url: "ServerSide/Spanish.json" },
            columns: [
                { data: "fecha", class: "text-center" },
                { data: "codigo", class: "text-center" },
                { data: "descripcion", class: "text-start" },
                {
                    data: "tipo",
                    class: "text-center",
                    render: function (data) {
                        return data === 'i'
                            ? '<span class="badge bg-success">INGRESO</span>'
                            : '<span class="badge bg-danger">SALIDA</span>';
                    }
                },
                { data: "motivo", class: "text-center" },
                {
                    data: null,
                    class: "text-center",
                    render: function (data, type, row) {
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
                            ? '<span class="badge bg-secondary">ANULADO</span>'
                            : '<span class="badge bg-primary">VIGENTE</span>';
                    }
                },
                { data: "referencia", class: "text-center" },
                { data: "usuario", class: "text-center" },
                { data: "observacion", class: "text-center" }
            ]
        });
    }

    function cargarKardexGeneral() {
        $("#loader-menor").show();
        $.ajax({
            url: _URL + "/ajs/almacen/kardex/general",
            method: "POST",
            success: function (resp) {
                $("#loader-menor").hide();
                pintarKardex(JSON.parse(resp));
            },
            error: function () { $("#loader-menor").hide(); }
        });
    }

    function buscarKardex() {
        let idProducto = $("#idProductoSel").val();
        if (!idProducto) {
            cargarKardexGeneral();
            return;
        }
        $("#loader-menor").show();
        $.ajax({
            url: _URL + "/ajs/almacen/kardex/producto",
            method: "POST",
            data: {
                id_producto: idProducto,
                fecha_inicio: $("#fecha_inicio").val(),
                fecha_fin: $("#fecha_fin").val()
            },
            success: function (resp) {
                $("#loader-menor").hide();
                pintarKardex(JSON.parse(resp));
            },
            error: function () { $("#loader-menor").hide(); }
        });
    }

    function limpiarKardex() {
        $("#idProductoSel").val('');
        $("#buscarProducto").val('');
        $("#fecha_inicio").val('');
        $("#fecha_fin").val('');
        $("#productoSeleccionado").hide();
        $("#stockActual").hide();
        cargarKardexGeneral();
    }

    $(document).ready(function () {
        cargarKardexGeneral();

        let timerBusqueda = null;
        $("#buscarProducto").on("input", function () {
            const q = $(this).val().trim();
            $("#idProductoSel").val('');
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
            const id = $(this).data("id");
            const codigo = $(this).data("codigo");
            const descripcion = $(this).data("descripcion");
            const stock = $(this).data("stock");
            $("#idProductoSel").val(id);
            $("#buscarProducto").val(codigo + " — " + descripcion);
            $("#resultadosProducto").empty();
            $("#productoSeleccionado").text(codigo + " | " + descripcion).show();
            $("#stockActual").text("Stock actual: " + stock).show();
            buscarKardex();
        });
    });
</script>
