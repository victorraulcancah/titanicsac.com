<div class="page-title-box">
    <div class="row align-items-center">
        <!-- <div class="col-md-8">
            <h6 class="page-title">Ventas</h6>
            <ol class="breadcrumb m-0">
                <li class="breadcrumb-item"><a href="javascript: void(0);">Facturacion</a></li>
                <li class="breadcrumb-item"><a href="/ventas" class="button-link">Ventas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Productos</li>
            </ol>
        </div> -->
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

                    <table id="datatable" class="table table-bordered dt-responsive nowrap text-center table-sm" style="border-collapse: collapse; border-spacing: 0; width: 100%;">

                        <thead>
                        <tr>
                            <th style="text-align: center;">Id</th>
                            <th style="text-align: center;">Factura</th>
                            <th style="text-align: center;">FechaEmisión</th>
                            <th style="text-align: center;">Código</th>
                            <th style="text-align: center;">Producto</th>
                            <th style="text-align: center;">Cantidad</th>
                            <th style="text-align: center;">Usuario</th>
                            <th style="text-align: center;">Fecha</th>
                            <th style="text-align: center;">Destino</th>
                            <th style="text-align: center;">Acción</th>
                        </tr>
                        </thead>

                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // 'var' (no let/const) a nivel de <script>: el fragmento se reinyecta al navegar por AJAX y un 'let' global no puede redeclararse.
    var id_rol_devol = <?= isset($_SESSION['rol']) ? intval($_SESSION['rol']) : 0 ?>;

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
                    data: "id_devolucion",
                    class: "text-center",
                },
                {
                    data: "factura",
                    class: "text-center",
                },
                {
                    data: "fecha_emision",
                    class: "text-center",
                },
                {
                    data: "codigo",
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
                    data: "usuario",
                    class: "text-center",
                },
                {
                    data: "fecha",
                    class: "text-center",
                },
                {
                    data: "destino",
                    class: "text-center",
                    render: function(data) {
                        if (data === 'a') return '<span class="badge bg-success">ALMACÉN</span>';
                        if (data === 'p') return '<span class="badge bg-danger">PÉRDIDA</span>';
                        return '<span class="badge bg-warning">Pendiente</span>';
                    }
                },
                {
                    data: null,
                    class: "text-center",
                    render: function(data, type, row) {
                        // Solo admin decide, y solo si aún está pendiente
                        if (id_rol_devol != 1 || row.destino === 'a' || row.destino === 'p') {
                            return '-';
                        }
                        return `<div class="btn-group">
                            <button data-id="${row.id_devolucion}" data-destino="a" class="btn btn-success btn-sm btnDestino" title="Regresó al almacén"><i class="fa fa-warehouse"></i></button>
                            <button data-id="${row.id_devolucion}" data-destino="p" class="btn btn-danger btn-sm btnDestino" title="Pérdida (producto malogrado)"><i class="fa fa-trash"></i></button>
                        </div>`;
                    }
                },
            ],
        });

        $("#datatable").on("click", ".btnDestino", function() {
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