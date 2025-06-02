const sidebar = document.querySelector(".sidebar");
sidebar.classList.add("collapsed"); // Sidebar cerrado por defecto
document.body.classList.add("sidebar-collapsed"); // Agregar clase al body
const sidebarToggler = document.querySelector(".sidebar-toggler");
const menuToggler = document.querySelector(".menu-toggler");

const collapsedSidebarHeight = "56px";
const fullSidebarHeight = "calc(100vh - 32px)";

// Función placeholder para evitar errores si se llama desde algún lugar
window.generateReportsLegends = function() {
    console.log("generateReportsLegends fue llamada, pero no está implementada.");
};

//Toggle sidebar's collapsed state
sidebarToggler.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed"); // Toggle collapsed state
    // Sincronizar la clase del body con el estado del sidebar
    if (sidebar.classList.contains("collapsed")) {
        document.body.classList.add("sidebar-collapsed");
    } else {
        document.body.classList.remove("sidebar-collapsed");
    }
});

//update sidebar height and menu toggle text
const togglemenu = (isMenuActive) => {
    sidebar.style.height = isMenuActive ? `${sidebar.scrollHeight}px` : collapsedSidebarHeight; 
    menuToggler.querySelector("span").innerText = isMenuActive ? "close" : "menu";
};

//toggle menu active class and adjust height
menuToggler.addEventListener("click", () => {
    togglemenu(sidebar.classList.toggle("menu-active"));
})

// Ajustar la altura de la barra lateral al cambiar el tamaño de la ventana
window.addEventListener("resize", () => {
    if (window.innerHeight >= 1024) {
        sidebar.style.height = fullSidebarHeight;
    } else {
        sidebar.classList.remove("collapsed");
        sidebar.style.height = "auto";
        togglemenu(sidebar.classList.contains("menu-active"));
    }
});

// Verifica si hay elementos con IDs específicos (solo si existen en la página)
document.addEventListener("DOMContentLoaded", () => {
    const botonReporteSiguiente = document.getElementById("generarReporteSiguiente");
    if (botonReporteSiguiente) {
        console.log("El botón 'generarReporteSiguiente' está correctamente definido.");
    }
    // No mostrar error si no existe, ya que es normal que no esté en todas las páginas
});