(function () {
    const configProyecto = window.UPTASK_PROYECTO || {
        url: obtenerProyecto(),
        esAdmin: true,
        miembros: []
    };

    const esAdmin = Boolean(configProyecto.esAdmin);
    let miembros = Array.isArray(configProyecto.miembros) ? [...configProyecto.miembros] : [];
    let tareas = [];
    let filtradas = [];

    obtenerTareas();

    // Botón para mostrar modal de agregar tarea (solo existe para Administrador)
    const nuevaTareaBtn = document.querySelector('#agregar-tarea');
    if (nuevaTareaBtn) {
        nuevaTareaBtn.addEventListener('click', function () {
            mostrarFormulario(false, {});
        });
    }

    // Invitar miembros al proyecto (solo Administrador)
    const formMiembro = document.querySelector('#form-agregar-miembro');
    if (formMiembro) {
        formMiembro.addEventListener('submit', agregarMiembro);
    }

    // Filtros de búsqueda
    const filtros = document.querySelectorAll('#filtros input[type="radio"]');
    filtros.forEach(radio => {
        radio.addEventListener('input', filtrarTareas);
    });

    function filtrarTareas(e) {
        const filtro = e.target.value;

        if (filtro !== '') {
            filtradas = tareas.filter(tarea => String(tarea.estado) === String(filtro));
        } else {
            filtradas = [];
        }

        mostrarTareas();
    }

    async function obtenerTareas() {
        try {
            const id = obtenerProyecto();
            const respuesta = await fetch(`/api/tareas?id=${encodeURIComponent(id)}`);
            const resultado = await respuesta.json();

            if (!respuesta.ok) {
                Swal.fire('Acceso denegado', resultado.mensaje || 'No tienes permisos para ver estas tareas', 'error');
                return;
            }

            tareas = resultado.tareas || [];
            mostrarTareas();
        } catch (error) {
            console.error(error);
        }
    }

    function obtenerNombreMiembro(asignadoId) {
        if (!asignadoId) return null;
        const encontrado = miembros.find(m => String(m.id) === String(asignadoId));
        return encontrado ? encontrado.nombre : null;
    }

    function mostrarTareas() {
        limpiarTareas();
        totalPendientes();
        totalCompletadas();

        const filtroActivo = document.querySelector('#filtros input[type="radio"]:checked')?.value || '';
        const arrayTareas = filtroActivo !== ''
            ? tareas.filter(t => String(t.estado) === String(filtroActivo))
            : tareas;

        const listadoTareas = document.querySelector('#listado-tareas');

        if (arrayTareas.length === 0) {
            const textoNoTareas = document.createElement('LI');
            textoNoTareas.textContent = 'No Hay Tareas';
            textoNoTareas.classList.add('no-tareas');
            listadoTareas.appendChild(textoNoTareas);
            return;
        }

        const estados = {
            0: 'Pendiente',
            1: 'Completa'
        };

        arrayTareas.forEach(tarea => {
            const contenedorTarea = document.createElement('LI');
            contenedorTarea.dataset.tareaId = tarea.id;
            contenedorTarea.classList.add('tarea');

            const infoTarea = document.createElement('DIV');
            infoTarea.classList.add('tarea-info');

            const nombreTarea = document.createElement('P');
            nombreTarea.textContent = tarea.nombre;

            if (esAdmin) {
                nombreTarea.title = 'Doble clic para editar';
                nombreTarea.ondblclick = function () {
                    mostrarFormulario(true, { ...tarea });
                };
            }

            infoTarea.appendChild(nombreTarea);

            const nombreResponsable = obtenerNombreMiembro(tarea.asignadoId);
            if (nombreResponsable) {
                const responsableTag = document.createElement('SPAN');
                responsableTag.classList.add('tarea-asignado');
                responsableTag.textContent = `Asignado a: ${nombreResponsable}`;
                infoTarea.appendChild(responsableTag);
            }

            const opcionesDiv = document.createElement('DIV');
            opcionesDiv.classList.add('opciones');

            // Botón de estado (disponible para Administrador y Miembro de Equipo)
            const estadoActual = String(tarea.estado);
            const btnEstadoTarea = document.createElement('BUTTON');
            btnEstadoTarea.type = 'button';
            btnEstadoTarea.classList.add('estado-tarea', estados[estadoActual].toLowerCase());
            btnEstadoTarea.textContent = estados[estadoActual];
            btnEstadoTarea.dataset.estadoTarea = estadoActual;
            btnEstadoTarea.onclick = function () {
                cambiarEstadoTarea({ ...tarea });
            };

            opcionesDiv.appendChild(btnEstadoTarea);

            // Botón eliminar (solo Administrador)
            if (esAdmin) {
                const btnEliminarTarea = document.createElement('BUTTON');
                btnEliminarTarea.type = 'button';
                btnEliminarTarea.classList.add('eliminar-tarea');
                btnEliminarTarea.dataset.idTarea = tarea.id;
                btnEliminarTarea.textContent = 'Eliminar';
                btnEliminarTarea.onclick = function () {
                    confirmarEliminarTarea({ ...tarea });
                };
                opcionesDiv.appendChild(btnEliminarTarea);
            }

            contenedorTarea.appendChild(infoTarea);
            contenedorTarea.appendChild(opcionesDiv);
            listadoTareas.appendChild(contenedorTarea);
        });
    }

    function totalPendientes() {
        const pendientes = tareas.filter(tarea => String(tarea.estado) === '0');
        const pendientesRadio = document.querySelector('#pendientes');
        if (pendientesRadio) {
            pendientesRadio.disabled = pendientes.length === 0;
        }
    }

    function totalCompletadas() {
        const completadas = tareas.filter(tarea => String(tarea.estado) === '1');
        const completadasRadio = document.querySelector('#completadas');
        if (completadasRadio) {
            completadasRadio.disabled = completadas.length === 0;
        }
    }

    function mostrarFormulario(editar = false, tarea = {}) {
        const modal = document.createElement('DIV');
        modal.classList.add('modal');

        const opcionesMiembros = miembros.map(m => {
            const seleccionado = String(tarea.asignadoId || '') === String(m.id) ? 'selected' : '';
            return `<option value="${m.id}" ${seleccionado}>${escaparHTML(m.nombre)} (${escaparHTML(m.email)})</option>`;
        }).join('');

        modal.innerHTML = `
            <form class="formulario nueva-tarea">
                <legend>${editar ? 'Editar Tarea' : 'Añade una nueva tarea'}</legend>
                <div class="campo">
                    <label for="tarea">Tarea</label>
                    <input
                        type="text"
                        name="tarea"
                        placeholder="${editar ? 'Edita la Tarea' : 'Añadir tarea al proyecto actual'}"
                        id="tarea"
                        value="${escaparHTML(tarea.nombre || '')}"
                    />
                </div>
                <div class="campo">
                    <label for="asignadoId">Asignar a</label>
                    <select id="asignadoId" name="asignadoId">
                        <option value="">-- Sin asignar --</option>
                        ${opcionesMiembros}
                    </select>
                </div>
                <div class="opciones">
                    <input type="submit" class="submit-nueva-tarea" value="${editar ? 'Guardar Cambios' : 'Agregar Tarea'}" />
                    <button type="button" class="cerrar-modal">Cancelar</button>
                </div>
            </form>
        `;

        const formulario = modal.querySelector('.formulario');
        setTimeout(() => formulario.classList.add('animar'), 0);

        modal.querySelector('.cerrar-modal').addEventListener('click', function () {
            formulario.classList.add('cerrar');
            setTimeout(() => modal.remove(), 300);
        });

        formulario.addEventListener('submit', function (e) {
            e.preventDefault();
            const nombreTarea = modal.querySelector('#tarea').value.trim();
            const asignadoId = modal.querySelector('#asignadoId').value;

            if (nombreTarea === '') {
                mostrarAlerta('El nombre de la tarea es Obligatorio', 'error', formulario.querySelector('legend'));
                return;
            }

            if (editar) {
                tarea.nombre = nombreTarea;
                tarea.asignadoId = asignadoId || null;
                actualizarTarea(tarea);
            } else {
                agregarTarea(nombreTarea, asignadoId);
            }
        });

        document.querySelector('.dashboard').appendChild(modal);
    }

    function mostrarAlerta(mensaje, tipo, referencia) {
        const alertaPrevia = referencia.parentElement.querySelector('.alerta');
        if (alertaPrevia) {
            alertaPrevia.remove();
        }

        const alerta = document.createElement('DIV');
        alerta.classList.add('alerta', tipo);
        alerta.textContent = mensaje;

        referencia.parentElement.insertBefore(alerta, referencia.nextElementSibling);

        setTimeout(() => {
            alerta.remove();
        }, 4000);
    }

    async function agregarTarea(nombre, asignadoId = '') {
        const datos = new FormData();
        datos.append('nombre', nombre);
        datos.append('proyectoId', obtenerProyecto());
        if (asignadoId) {
            datos.append('asignadoId', asignadoId);
        }

        try {
            const respuesta = await fetch('/api/tarea', {
                method: 'POST',
                body: datos
            });

            const resultado = await respuesta.json();

            if (!respuesta.ok || resultado.tipo !== 'exito') {
                mostrarAlerta(resultado.mensaje || 'No se pudo crear la tarea', 'error', document.querySelector('.modal .formulario legend'));
                return;
            }

            mostrarAlerta(resultado.mensaje, resultado.tipo, document.querySelector('.modal .formulario legend'));

            const modal = document.querySelector('.modal');
            if (modal) {
                setTimeout(() => modal.remove(), 600);
            }

            const tareaObj = {
                id: String(resultado.resultado),
                nombre: nombre,
                estado: '0',
                proyectoId: resultado.proyectoId,
                asignadoId: resultado.asignadoId || null
            };

            tareas = [...tareas, tareaObj];
            mostrarTareas();
        } catch (error) {
            console.error(error);
        }
    }

    function cambiarEstadoTarea(tarea) {
        tarea.estado = String(tarea.estado) === '1' ? '0' : '1';
        actualizarTarea(tarea);
    }

    async function actualizarTarea(tarea) {
        const { estado, id, nombre, asignadoId } = tarea;

        const datos = new FormData();
        datos.append('id', id);
        datos.append('nombre', nombre);
        datos.append('estado', estado);
        datos.append('proyectoId', obtenerProyecto());
        datos.append('asignadoId', asignadoId ?? '');

        try {
            const respuesta = await fetch('/api/tarea/actualizar', {
                method: 'POST',
                body: datos
            });

            const resultado = await respuesta.json();

            if (!respuesta.ok || !resultado.respuesta || resultado.respuesta.tipo !== 'exito') {
                Swal.fire('Error', resultado.mensaje || 'No tienes permisos para actualizar esta tarea', 'error');
                return;
            }

            const modal = document.querySelector('.modal');
            if (modal) {
                modal.remove();
            }

            tareas = tareas.map(tareaMemoria => {
                if (String(tareaMemoria.id) === String(id)) {
                    tareaMemoria.estado = String(estado);
                    tareaMemoria.nombre = nombre;
                    tareaMemoria.asignadoId = asignadoId || null;
                }
                return tareaMemoria;
            });

            mostrarTareas();
        } catch (error) {
            console.error(error);
        }
    }

    function confirmarEliminarTarea(tarea) {
        Swal.fire({
            title: '¿Eliminar Tarea?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                eliminarTarea(tarea);
            }
        });
    }

    async function eliminarTarea(tarea) {
        const datos = new FormData();
        datos.append('id', tarea.id);
        datos.append('proyectoId', obtenerProyecto());

        try {
            const respuesta = await fetch('/api/tarea/eliminar', {
                method: 'POST',
                body: datos
            });

            const resultado = await respuesta.json();

            if (!respuesta.ok || !resultado.resultado) {
                Swal.fire('Error', resultado.mensaje || 'No se pudo eliminar la tarea', 'error');
                return;
            }

            Swal.fire('Eliminada', resultado.mensaje, 'success');
            tareas = tareas.filter(tareaMemoria => String(tareaMemoria.id) !== String(tarea.id));
            mostrarTareas();
        } catch (error) {
            console.error(error);
        }
    }

    async function agregarMiembro(e) {
        e.preventDefault();
        const inputEmail = document.querySelector('#email-miembro');
        const email = inputEmail.value.trim();

        if (!email) return;

        const datos = new FormData();
        datos.append('proyectoId', obtenerProyecto());
        datos.append('email', email);

        try {
            const respuesta = await fetch('/api/proyecto/miembro', {
                method: 'POST',
                body: datos
            });

            const resultado = await respuesta.json();

            if (!respuesta.ok || resultado.tipo !== 'exito') {
                Swal.fire('No se pudo agregar', resultado.mensaje || 'Error al agregar miembro', 'error');
                return;
            }

            const nuevo = resultado.miembro;
            miembros = [...miembros, nuevo];

            const listadoMiembros = document.querySelector('#listado-miembros');
            if (listadoMiembros) {
                const li = document.createElement('LI');
                li.classList.add('miembro');
                li.innerHTML = `
                    <span>${escaparHTML(nuevo.nombre)} (${escaparHTML(nuevo.email)})</span>
                    <span class="badge-rol rol-miembro">Miembro</span>
                `;
                listadoMiembros.appendChild(li);
            }

            inputEmail.value = '';
            Swal.fire('Miembro agregado', resultado.mensaje, 'success');
        } catch (error) {
            console.error(error);
        }
    }

    function obtenerProyecto() {
        const proyectoParams = new URLSearchParams(window.location.search);
        return proyectoParams.get('id') || '';
    }

    function limpiarTareas() {
        const listadoTareas = document.querySelector('#listado-tareas');
        while (listadoTareas.firstChild) {
            listadoTareas.removeChild(listadoTareas.firstChild);
        }
    }

    function escaparHTML(texto) {
        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
})();)();
