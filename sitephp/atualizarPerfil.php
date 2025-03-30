<?php
include('protect.php');
include('conexao.php');

// Verificar se o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$usuario_id = $_SESSION['id'];
$tipo_usuario = $_SESSION['tipo'];
$mensagem = "";
$tipo_mensagem = "";

// Determinar a tabela correta com base no tipo de usuário
$tabela = ($tipo_usuario == 'funcionario') ? 'funcionario' : 'clientes';

// Buscar dados atuais do usuário
$sql = "SELECT * FROM $tabela WHERE id = $usuario_id";
$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {
    $usuario_data = $result->fetch_assoc();
    // Adicione um log para debug
    error_log("Dados do usuário: " . print_r($usuario_data, true));
} else {
    $mensagem = "Erro ao buscar dados do usuário";
    $tipo_mensagem = "erro";
    // Adicione um log para debug
    error_log("Erro na consulta: " . $mysqli->error);
}

// Processar formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $usuario = isset($_POST['usuario']) ? $mysqli->real_escape_string($_POST['usuario']) : '';
    
    // Verificar se o nome de usuário foi fornecido
    // Processar formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $usuario = isset($_POST['usuario']) ? $mysqli->real_escape_string($_POST['usuario']) : '';
    
    // Verificar se o nome de usuário foi fornecido apenas se o formulário foi enviado
    if (empty($usuario)) {
        $mensagem = "O nome de usuário é obrigatório";
        $tipo_mensagem = "erro";
    } else {
        // Verificar se a senha foi fornecida para atualização
        $atualizar_senha = false;
        $nova_senha = '';
        
        if (!empty($_POST['nova_senha'])) {
            $atualizar_senha = true;
            $nova_senha = $mysqli->real_escape_string($_POST['nova_senha']);
            
            // Verificar se a senha atual está correta
            if (empty($_POST['senha_atual'])) {
                $mensagem = "Por favor, informe sua senha atual para confirmar a alteração de senha";
                $tipo_mensagem = "erro";
                $atualizar_senha = false;
            } else {
                $senha_atual = $mysqli->real_escape_string($_POST['senha_atual']);
                
                // Verificar se a senha atual está correta
                if ($senha_atual != $usuario_data['senha']) {
                    $mensagem = "Senha atual incorreta";
                    $tipo_mensagem = "erro";
                    $atualizar_senha = false;
                } else {
                    // Se não for POST, não exibir mensagem de erro
                    $mensagem = "";
                    $tipo_mensagem = "";
                }
            }
        }
    }
}
    // Se não houver erros, atualizar os dados
    if (empty($mensagem)) {
        // Construir a consulta SQL de atualização
        $sql_update = "UPDATE $tabela SET usuario = '$usuario'";
        
        // Adicionar campos específicos para clientes
        if ($tipo_usuario == 'cliente') {
            $email = isset($_POST['email']) ? $mysqli->real_escape_string($_POST['email']) : '';
            $telefone = isset($_POST['telefone']) ? $mysqli->real_escape_string($_POST['telefone']) : '';
            
            // Verificar se os campos existem na tabela antes de incluí-los na consulta
            $check_columns = $mysqli->query("SHOW COLUMNS FROM clientes LIKE 'email'");
            if ($check_columns && $check_columns->num_rows > 0) {
                $sql_update .= ", email = '$email'";
            }
            
            $check_columns = $mysqli->query("SHOW COLUMNS FROM clientes LIKE 'telefone'");
            if ($check_columns && $check_columns->num_rows > 0) {
                $sql_update .= ", telefone = '$telefone'";
            }
        }
        
        // Adicionar atualização de senha se necessário
        if ($atualizar_senha) {
            $sql_update .= ", senha = '$nova_senha'";
        }
        
        // Finalizar a consulta
        $sql_update .= " WHERE id = $usuario_id";
        
        // Executar a atualização
        if ($mysqli->query($sql_update)) {
            // Importante: Atualizar o nome de usuário na sessão se ele foi alterado
            if ($_SESSION['nome'] != $usuario) {
                $_SESSION['nome'] = $usuario;
            }
            
            $mensagem = "Perfil atualizado com sucesso!";
            $tipo_mensagem = "sucesso";
            
            // Atualizar os dados exibidos
            $result = $mysqli->query("SELECT * FROM $tabela WHERE id = $usuario_id");
            if ($result && $result->num_rows > 0) {
                $usuario_data = $result->fetch_assoc();
            }
        } else {
            $mensagem = "Erro ao atualizar perfil: " . $mysqli->error;
            $tipo_mensagem = "erro";
        }
    }
}

// Determinar a página de retorno com base no tipo de usuário
$pagina_retorno = ($tipo_usuario == 'funcionario') ? 'painel.php' : 'painel_cliente.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Perfil - Loja Eletrônicos</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #043972;
            color: white;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: rgb(8, 76, 145);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        h1 {
            margin: 0;
            color: white;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: #0087FF;
        }
        
        .profile-container {
            background-color: rgb(8, 76, 145);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        
        .btn {
            background-color: #0087FF;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: deepskyblue;
        }
        
        .mensagem {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .mensagem.sucesso {
            background-color: rgba(40, 167, 69, 0.3);
            color: #98ff98;
        }
        
        .mensagem.erro {
            background-color: rgba(220, 53, 69, 0.3);
            color: #ffcccb;
        }
        
        .senha-section {
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Atualizar Perfil</h1>
            <div class="nav-links">
                <a href="<?php echo $pagina_retorno; ?>">Voltar ao início</a>
            </div>
        </header>
        
        <?php if(!empty($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-container">
            <h2>Seus Dados</h2>
            
            <form action="atualizarPerfil.php" method="post">
                <div class="form-group">
                <label for="usuario">Nome de Usuário</label>
    <input type="text" id="usuario" name="usuario" 
           value="<?php echo htmlspecialchars($usuario_data['usuario'] ?? ''); ?>" 
           required>
                </div>
                
                <?php if($tipo_usuario == 'cliente'): ?>
                    <?php 
                    // Verificar se a coluna 'email' existe na tabela clientes
                    $check_email = $mysqli->query("SHOW COLUMNS FROM clientes LIKE 'email'");
                    if($check_email && $check_email->num_rows > 0): 
                    ?>
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario_data['email'] ?? ''); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Verificar se a coluna 'telefone' existe na tabela clientes
                    $check_telefone = $mysqli->query("SHOW COLUMNS FROM clientes LIKE 'telefone'");
                    if($check_telefone && $check_telefone->num_rows > 0): 
                    ?>
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario_data['telefone'] ?? ''); ?>">
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="senha-section">
                    <h3>Alterar Senha</h3>
                    <p>Preencha apenas se desejar alterar sua senha atual</p>
                    
                    <div class="form-group">
                        <label for="senha_atual">Senha Atual</label>
                        <input type="password" id="senha_atual" name="senha_atual">
                    </div>
                    
                    <div class="form-group">
                        <label for="nova_senha">Nova Senha</label>
                        <input type="password" id="nova_senha" name="nova_senha">
                    </div>
                </div>
                
                <button type="submit" class="btn">Atualizar Perfil</button>
            </form>
        </div>
    </div>
</body>
</html>
