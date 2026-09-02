<?php

require_once "app/models/Venta.php";
require_once "app/models/Cliente.php";
require_once "app/models/DocumentoEmpresa.php";
require_once "app/models/ProductoVenta.php";
require_once "app/models/VentaServicio.php";
require_once "app/models/Varios.php";
require_once "app/models/VentaSunat.php";
require_once "app/models/VentaAnulada.php";
require_once "app/models/GuiaRemision.php";
require_once "app/clases/SendURL.php";
require_once "app/clases/SunatApi.php";


class VentasController extends Controller
{
    private $venta;
    private $sunatApi;
    private $conexion;
    private $guia;
    public function __construct()
    {
        $this->venta = new Venta();
        $this->sunatApi = new SunatApi();
        $this->guia = new GuiaRemision();
        $this->conexion = (new Conexion())->getConexion();
    }


    public function ingresosEgresosRender()
    {
        $lista = [];
        $sql = "SELECT
                	ingreso_egreso.*,
                	productos.descripcion,
                	productos.codigo,
                	usuario 
                FROM
                	ingreso_egreso
                	JOIN productos ON ingreso_egreso.id_producto = productos.id_producto
                	INNER JOIN usuarios on usuarios.usuario_id = ingreso_egreso.id_usuario
                ORDER BY
                	intercambio_id ASC";
        $result = $this->conexion->query($sql);
        /*  foreach ($result as $res) {
            $lista[] = $res;
        }
 */
        return $result;
    }

    // agregando 10/04/2025
    public function getAllByProductosIdVenta($request)
    {
        try {
            $id = $request->id;

            $sql = "SELECT 
                    pc.id_producto, 
                    pc.cantidad, 
                    pc.presenta_cnt,
                    p.precio, 
                    (pc.cantidad * pc.presenta_cnt * p.precio) AS total, 
                    p.descripcion 
                FROM productos_cotis pc
                INNER JOIN productos p ON p.id_producto = pc.id_producto
                WHERE id_coti = '$id'";

            $result = $this->conexion->query($sql);
            return json_encode($result->fetch_all(MYSQLI_ASSOC));
        } catch (Exception $e) {
            echo $e->getTraceAsString();
        }
    }


    /*  public function  */
    public function ingresoAlmacen()
    {
        $respuesta['res'] = false;
        $sql = "INSERT INTO ingreso_egreso set id_producto = '{$_POST['productoid']}', tipo = '{$_POST['tipo']}',cantidad = '{$_POST['cantidad']}', id_usuario = '{$_SESSION['usuario_fac']}', almacen_ingreso = '{$_POST['almacen']}'";
        if ($this->conexion->query($sql)) {
            $sql = "update productos set cantidad=cantidad+'{$_POST['cantidad']}' where id_producto= '{$_POST['productoid']}'";
            $this->conexion->query($sql);
            $respuesta['res'] = true;
        }
        echo json_encode($respuesta);
    }
    public function egresoAlmacen()
    {
        $respuesta['res'] = false;
        $sql = "INSERT INTO ingreso_egreso set id_producto = '{$_POST['productoid']}', tipo = '{$_POST['tipo']}',cantidad = '{$_POST['cantidad']}', id_usuario = '{$_SESSION['usuario_fac']}', almacen_ingreso = '{$_POST['alAlmacen']}', almacen_egreso = '{$_POST['almacen']}', estado = 0";
        if ($this->conexion->query($sql)) {
            //$sql="select  * from productos where id_producto= '{$_POST['productoid']}'";
            //$result =  $this->conexion->query($sql)->fetch_assoc();
            //
            //$sql="update productos set cantidad=cantidad-'{$_POST['cantidad']}' where id_producto= '{$_POST['productoid']}'";
            //$this->conexion->query($sql);
            //$sql="update productos set cantidad=cantidad+'{$_POST['cantidad']}' where codigo= '{$result['codigo']}' and almacen='{$_POST['alAlmacen']}'";
            //$this->conexion->query($sql);
            $respuesta['res'] = true;
        }
        echo json_encode($respuesta);
    }
    public function envioComunicacionBajaPorEmpresa()
    {
        $listaBoletas = [];
        foreach (json_decode($_POST['boletas'], true) as  $bol) {
            $listaBoletas[] = "v.id_venta='$bol'";
        }

        $sql = "select v.id_venta, v.enviado_sunat,vs.nombre_xml from ventas v
        join ventas_sunat vs on v.id_venta = vs.id_venta
        where " . implode(" OR ", $listaBoletas);

        $listaPorEnviar = $this->venta->exeSQL($sql);

        foreach ($listaPorEnviar as $vpr) {
            if ($vpr['enviado_sunat'] == '0') {
                if ($this->sunatApi->envioIndividualDocumentoVPorEmpresa($vpr['nombre_xml'], $_POST['empresa'])) {
                    $sql = "update ventas set enviado_sunat='1' where id_venta='{$vpr['id_venta']}'";
                    $this->venta->exeSQL($sql);
                }
                sleep(2);
            }
        }
        $respuesta = [];
        $respuesta['msg_resumen'] = $this->sunatApi->comunicacionBajaPorEmpresa(
            $listaBoletas,
            $_POST['empresa'],
            $_POST['fecharesumen'],
            $_POST["fechagen"],
            $_POST['correlativo1']
        );

        return json_encode($respuesta);
    }

    public function envioResumenDiarioPorEmpresa()
    {
        $listaBoletas = [];
        foreach (json_decode($_POST['boletas'], true) as  $bol) {
            $listaBoletas[] = "v.id_venta='$bol'";
        }
        return json_encode([
            $this->sunatApi->resumenDiarioPorEmpresa(
                $listaBoletas,
                $_POST['empresa'],
                $_POST['fechagen'],
                $_POST['fecharesumen'],
                $_POST['correlativo1']
            ),
            $this->sunatApi->resumenDiarioBajaPorEmpresa(
                $listaBoletas,
                $_POST['empresa'],
                $_POST['fechagen'],
                $_POST['fecharesumen'],
                $_POST['correlativo2']
            )
        ]);
    }

    public function enviarDocumentoSunatPorEmpresa()
    {
        $sql = "select vs.*,v.id_empresa from ventas_sunat vs
        join ventas v on v.id_venta = vs.id_venta
        where vs.id_venta = '{$_POST["cod"]}'";
        $resultado = ["res" => false];
        if ($row = $this->venta->exeSQL($sql)->fetch_assoc()) {
            if ($this->sunatApi->envioIndividualDocumentoVPorEmpresa($row["nombre_xml"], $row['id_empresa'])) {
                $sql = "update ventas set  enviado_sunat='1'
                where id_venta = '{$_POST["cod"]}'";
                $this->venta->exeSQL($sql);
                $resultado['res'] = true;
            } else {
                $resultado['msg'] = $this->sunatApi->getMensaje();
            }
        }
        return json_encode($resultado);
    }

    public function regenerarXML()
    {
        $venta = $_POST["venta"];

        $sql = "SELECT * from ventas where id_venta='$venta'";
        $ventaData = $this->venta->exeSQL($sql)->fetch_assoc();
        $empresa = $this->venta->exeSQL("select * from empresas where id_empresa='{$ventaData['id_empresa']}'")->fetch_assoc();
        $cliente = $this->venta->exeSQL("select * from clientes where id_cliente='{$ventaData['id_cliente']}'")->fetch_assoc();


        $dataSend = [];
        $dataSend["certGlobal"] = false;

        $direccionselk = $cliente["direccion"];



        if (strlen(trim($direccionselk)) == "") {
            $direccionselk = '-';
        }
        if (trim($cliente["datos"]) == "") {
            $cliente["datos"] = '-';
        }

        $dataSend['cliente'] = json_encode([
            'doc_num' => $cliente["documento"],
            'nom_RS' => $cliente["datos"],
            'direccion' => $direccionselk
        ]);
        $dataSend['productos'] = [];
        $dataSend['apli_igv'] = $ventaData['apli_igv'] == 1;
        $dataSend['total'] = $ventaData["total"];
        $dataSend['serie'] = $ventaData["serie"];
        $dataSend['numero'] = $ventaData["numero"];
        $dataSend['fechaE'] = $ventaData["fecha_emision"];
        $dataSend['fechaV'] = $ventaData["fecha_vencimiento"];
        $dataSend['tipo_pago'] = $ventaData["id_tipo_pago"];
        $dataSend['igv_venta'] = $ventaData["igv"];
        $dataSend['dias_pagos'] = [];
        $dataSend['moneda'] = "PEN";

        $sql = "select * from dias_ventas where id_venta='$venta'";
        $cuotasVentas = $this->venta->exeSQL($sql);

        foreach ($cuotasVentas as $cuotas) {
            $dataSend['dias_pagos'][] = [
                "monto" => $cuotas['monto'],
                "fecha" => $cuotas['fecha']
            ];
        }

        $sql = "select pv.*,p.descripcion from productos_ventas pv
        join productos p on p.id_producto = pv.id_producto
        where pv.id_venta='$venta'";
        $listaProductos = $this->venta->exeSQL($sql);
        foreach ($listaProductos as $prod) {
            $dataSend['productos'][] = [
                "precio" => number_format($prod['precio'], 2, ".", ""),
                "cantidad" => number_format($prod['cantidad'], 0),
                "cod_pro" => $prod['id_producto'],
                "cod_sunat" => "",
                "descripcion" => $prod['descripcion']
            ];
        }

        $sql = "select * from ventas_servicios where  id_venta='$venta'";
        $listaProductos = $this->venta->exeSQL($sql);
        foreach ($listaProductos as $prod) {
            $dataSend['productos'][] = [
                "precio" => number_format($prod['monto'], 2, ".", ""),
                "cantidad" => number_format($prod['cantidad'], 0),
                "cod_pro" => $prod['id_item'],
                "cod_sunat" => $prod['codsunat'],
                "descripcion" => $prod['descripcion']
            ];
        }

        $dataSend["endpoints"] = $empresa['modo'];

        $dataSend['empresa'] = json_encode([
            'ruc' => $empresa['ruc'],
            'razon_social' => $empresa['razon_social'],
            'direccion' => $empresa['direccion'],
            'ubigeo' => $empresa['ubigeo'],
            'distrito' => $empresa['distrito'],
            'provincia' => $empresa['provincia'],
            'departamento' => $empresa['departamento'],
            'clave_sol' => $empresa['clave_sol'],
            'usuario_sol' => $empresa['user_sol']
        ]);
        $respuesta = ["res" => false];

        if ($ventaData['id_tido'] == 1 || $ventaData['id_tido'] == 2) {
            $dataSend['dias_pagos'] = json_encode($dataSend['dias_pagos']);

            $dataSend['productos'] = json_encode($dataSend['productos']);
            file_put_contents("Dataaaaaaaaaaaaaaaaaaaa.json", json_encode($dataSend));
            if ($ventaData['id_tido'] == 1) {
                $dataResp = $this->sunatApi->genBoletaXML($dataSend);
            } else {
                $dataResp = $this->sunatApi->genFacturaXML($dataSend);
            }
            if ($dataResp["res"]) {
                $respuesta["res"] = true;
                $sql = "select * from ventas_sunat where id_venta = '$venta'";
                if ($rrroooo = $this->venta->exeSQL($sql)->fetch_assoc()) {
                    $sql = "update ventas_sunat set hash='{$dataResp['data']['hash']}',
                      nombre_xml='{$dataResp['data']['nombre_archivo']}',
                      qr_data='{$dataResp['data']['qr']}' where id_venta = '$venta' ";
                    $this->venta->exeSQL($sql);
                } else {
                    $sql = "insert into ventas_sunat set hash='{$dataResp['data']['hash']}',
                      nombre_xml='{$dataResp['data']['nombre_archivo']}',
                      qr_data='{$dataResp['data']['qr']}',  id_venta = '$venta' ";
                    $this->venta->exeSQL($sql);
                }
            }
        }

        return json_encode($respuesta);
    }

    public function listaVentasPorEmpresa()
    {
        return json_encode($this->venta->verFilasPorEmpresas($_POST["empresa"], $_POST["sucursal"]));
    }


    public function enviarDocumentoSunat()
    {
        $sql = "select * from ventas_sunat where id_venta = '{$_POST["cod"]}'";
        $resultado = ["res" => false];
        if ($row = $this->venta->exeSQL($sql)->fetch_assoc()) {
            if ($this->sunatApi->envioIndividualDocumentoV($row["nombre_xml"])) {
                $sql = "update ventas set  enviado_sunat='1' where id_venta = '{$_POST["cod"]}'";
                $this->venta->exeSQL($sql);
                $resultado['res'] = true;
            } else {
                $resultado['msg'] = $this->sunatApi->getMensaje();
            }
        }
        return json_encode($resultado);
    }

    /**
     * Lista los pedidos de un rango de fechas para la venta masiva: se muestran todos y
     * cada uno indica si ya tiene una venta vigente (esos no se pueden volver a vender).
     */
    public function listarPedidosParaVender()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] == 3) {
            return json_encode(["res" => false, "msj" => "Su rol no permite convertir pedidos en venta"]);
        }
        $desde = isset($_POST['desde']) ? trim($_POST['desde']) : '';
        $hasta = isset($_POST['hasta']) ? trim($_POST['hasta']) : '';
        if ($desde === '' || $hasta === '') {
            return json_encode(["res" => false, "msj" => "Indique la fecha de inicio y la fecha fin"]);
        }
        $desdeEsc = $this->conexion->real_escape_string($desde);
        $hastaEsc = $this->conexion->real_escape_string($hasta);
        $idEmpresa = $_SESSION['id_empresa'];

        // Filtros opcionales sobre el cliente del pedido: dia de visita y vehiculo (camion).
        // El vehiculo no es un dato del cliente: se traduce a dia de visita + rutas, con el mismo
        // mapeo que usan el listado de pedidos y los reportes (ConsultaDelcontroller::filtrosReparto).
        $dia = isset($_POST['dia']) ? trim($_POST['dia']) : '';
        $vehiculo = isset($_POST['vehiculo']) ? trim($_POST['vehiculo']) : '';
        $mapaVehiculos = [
            '1' => ['lunes' => ['1','7'], 'martes' => ['5','7'], 'miercoles' => ['5'], 'jueves' => ['1','7'], 'viernes' => ['6','7'], 'sabado' => ['7','8']],
            '2' => ['lunes' => ['3','6'], 'martes' => ['1','3'], 'miercoles' => ['1','3'], 'jueves' => ['6','3'], 'viernes' => ['3','5'], 'sabado' => ['3','6']],
            '3' => ['miercoles' => ['6','7'], 'viernes' => ['8','2'], 'sabado' => ['1','5']],
        ];

        $condFiltro = '';
        if ($vehiculo !== '' && isset($mapaVehiculos[$vehiculo])) {
            $filtros = $mapaVehiculos[$vehiculo];
            if ($dia !== '') {
                $filtros = isset($filtros[$dia]) ? [$dia => $filtros[$dia]] : [];
            }
            $partes = [];
            foreach ($filtros as $diaMapa => $rutas) {
                $diaEsc = $this->conexion->real_escape_string($diaMapa);
                $rutasEsc = implode(',', array_map('intval', $rutas));
                $partes[] = "(LOWER(c.dias_visitas) = LOWER('$diaEsc') AND c.id_ruta IN ($rutasEsc))";
            }
            // Ese vehiculo no recorre el dia elegido: no debe listar nada
            $condFiltro = empty($partes) ? " AND 1 = 0" : " AND (" . implode(' OR ', $partes) . ")";
        } elseif ($dia !== '') {
            $diaEsc = $this->conexion->real_escape_string($dia);
            $condFiltro = " AND LOWER(c.dias_visitas) = LOWER('$diaEsc')";
        }

        $sql = "SELECT co.cotizacion_id, co.numero, DATE(COALESCE(co.fecha_registro, co.fecha)) AS fecha,
                    co.total,
                    CONCAT(IFNULL(c.documento,''), ' | ', IFNULL(c.datos,'SIN CLIENTE')) AS cliente,
                    IFNULL(u.usuario,'') AS vendedor,
                    IFNULL(c.dias_visitas,'') AS dias_visitas,
                    IFNULL(c.id_ruta,'') AS ruta,
                    (SELECT COUNT(*) FROM productos_cotis pc WHERE pc.id_coti = co.cotizacion_id) AS items,
                    (SELECT CONCAT(v.serie,'-',v.numero) FROM ventas v
                      WHERE v.id_coti = co.cotizacion_id AND v.estado = 1 LIMIT 1) AS venta
                FROM cotizaciones co
                LEFT JOIN clientes c ON c.id_cliente = co.id_cliente
                LEFT JOIN usuarios u ON u.usuario_id = co.id_usuario
                WHERE co.id_empresa = '$idEmpresa'
                  AND DATE(COALESCE(co.fecha_registro, co.fecha)) BETWEEN '$desdeEsc' AND '$hastaEsc'
                  AND co.estado <> 2
                  -- Solo los pendientes: los que ya tienen venta vigente no se listan
                  AND NOT EXISTS (SELECT 1 FROM ventas v2 WHERE v2.id_coti = co.cotizacion_id AND v2.estado = 1)
                  $condFiltro
                ORDER BY co.cotizacion_id ASC
                LIMIT 500";
        $rs = $this->conexion->query($sql);
        if (!$rs) {
            return json_encode(["res" => false, "msj" => "No se pudieron leer los pedidos: " . $this->conexion->error]);
        }
        return json_encode(["res" => true, "pedidos" => $rs->fetch_all(MYSQLI_ASSOC)], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Convierte en VENTA los pedidos de un rango de fechas.
     * Un pedido que YA tiene una venta vigente NO se vuelve a vender: se omite y se informa
     * en el resumen ("Ya tenían venta"), mientras el resto del rango se convierte igual.
     * Aplica las mismas reglas que la conversión individual: Nota de Venta sin IGV, correlativo
     * que avanza, descuento de stock + kardex, y las cuotas del pedido pasan a la venta
     * conservando su estado (las ya cobradas mantienen usuario, fecha y método originales).
     */
    public function convertirMasivo()
    {
        $resultado = ["res" => false, "convertidos" => 0, "omitidos" => [], "errores" => []];

        if (!isset($_SESSION['rol']) || $_SESSION['rol'] == 3) {
            $resultado['msj'] = 'Su rol no permite convertir pedidos en venta';
            return json_encode($resultado);
        }

        // Se convierten SOLO los pedidos marcados en la lista
        $idsRaw = isset($_POST['ids']) ? $_POST['ids'] : '';
        $ids = array_values(array_filter(array_map('intval', explode(',', $idsRaw))));
        if (count($ids) == 0) {
            $resultado['msj'] = 'Seleccione al menos un pedido';
            return json_encode($resultado);
        }
        if (count($ids) > 200) {
            $resultado['msj'] = 'Seleccione como máximo 200 pedidos por vez';
            return json_encode($resultado);
        }
        $idsSql = implode(',', $ids);
        $idEmpresa = $_SESSION['id_empresa'];
        $sucursal = $_SESSION['sucursal'];
        $idUsuario = isset($_SESSION['usuario_fac']) ? intval($_SESSION['usuario_fac']) : (isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0);

        // El proceso puede tardar: se libera el bloqueo de la sesión para que el resto del
        // sistema siga respondiendo mientras tanto (PHP bloquea la sesión por usuario y
        // dejaba "cargando" cualquier otra pantalla hasta terminar).
        if (function_exists('session_write_close')) {
            @session_write_close();
        }
        set_time_limit(0);

        // De los marcados, solo los que siguen SIN venta vigente y tienen productos
        $sqlPedidos = "SELECT co.* FROM cotizaciones co
            WHERE co.cotizacion_id IN ($idsSql)
              AND co.id_empresa = '$idEmpresa'
              AND co.estado <> 2
              AND NOT EXISTS (SELECT 1 FROM ventas v WHERE v.id_coti = co.cotizacion_id AND v.estado = 1)
              AND EXISTS (SELECT 1 FROM productos_cotis pc WHERE pc.id_coti = co.cotizacion_id)
            ORDER BY co.cotizacion_id ASC";
        // Los que se saltan por tener ya una venta vigente se calculan ANTES de convertir
        // (si se hiciera después, los recién convertidos aparecerían aquí por error)
        $rsOmit = $this->conexion->query("SELECT co.numero FROM cotizaciones co
            WHERE co.cotizacion_id IN ($idsSql)
              AND EXISTS (SELECT 1 FROM ventas v WHERE v.id_coti = co.cotizacion_id AND v.estado = 1)");
        if ($rsOmit) {
            foreach ($rsOmit as $o) {
                $resultado['omitidos'][] = $o['numero'];
            }
        }

        $rsPedidos = $this->conexion->query($sqlPedidos);
        if (!$rsPedidos) {
            $resultado['msj'] = 'No se pudieron leer los pedidos: ' . $this->conexion->error;
            return json_encode($resultado);
        }
        $pedidos = $rsPedidos->fetch_all(MYSQLI_ASSOC);
        if (count($pedidos) == 0) {
            $resultado['res'] = true;
            $resultado['msj'] = 'Ninguno de los pedidos seleccionados se pudo convertir (ya tienen venta o no tienen productos)';
            return json_encode($resultado);
        }

        $c_tido = new DocumentoEmpresa();
        $fechaAhora = date('Y-m-d H:i:s');

        foreach ($pedidos as $ped) {
            $idCoti = intval($ped['cotizacion_id']);
            try {
                // Correlativo del documento (Nota de Venta = 6)
                $c_tido->setIdEmpresa($idEmpresa);
                $c_tido->setIdTido(6);
                $c_tido->obtenerDatos();
                $serie = $this->conexion->real_escape_string($c_tido->getSerie());
                $numero = $this->conexion->real_escape_string($c_tido->getNumero());

                $fechaEmision = !empty($ped['fecha']) ? $ped['fecha'] : date('Y-m-d');
                $direccion = $this->conexion->real_escape_string($ped['direccion'] ?? '');
                $observacion = $this->conexion->real_escape_string($ped['observacion'] ?? '');
                $moneda = intval($ped['moneda']) > 0 ? intval($ped['moneda']) : 1;
                $tc = floatval($ped['cm_tc']) > 0 ? floatval($ped['cm_tc']) : 1;
                $tipoPago = intval($ped['id_tipo_pago']) > 0 ? intval($ped['id_tipo_pago']) : 2;
                $total = floatval($ped['total']);

                // Nota de Venta: sin IGV
                $sqlVenta = "INSERT INTO ventas SET
                    id_tido = 6, id_tipo_pago = '$tipoPago', fecha_emision = '$fechaEmision',
                    fecha_vencimiento = '$fechaEmision', dias_pagos = '', direccion = '$direccion',
                    serie = '$serie', numero = '$numero', id_cliente = '{$ped['id_cliente']}',
                    total = '$total', estado = '1', enviado_sunat = '0', id_empresa = '$idEmpresa',
                    sucursal = '$sucursal', apli_igv = '0', observacion = '$observacion', igv = '0',
                    moneda = '$moneda', cm_tc = '$tc', id_coti = '$idCoti', id_vendedor = '$idUsuario'";
                if (!$this->conexion->query($sqlVenta)) {
                    $resultado['errores'][] = "Pedido #{$ped['numero']}: " . $this->conexion->error;
                    continue;
                }
                $idVenta = $this->conexion->insert_id;
                $c_tido->incrementarNumero();

                // Detalle: descuenta stock y registra kardex (Venta / Recojo si es negativo)
                $c_detalle = new ProductoVenta();
                $c_detalle->setIdVenta($idVenta);
                $rsProd = $this->conexion->query("SELECT * FROM productos_cotis WHERE id_coti = $idCoti");
                foreach ($rsProd as $pr) {
                    $presentaCnt = ($pr['presenta_cnt'] == 0) ? 1 : $pr['presenta_cnt'];
                    $c_detalle->setIdProducto($pr['id_producto']);
                    $c_detalle->setCantidad($pr['cantidad']);
                    $c_detalle->setCosto($pr['costo']);
                    $c_detalle->setMedida($pr['medida']);
                    $c_detalle->setPresenta($pr['presenta']);
                    $c_detalle->setPresentaCnt($presentaCnt);
                    $c_detalle->setPrecio($pr['precio']);
                    $c_detalle->setPrecioUsado('1');
                    $c_detalle->insertar();
                }

                // Cuotas: las ya cobradas conservan usuario, fecha y método originales
                $rsCuotas = $this->conexion->query("SELECT * FROM cuotas_cotizacion WHERE id_coti = $idCoti");
                foreach ($rsCuotas as $cu) {
                    $monto = floatval($cu['monto']);
                    if ($monto <= 0) {
                        continue;
                    }
                    $pagada = ($cu['estado'] == '1' && !empty($cu['fecha_pago_real']));
                    $estadoCuota = $pagada ? '1' : '0';
                    $usuarioCuota = $pagada && !empty($cu['id_usuario']) ? intval($cu['id_usuario']) : $idUsuario;
                    $metodoCuota = $this->conexion->real_escape_string($cu['tipo_pago'] ?? '');
                    $fechaCuota = (empty($cu['fecha']) || $cu['fecha'] == '0000-00-00') ? $fechaEmision : $cu['fecha'];
                    $fechaRealSql = $pagada ? "'" . $this->conexion->real_escape_string($cu['fecha_pago_real']) . "'" : 'NULL';
                    $this->conexion->query("INSERT INTO dias_ventas SET id_venta = '$idVenta',
                        monto = '$monto', fecha = '$fechaCuota', estado = '$estadoCuota',
                        tipo_pago = '$metodoCuota', id_usuario = '$usuarioCuota',
                        fecha_pago_real = $fechaRealSql");
                }

                $this->conexion->query("UPDATE cotizaciones SET estado = 1 WHERE cotizacion_id = $idCoti");
                $resultado['convertidos']++;
            } catch (Throwable $e) {
                $resultado['errores'][] = "Pedido #{$ped['numero']}: " . $e->getMessage();
            }
        }

        $resultado['res'] = true;
        $resultado['msj'] = "Se convirtieron {$resultado['convertidos']} pedido(s) en venta.";
        return json_encode($resultado, JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function anularVenta()
    {
        $this->venta->setIdVenta($_POST['iventa']);
        $c_anulada = new VentaAnulada();
        $c_producto = new ProductoVenta();

        /*$c_producto->setIdVenta($this->venta->getIdVenta());
        $c_producto->eliminar();*/

        $c_anulada->setIdVenta($this->venta->getIdVenta());
        $c_anulada->setFecha(date("Y-m-d"));
        $c_anulada->setMotivo("-");
        $resultado = ["res" => false];
        if ($this->venta->anular()) {
            $resultado['res'] = true;
            $c_anulada->insertar();

            // El pedido de origen vuelve a quedar disponible para convertirse en venta,
            // salvo que tenga otra venta vigente (estado = 1)
            $idVentaAnulada = intval($this->venta->getIdVenta());
            $rsCoti = $this->conexion->query("SELECT id_coti FROM ventas WHERE id_venta = $idVentaAnulada");
            $filaCoti = $rsCoti ? $rsCoti->fetch_assoc() : null;
            $idCoti = ($filaCoti && !empty($filaCoti['id_coti'])) ? intval($filaCoti['id_coti']) : 0;
            if ($idCoti > 0) {
                $rsOtras = $this->conexion->query("SELECT COUNT(*) AS c FROM ventas WHERE id_coti = $idCoti AND estado = 1");
                $otras = $rsOtras ? intval($rsOtras->fetch_assoc()['c']) : 0;
                if ($otras == 0) {
                    $this->conexion->query("UPDATE cotizaciones SET estado = 0 WHERE cotizacion_id = $idCoti");
                }
            }
        }
        return json_encode($resultado);
    }

    public function listarVentas()
    {
        // Limpiar cualquier output previo
        if (ob_get_level()) ob_clean();
        
        require_once "app/clases/serverside.php";
        header('Content-Type: application/json');
        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $table_data = new TableData();
        // view_ventas no tiene columna sucursal: para roles no-admin se filtra vía subconsulta a
        // ventas (antes el "where sucursal=" reventaba la consulta y DataTables recibía "Invalid JSON")
        $where = ($_SESSION['rol'] == 1) ? "" : "where id_venta in (select id_venta from ventas where sucursal = {$_SESSION["sucursal"]}) ";
        ob_start();
        // El buscador también encuentra por NÚMERO DE PEDIDO: como ese dato no está en
        // view_ventas, se resuelven aquí las ventas cuyo pedido coincide y se pasan como
        // condición extra de la búsqueda.
        $orPedido = "";
        $termino = isset($_GET['sSearch']) ? trim($_GET['sSearch']) : '';
        if ($termino !== '') {
            $terminoEsc = $this->conexion->real_escape_string($termino);
            $rsPed = $this->conexion->query("SELECT v.id_venta FROM ventas v
                INNER JOIN cotizaciones co ON co.cotizacion_id = v.id_coti
                WHERE co.numero LIKE '%$terminoEsc%' LIMIT 5000");
            if ($rsPed && $rsPed->num_rows > 0) {
                $idsPed = [];
                foreach ($rsPed as $rp) {
                    $idsPed[] = intval($rp['id_venta']);
                }
                $orPedido = "cod_v IN (" . implode(',', $idsPed) . ")";
            }
        }

        // Se cuenta por cod_v y no por id_venta: en la vista, id_venta es un CONCAT con
        // nombre_xml y queda NULL cuando la venta no tiene registro en ventas_sunat,
        // por lo que COUNT(id_venta) devolvía un total menor al real.
        $table_data->get("view_ventas", "cod_v", [
            "cod_v",
            "sn_v",
            "fecha_emision",
            "datos_cl",
            "subtotal",
            "igv_v",
            "total",
            "doc_ventae",
            "estado",
            "id_venta",
        ], $where, $orPedido);
        $salida = ob_get_clean();

        // Columna extra "Pedido": número del pedido (cotización) del que se generó cada venta.
        // Se agrega al final de cada fila (índice 10) sin modificar la vista view_ventas.
        // Se toma el JSON desde '{"sEcho"' para ignorar cualquier aviso de PHP impreso antes.
        $posJson = strpos($salida, '{"sEcho"');
        $json = ($posJson !== false) ? json_decode(substr($salida, $posJson), true) : null;
        if (is_array($json) && !empty($json['aaData'])) {
            // El id se toma de la columna 0 (cod_v): la 9 es un CONCAT que llega nulo cuando la
            // venta no tiene registro en ventas_sunat, y entonces no se hallaba su pedido.
            $ids = array_values(array_filter(array_map(function ($fila) { return intval($fila[0]); }, $json['aaData'])));
            $pedidos = [];
            if (!empty($ids)) {
                $rs = $this->conexion->query("SELECT v.id_venta, co.numero FROM ventas v
                    INNER JOIN cotizaciones co ON co.cotizacion_id = v.id_coti
                    WHERE v.id_venta IN (" . implode(',', $ids) . ")");
                if ($rs) {
                    foreach ($rs as $r) {
                        $pedidos[$r['id_venta']] = $r['numero'];
                    }
                }
            }
            foreach ($json['aaData'] as &$fila) {
                // La columna 9 (id_venta) es un CONCAT con nombre_xml: si la venta no tiene
                // registro en ventas_sunat llega NULL y el front fallaba al hacer split('--'),
                // dejando la tabla en "Processing...". Se reconstruye con el id real (columna 0).
                if ($fila[9] === null || $fila[9] === '') {
                    $fila[9] = $fila[0] . '---';
                }
                $fila[] = isset($pedidos[intval($fila[0])]) ? $pedidos[intval($fila[0])] : '';
            }
            unset($fila);
            $salida = json_encode($json);
        }
        echo $salida;

        //$this->venta->setIdEmpresa($_SESSION['id_empresa']);
        //$lista = $this->venta->verFilas("202202");
        //return json_encode($lista);
    }
    public function detalleVenta()
    {
        //echo $_POST['iventa'];
        $this->venta->setIdVenta($_POST['iventa']);
        return $this->venta->verDetalle();
    }
    public function tipoVenta()
    {
        //echo $_POST['iventa'];
        $idVenta = $_POST['iventa'];
        $sqlProducto = "SELECT * FROM productos_ventas WHERE id_venta = $idVenta";
        $sqlServicio = "SELECT * FROM ventas_servicios WHERE id_venta = $idVenta";
        $returnFetch = $this->venta->exeSQL($sqlProducto)->fetch_assoc();
        $respuesta['tipo'] = '';
        $respuesta['res'] = false;
        if (empty($returnFetch)) {
            $returnFetchServicios = $this->venta->exeSQL($sqlServicio)->fetch_assoc();
            $respuesta['tipo'] = 'servicio';
            $respuesta['data'] = $returnFetchServicios;
            $respuesta['res'] = true;
            return json_encode($respuesta);
        } else {
            $respuesta['tipo'] = 'productos';
            $respuesta['data'] = $returnFetch;
            $respuesta['res'] = true;
            return json_encode($respuesta);
        }
    }


    public function detalleVenta2()
    {
        //echo $_POST['iventa'];
        $this->venta->setIdVenta($_POST['iventa']);
        return $this->venta->verDetalle2();
    }

    public function editVentaServicio()
    {
        $resultado = ["res" => false];



        $dataSend = [];
        $dataSend["certGlobal"] = false;


        $c_cliente = new Cliente();
        $c_venta = new Venta();
        $c_tido = new DocumentoEmpresa();
        $c_detalle = new ProductoVenta();
        $c_servicio = new VentaServicio();
        // $c_curl = new SendCurlVenta();
        $c_sunat = new VentaSunat();
        $c_varios = new Varios();

        $id_empresa = $_SESSION['id_empresa'];

        $sql = "SELECT * from empresas where id_empresa = " . $id_empresa;

        $respEmpre = $c_venta->exeSQL($sql)->fetch_assoc();

        $igv_empr_sel = $respEmpre['igv'];


        $c_cliente->setIdEmpresa($id_empresa);
        $c_cliente->setDocumento(filter_input(INPUT_POST, 'num_doc'));
        $c_cliente->setDatos(filter_input(INPUT_POST, 'nom_cli'));
        $c_cliente->setDireccion(filter_input(INPUT_POST, 'dir_cli'));
        //$c_cliente->setDireccion2(filter_input(INPUT_POST, 'dir2_cli'));

        if ($c_cliente->getDocumento() == "") {
            $numDoc = $_POST['num_doc'] == '' ? '' : $_POST['num_doc'];
            $nombre = $_POST['nom_cli'] == '' ? '' : $_POST['nom_cli'];
            $c_cliente->modificar("SD" . $c_varios->generarCodigo(5), $nombre, $_POST['id_cliente']);
            /*             $c_cliente->setDocumento("SD" . $c_varios->generarCodigo(5));
            $c_cliente->insertar(); */
        } else {
            $numDoc = $_POST['num_doc'] == '' ? '' : $_POST['num_doc'];
            $nombre = $_POST['nom_cli'] == '' ? '' : $_POST['nom_cli'];
            $c_cliente->modificar($numDoc, $nombre, $_POST['id_cliente']);
            /*  $numDoc = $_POST['num_doc'] == '' ? '' : $_POST['num_doc'];
            $nombre = $_POST['nom_cli'] == '' ? '' : $_POST['nom_cli'];
            $c_cliente->modificar($numDoc, $nombre, $_POST['id_cliente']); */
            /*  if (!$c_cliente->verificarDocumento()) {
                $c_cliente->insertar();
            } else {
                $numDoc = $_POST['num_doc'] == '' ? '' : $_POST['num_doc'];
                $nombre = $_POST['nom_cli'] == '' ? '' : $_POST['nom_cli'];
                $c_cliente->modificar($numDoc, $nombre, $_POST['id_cliente']);
            } */
        }
        /*  $numDoc = $_POST['num_doc'] == '' ? '' : $_POST['num_doc'];
        $nombre = $_POST['nom_cli'] == '' ? '' : $_POST['nom_cli'];
        $c_cliente->modificar($numDoc, $nombre, $_POST['id_cliente']); */


        $resultado["email"] = $c_cliente->getEmail() ? $c_cliente->getEmail() : '';
        $resultado["cel"] = $c_cliente->getTelefono() ? $c_cliente->getTelefono() : '';

        $direccionselk = '';
        if ($_POST['dir_pos'] == 1) {
            $direccionselk = $_POST['dir_cli'];
        } elseif ($_POST['dir_pos'] == 2) {
            $direccionselk = $_POST['dir2_cli'];
        }

        if (trim($c_cliente->getDocumento()) == "") {
            $c_cliente->setDocumento('');
        }
        if (strlen(trim($direccionselk)) == "") {
            $direccionselk = '-';
        }
        if (trim($c_cliente->getDatos()) == "") {
            $c_cliente->setDatos('-');
        }

        $dataSend['cliente'] = json_encode([
            'doc_num' => $c_cliente->getDocumento(),
            'nom_RS' => $c_cliente->getDatos(),
            'direccion' => $direccionselk
        ]);
        $c_venta->setDireccion($direccionselk);
        /*   $dataSend['productos'] = []; */

        $c_venta->setApliIgv($_POST['apli_igv']);
        $c_venta->setIdEmpresa($id_empresa);
        $c_venta->setFecha($_POST['fecha']);
        $c_venta->setFechaVenc($_POST['tipo_pago'] == '1' ? $_POST['fecha'] : $_POST['fechaVen']);
        $c_venta->setDiasPagos($_POST['dias_pago']);
        $c_venta->setIdTipoPago($_POST['tipo_pago']);
        $c_venta->setObserva($_POST['observ']);

        $c_venta->setIdCliente($_POST['id_cliente']);
        $c_venta->setIgv($igv_empr_sel);
        $c_venta->setTotal(filter_input(INPUT_POST, 'total'));
        /*     $c_venta->setIdVenta(); */
        $tipoventa = filter_input(INPUT_POST, 'tipoventa');
        /* 

        $dataSend['apli_igv'] = $_POST['apli_igv'] == 1;
        $dataSend['total'] = $c_venta->getTotal();
        $dataSend['serie'] = $c_tido->getSerie();
        $dataSend['numero'] = $c_tido->getNumero();
        $dataSend['fechaE'] = $c_venta->getFecha();
        $dataSend['fechaV'] = $c_venta->getFechaVenc();
        $dataSend['tipo_pago'] = $c_venta->getIdTipoPago();
        $dataSend['igv_venta'] = $igv_empr_sel;
        $dataSend['dias_pagos'] = [];
        $dataSend['moneda'] = "PEN"; */

        $listaPagos = json_decode($_POST['dias_lista'], true);

        if ($c_venta->editar($_POST['idVenta'])) {

            $resultado["res"] = true;
            $array_detalle = json_decode($_POST['listaPro'], true);
            $id_usuario_pago = isset($_SESSION['usuario_fac']) ? $_SESSION['usuario_fac'] : (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null);
            $fecha_actual_pago = date('Y-m-d H:i:s');
            
            foreach ($listaPagos as $diaP) {
                $sql = "insert into dias_ventas set id_venta='{$c_venta->getIdVenta()}',
                    monto='{$diaP['monto']}',fecha='{$diaP['fecha']}',estado='0', id_usuario='$id_usuario_pago', fecha_pago_real='$fecha_actual_pago'";
                $c_venta->exeSQL($sql);
            }
            /*    $dataSend['dias_pagos'] = json_encode($dataSend['dias_pagos']); */

            $nroitem = 1;


            /*  $c_servicio->setIdventa(); */
            $c_servicio->eliminar($_POST['idVenta']);

            foreach ($array_detalle as $fila) {
                $c_servicio->setDescripcion($fila['descripcion']);
                $c_servicio->setCantidad($fila['cantidad']);
                $c_servicio->setMonto($fila['precioVenta']);
                $c_servicio->setCodsunat(isset($fila['codsunat']) ? $fila['codsunat'] : '');
                $c_servicio->setIditem($nroitem);
                /*  $c_servicio->setIdventa($_POST['idVenta']); */
                $c_servicio->editar($_POST['idVenta']);
                $nroitem++;
                /*     $dataSend['productos'][] = [
                    "precio" => $fila['precio'],
                    "cantidad" => $fila['cantidad'],
                    "cod_pro" => $nroitem,
                    "cod_sunat" => isset($fila['codsunat']) ? $fila['codsunat'] : '',
                    "descripcion" => $fila['descripcion']
                ]; */
            }

            //definir url segun el tipo de documento sunat
            if ($c_venta->getIdTido() == 1) {
                $archivo = "boleta";
            }
            if ($c_venta->getIdTido() == 2) {
                $archivo = "factura";
            }

            /*   if ($c_venta->getIdTido() == 1 || $c_venta->getIdTido() == 2) { */

            /* 
                $dataSend["endpoints"] = $respEmpre['modo'];

                $dataSend['empresa'] = json_encode([
                    'ruc' => $respEmpre['ruc'],
                    'razon_social' => $respEmpre['razon_social'],
                    'direccion' => $respEmpre['direccion'],
                    'ubigeo' => $respEmpre['ubigeo'],
                    'distrito' => $respEmpre['distrito'],
                    'provincia' => $respEmpre['provincia'],
                    'departamento' => $respEmpre['departamento'],
                    'clave_sol' => $respEmpre['clave_sol'],
                    'usuario_sol' => $respEmpre['user_sol']
                ]);



                $dataSend['productos'] = json_encode($dataSend['productos']); */
            /* 
                if ($c_venta->getIdTido() == 1) {
                    $dataResp = $this->sunatApi->genBoletaXML($dataSend);
                } else {
                    $dataResp = $this->sunatApi->genFacturaXML($dataSend);
                }



                if ($dataResp["res"]) {
                    $c_sunat->setIdVenta($c_venta->getIdVenta());
                    $c_sunat->setHash($dataResp['data']['hash']);
                    $c_sunat->setNombreXml($dataResp['data']['nombre_archivo']);
                    $c_sunat->setQrData($dataResp['data']['qr']);
                    $c_sunat->insertar();
                } else {
                } */
            /* } */ /* else {
                $c_sunat->setIdVenta($c_venta->getIdVenta());
                $c_sunat->setHash("-");
                $c_sunat->setNombreXml("-");
                $c_sunat->setQrData('-');
                $c_sunat->insertar();

                $resultado["valor"] = $c_venta->getIdVenta();
            } */
            /*    $resultado["nomFact"] = $c_sunat->getNombreXml() . ".pdf";
            $resultado["urlFact"] = URL::to('/venta/comprobante/pdf/' . $c_sunat->getIdVenta() . '/' . $c_sunat->getNombreXml());
            $resultado["urlFactd"] = URL::to('/venta/comprobante/pdfd/' . $c_sunat->getIdVenta() . '/' . $c_sunat->getNombreXml());
        } */
        }
        /*  $_REQUEST */
        $resultado["nomFact"] =  '2020' . ".pdf";
        $resultado["urlFact"] = URL::to('/venta/comprobante/pdf/' . $_POST['idVenta'] . '/' . '2020');
        $resultado["urlFactd"] = URL::to('/venta/comprobante/pdfd/' . $_POST['idVenta'] . '/2020');

        return json_encode($resultado);
    }
    public function editVentaProducto()
    {
        /* echo '<pre>';
        print_r(json_decode($_POST['listaPro'], true));
        echo '</pre>';
        exit(); */
        $resultado = ["res" => false];



        $dataSend = [];
        $dataSend["certGlobal"] = false;


        $c_cliente = new Cliente();
        $c_venta = new Venta();
        $c_tido = new DocumentoEmpresa();
        $c_detalle = new ProductoVenta();
        /*  $c_servicio = new VentaServicio(); */
        // $c_curl = new SendCurlVenta();
        $c_sunat = new VentaSunat();
        $c_varios = new Varios();

        $id_empresa = $_SESSION['id_empresa'];

        $sql = "SELECT * from empresas where id_empresa = " . $id_empresa;

        $respEmpre = $c_venta->exeSQL($sql)->fetch_assoc();

        $igv_empr_sel = $respEmpre['igv'];


        $c_cliente->setIdEmpresa($id_empresa);
        $c_cliente->setDocumento(filter_input(INPUT_POST, 'num_doc'));
        $c_cliente->setDatos(filter_input(INPUT_POST, 'nom_cli'));
        $c_cliente->setDireccion(filter_input(INPUT_POST, 'dir_cli'));
        //$c_cliente->setDireccion2(filter_input(INPUT_POST, 'dir2_cli'));


        if ($c_cliente->getDocumento() == "") {
            $numDoc = $_POST['num_doc'] == '' ? '' : $_POST['num_doc'];
            $nombre = $_POST['nom_cli'] == '' ? '' : $_POST['nom_cli'];
            $c_cliente->modificar("SD" . $c_varios->generarCodigo(5), $nombre, $_POST['id_cliente']);
            /*             $c_cliente->setDocumento("SD" . $c_varios->generarCodigo(5));
            $c_cliente->insertar(); */
        } else {
            $numDoc = $_POST['num_doc'] == '' ? '' : $_POST['num_doc'];
            $nombre = $_POST['nom_cli'] == '' ? '' : $_POST['nom_cli'];
            $c_cliente->modificar($numDoc, $nombre, $_POST['id_cliente']);
            /*  $numDoc = $_POST['num_doc'] == '' ? '' : $_POST['num_doc'];
            $nombre = $_POST['nom_cli'] == '' ? '' : $_POST['nom_cli'];
            $c_cliente->modificar($numDoc, $nombre, $_POST['id_cliente']); */
            /*  if (!$c_cliente->verificarDocumento()) {
                $c_cliente->insertar();
            } else {
                $numDoc = $_POST['num_doc'] == '' ? '' : $_POST['num_doc'];
                $nombre = $_POST['nom_cli'] == '' ? '' : $_POST['nom_cli'];
                $c_cliente->modificar($numDoc, $nombre, $_POST['id_cliente']);
            } */
        }

        $resultado["email"] = $c_cliente->getEmail() ? $c_cliente->getEmail() : '';
        $resultado["cel"] = $c_cliente->getTelefono() ? $c_cliente->getTelefono() : '';

        $direccionselk = '';
        if ($_POST['dir_pos'] == 1) {
            $direccionselk = $_POST['dir_cli'];
        } elseif ($_POST['dir_pos'] == 2) {
            $direccionselk = $_POST['dir2_cli'];
        }

        if (trim($c_cliente->getDocumento()) == "") {
            $c_cliente->setDocumento('');
        }
        if (strlen(trim($direccionselk)) == "") {
            $direccionselk = '-';
        }
        if (trim($c_cliente->getDatos()) == "") {
            $c_cliente->setDatos('-');
        }

        /*  $dataSend['cliente'] = json_encode([
            'doc_num' => $c_cliente->getDocumento(),
            'nom_RS' => $c_cliente->getDatos(),
            'direccion' => $direccionselk
        ]); */
        $c_venta->setDireccion($direccionselk);
        $c_tido->setIdEmpresa($id_empresa);
        $c_tido->setIdTido(filter_input(INPUT_POST, 'tipo_doc'));
        $c_tido->obtenerDatos();
        $c_venta->setApliIgv($_POST['apli_igv']);
        $c_venta->setIdEmpresa($id_empresa);
        $c_venta->setFecha($_POST['fecha']);
        $c_venta->setFechaVenc($_POST['tipo_pago'] == '1' ? $_POST['fecha'] : $_POST['fechaVen']);
        $c_venta->setDiasPagos($_POST['dias_pago']);
        $c_venta->setIdTipoPago($_POST['tipo_pago']);
        $c_venta->setObserva($_POST['observ']);
        $c_venta->setIdTido($c_tido->getIdTido());
        $c_venta->setSerie($c_tido->getSerie());
        $c_venta->setNumero($c_tido->getNumero());
        $c_venta->setIdCliente($_POST['id_cliente']);
        $c_venta->setIgv($igv_empr_sel);
        $c_venta->setTotal(filter_input(INPUT_POST, 'total'));


        /*      $dataSend['apli_igv'] = $_POST['apli_igv'] == 1;
        $dataSend['total'] = $c_venta->getTotal();
        $dataSend['serie'] = $c_tido->getSerie();
        $dataSend['numero'] = $c_tido->getNumero();
        $dataSend['fechaE'] = $c_venta->getFecha();
        $dataSend['fechaV'] = $c_venta->getFechaVenc();
        $dataSend['tipo_pago'] = $c_venta->getIdTipoPago();
        $dataSend['igv_venta'] = $igv_empr_sel;
        $dataSend['dias_pagos'] = [];
        $dataSend['moneda'] = "PEN"; */

        $listaPagos = json_decode($_POST['dias_lista'], true);

        if ($c_venta->editar($_POST['idVenta'])) {

            $resultado["res"] = true;
            $array_detalle = json_decode($_POST['listaPro'], true);
            $id_usuario_pago = isset($_SESSION['usuario_fac']) ? $_SESSION['usuario_fac'] : (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null);
            $fecha_actual_pago = date('Y-m-d H:i:s');

            foreach ($listaPagos as $diaP) {
                $tipo_pago_p = isset($diaP['metodo_nombre']) ? $diaP['metodo_nombre'] : 'Efectivo';
                $sql = "insert into dias_ventas set id_venta='{$_POST['idVenta']}',
                    monto='{$diaP['monto']}',fecha='{$diaP['fecha']}',estado='0', id_usuario='$id_usuario_pago', fecha_pago_real='$fecha_actual_pago', tipo_pago='$tipo_pago_p'";
                $c_venta->exeSQL($sql);
            }
            /*  $dataSend['dias_pago'] = json_encode($dataSend['dias_pagos']); */
            #verificar los cambios de cantidades
            //    $sql_detalle = "SELECT * FROM productos_ventas WHERE id_producto=92 and id_venta='{$_POST['idVenta']}';";
            $sql_detalle = "SELECT * FROM productos_ventas WHERE id_venta='{$_POST['idVenta']}'";
            $detalle_venta = $this->conexion->query($sql_detalle)->fetch_all(MYSQLI_ASSOC);
            $cambios_cantidad = [];
            $i = 0;
            $arr_product_id_cambios = [];
            #retornamos el stock al inventario
            require_once "app/models/Kardex.php";
            $c_kardex = new Kardex($this->conexion);
            # Las salidas anteriores de esta venta quedan ANULADAS en el kardex (no se borran);
            # el reingreso las compensa y los productos finales vuelven a salir como 'Venta'.
            $c_kardex->anularPorReferencia('venta:' . $_POST['idVenta'], 'e');
            $c_kardex->anularPorReferencia('venta:' . $_POST['idVenta'], 'i', 'Recojo');
            foreach ($detalle_venta as $detalle) {
                $presenta_cnt = $detalle['presenta_cnt'];
                $presenta_cnt = ($presenta_cnt == 0) ? 1 : $presenta_cnt;
                $cantidad = $detalle['cantidad'] * $presenta_cnt;
                #
                $sql = "update productos set  cantidad= cantidad+{$cantidad} where id_producto='{$detalle['id_producto']}' ";
                //echo $sql;
                $this->conexion->query($sql);
                # Kardex: reversa por edición de venta (el nuevo detalle volverá a registrarse como 'Venta'/'Recojo').
                # Línea positiva => reingreso; línea NEGATIVA (recojo) => el UPDATE la restó, es una salida.
                if ($cantidad < 0) {
                    $c_kardex->registrar($detalle['id_producto'], 'e', 'Edicion de venta', abs($cantidad), 'venta:' . $_POST['idVenta'], 'Reversa de recojo por edición');
                } else {
                    $c_kardex->registrar($detalle['id_producto'], 'i', 'Edicion de venta', $cantidad, 'venta:' . $_POST['idVenta']);
                }
            }
            # primero recorremos los nuevos productos
            foreach ($array_detalle as $key => $a_detalle) {
                $existe = -1;
                foreach ($detalle_venta as $detalle) {
                    if ($detalle['id_producto'] == $a_detalle['productoid']) {
                        $cantidad_db = $detalle['cantidad'] * $detalle['presenta_cnt'];
                        $presenta_cnt = (isset($a_detalle['presenta_cnt'])) ? $a_detalle['presenta_cnt'] : $a_detalle['presentacionCnt'];
                        $cantidad_ingreso = $a_detalle['cantidad'] * $presenta_cnt;
                        $cantidad = $cantidad_db - $cantidad_ingreso;
                        if ($cantidad > 0) {
                            $arr_product_id_cambios[] = $detalle['id_producto'];
                            $cambios_cantidad[$i] = $detalle;
                            $cambios_cantidad[$i]['cantidad'] = $cantidad;
                            $cambios_cantidad[$i]['signo'] = '+';
                            $i++;
                        }
                        /* else if ($cantidad < 0) {
                            $arr_product_id_cambios[] = $detalle['id_producto'];
                            $cambios_cantidad[$i] = $detalle;
                            $cambios_cantidad[$i]['cantidad'] = abs($cantidad);
                            $cambios_cantidad[$i]['signo'] = '-';
                            $i++;
                        } */
                        $existe = 1;
                        break;
                    }
                }
                if ($existe === -1) {
                    $arr_product_id_cambios[] = $a_detalle['productoid'];
                    $cambios_cantidad[$i] = $a_detalle;
                    $cambios_cantidad[$i]['signo'] = '+';
                    $i++;
                }
            }
            #luego verificamos en los productos que existian
            foreach ($detalle_venta as $detalle) {
                if (in_array($detalle['id_producto'], $arr_product_id_cambios)) continue;
                $existe = -1;
                foreach ($array_detalle as $a_detalle) {
                    if ($detalle['id_producto'] == $a_detalle['productoid']) {
                        $cantidad_db = $detalle['cantidad'] * $detalle['presenta_cnt'];
                        $presenta_cnt = (isset($a_detalle['presenta_cnt'])) ? $a_detalle['presenta_cnt'] : $a_detalle['presentacionCnt'];
                        $cantidad_ingreso = $a_detalle['cantidad'] * $presenta_cnt;
                        $cantidad = $cantidad_db - $cantidad_ingreso;
                        if ($cantidad > 0) {
                            $arr_product_id_cambios[] = $detalle['id_producto'];
                            $cambios_cantidad[$i] = $detalle;
                            $cambios_cantidad[$i]['cantidad'] = $cantidad;
                            $cambios_cantidad[$i]['signo'] = '+';
                        }
                        /* else if ($cantidad < 0) {
                            $arr_product_id_cambios[] = $detalle['id_producto'];
                            $cambios_cantidad[$i] = $detalle;
                            $cambios_cantidad[$i]['cantidad'] = abs($cantidad);
                            $cambios_cantidad[$i]['signo'] = '-';
                        } */
                        $existe = 1;
                        break;
                    }
                }
                if ($existe === -1) {
                    $arr_product_id_cambios[] = $detalle['id_producto'];
                    $cambios_cantidad[$i] = $detalle;
                    $cambios_cantidad[$i]['signo'] = '+';
                    $cambios_cantidad[$i]['algo'] = 'asasd';
                    $cambios_cantidad[$i]['algo1'] = $detalle['id_producto'];
                }
                $i++;
            }
            #
            foreach ($cambios_cantidad as $item) {
                // Las líneas de RECOJO (cantidad negativa) no son devoluciones de la venta:
                // ya entran al stock y al kardex con el motivo 'Recojo'.
                if (floatval($item['cantidad']) < 0) {
                    continue;
                }
                $presenta = (isset($item['presenta'])) ? $item['presenta'] : $item['presentacion'];
                $presenta_cnt = (isset($item['presenta_cnt'])) ? $item['presenta_cnt'] : $item['presentacionCnt'];
                $presenta_cnt = ($presenta_cnt == 0) ? 1 : $presenta_cnt;
                #
                if ($c_venta->getIdTido() == 6) {
                    $sql_1 = "
                   INSERT INTO devoluciones_nv (id_venta,id_producto,id_usuario,cantidad,presenta,presenta_cnt,signo)
                   VALUES ('{$item['id_venta']}','{$item['id_producto']}','{$_SESSION['usuario_fac']}','{$item['cantidad']}','{$presenta}','{$presenta_cnt}','{$item['signo']}')
                   ";
                    //echo $sql_1;
                    $this->conexion->query($sql_1);
                }
            }

            /* $c_detalle->setIdVenta($c_venta->getIdVenta()); */
            $c_detalle->eliminar($_POST['idVenta']);

            /*  $c_servicio->eliminar($_POST['idVenta']);   */
            $c_detalle->setIdVenta($_POST['idVenta']);
            foreach ($array_detalle as $fila) {
                $presenta = (isset($fila['presenta'])) ? $fila['presenta'] : $fila['presentacion'];
                $presenta_cnt = (isset($fila['presenta_cnt'])) ? $fila['presenta_cnt'] : $fila['presentacionCnt'];
                $presenta_cnt = ($presenta_cnt == 0) ? 1 : $presenta_cnt;
                $c_detalle->setIdProducto($fila['productoid']);
                $c_detalle->setCantidad($fila['cantidad']);
                $c_detalle->setCosto($fila['costo']);
                $c_detalle->setMedida($fila['medida']);
                $c_detalle->setPresenta($presenta);
                $c_detalle->setPresentaCnt($presenta_cnt);
                $c_detalle->setPrecio($_POST['moneda'] == 1 ? $fila['precioVenta'] : $fila['precioVenta'] / $_POST['tc']);
                $c_detalle->setPrecioUsado($_POST['moneda'] == 1 ? $fila['precio_usado'] : $fila['precio_usado'] / $_POST['tc']);
                $c_detalle->insertar();
                /*   $dataSend['productos'][] = [
                    "precio" => $fila['precio'],
                    "cantidad" => $fila['cantidad'],
                    "cod_pro" => $fila['productoid'],
                    "cod_sunat" => "",
                    "descripcion" => $fila['descripcion']
                ]; */
            }

            //definir url segun el tipo de documento sunat
            /*   if ($c_venta->getIdTido() == 1) {
                $archivo = "boleta";
            }
            if ($c_venta->getIdTido() == 2) {
                $archivo = "factura";
            }

            if ($c_venta->getIdTido() == 1 || $c_venta->getIdTido() == 2) {


                $dataSend["endpoints"] = $respEmpre['modo'];

                $dataSend['empresa'] = json_encode([
                    'ruc' => $respEmpre['ruc'],
                    'razon_social' => $respEmpre['razon_social'],
                    'direccion' => $respEmpre['direccion'],
                    'ubigeo' => $respEmpre['ubigeo'],
                    'distrito' => $respEmpre['distrito'],
                    'provincia' => $respEmpre['provincia'],
                    'departamento' => $respEmpre['departamento'],
                    'clave_sol' => $respEmpre['clave_sol'],
                    'usuario_sol' => $respEmpre['user_sol']
                ]);



                $dataSend['productos'] = json_encode($dataSend['productos']);

                if ($c_venta->getIdTido() == 1) {
                    $dataResp = $this->sunatApi->genBoletaXML($dataSend);
                } else {
                    $dataResp = $this->sunatApi->genFacturaXML($dataSend);
                }



                if ($dataResp["res"]) {
                    $c_sunat->setIdVenta($c_venta->getIdVenta());
                    $c_sunat->setHash($dataResp['data']['hash']);
                    $c_sunat->setNombreXml($dataResp['data']['nombre_archivo']);
                    $c_sunat->setQrData($dataResp['data']['qr']);
                    $c_sunat->insertar();
                } else {
                }
            } else {
                $c_sunat->setIdVenta($c_venta->getIdVenta());
                $c_sunat->setHash("-");
                $c_sunat->setNombreXml("-");
                $c_sunat->setQrData('-');
                $c_sunat->insertar();

                $resultado["valor"] = $c_venta->getIdVenta();
            } */
            $resultado["nomFact"] =  '2020' . ".pdf";
            $resultado["urlFact"] = URL::to('/venta/comprobante/pdf/' . $_POST['idVenta'] . '/' . '2020');
            $resultado["urlFactd"] = URL::to('/venta/comprobante/pdfd/' . $_POST['idVenta'] . '/2020');
        }

        return json_encode($resultado);
    }
    public function guardarVentas()
    {
        $resultado = ["res" => false];

        // VALIDACIÓN: un pedido ya convertido no se puede volver a convertir.
        // Se libera solo al ANULAR la venta que generó (ver anularVenta()).
        $cotiIdConv = isset($_POST['cotiId']) ? intval($_POST['cotiId']) : (isset($_POST['idCoti']) ? intval($_POST['idCoti']) : 0);
        if ($cotiIdConv > 0) {
            $rsConv = $this->conexion->query("SELECT COUNT(*) AS c FROM ventas WHERE id_coti = $cotiIdConv AND estado = 1");
            if ($rsConv && intval($rsConv->fetch_assoc()['c']) > 0) {
                echo json_encode([
                    'res' => false,
                    'msj' => 'Este pedido ya fue convertido en venta. Para volver a convertirlo debe anular la venta.'
                ]);
                return;
            }
        }

        // VALIDACIÓN: Cliente obligatorio
        $num_doc = trim(filter_input(INPUT_POST, 'num_doc'));
        $nom_cli = trim(filter_input(INPUT_POST, 'nom_cli'));
        
        if (empty($num_doc) || empty($nom_cli)) {
            echo json_encode([
                'res' => false,
                'msj' => 'El cliente es obligatorio. Debe ingresar documento y nombre del cliente.'
            ]);
            return;
        }

        // 2026-07-18: Se eliminó la validación que exigía tener todas las cuotas de la
        // cotización pagadas antes de convertirla en venta. Ahora se puede convertir con
        // pagos pendientes: la deuda se sigue cobrando desde la cuenta por cobrar de ventas.

        $dataSend = [];
        $dataSend["certGlobal"] = false;


        $c_cliente = new Cliente();
        $c_venta = new Venta();
        $c_tido = new DocumentoEmpresa();
        $c_detalle = new ProductoVenta();
        $c_servicio = new VentaServicio();
        // $c_curl = new SendCurlVenta();
        $c_sunat = new VentaSunat();
        $c_varios = new Varios();
        $c_guia = new GuiaRemision();

        $id_empresa = $_SESSION['id_empresa'];

        $sql = "SELECT * from empresas where id_empresa = " . $id_empresa;

        $respEmpre = $c_venta->exeSQL($sql)->fetch_assoc();

        $igv_empr_sel = $respEmpre['igv'];


        $c_cliente->setIdEmpresa($id_empresa);
        $c_cliente->setDocumento($num_doc);
        $c_cliente->setDatos($nom_cli);
        $c_cliente->setDireccion(filter_input(INPUT_POST, 'dir_cli'));
        //$c_cliente->setDireccion2(filter_input(INPUT_POST, 'dir2_cli'));

        // Verificar si el cliente ya existe, si no, insertarlo
        if (!$c_cliente->verificarDocumento()) {
            $c_cliente->insertar();
        }

        $resultado["email"] = $c_cliente->getEmail() ? $c_cliente->getEmail() : '';
        $resultado["cel"] = $c_cliente->getTelefono() ? $c_cliente->getTelefono() : '';

        $direccionselk = '';
        if ($_POST['dir_pos'] == 1) {
            $direccionselk = $_POST['dir_cli'];
        } elseif ($_POST['dir_pos'] == 2) {
            $direccionselk = $_POST['dir2_cli'];
        }

        if (trim($c_cliente->getDocumento()) == "") {
            $c_cliente->setDocumento('');
        }
        if (strlen(trim($direccionselk)) == "") {
            $direccionselk = '-';
        }
        if (trim($c_cliente->getDatos()) == "") {
            $c_cliente->setDatos('-');
        }

        $dataSend['cliente'] = json_encode([
            'doc_num' => $c_cliente->getDocumento(),
            'nom_RS' => $c_cliente->getDatos(),
            'direccion' => $direccionselk
        ]);
        $c_venta->setDireccion($direccionselk);
        $dataSend['productos'] = [];
        $c_tido->setIdEmpresa($id_empresa);
        $c_tido->setIdTido(filter_input(INPUT_POST, 'tipo_doc'));
        $c_tido->obtenerDatos();
        $c_venta->setApliIgv($_POST['apli_igv']);
        $c_venta->setIdEmpresa($id_empresa);
        $c_venta->setFecha($_POST['fecha']);
        $c_venta->setFechaVenc($_POST['tipo_pago'] == '1' ? $_POST['fecha'] : $_POST['fechaVen']);
        $c_venta->setDiasPagos($_POST['dias_pago']);
        $c_venta->setIdTipoPago($_POST['tipo_pago']);
        $metodo = intval($_POST['metodo']);
        $c_venta->setMetodo($metodo);
        $c_venta->setObserva($_POST['observ']);
        $c_venta->setIdTido($c_tido->getIdTido());
        $c_venta->setSerie($c_tido->getSerie());
        $c_venta->setNumero($c_tido->getNumero());
        $c_venta->setIdCliente($c_cliente->getIdCliente());
        $c_venta->setIgv($igv_empr_sel);
        $c_venta->setTotal(filter_input(INPUT_POST, 'total'));
        $c_venta->setIdCoti($_POST['idCoti']);
        $tipoventa = filter_input(INPUT_POST, 'tipoventa');


        $dataSend['apli_igv'] = $_POST['apli_igv'] == 1;
        $dataSend['total'] = number_format($c_venta->getTotal(), 2, '.', '');
        $dataSend['serie'] = $c_tido->getSerie();
        $dataSend['numero'] = $c_tido->getNumero();
        $dataSend['fechaE'] = $c_venta->getFecha();
        $dataSend['fechaV'] = $c_venta->getFechaVenc();
        $dataSend['tipo_pago'] = $c_venta->getIdTipoPago();
        $dataSend['igv_venta'] = $igv_empr_sel;
        $dataSend['dias_pagos'] = [];
        $dataSend['moneda'] = $_POST['moneda'] == 1 ? "PEN" : "USD";
        $dataSend['tc'] = $_POST['tc'];


        $datosGuiaRemosion = json_decode($_POST['datosGuiaRemosion'], true);
        $datosTransporteGuiaRemosion = json_decode($_POST['datosTransporteGuiaRemosion'], true);

        /* echo json_encode($datosGuiaRemosion);

        return; */
        /*   $datosUbigeoGuiaRemosion = json_decode($_POST['datosUbigeoGuiaRemosion'], true); */
        /*   echo json_encode($datosGuiaRemosion['fecha_emision']);
        echo json_encode($datosGuiaRemosion['dir_cli']);
        echo json_encode($_POST['datosUbigeoGuiaRemosion']);
        echo json_encode($datosTransporteGuiaRemosion); */
        /*  echo json_encode($datosGuiaRemosion['fecha_emision']);
        echo json_encode($datosGuiaRemosion['fecha_emision']); */
        /*  return */
        $listaPagos = json_decode($_POST['dias_lista'], true);

        if ($c_venta->insertar()) {
            // Avanzar el correlativo del documento: la siguiente venta usará numero + 1
            // (antes no se incrementaba y todas las ventas salían con el mismo número)
            $c_tido->incrementarNumero();

            $pagos = $_POST["pagos"];
            foreach ($pagos as $i => $pago) {
                $npago = $i + 1;
                if ($pago["metodoPago"] !== "" && $pago['montoPago'] !== "") {
                    $sql = "insert into ventas_pagos set id_venta='{$c_venta->getIdVenta()}',
                    metodo_pago='{$pago['metodoPago']}',monto='{$pago['montoPago']}',npago='{$npago}'";
                    $c_venta->exeSQL($sql);
                }
            }
            if (isset($_POST['cotiId'])) {
                $sql = "UPDATE cotizaciones set estado = 1 WHERE cotizacion_id = '{$_POST['cotiId']}'";
                $this->conexion->query($sql);
            }


            $resultado["res"] = true;
            $array_detalle = json_decode($_POST['listaPro'], true);

            // NOTA: convertir un pedido quitando/reduciendo productos NO genera devoluciones:
            // el pedido nunca descontó stock, así que no hay nada que devolver. Las devoluciones
            // nacen al EDITAR o ANULAR una venta ya emitida (ahí el stock sí había salido).

            // Si proviene de cotización, limpiamos SOLO las cuotas que estaban originalmente NO PAGADAS (estado='0' o null).
            // Mantenemos las pagadas originales para no perder su registro histórico.
            if (isset($_POST['cotiId'])) {
                $sqlCotiDel = "DELETE FROM cuotas_cotizacion WHERE id_coti='{$_POST['cotiId']}' AND (estado='0' OR estado IS NULL)";
                $c_venta->exeSQL($sqlCotiDel);
            }

            $id_usuario_pago = isset($_SESSION['usuario_fac']) ? $_SESSION['usuario_fac'] : (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null);
            $fecha_actual_pago = date('Y-m-d H:i:s');

            foreach ($listaPagos as $diaP) {
                $montoVal = floatval($diaP['monto']);
                if ($montoVal <= 0) {
                    continue; // Saltar cuotas vacías (como las generadas en 0.00 por defecto)
                }

                // Conservar el estado real de la cuota. Si no llega, se asume PENDIENTE ('0'):
                // bajo el nuevo modelo la deuda de una venta a crédito nace pendiente
                // y se cobra desde Cuentas por Cobrar de Ventas.
                $estadoP = (isset($diaP['estado']) && $diaP['estado'] == '1') ? '1' : '0';

                // Si la cuota viene del pedido y YA estaba cobrada en Cuentas por Cobrar, el cobro
                // conserva su fecha, usuario y método ORIGINALES (no "hoy" ni quien convierte):
                // así Arqueo diario y Mis cobros no mueven ni duplican ese cobro.
                $cuotaId = (isset($_POST['cotiId']) && isset($diaP['cuotaid'])) ? trim($diaP['cuotaid']) : '';
                $cuotaOrig = null;
                if (!empty($cuotaId)) {
                    $rsOrig = $c_venta->exeSQL("SELECT estado, tipo_pago, id_usuario, fecha_pago_real FROM cuotas_cotizacion WHERE cuota_coti_id='{$cuotaId}'");
                    $cuotaOrig = $rsOrig ? $rsOrig->fetch_assoc() : null;
                }
                $yaPagadaEnCxC = ($cuotaOrig && $cuotaOrig['estado'] == '1' && !empty($cuotaOrig['fecha_pago_real']));
                if ($yaPagadaEnCxC) {
                    $estadoP = '1';
                    $usuarioCobro = !empty($cuotaOrig['id_usuario']) ? $cuotaOrig['id_usuario'] : $id_usuario_pago;
                    $fechaCobro = $cuotaOrig['fecha_pago_real'];
                    $metodoCobro = !empty($cuotaOrig['tipo_pago']) ? $cuotaOrig['tipo_pago'] : $diaP['metodo_nombre'];
                } else {
                    $usuarioCobro = $id_usuario_pago;
                    $fechaCobro = $fecha_actual_pago;
                    $metodoCobro = $diaP['metodo_nombre'];
                }

                // Insertar la cuota en la venta
                $sql = "insert into dias_ventas set id_venta='{$c_venta->getIdVenta()}',
                    monto='{$diaP['monto']}',fecha='{$diaP['fecha']}',estado='$estadoP', tipo_pago='{$metodoCobro}', id_usuario='$usuarioCobro', fecha_pago_real=" . ($estadoP === '1' ? "'$fechaCobro'" : "NULL") . "";
                $c_venta->exeSQL($sql);

                // Sincronizar con cuotas_cotizacion
                if (isset($_POST['cotiId'])) {
                    if ($cuotaOrig) {
                        if ($yaPagadaEnCxC) {
                            // Cobro original: no se toca (conserva fecha, usuario y método del cobro en CxC)
                        } else {
                            // Actualizar la cuota que se preservó (respetando su estado real:
                            // ahora se puede convertir a venta con cuotas pendientes)
                            $sqlCotiInst = "UPDATE cuotas_cotizacion SET monto='{$diaP['monto']}', fecha='{$diaP['fecha']}', estado='$estadoP', tipo_pago='{$diaP['metodo_nombre']}', id_usuario='$id_usuario_pago', fecha_pago_real=" . ($estadoP === '1' ? "'$fecha_actual_pago'" : "NULL") . " WHERE cuota_coti_id='{$cuotaId}'";
                            $c_venta->exeSQL($sqlCotiInst);
                        }
                    } else {
                        // Insertar nueva cuota conservando su estado real (pagada o pendiente)
                        $sqlCotiInst = "insert into cuotas_cotizacion set id_coti='{$_POST['cotiId']}',
                            monto='{$diaP['monto']}',fecha='{$diaP['fecha']}',estado='$estadoP', tipo_pago='{$diaP['metodo_nombre']}', id_usuario='$id_usuario_pago', fecha_pago_real=" . ($estadoP === '1' ? "'$fecha_actual_pago'" : "NULL") . "";
                        $c_venta->exeSQL($sqlCotiInst);
                    }
                }

                $dataSend['dias_pagos'][] = [
                    "monto" => $diaP['monto'],
                    "fecha" => $diaP['fecha']
                ];
            }
            $dataSend['dias_pagos'] = json_encode($dataSend['dias_pagos']);

            $dataSaveLog = "Venta: {$c_venta->getIdVenta()}, fecha: " . date("Y-m-d") . "\n\n";

            if ($tipoventa == 1) {
                $c_detalle->setIdVenta($c_venta->getIdVenta());
                foreach ($array_detalle as $fila) {
                    $presenta = (isset($fila['presenta'])) ? $fila['presenta'] : $fila['presentacion'];
                    $presenta_cnt = (isset($fila['presenta_cnt'])) ? $fila['presenta_cnt'] : $fila['presentacionCnt'];
                    $c_detalle->setIdProducto($fila['productoid']);
                    $c_detalle->setCantidad($fila['cantidad']);
                    $c_detalle->setCosto($fila['costo']);
                    $c_detalle->setMedida($fila['medida']);
                    $c_detalle->setPresenta($presenta);
                    $c_detalle->setPresentaCnt($presenta_cnt);
                    $c_detalle->setPrecio($_POST['moneda'] == 1 ? $fila['precioVenta'] : $fila['precioVenta'] / $_POST['tc']);
                    $c_detalle->setPrecioUsado($_POST['moneda'] == 1 ? $fila['precio_usado'] : $fila['precio_usado'] / $_POST['tc']);
                    if ($c_detalle->insertar()) {
                        $dataSaveLog .= "Prod: " . $c_detalle->getSql() . " - true";
                    } else {
                        $dataSaveLog .= "Prod: " . $c_detalle->getSql() . " - false \n";
                        $dataSaveLog .= $c_detalle->getSqlError() . "\n\n\n";
                    }

                    $dataSend['productos'][] = [
                        "precio" => $_POST['moneda'] == 1 ? $fila['precioVenta'] : number_format($fila['precioVenta'] / $_POST['tc'], 2, '.', ''),
                        "cantidad" => $fila['cantidad'],
                        "cod_pro" => $fila['productoid'],
                        "cod_sunat" => "",
                        "descripcion" => $fila['descripcion']
                    ];

                    //$sql="update productos set  cantidad= cantidad-'{$fila['cantidad']}' where id_producto='{$fila['productoid']}' ";
                    //$c_venta->exeSQL($sql);
                }
            }
            file_put_contents("files/log/ventas/Venta_" . $c_venta->getIdVenta() . "_" . $dataSend['serie'] . '-' . $dataSend['numero'] . '.txt', $dataSaveLog);

            if ($tipoventa == 2) {
                $nroitem = 1;
                $c_servicio->setIdventa($c_venta->getIdVenta());
                foreach ($array_detalle as $fila) {
                    $c_servicio->setDescripcion($fila['descripcion']);
                    $c_servicio->setCantidad($fila['cantidad']);
                    $c_servicio->setMonto($fila['precioVenta']);
                    $c_servicio->setCodsunat(isset($fila['codsunat']) ? $fila['codsunat'] : '');
                    $c_servicio->setIditem($nroitem);
                    $c_servicio->insertar();
                    $nroitem++;
                    $dataSend['productos'][] = [
                        "precio" => $fila['precioVenta'],
                        "cantidad" => $fila['cantidad'],
                        "cod_pro" => $nroitem,
                        "cod_sunat" => isset($fila['codsunat']) ? $fila['codsunat'] : '',
                        "descripcion" => $fila['descripcion']
                    ];
                }
            }


            //definir url segun el tipo de documento sunat
            if ($c_venta->getIdTido() == 1) {
                $archivo = "boleta";
            }
            if ($c_venta->getIdTido() == 2) {
                $archivo = "factura";
            }

            $nom_xmlFac = '-';

            if ($c_venta->getIdTido() == 1 || $c_venta->getIdTido() == 2) {


                $dataSend["endpoints"] = $respEmpre['modo'];
                //


                if ($_SESSION['sucursal'] != '1') {
                    $datoSucursal = $this->conexion->query("SELECT * FROM sucursales WHERE cod_sucursal ='{$_SESSION['sucursal']}' AND empresa_id=" . $_SESSION['id_empresa'])->fetch_assoc();
                    $dataSend['empresa'] = json_encode([
                        'ruc' => $respEmpre['ruc'],
                        'razon_social' => $respEmpre['razon_social'],
                        'direccion' => $datoSucursal['direccion'],
                        'ubigeo' => $datoSucursal['ubigeo'],
                        'distrito' => $datoSucursal['distrito'],
                        'provincia' => $datoSucursal['provincia'],
                        'departamento' => $datoSucursal['departamento'],
                        'clave_sol' => $respEmpre['clave_sol'],
                        'usuario_sol' => $respEmpre['user_sol']
                    ]);
                } else {
                    $dataSend['empresa'] = json_encode([
                        'ruc' => $respEmpre['ruc'],
                        'razon_social' => $respEmpre['razon_social'],
                        'direccion' => $respEmpre['direccion'],
                        'ubigeo' => $respEmpre['ubigeo'],
                        'distrito' => $respEmpre['distrito'],
                        'provincia' => $respEmpre['provincia'],
                        'departamento' => $respEmpre['departamento'],
                        'clave_sol' => $respEmpre['clave_sol'],
                        'usuario_sol' => $respEmpre['user_sol']
                    ]);
                }

                /*  if() */


                $dataSend['productos'] = json_encode($dataSend['productos']);

                file_put_contents("aaaaaaaaaaas.json", json_encode($dataSend));
                if ($c_venta->getIdTido() == 1) {
                    $dataResp = $this->sunatApi->genBoletaXML($dataSend);
                } else {
                    $dataResp = $this->sunatApi->genFacturaXML($dataSend);
                }



                if ($dataResp["res"]) {
                    $c_sunat->setIdVenta($c_venta->getIdVenta());
                    $c_sunat->setHash($dataResp['data']['hash']);
                    $c_sunat->setNombreXml($dataResp['data']['nombre_archivo']);
                    $c_sunat->setQrData($dataResp['data']['qr']);
                    $c_sunat->insertar();

                    $nom_xmlFac = $dataResp['data']['nombre_archivo'];
                } else {
                }
            } else {
                $c_sunat->setIdVenta($c_venta->getIdVenta());
                $c_sunat->setHash("-");
                $c_sunat->setNombreXml("-");
                $c_sunat->setQrData('-');
                $c_sunat->insertar();

                $resultado["valor"] = $c_venta->getIdVenta();
            }
            $resultado["nomxml"] = $nom_xmlFac;
            $resultado["venta"] = $c_venta->getIdVenta();
            $resultado["nomFact"] = $c_sunat->getNombreXml() . ".pdf";
            $resultado["urlFact"] = URL::to('/venta/comprobante/pdf/' . $c_sunat->getIdVenta() . '/' . $c_sunat->getNombreXml());
            $resultado["urlFactd"] = URL::to('/venta/comprobante/pdfd/' . $c_sunat->getIdVenta() . '/' . $c_sunat->getNombreXml());
        }
        // JSON_INVALID_UTF8_SUBSTITUTE: si algún texto (nombre de cliente, dirección) viene con
        // codificación inválida, json_encode devolvía false y el front mostraba "Error en el
        // servidor" pese a que la venta sí se guardó.
        return json_encode($resultado, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}