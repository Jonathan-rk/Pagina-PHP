<?php
include('protect.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel</title>
    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
            background-image: linear-gradient(45deg, green, orange);
        }
        .tela-painel{
            background-color: rgba(0, 0, 0, 0.8);
            position:absolute;
            top:50%;
            left: 50%;
            transform: translate(-50%,-50%);
            padding: 60px;
            border-radius: 20px;
            color: whitesmoke;
        }

        input{
            padding: 16px;
            border: none;
            outline: none;
            font-size: 18px;
            border-radius: 12px;
            align-items: center;
            display: block;
            margin-bottom: -10px;
        }

        button{
            background-color: dodgerblue;
            border: none;
            outline: none;
            padding: 16px;
            width: 100%;
            border-radius: 12px;
            color: white;
            font-size: 20px;
            align-items: center;
            display: block;
            margin-bottom: 15px;
        }
        button:hover{
            background-color: deepskyblue;
            cursor: pointer;
        }

        a{
            text-decoration: none;
            text-align: center;
            color: white;
            display: block;
            margin-top: 20px;
            margin-bottom: -10px;
        }

        h2{
            text-align: center;
            display: block;
        }
    </style>
</head>
<body>
    <div class="tela-painel">
    <h2>Bem vindo ao Painel!</h2>
    <form action="logout.php" method="post">
        <button type="submit">Sair</button>
    </form>
    <form action="atualizarPerfil.php" method="post">
        <button type="submit">Atualizar Perfil</button>
    </form>
    <form action="deletar.php" method="post">
        <button type="submit">Deletar Conta</button>
    </form>
    </div>
</body>
</html>