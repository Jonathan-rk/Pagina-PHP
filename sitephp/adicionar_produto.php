<?php
include('protect.php');
include('conexao.php');

// Verificar se o usuário está logado como funcionário
if (!isset($_SESSION['id']) || $_SESSION['tipo'] != 'funcionario') {
    header("Location: index.php");
    exit;
}

// Verificar se a coluna imagem existe na tabela produtos
$check_column = $mysqli->query("SHOW COLUMNS FROM produtos LIKE 'imagem'");
if ($check_column->num_rows == 0) {
    $mysqli->query("ALTER TABLE produtos ADD COLUMN imagem VARCHAR(255)");
}

$mensagem = "";
$tipo_mensagem = "";

if(isset($_POST['submit'])) {
    $nome_produto = $mysqli->real_escape_string($_POST['nome_produto']);
    $descricao = $mysqli->real_escape_string($_POST['descricao']);
    $valor_compra = floatval(str_replace(',', '.', $_POST['valor_compra']));
    $valor_venda = floatval(str_replace(',', '.', $_POST['valor_venda']));
    $quantidade = intval($_POST['quantidade']);
    
    // Processar o upload da imagem
    $caminho_imagem = "";
    if(isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        // Diretório para salvar as imagens
        $diretorio = "uploads/";
        
        // Criar o diretório se não existir
        if(!is_dir($diretorio)) {
            mkdir($diretorio, 0755, true);
        }
        
        // Obter a extensão do arquivo
        $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        
        // Gerar um nome único para o arquivo
        $nome_arquivo = 'produto_' . time() . '_' . rand(1000, 9999) . '.' . $extensao;
        
        // Caminho completo do arquivo
        $caminho_imagem = $diretorio . $nome_arquivo;
        
        // Mover o arquivo para o diretório de uploads
        if(!move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_imagem)) {
            $mensagem = "Erro ao fazer upload da imagem.";
            $tipo_mensagem = "erro";
            $caminho_imagem = ""; // Resetar o caminho se falhar
        }
    }
    
    // Inserir produto no banco de dados
    $sql = "INSERT INTO produtos (nome_produto, descricao, valor_compra, valor_venda, quantidade, imagem) 
            VALUES ('$nome_produto', '$descricao', $valor_compra, $valor_venda, $quantidade, '$caminho_imagem')";
    
    if($mysqli->query($sql)) {
        $mensagem = "Produto adicionado com sucesso!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro ao adicionar produto: " . $mysqli->error;
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
    <title>Adicionar Produto - Loja Eletrônicos</title>
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
        
        .form-container {
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
        input[type="number"], 
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            background-color: white;
            color: #333;
            box-sizing: border-box;
        }
        
        textarea {
            height: 100px;
            resize: vertical;
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
        
        .preview-container {
            margin-top: 10px;
            text-align: center;
        }
        
        #image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 5px;
            display: none;
            margin: 0 auto;
        }
    </style>
    <script>
        // Função para mostrar preview da imagem
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const previewContainer = document.querySelector('.preview-container');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    previewContainer.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
                previewContainer.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Adicionar Produto</h1>
            <div class="nav-links">
                <a href="painel.php">Voltar ao Painel</a>
            </div>
        </header>
        
        <?php if(!empty($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <h2>Novo Produto</h2>
            
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nome_produto">Nome do Produto</label>
                    <input type="text" name="nome_produto" id="nome_produto" required>
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea name="descricao" id="descricao"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="valor_compra">Valor de Compra (R$)</label>
                    <input type="text" name="valor_compra" id="valor_compra" required>
                </div>
                
                <div class="form-group">
                    <label for="valor_venda">Valor de Venda (R$)</label>
                    <input type="text" name="valor_venda" id="valor_venda" required>
                </div>
                
                <div class="form-group">
                    <label for="quantidade">Quantidade em Estoque</label>
                    <input type="number" name="quantidade" id="quantidade" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="imagem">Imagem do Produto</label>
                    <input type="file" name="imagem" id="imagem" accept="image/*" onchange="previewImage(this)">
                    <small style="display: block; margin-top: 5px; color: #ccc;">
                        Formatos aceitos: JPG, JPEG, PNG, GIF. Tamanho máximo: 2MB.
                    </small>
                    <div class="preview-container" style="display: none;">
                        <p>Preview:</p>
                        <img id="image-preview" src="#" alt="Preview da imagem">
                    </div>
                </div>
                
                <button type="submit" name="submit" class="btn">Adicionar Produto</button>
            </form>
        </div>
    </div>
</body>
</html>
