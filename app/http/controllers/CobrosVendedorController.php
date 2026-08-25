<?php

class CobrosVendedorController extends Controller
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = (new Conexion())->getConexion();
    }

    // Obtener cobros por rango de fechas del vendedora
    public function obtenerCobrosPorRango()
    {
        $id_usuario = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : $_SESSION['usuario_fac'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        
        $cobros = [];
        
        // Cobros de ventas
        $sql_ventas = "SELECT 
                        dv.dias_venta_id,
                        dv.monto,
                        dv.tipo_pago,
                        dv.fecha_pago_real,
                        CONCAT(v.serie, '-', v.numero) as documento,
                        c.datos as cliente,
                        'Venta' as tipo_documento
                    FROM dias_ventas dv
                    INNER JOIN ventas v ON dv.id_venta = v.id_venta
                    INNER JOIN clientes c ON v.id_cliente = c.id_cliente
                    WHERE dv.id_usuario = '$id_usuario'
                    AND dv.estado = '1'
                    AND DATE(dv.fecha_pago_real) BETWEEN '$fecha_inicio' AND '$fecha_fin'
                    AND v.id_empresa = '{$_SESSION['id_empresa']}'
                    AND v.sucursal = '{$_SESSION['sucursal']}'
                    ORDER BY dv.fecha_pago_real DESC";
        
        $result_ventas = $this->conexion->query($sql_ventas);
        while ($row = $result_ventas->fetch_assoc()) {
            $cobros[] = $row;
        }
        
        // Cobros de cotizaciones
        $sql_cotizaciones = "SELECT 
                                cc.cuota_coti_id as dias_venta_id,
                                cc.monto,
                                cc.tipo_pago,
                                cc.fecha_pago_real,
                                CONCAT('COT-', cot.numero) as documento,
                                c.datos as cliente,
                                'Cotización' as tipo_documento
                            FROM cuotas_cotizacion cc
                            INNER JOIN cotizaciones cot ON cc.id_coti = cot.cotizacion_id
                            INNER JOIN clientes c ON cot.id_cliente = c.id_cliente
                            WHERE cc.id_usuario = '$id_usuario'
                            AND cc.estado = '1'
                            AND DATE(cc.fecha_pago_real) BETWEEN '$fecha_inicio' AND '$fecha_fin'
                            AND cot.id_empresa = '{$_SESSION['id_empresa']}'
                            AND cot.sucursal = '{$_SESSION['sucursal']}'
                            -- Pedido ya convertido: sus cobros viven en dias_ventas. Se comprueba contra la
                            -- tabla ventas (no contra cotizaciones.estado) para que funcione aunque el flag
                            -- del pedido quedara desincronizado.
                            AND NOT EXISTS (SELECT 1 FROM ventas v2 WHERE v2.id_coti = cot.cotizacion_id AND v2.estado = 1)
                            ORDER BY cc.fecha_pago_real DESC";
        
        $result_cotizaciones = $this->conexion->query($sql_cotizaciones);
        while ($row = $result_cotizaciones->fetch_assoc()) {
            $cobros[] = $row;
        }

        // Cobros ANULADOS: siguen visibles como constancia (el front los muestra tachados
        // y no los suma en los totales)
        $sql_anulados = "SELECT
                            ca.cobro_anulado_id AS dias_venta_id,
                            ca.monto,
                            ca.tipo_pago,
                            ca.fecha_pago_real,
                            CASE WHEN ca.tipo = 'v'
                                 THEN (SELECT CONCAT(v.serie, '-', v.numero) FROM ventas v WHERE v.id_venta = ca.id_documento)
                                 ELSE (SELECT CONCAT('COT-', co.numero) FROM cotizaciones co WHERE co.cotizacion_id = ca.id_documento)
                            END AS documento,
                            CASE WHEN ca.tipo = 'v'
                                 THEN (SELECT c.datos FROM ventas v INNER JOIN clientes c ON c.id_cliente = v.id_cliente WHERE v.id_venta = ca.id_documento)
                                 ELSE (SELECT c.datos FROM cotizaciones co INNER JOIN clientes c ON c.id_cliente = co.id_cliente WHERE co.cotizacion_id = ca.id_documento)
                            END AS cliente,
                            CASE WHEN ca.tipo = 'v' THEN 'Venta' ELSE 'Cotización' END AS tipo_documento,
                            1 AS anulado
                        FROM cobros_anulados ca
                        WHERE ca.id_usuario = '$id_usuario'
                        AND DATE(ca.fecha_pago_real) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        $result_anulados = $this->conexion->query($sql_anulados);
        if ($result_anulados) {
            while ($row = $result_anulados->fetch_assoc()) {
                $cobros[] = $row;
            }
        }

        // Ordenar todos los cobros por fecha
        usort($cobros, function($a, $b) {
            return strtotime($b['fecha_pago_real']) - strtotime($a['fecha_pago_real']);
        });
        
        return json_encode($cobros);
    }
}
