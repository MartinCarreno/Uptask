<div class="campo">
    <label for="proyecto">Nombre del Proyecto</label>
    <input
        type="text"
        name="proyecto"
        id="proyecto"
        placeholder="Nombre del Proyecto"
        value="<?php echo s($proyecto->proyecto ?? ''); ?>"
    />
</div>

<div class="campo">
    <label for="cliente">Cliente</label>
    <input
        type="text"
        name="cliente"
        id="cliente"
        placeholder="Nombre del Cliente u Organización"
        value="<?php echo s($proyecto->cliente ?? ''); ?>"
    />
</div>

<div class="campo">
    <label for="descripcion">Descripción</label>
    <textarea
        name="descripcion"
        id="descripcion"
        rows="4"
        placeholder="Descripción breve del alcance o entregables del proyecto"
    ><?php echo s($proyecto->descripcion ?? ''); ?></textarea>
</div>
