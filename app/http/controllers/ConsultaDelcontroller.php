<?php

require_once 'utils/lib/mpdf/vendor/autoload.php';
require_once "utils/lib/code/vendor/autoload.php";
use Picqer\Barcode\BarcodeGeneratorPNG;

require_once "app/clases/serverside.php";


class ConsultaDelcontroller extends Controller
{
    private $conexion;
    private $mpdf;
    public function __construct()
    {
        $this->conexion = (new Conexion())->getConexion();

        /*   $c_producto->setIdEmpresa($_SESSION['id_empresa']); */
    }

    /**
     * Filtros de reparto para el listado de pedidos: fecha, camión, día de visita, ruta y mercado.
     * Devuelve un arreglo de condiciones SQL ya escapadas (vacío si no se envió ningún filtro).
     * El camión se traduce a día de visita + rutas del cliente (mismo mapeo que los reportes).
     */
    private function filtrosReparto()
    {
        $cond = [];
        $desde = isset($_GET['f_desde']) ? trim($_GET['f_desde']) : '';
        $hasta = isset($_GET['f_hasta']) ? trim($_GET['f_hasta']) : '';
        $camion = isset($_GET['f_camion']) ? trim($_GET['f_camion']) : '';
        $dia = isset($_GET['f_dia']) ? trim($_GET['f_dia']) : '';
        $ruta = isset($_GET['f_ruta']) ? trim($_GET['f_ruta']) : '';
        $mercado = isset($_GET['f_mercado']) ? trim($_GET['f_mercado']) : '';

        // Rango de fechas del pedido (el reparto de un día atiende pedidos de días anteriores)
        if ($desde !== '') {
            $desdeEsc = $this->conexion->real_escape_string($desde);
            $cond[] = "DATE(COALESCE(c.fecha_registro, c.fecha)) >= '$desdeEsc'";
        }
        if ($hasta !== '') {
            $hastaEsc = $this->conexion->real_escape_string($hasta);
            $cond[] = "DATE(COALESCE(c.fecha_registro, c.fecha)) <= '$hastaEsc'";
        }

        // Mapa camión => día de visita => rutas (igual que ReportesDeudaController::obtenerFiltros)
        $mapa = [
            '1' => ['lunes' => ['1','7'], 'martes' => ['5','7'], 'miercoles' => ['5'], 'jueves' => ['1','7'], 'viernes' => ['6','7'], 'sabado' => ['7','8']],
            '2' => ['lunes' => ['3','6'], 'martes' => ['1','3'], 'miercoles' => ['1','3'], 'jueves' => ['6','3'], 'viernes' => ['3','5'], 'sabado' => ['3','6']],
            '3' => ['miercoles' => ['6','7'], 'viernes' => ['8','2'], 'sabado' => ['1','5']],
        ];

        if ($camion !== '' && isset($mapa[$camion])) {
            $filtros = $mapa[$camion];
            if ($dia !== '') {
                $filtros = isset($filtros[$dia]) ? [$dia => $filtros[$dia]] : [];
            }
            $partes = [];
            foreach ($filtros as $diaMapa => $rutas) {
                $diaEsc = $this->conexion->real_escape_string($diaMapa);
                $rutasEsc = implode(',', array_map('intval', $rutas));
                $partes[] = "(LOWER(cl.dias_visitas) = LOWER('$diaEsc') AND cl.id_ruta IN ($rutasEsc))";
            }
            // Camión sin recorrido ese día: no debe listar nada
            $cond[] = empty($partes) ? "1 = 0" : "(" . implode(' OR ', $partes) . ")";
        } elseif ($dia !== '') {
            $diaEsc = $this->conexion->real_escape_string($dia);
            $cond[] = "LOWER(cl.dias_visitas) = LOWER('$diaEsc')";
        }

        // Ruta y mercado del cliente: se combinan con lo anterior
        if ($ruta !== '') {
            $rutaEsc = $this->conexion->real_escape_string($ruta);
            $cond[] = "cl.id_ruta = '$rutaEsc'";
        }
        if ($mercado !== '') {
            $mercadoEsc = $this->conexion->real_escape_string($mercado);
            $cond[] = "cl.mercado = '$mercadoEsc'";
        }

        return $cond;
    }

    public function getDataCotizacionSS(){
        // Limpiar cualquier salida previa (solo si hay buffer activo)
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        try {
            // Construir el WHERE según el rol: admin (1), cajero (4) y repartidor (7) ven TODOS
            // los pedidos; los demás roles (ej. vendedor) solo los suyos
            $where = "";
            if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 4 && $_SESSION['rol'] != 7) {
                $where = "WHERE c.id_usuario = '{$_SESSION['usuario_fac']}'";
            }

            // Filtros de reparto (fecha / camión / día de visita). El camión se traduce a la
            // combinación día de visita + rutas del cliente, igual que en Cuentas por Cobrar.
            $condFiltros = $this->filtrosReparto();
            foreach ($condFiltros as $cond) {
                $where .= ($where == '') ? "WHERE $cond" : " AND $cond";
            }
            
            // Búsqueda
            $search = isset($_GET['sSearch']) && $_GET['sSearch'] != '' ? $_GET['sSearch'] : '';
            if ($search != '') {
                $searchCondition = "(
                    c.numero LIKE '%{$search}%' OR
                    c.fecha LIKE '%{$search}%' OR
                    cl.documento LIKE '%{$search}%' OR
                    cl.datos LIKE '%{$search}%' OR
                    c.total LIKE '%{$search}%' OR
                    u.usuario LIKE '%{$search}%'
                )";
                
                if ($where != '') {
                    $where .= " AND " . $searchCondition;
                } else {
                    $where = "WHERE " . $searchCondition;
                }
            }
            
            // Paginación
            $start = isset($_GET['iDisplayStart']) ? intval($_GET['iDisplayStart']) : 0;
            $length = isset($_GET['iDisplayLength']) ? intval($_GET['iDisplayLength']) : 10;
            
            // Ordenamiento
            $orderColumn = isset($_GET['iSortCol_0']) ? intval($_GET['iSortCol_0']) : 0;
            $orderDir = isset($_GET['sSortDir_0']) && $_GET['sSortDir_0'] == 'asc' ? 'ASC' : 'DESC';
            
            // MODIFICADO: Sin subtotal e igv
            $columns = [
                'c.numero',
                'c.fecha',
                "CONCAT(cl.documento, ' | ', cl.datos)",
                'c.total',
                'u.usuario',
                'c.estado'
            ];
            
            $orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : 'c.cotizacion_id';
            
            // Consulta principal - CON subtotal e igv para coincidir con headers
            $sql = "SELECT 
                c.numero,
                c.fecha,
                CONCAT(cl.documento, ' | ', cl.datos) as documento,
                ROUND(c.total / 1.18, 2) as subtotal,
                ROUND(c.total - (c.total / 1.18), 2) as igv,
                c.total,
                u.usuario as vendedor,
                c.estado,
                c.cotizacion_id as vender_id,
                c.cotizacion_id as guia_id,
                c.cotizacion_id,
                COALESCE(c.fecha_registro, c.fecha) as fecha_registro,
                c.id_usuario as usuario
            FROM cotizaciones c
            LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
            LEFT JOIN usuarios u ON c.id_usuario = u.usuario_id
            $where
            ORDER BY $orderBy $orderDir
            LIMIT $start, $length";
            
            $result = $this->conexion->query($sql);
            
            if (!$result) {
                throw new Exception("Error en la consulta: " . $this->conexion->error);
            }
            
            // Contar total de registros (sin filtro de búsqueda) — mismos roles que el listado:
            // admin (1), cajero (4) y repartidor (7) ven todos
            $whereBase = "";
            if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 4 && $_SESSION['rol'] != 7) {
                $whereBase = "WHERE c.id_usuario = '{$_SESSION['usuario_fac']}'";
            }
            // Los filtros de reparto también cuentan aquí (si no, el total no coincide con lo listado)
            foreach ($condFiltros as $cond) {
                $whereBase .= ($whereBase == '') ? "WHERE $cond" : " AND $cond";
            }
            $sqlCountTotal = "SELECT COUNT(*) as total FROM cotizaciones c
                LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
                $whereBase";
            $countTotalResult = $this->conexion->query($sqlCountTotal);
            $totalRecords = $countTotalResult->fetch_assoc()['total'];
            
            // Contar registros filtrados (con búsqueda)
            $sqlCount = "SELECT COUNT(*) as total 
                FROM cotizaciones c
                LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
                LEFT JOIN usuarios u ON c.id_usuario = u.usuario_id
                $where";
            $countResult = $this->conexion->query($sqlCount);
            
            if (!$countResult) {
                throw new Exception("Error al contar registros: " . $this->conexion->error);
            }
            
            $filteredRecords = $countResult->fetch_assoc()['total'];
            
            // Preparar datos
            $data = [];
            while($row = $result->fetch_array(MYSQLI_NUM)) {
                $data[] = $row;
            }
            
            // Respuesta JSON
            $output = [
                "sEcho" => isset($_GET['sEcho']) ? intval($_GET['sEcho']) : 0,
                "iTotalRecords" => $totalRecords,
                "iTotalDisplayRecords" => $filteredRecords,
                "aaData" => $data
            ];
            
            if (ob_get_length()) { ob_clean(); }
            header('Content-Type: application/json');
            echo json_encode($output);
            exit;
            
        } catch (Exception $e) {
            if (ob_get_length()) { ob_clean(); }
            header('Content-Type: application/json');
            echo json_encode([
                "error" => $e->getMessage(),
                "sEcho" => isset($_GET['sEcho']) ? intval($_GET['sEcho']) : 0,
                "iTotalRecords" => 0,
                "iTotalDisplayRecords" => 0,
                "aaData" => []
            ]);
            exit;
        }
    }
    public function generarBarCode2(){
// Crea una instancia del generador de códigos de barras HTML


        $sql="select * from productos where  id_producto='{$_GET['nombre']}'";
        $re3sult = $this->conexion->query($sql)->fetch_assoc();

        $barcodeGenerator = new BarcodeGeneratorPNG();

        $fontSixe1=' 13px';
        $fontSixe2=' 15px';

// Genera el código de barras como una imagen PNG
        $barcodeData = trim($re3sult['codigo']); // Datos del código de barras
        $barcodeImage = $barcodeGenerator->getBarcode($barcodeData, $barcodeGenerator::TYPE_CODE_128_B);
        if ($_GET['scal']==2){
            $fontSixe1=' 10px';
            $fontSixe2=' 14px';
        }
        //$re3sult['descripcion']=strlen($re3sult['descripcion'])>46?substr($re3sult['descripcion'],0,54):$re3sult['descripcion'];
        //$re3sult['descripcion']='FOCO LED Y3 2 CARAS ULTRA SLIM H4 20000LM CHIP LED DOB 30W - 12V DISIPADOR METALICO X UNIDAD (Y3-H4)';

// Agrega la imagen del código de barras al contenido del PDF
        $html = '<div style="font-family: Arial, Helvetica, sans-serif;width: 100%; text-align: center"><span style="font-size: '.$fontSixe1.';  ">'.$re3sult['descripcion'].'</span>
<br> <span style="font-family: Arial, Helvetica, sans-serif;font-weight: bold;font-size: '.$fontSixe2.' ">S/ '.number_format($re3sult['precio_unidad'],2).'</span> - 
 <span style="font-family: Arial, Helvetica, sans-serif;font-weight: bold;font-size: '.$fontSixe2.' ">CLUB S/ '.number_format($re3sult['precio4'],2).'</span></div>';
        $html .= '<img style="display: block;margin: auto;margin-left: 20px;" src="data:image/png;base64,' . base64_encode($barcodeImage) . '">';
        $html .= '<div style="font-weight: bold;font-family: Arial, Helvetica, sans-serif;width: 100%; text-align: center;font-size: '.$fontSixe2.'"><span>'.$re3sult['codigo'].'</span></div>';


        $this->mpdf  = new \Mpdf\Mpdf([
            'margin_bottom' => 1,
            'margin_top' => 5,
            'margin_left' => 3,
            'margin_right' => 3,
            'mode' => 'utf-8',
        ]);

        $this->mpdf->AddPageByArray([
            "orientation" => "P",
            "newformat" =>[75, 50], //
        ]); ;
        $this->mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $this->mpdf->Output();
    }
    public function generarBarCode(){
// Crea una instancia del generador de códigos de barras HTML


        $sql="select * from productos where  id_producto='{$_GET['nombre']}'";
        $re3sult = $this->conexion->query($sql)->fetch_assoc();

        $barcodeGenerator = new BarcodeGeneratorPNG();

        $fontSixe1=' 10px';
        $fontSixe2=' 12px';

// Genera el código de barras como una imagen PNG
        $barcodeData = trim($re3sult['codigo']); // Datos del código de barras
        $barcodeImage = $barcodeGenerator->getBarcode($barcodeData, $barcodeGenerator::TYPE_CODE_128_B);
        if ($_GET['scal']==2){
            $fontSixe1=' 7px';
            $fontSixe2=' 11px';
        }
        //$re3sult['descripcion']=strlen($re3sult['descripcion'])>46?substr($re3sult['descripcion'],0,54):$re3sult['descripcion'];
        //$re3sult['descripcion']='FOCO LED Y3 2 CARAS ULTRA SLIM H4 20000LM CHIP LED DOB 30W - 12V DISIPADOR METALICO X UNIDAD (Y3-H4)';

// Agrega la imagen del código de barras al contenido del PDF
        $html = '<div style="font-family: Arial, Helvetica, sans-serif;width: 100%; text-align: center"><span style="font-size: '.$fontSixe1.';  ">'.$re3sult['descripcion'].'</span>
<br> <span style="font-family: Arial, Helvetica, sans-serif;font-weight: bold;font-size: '.$fontSixe2.' ">S/ '.number_format($re3sult['precio_unidad'],2).'</span> - 
 <span style="font-family: Arial, Helvetica, sans-serif;font-weight: bold;font-size: '.$fontSixe2.' ">CLUB S/ '.number_format($re3sult['precio4'],2).'</span></div>';
        //$html .= '<img src="data:image/png;base64,' . base64_encode($barcodeImage) . '">';
        $html .= '<div style="font-weight: bold;font-family: Arial, Helvetica, sans-serif;width: 100%; text-align: center;font-size: '.$fontSixe2.'"><span>'.$re3sult['codigo'].'</span></div>';


        $this->mpdf  = new \Mpdf\Mpdf([
            'margin_bottom' => 1,
            'margin_top' => 1,
            'margin_left' => 3,
            'margin_right' => 3,
            'mode' => 'utf-8',
        ]);

        $this->mpdf->AddPageByArray([
            "orientation" => "P",
            "newformat" =>[50, 30], //
        ]); ;
        $this->mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
        $this->mpdf->Output();
    }
}
