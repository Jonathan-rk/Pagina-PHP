<?php
include('conexao.php');
$mensagem = "";

if(isset($_POST['usuario']) && isset($_POST['senha'])) {
    if(strlen($_POST['usuario']) == 0) {
        $mensagem = "Usuário não informado";
    } else if(strlen($_POST['senha']) == 0) {
        $mensagem = "Preencha sua nova senha";
    } else {
        $usuario = $mysqli->real_escape_string($_POST['usuario']);
        $nova_senha = $mysqli->real_escape_string($_POST['senha']);
        
        // Primeiro verificamos se o usuário existe
        $sql_check = "SELECT * FROM usuarios WHERE usuario = '$usuario'";
        $check_query = $mysqli->query($sql_check) or die("Falha na execução do código SQL: " . $mysqli->error);
        
        if($check_query->num_rows == 1) {
            // Usuário existe, vamos atualizar a senha
            $sql_update = "UPDATE usuarios SET senha = '$nova_senha' WHERE usuario = '$usuario'";
            if($mysqli->query($sql_update)) {
                $mensagem = "Senha atualizada com sucesso!";
            } else {
                $mensagem = "Erro ao atualizar senha: " . $mysqli->error;
            }
        } else {
            $mensagem = "Usuário não encontrado";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha</title>
    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
            background-color: rgb(5, 45, 85);
        }
        .tela-login{
            background-color: rgb(8, 76, 145);
            position:absolute;
            top: 50%;
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

    </style>
</head>
<body>
    <div class="tela-login">
    <h1>Atualizar senha</h1>
    <?php if(!empty($mensagem)) echo "<p style='color: yellow; display: block; text-align: center;'>$mensagem</p>"; ?>
    <form action="" method="POST">
        <br>
            <input type="text" name="usuario" placeholder="Usuário">
            <br><br>
            <input type="password" name="senha" placeholder="Senha">
            <br><br>   
            <button type="submit">Atualizar</button>
    </form>
    <a href="index.php">Fazer Login</a>
    </div>
</body>
</html>