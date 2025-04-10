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

if (isset($_POST['produto_id'])) {
    $produto_id = (int)$_POST['produto_id'];
    $quantidade = isset($_POST['quantidade']) ? (int)$_POST['quantidade'] : 1;
    
    if ($quantidade < 1) $quantidade = 1;
    
    // Verificar se o produto existe e tem estoque suficiente
    $check_produto = "SELECT * FROM produtos WHERE id = $produto_id AND quantidade >= $quantidade";
    $produto_result = $mysqli->query($check_produto);
    
    if ($produto_result && $produto_result->num_rows > 0) {
        $produto_data = $produto_result->fetch_assoc();
        
        // Verificar se o produto já está no carrinho
        $check_sql = "SELECT * FROM carrinho WHERE cliente_id = $cliente_id AND produto_id = $produto_id";
        $check_result = $mysqli->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Atualizar quantidade
            $update_sql = "UPDATE carrinho SET quantidade = quantidade + $quantidade 
                          WHERE cliente_id = $cliente_id AND produto_id = $produto_id";
            if ($mysqli->query($update_sql)) {
                // Diminuir a quantidade no estoque
                $update_estoque = "UPDATE produtos SET quantidade = quantidade - $quantidade 
                                  WHERE id = $produto_id";
                $mysqli->query($update_estoque);
                
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
                // Diminuir a quantidade no estoque
                $update_estoque = "UPDATE produtos SET quantidade = quantidade - $quantidade 
                                  WHERE id = $produto_id";
                $mysqli->query($update_estoque);
                
                $mensagem = "Produto adicionado ao carrinho!";
                $tipo_mensagem = "sucesso";
            } else {
                $mensagem = "Erro ao adicionar ao carrinho: " . $mysqli->error;
                $tipo_mensagem = "erro";
            }
        }
    } else {
        $mensagem = "Produto não disponível ou estoque insuficiente!";
        $tipo_mensagem = "erro";
    }
    
    // Redirecionar para o carrinho com mensagem
    header("Location: carrinho.php?msg=" . urlencode($mensagem) . "&tipo=" . urlencode($tipo_mensagem));
    exit;
} else {
    // Se não houver produto_id, redirecionar para a página de produtos
    header("Location: painel_cliente.php");
    exit;
}
?>
