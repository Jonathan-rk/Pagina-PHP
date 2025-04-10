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

// Buscar dados do cliente para pré-preencher o endereço (se disponível)
$cliente_data = [];
$sql_cliente = "SELECT * FROM clientes WHERE id = $cliente_id";
$result_cliente = $mysqli->query($sql_cliente);
if ($result_cliente && $result_cliente->num_rows > 0) {
    $cliente_data = $result_cliente->fetch_assoc();
}

// Processar a finalização da compra
if (isset($_POST['finalizar_compra']) && $tem_itens) {
    // Verificar estoque antes de finalizar (verificação extra de segurança)
    $estoque_ok = true;
    $produtos_sem_estoque = [];
    
    foreach ($itens_carrinho as $item) {
        if ($item['quantidade'] > ($item['estoque_disponivel'] + $item['quantidade'])) {
            $estoque_ok = false;
            $produtos_sem_estoque[] = $item['nome_produto'];
        }
    }
    
    // Verificar se os campos de endereço foram preenchidos
    $endereco = isset($_POST['endereco']) ? $mysqli->real_escape_string($_POST['endereco']) : '';
    $numero = isset($_POST['numero']) ? $mysqli->real_escape_string($_POST['numero']) : '';
    $complemento = isset($_POST['complemento']) ? $mysqli->real_escape_string($_POST['complemento']) : '';
    $bairro = isset($_POST['bairro']) ? $mysqli->real_escape_string($_POST['bairro']) : '';
    $cidade = isset($_POST['cidade']) ? $mysqli->real_escape_string($_POST['cidade']) : '';
    $estado = isset($_POST['estado']) ? $mysqli->real_escape_string($_POST['estado']) : '';
    $cep = isset($_POST['cep']) ? $mysqli->real_escape_string($_POST['cep']) : '';
    $metodo_pagamento = isset($_POST['metodo_pagamento']) ? $mysqli->real_escape_string($_POST['metodo_pagamento']) : '';
    
    if (empty($endereco) || empty($numero) || empty($bairro) || empty($cidade) || empty($estado) || empty($cep) || empty($metodo_pagamento)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios do endereço de entrega e método de pagamento.";
        $tipo_mensagem = "erro";
    } else if (!$estoque_ok) {
        $mensagem = "Alguns produtos não têm estoque suficiente: " . implode(", ", $produtos_sem_estoque);
        $tipo_mensagem = "erro";
    } else {
        // Iniciar transação para garantir integridade dos dados
        $mysqli->begin_transaction();
        
        try {
            // 1. Criar registro na tabela de pedidos
            $data_pedido = date('Y-m-d H:i:s');
            $sql_pedido = "INSERT INTO pedidos (cliente_id, data_pedido, valor_total, status, metodo_pagamento,
                          endereco, numero, complemento, bairro, cidade, estado, cep) 
                          VALUES ($cliente_id, '$data_pedido', $total, 'Confirmado', '$metodo_pagamento',
                          '$endereco', '$numero', '$complemento', '$bairro', '$cidade', '$estado', '$cep')";
            
            if (!$mysqli->query($sql_pedido)) {
                throw new Exception("Erro ao criar pedido: " . $mysqli->error);
            }
            
            $pedido_id = $mysqli->insert_id;
            
            // Atualizar os dados de endereço do cliente na tabela clientes
            $sql_atualizar_cliente = "UPDATE clientes SET 
                                     endereco = '$endereco',
                                     numero = '$numero',
                                     complemento = '$complemento',
                                     bairro = '$bairro',
                                     cidade = '$cidade',
                                     estado = '$estado',
                                     cep = '$cep'
                                     WHERE id = $cliente_id";

            // Executar a atualização (não lançamos exceção se falhar, apenas registramos o erro)
            if (!$mysqli->query($sql_atualizar_cliente)) {
                error_log("Erro ao atualizar endereço do cliente: " . $mysqli->error);
            }
            
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

// Verificar se a tabela pedidos tem os campos de endereço
// Se não tiver, executar ALTER TABLE para adicionar
$check_columns = $mysqli->query("SHOW COLUMNS FROM pedidos LIKE 'endereco'");
if ($check_columns && $check_columns->num_rows == 0) {
    // Adicionar os campos de endereço à tabela pedidos
    $sql_alter = "ALTER TABLE pedidos 
                 ADD COLUMN endereco VARCHAR(255) AFTER status,
                 ADD COLUMN numero VARCHAR(20) AFTER endereco,
                 ADD COLUMN complemento VARCHAR(100) AFTER numero,
                 ADD COLUMN bairro VARCHAR(100) AFTER complemento,
                 ADD COLUMN cidade VARCHAR(100) AFTER bairro,
                 ADD COLUMN estado VARCHAR(50) AFTER cidade,
                 ADD COLUMN cep VARCHAR(20) AFTER estado";
    $mysqli->query($sql_alter);
}

// Verificar se a tabela pedidos tem a coluna de método de pagamento
$check_metodo_pagamento = $mysqli->query("SHOW COLUMNS FROM pedidos LIKE 'metodo_pagamento'");
if ($check_metodo_pagamento && $check_metodo_pagamento->num_rows == 0) {
    // Adicionar a coluna de método de pagamento à tabela pedidos
    $sql_alter_metodo = "ALTER TABLE pedidos ADD COLUMN metodo_pagamento VARCHAR(50) AFTER status";
    $mysqli->query($sql_alter_metodo);
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

/* Estilos para o formulário de endereço */
.endereco-entrega {
    background-color: rgba(255, 255, 255, 0.1);
    padding: 30px;
    border-radius: 5px;
    margin-bottom: 30px;
}

.endereco-entrega h3 {
    margin-top: 0;
    margin-bottom: 25px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    padding-bottom: 15px;
    font-size: 20px;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    margin-bottom: 25px;
}

.form-group {
    flex: 1;
    min-width: 200px;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: bold;
    font-size: 16px;
}

.form-group input, .form-group select {
    width: 100%;
    padding: 12px;
    border-radius: 5px;
    border: 1px solid #ccc;
    background-color: #f8f8f8;
    color: #333;
    font-size: 15px;
}

.form-control {
    width: 100%;
    padding: 12px;
    border-radius: 5px;
    border: 1px solid #ccc;
    background-color: #f8f8f8;
    color: #333;
    font-size: 15px;
    transition: all 0.3s ease;
    box-sizing: border-box;
    margin-bottom: 10px;
}

.form-control:focus {
    outline: none;
    border-color: #0087FF;
    box-shadow: 0 0 0 2px rgba(0, 135, 255, 0.25);
}

.form-group.small {
    flex: 0 0 120px;
}

.required:after {
    content: " *";
    color: #ff6b6b;
    font-size: 18px;
}

/* Espaçamento adicional entre seções */
.endereco-entrega + table {
    margin-top: 40px;
}

/* Estilos para o método de pagamento */
.metodo-pagamento {
    background-color: rgba(255, 255, 255, 0.1);
    padding: 30px;
    border-radius: 5px;
    margin-bottom: 30px;
}

.metodo-pagamento h3 {
    margin-top: 0;
    margin-bottom: 25px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    padding-bottom: 15px;
    font-size: 20px;
}

.pix-info, .boleto-info {
    background-color: rgba(0, 0, 0, 0.1);
    padding: 15px;
    border-radius: 5px;
    margin-top: 15px;
}

.pix-info p, .boleto-info p {
    margin: 5px 0;
    font-size: 14px;
}

/* Melhorar a responsividade em telas pequenas */
@media (max-width: 768px) {
    .form-group, .form-group.small {
        flex: 0 0 100%;
        min-width: 100%;
    }
    
    .form-row {
        gap: 15px;
    }
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
                
                <form action="" method="post">
                    <!-- Formulário de Endereço de Entrega -->
                    <div class="endereco-entrega">
                        <h3>Endereço de Entrega</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="endereco" class="required">Endereço</label>
                                <input type="text" id="endereco" name="endereco" class="form-control" value="<?php echo htmlspecialchars($cliente_data['endereco'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group small">
                                <label for="numero" class="required">Número</label>
                                <input type="text" id="numero" name="numero" class="form-control" value="<?php echo htmlspecialchars($cliente_data['numero'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="complemento">Complemento</label>
                                <input type="text" id="complemento" name="complemento" class="form-control" value="<?php echo htmlspecialchars($cliente_data['complemento'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="bairro" class="required">Bairro</label>
                                <input type="text" id="bairro" name="bairro" class="form-control" value="<?php echo htmlspecialchars($cliente_data['bairro'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cidade" class="required">Cidade</label>
                                <input type="text" id="cidade" name="cidade" class="form-control" value="<?php echo htmlspecialchars($cliente_data['cidade'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group small">
                                <label for="estado" class="required">Estado</label>
                                <select id="estado" name="estado" class="form-control" required>
                                    <option value="">Selecione</option>
                                    <option value="AC" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'AC') ? 'selected' : ''; ?>>AC</option>
                                    <option value="AL" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'AL') ? 'selected' : ''; ?>>AL</option>
                                    <option value="AP" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'AP') ? 'selected' : ''; ?>>AP</option>
                                    <option value="AM" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'AM') ? 'selected' : ''; ?>>AM</option>
                                    <option value="BA" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'BA') ? 'selected' : ''; ?>>BA</option>
                                    <option value="CE" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'CE') ? 'selected' : ''; ?>>CE</option>
                                    <option value="DF" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'DF') ? 'selected' : ''; ?>>DF</option>
                                    <option value="ES" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'ES') ? 'selected' : ''; ?>>ES</option>
                                    <option value="GO" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'GO') ? 'selected' : ''; ?>>GO</option>
                                    <option value="MA" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'MA') ? 'selected' : ''; ?>>MA</option>
                                    <option value="MT" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'MT') ? 'selected' : ''; ?>>MT</option>
                                    <option value="MS" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'MS') ? 'selected' : ''; ?>>MS</option>
                                    <option value="MG" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'MG') ? 'selected' : ''; ?>>MG</option>
                                    <option value="PA" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'PA') ? 'selected' : ''; ?>>PA</option>
                                    <option value="PB" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'PB') ? 'selected' : ''; ?>>PB</option>
                                    <option value="PR" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'PR') ? 'selected' : ''; ?>>PR</option>
                                    <option value="PE" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'PE') ? 'selected' : ''; ?>>PE</option>
                                    <option value="PI" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'PI') ? 'selected' : ''; ?>>PI</option>
                                    <option value="RJ" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'RJ') ? 'selected' : ''; ?>>RJ</option>
                                    <option value="RN" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'RN') ? 'selected' : ''; ?>>RN</option>
                                    <option value="RS" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'RS') ? 'selected' : ''; ?>>RS</option>
                                    <option value="RO" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'RO') ? 'selected' : ''; ?>>RO</option>
                                    <option value="RR" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'RR') ? 'selected' : ''; ?>>RR</option>
                                    <option value="SC" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'SC') ? 'selected' : ''; ?>>SC</option>
                                    <option value="SP" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'SP') ? 'selected' : ''; ?>>SP</option>
                                    <option value="SE" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'SE') ? 'selected' : ''; ?>>SE</option>
                                    <option value="TO" <?php echo (isset($cliente_data['estado']) && $cliente_data['estado'] == 'TO') ? 'selected' : ''; ?>>TO</option>
                                </select>
                            </div>
                            
                            <div class="form-group small">
                                <label for="cep" class="required">CEP</label>
                                <input type="text" id="cep" name="cep" class="form-control" value="<?php echo htmlspecialchars($cliente_data['cep'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Método de Pagamento -->
                    <div class="metodo-pagamento">
                        <h3>Método de Pagamento</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="metodo_pagamento" class="required">Selecione como deseja pagar</label>
                                <select id="metodo_pagamento" name="metodo_pagamento" class="form-control" required>
                                    <option value="">Selecione um método de pagamento</option>
                                    <option value="cartao_credito">Cartão de Crédito</option>
                                    <option value="cartao_debito">Cartão de Débito</option>
                                    <option value="boleto">Boleto Bancário</option>
                                    <option value="pix">PIX</option>
                                    <option value="dinheiro">Dinheiro na Entrega</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Campos adicionais para cartão (exibidos apenas quando cartão for selecionado) -->
                        <div id="campos-cartao" style="display: none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="numero_cartao" class="required">Número do Cartão</label>
                                    <input type="text" id="numero_cartao" name="numero_cartao" class="form-control" placeholder="0000 0000 0000 0000">
                                </div>
                                
                                <div class="form-group small">
                                    <label for="validade" class="required">Validade</label>
                                    <input type="text" id="validade" name="validade" class="form-control" placeholder="MM/AA">
                                </div>
                                
                                <div class="form-group small">
                                    <label for="cvv" class="required">CVV
                                    </label>
                                    <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123" maxlength="4">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nome_cartao" class="required">Nome no Cartão</label>
                                    <input type="text" id="nome_cartao" name="nome_cartao" class="form-control" placeholder="Nome como está no cartão">
                                </div>
                                
                                <div class="form-group small">
                                    <label for="parcelas" class="required">Parcelas</label>
                                    <select id="parcelas" name="parcelas" class="form-control">
                                        <option value="1">1x sem juros</option>
                                        <option value="2">2x sem juros</option>
                                        <option value="3">3x sem juros</option>
                                        <option value="4">4x com juros</option>
                                        <option value="5">5x com juros</option>
                                        <option value="6">6x com juros</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Instruções para PIX (exibidas apenas quando PIX for selecionado) -->
                        <div id="campos-pix" style="display: none;">
                            <div class="pix-info">
                                <p>Ao finalizar a compra, você receberá um QR Code para pagamento via PIX.</p>
                                <p>O pedido será processado após a confirmação do pagamento.</p>
                            </div>
                        </div>
                        
                        <!-- Instruções para Boleto (exibidas apenas quando Boleto for selecionado) -->
                        <div id="campos-boleto" style="display: none;">
                            <div class="boleto-info">
                                <p>Ao finalizar a compra, você poderá imprimir o boleto bancário.</p>
                                <p>O pedido será processado após a confirmação do pagamento (até 3 dias úteis).</p>
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
                        
                        <a href="carrinho.php" class="btn">Voltar ao Carrinho</a>
                        <button type="submit" name="finalizar_compra" class="btn btn-success" onclick="return confirm('Confirmar a compra?')">Confirmar Compra</button>
                    </div>
                </form>
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
    
    <script>
        // Script para formatar o CEP automaticamente
        document.getElementById('cep').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 8);
            }
            e.target.value = value;
        });
        
        // Script para mostrar/ocultar campos específicos do método de pagamento
        document.getElementById('metodo_pagamento').addEventListener('change', function() {
            const metodoPagamento = this.value;
            
            // Ocultar todos os campos específicos
            document.getElementById('campos-cartao').style.display = 'none';
            document.getElementById('campos-pix').style.display = 'none';
            document.getElementById('campos-boleto').style.display = 'none';
            
            // Mostrar campos específicos com base na seleção
            if (metodoPagamento === 'cartao_credito' || metodoPagamento === 'cartao_debito') {
                document.getElementById('campos-cartao').style.display = 'block';
                
                // Se for débito, ocultar parcelas
                if (metodoPagamento === 'cartao_debito') {
                    document.getElementById('parcelas').value = '1';
                    document.getElementById('parcelas').parentElement.style.display = 'none';
                } else {
                    document.getElementById('parcelas').parentElement.style.display = 'block';
                }
            } else if (metodoPagamento === 'pix') {
                document.getElementById('campos-pix').style.display = 'block';
            } else if (metodoPagamento === 'boleto') {
                document.getElementById('campos-boleto').style.display = 'block';
            }
        });
        
        // Formatação para campos de cartão
        document.getElementById('numero_cartao').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.match(/.{1,4}/g).join(' ');
            }
            e.target.value = value.substring(0, 19); // Limitar a 16 dígitos + 3 espaços
        });
        
        document.getElementById('validade').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
        
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
        });
    </script>
</body>
</html>
