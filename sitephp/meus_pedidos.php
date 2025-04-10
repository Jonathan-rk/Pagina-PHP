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

// Buscar pedidos do cliente
$sql_pedidos = "SELECT * FROM pedidos WHERE cliente_id = $cliente_id ORDER BY data_pedido DESC";
$result_pedidos = $mysqli->query($sql_pedidos);

$pedidos = [];
if ($result_pedidos && $result_pedidos->num_rows > 0) {
    while ($pedido = $result_pedidos->fetch_assoc()) {
        // Buscar itens do pedido
        $pedido_id = $pedido['id'];
        $sql_itens = "SELECT pi.*, p.nome_produto, p.imagem 
                      FROM pedido_itens pi 
                      JOIN produtos p ON pi.produto_id = p.id 
                      WHERE pi.pedido_id = $pedido_id";
        $result_itens = $mysqli->query($sql_itens);
        
        $itens = [];
        if ($result_itens && $result_itens->num_rows > 0) {
            while ($item = $result_itens->fetch_assoc()) {
                $itens[] = $item;
            }
        }
        
        $pedido['itens'] = $itens;
        $pedidos[] = $pedido;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos - Loja Eletrônicos</title>
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
        
        .orders-container {
            background-color: rgb(8, 76, 145);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .order {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .order-items th, .order-items td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .order-items th {
            background-color: rgba(0, 0, 0, 0.2);
        }
        
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            background-color: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            font-weight: bold;
        }
        
        .order-total {
            text-align: right;
            font-weight: bold;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .empty-orders {
            text-align: center;
            padding: 40px;
            font-size: 1.2em;
            color: rgba(255, 255, 255, 0.7);
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
        
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-confirmado {
            background-color: rgba(40, 167, 69, 0.3);
            color: #98ff98;
        }
        
        .status-enviado {
            background-color: rgba(0, 123, 255, 0.3);
            color: #98d8ff;
        }
        
        .status-entregue {
            background-color: rgba(108, 117, 125, 0.3);
            color: #d9d9d9;
        }
        
        .status-cancelado {
            background-color: rgba(220, 53, 69, 0.3);
            color: #ffcccb;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Meus Pedidos</h1>
            <div class="nav-links">
                <a href="painel_cliente.php">Voltar ao início</a>
                <a href="carrinho.php">Meu Carrinho</a>
            </div>
        </header>
        
        <div class="orders-container">
            <h2>Histórico de Pedidos</h2>
            
            <?php if(count($pedidos) > 0): ?>
                <?php foreach($pedidos as $pedido): ?>
                    <div class="order">
                        <div class="order-header">
                            <div>
                                <h3>Pedido #<?php echo $pedido['id']; ?></h3>
                                <p>Data: <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></p>
                            </div>
                            <div>
                                <?php 
                                    $status_class = '';
                                    switch($pedido['status']) {
                                        case 'Confirmado':
                                            $status_class = 'status-confirmado';
                                            break;
                                        case 'Enviado':
                                            $status_class = 'status-enviado';
                                            break;
                                        case 'Entregue':
                                            $status_class = 'status-entregue';
                                            break;
                                        case 'Cancelado':
                                            $status_class = 'status-cancelado';
                                            break;
                                    }
                                ?>
                                <span class="status <?php echo $status_class; ?>">
                                    <?php echo $pedido['status']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <table class="order-items">
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
                                <?php foreach($pedido['itens'] as $item): ?>
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
                                        <td>R$ <?php echo number_format($item['valor_unitario'], 2, ',', '.'); ?></td>
                                        <td><?php echo $item['quantidade']; ?></td>
                                        <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="order-total">
                            Total: R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <p>Você ainda não realizou nenhum pedido.</p>
                    <a href="painel_cliente.php" class="btn">Ir às Compras</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
