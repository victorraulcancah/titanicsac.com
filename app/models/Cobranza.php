<?php

class Cobranza
{
    /**
     * Desde esta fecha un PEDIDO ya no genera cuenta por cobrar: la deuda nace recien
     * cuando el pedido se convierte en venta (ahi vive en dias_ventas). Los pedidos
     * anteriores se siguen mostrando para no perder la deuda historica pendiente.
     */
    const FECHA_CORTE_CXC_PEDIDOS = '2026-09-02';

    public $conectar;
    public function __construct()
    {
        $this->conectar = (new Conexion())->getConexion();
    }

    /**
     * Condicion que deja fuera de Cuentas por Cobrar a los pedidos nuevos.
     * Un pedido posterior al corte solo sigue visible si ya tiene algun cobro
     * registrado, para que ningun cobro quede sin pantalla donde consultarse.
     */
    private function condicionPedidosCxC()
    {
        $corte = self::FECHA_CORTE_CXC_PEDIDOS;
        return " AND (DATE(COALESCE(co.fecha_registro, co.fecha)) < '$corte'
                     OR EXISTS (SELECT 1 FROM cuotas_cotizacion cxc WHERE cxc.id_coti = co.cotizacion_id AND cxc.estado = 1))";
    }
    public function getAllCobranzas()
{
    try {
        $condPedidos = $this->condicionPedidosCxC();
        if ($_SESSION["rol"] == 4) {
            $sql = "
            (SELECT 
                'v' AS tipo_co,
                v.id_venta,
                CONCAT(v.serie, ' | ', v.numero) AS factura,
                MAX(v.fecha_emision) AS fecha_emision,
                MAX(v.fecha_vencimiento) AS fecha_vencimiento,
                CONCAT(c.documento, ' | ', c.datos) AS cliente,
                c.mercado AS mercado,
                IFNULL(co_v.numero, '') AS pedido,
                '' AS nota_venta,
                MAX(v.total) AS total,
                IFNULL(us_ped.usuario, IFNULL(us_v.usuario, '')) AS vendedor,
                SUM(CASE WHEN dv.estado = '1' THEN dv.monto ELSE 0 END) AS pagado,
                (MAX(v.total) - SUM(CASE WHEN dv.estado = '1' THEN dv.monto ELSE 0 END)) AS saldo
            FROM ventas AS v
            INNER JOIN dias_ventas AS dv ON v.id_venta = dv.id_venta 
            INNER JOIN clientes AS c ON v.id_cliente = c.id_cliente
            LEFT JOIN cotizaciones AS co_v ON co_v.cotizacion_id = v.id_coti
            LEFT JOIN usuarios AS us_ped ON us_ped.usuario_id = co_v.id_usuario
            LEFT JOIN usuarios AS us_v ON us_v.usuario_id = v.id_vendedor
            WHERE v.estado = 1 
              AND v.id_tipo_pago = 2  
              AND v.id_empresa = '{$_SESSION['id_empresa']}'
            GROUP BY v.id_venta, c.datos, c.mercado, co_v.numero, us_ped.usuario, us_v.usuario)
            
            UNION ALL
            
            (SELECT 
                'c' AS tipo_co,
                co.cotizacion_id AS id_venta,
                CONCAT('#', co.numero) AS factura,
                co.fecha AS fecha_emision,
                (
                    SELECT fecha 
                    FROM cuotas_cotizacion cc 
                    WHERE cc.id_coti = co.cotizacion_id  
                    ORDER BY fecha DESC 
                    LIMIT 1
                ) AS fecha_vencimiento,
                CONCAT(c.documento, ' | ', c.datos) AS cliente,
                c.mercado AS mercado,
                co.numero AS pedido,
                IFNULL(vv.nota_venta, '') AS nota_venta,
                co.total,
                if(us.usuario_id is null,'Usuario Eliminado',us.usuario) AS vendedor,
                (
                    SELECT IFNULL(SUM(cc.monto), 0) 
                    FROM cuotas_cotizacion cc 
                    WHERE cc.id_coti = co.cotizacion_id AND cc.estado = 1
                ) AS pagado,
                (co.total - (
                    SELECT IFNULL(SUM(cc.monto), 0) 
                    FROM cuotas_cotizacion cc 
                    WHERE cc.id_coti = co.cotizacion_id AND cc.estado = 1
                )) AS saldo
            FROM cotizaciones co
            INNER JOIN clientes AS c ON c.id_cliente = co.id_cliente
            LEFT JOIN usuarios us ON us.usuario_id = co.id_usuario
            -- Nota de venta del pedido: un solo join agrupado. Como ventas.id_coti no esta
            -- indexado, una subconsulta por fila tardaba casi un minuto sobre 54 mil pedidos.
            LEFT JOIN (SELECT id_coti, MIN(CONCAT(serie, ' | ', numero)) AS nota_venta
                       FROM ventas WHERE estado = 1 AND id_coti > 0 GROUP BY id_coti) vv
                   ON vv.id_coti = co.cotizacion_id
            WHERE co.id_tipo_pago = 2 AND co.estado!=2
            $condPedidos)
            
            ORDER BY mercado ASC, cliente ASC, fecha_emision ASC";
        } else {
            $sql = "
            (SELECT 
                'v' AS tipo_co,
                v.id_venta,
                CONCAT(v.serie, ' | ', v.numero) AS factura,
                MAX(v.fecha_emision) AS fecha_emision,
                MAX(v.fecha_vencimiento) AS fecha_vencimiento,
                CONCAT(c.documento, ' | ', c.datos) AS cliente,
                c.mercado AS mercado,
                IFNULL(co_v.numero, '') AS pedido,
                '' AS nota_venta,
                MAX(v.total) AS total,
                IFNULL(us_ped.usuario, IFNULL(us_v.usuario, '')) AS vendedor,
                SUM(CASE WHEN dv.estado = '1' THEN dv.monto ELSE 0 END) AS pagado,
                (MAX(v.total) - SUM(CASE WHEN dv.estado = '1' THEN dv.monto ELSE 0 END)) AS saldo
            FROM ventas AS v
            INNER JOIN dias_ventas AS dv ON v.id_venta = dv.id_venta 
            INNER JOIN clientes AS c ON v.id_cliente = c.id_cliente
            LEFT JOIN cotizaciones AS co_v ON co_v.cotizacion_id = v.id_coti
            LEFT JOIN usuarios AS us_ped ON us_ped.usuario_id = co_v.id_usuario
            LEFT JOIN usuarios AS us_v ON us_v.usuario_id = v.id_vendedor
            WHERE v.estado = 1 
              AND v.id_tipo_pago = 2 
              AND v.sucursal = '{$_SESSION['sucursal']}'
              AND v.id_empresa = '{$_SESSION['id_empresa']}'
            GROUP BY v.id_venta, c.datos, c.mercado, co_v.numero, us_ped.usuario, us_v.usuario)
            
            UNION ALL
            
            (SELECT 
                'c' AS tipo_co,
                co.cotizacion_id AS id_venta,
                CONCAT('#', co.numero) AS factura,
                co.fecha AS fecha_emision,
                (
                    SELECT fecha 
                    FROM cuotas_cotizacion cc 
                    WHERE cc.id_coti = co.cotizacion_id  
                    ORDER BY fecha DESC 
                    LIMIT 1
                ) AS fecha_vencimiento,
                CONCAT(c.documento, ' | ', c.datos) AS cliente,
                c.mercado AS mercado,
                co.numero AS pedido,
                IFNULL(vv.nota_venta, '') AS nota_venta,
                co.total,
                if(us.usuario_id is null,'Usuario Eliminado',us.usuario) AS vendedor,
                (
                    SELECT IFNULL(SUM(cc.monto), 0) 
                    FROM cuotas_cotizacion cc 
                    WHERE cc.id_coti = co.cotizacion_id AND cc.estado = 1
                ) AS pagado,
                (co.total - (
                    SELECT IFNULL(SUM(cc.monto), 0) 
                    FROM cuotas_cotizacion cc 
                    WHERE cc.id_coti = co.cotizacion_id AND cc.estado = 1
                )) AS saldo
            FROM cotizaciones co
            INNER JOIN clientes AS c ON c.id_cliente = co.id_cliente
            LEFT JOIN usuarios us ON us.usuario_id = co.id_usuario
            -- Nota de venta del pedido: un solo join agrupado. Como ventas.id_coti no esta
            -- indexado, una subconsulta por fila tardaba casi un minuto sobre 54 mil pedidos.
            LEFT JOIN (SELECT id_coti, MIN(CONCAT(serie, ' | ', numero)) AS nota_venta
                       FROM ventas WHERE estado = 1 AND id_coti > 0 GROUP BY id_coti) vv
                   ON vv.id_coti = co.cotizacion_id
            WHERE co.id_tipo_pago = 2 AND co.estado!=2
            $condPedidos)
            
            ORDER BY mercado ASC, cliente ASC, fecha_emision ASC";
        }

        $fila = mysqli_query($this->conectar, $sql);
        $lista = mysqli_fetch_all($fila, MYSQLI_ASSOC);

        return $lista;
    } catch (Exception $e) {
        echo $e->getTraceAsString();
    }
}
 public function getAllDeudas()
    {
        try {
            $condPedidos = $this->condicionPedidosCxC();
            $sql = "SELECT 
                        tb.cliente as cliente,
                        SUM(tb.total) AS total,
                        SUM(tb.pagado) AS pagado,
                        SUM(tb.total - tb.pagado) AS saldo
            FROM (SELECT 
                'c' AS tipo_co,
                co.cotizacion_id AS id_venta,
                CONCAT('#',co.numero) AS factura,
                us.usuario vendedor, 
                co.fecha AS fecha_emision,
                (SELECT  fecha FROM cuotas_cotizacion cc WHERE cc.id_coti = co.cotizacion_id  ORDER BY fecha DESC LIMIT 1)   AS fecha_vencimiento,
                CONCAT(c.documento ,' | ' ,c.datos) AS cliente,
                co.total,
                (SELECT IFNULL( SUM(cc.monto),0) FROM cuotas_cotizacion cc WHERE cc.id_coti = co.cotizacion_id AND cc.estado=1) pagado

                FROM cotizaciones co

                INNER JOIN clientes AS c ON c.id_cliente=co.id_cliente
                JOIN usuarios us ON us.usuario_id = co.id_usuario
                WHERE co.id_tipo_pago=2 AND co.estado!=2 $condPedidos) tb GROUP BY tb.cliente;";

            $fila = mysqli_query($this->conectar, $sql);
            $lista2 =  mysqli_fetch_all($fila, MYSQLI_ASSOC);

            return array_merge($lista2, array());
        } catch (Exception $e) {
            echo $e->getTraceAsString();
        }
    }
    
    public function getAllCobros($whereCliente,$whereVendedor,$fecha_inicio,$fecha_fin,$whereClientes,$whereDiasVisita,$whereRuta)
    {
        try {
            $sql = "
                SELECT 
                'c' AS tipo_co,
                co.cotizacion_id AS id_venta,
                CONCAT('#',co.numero) AS factura,
                us.usuario vendedor, 
                co.fecha AS fecha_emision,
                c.id_cliente,
                CONCAT(c.documento ,' | ' ,c.datos) AS cliente,
                co.total,
                cu.monto as pagado,
                IFNULL(cu.tipo_pago,'-') AS 'metodo_pago',
                IFNULL(c.dias_visitas,'') AS dias_visitas,IFNULL(c.id_ruta,'') AS id_ruta

                FROM cotizaciones co

                INNER JOIN clientes AS c ON c.id_cliente=co.id_cliente
                INNER JOIN cuotas_cotizacion cu ON cu.id_coti = co.cotizacion_id and cu.estado=1
                LEFT JOIN usuarios us ON us.usuario_id = cu.id_usuario
                WHERE co.id_tipo_pago=2 AND co.estado!=2
                AND cu.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' 
                $whereCliente
                $whereVendedor
                $whereClientes
                $whereDiasVisita
                $whereRuta
                ORDER BY CONCAT(c.documento ,' | ' ,c.datos) ASC,co.cotizacion_id DESC
            ";
            $fila = mysqli_query($this->conectar, $sql);
            $lista2 =  mysqli_fetch_all($fila, MYSQLI_ASSOC);

            return array_merge($lista2, array());
        } catch (Exception $e) {
            echo $e->getTraceAsString();
        }
    }
    #
    public function getAllCobrosByVendedor($whereCliente,$whereVendedor,$fecha_inicio,$fecha_fin,$whereClientes,$whereDiasVisita,$whereRuta)
    {
        try {
            $sql = "
                SELECT 
                'c' AS tipo_co,
                co.cotizacion_id AS id_venta,
                CONCAT('#',co.numero) AS factura,
                us.usuario vendedor, 
                co.fecha AS fecha_emision,
                cu.fecha AS fecha_pago,
                c.id_cliente,
                c.documento,
                c.datos AS nombre_cliente,
                CONCAT(c.documento ,' | ' ,c.datos) AS cliente,
                co.total,
                cu.monto as pagado,
                (
                select sum(cu1.monto) from cuotas_cotizacion cu1 where cu1.fecha<=cu.fecha and cu1.id_coti=cu.id_coti
                ) AS 'total_pagado',
                IFNULL(cu.tipo_pago,'-') AS 'metodo_pago',
                IFNULL(c.dias_visitas,'') AS dias_visitas,IFNULL(c.id_ruta,'') AS id_ruta

                FROM cotizaciones co

                INNER JOIN clientes AS c ON c.id_cliente=co.id_cliente
                INNER JOIN cuotas_cotizacion cu ON cu.id_coti = co.cotizacion_id and cu.estado=1
                LEFT JOIN usuarios us ON us.usuario_id = cu.id_usuario
                WHERE co.id_tipo_pago=2 AND co.estado!=2
                AND cu.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' 
                $whereCliente
                $whereVendedor
                $whereClientes
                $whereDiasVisita
                $whereRuta
                ORDER BY CONCAT(c.documento ,' | ' ,c.datos) ASC,co.cotizacion_id DESC
            ";
            $fila = mysqli_query($this->conectar, $sql);
            $lista2 =  mysqli_fetch_all($fila, MYSQLI_ASSOC);

            return array_merge($lista2, array());
        } catch (Exception $e) {
            echo $e->getTraceAsString();
        }
    }
    
    public function getAllDeudaByRuta($whereCliente,$id_vendedor, $whereVendedor, $fecha_inicio, $fecha_fin, $whereClientes, $whereDiasVisita, $whereRuta)
    {
        $filtroUsuarioVenta = ($whereVendedor!="") ? " AND v.id_vendedor={$id_vendedor} " : "";
        try {
            $sql = "SELECT 
                'v' AS tipo_co,
                v.id_venta,
                CONCAT(v.serie, ' | ', v.numero) AS factura,
                MAX(v.fecha_emision) AS fecha_emision,
                MAX(v.fecha_vencimiento) AS fecha_vencimiento,
                c.documento,
                c.datos AS cliente,
                c.mercado AS mercado,
                MAX(v.total) AS total,
                IFNULL(us_ped.usuario, IFNULL(us_v.usuario, '')) AS vendedor,
                SUM(CASE WHEN dv.estado = '1' THEN dv.monto ELSE 0 END) AS pagado,
                (MAX(v.total) - SUM(CASE WHEN dv.estado = '1' THEN dv.monto ELSE 0 END)) AS saldo
            FROM ventas AS v
            INNER JOIN dias_ventas AS dv ON v.id_venta = dv.id_venta 
            INNER JOIN clientes AS c ON v.id_cliente = c.id_cliente
            LEFT JOIN cotizaciones AS co_v ON co_v.cotizacion_id = v.id_coti
            LEFT JOIN usuarios AS us_ped ON us_ped.usuario_id = co_v.id_usuario
            LEFT JOIN usuarios AS us_v ON us_v.usuario_id = v.id_vendedor
            WHERE v.estado = 1 
            AND v.id_tipo_pago = 2 
            AND v.sucursal = '{$_SESSION['sucursal']}'
            AND v.id_empresa = '{$_SESSION['id_empresa']}'
            AND v.fecha_emision BETWEEN '$fecha_inicio' AND '$fecha_fin'
            $whereCliente
            $filtroUsuarioVenta
            $whereClientes
            $whereDiasVisita
            $whereRuta
            GROUP BY v.id_venta , c.datos, c.mercado, us_ped.usuario, us_v.usuario
            ORDER BY v.id_venta ASC,c.mercado ASC, c.datos ASC";

            $fila = mysqli_query($this->conectar, $sql);
            $lista = mysqli_fetch_all($fila, MYSQLI_ASSOC);

            // Consulta de cotizaciones
            $sql = "SELECT 
            'c' AS tipo_co,
            co.cotizacion_id AS id_venta,
            CONCAT('#', co.numero) AS factura,
            if(us.usuario_id is null,'Usuario Eliminado',us.usuario) AS vendedor,
            co.fecha AS fecha_emision,
            (
                SELECT fecha 
                FROM cuotas_cotizacion cc 
                WHERE cc.id_coti = co.cotizacion_id  
                ORDER BY fecha DESC 
                LIMIT 1
            ) AS fecha_vencimiento,
            c.documento,
            c.datos AS cliente,
            c.mercado AS mercado,
            co.total,
            (
                SELECT IFNULL(SUM(cc.monto), 0) 
                FROM cuotas_cotizacion cc 
                WHERE cc.id_coti = co.cotizacion_id AND cc.estado = 1
            ) AS pagado,
            (co.total - (
                SELECT IFNULL(SUM(cc.monto), 0) 
                FROM cuotas_cotizacion cc 
                WHERE cc.id_coti = co.cotizacion_id AND cc.estado = 1
            )) AS saldo
            FROM cotizaciones co
            INNER JOIN clientes AS c ON c.id_cliente = co.id_cliente
            LEFT JOIN usuarios us ON us.usuario_id = co.id_usuario
            WHERE co.id_tipo_pago = 2 AND co.estado!=2
            {$this->condicionPedidosCxC()}
            $whereCliente
            $whereVendedor
            $whereClientes
            $whereDiasVisita
            $whereRuta
            ORDER BY co.cotizacion_id DESC, c.mercado ASC, c.datos ASC";

            $fila = mysqli_query($this->conectar, $sql);
            $lista2 = mysqli_fetch_all($fila, MYSQLI_ASSOC);

            return array_merge($lista2, $lista);
        } catch (Exception $e) {
            echo $e->getTraceAsString();
        }
    }
    
    public function getAllByIdVenta($id,$tip)
    {
        try {
            error_log("=== getAllByIdVenta ===");
            error_log("ID: " . $id);
            error_log("Tipo: " . $tip);
            
            $sql ="";
            if ($tip=='c'){
                $sql = "SELECT *,cuota_coti_id as dias_venta_id,'c' as tipo_doc FROM cuotas_cotizacion WHERE id_coti = '$id'";
            }else{
                $sql = "SELECT  *,'v' as tipo_doc FROM dias_ventas WHERE id_venta = '$id'";
            }

            error_log("SQL: " . $sql);
            $fila = mysqli_query($this->conectar, $sql);
            
            if (!$fila) {
                error_log("ERROR en query: " . mysqli_error($this->conectar));
                return [];
            }
            
            $resultado = mysqli_fetch_all($fila, MYSQLI_ASSOC);
            error_log("Registros encontrados: " . count($resultado));
            error_log("Datos: " . json_encode($resultado));
            
            return $resultado;
        } catch (Exception $e) {
            error_log("EXCEPCIÓN: " . $e->getMessage());
            echo $e->getTraceAsString();
        }
    }
    public function pagarCuota($id)
    {
        // Obtener la fecha y hora actual del sistema
        date_default_timezone_set('America/Lima');
        $fecha_pago_real = date('Y-m-d H:i:s');
        
        // Actualizar el estado y guardar la fecha real del pago
        $sql = "UPDATE dias_ventas SET 
                estado = '1',
                fecha_pago_real = '$fecha_pago_real'
                WHERE dias_venta_id = $id";
        
        $result = $this->conectar->query($sql);
        if ($result) {
            return $result;
        }
    }
}
