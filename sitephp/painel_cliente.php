<?php
include('protect.php');
include('conexao.php');

// Verificar se o usuário está logado como cliente
if (!isset($_SESSION['id']) || $_SESSION['tipo'] != 'cliente') {
    header("Location: index.php");
    exit;
}

// Buscar produtos do banco de dados
$sql_produtos = "SELECT * FROM produtos WHERE quantidade > 0 ORDER BY id DESC"; // Mostrar apenas produtos em estoque
$result_produtos = $mysqli->query($sql_produtos);
$tem_produtos = ($result_produtos && $result_produtos->num_rows > 0);

// Verificar se há mensagens
$mensagem = "";
$tipo_mensagem = "";

if(isset($_GET['compra']) && $_GET['compra'] == 'sucesso') {
    $mensagem = "Compra realizada com sucesso!";
    $tipo_mensagem = "sucesso";
}

if(isset($_GET['msg']) && isset($_GET['tipo'])) {
    $mensagem = $_GET['msg'];
    $tipo_mensagem = $_GET['tipo'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Loja Eletrônicos</title>
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      background-color: #043972;
      margin: 0;
      padding: 0;
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
    .botao-comprar {
      background-color: rgb(34, 158, 34);
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      width: 100%;
      margin-top: 10px;
      transition: background-color 0.3s;
    }
    .botao-comprar:hover {
      background-color: rgb(10, 112, 10);
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
    .mensagem.sucesso {
      background-color: rgba(40, 167, 69, 0.3);
      color: #98ff98;
    }
    .mensagem.erro {
      background-color: rgba(220, 53, 69, 0.3);
      color: #ffcccb;
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
      color: #f39c12;
      margin-top: 5px;
    }
    .quantidade-input {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }
    .quantidade-input label {
      margin-right: 10px;
      font-size: 14px;
    }
    .quantidade-input input {
      width: 60px;
      padding: 5px;
      border-radius: 5px;
      border: 1px solid #ccc;
      text-align: center;
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
    .sem-imagem {
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
    .carrinho-badge {
      background-color: #dc3545;
      color: white;
      border-radius: 50%;
      padding: 2px 6px;
      font-size: 12px;
      position: relative;
      top: -8px;
      left: -5px;
    }
  </style>
</head>
<body>
  <header>
    <div class="header-left">
      <img src="img/tecverse.png" alt="Logo da loja">
    </div>
    <div class="header-right">
      <form action="carrinho.php" method="get">
        <button type="submit">
          Meu Carrinho
          <?php
            // Contar itens no carrinho
            $cliente_id = $_SESSION['id'];
            $count_sql = "SELECT COUNT(*) as total FROM carrinho WHERE cliente_id = $cliente_id";
            $count_result = $mysqli->query($count_sql);
            if ($count_result && $count_result->num_rows > 0) {
              $count = $count_result->fetch_assoc()['total'];
              if ($count > 0) {
                echo "<span class='carrinho-badge'>$count</span>";
              }
            }
          ?>
        </button>
      </form>
      <form action="meus_pedidos.php" method="get">
        <button type="submit">Meus Pedidos</button>
      </form>
      <form action="atualizarPerfil.php" method="get">
        <button type="submit">Atualizar Perfil</button>
      </form>
      <form action="deletar.php" method="get">
        <button type="submit">Deletar Conta</button>
      </form>
      <form action="logout.php" method="get">
        <button type="submit">Sair</button>
      </form>
    </div>
  </header>
  
  <div class="tela-painel">
    <?php if(!empty($mensagem)): ?>
      <div class="mensagem <?php echo $tipo_mensagem; ?>">
        <?php echo $mensagem; ?>
      </div>
    <?php endif; ?>
    
    <h2>Produtos Disponíveis</h2>
    
    <div class="filtro">
      <input type="text" id="busca" placeholder="Buscar produto...">
      <select id="ordenar">
        <option value="">Ordenar por</option>
        <option value="preco_menor">Menor Preço</option>
        <option value="preco_maior">Maior Preço</option>
        <option value="nome">Nome (A-Z)</option>
      </select>
      <button onclick="filtrarProdutos()">Filtrar</button>
    </div>
    
    <?php if($tem_produtos): ?>
      <div class="produtos">
        <?php while($produto = $result_produtos->fetch_assoc()): ?>
          <div class="produto">
            <?php if(!empty($produto['imagem'])): ?>
              <img src="<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome_produto']); ?>" class="produto-imagem">
            <?php else: ?>
              <div class="sem-imagem">
                <span>Sem imagem disponível</span>
              </div>
            <?php endif; ?>
            
            <h4><?php echo htmlspecialchars($produto['nome_produto']); ?></h4>
            <p>
              <?php echo htmlspecialchars(substr($produto['descricao'] ?? 'Sem descrição disponível.', 0, 100)); ?>
              <?php if(strlen($produto['descricao'] ?? '') > 100): ?>...<?php endif; ?>
            </p>
            <p class="preco">R$ <?php echo number_format((float)$produto['valor_venda'], 2, ',', '.'); ?></p>
            <p class="estoque">Disponível: <?php echo $produto['quantidade']; ?> unidades</p>
            
            <form action="adicionar_carrinho.php" method="post">
              <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
              <div class="quantidade-input">
                <label for="quantidade-<?php echo $produto['id']; ?>">Quantidade:</label>
                <input type="number" 
                       id="quantidade-<?php echo $produto['id']; ?>" 
                       name="quantidade" 
                       value="1" 
                       min="1" 
                       max="<?php echo $produto['quantidade']; ?>">
              </div>
              <button type="submit" class="botao-comprar">Adicionar ao Carrinho</button>
            </form>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="sem-produtos">
        <h2>Nenhum produto disponível no momento</h2>
        <p>Volte mais tarde para conferir nossos produtos.</p>
      </div>
    <?php endif; ?>
  </div>
  
  <script>
    function filtrarProdutos() {
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
          }
        });
        
        const container = document.querySelector('.produtos');
        produtosArray.forEach(produto => {
          container.appendChild(produto);
        });
      }
    }
    
    // Validar quantidade ao enviar o formulário
    document.querySelectorAll('form').forEach(form => {
      form.addEventListener('submit', function(e) {
        const quantidadeInput = this.querySelector('input[name="quantidade"]');
        if (quantidadeInput) {
          const max = parseInt(quantidadeInput.getAttribute('max'));
          const valor = parseInt(quantidadeInput.value);
          
          if (valor > max) {
            e.preventDefault();
            alert(`Quantidade máxima disponível: ${max} unidades`);
            quantidadeInput.value = max;
          } else if (valor < 1) {
            e.preventDefault();
            alert('A quantidade mínima é 1');
            quantidadeInput.value = 1;
          }
        }
      });
    });
  </script>
</body>
</html>
