# Cuentas por Cobrar desde VENTAS — Documento de cambio

> Estado: **IMPLEMENTADO EN LOCAL** (pendiente probar con login y subir a producción)
> Fecha inicio: 2026-07-18

## 0. IMPLEMENTACIÓN REALIZADA (2026-07-18)

### Parte 1 — Cuentas por cobrar de ventas
| Cambio | Archivo |
|---|---|
| Se quitó la validación "cobrar todo antes de convertir a venta" | `app/http/controllers/VentasController.php` (~línea 971) |
| La sincronización de cuotas al convertir respeta el estado real (pagada/pendiente) en vez de marcar todo como pagado | `VentasController.php` (~línea 1163) |
| Nueva vista **CxC VENTAS** (`/cobranzas/ventas`): reutiliza la pantalla de Cobranzas filtrada a `tipo_co='v'`; cobrar cuotas usa los endpoints existentes | `resources/views/fragment-views/cliente/cobranzas-ventas.php` (+ flag en `cobranzas.php`) |
| Ruta y controller de la vista | `routes/web.php`, `FragmentController@cobranzasVentas` |
| Enlaces de menú (admin, vendedor no, cajera sí) | `resources/views/fragment/nav-bar.php` |

### Parte 2 — Almacén: Kardex + Ingresos/Salidas
| Pieza | Archivo |
|---|---|
| Tablas `almacen_motivos` y `almacen_kardex` (migración idempotente; aplicada en local `titanic2`) | `config/migracion_kardex.sql` — **EJECUTAR EN PRODUCCIÓN** |
| Modelo Kardex (registrar, ajusteManual, motivos, movimientos) | `app/models/Kardex.php` |
| Controller con 5 endpoints ajax (`/ajs/almacen/...`) | `app/http/controllers/AlmacenController.php`, `routes/ajax2.php` |
| Vista **Kardex** (`/almacen/kardex`): buscador de producto, filtro fechas, movimientos con saldo | `fragment-views/cliente/almacen-kardex.php` |
| Vista **Ingreso/Salida** (`/almacen/movimientos`): ajustes manuales con motivo + últimos movimientos | `fragment-views/cliente/almacen-movimientos.php` |
| Hooks automáticos (motivos fijos): Venta (salida), Compra (ingreso), Anulación de venta (ingreso), Edición de venta (reingreso+salida) | `ProductoVenta.php`, `ProductoCompra.php`, `Compra.php`, `VentaAnulada.php`, `VentasController.php` |
| Menú ALMACÉN: Productos / Kardex / Ingreso-Salida (admin y rol 6) | `nav-bar.php` |

Probado en local: ajuste manual de ingreso/salida (stock actualizado + kardex con saldo),
rechazo de motivos de sistema en uso manual, hook de venta registra salida automática.
Los motivos fijos ('Venta','Compra','Anulacion de venta','Edicion de venta') no se pueden
elegir a mano. El kardex nunca rompe una venta/compra: si falla, solo escribe en error_log.

## 1. Lo que se quiere (pedido del usuario)

- Hoy la deuda (cuenta por cobrar) se genera al **crear el pedido desde cotizaciones**.
- Se quiere crear **otra cuenta por cobrar basada en VENTAS**, para que las deudas se generen desde la venta.

## 2. Cómo funciona HOY (análisis del código)

### 2.1 Origen de la deuda: el PEDIDO (cotización a crédito)

- Al crear un pedido a crédito (`id_tipo_pago = 2`) en `CotizacionesController` (~línea 592),
  se insertan sus cuotas en **`cuotas_cotizacion`** (`id_coti`, `monto`, `fecha`, `estado='0'`).
- Si no se definen cuotas, se crea una sola cuota por el total.
- La deuda nace ahí: pedido → cuotas → pantalla de Cobranza.

### 2.2 Ventas también tiene su tabla de cuotas (ya existe)

- Las ventas a crédito insertan cuotas en **`dias_ventas`**
  (`VentasController` líneas 537, 760, 1148: `id_venta`, `monto`, `fecha`, `estado`).
- `estado='1'` pendiente / `estado='0'` pagado en la vista antigua;
  en Cobranza se suma como pagado `dv.estado='1'` (¡OJO: criterio invertido entre pantallas!).

### 2.3 Pantalla de Cobranza (cuentas por cobrar actual)

- `Cobranza.php → getAllCobranzas()` hace un **UNION** de dos orígenes:
  - `tipo_co='v'`: `ventas` + `dias_ventas` (código con formato `SERIE | NUMERO`).
  - `tipo_co='c'`: `cotizaciones` + `cuotas_cotizacion` (código con formato `#NUMERO`).
- Es decir: **la pantalla ya mezcla deudas de ventas y de pedidos**, pero el flujo
  del negocio hoy alimenta casi todo por el lado de pedidos (cotizaciones).

### 2.4 Validación actual para convertir pedido → venta (SE VA A QUITAR)

- `VentasController.php` líneas **971-995**: al crear la venta desde una cotización,
  valida que **todas las cuotas** de `cuotas_cotizacion` estén pagadas (`estado='1'`).
  Si hay pendientes, bloquea con: *"No se puede crear la venta. La cotización tiene
  pagos pendientes. Debe completar todos los pagos en Cobranzas antes de convertirla en venta."*
- **Decisión del usuario:** esta validación **ya no se requiere**. Con el nuevo modelo
  se podrá convertir a venta sin haber cobrado todo, porque la deuda pasará a vivir
  en la venta.

### 2.5 Descuento de stock al convertir en venta (SE MANTIENE)

- Al crear la venta, cada producto insertado en `productos_ventas` descuenta stock
  del almacén: `ProductoVenta::insertar()` (`app/models/ProductoVenta.php:214-217`)
  ejecuta `UPDATE productos SET cantidad = cantidad - (cantidad * presenta_cnt)`.
- Es decir: **pedido NO descuenta stock; la venta SÍ**. Esto se mantiene igual.
- Coherencia del nuevo modelo: se convierte a venta apenas se despacha la mercadería
  (descuenta stock) y el cobro de la deuda continúa después, sobre la venta.

### 2.6 Problema conocido del modelo actual

- `eliminarCotizacion()` borra FÍSICAMENTE el pedido + productos + cuotas
  (incluidas cuotas pagadas). Caso confirmado: pedido #52047 (id 53825) del 2026-05-27
  desapareció sin rastro. La deuda muere con el pedido.
- **Ventaja clave del cambio**: si la deuda vive en la VENTA, borrar el pedido
  ya no destruye la cuenta por cobrar.

## 3. El cambio (alcance definido por el usuario)

**NO se elimina ni se modifica nada de lo existente.** El cambio consiste solo en:

1. **CREAR algo nuevo**: una cuenta por cobrar de **VENTAS** — al crear la venta
   a crédito se genera su deuda (desde ahí se cobran las cuotas).
2. **QUITAR validaciones**: la que exige cobrar todas las cuotas del pedido antes
   de convertirlo en venta (`VentasController.php:971-995`).

Lo actual (cuenta por cobrar de pedidos/cotizaciones) **sigue funcionando igual**;
ambas conviven.

## 4. Preguntas abiertas (por responder)

- [ ] La nueva cuenta por cobrar de ventas: ¿pantalla/vista nueva propia, o se apoya
      en la sección de ventas dentro de Cobranzas actual?
- [ ] Al convertir un pedido con cuotas ya pagadas: ¿la deuda de la venta nace por el
      TOTAL o por el SALDO restante (total − lo ya cobrado en el pedido)?
- [ ] ¿Qué roles pueden ver/cobrar en la nueva vista?
- [ ] ¿Hay más validaciones que quitar además de la de "cobrar todo antes de convertir"?

## 5. Decisiones tomadas

1. **Quitar la validación** de "cobrar todo antes de convertir a venta"
   (`VentasController.php:971-995`). Ya no será requisito.
2. **El descuento de stock al convertir en venta se mantiene** tal como está
   (`ProductoVenta::insertar`).

---

# PARTE 2 — Módulo ALMACÉN: Kardex + Ingresos/Salidas

## 7. El problema actual (según el usuario)

- Al hacer ventas hay **recojos** y **devoluciones de ventas anteriores**.
- El almacén está **entreverado**: todo mezclado, sin trazabilidad de movimientos.

## 8. El cambio pedido

1. **Botón/sección de Almacén** en el sistema.
2. **Nueva vista: KARDEX** — historial de movimientos por producto
   (qué entró, qué salió, cuándo, por qué motivo, saldo).
3. **Nueva vista: Ingreso y Salida de productos** — ajustes de cuadre manual:
   - **INGRESO** con motivos: compra, devolución, entre otros.
   - **SALIDA** con motivos: pérdida, venta, préstamo, entre otros.
   - **Motivos fijos de sistema**: "venta" y "compra" los genera el sistema
     automáticamente (no se eligen a mano); el resto son motivos manuales.

## 9. Lo que ya existe en el sistema (análisis)

| Pieza | Estado actual |
|---|---|
| Stock del producto | `productos.cantidad` (un solo número; se descuenta al vender — `ProductoVenta::insertar`, se suma al comprar — `ProductoCompra`) |
| Rutas de almacén | `/almacen/productos` y `/almacen/intercambio/productos` (intercambio entre almacén 1 y 2) |
| Tabla `ingreso_egreso` | Existe pero casi sin uso (9 filas): `id_producto, tipo (i/e), cantidad, almacen_ingreso, almacen_egreso, id_usuario, estado`. **Sin motivo, sin fecha, sin saldo** |
| Devoluciones | Tabla `devoluciones_nv` (`id_venta, id_producto, cantidad, signo, fecha`) |
| Kardex | **NO existe** |
| Catálogo de motivos | **NO existe** |

**Conclusión técnica:** hoy el stock es un solo número que se pisa desde varios
flujos (venta, compra, devolución, edición manual, intercambio) sin registro
unificado — por eso el "entrevero". El Kardex necesita una **tabla de movimientos**
(producto, fecha, tipo, motivo, cantidad, referencia al documento origen, usuario,
saldo resultante) que TODOS los flujos alimenten.

## 10. Decisiones tomadas — Parte 2 (respondidas por el usuario 2026-07-18)

- **Kardex GLOBAL** (no por almacén 1/2). ✔ implementado así.
- **Motivos fijos de sistema**: Venta (salida), Compra (ingreso) y también
  **Devolución** (ingreso) — la genera la pantalla de Devoluciones, no es manual.
  Implementado: `Devolucion` marcado `fijo='1'`; `Recojo` desactivado (los recojos
  entran por la pantalla de Devoluciones).
- **Devoluciones** (`/devoluciones`): cada devolución ahora tiene un **destino**
  que decide el admin:
  - **Regresó al almacén** (`destino='a'`): el stock se mantiene (ya fue devuelto
    por el flujo de venta); solo se deja constancia.
  - **Pérdida** (`destino='p'`): producto malogrado — se **descuenta del stock** y
    queda en el Kardex como salida "Perdida" con referencia `devolucion:ID`.
    Así se compensa la devolución de la venta cuando el producto no es vendible.
  - Columna nueva `devoluciones_nv.destino` (NULL = pendiente de decisión).
- **Ajustes manuales: SOLO ADMINS (rol 1)** — validado en el endpoint
  (`AlmacenController@registrarMovimiento`) y en `DevolucionesController@definirDestino`.
  El enlace Ingreso/Salida se quitó del menú del rol 6 (almacén); Kardex sí lo ve.
- Kardex registra desde ahora en adelante (no se reconstruye historial pasado).

## 11. Registro de la conversación

- 2026-07-18: Usuario define la idea inicial: la deuda hoy nace del pedido (cotizaciones);
  se quiere generar la deuda desde ventas con una nueva cuenta por cobrar.
- 2026-07-18: Usuario confirma: (a) hoy existe validación que exige cobrar todas las
  cuotas del pedido antes de convertirlo en venta — se eliminará; (b) la venta al
  convertirse descuenta stock de almacén — se mantiene.
- 2026-07-18: Usuario aclara el alcance: NO desaparece nada de lo existente.
  Solo se CREA la nueva cuenta por cobrar de ventas y se QUITAN validaciones.
- 2026-07-18: Usuario agrega la Parte 2: el almacén está entreverado por recojos y
  devoluciones; se pide botón de Almacén, vista Kardex y vista de ingresos/salidas
  con motivos (venta y compra son motivos fijos de sistema; el resto manuales).
- 2026-07-18: Usuario responde las preguntas abiertas: kardex global; Devolución
  también es motivo fijo; los recojos entran por la pantalla de Devoluciones, donde
  el admin define si el producto regresa al almacén o va a pérdida (malogrado),
  compensando la salida original de la venta; ajustes manuales solo admins.
