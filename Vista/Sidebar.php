<!DOCTYPE html>
<aside class="sidebar">
        <header class="sidebar-header">
            <a href="javascript:location.reload(true);" class="header-logo">
                <img src="../Imagen/logo_sinLetra.png" alt="Logo Universidad">
            </a>
            <!-- Botón toggle para desktop (oculto en móvil) -->
             <button class="toggler sidebar-toggler">
                <span class="material-symbols-outlined">
                    <img src="../Imagen/Iconos/Menu_3lineas.svg" alt="" />
                </span>
            </button>
        </header>






        <nav class="sidebar-nav">
            <!--Nav primario-->
            <ul class="nav-list nav-primero">



                <!-- ROL ADMINISTRADOR -->
                <!-- administrar usuarios administrador -->
                <?php if ($_SESSION['usuario_rol'] === 'Administrador'): ?>
                    <li class="nav-item">
                        <a href="../Vista/Administrar_Usuarios.php" class="nav-link">
                            <img src="../Imagen/Iconos/Administrar_Usuarios.svg" alt="" />
                            <span class="nav-label">Administrar Usuarios</span>

                        </a>
                        <span class="nav-tooltip">Administrar Usuarios</span>
                    </li>
                <?php endif; ?>




                <!-- registros administrador -->
                <?php if ($_SESSION['usuario_rol'] === 'Administrador'): ?>
                    <li class="nav-item">
                        <a href="../Vista/Registro.php" class="nav-link">
                            <img src="../Imagen/Iconos/Registros.svg" alt="" />
                            <span class="nav-label">Registro</span>
                        </a>
                        <span class="nav-tooltip">Registro</span>
                    </li>
                <?php endif; ?>




                



                <?php if ($_SESSION['usuario_rol'] === 'Administrador'): ?>
                    <li class="nav-item">
                        <a href="../Vista/Estadisticas.php" class="nav-link">
                            <img src="../Imagen/Iconos/Estadistica.svg" alt="" />
                            <span class="nav-label">Estadisticas</span>
                        </a>
                        <span class="nav-tooltip">Estadisticas</span>
                    </li>
                <?php endif; ?>








                <!-- ROL DOCENTE y estudiante -->

                <?php if ($_SESSION['usuario_rol'] !== 'Administrador'): ?>
                    <li class="nav-item">
                        <a href="../Vista/Reservas_Usuarios.php" class="nav-link">
                            <img src="../Imagen/Iconos/Reservas.svg" alt="" />
                            <span class="nav-label">Reservas</span>
                        </a>
                        <span class="nav-tooltip">Reservas</span>
                    </li>
                <?php endif; ?>

                <?php if ($_SESSION['usuario_rol'] !== 'Administrador'): ?>
                    <li class="nav-item">
                        <a href="../Vista/Historial.php" class="nav-link">
                            <img src="../Imagen/Iconos/Historial.svg" alt="" />
                            <span class="nav-label">Historial</span>
                        </a>
                        <span class="nav-tooltip">Historial</span>
                    </li>
                <?php endif; ?>








                <!-- se cierra el nav-primero ( de arriba) -->
            </ul>

            <ul class="nav-list nav-segundo">
                <li class="nav-item">
                    <a href="../Vista/Perfil.php" class="nav-link">
                        <img src="../Imagen/Iconos/Perfil.svg" alt="" />
                        <span class="nav-label">Perfil</span>
                    </a>
                    <span class="nav-tooltip">Perfil</span>
                </li>



                <li class="nav-item">
                    <a href="../Controlador/logout.php" class="nav-link">
                        <img src="../Imagen/Iconos/Cerrar_Sesion.svg" alt="" />                    <span class="nav-label">Cerrar sesión</span>
                    </a>
                    <span class="nav-tooltip">Cerrar sesión</span>
                </li>
            </ul>
        </nav>
    </aside>
    <script src="../js/sidebar.js"></script>