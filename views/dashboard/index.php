<?php include_once __DIR__ . '/header-dashboard.php'; ?>

    <?php if (count($proyectos) === 0) { ?>
        <p class="no-proyectos">No Hay Proyectos Aún <a href="/crear-proyecto">Comienza creando uno</a></p>
    <?php } else { ?>
        <ul class="listado-proyectos">
            <?php foreach ($proyectos as $proyecto) { ?>
                <?php $esAdmin = ((int) ($proyecto->rolId ?? 2) === 1); ?>
                <li class="proyecto">
                    <div class="proyecto-info">
                        <a href="/proyecto?id=<?php echo s($proyecto->url); ?>" class="proyecto-titulo">
                            <?php echo s($proyecto->proyecto); ?>
                        </a>

                        <?php if (!empty($proyecto->cliente)) { ?>
                            <p class="proyecto-cliente">Cliente: <?php echo s($proyecto->cliente); ?></p>
                        <?php } ?>

                        <span class="badge-rol <?php echo $esAdmin ? 'rol-admin' : 'rol-miembro'; ?>">
                            <?php echo $esAdmin ? 'Administrador' : 'Miembro'; ?>
                        </span>
                    </div>

                    <?php if ($esAdmin) { ?>
                        <div class="proyecto-acciones">
                            <a
                                href="/editar-proyecto?id=<?php echo s($proyecto->url); ?>"
                                class="btn-editar-proyecto"
                            >Editar</a>

                            <form
                                method="POST"
                                action="/eliminar-proyecto"
                                class="form-eliminar-proyecto"
                            >
                                <input type="hidden" name="url" value="<?php echo s($proyecto->url); ?>">
                                <button type="submit" class="btn-eliminar-proyecto">Eliminar</button>
                            </form>
                        </div>
                    <?php } ?>
                </li>
            <?php } ?>
        </ul>
    <?php } ?>

<?php include_once __DIR__ . '/footer-dashboard.php'; ?>

<?php
$script .= '
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll(".form-eliminar-proyecto").forEach(form => {
            form.addEventListener("submit", function (e) {
                e.preventDefault();
                Swal.fire({
                    title: "¿Eliminar Proyecto?",
                    text: "Se eliminará el proyecto junto con todas sus tareas asociadas.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Sí, eliminar",
                    cancelButtonText: "Cancelar"
                }).then(result => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
';
?>
