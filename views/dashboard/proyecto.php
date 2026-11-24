<?php include_once __DIR__ . '/header-dashboard.php'; ?>

    <div class="contenedor-sm">
        <div class="proyecto-resumen">
            <p class="proyecto-meta">
                <strong>Cliente:</strong> <?php echo s($proyecto->cliente); ?>
                <span class="badge-rol <?php echo $esAdmin ? 'rol-admin' : 'rol-miembro'; ?>">
                    <?php echo $esAdmin ? 'Administrador' : 'Miembro'; ?>
                </span>
            </p>
            <?php if (!empty($proyecto->descripcion)) { ?>
                <p class="proyecto-descripcion"><?php echo s($proyecto->descripcion); ?></p>
            <?php } ?>
        </div>

        <?php if ($esAdmin) { ?>
            <div class="contenedor-nueva-tarea">
                <button
                    type="button"
                    class="agregar-tarea"
                    id="agregar-tarea"
                >&#43; Nueva Tarea</button>

                <a
                    href="/editar-proyecto?id=<?php echo s($proyecto->url); ?>"
                    class="enlace-editar-proyecto"
                >Editar Proyecto</a>
            </div>
        <?php } ?>

        <section class="equipo-proyecto">
            <h3>Equipo del Proyecto</h3>
            <ul id="listado-miembros" class="listado-miembros">
                <?php foreach ($miembros as $miembro) { ?>
                    <li class="miembro">
                        <span><?php echo s($miembro['nombre']); ?> (<?php echo s($miembro['email']); ?>)</span>
                        <span class="badge-rol <?php echo ((int) $miembro['rolId'] === 1) ? 'rol-admin' : 'rol-miembro'; ?>">
                            <?php echo ((int) $miembro['rolId'] === 1) ? 'Admin' : 'Miembro'; ?>
                        </span>
                    </li>
                <?php } ?>
            </ul>

            <?php if ($esAdmin) { ?>
                <form id="form-agregar-miembro" class="form-miembro">
                    <input
                        type="email"
                        id="email-miembro"
                        placeholder="Correo de usuario registrado para invitar"
                        required
                    />
                    <button type="submit" class="btn-agregar-miembro">Agregar Miembro</button>
                </form>
            <?php } ?>
        </section>

        <div id="filtros" class="filtros">
            <div class="filtros-inputs">
                <h2>Filtros:</h2>
                <div class="campo">
                    <label for="todas">Todas</label>
                    <input
                        type="radio"
                        id="todas"
                        name="filtro"
                        value=""
                        checked
                    />
                </div>

                <div class="campo">
                    <label for="completadas">Completadas</label>
                    <input
                        type="radio"
                        id="completadas"
                        name="filtro"
                        value="1"
                    />
                </div>

                <div class="campo">
                    <label for="pendientes">Pendientes</label>
                    <input
                        type="radio"
                        id="pendientes"
                        name="filtro"
                        value="0"
                    />
                </div>
            </div>
        </div>

        <ul id="listado-tareas" class="listado-tareas"></ul>
    </div>

<?php include_once __DIR__ . '/footer-dashboard.php'; ?>

<?php
$configProyecto = json_encode([
    'url' => $proyecto->url,
    'esAdmin' => (bool) $esAdmin,
    'miembros' => $miembros
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$script .= '
    <script>window.UPTASK_PROYECTO = ' . $configProyecto . ';</script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/build/js/tareas.js"></script>
';
?>>
