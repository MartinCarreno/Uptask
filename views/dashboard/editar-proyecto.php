<?php include_once __DIR__ . '/header-dashboard.php'; ?>

<div class="contenedor-sm">
    <?php include_once __DIR__ . '/../templates/alertas.php'; ?>

    <a href="/proyecto?id=<?php echo s($proyecto->url); ?>" class="enlace">&larr; Volver al Proyecto</a>

    <form class="formulario" method="POST" action="/editar-proyecto?id=<?php echo s($proyecto->url); ?>">
        <?php include_once __DIR__ . '/formulario-proyecto.php'; ?>
        <input type="submit" value="Guardar Cambios">
    </form>
</div>

<?php include_once __DIR__ . '/footer-dashboard.php'; ?>
