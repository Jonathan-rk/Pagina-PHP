-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 31/03/2025 às 00:24
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sistema`
--
CREATE DATABASE IF NOT EXISTS `sistema` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sistema`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `carrinho`
--

DROP TABLE IF EXISTS `carrinho`;
CREATE TABLE `carrinho` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `data_adicionado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id` int(255) NOT NULL,
  `usuario` varchar(55) NOT NULL,
  `senha` varchar(40) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` int(10) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `usuario`, `senha`, `email`, `telefone`, `data_cadastro`) VALUES
(2, 'joão', '54321', 'joao@gmail.com', 479999999, '2025-03-29 23:22:33');

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionario`
--

DROP TABLE IF EXISTS `funcionario`;
CREATE TABLE `funcionario` (
  `id` int(50) NOT NULL,
  `usuario` varchar(40) NOT NULL,
  `senha` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `funcionario`
--

INSERT INTO `funcionario` (`id`, `usuario`, `senha`) VALUES
(6, 'joão', '54321');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `data_pedido` datetime NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Confirmado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido_itens`
--

DROP TABLE IF EXISTS `pedido_itens`;
CREATE TABLE `pedido_itens` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

DROP TABLE IF EXISTS `produtos`;
CREATE TABLE `produtos` (
  `id` int(50) NOT NULL,
  `nome_produto` varchar(40) DEFAULT NULL,
  `valor_venda` decimal(15,2) DEFAULT NULL,
  `valor_compra` decimal(15,2) DEFAULT NULL,
  `descricao` varchar(200) DEFAULT NULL,
  `quantidade` int(100) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome_produto`, `valor_venda`, `valor_compra`, `descricao`, `quantidade`, `imagem`) VALUES
(6, 'Mause', 140.00, 60.00, 'Mouse Gamer Rgb Lehmox Hyper Gt 3200 Dpi Gt-m10 ', 120, 'uploads/produto_1743371415_3124.jpeg'),
(7, 'Monitor', 1800.00, 400.00, 'Monitor game hq, 27 Polegadas, 165 hz', 200, 'uploads/produto_1743371598_6156.jpeg'),
(8, 'Teclado Mecânico', 189.90, 70.00, 'Teclado Mecânico Gamer, Gaming TG600, Preto, 60% e ABNT2, RGB', 60, 'uploads/produto_1743371797_6588.jpeg'),
(9, 'Smart TV LG 65', 3989.05, 1500.00, '4K QNED Processador a5 Ger7 AI Local Dimming Design Super Slim Alexa/Chromecast integrado webOS 24', 50, 'uploads/produto_1743371935_1581.jpeg'),
(10, 'Notebook Positivo', 2000.00, 800.00, 'Intel Celeron 4Gb Ram 128Gb Tela De 15,6\" W11 - Bivolt', 100, 'uploads/produto_1743372098_7328.jpeg'),
(11, 'Notebook Gamer Acer Nitro V', 6599.00, 3050.90, 'I7 13aGen Linux Gutta 16GB 512GB Ssd RTX4050 15.6\' Fhd', 50, 'uploads/produto_1743372286_5315.jpeg'),
(12, 'Celular Samsung Galaxy A15', 999.00, 200.00, '128GB, 4GB RAM, Tela Infinita Super AMOLED de 6.5\" ', 100, 'uploads/produto_1743372398_9241.jpeg'),
(13, 'Celular Samsung Galaxy A55', 2300.90, 1200.00, '5G, Câmera Tripla até 50MP, Tela 6.6\", 128GB', 30, 'uploads/produto_1743372532_4728.jpeg'),
(14, 'Carregador ', 60.00, 20.00, 'USB 1A + Cabo USB-C 1,2m 2,4A i2GO Preto', 60, 'uploads/produto_1743372641_4954.jpeg'),
(15, 'Placa de vídeo RTX 2060', 2500.00, 1000.00, '12GB GDDR5 GIGABYTE', 20, 'uploads/produto_1743372787_3755.jpeg');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `carrinho`
--
ALTER TABLE `carrinho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `funcionario`
--
ALTER TABLE `funcionario`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Índices de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `carrinho`
--
ALTER TABLE `carrinho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `funcionario`
--
ALTER TABLE `funcionario`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `carrinho`
--
ALTER TABLE `carrinho`
  ADD CONSTRAINT `carrinho_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carrinho_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Restrições para tabelas `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD CONSTRAINT `pedido_itens_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `pedido_itens_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
