<?php
include('protect.php');
include('conexao.php');

// Verificar se o usuário está logado como funcionário
if (!isset($_SESSION['id']) || $_SESSION['tipo'] != 'funcionario') {
    header("Location: index.php");
    exit;
}

$mensagem = "";
$tipo_mensagem = "";
$produto_selecionado = null;

// Verificar se foi passado um ID de produto na URL
if(isset($_GET['id'])) {
    $produto_id = (int)$_GET['id'];
    
    // Buscar informações do produto selecionado
    $sql_produto = "SELECT id, nome_produto, imagem FROM produtos WHERE id = $produto_id";
    $result_produto = $mysqli->query($sql_produto);
    
    if($result_produto && $result_produto->num_rows > 0) {
        $produto_selecionado = $result_produto->fetch_assoc();
    }
}

// Verificar se há mensagens na URL
if(isset($_GET['mensagem'])) {
    $mensagem = $_GET['mensagem'];
    $tipo_mensagem = isset($_GET['tipo']) ? $_GET['tipo'] : 'sucesso';
}

// Buscar produtos para o formulário
$sql_produtos = "SELECT id, nome_produto FROM produtos ORDER BY nome_produto";
$result_produtos = $mysqli->query($sql_produtos);

// Função para redimensionar imagem
function redimensionarImagem($origem, $destino, $largura_desejada, $altura_desejada) {
    // Obter informações da imagem original
    list($largura_original, $altura_original, $tipo) = getimagesize($origem);
    
    // Criar nova imagem com as dimensões desejadas
    $imagem_destino = imagecreatetruecolor($largura_desejada, $altura_desejada);
    
    // Definir fundo branco
    $branco = imagecolorallocate($imagem_destino, 255, 255, 255);
    imagefill($imagem_destino, 0, 0, $branco);
    
    // Habilitar transparência para PNG
    if ($tipo == IMAGETYPE_PNG) {
        imagealphablending($imagem_destino, false);
        imagesavealpha($imagem_destino, true);
        $transparent = imagecolorallocatealpha($imagem_destino, 255, 255, 255, 127);
        imagefilledrectangle($imagem_destino, 0, 0, $largura_desejada, $altura_desejada, $transparent);
    }
    
    // Criar imagem de origem baseada no tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagem_origem = imagecreatefromjpeg($origem);
            break;
        case IMAGETYPE_PNG:
            $imagem_origem = imagecreatefrompng($origem);
            break;
        case IMAGETYPE_GIF:
            $imagem_origem = imagecreatefromgif($origem);
            break;
        default:
            return false;
    }
    
    // Calcular proporções para manter a relação de aspecto
    $proporcao_original = $largura_original / $altura_original;
    $proporcao_destino = $largura_desejada / $altura_desejada;
    
    if ($proporcao_original > $proporcao_destino) {
        // Imagem original mais larga que a proporção de destino
        $nova_largura = $largura_desejada;
        $nova_altura = $largura_desejada / $proporcao_original;
        $offset_x = 0;
        $offset_y = ($altura_desejada - $nova_altura) / 2;
    } else {
        // Imagem original mais alta que a proporção de destino
        $nova_altura = $altura_desejada;
        $nova_largura = $altura_desejada * $proporcao_original;
        $offset_x = ($largura_desejada - $nova_largura) / 2;
        $offset_y = 0;
    }
    
    // Redimensionar e recortar a imagem
    imagecopyresampled(
        $imagem_destino, $imagem_origem,
        $offset_x, $offset_y, 0, 0,
        $nova_largura, $nova_altura,
        $largura_original, $altura_original
    );
    
    // Salvar a imagem redimensionada
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            imagejpeg($imagem_destino, $destino, 90); // 90 é a qualidade
            break;
        case IMAGETYPE_PNG:
            imagepng($imagem_destino, $destino, 9); // 9 é o nível de compressão (0-9)
            break;
        case IMAGETYPE_GIF:
            imagegif($imagem_destino, $destino);
            break;
    }
    
    // Liberar memória
    imagedestroy($imagem_origem);
    imagedestroy($imagem_destino);
    
    return true;
}

// Processar o upload da imagem
if(isset($_POST['upload']) && isset($_FILES['imagem'])) {
    $produto_id = (int)$_POST['produto_id'];
    
    // Verificar se o upload foi bem-sucedido
    if($_FILES['imagem']['error'] == 0) {
        // Primeiro, verificar se o produto já tem uma imagem para excluí-la
        $sql_check_imagem = "SELECT imagem FROM produtos WHERE id = $produto_id";
        $result_check = $mysqli->query($sql_check_imagem);
        
        if($result_check && $result_check->num_rows > 0) {
            $produto_atual = $result_check->fetch_assoc();
            $imagem_antiga = $produto_atual['imagem'];
            
            // Se existe uma imagem antiga, excluí-la do sistema de arquivos
            if(!empty($imagem_antiga) && file_exists($imagem_antiga)) {
                unlink($imagem_antiga); // Remove o arquivo físico
            }
        }
        
        // Diretório para salvar as imagens
        $diretorio = "uploads/";
        
        // Criar o diretório se não existir
        if(!is_dir($diretorio)) {
            mkdir($diretorio, 0755, true);
        }
        
        // Obter a extensão do arquivo
        $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        
        // Gerar um nome único para o arquivo
        $nome_arquivo = 'produto_' . $produto_id . '_' . time() . '.' . $extensao;
        
        // Caminho completo do arquivo
        $caminho_arquivo = $diretorio . $nome_arquivo;
        
        // Caminho temporário do arquivo enviado
        $arquivo_temp = $_FILES['imagem']['tmp_name'];
        
        // Definir dimensões padrão
        $largura_padrao = 300;
        $altura_padrao = 300;
        
        // Redimensionar e salvar a imagem
        if(redimensionarImagem($arquivo_temp, $caminho_arquivo, $largura_padrao, $altura_padrao)) {
            // Atualizar o caminho da imagem no banco de dados
            $sql = "UPDATE produtos SET imagem = '$caminho_arquivo' WHERE id = $produto_id";
            
            if($mysqli->query($sql)) {
                // Redirecionar para a mesma página com mensagem de sucesso
                header("Location: upload_imagem.php?id=$produto_id&mensagem=Imagem atualizada com sucesso&tipo=sucesso");
                exit;
            } else {
                $mensagem = "Erro ao atualizar o banco de dados: " . $mysqli->error;
                $tipo_mensagem = "erro";
            }
        } else {
            $mensagem = "Erro ao redimensionar a imagem.";
            $tipo_mensagem = "erro";
        }
    } else {
        $mensagem = "Erro no upload do arquivo: " . $_FILES['imagem']['error'];
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
    <title>Upload de Imagem - Loja Eletrônicos</title>
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
        
        select, input[type="file"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            background-color: white;
            color: #333;
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
        
        .produto-atual {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .produto-atual h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .imagem-atual {
            max-width: 300px;
            max-height: 300px;
            border-radius: 5px;
            margin-top: 10px;
            display: block;
        }
        
        .preview-container {
            margin-top: 10px;
            text-align: center;
        }
        
        #image-preview {
            max-width: 300px;
            max-height: 300px;
            border-radius: 5px;
            display: none;
            margin: 0 auto;
        }
        
        .info-padrao {
            background-color: rgba(0, 135, 255, 0.2);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
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
            <h1>Upload de Imagem</h1>
            <div class="nav-links">
                <a href="painel.php">Voltar ao Painel</a>
            </div>
        </header>
        
        <?php if(!empty($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="info-padrao">
            <p><strong>Informação:</strong> Todas as imagens serão redimensionadas automaticamente para o tamanho padrão de 300px x 300px.</p>
        </div>
        
        <?php if($produto_selecionado): ?>
            <div class="produto-atual">
                <h3>Produto Selecionado: <?php echo htmlspecialchars($produto_selecionado['nome_produto']); ?></h3>
                
                <?php if(!empty($produto_selecionado['imagem'])): ?>
                    <p>Imagem atual:</p>
                    <img src="<?php echo htmlspecialchars($produto_selecionado['imagem']); ?>" alt="Imagem atual" class="imagem-atual">
                <?php else: ?>
                    <p>Este produto não possui imagem.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <h2><?php echo $produto_selecionado ? 'Alterar' : 'Adicionar'; ?> Imagem ao Produto</h2>
            
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="produto_id">Selecione o Produto</label>
                    <select name="produto_id" id="produto_id" required>
                        <option value="">Selecione um produto</option>
                        <?php if($result_produtos && $result_produtos->num_rows > 0): ?>
                            <?php while($produto = $result_produtos->fetch_assoc()): ?>
                                <option value="<?php echo $produto['id']; ?>" <?php echo ($produto_selecionado && $produto_selecionado['id'] == $produto['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($produto['nome_produto']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="imagem">Selecione a Imagem</label>
                    <input type="file" name="imagem" id="imagem" accept="image/*" required onchange="previewImage(this)">
                    <small style="display: block; margin-top: 5px; color: #ccc;">
                        Formatos aceitos: JPG, JPEG, PNG, GIF. A imagem será redimensionada para 300px x 300px.
                    </small>
                    <div class="preview-container" style="display: none;">
                        <p>Preview da nova imagem:</p>
                        <img id="image-preview" src="#" alt="Preview da imagem">
                        <p style="color: #ccc; font-size: 12px;">Nota: Esta é apenas uma prévia. A imagem final será redimensionada para 300px x 300px.</p>
                    </div>
                </div>
                
                <button type="submit" name="upload" class="btn">Enviar Imagem</button>
            </form>
        </div>
    </div>
</body>
</html>