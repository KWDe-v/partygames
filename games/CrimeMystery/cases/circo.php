<?php
/**
 * Caso: A Queda no Circo Estrela.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'circo',
    'title' => 'A Queda no Circo Estrela',
    'difficulty' => 'Médio',
    'intro' => 'No ensaio das 16h do Circo Estrela, o trapezista Dante Moreno errou um salto e caiu na rede de segurança — que se soltou. Ele sobreviveu com o braço quebrado e jura que a rede estava perfeita pela manhã.',
    'crime' => 'Os ganchos da rede de segurança foram soltos de propósito. Foi uma tentativa de ferir gravemente o trapezista.',
    'victim' => 'Dante Moreno, trapezista',
    'location' => 'Picadeiro do Circo Estrela',
    'time' => 'Entre 13h00 e 16h00',
    'suspects' => [
        'igor' => ['name' => 'Igor Vasko', 'role' => 'Domador', 'profile' => 'Cuida dos animais do circo, incluindo o tigre Sultão.', 'motive' => 'Nenhum motivo aparente: diz que Dante é seu amigo.', 'alibi' => 'Diz que só levou o tigre para o ensaio das 14h30.'],
        'nina' => ['name' => 'Nina Bastos', 'role' => 'Trapezista parceira', 'profile' => 'Faz dupla com Dante e sonha com um número solo.', 'motive' => 'Com Dante fora, o número solo seria dela.', 'alibi' => 'Diz que estava na clínica cuidando do tornozelo.'],
        'tonico' => ['name' => 'Tonico', 'role' => 'Palhaço', 'profile' => 'O artista mais antigo do circo. Vê tudo e conta tudo.', 'motive' => 'Dante ri das piadas dele no picadeiro.', 'alibi' => 'Diz que ensaiava uma esquete na arquibancada.'],
        'zuleica' => ['name' => 'Madame Zuleica', 'role' => 'Dona do circo', 'profile' => 'Administra o circo com mão de ferro e confere a rede todos os dias.', 'motive' => 'O circo está endividado.', 'alibi' => 'Diz que conferiu a rede às 13h e foi ao escritório.'],
    ],
    'motives' => [
        'denuncia' => 'Impedir uma denúncia de maus-tratos',
        'solo' => 'Ficar com o número solo',
        'seguro' => 'Receber dinheiro do seguro',
        'ciume' => 'Ciúmes da plateia',
    ],
    'clues' => [
        'c1' => ['title' => 'A rede solta', 'text' => 'Dois ganchos da rede foram desenganchados e os nós amarrados de novo de forma frouxa: a rede cederia com qualquer peso.', 'unlock' => ['type' => 'start']],
        'c2' => ['title' => 'Conferência diária', 'text' => 'Madame Zuleica conferiu a rede às 13h, como faz todos os dias, e assinou a ficha de segurança: estava perfeita.', 'unlock' => ['type' => 'start']],
        'c3' => ['title' => 'Picadeiro fechado', 'text' => 'Entre 13h e 16h o picadeiro ficou trancado. A única pessoa que entrou foi o domador, para o ensaio com o tigre às 14h30.', 'unlock' => ['type' => 'time', 'at' => 0.3], 'important' => true],
        'c4' => ['title' => 'Nós de jaula', 'text' => 'Os nós refeitos são do tipo usado para amarrar os portões das jaulas — não os nós de circo usados pelos montadores.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'c5' => ['title' => 'Rivalidade no trapézio', 'text' => 'Comenta-se que Nina pediu várias vezes um número solo e foi negada por causa de Dante.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'c6' => ['title' => 'O rascunho da denúncia', 'text' => 'No trailer de Dante há um rascunho de denúncia à proteção animal contra Igor, com fotos de animais machucados. Data: ontem.', 'unlock' => ['type' => 'search'], 'important' => true],
        'c7' => ['title' => 'O gancho perdido', 'text' => 'Na caixa de ferramentas do trailer de Igor está um dos ganchos da rede, com marcas recentes de alicate.', 'unlock' => ['type' => 'search'], 'important' => true],
        'c8' => ['title' => 'O que Tonico viu', 'text' => 'Tonico viu Igor mexendo na rede às 14h40 "para passar o tigre por baixo" — mas o tigre nunca passa por ali.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'c9' => ['title' => 'Raio-x', 'text' => 'O recibo da clínica mostra Nina sendo atendida das 13h às 16h30, a 5 km do circo.', 'unlock' => ['type' => 'time', 'at' => 0.6]],
        'c10' => ['title' => 'A apólice', 'text' => 'O seguro do circo não cobre acidentes em ensaio: com Dante ferido, Zuleica só perde dinheiro.', 'unlock' => ['type' => 'search']],
        'c11' => ['title' => 'A conversa de ontem', 'text' => 'Dante contou ao palhaço que "ia acabar com o reinado de crueldade do domador" — sem dar detalhes.', 'unlock' => ['type' => 'interrogation']],
    ],
    'locations' => [
        'trailer_dante' => ['name' => 'Trailer de Dante', 'clue' => 'c6'],
        'trailer_igor' => ['name' => 'Trailer de Igor', 'clue' => 'c7'],
        'escritorio' => ['name' => 'Escritório de Zuleica', 'clue' => 'c10'],
    ],
    'interrogations' => [
        'igor' => [
            ['id' => 'i1', 'question' => 'O que você fez no picadeiro?', 'answer' => 'Levei o Sultão para ensaiar às 14h30. Ficamos uns vinte minutos e saímos.'],
            ['id' => 'i2', 'question' => 'Você mexeu na rede?', 'answer' => 'Precisei afastar um pedaço para o tigre passar. Mas deixei tudo como estava.'],
            ['id' => 'i3', 'question' => '(Pressionar) Dante ia denunciá-lo?', 'answer' => '(Ele cerra os punhos.) Aquele moleque não entende nada de animais. Ninguém ia acreditar nele.', 'requires' => 'c6', 'role' => 'detetive'],
        ],
        'nina' => [
            ['id' => 'n1', 'question' => 'Onde você estava entre 13h e 16h?', 'answer' => 'Na clínica, fazendo raio-x do tornozelo. Tenho o recibo.'],
            ['id' => 'n2', 'question' => 'Você queria o número solo?', 'answer' => 'Quero muito. Mas não assim. Dante é meu parceiro.'],
        ],
        'tonico' => [
            ['id' => 't1', 'question' => 'Você viu alguém perto da rede?', 'answer' => 'Às 14h40 vi o Igor mexendo nos ganchos. Disse que era para o tigre passar por baixo — mas o Sultão nunca passa ali.', 'unlocks' => 'c8'],
            ['id' => 't2', 'question' => 'Dante tinha algum problema com alguém?', 'answer' => 'Ontem ele me disse que "ia acabar com o reinado de crueldade do domador".', 'unlocks' => 'c11'],
            ['id' => 't3', 'question' => 'Você estava onde?', 'answer' => 'Na arquibancada, ensaiando minha esquete nova. Ninguém quer ver, mas eu ensaio mesmo assim.'],
        ],
        'zuleica' => [
            ['id' => 'z1', 'question' => 'A rede estava boa às 13h?', 'answer' => 'Perfeita. Eu mesma puxei cada gancho e assinei a ficha.'],
            ['id' => 'z2', 'question' => 'O circo ganha algo com o acidente?', 'answer' => 'Ganhar? O seguro não cobre ensaio! Vou ter que pagar o hospital do meu bolso.'],
        ],
    ],
    'solution' => [
        'culprit' => 'igor',
        'motive' => 'denuncia',
        'key_clues' => ['c3', 'c6', 'c7', 'c8'],
        'explanation' => 'Igor Vasko maltratava os animais, e Dante preparava uma denúncia com fotos. Entre 13h e 16h o picadeiro ficou fechado, e só Igor entrou, com o tigre, às 14h30. Às 14h40 Tonico o viu mexendo nos ganchos com uma desculpa falsa: ele soltou dois ganchos e refez os nós com o tipo de nó das jaulas, para a rede ceder no salto de Dante. Um dos ganchos, com marcas de alicate, estava na caixa de ferramentas dele. Nina estava na clínica e Zuleica só tinha prejuízo com o acidente.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 1, 'clue' => 1],
];
