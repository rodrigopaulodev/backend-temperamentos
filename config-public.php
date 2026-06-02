<?php
// Endpoint público: retorna configurações visíveis ao frontend
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

$defaults = [
    'limite_consultas' => 4,
    'valor_pix'        => 1.00,
];

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );

    // Criar tabela com tipos corretos
    $pdo->exec("CREATE TABLE IF NOT EXISTS config (
        chave      VARCHAR(50)      NOT NULL PRIMARY KEY,
        valor_int  INT              DEFAULT NULL COMMENT 'Para valores inteiros (ex: limite_consultas)',
        valor_dec  DECIMAL(10,2)    DEFAULT NULL COMMENT 'Para valores decimais (ex: valor_pix)',
        descricao  VARCHAR(255)     DEFAULT NULL,
        updated_at INT UNSIGNED     NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Migration: se ainda existir coluna 'valor' (VARCHAR antigo), migrar e dropar
    $cols = $pdo->query("SHOW COLUMNS FROM config LIKE 'valor'")->fetchAll();
    if (!empty($cols)) {
        $pdo->exec("UPDATE config SET valor_int = CAST(valor AS UNSIGNED) WHERE chave = 'limite_consultas'");
        $pdo->exec("UPDATE config SET valor_dec = CAST(valor AS DECIMAL(10,2)) WHERE chave = 'valor_pix'");
        $pdo->exec("ALTER TABLE config DROP COLUMN valor");
    }

    // Inserir padrões se não existirem
    $pdo->prepare("INSERT IGNORE INTO config (chave, valor_int, descricao, updated_at) VALUES ('limite_consultas', ?, 'Máximo de consultas gratuitas por IP', ?)")
        ->execute([$defaults['limite_consultas'], time()]);
    $pdo->prepare("INSERT IGNORE INTO config (chave, valor_dec, descricao, updated_at) VALUES ('valor_pix', ?, 'Valor em reais para liberar novas consultas', ?)")
        ->execute([$defaults['valor_pix'], time()]);

    // Buscar valores
    $rows = $pdo->query("SELECT chave, valor_int, valor_dec FROM config")->fetchAll(PDO::FETCH_ASSOC);
    $cfg = [];
    foreach ($rows as $row) $cfg[$row['chave']] = $row;

    $limite = isset($cfg['limite_consultas']) ? (int)$cfg['limite_consultas']['valor_int'] : $defaults['limite_consultas'];
    $pix    = isset($cfg['valor_pix'])        ? (float)$cfg['valor_pix']['valor_dec']      : $defaults['valor_pix'];

    echo json_encode([
        'limite_consultas' => $limite,
        'valor_pix'        => number_format($pix, 2, ',', '.'),
    ]);

} catch (Exception $e) {
    echo json_encode([
        'limite_consultas' => $defaults['limite_consultas'],
        'valor_pix'        => number_format($defaults['valor_pix'], 2, ',', '.'),
    ]);
}
