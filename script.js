const sidebar = document.querySelector(".sidebar");
const sidebarToggler = document.querySelector(".sidebar-toggler");
const menuToggler = document.querySelector(".menu-toggler");

const collapsedSidebarHeight = "56px";
const fullSidebarHeight = "calc(100vh - 32px)";

//Toggle sidebar"s collapsed state

sidebarToggler.addEventListener("click", ()=> {
 sidebar.classList.toggle("collapsed");
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