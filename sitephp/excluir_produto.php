<?php
include('protect.php');
include('conexao.php');

// Verificar se o usuário está logado como funcionário
if (!isset($_SESSION['id']) || $_SESSION['tipo'] != 'funcionario') {
    header("Location: index.php");
    exit;
}

// Verificar se foi passado um ID válido
if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $produto_id = (int)$_GET['id'];
    
    // Primeiro, verificar se o produto existe
    $check_sql = "SELECT * FROM produtos WHERE id = $produto_id";
    $check_result = $mysqli->query($check_sql);
    
    if($check_result && $check_result->num_rows > 0) {
        $produto = $check_result->fetch_assoc();
        
        // Se o produto tiver uma imagem, excluí-la do sistema de arquivos
        if(!empty($produto['imagem']) && file_exists($produto['imagem'])) {
            unlink($produto['imagem']);
        }
        
        // Excluir o produto do banco de dados
        $delete_sql = "DELETE FROM produtos WHERE id = $produto_id";
        
        if($mysqli->query($delete_sql)) {
            // Redirecionar com mensagem de sucesso
            header("Location: painel.php?mensagem=" . urlencode("Produto excluído com sucesso!") . "&tipo=sucesso");
            exit;
        } else {
            // Redirecionar com mensagem de erro
            header("Location: painel.php?mensagem=" . urlencode("Erro ao excluir produto: " . $mysqli->error) . "&tipo=erro");
            exit;
        }
    } else {
        // Produto não encontrado
        header("Location: painel.php?mensagem=" . urlencode("Produto não encontrado!") . "&tipo=erro");
        exit;
    }
} else {
    // ID inválido
    header("Location: painel.php?mensagem=" . urlencode("ID de produto inválido!") . "&tipo=erro");
    exit;
}
?>
