<?php
include('protect.php');
include('conexao.php');

// Verificar se o usuário está logado como funcionário
if (!isset($_SESSION['id']) || $_SESSION['tipo'] != 'funcionario') {
    header("Location: index.php");
    exit;
}

// Buscar produtos do banco de dados
$sql_produtos = "SELECT * FROM produtos ORDER BY id DESC";
$result_produtos = $mysqli->query($sql_produtos);
$tem_produtos = ($result_produtos && $result_produtos->num_rows > 0);

// Verificar se há mensagens
$mensagem = "";
$tipo_mensagem = "";

if(isset($_GET['mensagem'])) {
    $mensagem = $_GET['mensagem'];
    $tipo_mensagem = isset($_GET['tipo']) ? $_GET['tipo'] : 'sucesso';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Painel de Funcionário - Loja Eletrônicos</title>
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      background-color: #043972;
      margin: 0;
      padding: 0;
      color: white;
    }
    header {
      height: 60px;
      background-color: rgb(8, 76, 145);
      border-bottom: 2px solid white;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 20px;
    }
    .header-left {
      float: right; 
      margin-right: 20px; 
      height: auto; 
      width: auto; 
      display: block;
      border-radius: 8px; 
    }
    .header-right {
      display: flex;
      gap: 10px;
    }
    .header-right form {
      margin: 0;
    }
    .header-right button {
      background-color: #043972;
      border: none;
      outline: none;
      padding: 8px 12px;
      border-radius: 7px;
      color: white;
      font-size: 14px;
      cursor: pointer;
    }
    .header-right button:hover {
      background-color: #0087FF;
    }
    .tela-painel {
      padding: 20px;
      color: white;
      text-align: center;
    }
    .produtos {
      margin-top: 30px;
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
    }
    .produto {
      background-color: rgb(8, 76, 145);
      padding: 15px;
      border-radius: 10px;
      width: 300px;
      box-sizing: border-box;
      text-align: left;
      position: relative;
      transition: transform 0.3s ease;
    }
    .produto:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    .produto h4 {
      margin-top: 0;
      margin-bottom: 10px;
      color: #fff;
      font-size: 18px;
    }
    .produto p {
      margin: 5px 0;
      font-size: 14px;
      line-height: 1.5;
    }
    .produto .info {
      font-weight: bold;
      color: #f8f8f8;
    }
    .produto .preco {
      font-size: 20px;
      color: rgb(41, 192, 41);
      margin: 10px 0;
      font-weight: bold;
    }
    .sem-produtos {
      background-color: rgb(8, 76, 145);
      color: white;
      padding: 30px;
      text-align: center;
      border-radius: 15px;
      margin: 50px auto;
      max-width: 600px;
    }
    .botao-acao {
      background-color: #0087FF;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      margin-right: 5px;
      text-decoration: none;
      display: inline-block;
      font-size: 14px;
    }
    .botao-acao.editar:hover {
      background-color: #0069d9;
    }
    .botao-acao.excluir {
      background-color: #dc3545;
    }
    .botao-acao.excluir:hover {
      background-color: #c82333;
    }
    .acoes {
      margin-top: 15px;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 8px;
    }
    .acoes-grupo {
      display: flex;
      gap: 5px;
    }
    .mensagem {
      background-color: rgba(46, 204, 113, 0.2);
      color: white;
      padding: 15px;
      border-radius: 10px;
      margin: 20px auto;
      max-width: 800px;
      text-align: center;
      font-weight: bold;
    }
    .mensagem.erro {
      background-color: rgba(231, 76, 60, 0.2);
    }
    .filtro {
      margin: 20px auto;
      max-width: 800px;
      display: flex;
      justify-content: center;
      gap: 15px;
    }
    .filtro select, .filtro input {
      padding: 10px;
      border-radius: 5px;
      border: none;
    }
    .filtro button {
      background-color: #0087FF;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
    }
    .estoque {
      font-size: 12px;
      color:rgb(255, 255, 255);
      margin-top: 5px;
    }
    .botao-adicionar {
      background-color: #28a745;
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      margin: 20px auto;
      display: inline-block;
      text-decoration: none;
      font-size: 16px;
    }
    .botao-adicionar:hover {
      background-color: #218838;
    }
    .produto-imagem {
  width: 100%;
  aspect-ratio: 1/1; /* Mantém o container quadrado */
  border-radius: 5px;
  margin-bottom: 10px;
  background-color: #ccc;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #333;
  font-weight: bold;
  overflow: hidden;
  position: relative;
}

.produto-imagem img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  border-radius: 5px;
}

    .produto-lucro {
      font-size: 14px;
      color:rgb(255, 255, 255);
      margin: 5px 0;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <header>
    <div class="header-left">
      <img src="img/tecverse.png" alt="Logo da loja">
    </div>
    <div class="header-right">
      <form action="atualizarPerfil.php" method="post">
        <button type="submit">Atualizar perfil</button>
      </form>
      <form action="deletar.php" method="post">
        <button type="submit">Deletar conta</button>
      </form>
      <form action="logout.php" method="post">
        <button type="submit">Sair</button>
      </form>
    </div>
  </header>
  
  <div class="tela-painel">
    <?php if(!empty($mensagem)): ?>
      <div class="mensagem <?php echo $tipo_mensagem == 'erro' ? 'erro' : ''; ?>">
        <?php echo $mensagem; ?>
      </div>
    <?php endif; ?>
    
    <h2>Gerenciar Produtos</h2>
    
    <a href="adicionar_produto.php" class="botao-adicionar">+ Adicionar Novo Produto</a>
    
    <div class="filtro">
      <input type="text" id="busca" placeholder="Buscar produto...">
      <select id="ordenar">
        <option value="">Ordenar por</option>
        <option value="preco_menor">Menor Preço</option>
        <option value="preco_maior">Maior Preço</option>
        <option value="nome">Nome (A-Z)</option>
        <option value="estoque">Estoque (Menor)</option>
      </select>
      <button onclick="filtrarProdutos()">Filtrar</button>
    </div>
    
    <?php if($tem_produtos): ?>
      <div class="produtos">
        <?php while($produto = $result_produtos->fetch_assoc()): 
          // Calcular lucro e margem como na página produtos.php
          $valor_compra = (float)$produto['valor_compra'];
          $valor_venda = (float)$produto['valor_venda'];
          $lucro_unitario = $valor_venda - $valor_compra;
          $margem_lucro = ($valor_compra > 0) ? ($lucro_unitario / $valor_compra) * 100 : 0;
          $lucro_total = $lucro_unitario * $produto['quantidade'];
        ?>
          <div class="produto">
            <div class="produto-imagem">
              <?php if(!empty($produto['imagem'])): ?>
                <img src="<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome_produto']); ?>">
              <?php else: ?>
                <?php echo substr(htmlspecialchars($produto['nome_produto']), 0, 1); ?>
              <?php endif; ?>
            </div>
            
            <h4><?php echo htmlspecialchars($produto['nome_produto']); ?></h4>
            <p>
              <?php echo htmlspecialchars(substr($produto['descricao'] ?? 'Sem descrição disponível.', 0, 100)); ?>
              <?php if(strlen($produto['descricao'] ?? '') > 100): ?>...<?php endif; ?>
            </p>
            <p class="preco">R$ <?php echo number_format($valor_venda, 2, ',', '.'); ?></p>
            <p class="info">Custo: R$ <?php echo number_format($valor_compra, 2, ',', '.'); ?></p>
            <p class="produto-lucro">
              <strong>Lucro por unidade:</strong> 
              R$ <?php echo number_format($lucro_unitario, 2, ',', '.'); ?>
              (<?php echo number_format($margem_lucro, 1, ',', '.'); ?>%)
            </p>
            
            <p class="estoque">Estoque: <?php echo $produto['quantidade']; ?> unidades</p>
            
            <p class="produto-lucro">
              <strong>Lucro total (estoque):</strong> 
              R$ <?php echo number_format($lucro_total, 2, ',', '.'); ?>
            </p>
            
            <div class="acoes">
              <div class="acoes-grupo">
                <a href="editar_produto.php?id=<?php echo $produto['id']; ?>" class="botao-acao editar">Editar</a>
                <a href="excluir_produto.php?id=<?php echo $produto['id']; ?>" class="botao-acao excluir" onclick="return confirm('Tem certeza que deseja excluir este produto?')">Excluir</a>
              </div>
              <?php if(empty($produto['imagem'])): ?>
                <a href="upload_imagem.php?id=<?php echo $produto['id']; ?>" class="botao-acao">Adicionar Imagem</a>
              <?php else: ?>
                <a href="upload_imagem.php?id=<?php echo $produto['id']; ?>" class="botao-acao">Alterar Imagem</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="sem-produtos">
        <h2>Nenhum produto cadastrado</h2>
        <p>Comece adicionando produtos à loja.</p>
      </div>
    <?php endif; ?>
  </div>
  
  <script>
    function filtrarProdutos() {
      // Implementar a funcionalidade de filtro com JavaScript
      const busca = document.getElementById('busca').value.toLowerCase();
      const ordenar = document.getElementById('ordenar').value;
      const produtos = document.querySelectorAll('.produto');
      
      produtos.forEach(produto => {
        const nome = produto.querySelector('h4').textContent.toLowerCase();
        if (nome.includes(busca)) {
          produto.style.display = 'block';
        } else {
          produto.style.display = 'none';
        }
      });
      
      // Implementar ordenação
      if (ordenar) {
        const produtosArray = Array.from(produtos);
        produtosArray.sort((a, b) => {
          if (ordenar === 'nome') {
            return a.querySelector('h4').textContent.localeCompare(b.querySelector('h4').textContent);
          } else if (ordenar === 'preco_menor') {
            const precoA = parseFloat(a.querySelector('.preco').textContent.replace('R$ ', '').replace('.', '').replace(',', '.'));
            const precoB = parseFloat(b.querySelector('.preco').textContent.replace('R$ ', '').replace('.', '').replace(',', '.'));
            return precoA - precoB;
          } else if (ordenar === 'preco_maior') {
            const precoA = parseFloat(a.querySelector('.preco').textContent.replace('R$ ', '').replace('.', '').replace(',', '.'));
            const precoB = parseFloat(b.querySelector('.preco').textContent.replace('R$ ', '').replace('.', '').replace(',', '.'));
            return precoB - precoA;
          } else if (ordenar === 'estoque') {
            const estoqueA = parseInt(a.querySelector('.estoque').textContent.replace('Estoque: ', '').replace(' unidades', ''));
            const estoqueB = parseInt(b.querySelector('.estoque').textContent.replace('Estoque: ', '').replace(' unidades', ''));
            return estoqueA - estoqueB;
          }
        });
        
        const container = document.querySelector('.produtos');
        produtosArray.forEach(produto => {
          container.appendChild(produto);
        });
      }
    }
  </script>
</body>
</html>
