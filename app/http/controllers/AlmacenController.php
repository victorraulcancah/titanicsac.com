<?php

require_once 'app/models/Kardex.php';

class AlmacenController extends Controller
{
    private $kardex;

    public function __construct()
    {
        $this->kardex = new Kardex();
    }

    /** Kardex de un producto (POST: id_producto, fecha_inicio?, fecha_fin?) */
    public function kardexProducto()
    {
        $idProducto = isset($_POST['id_producto']) ? intval($_POST['id_producto']) : 0;
        $fechaInicio = isset($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : '';
        $fechaFin = isset($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : '';
        if ($idProducto <= 0) {
            echo json_encode([]);
            return;
        }
        echo json_encode($this->kardex->movimientos($idProducto, $fechaInicio, $fechaFin));
    }

    /** Últimos movimientos de todo el almacén */
    public function kardexGeneral()
    {
        echo json_encode($this->kardex->ultimosMovimientos(300));
    }

    /** Últimos cuadres de inventario (solo ajustes con motivo manual) */
    public function cuadres()
    {
        echo json_encode($this->kardex->ultimosCuadres(300));
    }

    /** Motivos manuales (para los selects de ingreso/salida) */
    public function motivos()
    {
        echo json_encode($this->kardex->motivosManuales());
    }

    /** Registrar ajuste manual (POST: id_producto, tipo i/e, motivo_id, cantidad, observacion?). Solo admins. */
    public function registrarMovimiento()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            echo json_encode(['res' => false, 'msg' => 'Solo administradores pueden registrar ajustes manuales']);
            return;
        }
        $tipo = (isset($_POST['tipo']) && $_POST['tipo'] === 'i') ? 'i' : 'e';
        $resultado = $this->kardex->ajusteManual(
            $_POST['id_producto'] ?? 0,
            $tipo,
            $_POST['motivo_id'] ?? 0,
            $_POST['cantidad'] ?? 0,
            trim($_POST['observacion'] ?? '')
        );
        echo json_encode($resultado);
    }

    /** Anular un cuadre manual (POST: kardex_id). Solo admins. */
    public function cuadreAnular()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            echo json_encode(['res' => false, 'msg' => 'Solo administradores pueden anular cuadres']);
            return;
        }
        echo json_encode($this->kardex->anularCuadre($_POST['kardex_id'] ?? 0));
    }

    /** Editar la cantidad de un cuadre manual (POST: kardex_id, cantidad). Solo admins. */
    public function cuadreEditar()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            echo json_encode(['res' => false, 'msg' => 'Solo administradores pueden editar cuadres']);
            return;
        }
        echo json_encode($this->kardex->editarCuadre($_POST['kardex_id'] ?? 0, $_POST['cantidad'] ?? 0, trim($_POST['observacion'] ?? '')));
    }

    /** Todos los motivos activos, incluidos los fijos (para el CRUD) */
    public function motivosTodos()
    {
        echo json_encode($this->kardex->motivosTodos());
    }

    /** Crear motivo manual (POST: nombre, tipo i/e). Solo admins. */
    public function motivoCrear()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            echo json_encode(['res' => false, 'msg' => 'Solo administradores pueden gestionar motivos']);
            return;
        }
        echo json_encode($this->kardex->crearMotivo($_POST['nombre'] ?? '', $_POST['tipo'] ?? 'i'));
    }

    /** Editar nombre de motivo manual (POST: motivo_id, nombre). Solo admins. */
    public function motivoEditar()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            echo json_encode(['res' => false, 'msg' => 'Solo administradores pueden gestionar motivos']);
            return;
        }
        echo json_encode($this->kardex->editarMotivo($_POST['motivo_id'] ?? 0, $_POST['nombre'] ?? ''));
    }

    /** Eliminar (desactivar) motivo manual (POST: motivo_id). Solo admins. */
    public function motivoEliminar()
    {
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
            echo json_encode(['res' => false, 'msg' => 'Solo administradores pueden gestionar motivos']);
            return;
        }
        echo json_encode($this->kardex->eliminarMotivo($_POST['motivo_id'] ?? 0));
    }

    /** Buscador de productos (POST: q) */
    public function buscarProducto()
    {
        $q = isset($_POST['q']) ? trim($_POST['q']) : '';
        if (strlen($q) < 2) {
            echo json_encode([]);
            return;
        }
        $qEsc = $this->kardex->conectar->real_escape_string($q);
        $sql = "SELECT id_producto, codigo, descripcion, cantidad
                FROM productos
                WHERE (codigo LIKE '%$qEsc%' OR descripcion LIKE '%$qEsc%')
                ORDER BY descripcion ASC
                LIMIT 20";
        $res = $this->kardex->conectar->query($sql);
        echo json_encode($res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : []);
    }
}
