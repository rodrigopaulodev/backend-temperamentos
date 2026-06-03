<?php
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['payload'])) {
    http_response_code(400); echo '<p>Dados inválidos.</p>'; exit;
}

$raw = json_decode($_POST['payload'], true);
if (!$raw || empty($raw['winner'])) { http_response_code(400); exit; }

$valid = ['apaixonado','colérico','sanguíneo','sentimental','apático','fleumático','nervoso','amorfo'];
$winner    = in_array($raw['winner'], $valid, true) ? $raw['winner'] : null;
$secondKey = isset($raw['secondKey']) && in_array($raw['secondKey'], $valid, true) ? $raw['secondKey'] : null;
$scores    = isset($raw['scores']) && is_array($raw['scores']) ? $raw['scores'] : [];
$ai        = isset($raw['ai'])     && is_array($raw['ai'])     ? $raw['ai']     : [];
if (!$winner) { http_response_code(400); exit; }

function e(string $s): string {
    return htmlspecialchars(strip_tags($s), ENT_QUOTES, 'UTF-8');
}

$temps = [
    'apaixonado' => [
        'name'=>'Apaixonado','emoji'=>'🔥','badge'=>'Emotivo · Ativo · Secundário',
        'color'=>'#C0392B','light'=>'#FDEAEA','accent'=>'#E74C3C','img'=>'t-apaixonado.png',
        'desc'=>'Ardente, tenacioso, líder com grande força de vontade. Defeito dominante: ira e rancor. Virtude a cultivar: mansidão e perdão.',
        'pos'=>['Liderança natural e carismática','Determinação e perseverança','Alta energia e proatividade','Visão estratégica','Fidelidade às causas'],
        'neg'=>['Tendência à ira e ao rancor','Dificuldade em perdoar','Exigência excessiva','Orgulho e obstinação','Dureza com os outros'],
        'santos'=>[['emoji'=>'✝️','nome'=>'Santa Teresa d\'Ávila','desc'=>'Mística e reformadora — liderança ardente a serviço de Deus'],['emoji'=>'⚔️','nome'=>'São Paulo Apóstolo','desc'=>'Transformação radical e energia apostólica incansável'],['emoji'=>'🔥','nome'=>'Joana d\'Arc','desc'=>'Fé inabalável, coragem e ação movida por missão divina']],
    ],
    'colérico' => [
        'name'=>'Colérico','emoji'=>'⚡','badge'=>'Emotivo · Ativo · Primário',
        'color'=>'#C0392B','light'=>'#FFE0E0','accent'=>'#E24B4A','img'=>'t-colerico.png',
        'desc'=>'Impulsivo, aventureiro, cheio de energia. Defeito dominante: ira e superficialidade. Virtude a cultivar: paciência e profundidade.',
        'pos'=>['Iniciativa e coragem','Energia inesgotável','Decisão rápida','Carisma natural','Otimismo prático'],
        'neg'=>['Impulsividade','Dificuldade de concentração','Superficialidade emocional','Explosões de raiva','Impaciência'],
        'santos'=>[['emoji'=>'⚡','nome'=>'São Pedro Apóstolo','desc'=>'Entusiasmo e impulsividade convertidos em rocha da Igreja'],['emoji'=>'🛡️','nome'=>'São Jorge Mártir','desc'=>'Coragem e ação direta contra o mal com fé inabalável'],['emoji'=>'🌟','nome'=>'São Bartolomeu','desc'=>'Sinceridade direta e disposição imediata para seguir Cristo']],
    ],
    'sanguíneo' => [
        'name'=>'Sanguíneo','emoji'=>'☀️','badge'=>'Não-Emotivo · Ativo · Primário',
        'color'=>'#D35400','light'=>'#FFF0C0','accent'=>'#E67E22','img'=>'t-sanguineo.png',
        'desc'=>'Alegre, comunicativo, adaptável. Defeito dominante: inconstância e busca de aprovação. Virtude a cultivar: perseverança e vida interior.',
        'pos'=>['Otimismo contagiante','Facilidade de comunicação','Adaptabilidade','Generosidade espontânea','Criatividade social'],
        'neg'=>['Inconstância emocional','Superficialidade nos vínculos','Dificuldade de foco','Busca de aprovação','Promete mais do que cumpre'],
        'santos'=>[['emoji'=>'☀️','nome'=>'São Filipe Néri','desc'=>'Alegria contagiante e apostolado através do bom humor'],['emoji'=>'🎵','nome'=>'São Francisco de Sales','desc'=>'Gentileza e comunicação que conquistava corações com leveza'],['emoji'=>'✨','nome'=>'São João Bosco','desc'=>'Otimismo e amor pelos jovens transformando vidas com alegria']],
    ],
    'sentimental' => [
        'name'=>'Sentimental','emoji'=>'💔','badge'=>'Emotivo · Inativo · Secundário',
        'color'=>'#6C3483','light'=>'#FCE4EC','accent'=>'#9B59B6','img'=>'t-sentimental.png',
        'desc'=>'Profundo, reflexivo, criativo. Defeito dominante: tristeza e autocrítica. Virtude a cultivar: esperança e autocompaixão.',
        'pos'=>['Profundidade emocional','Empatia intensa','Senso estético elevado','Lealdade profunda','Riqueza interior'],
        'neg'=>['Tendência ao pessimismo','Autocrítica excessiva','Dificuldade em superar mágoas','Isolamento','Melancolia'],
        'santos'=>[['emoji'=>'🌹','nome'=>'Santa Teresinha do Menino Jesus','desc'=>'Sensibilidade profunda transformada em pequeno caminho de amor'],['emoji'=>'📖','nome'=>'São João Apóstolo','desc'=>'Vida interior rica e amor contemplativo — o discípulo amado'],['emoji'=>'💧','nome'=>'São Agostinho','desc'=>'Profundidade emocional convertida em busca ardente de Deus']],
    ],
    'apático' => [
        'name'=>'Apático','emoji'=>'🪨','badge'=>'Não-Emotivo · Inativo · Secundário',
        'color'=>'#34495E','light'=>'#ECEFF1','accent'=>'#95A5A6','img'=>'t-apatico.png',
        'desc'=>'Tranquilo, observador, sábio. Defeito dominante: apatia e desinteresse. Virtude a cultivar: fervor e iniciativa.',
        'pos'=>['Estabilidade emocional','Prudência e sabedoria','Imparcialidade','Presença tranquilizadora','Constância'],
        'neg'=>['Passividade excessiva','Dificuldade de motivação','Indiferença aparente','Resistência a mudanças','Procrastinação'],
        'santos'=>[['emoji'=>'🕊️','nome'=>'São Bento de Núrsia','desc'=>'Equilíbrio e ordem — Ora et Labora como caminho de santidade'],['emoji'=>'📜','nome'=>'São Tomás de Aquino','desc'=>'Reflexão profunda e sabedoria que ordenava tudo com calma'],['emoji'=>'⚖️','nome'=>'São Tomás Moro','desc'=>'Serenidade e firmeza interior mesmo diante do martírio']],
    ],
    'fleumático' => [
        'name'=>'Fleumático','emoji'=>'🌊','badge'=>'Não-Emotivo · Ativo · Secundário',
        'color'=>'#0E8C6A','light'=>'#C8F0E8','accent'=>'#1ABC9C','img'=>'t-fleumatico.png',
        'desc'=>'Confiável, paciente, constante. Defeito dominante: acídia e comodismo. Virtude a cultivar: diligência e entusiasmo.',
        'pos'=>['Confiabilidade','Paciência e equilíbrio','Mediador de conflitos','Persistência silenciosa','Método e organização'],
        'neg'=>['Acídia e preguiça espiritual','Comodismo','Lentidão para decidir','Falta de entusiasmo','Resistência ao novo'],
        'santos'=>[['emoji'=>'🌊','nome'=>'São João Maria Vianney','desc'=>'Paciência e constância no confessonário — pilar silencioso da Igreja'],['emoji'=>'🕯️','nome'=>'São José','desc'=>'Fidelidade discreta e trabalho constante a serviço da Sagrada Família'],['emoji'=>'📿','nome'=>'São Domingos de Gusmão','desc'=>'Método constante e perseverante na pregação e no rosário']],
    ],
    'nervoso' => [
        'name'=>'Nervoso','emoji'=>'🎨','badge'=>'Emotivo · Inativo · Primário',
        'color'=>'#A04000','light'=>'#FFF3CD','accent'=>'#F39C12','img'=>'t-nervoso.png',
        'desc'=>'Sensível, artístico, compassivo. Defeito dominante: sensibilidade que escraviza. Virtude a cultivar: fortaleza e desapego.',
        'pos'=>['Sensibilidade artística','Compaixão profunda','Criatividade','Percepção aguçada','Originalidade'],
        'neg'=>['Instabilidade emocional','Hipersensibilidade','Dificuldade de compromisso','Introspecção excessiva','Dispersão'],
        'santos'=>[['emoji'=>'🎨','nome'=>'Beato Angélico','desc'=>'Sensibilidade artística inteiramente consagrada à beleza de Deus'],['emoji'=>'🌺','nome'=>'Santa Rosa de Lima','desc'=>'Vida interior intensa e sensibilidade convertida em penitência amorosa'],['emoji'=>'🕊️','nome'=>'São Francisco de Assis','desc'=>'Sensibilidade poética e compaixão por toda a criação de Deus']],
    ],
    'amorfo' => [
        'name'=>'Amorfo','emoji'=>'💨','badge'=>'Não-Emotivo · Inativo · Primário',
        'color'=>'#5D6D7E','light'=>'#E0E0E0','accent'=>'#BDC3C7','img'=>'t-amorfo.png',
        'desc'=>'Influenciável, versátil, descompromissado. Defeito dominante: preguiça e negligência. Virtude a cultivar: responsabilidade e firmeza.',
        'pos'=>['Versatilidade','Sem conflitos','Adaptabilidade total','Abertura mental','Facilidade de convívio'],
        'neg'=>['Falta de iniciativa','Negligência','Influenciável','Sem objetivos claros','Preguiça crônica'],
        'santos'=>[['emoji'=>'🕊️','nome'=>'São Zacarias','desc'=>'Acolhida simples e disponibilidade que abriu espaço para o milagre'],['emoji'=>'🌾','nome'=>'São José de Cupertino','desc'=>'Simplicidade desapegada que se tornou instrumento de maravilhas'],['emoji'=>'💨','nome'=>'São Bartolomeu Dias','desc'=>'Docilidade à graça que permite a Deus agir apesar das fraquezas']],
    ],
];

$t  = $temps[$winner];
$t2 = $secondKey ? ($temps[$secondKey] ?? null) : null;

// Eixos (scores agora são e/a/r normalizados 0-100)
$axisE = isset($scores['e']) ? (int)$scores['e'] : 0;
$axisA = isset($scores['a']) ? (int)$scores['a'] : 0;
$axisR = isset($scores['r']) ? (int)$scores['r'] : 0;

$eLabel = $axisE > 50 ? 'Emotivo' : 'Não-Emotivo';
$aLabel = $axisA > 50 ? 'Ativo'   : 'Inativo';
$rLabel = $axisR > 50 ? 'Secundário' : 'Primário';

// Carregar imagem do personagem como base64 (evita problemas de cache e URL)
$imgFile = __DIR__ . '/../temperamentos/assests/personagens/' . $t['img'];
if (file_exists($imgFile)) {
    $imgData = base64_encode(file_get_contents($imgFile));
    $imgMime = 'image/png';
    $imgSrc  = 'data:' . $imgMime . ';base64,' . $imgData;
} else {
    $imgSrc = 'https://akutis.com.br/temperamentos/assests/personagens/' . $t['img'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Meu Temperamento: <?= e($t['name']) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,Arial,sans-serif;background:#f5f3ff;color:#1a1830;font-size:13.5px;line-height:1.65}
.wrap{max-width:680px;margin:0 auto;padding:32px 36px}

/* CABEÇALHO — igual à página */
.header{text-align:center;padding:28px 0 20px;margin-bottom:20px}
.res-emoji{font-size:2.8rem;display:block;margin-bottom:10px}
.sub-label{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#9090aa;margin-bottom:4px}
.temp-name{font-size:2.2rem;font-weight:800;color:#2d2b45;margin-bottom:8px;line-height:1.1}
.badge{display:inline-block;background:#f0effc;color:#534AB7;border-radius:99px;padding:4px 14px;font-size:10px;font-weight:500;margin-bottom:12px}
.temp-desc{font-size:11.5px;color:#6e6c85;line-height:1.75;max-width:520px;margin:0 auto}

/* SECUNDÁRIO */
.secondary{border-left:4px solid <?= $t2 ? $t2['accent'] : '#ccc' ?>;background:#fff;padding:11px 14px;border-radius:0 10px 10px 0;margin-bottom:18px;box-shadow:0 1px 6px rgba(0,0,0,.05)}
.sec-label{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#9090aa;margin-bottom:3px}
.sec-name{font-size:.95rem;font-weight:700;color:<?= $t2 ? $t2['color'] : '#333' ?>;margin-bottom:3px}
.sec-desc{font-size:11px;color:#4a4868;line-height:1.6}

/* 3 EIXOS */
.eixos-section{background:#fff;border-radius:16px;padding:20px;margin-bottom:18px;box-shadow:0 4px 20px rgba(127,119,221,.15)}
.eixos-header{display:grid;grid-template-columns:100px 1fr;gap:16px;align-items:center;margin-bottom:16px}
.eixos-personagem{width:100px;height:100px;object-fit:contain;filter:drop-shadow(0 4px 10px rgba(127,119,221,.25))}
.eixos-titulo{font-family:'Segoe UI',Arial,sans-serif;font-size:1.3rem;font-weight:900;line-height:1.15;color:#2d2b45;text-transform:uppercase;letter-spacing:1px}
.eixos-titulo span{color:#7F77DD}
.eixos-resumo-txt{font-size:11px;color:#555;line-height:1.6;margin-top:8px}
.eixos-resumo-txt strong{color:#534AB7}
.eixos-cards{display:flex;flex-direction:column;gap:10px;margin-bottom:16px}
.eixo-card{border-radius:8px;padding:12px 14px;display:flex;align-items:center;gap:12px;background:#fff}
.eixo-icon{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.eixo-body{flex:1}
.eixo-name{font-size:.85rem;font-weight:800;margin-bottom:2px}
.eixo-desc-txt{font-size:10px;color:#888;margin-bottom:7px;line-height:1.4}
.eixo-bar-track{height:8px;background:#f0f0f0;border-radius:99px;overflow:hidden;margin-bottom:4px}
.eixo-bar-fill{height:8px;border-radius:99px}
.eixo-bar-footer{display:flex;justify-content:space-between;font-size:10px;font-weight:700}
.eixos-resumo-titulo{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#7b73d5;border-bottom:2px solid #7b73d5;padding-bottom:6px;margin-bottom:10px}
.eixos-resumo{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
.eixo-resumo-item{border-radius:8px;padding:10px 12px;display:flex;flex-direction:column;gap:4px}
.eixo-resumo-titulo-item{font-size:10px;font-weight:800}
.eixo-resumo-desc{font-size:9.5px;color:#555;line-height:1.45}

/* PONTOS */
.section-card{background:#fff;border-radius:14px;padding:16px;margin-bottom:16px;box-shadow:0 1px 6px rgba(0,0,0,.05)}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.col-title{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#9090aa;margin-bottom:8px}
.pf{display:flex;align-items:flex-start;gap:7px;font-size:11.5px;color:#1a1830;margin-bottom:6px;line-height:1.4}
.dot{width:7px;height:7px;min-width:7px;border-radius:50%;margin-top:3px}
.dot-g{background:#1D9E75}.dot-r{background:#D85A30}

/* IA */
.ai-title{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#534AB7;margin-bottom:5px}
.ai-text{font-size:12px;color:#2d2b45;line-height:1.75;margin-bottom:14px}
.person{display:flex;gap:10px;background:#f4f2ff;border-radius:8px;padding:10px 12px;margin-bottom:8px}
.p-emoji{font-size:1.3rem;flex-shrink:0;line-height:1}
.p-name{font-weight:700;font-size:11.5px;color:#1a1830;margin-bottom:2px}
.p-desc{font-size:11px;color:#4a4868;line-height:1.6}

/* FOOTER */
hr{border:none;border-top:1px solid #e8e5f4;margin:16px 0}
.footer{text-align:center;font-size:10px;color:#9090aa;margin-top:20px;padding-top:12px;border-top:1px solid #e8e5f4;line-height:1.9}
.footer strong{color:#534AB7}

@page{size:A4;margin:0}
@media print{
  body{-webkit-print-color-adjust:exact;print-color-adjust:exact;background:#fff}
  .wrap{padding:10mm 14mm}
  .header,.eixos-section,.section-card{break-inside:avoid}
  .person{break-inside:avoid}
}
</style>
</head>
<body>
<div class="wrap">

  <!-- CABEÇALHO — igual à página -->
  <div class="header">
    <span class="res-emoji"><?= $t['emoji'] ?></span>
    <div class="sub-label">Temperamento Dominante</div>
    <div class="temp-name"><?= e($t['name']) ?></div>
    <div class="badge"><?= e($t['badge']) ?></div>
    <p class="temp-desc"><?= e($t['desc']) ?></p>
  </div>

  <?php if ($t2): ?>
  <div class="secondary">
    <div class="sec-label">Com influência de</div>
    <div class="sec-name"><?= $t2['emoji'] ?> <?= e($t2['name']) ?></div>
    <p class="sec-desc"><?= e($t2['desc']) ?></p>
  </div>
  <?php endif; ?>

  <!-- 3 EIXOS -->
  <?php
  $resumoE = $axisE>50 ? 'movido por emoções' : 'de mente equilibrada';
  $resumoA = $axisA>50 ? 'com muita energia para agir' : 'que prefere agir com cautela';
  $resumoR = $axisR>50 ? 'que busca conexões significativas' : 'que vive o presente com leveza';
  $resumoTxt = "Você é $resumoE, $resumoA, $resumoR. <strong>Esse é o que te torna único!</strong>";
  $eLabelResPos = 'Você sente intensamente e se conecta com profundidade.';
  $eLabelResNeg = 'Você é equilibrado emocionalmente e mantém a calma.';
  $aLabelResPos = 'Você tem energia de sobra e gosta de transformar ideias em ação.';
  $aLabelResNeg = 'Você prefere conservar energia e agir com cautela.';
  $rLabelResPos = 'Você está em desenvolvimento e cada passo te aproxima da sua melhor versão.';
  $rLabelResNeg = 'Você vive o presente com leveza e não carrega o passado.';
  ?>
  <div class="eixos-section">
    <!-- Header com personagem + título (igual à página) -->
    <div class="eixos-header">
      <img class="eixos-personagem" src="<?= $imgSrc ?>" alt="<?= e($t['name']) ?>">
      <div>
        <div class="eixos-titulo">Seu perfil nos <span>3 eixos</span></div>
        <p class="eixos-resumo-txt"><?= $resumoTxt ?></p>
      </div>
    </div>

    <div class="eixos-cards">
      <div class="eixo-card" style="background:#ede9fe;border-left:4px solid #7F77DD">
        <div class="eixo-icon" style="background:#ede9fe">🌟</div>
        <div class="eixo-body">
          <div class="eixo-name" style="color:#7F77DD">Emotividade</div>
          <div class="eixo-desc-txt">Refere-se à intensidade e à facilidade com que você sente e expressa emoções.</div>
          <div class="eixo-bar-track"><div class="eixo-bar-fill" style="width:<?= $axisE ?>%;background:#7F77DD"></div></div>
          <div class="eixo-bar-footer">
            <span style="color:#7F77DD"><?= $axisE ?>%</span>
            <span style="color:#7F77DD"><?= $axisE>50?'😊 Emotivo':'😐 Não-Emotivo' ?></span>
          </div>
        </div>
      </div>
      <div class="eixo-card" style="background:#fff3e0;border-left:4px solid #f97316">
        <div class="eixo-icon" style="background:#fff3e0">⚡</div>
        <div class="eixo-body">
          <div class="eixo-name" style="color:#ea580c">Atividade</div>
          <div class="eixo-desc-txt">Mostra seu nível de energia, iniciativa e disposição para agir e realizar.</div>
          <div class="eixo-bar-track"><div class="eixo-bar-fill" style="width:<?= $axisA ?>%;background:#f97316"></div></div>
          <div class="eixo-bar-footer">
            <span style="color:#f97316"><?= $axisA ?>%</span>
            <span style="color:#f97316"><?= $axisA>50?'⚡ Ativo':'😴 Inativo' ?></span>
          </div>
        </div>
      </div>
      <div class="eixo-card" style="background:#ccfbf1;border-left:4px solid #14b8a6">
        <div class="eixo-icon" style="background:#ccfbf1">🎯</div>
        <div class="eixo-body">
          <div class="eixo-name" style="color:#0d9488">Ressonância</div>
          <div class="eixo-desc-txt">Indica sua sensibilidade às pessoas e ao ambiente ao seu redor.</div>
          <div class="eixo-bar-track"><div class="eixo-bar-fill" style="width:<?= $axisR ?>%;background:#14b8a6"></div></div>
          <div class="eixo-bar-footer">
            <span style="color:#14b8a6"><?= $axisR ?>%</span>
            <span style="color:#14b8a6"><?= $axisR>50?'💭 Secundário':'⚡ Primário' ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="eixos-resumo-titulo">EM RESUMO, VOCÊ É:</div>
    <div class="eixos-resumo">
      <div class="eixo-resumo-item" style="background:#ede9fe;border-bottom:3px solid #7F77DD">
        <div class="eixo-resumo-titulo-item" style="color:#7F77DD"><?= $axisE>50?'😊 Emotivo':'😐 Não-Emotivo' ?></div>
        <div class="eixo-resumo-desc"><?= $axisE>50?$eLabelResPos:$eLabelResNeg ?></div>
      </div>
      <div class="eixo-resumo-item" style="background:#fff3e0;border-bottom:3px solid #f97316">
        <div class="eixo-resumo-titulo-item" style="color:#f97316"><?= $axisA>50?'⚡ Ativo':'😴 Inativo' ?></div>
        <div class="eixo-resumo-desc"><?= $axisA>50?$aLabelResPos:$aLabelResNeg ?></div>
      </div>
      <div class="eixo-resumo-item" style="background:#ccfbf1;border-bottom:3px solid #14b8a6">
        <div class="eixo-resumo-titulo-item" style="color:#14b8a6"><?= $axisR>50?'💭 Secundário':'⚡ Primário' ?></div>
        <div class="eixo-resumo-desc"><?= $axisR>50?$rLabelResPos:$rLabelResNeg ?></div>
      </div>
    </div>

    <p style="text-align:center;font-size:10px;color:#a78bfa;margin-top:12px">Continue assim! Seu autoconhecimento é o seu maior poder.</p>
  </div>

  <!-- PONTOS FORTES / A DESENVOLVER -->
  <div class="section-card">
    <div class="two-col">
      <div>
        <div class="col-title">Pontos Fortes</div>
        <?php foreach ($t['pos'] as $item): ?>
        <div class="pf"><div class="dot dot-g"></div><?= e($item) ?></div>
        <?php endforeach; ?>
      </div>
      <div>
        <div class="col-title">A Desenvolver</div>
        <?php foreach ($t['neg'] as $item): ?>
        <div class="pf"><div class="dot dot-r"></div><?= e($item) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php if (!empty($ai)): ?>
  <!-- ANÁLISE DA IA -->
  <div class="section-card">
    <?php foreach ($ai as $sec): ?>
      <?php if (empty($sec['persons'])): ?>
      <div class="ai-title"><?= e($sec['title']) ?></div>
      <div class="ai-text"><?= nl2br(e($sec['text'])) ?></div>
      <?php else: ?>
      <div class="ai-title"><?= e($sec['title']) ?></div>
      <?php foreach ($sec['persons'] as $p): ?>
      <div class="person">
        <div class="p-emoji"><?= $p['emoji'] ?></div>
        <div>
          <div class="p-name"><?= e($p['name']) ?></div>
          <div class="p-desc"><?= e($p['desc']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- SANTOS COM ESTE TEMPERAMENTO -->
  <?php if (!empty($t['santos'])): ?>
  <div class="section-card">
    <div class="col-title" style="margin-bottom:10px">Santos com este temperamento</div>
    <?php foreach ($t['santos'] as $santo): ?>
    <div class="person">
      <div class="p-emoji"><?= $santo['emoji'] ?></div>
      <div>
        <div class="p-name"><?= e($santo['nome']) ?></div>
        <div class="p-desc"><?= e($santo['desc']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="footer">
    Sistema Heymans-Le Senne · Heymans &amp; Wiersma · Pe. Gonzalez · Royo Marín<br>
    Padre Paulo Ricardo · Murilo Frizanco<br>
    <strong>Desenvolvido por Rodrigo Paulo</strong> · akutis.com.br/temperamentos
  </div>

</div>
<script>
window.onload = function() {
  setTimeout(function() { window.print(); }, 800);
};
</script>
</body>
</html>
