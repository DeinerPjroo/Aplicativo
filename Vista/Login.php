<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/Style.css">
    <title>Inicio</title>
</head>
<body class="fondo" >
    <section class="Panel">
        <img src="../Imagen/Logo_Universidad1.png" alt="">
        

        <section class="Login_Label">
            <h1>INICIAR SESION</h1>
        <form method="POST" action=""> <!-- (../Vista/Registro.php)Se envian los datos al controlador para validar el inicio de sesion -->

        <?php 
        include("../database/conection.php");
        include("../Controlador/Login.php"); ?>
       


            <label for="usuario">Usuario</label> <br>
            <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario"> <br>
            <label for="contraseña">Contraseña</label> <br>
            <input type="password" id="contraseña" name="contraseña" placeholder="Ingresa tu contraseña"> <br>
            <input type="submit"  name="btningresar" class="button" title="Click para ingresar" value="Ingresar"></input>
            

        </form>
        
    </section>
    </section>
    
</body>
</html>