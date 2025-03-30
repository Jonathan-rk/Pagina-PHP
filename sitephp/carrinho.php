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

// Adicionar produto ao carrinho
if (isset($_POST['adicionar_carrinho']) && isset($_POST['produto_id'])) {
    $produto_id = (int)$_POST['produto_id'];
    $quantidade = isset($_POST['quantidade']) ? (int)$_POST['quantidade'] : 1;
    
    if ($quantidade < 1) $quantidade = 1;
    
    // Verificar se há estoque suficiente
    $check_estoque = "SELECT quantidade FROM produtos WHERE id = $produto_id AND quantidade >= $quantidade";
    $result_estoque = $mysqli->query($check_estoque);
    
    if ($result_estoque && $result_estoque->num_rows > 0) {
        // Verificar se o produto já está no carrinho
        $check_sql = "SELECT * FROM carrinho WHERE cliente_id = $cliente_id AND produto_id = $produto_id";
        $check_result = $mysqli->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Atualizar quantidade
            $update_sql = "UPDATE carrinho SET quantidade = quantidade + $quantidade 
                          WHERE cliente_id = $cliente_id AND produto_id = $produto_id";
            if ($mysqli->query($update_sql)) {
                // Atualizar estoque
                $mysqli->query("UPDATE produtos SET quantidade = quantidade - $quantidade WHERE id = $produto_id");
                $mensagem = "Quantidade atualizada no carrinho!";
                $tipo_mensagem = "sucesso";
            } else {
                $mensagem = "Erro ao atualizar carrinho: " . $mysqli->error;
                $tipo_mensagem = "erro";
            }
        } else {
            // Adicionar novo item
            $insert_sql = "INSERT INTO carrinho (cliente_id, produto_id, quantidade) 
                          VALUES ($cliente_id, $produto_id, $quantidade)";
            if ($mysqli->query($insert_sql)) {
                // Atualizar estoque
                $mysqli->query("UPDATE produtos SET quantidade = quantidade - $quantidade WHERE id = $produto_id");
                $mensagem = "Produto adicionado ao carrinho!";
                $tipo_mensagem = "sucesso";
            } else {
                $mensagem = "Erro ao adicionar ao carrinho: " . $mysqli->error;
                $tipo_mensagem = "erro";
            }
        }
    } else {
        $mensagem = "Estoque insuficiente para este produto!";
        $tipo_mensagem = "erro";
    }
}

// Remover item do carrinho
if (isset($_GET['remover'])) {
    $item_id = (int)$_GET['remover'];
    
    // Primeiro, obter a quantidade do item para restaurar ao estoque
    $get_item = "SELECT produto_id, quantidade FROM carrinho WHERE id = $item_id AND cliente_id = $cliente_id";
    $result_item = $mysqli->query($get_item);
    
    if ($result_item && $result_item->num_rows > 0) {
        $item_data = $result_item->fetch_assoc();
        $produto_id = $item_data['produto_id'];
        $quantidade = $item_data['quantidade'];
        
        // Restaurar quantidade ao estoque
        $update_estoque = "UPDATE produtos SET quantidade = quantidade + $quantidade WHERE id = $produto_id";
        $mysqli->query($update_estoque);
        
        // Remover do carrinho
        $delete_sql = "DELETE FROM carrinho WHERE id = $item_id AND cliente_id = $cliente_id";
        if ($mysqli->query($delete_sql)) {
            $mensagem = "Item removido do carrinho!";
            $tipo_mensagem = "sucesso";
        } else {
            $mensagem = "Erro ao remover item: " . $mysqli->error;
            $tipo_mensagem = "erro";
        }
    }
}

// Atualizar quantidade
if (isset($_POST['atualizar_quantidade'])) {
    foreach ($_POST['quantidade'] as $item_id => $nova_quantidade) {
        $item_id = (int)$item_id;
        $nova_quantidade = (int)$nova_quantidade;
        
        // Obter a quantidade atual para calcular a diferença
        $get_atual = "SELECT produto_id, quantidade FROM carrinho WHERE id = $item_id AND cliente_id = $cliente_id";
        $result_atual = $mysqli->query($get_atual);
        
        if ($result_atual && $result_atual->num_rows > 0) {
            $item_atual = $result_atual->fetch_assoc();
            $produto_id = $item_atual['produto_id'];
            $quantidade_atual = $item_atual['quantidade'];
            $diferenca = $nova_quantidade - $quantidade_atual;
            
            if ($nova_quantidade < 1) {
                // Remover item se quantidade for menor que 1
                $mysqli->query("DELETE FROM carrinho WHERE id = $item_id AND cliente_id = $cliente_id");
                
                // Restaurar quantidade ao estoque
                $update_estoque = "UPDATE produtos SET quantidade = quantidade + $quantidade_atual WHERE id = $produto_id";
                $mysqli->query($update_estoque);
            } else {
                // Verificar se há estoque suficiente para aumentar a quantidade
                if ($diferenca > 0) {
                    $check_estoque = "SELECT quantidade FROM produtos WHERE id = $produto_id AND quantidade >= $diferenca";
                    $result_estoque = $mysqli->query($check_estoque);
                    
                    if ($result_estoque && $result_estoque->num_rows > 0) {
                        // Atualizar quantidade no carrinho
                        $mysqli->query("UPDATE carrinho SET quantidade = $nova_quantidade WHERE id = $item_id AND cliente_id = $cliente_id");
                        
                        // Atualizar estoque
                        $mysqli->query("UPDATE produtos SET quantidade = quantidade - $diferenca WHERE id = $produto_id");
                    } else {
                        $mensagem = "Estoque insuficiente para alguns produtos.";
                        $tipo_mensagem = "erro";
                        continue;
                    }
                } else if ($diferenca < 0) {
                    // Diminuindo a quantidade, restaurar ao estoque
                    $diferenca_abs = abs($diferenca);
                    
                    // Atualizar quantidade no carrinho
                    $mysqli->query("UPDATE carrinho SET quantidade = $nova_quantidade WHERE id = $item_id AND cliente_id = $cliente_id");
                    
                    // Restaurar ao estoque
                    $mysqli->query("UPDATE produtos SET quantidade = quantidade + $diferenca_abs WHERE id = $produto_id");
                }
                // Se diferença for 0, não precisa fazer nada
            }
        }
    }
    
    $mensagem = "Carrinho atualizado!";
    $tipo_mensagem = "sucesso";
}

// Limpar carrinho
if (isset($_GET['limpar'])) {
    // Primeiro, restaurar todas as quantidades ao estoque
    $get_items = "SELECT produto_id, quantidade FROM carrinho WHERE cliente_id = $cliente_id";
    $result_items = $mysqli->query($get_items);
    
    if ($result_items && $result_items->num_rows > 0) {
        while ($item = $result_items->fetch_assoc()) {
            $produto_id = $item['produto_id'];
            $quantidade = $item['quantidade'];
            
            // Restaurar ao estoque
            $update_estoque = "UPDATE produtos SET quantidade = quantidade + $quantidade WHERE id = $produto_id";
            $mysqli->query($update_estoque);
        }
    }
    
    // Agora limpar o carrinho
    $clear_sql = "DELETE FROM carrinho WHERE cliente_id = $cliente_id";
    
    if ($mysqli->query($clear_sql)) {
        $mensagem = "Carrinho esvaziado!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro ao esvaziar carrinho: " . $mysqli->error;
        $tipo_mensagem = "erro";
    }
}

// Buscar itens do carrinho
$sql_carrinho = "SELECT c.*, p.nome_produto, p.descricao, p.valor_venda, p.imagem 
                FROM carrinho c 
                JOIN produtos p ON c.produto_id = p.id 
                WHERE c.cliente_id = $cliente_id";
$result_carrinho = $mysqli->query($sql_carrinho);

$itens_carrinho = [];
$total = 0;

if ($result_carrinho && $result_carrinho->num_rows > 0) {
    while ($item = $result_carrinho->fetch_assoc()) {
        $subtotal = $item['quantidade'] * $item['valor_venda'];
        $total += $subtotal;
        
        $item['subtotal'] = $subtotal;
        $itens_carrinho[] = $item;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Carrinho - Loja Eletrônicos</title>
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
        
        .cart-container {
            background-color: rgb(8, 76, 145);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 10px;
        }
        
        .cart-actions {
            display: flex;
            gap: 10px;
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
        
        /* Estilo específico para o botão de perigo */
        .btn.btn-danger {
            background-color: #dc3545 !important;
        }
        
        .btn.btn-danger:hover {
            background-color: #c82333 !important;
        }
        
        .cart-items {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cart-items th, .cart-items td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .cart-items th {
            background-color: rgba(0, 0, 0, 0.2);
        }
        
        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .quantity-input {
            width: 60px;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
            text-align: center;
        }
        
        .cart-total {
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
        
        .checkout-btn {
            background-color: #28a745;
            font-size: 1.1em;
            padding: 12px 25px;
            margin-top: 10px;
        }
        
        .checkout-btn:hover {
            background-color: #218838;
        }
        
        .continue-shopping {
    display: block;
    text-align: center;
    margin: 20px auto;
    width: fit-content;
    font-size: 16px;
    font-weight: bold;
    padding: 12px 25px;
}

    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Meu Carrinho</h1>
            <div class="nav-links">
                <a href="painel_cliente.php">Voltar ao início</a>
            </div>
        </header>
        
        <?php if(!empty($mensagem)): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="cart-container">
            <div class="cart-header">
                <h2>Itens no Carrinho</h2>
                <div class="cart-actions">
                    <?php if(count($itens_carrinho) > 0): ?>
                        <a href="carrinho.php?limpar=1" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja esvaziar o carrinho?')">Esvaziar Carrinho</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if(count($itens_carrinho) > 0): ?>
                <form action="carrinho.php" method="post">
                    <table class="cart-items">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Nome</th>
                                <th>Preço</th>
                                <th>Quantidade</th>
                                <th>Subtotal</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($itens_carrinho as $item): ?>
                                <tr>
                                    <td>
                                        <?php if(!empty($item['imagem']) && file_exists($item['imagem'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['imagem']); ?>" alt="<?php echo htmlspecialchars($item['nome_produto']); ?>" class="product-img">
                                        <?php else: ?>
                                            <div class="product-img" style="background-color: #ccc; display: flex; align-items: center; justify-content: center;">
                                                <span><?php echo substr(htmlspecialchars($item['nome_produto']), 0, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['nome_produto']); ?></td>
                                    <td>R$ <?php echo number_format($item['valor_venda'], 2, ',', '.'); ?></td>
                                    <td>
                                        <input type="number" name="quantidade[<?php echo $item['id']; ?>]" value="<?php echo $item['quantidade']; ?>" min="1" class="quantity-input">
                                    </td>
                                    <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                                    <td>
                                        <a href="carrinho.php?remover=<?php echo $item['id']; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja remover este item?')">Remover</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="cart-total">
                        <p><strong>Total: R$ <?php echo number_format($total, 2, ',', '.'); ?></strong></p>
                        <button type="submit" name="atualizar_quantidade" class="btn">Atualizar Carrinho</button>
                        <a href="finalizar_compra.php" class="btn checkout-btn">Finalizar Compra</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-cart">
                    <p>Seu carrinho está vazio.</p>
                    <p>Adicione produtos para começar suas compras!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <a href="painel_cliente.php" class="btn continue-shopping">Continuar Comprando</a>
    </div>
</body>
</html>