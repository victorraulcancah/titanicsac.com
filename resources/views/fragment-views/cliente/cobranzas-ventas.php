<?php
// Cuentas por Cobrar de VENTAS: reutiliza la pantalla de cobranzas completa,
// pero activa el modo "solo ventas" (tipo_co='v'). Los pedidos/cotizaciones
// se siguen viendo en la pantalla de Cobranzas original.
?>
<script>
    window.__COBRANZAS_VENTAS__ = true;
    setTimeout(function () {
        var titulo = document.querySelector('.page-title');
        if (titulo) titulo.textContent = 'Cuentas por Cobrar 2';
    }, 0);
</script>
<?php include __DIR__ . '/cobranzas.php'; ?>
