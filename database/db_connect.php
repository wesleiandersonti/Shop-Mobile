<?php

$servername = "localhost";
$username = "mobileevo"; // Substitua pelo seu nome de usuário do banco de dados
$password = "LSYHhAA5SXBhz6ei"; // Substitua pela sua senha do banco de dados
$dbname = "mobileevo"; // Substitua pelo nome do seu banco de dados, se for diferente

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Define o modo de erro do PDO para exceção
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Conexão bem-sucedida";
} catch(PDOException $e) {
    die("Conexão falhou: " . $e->getMessage());
}

// Sistema de Migração Automática Simples
// Adiciona novos campos automaticamente se não existirem
if (!defined('SKIP_MIGRATIONS')) {
    try {
        // Lista de campos que devem existir na tabela configuracoes
        $campos_necessarios = [
            'garantia' => "ALTER TABLE configuracoes ADD COLUMN garantia VARCHAR(100) DEFAULT '3 meses' NOT NULL",
            'politica_devolucao' => "ALTER TABLE configuracoes ADD COLUMN politica_devolucao TEXT NULL",
            'instagram_url' => "ALTER TABLE configuracoes ADD COLUMN instagram_url VARCHAR(255) NULL",
            'facebook_url' => "ALTER TABLE configuracoes ADD COLUMN facebook_url VARCHAR(255) NULL",
            'youtube_url' => "ALTER TABLE configuracoes ADD COLUMN youtube_url VARCHAR(255) NULL",
            'x_twitter_url' => "ALTER TABLE configuracoes ADD COLUMN x_twitter_url VARCHAR(255) NULL"
        ];
        
        // Verificar e adicionar cada campo
        foreach ($campos_necessarios as $campo => $sql) {
            try {
                // Verificar se o campo existe
                $stmt = $conn->prepare("SHOW COLUMNS FROM configuracoes LIKE ?");
                $stmt->execute([$campo]);
                
                if (!$stmt->fetch()) {
                    // Campo não existe, adicionar
                    $conn->exec($sql);
                    error_log("Campo '{$campo}' adicionado automaticamente na tabela configuracoes");
                }
            } catch (Exception $e) {
                // Ignorar erro se campo já existe ou outros problemas
                error_log("Erro ao verificar/adicionar campo '{$campo}': " . $e->getMessage());
            }
        }
        
        // Garantir que existe pelo menos um registro na tabela
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM configuracoes");
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($count == 0) {
                $stmt = $conn->prepare("INSERT INTO configuracoes (nome_loja, whatsapp, titulo_footer, garantia) VALUES (?, ?, ?, ?)");
                $stmt->execute(['Loja Virtual', '', 'Loja Virtual - Todos os direitos reservados', '3 meses']);
                error_log("Registro padrão criado na tabela configuracoes");
            }
        } catch (Exception $e) {
            error_log("Erro ao verificar/criar registro padrão: " . $e->getMessage());
        }
        
    } catch (Exception $e) {
        // Em caso de erro geral, apenas logar (não quebrar o site)
        error_log("Erro no sistema de migração automática: " . $e->getMessage());
    }
}

?>

