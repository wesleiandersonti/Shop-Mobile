<?php
// Inicia a sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'php_error_reporting.php';
require_once 'database/db_connect.php';
require_once 'include/cart_functions.php';

// Buscar configurações da loja
$config_query = $conn->query("SELECT * FROM configuracoes LIMIT 1");
$config = $config_query->fetch(PDO::FETCH_ASSOC);

// Valores padrão caso não existam configurações
$whatsapp_loja = $config ? $config['whatsapp'] : '5511999999999';
$garantia = $config ? ($config['garantia'] ?? '3 meses') : '3 meses';
$politica_devolucao = $config ? ($config['politica_devolucao'] ?? '') : '';

initialize_cart();
$cart_item_count = get_cart_item_count();
$cart_items = get_cart_items();
$cart_total = get_cart_total();

$produto_id = $_GET['id'] ?? 0;

if (!$produto_id || !is_numeric($produto_id)) {
    header("Location: index.php");
    exit();
}

// Buscar produto
$stmt = $conn->prepare("SELECT p.*, c.nome as categoria_nome FROM produtos p 
                       LEFT JOIN categorias c ON p.categoria_id = c.id 
                       WHERE p.id = ? AND p.estoque > 0");
$stmt->execute([$produto_id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    header("Location: index.php");
    exit();
}

// Processar fotos adicionais
$fotos_adicionais = [];
if ($produto["fotos_adicionais"]) {
    $fotos_adicionais = explode(",", $produto["fotos_adicionais"]);
}

// Lógica para adicionar ao carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_quantity = $_POST['quantity'];
    $product_image = $_POST['product_image'];

    add_to_cart($product_id, $product_name, $product_price, $product_quantity, $product_image);
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($produto["nome"]); ?> - <?php echo isset($config) ? htmlspecialchars($config['nome_loja']) : 'Loja Virtual'; ?></title>
    <link rel="stylesheet" href="css/mobile-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos específicos da página do produto */
        .product-header {
            background: white;
            padding: 1rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: color 0.3s;
        }
        
        .back-button:hover {
            color: #764ba2;
        }
        
        .back-button .icon {
            margin-right: 0.5rem;
        }
        
        .product-gallery {
            margin-bottom: 1.5rem;
        }
        
        .main-image-container {
            width: 100%;
            padding-bottom: 100%;
            position: relative;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .main-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 15px;
        }
        
        .additional-images {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }
        
        .additional-images::-webkit-scrollbar {
            height: 4px;
        }
        
        .additional-images::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }
        
        .additional-images::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 2px;
        }
        
        .thumb-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            flex-shrink: 0;
            border: 2px solid transparent;
        }
        
        .thumb-image:hover,
        .thumb-image.active {
            border-color: #667eea;
            transform: scale(1.05);
        }
        
        .product-details {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        
        .product-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        
        .product-category {
            color: #667eea;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .product-price {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 1.5rem;
        }
        
        .product-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .purchase-section {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            position: sticky;
            bottom: 1rem;
        }
        
        .purchase-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 0.5rem;
            transform: scale(1.2);
        }
        
        .address-fields {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .address-fields.show {
            display: block;
        }
        
        .btn-whatsapp {
            width: 100%;
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }
        
        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
            color: white;
        }
        
        .no-image-large {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #6c757d;
            font-size: 6rem;
            border-radius: 15px;
        }
        
        .btn-favorite {
            background: transparent;
            border: 2px solid #ff4757;
            color: #ff4757;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            justify-content: center;
        }
        
        .btn-favorite:hover {
            background: #ff4757;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 71, 87, 0.3);
        }
        
        .btn-favorite.favorited {
            background: #ff4757;
            color: white;
            border-color: #ff4757;
        }
        
        .btn-favorite.favorited:hover {
            background: #ff3742;
            border-color: #ff3742;
        }
        
        .mobile-purchase-toggle {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            margin: 0;
            width: 100vw;
            max-width: 100vw;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            height: 56px;
            font-size: 1.25rem;
            font-weight: 700;
            border-radius: 0;
            box-shadow: 0 -2px 20px rgba(102,126,234,0.10);
            text-align: center;
            cursor: pointer;
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            padding: 0;
        }
        .mobile-purchase-toggle .toggle-text {
            font-weight: 700;
            font-size: 1.2rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mobile-purchase-toggle .toggle-icon {
            font-size: 1.4rem;
            margin-left: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .mobile-purchase-toggle.expanded .toggle-icon {
            transform: rotate(180deg);
        }
        
        .purchase-section.mobile-collapsed {
            transform: translateY(100%);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .purchase-section.mobile-expanded {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
            transition: all 0.3s ease;
        }

        /* Modal de confirmação */
        .confirmation-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            animation: fadeIn 0.3s ease;
        }

        .confirmation-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            position: relative;
            animation: slideIn 0.3s ease;
        }

        .confirmation-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
            animation: bounce 0.6s ease;
        }

        .confirmation-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }

        .confirmation-message {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .confirmation-countdown {
            font-size: 1.2rem;
            font-weight: bold;
            color: #25d366;
            margin-bottom: 1rem;
        }

        .confirmation-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-whatsapp-now {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-whatsapp-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
            color: white;
        }

        .btn-continue-shopping {
            background: #6c757d;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-continue-shopping:hover {
            background: #5a6268;
            color: white;
        }

        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            font-family: monospace;
            font-size: 0.9rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        /* Modal da Política de Devolução */
        .politica-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            animation: fadeIn 0.3s ease;
        }

        .politica-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            position: relative;
            animation: slideIn 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .politica-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .politica-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .politica-close {
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            color: white;
            transition: opacity 0.3s;
            line-height: 1;
        }

        .politica-close:hover {
            opacity: 0.7;
        }

        .politica-body {
            padding: 1.5rem;
            max-height: 50vh;
            overflow-y: auto;
            line-height: 1.6;
            color: #333;
        }

        .politica-body::-webkit-scrollbar {
            width: 6px;
        }

        .politica-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .politica-body::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 3px;
        }

        .politica-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
            text-align: center;
            background: #f8f9fa;
            border-radius: 0 0 15px 15px;
        }

        .btn-fechar {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-fechar:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }
        
        @media (max-width: 767px) {
            .mobile-purchase-toggle {
                display: flex;
            }
            
            .purchase-section {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 999;
                margin: 0;
                border-radius: 15px 15px 0 0;
                max-height: 80vh;
                overflow-y: auto;
                box-shadow: 0 -10px 30px rgba(0,0,0,0.2);
            }
            
            .purchase-section.mobile-collapsed {
                transform: translateY(calc(100% - 60px));
            }
            
            .purchase-section.mobile-expanded {
                transform: translateY(0);
            }
            
            .product-content {
                padding-bottom: 200px;
            }

            .confirmation-content {
                margin: 20% auto;
                width: 95%;
            }

            .confirmation-buttons {
                flex-direction: column;
            }
        }

        @media (min-width: 768px) {
            .main-container {
                display: grid;
                grid-template-columns: 1fr 400px;
                gap: 2rem;
                align-items: start;
            }
            
            .product-content {
                grid-column: 1;
            }
            
            .purchase-section {
                grid-column: 2;
                position: sticky;
                top: 100px;
            }
            
            .mobile-purchase-toggle {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Container principal -->
    <main class="main-container">
        <div class="product-content">
            <!-- Header da página -->
            <div class="product-header">
                <a href="index.php" class="back-button">
                    <span class="icon"><i class="fas fa-arrow-left"></i></span>
                    Voltar para a loja
                </a>
            </div>



            <!-- Galeria de imagens -->
            <section class="product-gallery">
                <?php if ($produto["foto_principal"]): ?>
                    <div class="main-image-container">
                        <img src="uploads/<?php echo htmlspecialchars($produto["foto_principal"]); ?>" 
                             alt="<?php echo htmlspecialchars($produto["nome"]); ?>" 
                             class="main-image" id="mainImage">
                    </div>
                <?php else: ?>
                    <div class="main-image-container no-image-large"><i class="fas fa-camera"></i></div>
                <?php endif; ?>
                
                <?php if (count($fotos_adicionais) > 0): ?>
                    <div class="additional-images">
                        <?php if ($produto["foto_principal"]): ?>
                            <img src="uploads/<?php echo htmlspecialchars($produto["foto_principal"]); ?>" 
                                 alt="<?php echo htmlspecialchars($produto["nome"]); ?>" 
                                 class="thumb-image active"
                                 onclick="changeMainImage(this.src)">
                        <?php endif; ?>
                        
                        <?php foreach ($fotos_adicionais as $foto): ?>
                            <?php if (trim($foto)): ?>
                                <img src="uploads/<?php echo htmlspecialchars(trim($foto)); ?>" 
                                     alt="<?php echo htmlspecialchars($produto["nome"]); ?>" 
                                     class="thumb-image"
                                     onclick="changeMainImage(this.src)">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Detalhes do produto -->
            <section class="product-details">
                <h1 class="product-title"><?php echo htmlspecialchars($produto["nome"]); ?></h1>
                <?php if ($produto["categoria_nome"]): ?>
                    <div class="product-category"><i class="fas fa-folder"></i> <?php echo htmlspecialchars($produto["categoria_nome"]); ?></div>
                <?php endif; ?>
                <!-- Removido o valor/preço daqui -->
                <?php if ($produto["descricao"]): ?>
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($produto["descricao"])); ?>
                    </div>
                <?php endif; ?>
                <!-- Removido botões de favoritos, adicionar ao carrinho e campo quantidade daqui -->
            </section>
        </div>

        <!-- Seção de compra -->
        <section class="purchase-section" id="purchaseFormSection">
            <div class="product-summary-panel">
                <div class="product-summary-title" style="font-size:1.2rem;font-weight:600;margin-bottom:0.5rem;display:none;"></div>
                <div class="product-summary-price" style="font-size:2rem;font-weight:bold;color:#28a745;margin-bottom:1rem;">
                    R$ <?php echo number_format($produto["preco"], 2, ",", "."); ?>
                </div>
                <?php if (isset($produto["estoque"])): ?>
                    <div class="product-summary-stock" style="color:#888;margin-bottom:1rem;">
                        Estoque disponível: <?php echo (int)$produto["estoque"]; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="product.php?id=<?php echo $produto_id; ?>" style="margin-bottom:1rem;">
                    <input type="hidden" name="product_id" value="<?php echo $produto["id"]; ?>">
                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($produto["nome"]); ?>">
                    <input type="hidden" name="product_price" value="<?php echo $produto["preco"]; ?>">
                    <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($produto["foto_principal"]); ?>">
                    <div class="form-group">
                        <label for="quantity_panel">Quantidade:</label>
                        <input type="number" id="quantity_panel" name="quantity" value="1" min="1" class="form-control" style="width: 80px;">
                    </div>
                    <button type="submit" name="add_to_cart" class="btn-whatsapp" style="background: #007bff; margin-top: 1rem; width:100%;font-size:1.1rem;">
                        <i class="fas fa-shopping-cart"></i> Adicionar ao Carrinho
                    </button>
                </form>
                <button id="favoriteBtnPanel" class="btn-favorite" style="margin-bottom:1rem;width:100%;" onclick="toggleFavorite(<?php echo $produto['id']; ?>)">
                    <i class="fas fa-heart" id="favoriteIconPanel"></i>
                    <span id="favoriteTextPanel">Adicionar aos Favoritos</span>
                </button>
                <div class="product-extra-info" style="margin-top:1.5rem;">
                    <?php if (!empty($config['horario_atendimento'])): ?>
                        <div style="margin-bottom:0.5rem;color:#666;"><i class="fas fa-clock"></i> Horário de atendimento: <?php echo htmlspecialchars($config['horario_atendimento']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($config['cidade_entrega'])): ?>
                        <div style="margin-bottom:0.5rem;color:#666;"><i class="fas fa-map-marker-alt"></i> Entrega para: <?php echo htmlspecialchars($config['cidade_entrega']); ?></div>
                    <?php endif; ?>
                    <div style="margin-bottom:0.5rem;color:#666;"><i class="fas fa-truck"></i> Prazo de envio: No Mesmo Dia</div>
                    <div style="margin-bottom:0.5rem;color:#666;display:flex;align-items:center;gap:0.7rem;">
                        <span><i class="fas fa-money-bill-wave"></i> Formas de pagamento:</span>
                        <?php if (!empty($config['pagamento_pix'])): ?>
                            <span title="Pix" style="color:#27ae60;font-weight:600;"><i class="fas fa-qrcode"></i> Pix</span>
                        <?php endif; ?>
                        <?php if (!empty($config['pagamento_cartao'])): ?>
                            <span title="Cartão" style="color:#2980b9;font-weight:600;"><i class="fas fa-credit-card"></i> Cartão</span>
                        <?php endif; ?>
                        <?php if (!empty($config['pagamento_dinheiro'])): ?>
                            <span title="Dinheiro" style="color:#e67e22;font-weight:600;"><i class="fas fa-coins"></i> Dinheiro</span>
                        <?php endif; ?>
                    </div>
                    <div style="margin-bottom:0.5rem;color:#666;"><i class="fas fa-shield-alt"></i> Garantia: <?php echo htmlspecialchars($garantia); ?></div>
                    <div style="margin-bottom:0.5rem;"><i class="fas fa-undo"></i> <a href="#" onclick="openPoliticaModal()" style="color:#667eea;text-decoration:underline;cursor:pointer;">Política de devolução</a></div>
                    <div style="margin-bottom:0.5rem;"><i class="fab fa-whatsapp"></i> <a href="https://api.whatsapp.com/send?phone=<?php echo $whatsapp_loja; ?>" target="_blank" style="color:#25d366;text-decoration:underline;">Dúvidas? Fale conosco</a></div>
                </div>
            </div>
        </section>
    </main>

    <!-- Botão de toggle para dispositivos móveis -->
    <div class="mobile-purchase-toggle" id="mobilePurchaseToggle" onclick="toggleMobilePurchase()">
        <span class="toggle-text">Mais Detalhes <i class="fas fa-chevron-up toggle-icon" id="toggleIcon"></i></span>
    </div>

    <!-- Modal de confirmação -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="confirmation-content">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="confirmation-title">Pedido Recebido com Sucesso!</h3>
            <p class="confirmation-message">
                Seu pedido foi registrado em nosso sistema. Em instantes você será redirecionado para o WhatsApp para finalizar a compra.
            </p>
            <div class="confirmation-countdown" id="countdownText">
                Redirecionando em <span id="countdown">5</span> segundos...
            </div>
            <div class="confirmation-buttons">
                <a href="#" id="whatsappLink" class="btn-whatsapp-now">
                    <i class="fab fa-whatsapp"></i>
                    Ir para WhatsApp Agora
                </a>
                <a href="index.php" class="btn-continue-shopping">
                    <i class="fas fa-store"></i>
                    Continuar Comprando
                </a>
            </div>
        </div>
    </div>

    <!-- Modal da Política de Devolução -->
    <div id="politicaModal" class="politica-modal">
        <div class="politica-content">
            <div class="politica-header">
                <h3><i class="fas fa-undo"></i> Política de Devolução</h3>
                <span class="politica-close" onclick="closePoliticaModal()">&times;</span>
            </div>
            <div class="politica-body">
                <?php if (!empty($politica_devolucao)): ?>
                    <?php echo nl2br(htmlspecialchars($politica_devolucao)); ?>
                <?php else: ?>
                    <p style="color: #666; font-style: italic;">
                        <i class="fas fa-info-circle"></i> 
                        Política de devolução ainda não configurada. Entre em contato conosco para mais informações.
                    </p>
                <?php endif; ?>
            </div>
            <div class="politica-footer">
                <button onclick="closePoliticaModal()" class="btn-fechar">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Função para alternar o container de compra em dispositivos móveis
        function toggleMobilePurchase() {
            const purchaseSection = document.querySelector(".purchase-section");
            const toggleButton = document.getElementById("mobilePurchaseToggle");
            const toggleIcon = document.getElementById("toggleIcon");

            if (purchaseSection.classList.contains("mobile-expanded")) {
                purchaseSection.classList.remove("mobile-expanded");
                purchaseSection.classList.add("mobile-collapsed");
                toggleButton.classList.remove("expanded");
                toggleIcon.className = "fas fa-chevron-up toggle-icon";
                toggleButton.style.display = "flex";
            } else {
                purchaseSection.classList.remove("mobile-collapsed");
                purchaseSection.classList.add("mobile-expanded");
                toggleButton.classList.add("expanded");
                toggleIcon.className = "fas fa-chevron-down toggle-icon";
                toggleButton.style.display = "flex";
            }
        }

        // Inicializar o estado do container móvel
        document.addEventListener("DOMContentLoaded", function() {
            const purchaseSection = document.querySelector(".purchase-section");
            const toggleButton = document.getElementById("mobilePurchaseToggle");
            const toggleIcon = document.getElementById("toggleIcon");
            if (window.innerWidth <= 767) {
                purchaseSection.classList.add("mobile-collapsed");
                purchaseSection.classList.remove("mobile-expanded");
                toggleButton.style.display = "flex";
                toggleIcon.className = "fas fa-chevron-up toggle-icon";
            } else {
                // Desktop: painel sempre visível, flecha para cima
                toggleIcon.className = "fas fa-chevron-up toggle-icon";
            }
        });

        // Função para mostrar/ocultar campos de endereço
        function toggleAddressFields() {
            const checkbox = document.getElementById("entregar_endereco");
            const addressFields = document.getElementById("addressFields");
            
            if (checkbox.checked) {
                addressFields.classList.add("show");
                addressFields.querySelectorAll("input").forEach(input => {
                    input.required = true;
                });
            } else {
                addressFields.classList.remove("show");
                addressFields.querySelectorAll("input").forEach(input => {
                    input.required = false;
                    input.value = "";
                });
            }
        }

        // Função para mostrar modal de confirmação
        function showConfirmationModal(whatsappUrl) {
            const modal = document.getElementById('confirmationModal');
            const whatsappLink = document.getElementById('whatsappLink');
            const countdownElement = document.getElementById('countdown');
            
            whatsappLink.href = whatsappUrl;
            modal.style.display = 'block';
            
            let countdown = 5;
            const countdownInterval = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    window.open(whatsappUrl, '_blank');
                    modal.style.display = 'none';
                }
            }, 1000);
        }

        // Função para processar a compra
        async function handlePurchase(event) {
            event.preventDefault();
            
            console.log('=== INÍCIO DO CHECKOUT PRODUCT ===');
            
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData);
            
            console.log('Dados do formulário:', data);
            
            if (!data.nome_completo || !data.whatsapp) {
                alert("Por favor, preencha todos os campos obrigatórios.");
                return;
            }

            // Preparar dados para enviar ao save_order.php
            // Enviando dados completos do carrinho
            const cartItems = [];
            <?php foreach ($cart_items as $item): ?>
                cartItems.push({
                    id: <?php echo $item['id']; ?>,
                    name: "<?php echo addslashes($item['name']); ?>",
                    price: <?php echo $item['price']; ?>,
                    quantity: <?php echo $item['quantity']; ?>
                });
            <?php endforeach; ?>

            const orderData = {
                nome_completo: data.nome_completo,
                whatsapp: data.whatsapp,
                entregar_endereco: data.entregar_endereco ? 1 : 0,
                rua: data.rua || '',
                numero: data.numero || '',
                bairro: data.bairro || '',
                cidade: data.cidade || '',
                cep: data.cep || '',
                cart_items: cartItems // Array completo com dados dos produtos
            };

            console.log('Dados do pedido a serem enviados:', orderData);

            try {
                console.log('Enviando requisição para save_order_cart_debug.php...');
                
                const response = await fetch('save_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                });

                console.log('Resposta recebida. Status:', response.status);
                console.log('Headers da resposta:', response.headers);

                const responseText = await response.text();
                console.log('Texto da resposta:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                    console.log('JSON parseado:', result);
                } catch (parseError) {
                    console.error('Erro ao fazer parse do JSON:', parseError);
                    console.log('Resposta não é JSON válido:', responseText);
                    alert('Erro: Resposta do servidor não é JSON válido. Verifique o console para mais detalhes.');
                    return;
                }

                if (result.success) {
                    console.log('Pedido salvo com sucesso:', result.pedido_id);
                    
                    // Criar mensagem do WhatsApp com dados do carrinho
                    let orderSummary = "";
                    <?php foreach ($cart_items as $item): ?>
                        orderSummary += "• <?php echo htmlspecialchars($item["name"]); ?> (x<?php echo $item["quantity"]; ?>): R$ <?php echo number_format($item["price"] * $item["quantity"], 2, ",", "."); ?>\n";
                    <?php endforeach; ?>

                    let message = `🛒 *Novo Pedido Recebido!*\n\n` +
                                  `📋 *Resumo do Pedido:*\n` +
                                  `${orderSummary}\n` +
                                  `━━━━━━━━━━━━━━━━━━━━\n` +
                                  `💰 *Total: R$ <?php echo number_format($cart_total, 2, ",", "."); ?>*\n\n` +
                                  `👤 *Dados do Cliente:*\n` +
                                  `• Nome: ${data.nome_completo}\n` +
                                  `• WhatsApp: ${data.whatsapp}\n`;
                    
                    if (data.entregar_endereco) {
                        message += `\n📍 *Endereço de Entrega:*\n` +
                                   `• Rua: ${data.rua}, ${data.numero}\n` +
                                   `• Bairro: ${data.bairro}\n` +
                                   `• Cidade: ${data.cidade}\n` +
                                   `• CEP: ${data.cep}\n`;
                    } else {
                        message += `\n🏪 *Retirada no local*\n`;
                    }
                    
                    message += `\n📝 *ID do Pedido:* #${result.pedido_id}\n` +
                               `⏰ *Data:* ${new Date().toLocaleDateString('pt-BR')} ${new Date().toLocaleTimeString('pt-BR')}\n\n` +
                               `✅ Pedido registrado com sucesso!`;
                    
                    const whatsappUrl = `https://api.whatsapp.com/send?phone=<?php echo $whatsapp_loja; ?>&text=${encodeURIComponent(message)}`;
                    
                    console.log('URL do WhatsApp:', whatsappUrl);
                    
                    // Limpar formulário
                    event.target.reset();
                    toggleAddressFields();
                    
                    // Limpar carrinho após pedido bem-sucedido
                    try {
                        console.log('Limpando carrinho...');
                        const clearResponse = await fetch('clear_cart.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        });
                        
                        const clearResult = await clearResponse.json();
                        if (clearResult.success) {
                            console.log('Carrinho limpo com sucesso');
                        } else {
                            console.warn('Erro ao limpar carrinho:', clearResult.message);
                        }
                    } catch (clearError) {
                        console.error('Erro na requisição de limpeza do carrinho:', clearError);
                    }
                    
                    // Mostrar modal de confirmação
                    showConfirmationModal(whatsappUrl);

                } else {
                    console.error('Erro retornado pelo servidor:', result.message);
                    alert('Erro ao salvar pedido: ' + result.message);
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                alert('Ocorreu um erro ao finalizar o pedido. Verifique o console para mais detalhes.');
            }
            
            console.log('=== FIM DO CHECKOUT PRODUCT ===');
        }

        // Função para mudar a imagem principal na galeria
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
            document.querySelectorAll('.thumb-image').forEach(img => {
                img.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Funções para gerenciar favoritos
        function toggleFavorite(productId) {
            let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
            // Botão da área de detalhes (abaixo da foto)
            const favoriteBtn = document.getElementById('favoriteBtn');
            const favoriteIcon = document.getElementById('favoriteIcon');
            const favoriteText = document.getElementById('favoriteText');
            // Botão do painel lateral
            const favoriteBtnPanel = document.getElementById('favoriteBtnPanel');
            const favoriteIconPanel = document.getElementById('favoriteIconPanel');
            const favoriteTextPanel = document.getElementById('favoriteTextPanel');

            const isFavorited = favorites.includes(productId);

            if (isFavorited) {
                // Remover dos favoritos
                favorites = favorites.filter(id => id !== productId);
                if (favoriteBtn) favoriteBtn.classList.remove('favorited');
                if (favoriteIcon) favoriteIcon.className = 'fas fa-heart';
                if (favoriteText) favoriteText.textContent = 'Adicionar aos Favoritos';
                if (favoriteBtnPanel) favoriteBtnPanel.classList.remove('favorited');
                if (favoriteIconPanel) favoriteIconPanel.className = 'fas fa-heart';
                if (favoriteTextPanel) favoriteTextPanel.textContent = 'Adicionar aos Favoritos';
                showToast('Produto removido dos favoritos!', 'info');
            } else {
                // Adicionar aos favoritos
                favorites.push(productId);
                if (favoriteBtn) favoriteBtn.classList.add('favorited');
                if (favoriteIcon) favoriteIcon.className = 'fas fa-heart';
                if (favoriteText) favoriteText.textContent = 'Remover dos Favoritos';
                if (favoriteBtnPanel) favoriteBtnPanel.classList.add('favorited');
                if (favoriteIconPanel) favoriteIconPanel.className = 'fas fa-heart';
                if (favoriteTextPanel) favoriteTextPanel.textContent = 'Remover dos Favoritos';
                showToast('Produto adicionado aos favoritos!', 'success');
            }
            localStorage.setItem('favorites', JSON.stringify(favorites));
        }

        // Função para mostrar toast
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check' : 'info'}-circle"></i>
                ${message}
            `;
            
            // Adicionar estilos do toast se não existirem
            if (!document.querySelector('#toast-styles')) {
                const style = document.createElement('style');
                style.id = 'toast-styles';
                style.textContent = `
                    .toast {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: white;
                        padding: 1rem 1.5rem;
                        border-radius: 10px;
                        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
                        z-index: 1000;
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                        transform: translateX(100%);
                        transition: transform 0.3s ease;
                    }
                    .toast.show {
                        transform: translateX(0);
                    }
                    .toast-success {
                        border-left: 4px solid #27ae60;
                        color: #27ae60;
                    }
                    .toast-info {
                        border-left: 4px solid #3498db;
                        color: #3498db;
                    }
                `;
                document.head.appendChild(style);
            }
            
            document.body.appendChild(toast);
            
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => document.body.removeChild(toast), 300);
            }, 3000);
        }

        // Verificar se o produto já está nos favoritos ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            const productId = <?php echo $produto['id']; ?>;
            let favorites = [];
            try {
                favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
            } catch (e) {
                favorites = [];
            }
            const isFavorited = favorites.includes(productId);
            const favoriteBtn = document.getElementById('favoriteBtn');
            const favoriteIcon = document.getElementById('favoriteIcon');
            const favoriteText = document.getElementById('favoriteText');
            const favoriteBtnPanel = document.getElementById('favoriteBtnPanel');
            const favoriteIconPanel = document.getElementById('favoriteIconPanel');
            const favoriteTextPanel = document.getElementById('favoriteTextPanel');
            if (isFavorited) {
                if (favoriteBtn) favoriteBtn.classList.add('favorited');
                if (favoriteIcon) favoriteIcon.className = 'fas fa-heart';
                if (favoriteText) favoriteText.textContent = 'Remover dos Favoritos';
                if (favoriteBtnPanel) favoriteBtnPanel.classList.add('favorited');
                if (favoriteIconPanel) favoriteIconPanel.className = 'fas fa-heart';
                if (favoriteTextPanel) favoriteTextPanel.textContent = 'Remover dos Favoritos';
            } else {
                if (favoriteBtn) favoriteBtn.classList.remove('favorited');
                if (favoriteIcon) favoriteIcon.className = 'fas fa-heart';
                if (favoriteText) favoriteText.textContent = 'Adicionar aos Favoritos';
                if (favoriteBtnPanel) favoriteBtnPanel.classList.remove('favorited');
                if (favoriteIconPanel) favoriteIconPanel.className = 'fas fa-heart';
                if (favoriteTextPanel) favoriteTextPanel.textContent = 'Adicionar aos Favoritos';
            }
            
            // Inicializar o estado do container móvel
            const purchaseSection = document.querySelector(".purchase-section");
            if (window.innerWidth <= 767) {
                purchaseSection.classList.add("mobile-collapsed");
            }
        });

        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('confirmationModal');
            const politicaModal = document.getElementById('politicaModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
            if (event.target === politicaModal) {
                politicaModal.style.display = 'none';
            }
        }

        // Funções para o modal da política de devolução
        function openPoliticaModal() {
            const modal = document.getElementById('politicaModal');
            modal.style.display = 'block';
        }

        function closePoliticaModal() {
            const modal = document.getElementById('politicaModal');
            modal.style.display = 'none';
        }

        // Fechar modal com tecla ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const politicaModal = document.getElementById('politicaModal');
                const confirmationModal = document.getElementById('confirmationModal');
                if (politicaModal.style.display === 'block') {
                    politicaModal.style.display = 'none';
                }
                if (confirmationModal.style.display === 'block') {
                    confirmationModal.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>

