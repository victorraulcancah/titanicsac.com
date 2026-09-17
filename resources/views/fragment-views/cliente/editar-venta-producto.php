<?php
$conexion = (new Conexion())->getConexion();

$datoEmpresa = $conexion->query("select * from empresas where id_empresa='{$_SESSION['id_empresa']}'")->fetch_assoc();

$igv_empresa = $datoEmpresa['igv'];



?>
<style>
    /* Cuotas de pago (mismo modal que la venta nueva y la conversion de pedidos) */
    @media (max-width: 767.98px) {
        /* Cuotas de pago: en móvil cada cuota se muestra como tarjeta apilada
           (sin desplazamiento horizontal). La etiqueta sale del atributo data-label. */
        #modal-cuotas-venta .table-responsive {
            overflow-x: visible;
        }
        .tabla-cuotas {
            min-width: 0 !important;
            border: 0 !important;
        }
        .tabla-cuotas thead {
            display: none;
        }
        .tabla-cuotas tbody tr {
            display: block;
            border: 1px solid #dee2e6;
            border-radius: .5rem;
            padding: .25rem .5rem;
            margin-bottom: .75rem;
            background: #fff;
        }
        .tabla-cuotas tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            border: 0 !important;
            border-bottom: 1px solid #f1f1f1 !important;
            padding: .45rem .1rem;
            text-align: right;
            white-space: normal;
        }
        .tabla-cuotas tbody td:last-child {
            border-bottom: 0 !important;
        }
        .tabla-cuotas tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            text-align: left;
            flex: 0 0 auto;
        }
        .tabla-cuotas tbody td > input,
        .tabla-cuotas tbody td > select {
            width: auto !important;
            max-width: 60%;
        }
    }
</style>
<div class="page-title-box">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h6 class="page-title">Ventas</h6>
            <ol class="breadcrumb m-0">
                <li class="breadcrumb-item"><a href="javascript: void(0);">Facturacion</a></li>
                <li class="breadcrumb-item"><a href="/ventas" class="button-link">Ventas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Productos</li>
            </ol>
        </div>
        <div class="col-md-4">
            <div class="float-end d-none d-md-block">
                <button id="backbuttonvp" href="/ventas" type="button" class="btn btn-warning button-link"><i class="fa fa-arrow-left"></i> Regresar</button>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="fecha-app" value="<?= date("Y-m-d") ?>">
<?php
if (isset($_GET["coti"])) {
    echo "<input type='hidden' id='cotizacion' value='{$_GET["coti"]}'>";
}
?>
<div class="row" id="container-vue">
    <div class="col-12 row">
        <div class="col-md-8">
            <div class="card ">
                <div class="card-body">

                    <h4 class="card-title">Venta de Productos</h4>

                    <div class="card-title-desc">

                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <form v-on:submit.prevent="addProduct" class="form-horizontal">
                                <input type="hidden" name="" id="idVentaUrl" value="<?php echo $idVenta; ?>">
                                <div class="form-group row mb-3">
                                    <label class="col-lg-2 control-label">Buscar</label>
                                    <div class="col-lg-10">

                                        <div class="input-group">
                                            <input type="text" placeholder="Consultar Productos" class="form-control ui-autocomplete-input" id="input_buscar_productos" autocomplete="off">
                                            <div class="input-group-btn">
                                                <button hidden @click="buscarPorCodigoBarra" type="button" class="btn btn-primary">Buscar Por Codigo Barra</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row mb-3">
                                    <label class="col-lg-2 control-label">Descripcion</label>
                                    <div class="col-lg-10">
                                        <input required v-model="producto.descripcion" type="text" placeholder="Descripcion" class="form-control" readonly="true">
                                    </div>
                                </div>
                                <div class="form-group ">
                                    <div class="row" style="margin-right: 0;">
                                        <div class="row  col-lg-3">
                                            <label for="example-text-input" class="col-form-label">Stock Actual</label>

                                            <div class="input-group">
                                                <input disabled v-model="producto.stock" class="form-control text-center" type="text" placeholder="0">
                                                <span class="input-group-text" id="basic-addon1">{{producto.medida}}</span>
                                            </div>
                                        </div>
                                        <div class="row  col-lg-5">
                                            <label for="example-text-input" class=" col-form-label">Cantidad</label>

                                            <div class="input-group">
                                                <input @keypress="onlyNumberNeg" required v-model="producto.cantidad" class="form-control text-center" type="text" placeholder="0" id="example-text-input">
                                                <select v-model="producto.presentacion" class="form-select">
                                                    <option v-for="(item ) in listaOpcionesPResen" :value="item.cod">{{item.nom}}</option>
                                                </select>
                                                <span class="input-group-text" >De</span>
                                                <template v-if="listaMedidasCnt.length>0">
                                                    <select required v-model="producto.presentacionCnt" class="form-select">
                                                        <option v-for="itm in listaMedidasCnt">{{itm}}</option>
                                                    </select>
                                                </template>
                                                <template v-else>
                                                    <input  v-model="producto.presentacionCnt" required @keypress="onlyNumber" class="form-control" />
                                                </template>
                                                <span class="input-group-text" >{{producto.medida}}</span>
                                            </div>


                                        </div>
                                        <div class="row  col-lg-3">
                                            <label for="example-text-input" class=" col-form-label">Precio</label>
                                            <div class="input-group">
                                                <select name="" id="" class="form-control" v-model="producto.precioVenta">
                                                    <option v-for="(value, key) in precioProductos" :value="value.precio" :key="key">{{ value.precio }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-2">
                                            <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Agregar</button>
                                        </div>
                                    </div>

                                </div>


                            </form>
                        </div>

                        <div class="row">
                            <div class="col-md-9"></div>
                            <div class="col-md-3">
                                <label for="">Usar</label>
                                <select name="" id="" class="form-control text-right" v-model="usar_precio" @change="cambiarPrecio($event)">
                                    <option value="1">Precio</option>
                                    <option value="2">Credito 1</option>
                                    <option value="3">Credito 2</option>
                                    <option value="4">Precio x Saco</option>
                                    <option value="5">Precio x Mayor</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12 mt-5">
                            <h4>Detalle Venta</h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Medida</th>
                                        <th>P. Unit.</th>
                                        <th>Parcial</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item,index) in productos">
                                        <td>{{index+1}}</td>
                                        <td>{{item.descripcion}} <span v-if="item.cantidad < 0" class="badge bg-danger" title="Recojo: producto que el cliente devuelve. Regresa al stock y se descuenta de la venta">RECOJO</span></td>
                                        <td><span v-if="!item.edicion" :class="{'text-danger fw-bold': item.cantidad < 0}">{{cantidadFinal(item)}}</span><span v-if="item.edicion" class="text-nowrap"><input type="number" step="0.01" style="width: 95px;" :value="cantidadFinalNum(item)" @keypress="onlyNumberNeg" @change="setCantidadFinal(item, $event.target.value)" title="Cantidad final a entregar"> {{ item.medida }}</span></td>
                                        <td> </td>
                                        <td><span v-if="!item.edicion" >{{formatoDecimal(item.precioVenta)}}</span><input v-if="item.edicion" type="number" step="0.01" style="width: 100px;" :value="precioUnitarioNum(item)" @change="setPrecioUnitario(item, $event.target.value)" title="Precio por unidad (ej. por kilo)"></td>

                                        <td :class="{'text-danger fw-bold': item.cantidad < 0}">{{formatoDecimal(item.precioVenta*item.cantidad)}}</td>
                                        <td><button @click="eliminarItemPro(index)" type="button" class="btn btn-danger btn-sm">
                                                <i class="fa fa-times"></i>
                                            </button>
                                            <button v-if="!item.edicion" @click="item.edicion=true" class="btn btn-info btn-sm"><i class="fa fa-edit"></i></button>
                                            <button v-if="item.edicion" @click="item.edicion=false" class="btn btn-warning btn-sm"><i class="fa fa-save"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>

                </div>
            </div>

        </div>
        <div class="col-md-4">
            <div class="card ">
                <div class="card-body">
                    <div class="col-md-12">
                        <div class="widget padding-0 white-bg">
                            <div class="padding-20 text-center">
                                <form v-on:submit.prevent role="form" class="form-horizontal">
                                    <div class="row">
                                        <div class="col-md-12 form-group">
                                            <label class="control-label">Aplicar IGV Venta</label>
                                            <select :disabled="!apli_igv_is" v-model="venta.apli_igv" class="form-control">
                                                <option value="1">SI</option>
                                                <option value="0">NO</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label class="control-label">Documento</label>
                                            <div class="col-md-12">
                                                <select @change="onChangeTiDoc($event)" v-model="venta.tipo_doc" class="form-control">
                                                    <option value="1">BOLETA DE VENTA</option>
                                                    <option value="2">FACTURA</option>
                                                    <option value="6">NOTA DE VENTA</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label class="control-label">Tipo Pago</label>
                                            <select v-model="venta.tipo_pago" @change="changeTipoPago" class="form-control">
                                                <option value="1">Contado</option>
                                                <option value="2">Credito</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 form-group">
                                            <label class="control-label">Método Pago</label>
                                            <select class="form-control" v-model='venta.metodo'>
                                                <option v-for="(value, key) in metodosPago" :value="value.id_metodo_pago" :key="key">{{ value.nombre }}</option>

                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-4 control-label">Ser | Num</label>
                                        <div class="col-lg-12 row">
                                            <div class="col-lg-6">
                                                <input v-model="venta.serie" type="text" class="form-control text-center" readonly="">
                                            </div>
                                            <div class="col-lg-6">
                                                <input v-model="venta.numero" type="text" class="form-control text-center" readonly="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group  mb-3">
                                        <label class="col-lg-4 control-label"> </label>
                                        <div class="col-lg-12">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group ">
                                                        <label class="control-label">Moneda</label>
                                                        <div class="col-lg-12">
                                                            <select v-model="venta.moneda" class="form-control">
                                                                <option value="1">SOLES</option>
                                                                <option value="2">DOLARES</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group ">
                                                        <label class="control-label">Tasa de cambio</label>
                                                        <div class="col-lg-12">
                                                            <input  v-model="venta.tc" type="text"  >
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group  mb-3">
                                        <label class="col-lg-4 control-label">Fecha</label>
                                        <div class="col-lg-12">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group ">
                                                        <label class="control-label">Emision</label>
                                                        <div class="col-lg-12">
                                                            <input v-model="venta.fecha" type="date" placeholder="dd/mm/aaaa" name="input_fecha" class="form-control text-center" value="2021-10-16">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group ">
                                                        <label class="control-label">Vencimiento</label>
                                                        <div class="col-lg-12">
                                                            <input disabled v-model="venta.fechaVen" type="date" placeholder="dd/mm/aaaa" name="input_fecha" class="form-control text-center" value="2021-10-16">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- CRÉDITO: cuotas de pago (mismo modal que la venta nueva y la conversión de pedidos) -->
                                    <div v-if="venta.tipo_pago == '2'" class="form-group mb-3">
                                        <label class="control-label">Cuotas de pago</label>
                                        <div class="d-grid">
                                            <button type="button" class="btn btn-primary" @click="abrirModalCuotas"><i class="fa fa-list"></i> Cuotas de pago <span class="badge bg-light text-dark">{{ venta.dias_lista.length }}</span></button>
                                        </div>
                                        <small class="text-muted">Falta pagar: <strong class="text-danger">{{ monedaSibol }} {{ formatoDecimal(faltaPagarCuotas) }}</strong></small>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-lg-4 control-label">Cliente</label>
                                    </div>

                                    <div class="form-group mb-3">
                                        <div class="col-lg-12">
                                            <div class="input-group">

                                                <input id="input_datos_cliente" v-model="venta.num_doc" type="text" placeholder="Ingrese Documento" class="form-control" maxlength="11">
                                                <div class="input-group-prepend">
                                                    <button @click="buscarDocumentSS" class="btn btn-primary" type="button"><i class="fa fa-search"></i></button>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="form-group  mb-3">
                                        <div class="col-lg-12">
                                            <input v-model="venta.nom_cli" type="text" placeholder="Nombre del cliente" class="form-control ui-autocomplete-input" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="form-group  mb-3">
                                        <div class="col-lg-12">
                                            <div class="input-group">
                                                <input v-model="venta.dir_cli" type="text" placeholder="Direccion 1" class="form-control ui-autocomplete-input" autocomplete="off">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="basic-addon1">
                                                        <input v-model="venta.dir_pos" name="dirserl" value="1" type="radio" class="form-check-input">
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group  mb-3">
                                        <div class="col-lg-12">
                                            <div class="input-group">
                                                <input v-model="venta.dir2_cli" type="text" placeholder="Direccion 2" class="form-control ui-autocomplete-input" autocomplete="off">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="basic-addon1">
                                                        <input :disabled="!isDirreccionCont" v-model="venta.dir_pos" name="dirserl" value="2" type="radio" class="form-check-input">
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group  mb-3">
                                        <div class="col-lg-12">
                                            <label>Observaciones</label>
                                            <div class="input-group">

                                                <input v-model="venta.observ" type="text" placeholder="" class="form-control ui-autocomplete-input" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>


                                    <div class="form-group  mb-3">
                                        <div class="col-lg-12">
                                            <button @click="guardarVenta" type="button" class="btn btn-lg btn-primary" id="btn_finalizar_pedido">
                                                <i class="fa fa-save"></i> Guardar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="bg-primary pv-15 text-center  p-3" style="height: 90px; color: white">
                                <h1 class="mv-0 font-400" id="lbl_suma_pedido">S/ {{totalProdustos}}</h1>
                                <div class="text-uppercase">Suma Pedido</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>


    <!-- Modal: cuotas de pago de la venta a crédito (mismo diseño que Cuentas por Cobrar de Ventas) -->
    <div class="modal fade" id="modal-cuotas-venta" tabindex="-1" aria-labelledby="modalCuotasVentaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-fullscreen-md-down modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalCuotasVentaLabel">Cuotas de pago</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4>Cliente: {{ venta.nom_cli }}</h4>
                    <!-- Card informativo -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <div class="card border-primary h-100 mb-0">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Total</div>
                                    <h5 class="mb-0">{{ monedaSibol }} {{ formatoDecimal(venta.total) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-success h-100 mb-0">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Total pagado</div>
                                    <h5 class="mb-0 text-success">{{ monedaSibol }} {{ formatoDecimal(totalPagadoCuotas) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-danger h-100 mb-0">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Falta pagar</div>
                                    <h5 class="mb-0 text-danger">{{ monedaSibol }} {{ formatoDecimal(faltaPagarCuotas) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- tabla-cuotas: en móvil cada cuota se apila como tarjeta (ver CSS al inicio de la vista) -->
                    <div class="col-xs-12 col-sm-12 col-md-12 no-padding table-responsive">
                        <table class="table table-bordered dt-responsive nowrap text-center table-sm tabla-cuotas" style="border-collapse: collapse; border-spacing: 0; width: 100%; min-width: 620px;">
                            <thead>
                                <tr>
                                    <th style="text-align: center;">Id</th>
                                    <th style="text-align: center;">Monto</th>
                                    <th style="text-align: center;">F. Pago</th>
                                    <th style="text-align: center;">Estado</th>
                                    <th style="text-align: center;">Pago</th>
                                    <th style="text-align: center;">Pagar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in venta.dias_lista" :key="index">
                                    <td data-label="Cuota">{{ index + 1 }}</td>
                                    <td data-label="Monto"><input type="number" step="0.01" min="0.01" style="width: 110px;" v-model="item.monto" @keypress="onlyNumber" @change="validarMontoCuota(item)" :disabled="item.estado == '1'"></td>
                                    <td data-label="F. Pago"><input type="date" v-model="item.fecha" :disabled="item.estado == '1'"></td>
                                    <td data-label="Estado"><div class="btn-group"><span class="badge" :class="claseEstadoCuota(item)">{{ textoEstadoCuota(item) }}</span></div></td>
                                    <td data-label="Pago">
                                        <select v-model="item.metodo_nombre" :disabled="item.estado == '1'">
                                            <option disabled value="">Elija Uno</option>
                                            <option v-for="mp in metodosPagoCxC" :value="mp" :key="mp">{{ mp }}</option>
                                        </select>
                                    </td>
                                    <td data-label="Pagar">
                                        <div class="btn-group">
                                            <button v-if="item.estado != '1'" type="button" class="btn btn-success btn-sm" title="Pagar" @click="pagarCuotaVenta(item)"><i class="fa fa-money-bill"></i></button>
                                            <button v-if="item.estado == '1' && !item.cuotaPagadaOrigen" type="button" class="btn btn-warning btn-sm" title="Deshacer pago" @click="item.estado = '0'"><i class="fa fa-undo"></i></button>
                                            <button v-if="item.estado != '1'" type="button" class="btn btn-danger btn-sm" title="Quitar cuota" @click="quitardiaspago(index)"><i class="fa fa-times"></i></button>
                                            <span v-if="item.cuotaPagadaOrigen" class="btn btn-light btn-sm disabled" title="Cobro ya registrado: para anularlo use Cuentas por Cobrar"><i class="fa fa-lock"></i></span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="venta.dias_lista.length == 0"><td colspan="6">Ningún dato disponible en esta tabla</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="sumardiaspago"><i class="fas fa-plus"></i> Agregar Pago</button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>



</div>



<script>
    /* let igv_apli=parseFloat("<?= $igv_empresa ?>"); */
    /* function modalFunsns(link, linkd, nameFile, num, email) {
        const html = `
        <div class="row text-start">
            <div class="col-md-12">
                <form id="from-sen-email" >
                <div class="form-group">
                    <label>Enviar Por Email</label>
                    <div class="input-group mb-3">
                        <input type="hidden" name="nombrefile" value="${nameFile}">
                        <input type="hidden" name="link" value="${linkd}">
                      <input value="${email}" required name="email" type="email" class="form-control" placeholder="ejemplo@gmail.com" >
                      <div class="input-group-prepend">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-send"></i> Enviar</button>
                      </div>
                    </div>
                </div>
                </form>

                <form id="from-sen-whatsapp" >

                <div class="form-group">
                    <label>Enviar a Whatsapp</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">+51 </span>
                         </div>
                      <input require name="num" value="${num}" type="text" class="form-control" placeholder="00000" >
                        <input type="hidden" name="link" value="${link}">
                      <div class="input-group-prepend">
                        <button class="btn btn-primary"><i class="fa fa-send"></i> Enviar</button>
                      </div>
                    </div>
                </div>
             </form>
            </div>
        </div>`;
        Swal.fire({
            title: "Enviar Factura",
            html,
            didOpen: () => {
                //Swal.showLoading()
                const formSendEmail = Swal.getHtmlContainer().querySelector('#from-sen-email');
                formSendEmail.addEventListener("submit", function(evt) {
                    evt.preventDefault();
                    $("#loader-menor").show();
                    _post("/ajs/send/comprobante/email", $(this).serialize(),
                        function(resp) {
                            console.log(resp);
                            if (resp.res) {
                                alertExito("Enviado")
                            } else {
                                alertAdvertencia("No se pudo Enviar")
                            }
                        });
                });
                const formSendWatsapp = Swal.getHtmlContainer().querySelector('#from-sen-whatsapp');
                formSendWatsapp.addEventListener("submit", function(evt) {
                    evt.preventDefault();
                    const numero = $(this).find("input[name='num']").val();
                    const linkVen = $(this).find("input[name='link']").val();

                    var link = "https://api.whatsapp.com/send?phone=";
                    const cod_ = 51;
                    const number_ = numero;
                    const mensaje = linkVen;
                    if (number_.length > 0) {
                        link += cod_ + number_
                        if (mensaje.length > 0) {
                            link += "&text=" + encodeURIComponent(mensaje)
                        }
                    }
                    window.open(link);
                    //console.log($(this).find("input[name='num']"))
                });
                console.log(formSendEmail);
            },
        })
        setTimeout(function() {}, 100)
    } */
    $(document).ready(function() {
        const app = new Vue({
            el: "#container-vue",
            data: {
                listaMedida:[{cod:1,nom:'Unidad'},{cod:2,nom:'Caja'},{cod:3,nom:'Bolsa'},{cod:4,nom:'Saco'},],
                apli_igv_is: true,
                listaMedidasCnt:[],
                idVenta: '',
                metodosPago:[],
                producto: {
                    presentacionTmepPO:[],
                    productoid: "",
                    descripcion: "",
                    nom_prod: "",
                    cantidad: "",
                    medida: "",
                    stock: "",
                    codigo: "",
                    costo: "",
                    codsunat: "",
                    precio: "",
                    precio: '',
                    almacen: '<?php echo $_SESSION["sucursal"] ?>',
                    precio2: '',
                    precio3: '',
                    precio4: '',
                    precio_unidad: '',
                    precioVenta: '',
                    precio_usado: 1,
                    presentacion:'1',
                    presentacionCnt:'1',
                },
                usar_precio: '1', // nivel "Precio", igual que en el pedido y en la venta nueva
                metodosPagoCxC: ["Efectivo", "Plin", "Yape", "BCP", "BBVA"], // mismas opciones que Cuentas por Cobrar
                productos: [],
                precioProductos: [],
                venta: {
                    observ: '',
                    moneda:'',
                    tc:'',
                    metodo:'',
                    apli_igv: 1,
                    dir_pos: 1,
                    tipo_doc: '1',
                    serie: '',
                    numero: '',
                    tipo_pago: '1',
                    dias_pago: '',
                    fecha: $("#fecha-app").val(),
                    fechaVen: $("#fecha-app").val(),
                    sendwp: false,
                    numwp: "",
                    num_doc: "",
                    nom_cli: "",
                    dir_cli: "",
                    dir2_cli: "",
                    tipoventa: 1,
                    total: 0,
                    dias_lista: [],

                }
            },
            watch: {
                // Cada vez que cambia el total (cantidad editada, producto agregado/quitado, recojo)
                // las cuotas pendientes se reajustan para seguir cuadrando con el total
                'venta.total'() {
                    if (this.venta.dias_lista.length > 0) {
                        this.sincronizarCuotasConTotal();
                    }
                },
                /* 'venta.dias_pago'(newValue) {
                    const listD = (newValue + "").split(",");
                    this.dias_lista = [];
                    if (listD.length > 0) {

                        var listaTemp = listD.filter(ite => ite.length > 0)
                        const palorInicial = (parseFloat(this.venta.total + "") / listaTemp.length).toFixed(0)
                        var totalValos = parseFloat(this.venta.total + "");
                        listaTemp = listaTemp.map((num, index) => {
                            var fecha_ = new Date(this.venta.fecha)
                            const dias_ = parseInt(num + "")
                            fecha_.setDate(fecha_.getDate() + dias_);
                            var value = 0;
                            if (index + 1 == listaTemp.length) {
                                value = totalValos;
                                this.venta.fechaVen = this.formatDate(fecha_)
                            } else {
                                value = palorInicial;
                                totalValos -= palorInicial;
                            }
                            return {
                                fecha: this.formatDate(fecha_),
                                monto: value
                            }
                        });
                        //console.log(palorInicial+"<<<<<<<<<<<<<")
                        this.venta.dias_lista = listaTemp
                        //console.log(listaTemp);
                    }

                } */
            },
            methods: {
                nombreMedida(cod){
                    return this.listaMedida.find(item => item.cod==cod)?.nom
                },
                formatoDecimal(num, desc = 2) {
                    return parseFloat(num + "").toFixed(desc);
                },
                loadProductos() {
                    /*    var pathArray = */
                    /*      console.log(pathArray[3]); */
                    let idVenta = $('#idVentaUrl').val();
                    console.log('asdmakdas');
                    console.log(idVenta);
                    /*  idVenta = idVenta[3]  */
                    this.idVenta = idVenta
                    var self = this
                    _ajax("/ajs/cargar/venta/productos", "POST", {
                            idVenta
                        },
                        function(resp) {
                            console.log("zzzzzzzzzz:",resp)
                            $("#loader-menor").hide()
                            resp=resp.map(err=>{
                                err.edicion = false
                                return err
                            });
                            self.productos.push(resp)
                            self.productos = self.productos[0]
                        }
                    )
                    /*  return */
                    _ajax("/ajs/cargar/venta/info", "POST", {
                            idVenta
                        },
                        function(resp) {
                            $("#loader-menor").hide()
                            console.log("aaaaaaa:",resp[0]);
                            let dataVenta = resp[0]
                            self.venta.tc = dataVenta.cm_tc
                            self.venta.moneda = dataVenta.moneda
                            self.venta.metodo = dataVenta.medoto_pago_id
                            self.venta.apli_igv = dataVenta.apli_igv
                            self.venta.tipo_doc = dataVenta.id_tido
                            self.venta.tipo_pago = dataVenta.id_tipo_pago
                            self.venta.fecha = dataVenta.fecha_emision
                            self.venta.fechaVen = dataVenta.fecha_vencimiento
                            self.venta.serie = dataVenta.serie
                            self.venta.numero = dataVenta.numero
                            self.venta.id_cliente = dataVenta.id_cliente
                            self.venta.observ = dataVenta.observacion
                            self.venta.dir_cli = dataVenta.direccion

                            self.venta.dias_pago = dataVenta.dias_pagos
                            setTimeout(function() {
                                // Las cuotas ya cobradas llegan con estado 1 y se muestran bloqueadas
                                self.venta.dias_lista = (dataVenta.cuotas || []).map(function(c) {
                                    return {
                                        cuotaid: c.cuotaid,
                                        fecha: c.fecha,
                                        monto: parseFloat(c.monto || 0).toFixed(2),
                                        estado: (c.estado == '1') ? '1' : '0',
                                        metodo: 12,
                                        metodo_nombre: self.metodoCxCDesdeNombre(c.tipo_pago),
                                        cuotaPagadaOrigen: (c.estado == '1')
                                    };
                                });
                            }, 1000)

                            _ajax("/ajs/clientes/getOne", "POST", {
                                    id: self.venta.id_cliente
                                },
                                function(resp) {
                                    $("#loader-menor").hide()
                                    console.log(resp);
                                    let dataCliente = resp[0]
                                    self.venta.num_doc = dataCliente.documento
                                    self.venta.nom_cli = dataCliente.datos
                                    /*    let dataVenta = resp[0]
                                       self.venta.apli_igv = dataVenta.apli_igv
                                       self.venta.tipo_doc = dataVenta.id_tido
                                       self.venta.tipo_pago = dataVenta.id_tipo_pago
                                       self.venta.fecha = dataVenta.fecha_emision
                                       self.venta.fechaVen = dataVenta.fecha_vencimiento
                                       self.venta.serie = dataVenta.serie
                                       self.venta.numero = dataVenta.numero
                                       self.venta.id_cliente = dataVenta.id_cliente
                                       console.log(); */
                                    /*  self.productos.push(resp)
                                     self.productos = self.productos[0] */
                                }
                            )
                            /*  self.productos.push(resp)
                             self.productos = self.productos[0] */
                        }
                    )



                    console.log('servicios cargados');

                },
                buscarPorCodigoBarra() {

                },
                cargarCotizacion() {
                    const vue = this;
                    _post("/ajs/cotizaciones/info", {
                            coti: $("#cotizacion").val()
                        },
                        function(resp) {
                            console.log(resp);
                            vue.productos = resp.productos
                            vue.venta.fecha = resp.fecha
                            vue.venta.cotiId = resp.cotizacion_id
                            vue.venta.tipo_doc = resp.id_tido
                            vue.venta.tipo_pago = resp.id_tipo_pago
                            vue.venta.dias_pago = resp.dias_pagos
                            vue.venta.dir_pos = parseInt(resp.direccion + "")
                            vue.venta.num_doc = resp.cliente_doc
                            vue.venta.nom_cli = resp.cliente_nom
                            vue.venta.dir_cli = resp.cliente_dir1
                            vue.venta.dir2_cli = resp.cliente_dir2

                            setTimeout(function() {
                                vue.venta.dias_lista = resp.cuotas
                            }, 1000)


                        }
                    )
                },
                formatoDecimal(num, desc = 2) {
                    return parseFloat(num + "").toFixed(desc);
                },
                visualFechaSee(fecha) {
                    return formatFechaVisual(fecha);
                },
                formatDate(date) {
                    console.log(date);
                    var d = date,
                        month = '' + (d.getMonth() + 1),
                        day = '' + (d.getDate() + 1),
                        year = d.getFullYear();

                    if (month.length < 2)
                        month = '0' + month;
                    if (day.length < 2)
                        day = '0' + day;

                    return [year, month, day].join('-');
                },
                onlyNumberComas($event) {
                    //console.log($event.keyCode); //keyCodes value
                    let keyCode = ($event.keyCode ? $event.keyCode : $event.which);
                    if ((keyCode < 48 || keyCode > 57) && keyCode !== 44) { // 46 is dot
                        $event.preventDefault();
                    }
                },
                changeTipoPago(event) {
                    console.log(event.target.value)
                    this.venta.fechaVen = this.venta.fecha;
                    this.venta.dias_lista = this.venta.dias_lista.filter(c => c.estado == '1')
                    this.venta.dias_pago = ''
                },
                onlyNumber($event) {
                    //console.log($event.keyCode); //keyCodes value
                    let keyCode = ($event.keyCode ? $event.keyCode : $event.which);
                    if ((keyCode < 48 || keyCode > 57) && keyCode !== 46) { // 46 is dot
                        $event.preventDefault();
                    }
                },
                precioUnitarioNum(item) {
                    // Precio por unidad (ej. por kilo) = precio de la presentación ÷ unidad derivada
                    let derivada = parseFloat(item.presenta_cnt ?? item.presentacionCnt ?? 1) || 1;
                    return Math.round((parseFloat(item.precioVenta || 0) / derivada) * 10000) / 10000;
                },
                setPrecioUnitario(item, valor) {
                    // Se escribe el precio por unidad; internamente se guarda el de la presentación
                    let v = parseFloat(valor);
                    if (isNaN(v)) return;
                    let derivada = parseFloat(item.presenta_cnt ?? item.presentacionCnt ?? 1) || 1;
                    item.precioVenta = Math.round(v * derivada * 10000) / 10000;
                },
                cantidadFinal(item) {
                    // Lo que realmente se entrega: cantidad × unidad derivada (ej. 2 × 3 = 6.00 Kilos)
                    let derivada = parseFloat(item.presenta_cnt ?? item.presentacionCnt ?? 1) || 1;
                    let total = parseFloat(item.cantidad || 0) * derivada;
                    return this.formatoDecimal(total) + ' ' + (item.medida || '');
                },
                cantidadFinalNum(item) {
                    // Cantidad final numérica, para el input de edición
                    let derivada = parseFloat(item.presenta_cnt ?? item.presentacionCnt ?? 1) || 1;
                    return Math.round(parseFloat(item.cantidad || 0) * derivada * 100) / 100;
                },
                cambiarPrecio(event) {
                    // Reaplica el nivel de precio elegido a todo el detalle (igual que en la venta nueva)
                    this.productos.forEach(element => {
                        if (event.target.value == 1) {
                            element.precioVenta = element.precio
                            element.precio_usado = '1'
                        } else if (event.target.value == 2) {
                            element.precioVenta = element.precio2
                            element.precio_usado = '2'
                        } else if (event.target.value == 3) {
                            element.precioVenta = element.precio3
                            element.precio_usado = '3'
                        } else if (event.target.value == 4) {
                            element.precioVenta = element.precio4
                            element.precio_usado = '4'
                        } else {
                            element.precioVenta = element.precio_unidad
                            element.precio_usado = '5'
                        }
                    });
                },
                setCantidadFinal(item, valor) {
                    // El usuario escribe la cantidad FINAL (ej. 5.9 kilos); internamente se guarda
                    // cantidad = final / unidad derivada con 6 decimales, así el total sale exacto.
                    let v = parseFloat(valor);
                    if (isNaN(v) || v === 0) {
                        alertAdvertencia("Ingrese una cantidad distinta de 0");
                        return;
                    }
                    let derivada = parseFloat(item.presenta_cnt ?? item.presentacionCnt ?? 1) || 1;
                    let cantidad = Math.round((v / derivada) * 1000000) / 1000000;
                    item.cantidad = cantidad;
                    let finalReal = Math.round(cantidad * derivada * 100) / 100;
                    if (Math.abs(finalReal - v) > 0.005) {
                        alertAdvertencia("Con la presentación de este producto (x" + derivada + ") la cantidad se ajustó a " + this.formatoDecimal(finalReal) + " " + (item.medida || ''));
                    }
                },
                onlyNumberNeg($event) {
                    // Como onlyNumber, pero admite el signo "-" al inicio. Cantidad NEGATIVA = RECOJO
                    // (producto que el cliente devuelve): regresa al stock y entra al kardex como 'Recojo'.
                    let keyCode = ($event.keyCode ? $event.keyCode : $event.which);
                    let val = ($event.target.value || '') + '';
                    if (keyCode === 45 && $event.target.selectionStart === 0 && !val.includes('-')) return;
                    if ((keyCode < 48 || keyCode > 57) && keyCode !== 46) {
                        $event.preventDefault();
                    }
                },
                eliminarItemPro(index) {
                    this.productos.splice(index, 1)
                },
                buscarDocumentSS() {
                    if (this.venta.num_doc.length == 8 || this.venta.num_doc.length == 11) {
                        $("#loader-menor").show()
                        this.venta.dir_pos = 1
                        _ajax("/ajs/consulta/doc/cliente", "POST", {
                                doc: this.venta.num_doc
                            },
                            function(resp) {
                                $("#loader-menor").hide()
                                console.log(resp);
                                if (resp.res) {
                                    app._data.venta.nom_cli = (resp.data.nombre ? resp.data.nombre : '') + (resp.data.razon_social ? resp.data.razon_social : '')
                                    app._data.venta.dir_cli = resp.data.direccion.trim().length > 0 ? resp.data.direccion : '-'
                                } else {
                                    alertAdvertencia("Documento no enocntrado")
                                }
                            }
                        )
                    } else {
                        alertAdvertencia("Documento, DNI es 8 digitos y RUC 11 digitos")
                    }
                },
                guardarVenta() {
                    if (this.productos.length > 0) {

                        var continuar = true;
                        var mensaje = '';



                        if (this.venta.tipo_doc == '1') {
                            if (this.venta.num_doc.length == 11) {
                                continuar = false;
                                mensaje = 'No puede emitir Boleta usando RUC';
                            }
                            if (this.venta.tipo_pago == 2) {
                                if (this.venta.dias_lista.length == 0) {
                                    continuar = false;
                                    mensaje = 'Debe especificar los días de pagos para un venta a crédito';
                                }
                            }
                        } else if (this.venta.tipo_doc == '2') {
                            if (this.venta.nom_cli.length < 5) {
                                mensaje = 'Debe escribir la Razón Social o dar al botón para buscar el ruc';
                                continuar = false;
                            }
                            if (this.venta.num_doc.length != 11) {
                                mensaje = 'Solo se puede emitir Factura usando RUC';
                                continuar = false;
                            }

                            if (this.venta.tipo_pago == 2) {
                                if (this.venta.dias_lista.length == 0) {
                                    continuar = false;
                                    mensaje = 'Debe especificar los días de pagos para un venta a crédito';
                                }
                            }


                        }

                        // Venta a crédito: las cuotas deben ser > 0 y sumar el total (mismas reglas que al convertir un pedido)
                        if (continuar && this.venta.tipo_pago == '2') {
                            this.sincronizarCuotasConTotal();
                            let totalCuotas = 0;
                            this.venta.dias_lista.forEach(el => { totalCuotas += parseFloat(el.monto || 0); });
                            if (this.venta.dias_lista.length == 0) {
                                continuar = false;
                                mensaje = 'Una venta a crédito necesita al menos una cuota en "Cuotas de pago".';
                            } else if (this.venta.dias_lista.some(c => !(parseFloat(c.monto) > 0))) {
                                continuar = false;
                                mensaje = 'Hay cuotas con monto 0 o negativo. Corrija o quite esas cuotas en "Cuotas de pago".';
                            } else if (Math.abs(totalCuotas - this.venta.total) > 0.01) {
                                continuar = false;
                                mensaje = (this.totalPagadoCuotas > parseFloat(this.venta.total) + 0.01)
                                    ? 'Lo ya cobrado (' + this.totalPagadoCuotas.toFixed(2) + ') supera el total de la venta (' + parseFloat(this.venta.total).toFixed(2) + '). Revise esos cobros en Cuentas por Cobrar.'
                                    : 'El total de las cuotas (' + totalCuotas.toFixed(2) + ') debe ser igual al total de la venta (' + parseFloat(this.venta.total).toFixed(2) + ')';
                            }
                        }

                        if (continuar) {
                            if (this.venta.total > 0) {
                                const data = {
                                    ...this.venta,
                                    listaPro: JSON.stringify(this.productos)
                                }
                                data.idVenta = this.idVenta
                                data.dias_lista = JSON.stringify(data.dias_lista)
                                console.log(data);
                                $("#loader-menor").show();
                                _ajax("/ajs/ventas/productos/edit", "POST",
                                    data,
                                    function(resp) {
                                        console.log(resp);
                                        if (resp.res) {
                                            alertExito("Exito", "Venta Guardada")
                                                .then(function() {
                                                    $("#backbuttonvp").click();
                                                    /* modalFunsns(resp.urlFact, resp.urlFactd,
                                                        resp.nomFact, resp.cel, resp.email) */

                                                })

                                        } else {
                                            alertAdvertencia("No se pudo Guardar la Venta")
                                        }
                                    }
                                )
                            } else {
                                alertAdvertencia('El monto debe ser mayor a 0')
                            }


                        } else {
                            alertAdvertencia(mensaje)
                        }
                    } else {
                        alertAdvertencia("No hay productos agregados a la lista ")
                    }

                },
                buscarSNdoc() {
                    _ajax("/ajs/consulta/sn", "POST", {
                            doc: this.venta.tipo_doc
                        },
                        function(resp) {
                            app.venta.serie = resp.serie
                            app.venta.numero = resp.numero
                        }
                    )
                },
                onChangeTiDoc(event) {
                    this.buscarSNdoc();
                    if (this.venta.tipo_doc == 6) {
                        this.apli_igv_is = false
                        this.venta.apli_igv = 1
                    } else {
                        this.apli_igv_is = true;
                    }
                },
                limpiasDatos() {
                    this.listaMedidasCnt=[]
                    this.producto = {
                        presentacionTmepPO:[],
                        productoid: "",
                        descripcion: "",
                        nom_prod: "",
                        cantidad: "",
                        medida: "",
                        stock: "",
                        codigo: "",
                        costo: "",
                        codsunat: "",
                        precio: "",
                        precio: '',
                        almacen: '<?php echo $_SESSION["sucursal"] ?>',
                        precio2: '',
                        precio3: '',
                        precio4: '',
                        precio_unidad: '',
                        precioVenta: '',
                        precio_usado: 1,
                        presentacion:'1',
                        presentacionCnt:'1',
                    }
                },
                addProduct() {
                    //if (this.producto.stock)

                    if (this.producto.descripcion.length > 0) {
                        const prod = {
                            ...this.producto
                        }
                        // Precio por presentación. NO se multiplica por la cantidad: el signo vive
                        // solo en la cantidad (negativa = RECOJO) y el parcial es precio × cantidad.
                        prod.precioVenta = prod.precioVenta * prod.presentacionCnt

                        this.productos.push(prod)
                        this.limpiasDatos();
                        this.usar_precio = 1
                    } else {
                        alertAdvertencia("Busque un producto primero")
                            .then(function() {
                                setTimeout(function() {
                                    $("#input_buscar_productos").focus();
                                }, 500)
                            })
                    }

                },
                sumardiaspago() {
                    // Agrega una cuota pendiente por lo que falte cubrir del total (pagadas y pendientes)
                    let restante = Math.round(this.montoSinCubrirCuotas(null) * 100) / 100;
                    if (restante <= 0) {
                        alertAdvertencia("Las cuotas ya cubren el total de la venta (" + this.monedaSibol + " " + this.formatoDecimal(this.venta.total) + ")");
                        return;
                    }
                    this.venta.dias_lista.push({
                        fecha: this.hoyISO(),
                        monto: restante.toFixed(2),
                        metodo: 12, metodo_nombre: '',
                        estado: '0'
                    });
                },
                hoyISO() {
                    const d = new Date();
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                },
                abrirModalCuotas() {
                    this.sincronizarCuotasConTotal();
                    $("#modal-cuotas-venta").modal("show");
                },
                sincronizarCuotasConTotal() {
                    // Mantiene las cuotas PENDIENTES cuadradas con el total de la venta. Las cobradas no se
                    // tocan. Si falta dinero se suma a la ultima pendiente (o se crea una); si sobra, se
                    // descuenta de las pendientes empezando por la ultima.
                    if (this.venta.tipo_pago != '2') return;
                    let total = parseFloat(this.venta.total || 0);
                    let suma = 0;
                    this.venta.dias_lista.forEach(c => { suma += parseFloat(c.monto || 0); });
                    let dif = Math.round((total - suma) * 100) / 100;
                    if (Math.abs(dif) <= 0.01) return;
                    let pendientes = this.venta.dias_lista.filter(c => c.estado != '1');
                    if (pendientes.length === 0) {
                        if (dif > 0) {
                            this.venta.dias_lista.push({ fecha: this.venta.fecha || this.hoyISO(), monto: dif.toFixed(2), metodo: 12, metodo_nombre: '', estado: '0' });
                        }
                        return; // si sobra y todo esta cobrado no hay nada que ajustar (se avisa al guardar)
                    }
                    for (let i = pendientes.length - 1; i >= 0 && Math.abs(dif) > 0.005; i--) {
                        let c = pendientes[i];
                        let nuevo = Math.round((parseFloat(c.monto || 0) + dif) * 100) / 100;
                        if (nuevo >= 0) {
                            c.monto = nuevo.toFixed(2);
                            dif = 0;
                        } else {
                            c.monto = '0.00';
                            dif = nuevo;
                        }
                    }
                    this.venta.dias_lista = this.venta.dias_lista.filter(c => c.estado == '1' || parseFloat(c.monto || 0) > 0);
                },
                montoSinCubrirCuotas(excluir) {
                    let suma = 0;
                    this.venta.dias_lista.forEach(c => { if (c !== excluir) suma += parseFloat(c.monto || 0); });
                    return parseFloat(this.venta.total || 0) - suma;
                },
                validarMontoCuota(item) {
                    // No se permite 0 ni negativo, ni que la suma de cuotas supere el total de la venta
                    let monto = parseFloat(item.monto);
                    if (!(monto > 0)) {
                        item.monto = '';
                        alertAdvertencia("El monto de la cuota debe ser mayor a 0");
                        return false;
                    }
                    let maximo = Math.round(this.montoSinCubrirCuotas(item) * 100) / 100;
                    if (monto > maximo + 0.001) {
                        item.monto = (maximo > 0 ? maximo : 0).toFixed(2);
                        alertAdvertencia("La suma de las cuotas no puede superar el total de la venta (" + this.monedaSibol + " " + this.formatoDecimal(this.venta.total) + "). Máximo para esta cuota: " + this.monedaSibol + " " + this.formatoDecimal(maximo > 0 ? maximo : 0));
                        return false;
                    }
                    return true;
                },
                pagarCuotaVenta(item) {
                    // Mismo efecto que "Pagar" en Cuentas por Cobrar: la cuota queda PAGADA con su metodo
                    if (!(parseFloat(item.monto) > 0)) {
                        alertAdvertencia("Ingrese el monto de la cuota antes de marcarla como pagada");
                        return;
                    }
                    if (!this.validarMontoCuota(item)) {
                        return;
                    }
                    if (!item.metodo_nombre) {
                        alertAdvertencia("Elija el método de pago de la cuota");
                        return;
                    }
                    item.estado = '1';
                },
                metodoCxCDesdeNombre(nombreBD) {
                    let dbPago = (nombreBD || '').toUpperCase();
                    let encontrado = this.metodosPagoCxC.find(item => item.toUpperCase() === dbPago || dbPago.includes(item.toUpperCase()));
                    return encontrado || '';
                },
                textoEstadoCuota(item) {
                    if (item.estado == '1') return 'Pagado';
                    let hoy = this.hoyISO();
                    return (item.fecha && item.fecha < hoy) ? 'Vencido' : 'Vigente';
                },
                claseEstadoCuota(item) {
                    if (item.estado == '1') return 'bg-success';
                    let hoy = this.hoyISO();
                    return (item.fecha && item.fecha < hoy) ? 'bg-danger' : 'bg-primary';
                },
                quitardiaspago(index) {
                    this.venta.dias_lista.splice(index, 1);
                }
            },
            computed: {
                listaOpcionesPResen(){
                    const vue = this
                    if (this.producto.presentacionTmepPO.length > 0) {
                        return this.listaMedida.filter(item=>{
                            return vue.producto.presentacionTmepPO.find(item2=>item2 == item.cod)
                        })
                    }else{
                        return this.listaMedida
                    }
                },
                monedaSibol() {
                    return (this.venta.moneda == 1 ? 'S/' : '$')
                },
                totalPagadoCuotas() {
                    let t = 0;
                    this.venta.dias_lista.forEach(c => { if (c.estado == '1') t += parseFloat(c.monto || 0); });
                    return t;
                },
                faltaPagarCuotas() {
                    return parseFloat(this.venta.total || 0) - this.totalPagadoCuotas;
                },
                isDirreccionCont() {
                    return this.venta.dir2_cli.length > 0;
                },
                totalProdustos() {
                    var total = 0;
                    this.productos.forEach(function(prod) {
                        total += prod.precioVenta * prod.cantidad

                    })
                    this.venta.total = total;
                    return total.toFixed(2);
                }
            }
        });
        app.buscarSNdoc();
        app.loadProductos();

        _ajax("/ajs/consulta/metodo/pago", "POST", {

            },
            function(resp) {
                console.log(resp);
                app._data.metodosPago = resp
                /*     app.venta.serie = resp.serie
                    app.venta.numero = resp.numero */
            }
        )

        $("#input_datos_cliente").autocomplete({
            source: _URL + "/ajs/buscar/cliente/datos",
            minLength: 2,
            select: function(event, ui) {
                event.preventDefault();
                console.log(ui.item);
                app._data.venta.dir_pos = 1
                app._data.venta.nom_cli = ui.item.datos
                app._data.venta.num_doc = ui.item.documento
                app._data.venta.dir_cli = ui.item.direccion
                /*$('#input_datos_cliente').val(ui.item.datos);
                $('#input_documento_cliente').val(ui.item.documento);
                $('#input_datos_cliente').focus();*/
            }
        });
        $("#input_buscar_productos").autocomplete({
            /* source: _URL + "/ajs/cargar/productos", */
            source: _URL + `/ajs/cargar/productos/${app.producto.almacen}`,
            minLength: 1,
            select: function(event, ui) {
                event.preventDefault();
                /*    console.log(item);
                   console.log(ui); */
                console.log(ui.item);
                /*  return */
                app.listaMedidasCnt=[]
                if (ui.item.cnt_presenta!=null&&ui.item.cnt_presenta!=''){
                    app.listaMedidasCnt =ui.item.cnt_presenta.split(',')
                }
                app.producto.productoid = ui.item.codigo
                app.producto.descripcion = ui.item.codigo_pp + " | " + ui.item.descripcion
                app.producto.nom_prod = ui.item.descripcion
                app.producto.medida = ui.item.medida
                app.producto.cantidad = ''
                app.producto.stock = ui.item.cnt
                app.producto.presentacionTmepPO = ui.item.presentaciones?ui.item.presentaciones.split(","):[];
                app.producto.precio = ui.item.precio == null ? parseFloat(0 + "").toFixed(4) : ui.item.precio
                app.producto.precio2 = ui.item.precio2 == null ? parseFloat(0 + "").toFixed(4) : parseFloat(ui.item.precio2 + "").toFixed(4)
                app.producto.precio3 = ui.item.precio3 == null ? parseFloat(0 + "").toFixed(4) : parseFloat(ui.item.precio3 + "").toFixed(4)
                app.producto.precio4 = ui.item.precio4 == null ? parseFloat(0 + "").toFixed(4) : parseFloat(ui.item.precio4 + "").toFixed(4)
                app.producto.precio_unidad = ui.item.precio_unidad == null ? parseFloat(0 + "").toFixed(4) : ui.item.precio_unidad
                app.producto.precioVenta = ui.item.precio == null ? parseFloat(0 + "").toFixed(4) : ui.item.precio
                app.producto.codigo = ui.item.codigo
                app.producto.costo = ui.item.costo
                let array = [{
                        precio: app.producto.precio
                    },
                    {
                        precio: app.producto.precio2
                    },
                    {
                        precio: app.producto.precio3
                    },
                    {
                        precio: app.producto.precio4
                    },
                    {
                        precio: app.producto.precio_unidad
                    }
                ]

                app.precioProductos = array
                /*  app.precioProductos = array */
                console.log(array);
                $('#input_buscar_productos').val("");
                $("#example-text-input").focus()
            }
        });

        <?php
        if (isset($_GET["coti"])) {
            echo "app.cargarCotizacion()";
        }
        ?>
    })
</script>