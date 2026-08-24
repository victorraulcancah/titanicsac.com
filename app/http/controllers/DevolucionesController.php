<?php

require_once "utils/lib/exel/vendor/autoload.php";
class DevolucionesController extends Controller
{
    private $conectar;

    public function __construct()
    {
        $this->conectar=(new Conexion())->getConexion();
    }

    /** Listado por DOCUMENTO: una fila por venta con sus devoluciones agrupadas
     *  (el detalle de productos se ve en el modal de confirmación) */
    public function render(){
        try {
            $sql = "
            SELECT
            v.id_venta,
            CONCAT(v.serie, ' | ', v.numero) AS factura,
            v.fecha_emision,
            co.numero AS pedido,
            CONCAT(IFNULL(c.documento,''), ' | ', IFNULL(c.datos,'SIN CLIENTE')) AS cliente,
            COUNT(*) AS total_items,
            SUM(CASE WHEN dnv.destino IS NULL OR dnv.destino = '' THEN 1 ELSE 0 END) AS pendientes,
            MAX(dnv.fecha) AS fecha
            FROM devoluciones_nv dnv
            INNER JOIN ventas v ON v.id_venta = dnv.id_venta
            LEFT JOIN cotizaciones co ON co.cotizacion_id = v.id_coti
            LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
            GROUP BY v.id_venta, v.serie, v.numero, v.fecha_emision, co.numero, c.documento, c.datos
            ORDER BY fecha DESC
            ";
            $fila = mysqli_query($this->conectar, $sql);
            return json_encode(mysqli_fetch_all($fila, MYSQLI_ASSOC));
        } catch (Exception $e) {
            return json_encode([]);
        }
    }

    /** Productos devueltos de un documento (para el modal de confirmación) */
    public function detalle(){
        try {
            $idVenta = isset($_POST['id_venta']) ? intval($_POST['id_venta']) : 0;
            $sql = "
            SELECT
            dnv.id_devolucion,
            p.codsunat AS codigo,
            p.descripcion,
            CONCAT(dnv.signo, dnv.cantidad) AS cantidad,
            u.usuario,
            dnv.fecha,
            dnv.destino
            FROM devoluciones_nv dnv
            INNER JOIN productos p ON p.id_producto = dnv.id_producto
            LEFT JOIN usuarios u ON u.usuario_id = dnv.id_usuario
            WHERE dnv.id_venta = $idVenta
            ORDER BY dnv.id_devolucion ASC
            ";
            $fila = mysqli_query($this->conectar, $sql);
            return json_encode(mysqli_fetch_all($fila, MYSQLI_ASSOC));
        } catch (Exception $e) {
            return json_encode([]);
        }
    }

    /**
     * Define el destino de una devolución (solo admin):
     * 'a' = regresa al almacén (el stock ya fue devuelto por el flujo de venta; solo se marca)
     * 'p' = pérdida (producto malogrado): se descuenta del stock y se registra en el kardex.
     */
    public function definirDestino(){
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            return json_encode(["res" => false, "msg" => "Solo administradores pueden definir el destino"]);
        }
        $id = isset($_POST['id_devolucion']) ? intval($_POST['id_devolucion']) : 0;
        $destino = (isset($_POST['destino']) && $_POST['destino'] === 'p') ? 'p' : 'a';
        if ($id <= 0) {
            return json_encode(["res" => false, "msg" => "Devolución inválida"]);
        }

        $res = $this->conectar->query("SELECT id_producto, cantidad, destino FROM devoluciones_nv WHERE id_devolucion = $id");
        if (!$res || $res->num_rows == 0) {
            return json_encode(["res" => false, "msg" => "Devolución no encontrada"]);
        }
        $dev = $res->fetch_assoc();
        if ($dev['destino'] !== null && $dev['destino'] !== '') {
            return json_encode(["res" => false, "msg" => "Esta devolución ya tiene destino definido"]);
        }

        require_once "app/models/Kardex.php";
        $kardex = new Kardex($this->conectar);
        $cantidad = abs(floatval($dev['cantidad']));

        if ($destino === 'p') {
            // Producto malogrado: sale del stock
            $ok = $this->conectar->query("UPDATE productos SET cantidad = cantidad - $cantidad WHERE id_producto = '{$dev['id_producto']}'");
            if (!$ok) {
                return json_encode(["res" => false, "msg" => "No se pudo descontar el stock"]);
            }
            $kardex->registrar($dev['id_producto'], 'e', 'Perdida', $cantidad, 'devolucion:' . $id, 'Producto malogrado (devolución)');
        } else {
            // Regresa al almacén: constancia en el kardex como INGRESO 'Devolucion' (motivo de
            // sistema), SIN alterar el saldo: el reingreso de stock ya lo registró el flujo que
            // originó la devolución (edición o anulación de la venta).
            $kardex->registrar($dev['id_producto'], 'i', 'Devolucion', $cantidad, 'devolucion:' . $id, 'Devolución confirmada: regresó al almacén', false);
        }

        $this->conectar->query("UPDATE devoluciones_nv SET destino = '$destino' WHERE id_devolucion = $id");
        return json_encode(["res" => true, "msg" => $destino === 'p' ? "Registrado como pérdida" : "Confirmado regreso al almacén"]);
    }


    public function exportarExcel(){
        // Eliminar cualquier salida previa
        ob_end_clean();
        $sql = "
            SELECT
            dnv.id_devolucion,
            v.id_venta,
            v.fecha_emision,
            CONCAT(v.serie, ' | ', v.numero) AS factura,
            p.id_producto,
            p.descripcion,
            p.codsunat AS 'codigo',
            u.usuario,
            CONCAT(dnv.signo,dnv.cantidad) AS cantidad,
            dnv.fecha
            FROM devoluciones_nv dnv
            INNER JOIN ventas v ON v.id_venta = dnv.id_venta
            INNER JOIN productos p ON p.id_producto = dnv.id_producto
            INNER JOIN usuarios u ON u.usuario_id = dnv.id_usuario  
            ORDER BY dnv.fecha DESC
            ";
        $fila = mysqli_query($this->conectar, $sql);
        $data = mysqli_fetch_all($fila, MYSQLI_ASSOC);

        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'Reporte de Devoluciones');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(20);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = [
            '#', 'FACTURA', 'F.EMISIÓN', 'CÓDIGO', 'PRODUCTO', 'CANTIDAD','USUARIO', 'FECHA'
        ];
        
        $row = 2;
        foreach ($headers as $key => $header) {
            $sheet->setCellValueByColumnAndRow($key + 1, 2, $header);
        }
        $sheet->getStyle("A$row:H$row")->getFill()->setFillType(PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF28719B');
        $sheet->getStyle("A$row:H$row")->getFont()->getColor()->setARGB(PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
        $sheet->getStyle("A$row:H$row")->getFont()->setBold(true);
        $sheet->getStyle("A$row:H$row")->getFont()->setBold(true)->setSize(12); 
        
        $row++;

        foreach ($data as $index => $rowData) {
            // Llenar datos por fila
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $rowData['factura'] ?? '');
            $sheet->setCellValue('C' . $row, $rowData['fecha_emision'] ?? '');
            $sheet->setCellValue('D' . $row, $rowData['codigo'] ?? '');
            $sheet->setCellValue('E' . $row, $rowData['descripcion'] ?? '');
            $sheet->setCellValue('F' . $row, $rowData['cantidad'] ?? '');
            $sheet->setCellValue('G' . $row, $rowData['usuario'] ?? '');
            $sheet->setCellValue('H' . $row, $rowData['fecha'] ?? '');

            $row++;
        }
        
        foreach (range('A', 'L') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Nombre del archivo
        $fileName = 'reporte_devoluciones.xlsx';
        // Enviar los encabezados para la descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header('Cache-Control: max-age=0');

        // Guardar el archivo y enviarlo al navegador
        $writer->save('php://output');
        exit();
    }

}