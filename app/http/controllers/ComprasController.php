<?php

require_once "app/models/Compra.php";
require_once "app/models/DocumentoEmpresa.php";

class ComprasController extends Controller
{
    private $conectar;

    public function __construct()
    {
        $this->conectar=(new Conexion())->getConexion();
    }

    public function guardarCompras()
    {
        /* $this->sunatApi = new SunatApi(); */
        $insert = false;

        $c_compra = new Compra();
        $c_tido = new DocumentoEmpresa();


        $idProveedor = '';
        $nombre_comercial = isset($_POST['nombre_comercial']) ? $_POST['nombre_comercial'] : '';
        
        if ($_POST['num_doc'] !== '') {
            $empresaExistente = $c_tido->consultarProveedor($_POST['num_doc']);
            if (!empty($empresaExistente)) {
                $idProveedor = $empresaExistente[0]['proveedor_id'];
                // Actualizar nombre comercial si cambió
                if ($nombre_comercial !== '') {
                    $sql = "UPDATE proveedores SET nombre_comercial = '$nombre_comercial' WHERE proveedor_id = '$idProveedor'";
                    $this->conectar->query($sql);
                }
            } else {
                $insert = $c_tido->insertarProveedor($_POST['num_doc'], $_POST['nom_cli'], $nombre_comercial);
                $idProveedor = $insert;
            }
        }

        $id_tido = $_POST['tipo_doc'] !== '' ? $_POST['tipo_doc'] : 2;
        $tipo_pago = $_POST['tipo_pago'] !== '' ? $_POST['tipo_pago'] : '';
        $fecha = isset($_POST['fecha'])  ? $_POST['fecha'] : '';
        $fechaVen = isset($_POST['fechaVen'])  ? $_POST['fechaVen'] : '';
        $dir_cli = $_POST['dir_cli'] !== '' ? $_POST['dir_cli'] : '-';
        $serie = $_POST['serie'] !== '' ? $_POST['serie'] : '';
        $numero = $_POST['numero'] !== '' ? $_POST['numero'] : '';
        $total = $_POST['total'] !== 0 ? intval($_POST['total']) : 0;
        $moneda = $_POST['moneda'] !== '' ? $_POST['moneda'] : '';
        $tipoventa = $_POST['tipoventa'] !== '' ? $_POST['tipoventa'] : '';



        if ($id_tido !== '' && $tipo_pago !== ''  && $fecha !== '' && $fechaVen !== '' && $dir_cli !== '' && $serie !== '' && $numero !== '' && $total > 0 && $moneda !== '' && $idProveedor !== '') {
            $array_detalle = json_decode($_POST['listaPro'], true);
            $listaPagos = json_decode($_POST['dias_lista'], true);
            $insertarCompra =  $c_compra->insertarCompra($id_tido, $tipo_pago, $idProveedor, $fecha, $fechaVen, $dir_cli, $serie, $numero, $total, $_SESSION['id_empresa'], $moneda);

            if (is_int($insertarCompra)) {
                // 2026-07-19: el stock ya NO ingresa al registrar la compra.
                // Ingresa al RECEPCIONAR la mercadería (total o parcial) desde la lista
                // de compras; lo rechazado nunca entra al stock. Ver recepcionRegistrar().
                $updateStock = true;
                if ($updateStock) {
                    if ($tipo_pago == 1) {
                        $insertCompra = false;
                        foreach ($array_detalle as $fila) {
                            // Normalizar el ID del producto
                            $productoid = isset($fila['productoid']) ? $fila['productoid'] : null;
                            
                            // Normalizar cantidad
                            $cantidad = isset($fila['cantidad']) ? $fila['cantidad'] : 0;
                            
                            // Normalizar precio (puede venir como 'precio' o 'costo')
                            $precio = isset($fila['precio']) ? $fila['precio'] : 0;
                            if ($precio == 0 && isset($fila['costo'])) {
                                $precio = $fila['costo'];
                            }
                            
                            // Validar que tengamos los datos mínimos necesarios
                            if ($productoid && $cantidad > 0 && $precio > 0) {
                                $insertCompra = $c_compra->insertProductosCompras($productoid, $insertarCompra, $cantidad, $precio);
                            }
                        }
                        if ($insertCompra) {
                            echo json_encode(array('resp' => true, 'msj' => 'Registro exitoso 135'));
                        } else {
                            echo json_encode(array('resp' => false, 'msj' => 'Ocurrio un Error 137'));
                        }
                    } elseif ($tipo_pago == 2) {
                        // Primero insertar los productos
                        $insertCompra = false;
                        foreach ($array_detalle as $fila) {
                            // Normalizar el ID del producto
                            $productoid = isset($fila['productoid']) ? $fila['productoid'] : null;
                            
                            // Normalizar cantidad
                            $cantidad = isset($fila['cantidad']) ? $fila['cantidad'] : 0;
                            
                            // Normalizar precio (puede venir como 'precio' o 'costo')
                            $precio = isset($fila['precio']) ? $fila['precio'] : 0;
                            if ($precio == 0 && isset($fila['costo'])) {
                                $precio = $fila['costo'];
                            }
                            
                            // Validar que tengamos los datos mínimos necesarios
                            if ($productoid && $cantidad > 0 && $precio > 0) {
                                $insertCompra = $c_compra->insertProductosCompras($productoid, $insertarCompra, $cantidad, $precio);
                            }
                        }
                        
                        // Luego insertar los días de pago
                        $insertDiasCompra = false;
                        foreach ($listaPagos as $fila) {
                            $insertDiasCompra = $c_compra->insertDiasCompras($insertarCompra, $fila['monto'], $fila['fecha']);
                        }
                        if ($insertDiasCompra && $insertCompra) {
                            echo json_encode(array('resp' => true, 'msj' => 'Registro exitoso'));
                        } else {
                            echo json_encode(array('resp' => false, 'msj' => 'Ocurrio un Error'));
                        }
                    }
                } else {
                    echo json_encode(array('resp' => false, 'msj' => 'Ocurrio un Error'));
                }
            } else {
                echo json_encode(array('resp' => false, 'msj' => 'Ocurrio un Error'));
            }
        } else {
            echo json_encode(array('resp' => false, 'msj' => 'Llene todos los campos'));
        }



        /*   */
        /*   echo json_encode(array($_POST['num_doc'])); */
        /*    $id_empresa = $_SESSION['id_empresa'];
        $c_cliente->setIdEmpresa($id_empresa);
        $c_cliente->setDocumento(filter_input(INPUT_POST, 'num_doc'));
        $c_cliente->setDatos(filter_input(INPUT_POST, 'nom_cli'));
        $c_cliente->setDireccion(filter_input(INPUT_POST, 'dir_cli'));
        $c_cliente->setDireccion2(filter_input(INPUT_POST, 'dir2_cli'));

        return json_encode(array($c_cliente->setDocumento(filter_input(INPUT_POST, 'num_doc'))));
        if ($c_cliente->getDocumento() == "") {
            $c_cliente->setDocumento("SD" . $c_varios->generarCodigo(5));
            $c_cliente->insertar();
        } else {
            if (!$c_cliente->verificarDocumento()) {
                $c_cliente->insertar();
            }
        }

        $direccionselk = $_POST['dir_cli'] !== null ? $_POST['dir_cli'] : '-'; */
    }

    public function getAll()
    {
        $where = ($_SESSION['rol'] == 1) ? "" : "and c.sucursal = {$_SESSION["sucursal"]} ";
        $sql = "SELECT c.id_compra,c.fecha_emision,c.fecha_vencimiento,c.serie,c.numero,c.total,c.estado_recepcion,COALESCE(NULLIF(p.nombre_comercial, ''), p.razon_social) as razon_social FROM compras AS c LEFT JOIN proveedores AS p ON
        c.id_proveedor=p.proveedor_id where c.id_empresa='{$_SESSION['id_empresa']}' $where";
        //echo $sql;
        return $this->conectar->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Estado de recepción de una compra: por producto, lo pedido, lo ya
     * recibido, lo rechazado y lo pendiente de recepcionar.
     */
    public function recepcionEstado()
    {
        $id = intval($_POST['id'] ?? 0);
        // Solo cuentan las recepciones VIGENTES (estado='1'); las anuladas no suman
        $sql = "SELECT pc.id_producto, p.codigo, p.descripcion, pc.cantidad AS pedida,
                       IFNULL((SELECT SUM(cr.cantidad_recibida) FROM compras_recepciones cr
                               WHERE cr.id_compra = pc.id_compra AND cr.id_producto = pc.id_producto AND cr.estado='1'), 0) AS recibida,
                       IFNULL((SELECT SUM(cr.cantidad_rechazada) FROM compras_recepciones cr
                               WHERE cr.id_compra = pc.id_compra AND cr.id_producto = pc.id_producto AND cr.estado='1'), 0) AS rechazada
                FROM productos_compras pc
                INNER JOIN productos p ON p.id_producto = pc.id_producto
                WHERE pc.id_compra = '$id'";
        $productos = $this->conectar->query($sql)->fetch_all(MYSQLI_ASSOC);
        foreach ($productos as &$prod) {
            $prod['pendiente'] = max(0, floatval($prod['pedida']) - floatval($prod['recibida']) - floatval($prod['rechazada']));
        }
        $estado = $this->conectar->query("SELECT estado_recepcion FROM compras WHERE id_compra = '$id'")->fetch_assoc();

        // Historial de recepciones (vigentes y anuladas — el registro nunca se borra)
        $sqlHist = "SELECT cr.recepcion_id, cr.id_producto, p.codigo, p.descripcion,
                           cr.cantidad_recibida, cr.cantidad_rechazada, cr.motivo_rechazo,
                           cr.fecha, cr.estado, IFNULL(u.usuario, '-') AS usuario
                    FROM compras_recepciones cr
                    INNER JOIN productos p ON p.id_producto = cr.id_producto
                    LEFT JOIN usuarios u ON u.usuario_id = cr.id_usuario
                    WHERE cr.id_compra = '$id'
                    ORDER BY cr.recepcion_id DESC";
        $historial = $this->conectar->query($sqlHist)->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'estado' => $estado ? $estado['estado_recepcion'] : 'p',
            'productos' => $productos,
            'historial' => $historial,
        ]);
    }

    /** Recalcula el estado de recepción de la compra (solo recepciones vigentes). */
    private function recalcularEstadoRecepcion($idCompra)
    {
        $idCompra = intval($idCompra);
        $resT = $this->conectar->query("SELECT
                (SELECT IFNULL(SUM(cantidad),0) FROM productos_compras WHERE id_compra = '$idCompra') AS pedida,
                (SELECT IFNULL(SUM(cantidad_recibida + cantidad_rechazada),0) FROM compras_recepciones WHERE id_compra = '$idCompra' AND estado='1') AS procesada");
        $tot = $resT->fetch_assoc();
        $nuevoEstado = 'p';
        if (floatval($tot['procesada']) >= floatval($tot['pedida']) - 0.001) {
            $nuevoEstado = 'c';
        } elseif (floatval($tot['procesada']) > 0) {
            $nuevoEstado = 'x';
        }
        $this->conectar->query("UPDATE compras SET estado_recepcion = '$nuevoEstado' WHERE id_compra = '$idCompra'");
        return $nuevoEstado;
    }

    /**
     * ANULA una recepción: revierte el stock recibido, marca la recepción y su
     * kardex como ANULADOS (nunca se borran) y registra el contramovimiento.
     * Solo administradores.
     */
    public function recepcionAnular()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            echo json_encode(['res' => false, 'msg' => 'Solo administradores pueden anular recepciones']);
            return;
        }
        $id = intval($_POST['recepcion_id'] ?? 0);
        $res = $this->conectar->query("SELECT * FROM compras_recepciones WHERE recepcion_id = $id");
        if (!$res || $res->num_rows == 0) {
            echo json_encode(['res' => false, 'msg' => 'Recepción no encontrada']);
            return;
        }
        $rec = $res->fetch_assoc();
        if ($rec['estado'] !== '1') {
            echo json_encode(['res' => false, 'msg' => 'Esta recepción ya está anulada']);
            return;
        }

        require_once "app/models/Kardex.php";
        $kardex = new Kardex($this->conectar);
        $recibida = floatval($rec['cantidad_recibida']);

        if ($recibida > 0) {
            $this->conectar->query("UPDATE productos SET cantidad = cantidad - $recibida WHERE id_producto = '{$rec['id_producto']}'");
            $kardex->anularPorReferencia('recepcion:' . $id, 'i');
            $kardex->registrar($rec['id_producto'], 'e', 'Anulacion de recepcion', $recibida, 'recepcion:' . $id, "Anulación de la recepción #$id (compra #{$rec['id_compra']})");
        }
        $this->conectar->query("UPDATE compras_recepciones SET estado='0' WHERE recepcion_id = $id");
        $nuevoEstado = $this->recalcularEstadoRecepcion($rec['id_compra']);

        echo json_encode(['res' => true, 'msg' => 'Recepción anulada y stock revertido', 'estado' => $nuevoEstado]);
    }

    /**
     * EDITA una recepción: anula la original (revierte stock) y registra una nueva
     * con las cantidades corregidas. Solo administradores.
     * POST: recepcion_id, recibida, rechazada, motivo
     */
    public function recepcionEditar()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            echo json_encode(['res' => false, 'msg' => 'Solo administradores pueden editar recepciones']);
            return;
        }
        $id = intval($_POST['recepcion_id'] ?? 0);
        $recibida = abs(floatval($_POST['recibida'] ?? 0));
        $rechazada = abs(floatval($_POST['rechazada'] ?? 0));
        $motivo = $this->conectar->real_escape_string(trim($_POST['motivo'] ?? ''));

        if ($recibida <= 0 && $rechazada <= 0) {
            echo json_encode(['res' => false, 'msg' => 'Ingrese al menos una cantidad']);
            return;
        }
        if ($rechazada > 0 && $motivo === '') {
            echo json_encode(['res' => false, 'msg' => 'Indique el motivo del rechazo']);
            return;
        }

        $res = $this->conectar->query("SELECT * FROM compras_recepciones WHERE recepcion_id = $id");
        if (!$res || $res->num_rows == 0) {
            echo json_encode(['res' => false, 'msg' => 'Recepción no encontrada']);
            return;
        }
        $rec = $res->fetch_assoc();
        if ($rec['estado'] !== '1') {
            echo json_encode(['res' => false, 'msg' => 'No se puede editar una recepción anulada']);
            return;
        }

        // Validar contra lo pendiente SIN contar esta recepción
        $resP = $this->conectar->query("SELECT pc.cantidad AS pedida,
                IFNULL((SELECT SUM(cr.cantidad_recibida + cr.cantidad_rechazada) FROM compras_recepciones cr
                        WHERE cr.id_compra = '{$rec['id_compra']}' AND cr.id_producto = '{$rec['id_producto']}'
                          AND cr.estado='1' AND cr.recepcion_id != $id), 0) AS procesada
                FROM productos_compras pc
                WHERE pc.id_compra = '{$rec['id_compra']}' AND pc.id_producto = '{$rec['id_producto']}'");
        $datos = $resP->fetch_assoc();
        $pendiente = floatval($datos['pedida']) - floatval($datos['procesada']);
        if ($recibida + $rechazada > $pendiente + 0.001) {
            echo json_encode(['res' => false, 'msg' => "Las cantidades exceden lo pendiente ($pendiente)"]);
            return;
        }

        require_once "app/models/Kardex.php";
        $kardex = new Kardex($this->conectar);
        $idUsuario = isset($_SESSION['usuario_fac']) ? intval($_SESSION['usuario_fac']) : 'NULL';

        // 1) Anular la original (revierte stock recibido)
        $recibidaOrig = floatval($rec['cantidad_recibida']);
        if ($recibidaOrig > 0) {
            $this->conectar->query("UPDATE productos SET cantidad = cantidad - $recibidaOrig WHERE id_producto = '{$rec['id_producto']}'");
            $kardex->anularPorReferencia('recepcion:' . $id, 'i');
            $kardex->registrar($rec['id_producto'], 'e', 'Anulacion de recepcion', $recibidaOrig, 'recepcion:' . $id, "Anulado por corrección de la recepción #$id");
        }
        $this->conectar->query("UPDATE compras_recepciones SET estado='0' WHERE recepcion_id = $id");

        // 2) Registrar la recepción corregida
        $this->conectar->query("INSERT INTO compras_recepciones (id_compra, id_producto, cantidad_recibida, cantidad_rechazada, motivo_rechazo, id_usuario)
                VALUES ('{$rec['id_compra']}', '{$rec['id_producto']}', '$recibida', '$rechazada', " . ($motivo !== '' ? "'$motivo'" : "NULL") . ", $idUsuario)");
        $nuevoId = $this->conectar->insert_id;
        if ($recibida > 0) {
            $this->conectar->query("UPDATE productos SET cantidad = cantidad + $recibida WHERE id_producto = '{$rec['id_producto']}'");
            $kardex->registrar($rec['id_producto'], 'i', 'Recepcion de compra', $recibida, 'recepcion:' . $nuevoId, "Corrección de la recepción #$id (compra #{$rec['id_compra']})");
        }
        $nuevoEstado = $this->recalcularEstadoRecepcion($rec['id_compra']);

        echo json_encode(['res' => true, 'msg' => 'Recepción corregida', 'estado' => $nuevoEstado]);
    }

    /**
     * Registra una recepción (total o parcial) de mercadería. Solo admin (1) o almacén (6).
     * POST: id_compra, items = JSON [{id_producto, recibida, rechazada, motivo}]
     * - Lo recibido SUMA stock y se registra en el kardex como Compra.
     * - Lo rechazado NO entra al stock; queda registrado con su motivo.
     */
    public function recepcionRegistrar()
    {
        // Solo admin: el rol ALMACEN (6) consulta las compras pero no recepciona
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            echo json_encode(['res' => false, 'msg' => 'No tiene permisos para recepcionar mercadería']);
            return;
        }
        $idCompra = intval($_POST['id_compra'] ?? 0);
        $items = json_decode($_POST['items'] ?? '[]', true);
        if ($idCompra <= 0 || !is_array($items) || empty($items)) {
            echo json_encode(['res' => false, 'msg' => 'Datos de recepción inválidos']);
            return;
        }

        $resC = $this->conectar->query("SELECT estado_recepcion FROM compras WHERE id_compra = '$idCompra'");
        if (!$resC || $resC->num_rows == 0) {
            echo json_encode(['res' => false, 'msg' => 'Compra no encontrada']);
            return;
        }
        if ($resC->fetch_assoc()['estado_recepcion'] === 'c') {
            echo json_encode(['res' => false, 'msg' => 'Esta compra ya está completamente recepcionada']);
            return;
        }

        require_once "app/models/Kardex.php";
        $kardex = new Kardex($this->conectar);
        $idUsuario = isset($_SESSION['usuario_fac']) ? intval($_SESSION['usuario_fac']) : 'NULL';
        $procesados = 0;

        foreach ($items as $item) {
            $idProducto = intval($item['id_producto'] ?? 0);
            $recibida = abs(floatval($item['recibida'] ?? 0));
            $rechazada = abs(floatval($item['rechazada'] ?? 0));
            $motivo = $this->conectar->real_escape_string(trim($item['motivo'] ?? ''));
            if ($idProducto <= 0 || ($recibida <= 0 && $rechazada <= 0)) {
                continue;
            }

            // Validar contra lo pendiente real (pedido - recibido - rechazado, solo vigentes)
            $resP = $this->conectar->query("SELECT pc.cantidad AS pedida,
                    IFNULL((SELECT SUM(cr.cantidad_recibida + cr.cantidad_rechazada) FROM compras_recepciones cr
                            WHERE cr.id_compra = '$idCompra' AND cr.id_producto = '$idProducto' AND cr.estado='1'), 0) AS procesada
                    FROM productos_compras pc
                    WHERE pc.id_compra = '$idCompra' AND pc.id_producto = '$idProducto'");
            if (!$resP || $resP->num_rows == 0) {
                continue;
            }
            $datos = $resP->fetch_assoc();
            $pendiente = floatval($datos['pedida']) - floatval($datos['procesada']);
            if ($recibida + $rechazada > $pendiente + 0.001) {
                echo json_encode(['res' => false, 'msg' => "El producto $idProducto excede lo pendiente ($pendiente). No se registró nada de esta línea en adelante."]);
                return;
            }

            $this->conectar->query("INSERT INTO compras_recepciones (id_compra, id_producto, cantidad_recibida, cantidad_rechazada, motivo_rechazo, id_usuario)
                    VALUES ('$idCompra', '$idProducto', '$recibida', '$rechazada', " . ($motivo !== '' ? "'$motivo'" : "NULL") . ", $idUsuario)");
            $idRecepcion = $this->conectar->insert_id;

            if ($recibida > 0) {
                $this->conectar->query("UPDATE productos SET cantidad = cantidad + $recibida WHERE id_producto = '$idProducto'");
                $obsK = "Recepción #$idRecepcion de la compra #$idCompra" . ($rechazada > 0 ? " (rechazadas: $rechazada)" : '');
                $kardex->registrar($idProducto, 'i', 'Recepcion de compra', $recibida, 'recepcion:' . $idRecepcion, $obsK);
            }
            $procesados++;
        }

        if ($procesados == 0) {
            echo json_encode(['res' => false, 'msg' => 'No se registró ninguna cantidad']);
            return;
        }

        $nuevoEstado = $this->recalcularEstadoRecepcion($idCompra);

        echo json_encode(['res' => true, 'msg' => 'Recepción registrada', 'estado' => $nuevoEstado]);
    }

    public function getDetalle()
    {
        $sql = "SELECT pc.id_producto_venta,p.descripcion,pc.cantidad,pc.precio FROM productos_compras AS pc LEFT JOIN productos AS p ON
        pc.id_producto=p.id_producto LEFT JOIN compras AS c ON
        pc.id_compra=c.id_compra WHERE c.id_compra = '{$_POST['id']}'";
        return json_encode($this->conectar->query($sql)->fetch_all(MYSQLI_ASSOC));

    }

    public function buscarProducto()
    {
        $dataProducto = $_POST['producto'];
        if ($dataProducto !== '') {
            $sql = "SELECT * from productos 
        where codigo LIKE '%$dataProducto%' AND almacen = 1" ;

            $getAll = $this->conectar->query($sql)->fetch_all(MYSQLI_ASSOC);
            if (!empty($getAll)) {
                $res = array("res" => true, "data" => $getAll);
                echo json_encode($res);
            } else {
                $res = array("res" => false, "msj" => 'No se encontró ningun producto');
                echo json_encode($res);
            }
        }
    }

    public function actualizarCompra()
    {
        // El rol ALMACEN (6) no edita compras, solo las consulta
        if (isset($_SESSION['rol']) && $_SESSION['rol'] == 6) {
            echo json_encode(['resp' => false, 'msj' => 'Su rol no permite editar compras']);
            return;
        }
        $id = $_POST['id'];
        $c_compra = new Compra();
        $c_tido = new DocumentoEmpresa();

        $idProveedor = '';
        $nombre_comercial = isset($_POST['nombre_comercial']) ? $_POST['nombre_comercial'] : '';
        
        if ($_POST['num_doc'] !== '') {
            $empresaExistente = $c_tido->consultarProveedor($_POST['num_doc']);
            if (!empty($empresaExistente)) {
                $idProveedor = $empresaExistente[0]['proveedor_id'];
                // Actualizar nombre comercial si cambió
                if ($nombre_comercial !== '') {
                    $sql = "UPDATE proveedores SET nombre_comercial = '$nombre_comercial' WHERE proveedor_id = '$idProveedor'";
                    $this->conectar->query($sql);
                }
            } else {
                $insert = $c_tido->insertarProveedor($_POST['num_doc'], $_POST['nom_cli'], $nombre_comercial);
                $idProveedor = $insert;
            }
        }

        $id_tido = $_POST['tipo_doc'] !== '' ? $_POST['tipo_doc'] : 2;
        $tipo_pago = $_POST['tipo_pago'] !== '' ? $_POST['tipo_pago'] : '';
        $fecha = isset($_POST['fecha'])  ? $_POST['fecha'] : '';
        $fechaVen = isset($_POST['fechaVen'])  ? $_POST['fechaVen'] : '';
        $dir_cli = $_POST['dir_cli'] !== '' ? $_POST['dir_cli'] : '-';
        $serie = $_POST['serie'] !== '' ? $_POST['serie'] : '';
        $numero = $_POST['numero'] !== '' ? $_POST['numero'] : '';
        $total = $_POST['total'] !== 0 ? intval($_POST['total']) : 0;
        $moneda = $_POST['moneda'] !== '' ? $_POST['moneda'] : '';

        if ($id_tido !== '' && $tipo_pago !== '' && $fecha !== '' && $fechaVen !== '' && $dir_cli !== '' && $serie !== '' && $numero !== '' && $total > 0 && $moneda !== '' && $idProveedor !== '') {
            
            // Actualizar la compra
            $sqlUpdate = "UPDATE compras SET 
                         id_tido = '$id_tido',
                         id_tipo_pago = '$tipo_pago',
                         id_proveedor = '$idProveedor',
                         fecha_emision = '$fecha',
                         fecha_vencimiento = '$fechaVen',
                         direccion = '$dir_cli',
                         serie = '$serie',
                         numero = '$numero',
                         total = '$total',
                         moneda = '$moneda'
                         WHERE id_compra = '$id'";
            
            $resultUpdate = $this->conectar->query($sqlUpdate);
            
            if ($resultUpdate) {
                // Eliminar productos anteriores
                $sqlDeleteProductos = "DELETE FROM productos_compras WHERE id_compra = '$id'";
                $this->conectar->query($sqlDeleteProductos);
                
                // Insertar nuevos productos
                $array_detalle = json_decode($_POST['listaPro'], true);
                $insertCompra = false;
                foreach ($array_detalle as $fila) {
                    // Normalizar el ID del producto (puede venir como 'productoid' o 'productoid')
                    $productoid = isset($fila['productoid']) ? $fila['productoid'] : null;
                    
                    // Normalizar cantidad
                    $cantidad = isset($fila['cantidad']) ? $fila['cantidad'] : 0;
                    
                    // Normalizar precio (puede venir como 'precio' o 'costo')
                    $precio = isset($fila['precio']) ? $fila['precio'] : 0;
                    if ($precio == 0 && isset($fila['costo'])) {
                        $precio = $fila['costo'];
                    }
                    
                    // Validar que tengamos los datos mínimos necesarios
                    if ($productoid && $cantidad > 0 && $precio > 0) {
                        $insertCompra = $c_compra->insertProductosCompras($productoid, $id, $cantidad, $precio);
                    }
                }
                
                // Si es pago a crédito, actualizar días de pago
                if ($tipo_pago == 2) {
                    // Eliminar solo cuotas pendientes (no pagadas), preservar las pagadas
                    $sqlDeleteDias = "DELETE FROM dias_compras WHERE id_compra = '$id' AND estado = '0'";
                    $this->conectar->query($sqlDeleteDias);
                    
                    // Insertar solo cuotas pendientes (estado != '1' y monto > 0)
                    $listaPagos = json_decode($_POST['dias_lista'], true);
                    foreach ($listaPagos as $fila) {
                        $esPagada = isset($fila['estado']) && $fila['estado'] == '1';
                        $montoValido = isset($fila['monto']) && floatval($fila['monto']) > 0;
                        if (!$esPagada && $montoValido) {
                            $c_compra->insertDiasCompras($id, $fila['monto'], $fila['fecha']);
                        }
                    }
                }
                
                echo json_encode(array('resp' => true, 'msj' => 'Actualización exitosa'));
            } else {
                echo json_encode(array('resp' => false, 'msj' => 'Error al actualizar'));
            }
        } else {
            echo json_encode(array('resp' => false, 'msj' => 'Llene todos los campos'));
        }
    }

    public function obtenerCompra()
    {
        $id = $_POST['id'];
        
        // Obtener datos de la compra
        $sqlCompra = "SELECT c.*, p.ruc, COALESCE(NULLIF(p.nombre_comercial, ''), p.razon_social) as razon_social 
                     FROM compras c
                     INNER JOIN proveedores p ON p.proveedor_id = c.id_proveedor
                     WHERE c.id_compra = '$id'";
        
        $resultCompra = $this->conectar->query($sqlCompra);
        
        if ($resultCompra && $resultCompra->num_rows > 0) {
            $compra = $resultCompra->fetch_assoc();
            
            // Obtener productos de la compra
            $sqlProductos = "SELECT pc.*, p.descripcion, p.codigo
                            FROM productos_compras pc
                            INNER JOIN productos p ON p.id_producto = pc.id_producto
                            WHERE pc.id_compra = '$id'";
            
            $resultProductos = $this->conectar->query($sqlProductos);
            $productos = [];
            
            if ($resultProductos) {
                while ($row = $resultProductos->fetch_assoc()) {
                    $productos[] = [
                        'productoid' => $row['id_producto'],
                        'descripcion' => $row['descripcion'],
                        'codigo' => $row['codigo'],
                        'cantidad' => $row['cantidad'],
                        'precio' => $row['precio'],
                        'costo' => $row['costo']
                    ];
                }
            }
            
            // Obtener cuotas (dias_compras) para créditos
            $sqlDias = "SELECT monto, fecha, estado FROM dias_compras WHERE id_compra = '$id' ORDER BY dias_compra_id ASC";
            $resultDias = $this->conectar->query($sqlDias);
            $diasLista = [];
            if ($resultDias) {
                while ($row = $resultDias->fetch_assoc()) {
                    $diasLista[] = $row;
                }
            }

            echo json_encode([
                'res' => true,
                'compra' => $compra,
                'productos' => $productos,
                'dias_lista' => $diasLista
            ]);
        } else {
            echo json_encode([
                'res' => false,
                'msg' => 'Compra no encontrada'
            ]);
        }
    }
}
