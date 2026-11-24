const mobileMenuBtn = document.querySelector('#mobile-menu');
const cerrarMenuBtn = document.querySelector('#cerrar-menu');
const sidebar = document.querySelector('.sidebar');

if (mobileMenuBtn && sidebar) {
    mobileMenuBtn.addEventListener('click', function () {
        sidebar.classList.add('mostrar');
    });
}

if (cerrarMenuBtn && sidebar) {
    cerrarMenuBtn.addEventListener('click', function () {
        sidebar.classList.add('ocultar');
        setTimeout(() => {
            sidebar.classList.remove('mostrar');
            sidebar.classList.remove('ocultar');
        }, 1000);
    });
}

// Elimina la clase de mostrar en tamaño de tablet y mayores
window.addEventListener('resize', function () {
    if (sidebar && window.innerWidth >= 768) {
        sidebar.classList.remove('mostrar');
    }
});

// Alternancia Modo Claro / Modo Oscuro con localStorage (HU-18 / RF-08)
(function inicializarTema() {
    const botonesTema = document.querySelectorAll('.btn-dark-mode');
    const temaGuardado = localStorage.getItem('uptask-theme');

    function aplicarTema(esOscuro) {
        document.body.classList.toggle('dark-mode', esOscuro);
        botonesTema.forEach(btn => {
            btn.textContent = esOscuro ? 'Modo Claro' : 'Modo Oscuro';
        });
    }

    aplicarTema(temaGuardado === 'dark');

    botonesTema.forEach(btn => {
        btn.addEventListener('click', function () {
            const activarOscuro = !document.body.classList.contains('dark-mode');
            aplicarTema(activarOscuro);
            localStorage.setItem('uptask-theme', activarOscuro ? 'dark' : 'light');
        });
    });
})();
