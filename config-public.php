<?php
// Endpoint público: retorna configurações visíveis ao frontend
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

$defaults = [
    'limite_consultas' => '4',
    'valor_pix'        => '1.00',
];

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );

    // Criar tabela config se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS config (
        chave      VARCHAR(50)  NOT NULL PRIMARY KEY,
        valor      VARCHAR(255) NOT NULL,
        descricao  VARCHAR(255) DEFAULT NULL,
        updated_at INT UNSIGNED NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Inserir valores padrão se a tabela estiver vazia
    foreach ($defaults as $chave => $valor) {
        $pdo->prepare("INSERT IGNORE INTO config (chave, valor, descricao, updated_at) VALUES (?, ?, ?, ?)")
            ->execute([$chave, $valor, null, time()]);
    }

    // Buscar todos os valores
    $rows = $pdo->query("SELECT chave, valor FROM config")->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode([
        'limite_consultas' => (int)($rows['limite_consultas'] ?? $defaults['limite_consultas']),
        'valor_pix'        => number_format((float)($rows['valor_pix'] ?? $defaults['valor_pix']), 2, ',', '.'),
    ]);

} catch (Exception $e) {
    // Fallback para os valores padrão se o banco falhar
    echo json_encode([
        'limite_consultas' => (int)$defaults['limite_consultas'],
        'valor_pix'        => number_format((float)$defaults['valor_pix'], 2, ',', '.'),
    ]);
}
