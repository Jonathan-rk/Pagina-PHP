<?php
include('protect.php');
include('conexao.php');

$mensagem = "";
$produto = null;

// Verificar se o ID foi fornecido
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $mysqli->real_escape_string($_GET['id']);
    
    // Buscar o produto pelo ID
    $sql_produto = "SELECT * FROM produtos WHERE id = '$id'";
    $result_produto = $mysqli->query($sql_produto);
    
    if($result_produto && $result_produto->num_rows > 0) {
        $produto = $result_produto->fetch_assoc();
    } else {
        $mensagem = "Produto não encontrado!";
    }
} else {
    $mensagem = "ID do produto não fornecido!";
}

// Processar o formulário de edição
if(isset($_POST['nome_produto']) && isset($_POST['valor_venda']) && isset($_POST['valor_compra']) && isset($_POST['quantidade'])) {
    // Validar e sanitizar os dados de entrada
    $id = $mysqli->real_escape_string($_POST['id']);
    $nome_produto = $mysqli->real_escape_string($_POST['nome_produto']);
    $valor_venda = $mysqli->real_escape_string($_POST['valor_venda']);
    $valor_compra = $mysqli->real_escape_string($_POST['valor_compra']);
    $quantidade = $mysqli->real_escape_string($_POST['quantidade']);
    $descricao = $mysqli->real_escape_string($_POST['descricao']); // Capturar a descrição
    
    // Verificar se os campos obrigatórios estão preenchidos
    if(empty($nome_produto) || empty($valor_venda) || empty($valor_compra) || empty($quantidade)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        // Converter os valores para formato numérico adequado para o banco de dados
        $valor_venda = str_replace(',', '.', $valor_venda);
        $valor_compra = str_replace(',', '.', $valor_compra);
        
        // Atualizar o produto no banco de dados
        $sql_code = "UPDATE produtos SET 
                     nome_produto = '$nome_produto', 
                     valor_venda = '$valor_venda', 
                     valor_compra = '$valor_compra',
                     quantidade = '$quantidade',
                     descricao = '$descricao'
                     WHERE id = '$id'";
        
        if($mysqli->query($sql_code)) {
            $mensagem = "Produto atualizado com sucesso!";
            
            // Atualizar os dados do produto na variável
            $produto['nome_produto'] = $nome_produto;
            $produto['valor_venda'] = $valor_venda;
            $produto['valor_compra'] = $valor_compra;
            $produto['quantidade'] = $quantidade;
            $produto['descricao'] = $descricao;
            
            // Redirecionar após 1.5 segundos
            echo "<script>setTimeout(function(){ window.location.href = 'painel.php?produto_atualizado=1'; }, 1500);</script>";
        } else {
            $mensagem = "Erro ao atualizar produto: " . $mysqli->error;
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
    <title>Editar Produto</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: rgb(5, 45, 85);
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .form-container {
            background-color: rgb(8, 76, 145);
            padding: 40px;
            border-radius: 20px;
            color: whitesmoke;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #fff;
            font-size: 28px;
        }

        label {
            display: block;
            margin: 15px 0 8px;
            font-size: 16px;
        }

        input, textarea {
            width: 100%;
            padding: 16px;
            border: none;
            outline: none;
            font-size: 16px;
            border-radius: 12px;
            box-sizing: border-box;
            margin-bottom: 20px;
            background-color: #f8f8f8;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button {
            background-color: dodgerblue;
            border: none;
            outline: none;
            padding: 16px;
            width: 100%;
            border-radius: 12px;
            color: white;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: deepskyblue;
        }

        a {
            text-decoration: none;
            text-align: center;
            color: white;
            display: block;
            margin-top: 20px;
            margin-bottom: -10px;
        }
        
        .mensagem {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .erro {
            background-color: rgba(220, 53, 69, 0.2);
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Editar Produto</h2>
        
        <?php if(!empty($mensagem)): ?>
            <div class="mensagem <?php echo strpos($mensagem, 'Erro') !== false ? 'erro' : ''; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <?php if($produto): ?>
            <form action="" method="post">
                <input type="hidden" name="id" value="<?php echo $produto['id']; ?>">
                
                <label for="nome_produto">Nome do Produto</label>
                <input type="text" name="nome_produto" id="nome_produto" value="<?php echo htmlspecialchars($produto['nome_produto']); ?>" required>
                
                <label for="descricao">Descrição do Produto</label>
                <textarea name="descricao" id="descricao"><?php echo htmlspecialchars($produto['descricao'] ?? ''); ?></textarea>
                
                <label for="valor_venda">Preço de Venda (R$)</label>
                <input type="number" name="valor_venda" id="valor_venda" step="0.01" min="0" value="<?php echo $produto['valor_venda']; ?>" required>
                
                <label for="valor_compra">Preço de Compra (R$)</label>
                <input type="number" name="valor_compra" id="valor_compra" step="0.01" min="0" value="<?php echo $produto['valor_compra']; ?>" required>
                
                <label for="quantidade">Quantidade</label>
                <input type="number" name="quantidade" id="quantidade" min="0" value="<?php echo $produto['quantidade']; ?>" required>
                
                <button type="submit">Atualizar Produto</button>
            </form>
        <?php endif; ?>
        
        <a href="painel.php" style="text-align: center; display: block; margin-top: 20px; color: white;">
            <i class="fas fa-arrow-left"></i> Voltar ao início
        </a>
    </div>
</body>
</html>
