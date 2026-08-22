-- ============================================================
-- MIGRACIÓN: Módulo Almacén — Kardex + Motivos
-- Ejecutar en producción UNA sola vez (idempotente).
-- ============================================================

CREATE TABLE IF NOT EXISTS almacen_motivos (
    motivo_id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    tipo CHAR(1) NOT NULL COMMENT 'i = ingreso, e = salida',
    fijo CHAR(1) NOT NULL DEFAULT '0' COMMENT '1 = motivo de sistema (no seleccionable manualmente)',
    activo CHAR(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (motivo_id),
    UNIQUE KEY uq_motivo (nombre, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Motivos iniciales (los "fijo=1" los genera el sistema automáticamente)
-- 'Devolucion' es FIJO: lo genera la pantalla de Devoluciones (regresar a almacén / pérdida).
-- Los recojos entran por la pantalla de Devoluciones (no son motivo manual propio).
INSERT IGNORE INTO almacen_motivos (nombre, tipo, fijo) VALUES
('Recepcion de compra','i', '1'),
('Venta',             'e', '1'),
('Anulacion de venta','i', '1'),
('Edicion de venta',  'i', '1'),
('Edicion de venta',  'e', '1'),
('Anulacion de venta','e', '1'),
('Recojo',            'i', '1'),
('Devolucion',        'i', '1'),
('Anulacion de cuadre','i','1'),
('Anulacion de cuadre','e','1'),
('Anulacion de recepcion','e','1'),
('Ajuste de cuadre',  'i', '0'),
('Perdida',           'e', '0'),
('Prestamo',          'e', '0'),
('Ajuste de cuadre',  'e', '0');

-- Correcciones sobre bases que ya tenían la versión anterior de los motivos
-- 'Compra' pasa a llamarse 'Recepcion de compra' (el stock entra al recepcionar, no al comprar)
UPDATE almacen_motivos SET nombre='Recepcion de compra' WHERE nombre='Compra' AND tipo='i';
UPDATE almacen_motivos SET fijo='1' WHERE nombre='Devolucion' AND tipo='i';
-- 'Recojo' es motivo de SISTEMA: lo genera una linea con cantidad NEGATIVA en una VENTA (regresa al stock)
UPDATE almacen_motivos SET activo='1', fijo='1' WHERE nombre='Recojo' AND tipo='i';

-- ============================================================
-- RECEPCIÓN DE MERCADERÍA (compras)
-- El stock de una compra ya NO entra al registrarla: entra al RECEPCIONAR
-- (total o parcialmente); lo rechazado nunca entra al stock.
-- NOTA MySQL: si la columna ya existe, este ALTER da error y se puede ignorar.
-- ============================================================
ALTER TABLE compras ADD COLUMN estado_recepcion CHAR(1) NOT NULL DEFAULT 'p' COMMENT 'p=pendiente, x=parcial, c=completa';
-- Las compras existentes ya ingresaron su stock al crearse (modelo anterior):
UPDATE compras SET estado_recepcion = 'c' WHERE estado_recepcion = 'p';

CREATE TABLE IF NOT EXISTS compras_recepciones (
    recepcion_id INT NOT NULL AUTO_INCREMENT,
    id_compra INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad_recibida DOUBLE(12,2) NOT NULL DEFAULT 0,
    cantidad_rechazada DOUBLE(12,2) NOT NULL DEFAULT 0,
    motivo_rechazo VARCHAR(250) DEFAULT NULL,
    id_usuario INT DEFAULT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado CHAR(1) NOT NULL DEFAULT '1' COMMENT '1=vigente, 0=anulada (el registro nunca se borra)',
    PRIMARY KEY (recepcion_id),
    KEY idx_compra (id_compra),
    KEY idx_producto (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Destino de cada devolución: NULL = pendiente de decisión,
-- 'a' = regresó al almacén (queda en stock), 'p' = pérdida (producto malogrado, sale del stock).
-- NOTA: MySQL no soporta "ADD COLUMN IF NOT EXISTS"; si la columna ya existe este
-- ALTER da error y se puede ignorar (el resto del script ya se aplicó).
ALTER TABLE devoluciones_nv ADD COLUMN destino CHAR(1) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS almacen_kardex (
    kardex_id INT NOT NULL AUTO_INCREMENT,
    id_producto INT NOT NULL,
    tipo CHAR(1) NOT NULL COMMENT 'i = ingreso, e = salida',
    motivo_id INT NOT NULL,
    cantidad DOUBLE(12,2) NOT NULL COMMENT 'siempre positiva; el signo lo da tipo',
    saldo_anterior DOUBLE(12,2) DEFAULT NULL COMMENT 'stock del producto antes del movimiento',
    saldo_resultante DOUBLE(12,2) DEFAULT NULL COMMENT 'stock del producto despues del movimiento',
    referencia VARCHAR(100) DEFAULT NULL COMMENT 'ej: venta:123, compra:45, manual',
    observacion VARCHAR(250) DEFAULT NULL,
    estado CHAR(1) NOT NULL DEFAULT '1' COMMENT '1=vigente, 0=anulado (el registro nunca se borra)',
    id_usuario INT DEFAULT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (kardex_id),
    KEY idx_producto_fecha (id_producto, fecha),
    KEY idx_motivo (motivo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- STOCK CON DECIMALES (ventas por peso: ej. 2.80 kg)
-- productos.cantidad era INT y redondeaba el stock al vender fracciones.
-- MODIFY conserva los valores actuales; es seguro ejecutarlo mas de una vez.
-- ============================================================
ALTER TABLE productos MODIFY cantidad DECIMAL(12,2) NOT NULL DEFAULT 0;
