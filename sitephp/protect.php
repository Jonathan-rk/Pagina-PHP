<?php
if(!isset($_SESSION)) {
    session_start();
}

if(!isset($_SESSION['id'])) {
    die("Você não pode acessar esta página porque não está logado.<p><a href=\"index.php\">Entrar</a></p>");
}

// Verificar se o usuário está tentando acessar uma página que não corresponde ao seu tipo
$current_page = basename($_SERVER['PHP_SELF']);

// Páginas exclusivas para funcionários
$funcionario_pages = ['painel.php', 'produtos.php', 'adicionar_produto.php', 'editar_produto.php', 'excluir_produto.php'];

// Páginas exclusivas para clientes
$cliente_pages = ['painel_cliente.php', 'carrinho.php', 'meus_pedidos.php'];

// Verificar se o funcionário está tentando acessar páginas de cliente
if($_SESSION['tipo'] == 'funcionario' && in_array($current_page, $cliente_pages)) {
    header("Location: painel.php");
    exit;
}

// Verificar se o cliente está tentando acessar páginas de funcionário
if($_SESSION['tipo'] == 'cliente' && in_array($current_page, $funcionario_pages)) {
    header("Location: painel_cliente.php");
    exit;
}
?>
