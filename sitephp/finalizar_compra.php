<?php
include('protect.php');
include('conexao.php');

// Verificar se o usuário está logado como cliente
if (!isset($_SESSION['id']) || $_SESSION['tipo'] != 'cliente') {
    header("Location: index.php");
    exit;
}

$cliente_id = $_SESSION['id'];
$mensagem = "";
$tipo_mensagem = "";
$compra_finalizada = false;

// Buscar itens do carrinho com JOIN para obter informações do produto
$sql_carrinho = "SELECT c.*, p.nome_produto, p.descricao, p.valor_venda, p.imagem, p.quantidade as estoque_disponivel 
                FROM carrinho c 
                JOIN produtos p ON c.produto_id = p.id 
                WHERE c.cliente_id = $cliente_id";
$result_carrinho = $mysqli->query($sql_carrinho);

$itens_carrinho = [];
$total = 0;
$tem_itens = false;

if ($result_carrinho && $result_carrinho->num_rows > 0) {
    $tem_itens = true;
    while ($item = $result_carrinho->fetch_assoc()) {
        $subtotal = $item['quantidade'] * $item['valor_venda'];
        $total += $subtotal;
        
        $item['subtotal'] = $subtotal;
        $itens_carrinho[] = $item;
    }
}

// Processar a finalização da compra
if (isset($_POST['finalizar_compra']) && $tem_itens) {
    // Verificar estoque antes de finalizar (verificação extra de segurança)
    $estoque_ok = true;
    $produtos_sem_estoque = [];
    
    foreach ($itens_carrinho as $item) {
        // Aqui não precisamos verificar o estoque novamente, pois já foi reservado
        // quando o item foi adicionado ao carrinho, mas mantemos a verificação por segurança
        if ($item['quantidade'] > ($item['estoque_disponivel'] + $item['quantidade'])) {
            $estoque_ok = false;
            $produtos_sem_estoque[] = $item['nome_produto'];
        }
    }
    
    if (!$estoque_ok) {
        $mensagem = "Alguns produtos não têm estoque suficiente: " . implode(", ", $produtos_sem_estoque);
        $tipo_mensagem = "erro";
    } else {
        // Iniciar transação para garantir integridade dos dados
        $mysqli->begin_transaction();
        
        try {
            // 1. Criar registro na tabela de pedidos
            $data_pedido = date('Y-m-d H:i:s');
            $sql_pedido = "INSERT INTO pedidos (cliente_id, data_pedido, valor_total, status) 
                          VALUES ($cliente_id, '$data_pedido', $total, 'Confirmado')";
            
            if (!$mysqli->query($sql_pedido)) {
                throw new Exception("Erro ao criar pedido: " . $mysqli->error);
            }
            
            $pedido_id = $mysqli->insert_id;
            
            // 2. Inserir itens do pedido
            foreach ($itens_carrinho as $item) {
                $produto_id = $item['produto_id'];
                $quantidade = $item['quantidade'];
                $valor_unitario = $item['valor_venda'];
                $subtotal = $item['subtotal'];
                
                $sql_item = "INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, valor_unitario, subtotal) 
                            VALUES ($pedido_id, $produto_id, $quantidade, $valor_unitario, $subtotal)";
                
                if (!$mysqli->query($sql_item)) {
                    throw new Exception("Erro ao inserir item do pedido: " . $mysqli->error);
                }
                
                // Não atualizamos o estoque aqui, pois já foi atualizado quando o item foi adicionado ao carrinho
            }
            
            // 3. Limpar o carrinho do cliente
            $sql_limpar = "DELETE FROM carrinho WHERE cliente_id = $cliente_id";
            
            if (!$mysqli->query($sql_limpar)) {
                throw new Exception("Erro ao limpar carrinho: " . $mysqli->error);
            }
            
            // Confirmar transação
            $mysqli->commit();
            
            $compra_finalizada = true;
            $mensagem = "Compra realizada com sucesso! Seu pedido #$pedido_id foi confirmado.";
            $tipo_mensagem = "sucesso";
            
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            $mysqli->rollback();
            $mensagem = $e->getMessage();
            $tipo_mensagem = "erro";
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
    <title>Finalizar Compra - Loja Eletrônicos</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #043972;
            color: white;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
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
        
        .checkout-container {
            background-color: rgb(8, 76, 145);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .checkout-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 10px;
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
        
        .success-actions {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.btn-success {
    background-color: #28a745;
    font-size: 16px;
    font-weight: bold;
    padding: 12px 25px;
}

.btn-success:hover {
    background-color: #218838;
}

.continue-shopping {
    font-size: 16px;
    font-weight: bold;
    padding: 12px 25px;
}

        
        .checkout-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .checkout-items th, .checkout-items td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .checkout-items th {
            background-color: rgba(0, 0, 0, 0.2);
        }
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            background-color: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            font-weight: bold;
        }
        
        .checkout-total {
            text-align: right;
            font-size: 1.2em;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px;
            font-size: 1.2em;
            color: rgba(255, 255, 255, 0.7);
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
        
        .order-summary {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .order-summary h3 {
            margin-top: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 10px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-total {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .success-message {
            text-align: center;
            padding: 30px;
        }
        
        .success-message h2 {
            color: #98ff98;
        }

    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Finalizar Compra</h1>
            <div class="nav-links">
                <a href="painel_cliente.php">Voltar ao início</a>
            </div>
        </header>
        
        <?php if(!empty($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <?php if($compra_finalizada): ?>
            <div class="checkout-container">
                <div class="success-message">
                    <h2>Compra Realizada com Sucesso!</h2>
                    <p>Obrigado por comprar conosco. Seu pedido foi confirmado e será processado em breve.</p>
                    
                    <div class="success-actions">
                        <a href="meus_pedidos.php" class="btn btn-success">Ver Meus Pedidos</a>
                        <a href="painel_cliente.php" class="btn continue-shopping">Continuar Comprando</a>
                    </div>
                </div>
            </div>
        <?php elseif($tem_itens): ?>
            <div class="checkout-container">
                <div class="checkout-header">
                    <h2>Resumo do Pedido</h2>
                </div>
                
                <div class="order-summary">
                    <h3>Itens do Pedido</h3>
                    
                    <?php foreach($itens_carrinho as $item): ?>
                        <div class="summary-item">
                            <div>
                                <?php echo htmlspecialchars($item['nome_produto']); ?> 
                                (<?php echo $item['quantidade']; ?>x)
                            </div>
                            <div>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="summary-total">
                        <div class="summary-item">
                            <div>Total:</div>
                            <div>R$ <?php echo number_format($total, 2, ',', '.'); ?></div>
                        </div>
                    </div>
                </div>
                
                <table class="checkout-items">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Nome</th>
                            <th>Preço</th>
                            <th>Quantidade</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($itens_carrinho as $item): ?>
                            <tr>
                                <td>
                                    <?php if(!empty($item['imagem']) && file_exists($item['imagem'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['imagem']); ?>" alt="<?php echo htmlspecialchars($item['nome_produto']); ?>" class="product-img">
                                    <?php else: ?>
                                        <div class="product-img">
                                            <?php echo substr(htmlspecialchars($item['nome_produto']), 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['nome_produto']); ?></td>
                                <td>R$ <?php echo number_format($item['valor_venda'], 2, ',', '.'); ?></td>
                                <td><?php echo $item['quantidade']; ?></td>
                                <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="checkout-total">
                    <p><strong>Total: R$ <?php echo number_format($total, 2, ',', '.'); ?></strong></p>
                    
                    <form action="" method="post">
                        <a href="carrinho.php" class="btn">Voltar ao Carrinho</a>
                        <button type="submit" name="finalizar_compra" class="btn btn-success" onclick="return confirm('Confirmar a compra?')">Confirmar Compra</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="checkout-container">
                <div class="empty-cart">
                    <p>Seu carrinho está vazio.</p>
                    <p>Adicione produtos para finalizar uma compra!</p>
                    <a href="painel_cliente.php" class="btn">Ir às Compras</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
