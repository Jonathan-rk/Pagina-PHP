<?php
include('protect.php');
include('conexao.php');

$mensagem = "";
$tipo_mensagem = "sucesso";

// Verificar se o usuário confirmou a exclusão
if(isset($_POST['confirmar_exclusao'])) {
    $id = $_SESSION['id'];
    $tipo_usuario = $_SESSION['tipo'];
    
    // Determinar qual tabela usar com base no tipo de usuário
    if($tipo_usuario == 'funcionario') {
        $tabela = 'funcionario';
    } else {
        $tabela = 'clientes';
    }
    
    // Executar a exclusão
    $sql_delete = "DELETE FROM $tabela WHERE id = '$id'";
    
    if($mysqli->query($sql_delete)) {
        // Destruir a sessão após excluir a conta
        session_start();
        session_unset();
        session_destroy();
        
        // Redirecionar para a página de login com mensagem de sucesso
        header("Location: index.php?excluido=1");
        exit;
    } else {
        $mensagem = "Erro ao excluir conta: " . $mysqli->error;
        $tipo_mensagem = "erro";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Conta</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #043972;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            background-color: rgb(8, 76, 145);
            padding: 40px;
            border-radius: 15px;
            color: white;
            width: 90%;
            max-width: 500px;
            text-align: center;
        }
        
        h1 {
            margin-top: 0;
            margin-bottom: 30px;
        }
        
        p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        button {
            background-color: #dc3545;
            border: none;
            padding: 15px 25px;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            margin-bottom: 15px;
        }
        
        button:hover {
            background-color: #c82333;
        }
        
        a {
            display: inline-block;
            text-decoration: none;
            color: white;
            border: 2px solid dodgerblue;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        a:hover {
            background-color: dodgerblue;
        }
        
        .mensagem {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .mensagem.erro {
            background-color: rgba(220, 53, 69, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Excluir Conta</h1>
        
        <?php if(!empty($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem == 'erro' ? 'erro' : ''; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <p>Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.</p>
        
        <form action="" method="POST">
            <button type="submit" name="confirmar_exclusao" value="1">Confirmar Exclusão</button>
        </form>
        
        <a href="<?php echo $_SESSION['tipo'] == 'funcionario' ? 'painel.php' : 'painel_cliente.php'; ?>">Cancelar</a>
    </div>
</body>
</html>
