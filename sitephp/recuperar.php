<?php
/**
 * Sistema de Recuperação de Senha
 * 
 * Este arquivo implementa a funcionalidade de recuperação de senha para o sistema de loja eletrônica.
 * Permite que tanto clientes quanto funcionários redefinam suas senhas caso as tenham esquecido.
 * 
 * @author Seu Nome
 * @version 1.0
 */

// Inclui o arquivo de conexão com o banco de dados
include('conexao.php');

// Inicializa variáveis para mensagens de feedback
$mensagem = "";
$mensagem_tipo = "";

// Processa o formulário quando enviado
if(isset($_POST['usuario'])) {
    
    // Verifica se o campo de usuário foi preenchido
    if(strlen($_POST['usuario']) == 0) {
        $mensagem = "Preencha seu usuário";
        $mensagem_tipo = "erro";
    } else {
        
        // Sanitiza a entrada do usuário para prevenir injeção SQL
        $usuario = $mysqli->real_escape_string($_POST['usuario']);
        $tipo_usuario = isset($_POST['tipo_usuario']) ? $_POST['tipo_usuario'] : 'cliente';
        
        // Seleciona a tabela correta com base no tipo de usuário
        if($tipo_usuario == 'funcionario') {
            $sql_code = "SELECT * FROM funcionario WHERE usuario = '$usuario'";
        } else {
            $sql_code = "SELECT * FROM clientes WHERE usuario = '$usuario'";
        }
        
        // Executa a consulta SQL
        $sql_query = $mysqli->query($sql_code);
        
        if(!$sql_query) {
            // Erro na consulta SQL
            $mensagem = "Erro na consulta: " . $mysqli->error;
            $mensagem_tipo = "erro";
        } else {
            $quantidade = $sql_query->num_rows;
            
            if($quantidade == 1) {
                // Usuário encontrado, verifica se está tentando redefinir a senha
                if(isset($_POST['nova_senha']) && isset($_POST['confirmar_senha'])) {
                    
                    // Valida a nova senha
                    if($_POST['nova_senha'] != $_POST['confirmar_senha']) {
                        $mensagem = "As senhas não coincidem";
                        $mensagem_tipo = "erro";
                    } else if(strlen($_POST['nova_senha']) < 6) {
                        $mensagem = "A senha deve ter pelo menos 6 caracteres";
                        $mensagem_tipo = "erro";
                    } else {
                        // Sanitiza a nova senha
                        $nova_senha = $mysqli->real_escape_string($_POST['nova_senha']);
                        
                        // Atualiza a senha na tabela apropriada
                        if($tipo_usuario == 'funcionario') {
                            $update_sql = "UPDATE funcionario SET senha = '$nova_senha' WHERE usuario = '$usuario'";
                        } else {
                            $update_sql = "UPDATE clientes SET senha = '$nova_senha' WHERE usuario = '$usuario'";
                        }
                        
                        // Executa a atualização
                        if($mysqli->query($update_sql)) {
                            $mensagem = "Senha atualizada com sucesso!";
                            $mensagem_tipo = "sucesso";
                        } else {
                            $mensagem = "Erro ao atualizar senha: " . $mysqli->error;
                            $mensagem_tipo = "erro";
                        }
                    }
                } else {
                    // Primeira etapa concluída, solicita a nova senha
                    $mensagem = "Usuário encontrado. Por favor, defina sua nova senha.";
                    $mensagem_tipo = "sucesso";
                }
            } else {
                // Usuário não encontrado
                $mensagem = "Usuário não encontrado";
                $mensagem_tipo = "erro";
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
    <title>Recuperar Senha</title>
    <style>
        /* Estilos gerais da página */
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #043972;
        }
        
        /* Container principal */
        .tela-recuperacao {
            background-color: rgb(8, 76, 145);
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
        
        /* Campos de entrada */
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
        
        /* Botão de envio */
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
        
        /* Links */
        a {
            text-decoration: none;
            color: white;
            border: 3px solid dodgerblue;
            border-radius: 10px;
            padding: 10px;
            display: block;
            text-align: center;
            margin-top: 20px;
        }
        
        a:hover {
            background-color: dodgerblue;
        }
        
        /* Título */
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        
        /* Seleção de tipo de usuário */
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
        
        /* Estilos para mensagens de feedback */
        .mensagem {
            padding: 15px;
            border-radius: 5px;
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
    </style>
</head>
<body>
    <div class="tela-recuperacao">
        <h1>Recuperar Senha</h1>
        
        <?php if(!empty($mensagem)): ?>
            <!-- Exibe mensagens de feedback -->
            <div class="mensagem <?php echo $mensagem_tipo; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <!-- Seleção de tipo de usuário -->
            <div class="tipo-usuario">
                <label>
                    <input type="radio" name="tipo_usuario" value="cliente" <?php echo (!isset($_POST['tipo_usuario']) || $_POST['tipo_usuario'] == 'cliente') ? 'checked' : ''; ?>> Cliente
                </label>
                <label>
                    <input type="radio" name="tipo_usuario" value="funcionario" <?php echo (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] == 'funcionario') ? 'checked' : ''; ?>> Funcionário
                </label>
            </div>
            
            <!-- Campo de usuário -->
            <input type="text" name="usuario" placeholder="Usuário" value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>">
            
            <?php if(isset($_POST['usuario']) && !empty($mensagem) && $mensagem_tipo == 'sucesso'): ?>
                <!-- Campos para nova senha (exibidos apenas após verificação do usuário) -->
                <input type="password" name="nova_senha" placeholder="Nova senha">
                <input type="password" name="confirmar_senha" placeholder="Confirmar nova senha">
            <?php endif; ?>
            
            <!-- Botão de envio (texto muda conforme a etapa) -->
            <input class="inputSubmit" type="submit" value="<?php echo (isset($_POST['usuario']) && !empty($mensagem) && $mensagem_tipo == 'sucesso') ? 'Atualizar Senha' : 'Verificar Usuário'; ?>">
        </form>
        
        <!-- Link para retornar à página de login -->
        <a href="index.php">Voltar para Login</a>
    </div>
</body>
</html>
