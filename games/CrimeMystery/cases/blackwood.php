<?php
/**
 * Caso: O Mistério da Mansão Blackwood.
 * Formato documentado em games/CrimeMystery/src/CaseValidator.php.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'blackwood',
    'title' => 'O Mistério da Mansão Blackwood',
    'difficulty' => 'Médio',
    'intro' => 'Uma noite de tempestade, um jantar de família e um anfitrião que não chegou à sobremesa. Arthur Blackwood, dono da mansão e de uma importadora próspera, reuniu o irmão, o sócio e os empregados mais antigos. Às 22h10 foi encontrado sem vida em seu escritório.',
    'crime' => 'Arthur Blackwood, 62 anos, foi encontrado morto sentado à escrivaninha, com um copo de conhaque pela metade. Não há sinais de luta.',
    'victim' => 'Arthur Blackwood, proprietário da mansão',
    'location' => 'Escritório da Mansão Blackwood',
    'time' => 'Entre 21h00 e 21h40',
    'suspects' => [
        'edmund' => [
            'name' => 'Edmund Blackwood',
            'role' => 'Irmão da vítima',
            'profile' => 'Pianista sem sucesso que vive da mesada do irmão. É o herdeiro da mansão.',
            'motive' => 'Herdaria a mansão e a fortuna de Arthur.',
            'alibi' => 'Diz que tocou piano na sala de música das 21h às 22h.',
        ],
        'helena' => [
            'name' => 'Helena Prado',
            'role' => 'Governanta há vinte anos',
            'profile' => 'Discreta e eficiente. Cuida das chaves, dos remédios da casa e das contas domésticas.',
            'motive' => 'Nenhum motivo aparente: era tratada como parte da família.',
            'alibi' => 'Diz que estava na cozinha preparando a sobremesa.',
        ],
        'victor' => [
            'name' => 'Victor Lang',
            'role' => 'Sócio da vítima',
            'profile' => 'Sócio da importadora há cinco anos. Discutiu com Arthur durante o jantar.',
            'motive' => 'Arthur ameaçava vetar a venda da empresa.',
            'alibi' => 'Diz que estava ao telefone no jardim de inverno.',
        ],
        'tomas' => [
            'name' => 'Tomás Rocha',
            'role' => 'Jardineiro',
            'profile' => 'Cuida dos jardins e da estufa. Conhece todas as plantas da propriedade, inclusive as venenosas.',
            'motive' => 'Arthur ameaçou demiti-lo por causa de um vaso quebrado.',
            'alibi' => 'Diz que passou a noite na estufa.',
        ],
    ],
    'motives' => [
        'heranca' => 'Ficar com a herança',
        'venda' => 'Garantir a venda da empresa',
        'falsificacao' => 'Esconder anos de cheques falsificados',
        'demissao' => 'Vingar-se de uma ameaça de demissão',
    ],
    'clues' => [
        'c1' => ['title' => 'Laudo preliminar', 'text' => 'Arthur morreu por volta das 21h30. O médico sentiu um cheiro amargo no copo de conhaque e suspeita de digitális, um veneno para o coração que também existe na planta dedaleira.', 'unlock' => ['type' => 'start']],
        'c2' => ['title' => 'A dedaleira do jardim', 'text' => 'Há canteiros de dedaleira perto da estufa. Qualquer pessoa da casa poderia ter colhido algumas folhas.', 'unlock' => ['type' => 'start']],
        'c3' => ['title' => 'O conhaque da noite', 'text' => 'A garrafa de conhaque do escritório é reabastecida todas as noites às 21h pela governanta. Só Helena e Arthur têm a chave do armário de bebidas.', 'unlock' => ['type' => 'time', 'at' => 0.3], 'important' => true],
        'c4' => ['title' => 'Análise do resíduo', 'text' => 'No fundo da garrafa há pó branco de comprimidos triturados de digoxina, remédio de farmácia. Folhas de dedaleira deixariam fragmentos verdes — e não há nenhum.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'c5' => ['title' => 'Boatos na cidade', 'text' => 'Comenta-se que Victor Lang devia dinheiro a Arthur e que a venda da empresa resolveria tudo.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'c6' => ['title' => 'Frasco vazio', 'text' => 'Na gaveta de Helena há um frasco vazio de digoxina com receita em nome de Beatriz Blackwood, a falecida esposa de Arthur. Helena guarda os remédios da casa desde a morte da patroa.', 'unlock' => ['type' => 'search'], 'important' => true],
        'c7' => ['title' => 'Anotação no talão de cheques', 'text' => 'No talão de Arthur, uma anotação recente: "H.P. — assinaturas falsas desde 2009? Ligar para o delegado amanhã cedo."', 'unlock' => ['type' => 'search'], 'important' => true],
        'c8' => ['title' => 'A discussão das 20h45', 'text' => 'Edmund ouviu Arthur gritar "Eu confiei em você durante vinte anos!" no corredor, às 20h45. Victor conhecia Arthur havia apenas cinco anos.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'c9' => ['title' => 'Registro da estufa', 'text' => 'O sistema de irrigação registra a porta da estufa aberta das 20h50 às 22h05 e as luzes acesas manualmente às 21h10 — compatível com Tomás trabalhando lá.', 'unlock' => ['type' => 'time', 'at' => 0.6]],
        'c10' => ['title' => 'Conta telefônica', 'text' => 'A extensão do jardim de inverno registra uma ligação de 38 minutos, das 21h02 às 21h40.', 'unlock' => ['type' => 'search']],
        'c11' => ['title' => 'A partitura no piano', 'text' => 'A copeira Clara confirma ter ouvido o piano sem pausas das 21h às 22h e chegou a pedir silêncio a Edmund às 21h20.', 'unlock' => ['type' => 'search']],
    ],
    'locations' => [
        'escritorio' => ['name' => 'Escritório de Arthur', 'clue' => 'c7'],
        'quarto_governanta' => ['name' => 'Quarto da governanta', 'clue' => 'c6'],
        'jardim_inverno' => ['name' => 'Jardim de inverno', 'clue' => 'c10'],
        'sala_musica' => ['name' => 'Sala de música', 'clue' => 'c11'],
    ],
    'interrogations' => [
        'edmund' => [
            ['id' => 'e1', 'question' => 'Onde você estava entre 21h e 21h40?', 'answer' => 'Na sala de música, tocando piano. A Clara até reclamou do barulho.'],
            ['id' => 'e2', 'question' => 'Você ouviu alguma discussão antes do jantar?', 'answer' => 'Ouvi. Às 20h45 Arthur gritou no corredor: "Eu confiei em você durante vinte anos!". Não vi com quem ele falava.', 'unlocks' => 'c8'],
            ['id' => 'e3', 'question' => 'Você precisava do dinheiro da herança?', 'answer' => 'Preciso de dinheiro, não nego. Mas Arthur era a única pessoa que ainda acreditava em mim.'],
            ['id' => 'e4', 'question' => '(Pressionar) Por que escondeu suas dívidas?', 'answer' => 'Devo a um agiota, sim. Ele quer o meu piano, não a vida do meu irmão.', 'role' => 'detetive'],
        ],
        'helena' => [
            ['id' => 'h1', 'question' => 'O que você fazia às 21h?', 'answer' => 'Reabasteci o conhaque do patrão no escritório, como toda noite, e voltei para a cozinha.'],
            ['id' => 'h2', 'question' => 'Quem mais tem a chave do armário de bebidas?', 'answer' => 'Só eu e o senhor Arthur. Sempre foi assim.'],
            ['id' => 'h3', 'question' => 'O que sabe sobre o frasco de digoxina?', 'answer' => 'Era da senhora Beatriz... eu só guardei. Não sei como ficou vazio.', 'requires' => 'c6'],
            ['id' => 'h4', 'question' => '(Pressionar) O que o senhor Arthur descobriu sobre os cheques?', 'answer' => '(Ela hesita.) Quem lhe contou isso? Eu sempre cuidei das contas desta casa... sempre.', 'requires' => 'c7', 'role' => 'detetive'],
        ],
        'victor' => [
            ['id' => 'v1', 'question' => 'Por que vocês discutiram no jantar?', 'answer' => 'Arthur queria vetar a venda da importadora. Discussão de negócios, nada mais.'],
            ['id' => 'v2', 'question' => 'Onde você estava entre 21h e 21h40?', 'answer' => 'Ao telefone com meu advogado, no jardim de inverno. Verifiquem a linha.'],
            ['id' => 'v3', 'question' => 'Você devia dinheiro à vítima?', 'answer' => 'Devia. Paguei na semana passada; o comprovante está no meu e-mail.'],
        ],
        'tomas' => [
            ['id' => 't1', 'question' => 'Onde você passou a noite?', 'answer' => 'Na estufa, cuidando das orquídeas. Só saí depois das dez.'],
            ['id' => 't2', 'question' => 'A dedaleira é perigosa?', 'answer' => 'Muito. Mas para virar veneno é preciso secar e moer as folhas. Ninguém mexeu nos meus canteiros — eu teria visto.'],
            ['id' => 't3', 'question' => 'O patrão ia demiti-lo?', 'answer' => 'Ameaçou, mas depois riu e disse que o vaso era feio mesmo.'],
        ],
    ],
    'solution' => [
        'culprit' => 'helena',
        'motive' => 'falsificacao',
        'key_clues' => ['c3', 'c6', 'c7', 'c8'],
        'explanation' => 'Helena Prado falsificava a assinatura de Arthur em cheques havia anos. Naquela noite, ele a confrontou no corredor ("Eu confiei em você durante vinte anos!" — só Helena estava na casa havia tanto tempo) e anotou que chamaria o delegado pela manhã. Às 21h, ao reabastecer o conhaque — tarefa que só ela fazia, com a única outra chave do armário —, Helena dissolveu na garrafa os comprimidos de digoxina da falecida patroa, guardados por ela. O frasco vazio estava em sua gaveta e o laudo mostra pó de comprimido, não folhas: a dedaleira do jardim era apenas uma pista falsa. Victor estava ao telefone e Edmund ao piano, ambos com testemunhas; Tomás estava na estufa.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 1, 'clue' => 1],
];
