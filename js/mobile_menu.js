/*
===============================================================================
                         MENÚ MÓVIL RESPONSIVE
         JavaScript para controlar el sidebar móvil en todas las páginas
===============================================================================
*/

document.addEventListener('DOMContentLoaded', function() {
    // Crear elementos del menú móvil si no existen
    initializeMobileMenu();
    
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (!menuToggle || !sidebar || !overlay) {
        console.warn('Elementos del menú móvil no encontrados');
        return;
    }    // Función para abrir el menú
    function openMenu() {
        console.log('=== ABRIENDO MENÚ MÓVIL ===');
        
        // Remover clase collapsed si existe (para móviles)
        sidebar.classList.remove('collapsed');
        sidebar.classList.add('active');
        overlay.classList.add('active');
        menuToggle.innerHTML = '<span class="close-icon">×</span>';
        toggleBodyScroll(true);
        
        // Debug detallado
        console.log('Sidebar classes:', sidebar.className);
        console.log('Sidebar computed style left:', window.getComputedStyle(sidebar).left);
        console.log('Sidebar computed style display:', window.getComputedStyle(sidebar).display);
        console.log('Sidebar computed style visibility:', window.getComputedStyle(sidebar).visibility);
        
        // Verificar contenido del sidebar
        const sidebarNav = sidebar.querySelector('.sidebar-nav');
        const navItems = sidebar.querySelectorAll('.nav-item');
        const navLinks = sidebar.querySelectorAll('.nav-link');
        
        console.log('Sidebar nav encontrado:', !!sidebarNav);
        console.log('Nav items encontrados:', navItems.length);
        console.log('Nav links encontrados:', navLinks.length);
        
        if (sidebarNav) {
            console.log('Sidebar nav display:', window.getComputedStyle(sidebarNav).display);
            console.log('Sidebar nav visibility:', window.getComputedStyle(sidebarNav).visibility);
        }
        
        // Forzar visibilidad de elementos internos
        if (sidebarNav) {
            sidebarNav.style.display = 'block';
            sidebarNav.style.visibility = 'visible';
            sidebarNav.style.opacity = '1';
        }
        
        navItems.forEach((item, index) => {
            console.log(`Nav item ${index} display:`, window.getComputedStyle(item).display);
            item.style.display = 'list-item';
            item.style.visibility = 'visible';
            item.style.opacity = '1';
        });
        
        navLinks.forEach((link, index) => {
            console.log(`Nav link ${index} display:`, window.getComputedStyle(link).display);
            link.style.display = 'flex';
            link.style.visibility = 'visible';
            link.style.opacity = '1';
        });
        
        console.log('=== MENÚ MÓVIL ABIERTO ===');
    }// Función para cerrar el menú
    function closeMenu() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        menuToggle.innerHTML = '<img src="../Imagen/Iconos/Menu_3lineas.svg" alt="Menú" class="menu-icon">';
        toggleBodyScroll(false);
    }
    
    // Toggle del menú al hacer clic en el botón
    menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (sidebar.classList.contains('active')) {
            closeMenu();
        } else {
            openMenu();
        }
    });
    
    // Cerrar menú al hacer clic en el overlay
    overlay.addEventListener('click', closeMenu);
    
    // Cerrar menú al hacer clic en un enlace del sidebar
    const sidebarLinks = sidebar.querySelectorAll('a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Pequeño delay para permitir que la navegación se complete
            setTimeout(closeMenu, 100);
        });
    });
    
    // Cerrar menú con la tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeMenu();
        }
    });
    
    // Cerrar menú al cambiar el tamaño de ventana si se hace muy grande
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar.classList.contains('active')) {
            closeMenu();
        }
    });
    
    // Prevenir scroll del body cuando el menú está abierto
    function toggleBodyScroll(disable) {
        if (disable) {
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.width = '100%';
        } else {
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.width = '';
        }
    }    // Función para inicializar los elementos del menú móvil
    function initializeMobileMenu() {
        // Crear botón de menú si no existe
        if (!document.getElementById('menuToggle')) {
            const menuButton = document.createElement('button');
            menuButton.id = 'menuToggle';
            menuButton.className = 'menu-toggle';
            menuButton.innerHTML = '<img src="../Imagen/Iconos/Menu_3lineas.svg" alt="Menú" class="menu-icon">';
            document.body.appendChild(menuButton);
        }
        
        // Crear overlay si no existe
        if (!document.getElementById('sidebarOverlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'sidebarOverlay';
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }
    }
    
    // Gestión de eventos táctiles para mejor experiencia móvil
    let touchStartX = null;
    let touchStartY = null;
    
    // Detectar swipe para cerrar el menú
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    });
    
    document.addEventListener('touchmove', function(e) {
        if (!touchStartX || !touchStartY) return;
        
        const touchEndX = e.touches[0].clientX;
        const touchEndY = e.touches[0].clientY;
        
        const diffX = touchStartX - touchEndX;
        const diffY = touchStartY - touchEndY;
        
        // Si es un swipe horizontal hacia la izquierda y el menú está abierto
        if (Math.abs(diffX) > Math.abs(diffY) && diffX > 50 && sidebar.classList.contains('active')) {
            closeMenu();
        }
    });
    
    document.addEventListener('touchend', function() {
        touchStartX = null;
        touchStartY = null;
    });
});

/*
===============================================================================
                           FUNCIONES GLOBALES
===============================================================================
*/

// Función global para abrir el menú (puede ser llamada desde otros scripts)
window.openMobileMenu = function() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuToggle = document.getElementById('menuToggle');
    
    if (sidebar && overlay && menuToggle) {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        menuToggle.innerHTML = '<span class="close-icon">×</span>';
        document.body.style.overflow = 'hidden';
    }
};

// Función global para cerrar el menú (puede ser llamada desde otros scripts)
window.closeMobileMenu = function() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuToggle = document.getElementById('menuToggle');
    
    if (sidebar && overlay && menuToggle) {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        menuToggle.innerHTML = '<img src="../Imagen/Iconos/Menu_3lineas.svg" alt="Menú" class="menu-icon">';
        document.body.style.overflow = '';
    }
};

// Función para verificar si el menú móvil está activo
window.isMobileMenuOpen = function() {
    const sidebar = document.querySelector('.sidebar');
    return sidebar ? sidebar.classList.contains('active') : false;
};
