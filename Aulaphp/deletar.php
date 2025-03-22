<?php
include('protect.php'); // This ensures only logged-in users can access this page
include('conexao.php');

$mensagem = "";

// Check if the user is trying to delete their account
if(isset($_POST['confirmar_exclusao']) && isset($_SESSION['id'])) {
    
    $user_id = $_SESSION['id'];
    
    // Delete the user from the database
    $sql_delete = "DELETE FROM usuarios WHERE id = '$user_id'";
    
    if($mysqli->query($sql_delete)) {
        // Destroy the session after successful deletion
        session_destroy();
        header("Location: index.php?deleted=true");
        exit;
    } else {
        $mensagem = "Erro ao excluir conta: " . $mysqli->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Conta</title>
    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
            background-image: linear-gradient(45deg, green, orange);
        }
        .tela-login{
            background-color: rgba(0, 0, 0, 0.8);
            position:absolute;
            top:50%;
            left: 50%;
            transform: translate(-50%,-50%);
            padding: 60px;
            border-radius: 20px;
            color: whitesmoke;
        }
        button{
            background-color: #ff3333;
            border: none;
            outline: none;
            padding: 16px;
            width: 100%;
            border-radius: 12px;
            color: white;
            font-size: 20px;
            align-items: center;
            display: block;
            margin-top: 20px;
        }
        button:hover{
            background-color: #ff6666;
            cursor: pointer;
        }
        a{
            text-decoration: none;
            text-align: center;
            color: white;
            display: block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="tela-login">
        <h1>Excluir Conta</h1>
        
        <?php
        if(!empty($mensagem)) {
            echo "<p>$mensagem</p>";
        }
        ?>
        
        <p>Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.</p>
        
        <form action="" method="POST">
            <button type="submit" name="confirmar_exclusao" value="1">Confirmar Exclusão</button>
        </form>
        
        <a href="painel.php">Cancelar</a>
    </div>
</body>
</html>