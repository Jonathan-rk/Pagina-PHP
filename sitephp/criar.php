<?php
include('conexao.php');
$mensagem = "";
$tipo_mensagem = ""; // Para controlar o estilo da mensagem (sucesso/erro)
$conta_criada = false; // Flag para controlar a exibição do formulário

// Inicializar variáveis para armazenar os valores do formulário
$form_usuario = '';
$form_email = '';
$form_telefone = '';
$form_tipo_usuario = 'cliente';

if(isset($_POST['usuario']) && isset($_POST['senha'])) {
    // Capturar os valores do formulário para preservá-los em caso de erro
    $form_usuario = $_POST['usuario'];
    $form_email = isset($_POST['email']) ? $_POST['email'] : '';
    $form_telefone = isset($_POST['telefone']) ? $_POST['telefone'] : '';
    $form_tipo_usuario = isset($_POST['tipo_usuario']) ? $_POST['tipo_usuario'] : 'cliente';
    
    if(strlen($_POST['usuario']) == 0) {
        $mensagem = "Usuário não informado";
        $tipo_mensagem = "erro";
    } else if(strlen($_POST['senha']) == 0) {
        $mensagem = "Preencha sua senha";
        $tipo_mensagem = "erro";
    } else {
        $usuario = $mysqli->real_escape_string($_POST['usuario']);
        $nova_senha = $mysqli->real_escape_string($_POST['senha']);
        $tipo_usuario = isset($_POST['tipo_usuario']) ? $_POST['tipo_usuario'] : 'cliente';
        
        // Verificar a tabela apropriada com base no tipo de usuário
        if($tipo_usuario == 'funcionario') {
            $tabela = 'funcionario';
        } else {
            $tabela = 'clientes';
        }
        
        // Primeiro verificamos se o usuário existe em qualquer tabela
        $sql_check_funcionario = "SELECT * FROM funcionario WHERE usuario = '$usuario'";
        $check_query_funcionario = $mysqli->query($sql_check_funcionario);
        
        $sql_check_cliente = "SELECT * FROM clientes WHERE usuario = '$usuario'";
        $check_query_cliente = $mysqli->query($sql_check_cliente);
        
        $usuario_existe = false;
        
        if (($check_query_funcionario && $check_query_funcionario->num_rows > 0) || 
            ($check_query_cliente && $check_query_cliente->num_rows > 0)) {
            $usuario_existe = true;
        }
        
        if ($usuario_existe) {
            $mensagem = "Este nome de usuário já está em uso. Escolha outro.";
            $tipo_mensagem = "erro";
        } else {
            // Verificar se as tabelas existem, caso contrário, criá-las
            if($tipo_usuario == 'funcionario') {
                $criar_tabela = "CREATE TABLE IF NOT EXISTS funcionario (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario VARCHAR(255) NOT NULL UNIQUE,
                    senha VARCHAR(255) NOT NULL
                )";
            } else {
                $criar_tabela = "CREATE TABLE IF NOT EXISTS clientes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario VARCHAR(55) NOT NULL UNIQUE,
                    senha VARCHAR(40) NOT NULL,
                    email VARCHAR(255),
                    telefone VARCHAR(10),
                    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
            }
            
            $mysqli->query($criar_tabela);
            
            // Usuário não existe, vamos criar
            if($tipo_usuario == 'funcionario') {
                $sql_insert = "INSERT INTO $tabela (usuario, senha) VALUES ('$usuario', '$nova_senha')";
            } else {
                // Para clientes, incluir campos adicionais
                $email = isset($_POST['email']) ? $mysqli->real_escape_string($_POST['email']) : '';
                $telefone = isset($_POST['telefone']) ? $mysqli->real_escape_string($_POST['telefone']) : '';
                
                $sql_insert = "INSERT INTO $tabela (usuario, senha, email, telefone) 
                              VALUES ('$usuario', '$nova_senha', '$email', '$telefone')";
            }
            
            if($mysqli->query($sql_insert)) {
                $mensagem = "Conta criada com sucesso!";
                $tipo_mensagem = "sucesso";
                $conta_criada = true; // Ativar a flag para ocultar o formulário
                
                // Limpar os valores do formulário após sucesso
                $form_usuario = '';
                $form_email = '';
                $form_telefone = '';
            } else {
                $mensagem = "Erro ao criar conta: " . $mysqli->error;
                $tipo_mensagem = "erro";
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
    <title>Criar Conta</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #043972;
        }
        
        .tela-login {
            background-color: rgb(5, 45, 85);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 80px;
            border-radius: 15px;
            color: white;
            width: 80%;
            max-width: 500px;
        }
        
        input {
            padding: 15px;
            border: none;
            outline: none;
            font-size: 15px;
            width: 100%;
            border-radius: 10px;
            box-sizing: border-box;
            margin-bottom: 15px;
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
            margin-top: 15px;
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
            margin-bottom: 0;
        }
        
        .campos-cliente {
            display: none;
        }
        
        .mensagem {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 18px;
        }
        
        .mensagem.sucesso {
            background-color: rgba(40, 167, 69, 0.3);
            color: #98ff98;
        }
        
        .mensagem.erro {
            background-color: rgba(220, 53, 69, 0.3);
            color: #ffcccb;
        }
        
        .botao-voltar {
            margin-top: 30px;
        }
    </style>
    <script>
        // Função para mostrar/ocultar campos adicionais do cliente
        function toggleCamposCliente() {
            var tipoUsuario = document.querySelector('input[name="tipo_usuario"]:checked').value;
            var camposCliente = document.getElementById('campos-cliente');
            
            if (tipoUsuario === 'cliente') {
                camposCliente.style.display = 'block';
            } else {
                camposCliente.style.display = 'none';
            }
        }

        window.onload = function() {
    // Código existente para toggleCamposCliente
    var radioButtons = document.querySelectorAll('input[name="tipo_usuario"]');
    for (var i = 0; i < radioButtons.length; i++) {
        radioButtons[i].addEventListener('change', toggleCamposCliente);
    }
    
    // Verificar estado inicial
    toggleCamposCliente();
    
    // Adicionar limitador de caracteres para o campo de telefone
    var telefoneInput = document.querySelector('input[name="telefone"]');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function() {
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    }
};
        
        // Inicializar quando a página carregar
        window.onload = function() {
            // Adicionar evento de mudança aos radio buttons
            var radioButtons = document.querySelectorAll('input[name="tipo_usuario"]');
            for (var i = 0; i < radioButtons.length; i++) {
                radioButtons[i].addEventListener('change', toggleCamposCliente);
            }
            
            // Verificar estado inicial
            toggleCamposCliente();
        };
    </script>
</head>
<body>
    <div class="tela-login">
        <h1>Criar Conta</h1>
        
        <?php if(!empty($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <?php if($conta_criada): ?>
            <!-- Exibir apenas o botão de voltar para o login quando a conta for criada -->
            <a href="index.php" class="botao-voltar">Voltar para Login</a>
        <?php else: ?>
            <!-- Exibir o formulário de criação de conta quando a conta não for criada -->
            <form action="" method="POST">
                <div class="tipo-usuario">
                    <label>
                        <input type="radio" name="tipo_usuario" value="cliente" <?php echo ($form_tipo_usuario == 'cliente') ? 'checked' : ''; ?>> Cliente
                    </label>
                    <label>
                        <input type="radio" name="tipo_usuario" value="funcionario" <?php echo ($form_tipo_usuario == 'funcionario') ? 'checked' : ''; ?>> Funcionário
                    </label>
                </div>
                
                <input type="text" name="usuario" placeholder="Usuário" value="<?php echo htmlspecialchars($form_usuario); ?>" required>
                <input type="password" name="senha" placeholder="Senha" required>
                
                <div id="campos-cliente" class="campos-cliente" <?php echo ($form_tipo_usuario == 'cliente') ? 'style="display:block;"' : ''; ?>>
                    <input type="email" name="email" placeholder="E-mail" value="<?php echo htmlspecialchars($form_email); ?>">
                    <input type="tel" name="telefone" placeholder="Telefone" value="<?php echo htmlspecialchars($form_telefone); ?>" maxlength="10">
                </div>
                
                <input class="inputSubmit" type="submit" name="submit" value="Criar Conta">
            </form>
            
            <a href="index.php">Voltar para Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
