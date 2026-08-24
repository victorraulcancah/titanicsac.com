<?php

class VentaAnulada
{
    private $id_venta;
    private $fecha;
    private $motivo;
    private $conectar;

    /**
     * VentaAnulada constructor.
     */
    public function __construct()
    {
        $this->conectar = (new Conexion())->getConexion();
    }

    /**
     * @return mixed
     */
    public function getIdVenta()
    {
        return $this->id_venta;
    }

    /**
     * @param mixed $id_venta
     */
    public function setIdVenta($id_venta)
    {
        $this->id_venta = $id_venta;
    }

    /**
     * @return mixed
     */
    public function getFecha()
    {
        return $this->fecha;
    }

    /**
     * @param mixed $fecha
     */
    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }

    /**
     * @return mixed
     */
    public function getMotivo()
    {
        return $this->motivo;
    }

    /**
     * @param mixed $motivo
     */
    public function setMotivo($motivo)
    {
        $this->motivo = $motivo;
    }

    public function insertar()
    {
        // El registro de auditoría no debe cortar la anulación (stock, kardex y devoluciones):
        // si falla, se deja constancia en el log y el proceso continúa.
        $resulta = false;
        try {
            $sql = "insert into ventas_anuladas
            values ('$this->id_venta', '$this->fecha', '$this->motivo')";
            $resulta = $this->conectar->query($sql);
        } catch (Throwable $e) {
            error_log('VENTA ANULADA (auditoria): ' . $e->getMessage());
        }

        #verificamos si es una NV
        $sql="select * from ventas where id_venta='$this->id_venta'";
        $venta = $this->conectar->query($sql)->fetch_assoc();
        #
        $sql="select * from productos_ventas where id_venta='$this->id_venta'";

        $listaVP = $this->conectar->query($sql);

        # Kardex: las salidas originales de la venta NO se eliminan; quedan marcadas como ANULADAS
        require_once __DIR__ . '/Kardex.php';
        (new Kardex($this->conectar))->anularPorReferencia('venta:' . $this->id_venta, 'e');
        # ... y también los ingresos por RECOJO (líneas con cantidad negativa) de esta venta
        (new Kardex($this->conectar))->anularPorReferencia('venta:' . $this->id_venta, 'i', 'Recojo');

        foreach ($listaVP as $item){
            $presenta_cnt = ($item['presenta_cnt'] && $item['presenta_cnt']) ? $item['presenta_cnt'] : 1;
            $cantidad = $item['cantidad']*$presenta_cnt;
            #
            $sql="update productos set  cantidad= cantidad+'{$cantidad}' where id_producto='{$item['id_producto']}' ";
            //echo $sql;
            $this->conectar->query($sql);
            # Kardex: anulación de venta (motivo fijo de sistema). Línea positiva => vuelve a INGRESAR;
            # línea NEGATIVA (recojo) => el UPDATE de arriba la restó del stock, así que es una SALIDA.
            require_once __DIR__ . '/Kardex.php';
            if ($cantidad < 0) {
                (new Kardex($this->conectar))->registrar($item['id_producto'], 'e', 'Anulacion de venta', abs($cantidad), 'venta:' . $this->id_venta, 'Reversa de recojo por anulación');
            } else {
                (new Kardex($this->conectar))->registrar($item['id_producto'], 'i', 'Anulacion de venta', $cantidad, 'venta:' . $this->id_venta);
            }
            #
            if($venta['id_tido']==6){
                $sql_1="
                INSERT INTO devoluciones_nv (id_venta,id_producto,id_usuario,cantidad,presenta,presenta_cnt,signo)
                VALUES ('{$item['id_venta']}','{$item['id_producto']}','{$_SESSION['usuario_fac']}','{$cantidad}','{$item['presenta']}','{$item['presenta_cnt']}','+')
                ";
                //echo $sql_1;
                $this->conectar->query($sql_1);
            }
        }

        return $resulta;
    }

    public function verFacturasAnuladas($id_empresa)
    {
        $sql = "select v.id_venta, v.fecha, va.fecha as fecha_anulado, ds.cod_sunat, ds.abreviatura, v.serie, v.numero, c.documento, c.datos, v.total, v.estado, v.id_tido, v.enviado_sunat, v.estado
        from ventas_anuladas as va 
            inner join ventas as v on v.id_venta = va.id_venta 
            inner join documentos_sunat ds on v.id_tido = ds.id_tido
            inner join clientes c on v.id_cliente = c.id_cliente 
        where v.id_empresa = '$id_empresa' and v.fecha = '$this->fecha' and v.id_tido = 2 ";
        return $this->conectar->get_Cursor($sql);
    }
}