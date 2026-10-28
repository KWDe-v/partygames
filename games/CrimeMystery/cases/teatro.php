<?php
/**
 * Caso: O Lustre do Teatro Real.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'teatro',
    'title' => 'O Lustre do Teatro Real',
    'difficulty' => 'Médio',
    'intro' => 'Ensaio geral da grande estreia do Teatro Real. No segundo ato, o lustre de cristal despencou sobre o palco e passou a centímetros da protagonista, Clara Moura. Por sorte, ninguém se feriu — mas o cabo não se rompeu sozinho.',
    'crime' => 'O cabo de aço que sustentava o lustre foi serrado de propósito. Foi uma tentativa de ferir a atriz principal.',
    'victim' => 'Clara Moura, atriz principal',
    'location' => 'Palco do Teatro Real',
    'time' => 'Intervalo do ensaio, entre 20h00 e 20h30',
    'suspects' => [
        'bianca' => ['name' => 'Bianca Torres', 'role' => 'Atriz substituta', 'profile' => 'Substituta de Clara e integrante do coro das estrelas, de figurino dourado.', 'motive' => 'Nenhum motivo aparente: diz admirar Clara.', 'alibi' => 'Diz que passou o intervalo no camarim, retocando a maquiagem.'],
        'henrique' => ['name' => 'Henrique Salles', 'role' => 'Diretor', 'profile' => 'Exigente e explosivo. Discutiu com Clara sobre o final da peça.', 'motive' => 'Clara ameaçou abandonar a montagem.', 'alibi' => 'Diz que ficou na plateia com o produtor.'],
        'juca' => ['name' => 'Juca Ferreira', 'role' => 'Técnico de palco', 'profile' => 'Cuida das cordas, cabos e cenários. Conhece cada canto do teatro.', 'motive' => 'Foi culpado por um acidente no ano passado.', 'alibi' => 'Diz que estava na coxia trocando o cenário.'],
        'rogerio' => ['name' => 'Rogério Vaz', 'role' => 'Patrocinador e ex-noivo de Clara', 'profile' => 'Financia a peça. O noivado com Clara terminou há um mês.', 'motive' => 'Ameaçou cortar o patrocínio depois do término.', 'alibi' => 'Diz que chegou tarde ao teatro.'],
    ],
    'motives' => [
        'papel' => 'Ficar com o papel principal',
        'ciume' => 'Ciúmes depois do fim do noivado',
        'controle' => 'Punir a atriz que ameaçou abandonar a peça',
        'seguro' => 'Receber o seguro do teatro',
    ],
    'clues' => [
        'te1' => ['title' => 'Cabo serrado', 'text' => 'O cabo de aço foi serrado quase todo, deixando um único fio: ele cederia com a vibração da música alta do segundo ato.', 'unlock' => ['type' => 'start']],
        'te2' => ['title' => 'A varanda técnica', 'text' => 'O cabo só é alcançável pela varanda técnica, acessada por uma escada que fica atrás do camarim 2.', 'unlock' => ['type' => 'start'], 'important' => true],
        'te3' => ['title' => 'Os camarins', 'text' => 'O camarim 1 é de Clara. O camarim 2, colado à escada técnica, é de Bianca, a substituta.', 'unlock' => ['type' => 'time', 'at' => 0.3], 'important' => true],
        'te4' => ['title' => 'Limalha com glitter', 'text' => 'A limalha de aço no chão da varanda está misturada a glitter dourado do figurino do coro das estrelas.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'te5' => ['title' => 'O ex-noivo', 'text' => 'Rogério ameaçou em público cortar o patrocínio depois que Clara terminou o noivado.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'te6' => ['title' => 'Serra e luva', 'text' => 'Na varanda técnica, uma minisserra embrulhada em uma luva dourada de figurino.', 'unlock' => ['type' => 'search'], 'important' => true],
        'te7' => ['title' => 'Roteiro anotado', 'text' => 'O roteiro de Bianca tem todas as falas da protagonista sublinhadas e a frase "a estreia vai ser minha" escrita na capa.', 'unlock' => ['type' => 'search'], 'important' => true],
        'te8' => ['title' => 'Quem subiu a escada', 'text' => 'Juca viu alguém de figurino dourado subir a escada técnica às 20h15, no meio do intervalo.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'te9' => ['title' => 'Gravação da plateia', 'text' => 'A câmera que grava os ensaios mostra Henrique na plateia, ao lado do produtor, durante todo o intervalo.', 'unlock' => ['type' => 'time', 'at' => 0.6]],
        'te10' => ['title' => 'Registro de chegada', 'text' => 'A portaria registrou a chegada de Rogério às 20h40, depois do fim do intervalo.', 'unlock' => ['type' => 'search']],
    ],
    'locations' => [
        'varanda' => ['name' => 'Varanda técnica', 'clue' => 'te6'],
        'camarim2' => ['name' => 'Camarim 2', 'clue' => 'te7'],
        'portaria' => ['name' => 'Portaria dos artistas', 'clue' => 'te10'],
    ],
    'interrogations' => [
        'bianca' => [
            ['id' => 'b1', 'question' => 'Onde você estava no intervalo?', 'answer' => 'No meu camarim, retocando a maquiagem. Sozinha.'],
            ['id' => 'b2', 'question' => 'Você sabe as falas da Clara?', 'answer' => 'Sou a substituta, é minha obrigação saber.'],
            ['id' => 'b3', 'question' => '(Pressionar) "A estreia vai ser minha" — o que isso significa?', 'answer' => '(Ela engole seco.) Era só... motivação. Todo mundo sonha com o papel principal.', 'requires' => 'te7', 'role' => 'detetive'],
        ],
        'henrique' => [
            ['id' => 'h1', 'question' => 'Onde você estava no intervalo?', 'answer' => 'Na plateia com o produtor, discutindo cortes. Está tudo gravado.'],
            ['id' => 'h2', 'question' => 'Por que vocês discutiram?', 'answer' => 'Clara queria mudar o final. Brigamos, mas eu nunca a machucaria — a peça depende dela.'],
        ],
        'juca' => [
            ['id' => 'j1', 'question' => 'Você viu alguém perto da varanda técnica?', 'answer' => 'Às 20h15 vi alguém de figurino dourado subindo a escada. Achei que era do coro procurando um adereço.', 'unlocks' => 'te8'],
            ['id' => 'j2', 'question' => 'O cabo estava bom antes do ensaio?', 'answer' => 'Conferi às 19h. Estava perfeito, posso jurar.'],
            ['id' => 'j3', 'question' => 'Quem usa figurino dourado?', 'answer' => 'Só o coro das estrelas no primeiro ato. A Bianca é a única do coro que tem camarim próprio.'],
        ],
        'rogerio' => [
            ['id' => 'r1', 'question' => 'Quando você chegou ao teatro?', 'answer' => 'Depois das oito e meia. O trânsito estava horrível.'],
            ['id' => 'r2', 'question' => 'Você ameaçou cortar o patrocínio?', 'answer' => 'Falei de cabeça quente. Já assinei a renovação, pode conferir.'],
        ],
    ],
    'solution' => [
        'culprit' => 'bianca',
        'motive' => 'papel',
        'key_clues' => ['te2', 'te3', 'te6', 'te7', 'te8'],
        'explanation' => 'Bianca Torres queria estrear no papel principal. No intervalo, ainda com o figurino dourado do coro, subiu pela escada técnica que fica atrás do próprio camarim e serrou o cabo até restar um fio, que cederia com a música do segundo ato. Juca viu alguém de dourado subir às 20h15; a serra estava embrulhada em uma luva do figurino e o roteiro dela mostrava a obsessão pelo papel. Henrique estava na plateia (gravado) e Rogério só chegou depois do intervalo.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 1, 'clue' => 1],
];
