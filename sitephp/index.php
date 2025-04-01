<?php
// Inclui o arquivo de conexão com o banco de dados
include('conexao.php');

// Inicializa a variável de mensagem
$mensagem = "";

// Verifica se o formulário foi enviado
if(isset($_POST['usuario']) && isset($_POST['senha'])) {
    
    // Valida se o campo de usuário foi preenchido
    if(strlen($_POST['usuario']) == 0) {
        $mensagem = "Preencha seu usuário";
    } 
    // Valida se o campo de senha foi preenchido
    else if(strlen($_POST['senha']) == 0) {
        $mensagem = "Preencha sua senha";
    } 
    // Processa a tentativa de login
    else {
        
        // Sanitiza os dados de entrada para prevenir injeção SQL
        $usuario = $mysqli->real_escape_string($_POST['usuario']);
        $senha = $mysqli->real_escape_string($_POST['senha']);
        $tipo_usuario = isset($_POST['tipo_usuario']) ? $_POST['tipo_usuario'] : 'cliente';
        
        // Determina a tabela e página de redirecionamento com base no tipo de usuário
        if($tipo_usuario == 'funcionario') {
            $sql_code = "SELECT * FROM funcionario WHERE usuario = '$usuario' AND senha = '$senha'";
            $redirect_page = "painel.php";
        } else {
            $sql_code = "SELECT * FROM clientes WHERE usuario = '$usuario' AND senha = '$senha'";
            $redirect_page = "painel_cliente.php";
        }
        
        // Executa a consulta SQL
        $sql_query = $mysqli->query($sql_code);
        
        // Verifica se houve erro na consulta
        if(!$sql_query) {
            $mensagem = "Erro na consulta: " . $mysqli->error;
        } else {
            $quantidade = $sql_query->num_rows;
            
            // Verifica se encontrou um usuário com as credenciais fornecidas
            if($quantidade == 1) {
                
                // Obtém os dados do usuário
                $usuario_data = $sql_query->fetch_assoc();
                
                // Inicia a sessão se ainda não estiver iniciada
                if(!isset($_SESSION)) {
                    session_start();
                }
                
                // Armazena informações do usuário na sessão
                $_SESSION['id'] = $usuario_data['id'];
                $_SESSION['nome'] = $usuario_data['usuario'];
                $_SESSION['tipo'] = $tipo_usuario; // Armazena o tipo de usuário na sessão
                
                // Redireciona para a página apropriada
                header("Location: $redirect_page");
                exit;
                
            } else {
                // Mensagem de erro para credenciais inválidas
                $mensagem = "Falha ao logar! Usuário ou senha incorretos";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #043972;
        }
        
        .tela-login {
            background-color: rgb(8, 76, 145);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 80px;
            border-radius: 15px;
            color: white;
        }
        
        input {
            padding: 15px;
            border: none;
            outline: none;
            font-size: 15px;
            width: 100%;
            border-radius: 10px;
            box-sizing: border-box;
        }
        
        .inputSubmit {
            background-color: #0087FF;
            border: none;
            padding: 15px;
            width: 100%;
            border-radius: 10px;
            color: white;
            font-size: 15px;
            cursor: pointer;
        }
        
        .inputSubmit:hover {
            background-color: deepskyblue;
        }
        
        a {
            text-decoration: none;
            color: white;
            border: 3px solid dodgerblue;
            border-radius: 10px;
            padding: 10px;
            display: block;
            text-align: center;
            margin-top: 10px;
        }
        
        a:hover {
            background-color: dodgerblue;
        }
        
        h1 {
            text-align: center;
        }
        
        .tipo-usuario {
            margin: 15px 0;
        }
        
        .tipo-usuario label {
            margin-right: 15px;
            cursor: pointer;
        }
        
        .tipo-usuario input {
            width: auto;
            margin-right: 5px;
            cursor: pointer;
        }
        
        .mensagem {
            color: yellow;
            display: block;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="tela-login">
        <h1>Login</h1>
        
        <?php if(!empty($mensagem)): ?>
            <p class="mensagem"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="tipo-usuario">
                <label>
                    <input type="radio" name="tipo_usuario" value="cliente" checked> Cliente
                </label>
                <label>
                    <input type="radio" name="tipo_usuario" value="funcionario"> Funcionário
                </label>
            </div>
            
            <input type="text" name="usuario" placeholder="Usuário">
            <br><br>
            <input type="password" name="senha" placeholder="Senha">
            <br><br>
            <input class="inputSubmit" type="submit" name="submit" value="Entrar">
        </form>
        <a href="criar.php">Criar conta</a>
        <a href="recuperar.php">Esqueci minha senha</a>
    </div>
</body>
</html>
