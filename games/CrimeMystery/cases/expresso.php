<?php
/**
 * Caso: O Roubo no Expresso Estrela do Sul.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'expresso',
    'title' => 'O Roubo no Expresso Estrela do Sul',
    'difficulty' => 'Fácil',
    'intro' => 'O trem noturno de luxo cruza a serra rumo ao litoral. A bordo, o colecionador Otávio Lacerda transporta o diamante "Estrela do Sul" para um leilão. Quando o trem chega à estação das 2h30, o cofre do vagão de bagagens está aberto — e vazio.',
    'crime' => 'O diamante Estrela do Sul desapareceu do cofre do vagão de bagagens. Ninguém ficou ferido.',
    'victim' => 'Dr. Otávio Lacerda, colecionador de joias',
    'location' => 'Vagão de bagagens do Expresso Estrela do Sul',
    'time' => 'Entre 1h00 e 2h00',
    'suspects' => [
        'raul' => [
            'name' => 'Raul Antunes',
            'role' => 'Condutor do trem',
            'profile' => 'Trabalha na companhia há 30 anos e tem as chaves de todos os vagões.',
            'motive' => 'Está prestes a se aposentar com uma pensão pequena.',
            'alibi' => 'Diz que estava na cabine da locomotiva com o maquinista.',
        ],
        'livia' => [
            'name' => 'Lívia Martins',
            'role' => 'Violinista do trio do vagão-restaurante',
            'profile' => 'Tocou para os passageiros até 0h30. Reservada, evitou o Dr. Otávio durante todo o jantar.',
            'motive' => 'Nenhum motivo aparente.',
            'alibi' => 'Diz que dormia em sua cabine.',
        ],
        'bruno' => [
            'name' => 'Bruno Seixas',
            'role' => 'Assistente do colecionador',
            'profile' => 'Organiza as viagens do Dr. Otávio e conhece a rotina do cofre.',
            'motive' => 'Tem dívidas de jogo.',
            'alibi' => 'Diz que jogava cartas no vagão-restaurante.',
        ],
        'icaro' => [
            'name' => 'Ícaro Bastos',
            'role' => 'Joalheiro aposentado',
            'profile' => 'Viaja com uma maleta de ferramentas de joalheria. Elogiou demais o diamante no jantar.',
            'motive' => 'Saberia revender a pedra no exterior.',
            'alibi' => 'Diz que estava acordado na própria cabine.',
        ],
    ],
    'motives' => [
        'dividas' => 'Pagar dívidas de jogo',
        'vinganca' => 'Vingar a falência da família',
        'revenda' => 'Revender a pedra no exterior',
        'aposentadoria' => 'Garantir a aposentadoria',
    ],
    'clues' => [
        'x1' => ['title' => 'O cofre', 'text' => 'O cofre foi aberto com a senha correta, sem arrombamento, entre 1h00 e 2h00.', 'unlock' => ['type' => 'start']],
        'x2' => ['title' => 'O túnel da serra', 'text' => 'Às 1h25 o trem atravessou o túnel da serra e as luzes de todos os vagões ficaram apagadas por quatro minutos.', 'unlock' => ['type' => 'start'], 'important' => true],
        'x3' => ['title' => 'A senha não era segredo', 'text' => 'No jantar, às 23h, o Dr. Otávio contou a todos que a senha do cofre era a data de aniversário da filha — e disse a data.', 'unlock' => ['type' => 'time', 'at' => 0.3]],
        'x4' => ['title' => 'Resíduo no teclado', 'text' => 'O teclado do cofre tem um pó amarelado e pegajoso: breu, a resina passada nas cordas de arcos de violino.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'x5' => ['title' => 'Dívidas de jogo', 'text' => 'Todos no trem comentam que Bruno deve dinheiro a apostadores.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'x6' => ['title' => 'Um estojo fora do lugar', 'text' => 'Um estojo de violino do trio está no vagão de bagagens. Ele tem fundo falso — vazio, mas o veludo guarda a marca de uma pedra grande.', 'unlock' => ['type' => 'search'], 'important' => true],
        'x7' => ['title' => 'Recorte de jornal', 'text' => 'Na cabine de Lívia há um recorte antigo: "Joalheria Martins vai à falência após processo movido por Otávio Lacerda".', 'unlock' => ['type' => 'search'], 'important' => true],
        'x8' => ['title' => 'Visto no corredor', 'text' => 'Ícaro viu alguém carregando um estojo comprido rumo ao vagão de bagagens logo depois que as luzes voltaram, às 1h29.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'x9' => ['title' => 'O carteado', 'text' => 'Três passageiros confirmam que Bruno jogou cartas com eles no vagão-restaurante até 2h15, sem sair da mesa.', 'unlock' => ['type' => 'time', 'at' => 0.6]],
        'x10' => ['title' => 'Diário de bordo', 'text' => 'O diário da locomotiva, assinado pelo maquinista, registra Raul na cabine das 0h50 às 2h10.', 'unlock' => ['type' => 'search']],
        'x11' => ['title' => 'Maleta lacrada', 'text' => 'A maleta de ferramentas de Ícaro tem o lacre da alfândega intacto desde o embarque.', 'unlock' => ['type' => 'search']],
    ],
    'locations' => [
        'vagao_bagagens' => ['name' => 'Vagão de bagagens', 'clue' => 'x6'],
        'cabine_livia' => ['name' => 'Cabine de Lívia', 'clue' => 'x7'],
        'locomotiva' => ['name' => 'Cabine da locomotiva', 'clue' => 'x10'],
        'cabine_icaro' => ['name' => 'Cabine de Ícaro', 'clue' => 'x11'],
    ],
    'interrogations' => [
        'raul' => [
            ['id' => 'r1', 'question' => 'Onde você estava entre 1h e 2h?', 'answer' => 'Na locomotiva com o maquinista, conferindo os sinais da serra.'],
            ['id' => 'r2', 'question' => 'Quem tem acesso ao vagão de bagagens?', 'answer' => 'À noite a porta fica destrancada para os músicos guardarem os instrumentos. Regra antiga da companhia.'],
            ['id' => 'r3', 'question' => '(Pressionar) Por que a porta não estava trancada hoje?', 'answer' => 'O trio pediu para guardar os estojos lá. A violinista insistiu muito.', 'role' => 'detetive'],
        ],
        'livia' => [
            ['id' => 'l1', 'question' => 'Onde você estava durante o túnel?', 'answer' => 'Na minha cabine, dormindo. Tocamos até 0h30 e eu estava exausta.'],
            ['id' => 'l2', 'question' => 'Por que seu estojo está no vagão de bagagens?', 'answer' => 'Guardei lá depois da apresentação. É mais seguro... não é?', 'requires' => 'x6'],
            ['id' => 'l3', 'question' => 'Você conhece o Dr. Otávio?', 'answer' => 'Só de nome. Muita gente conhece.'],
            ['id' => 'l4', 'question' => '(Pressionar) "Joalheria Martins" lhe diz alguma coisa?', 'answer' => '(Ela empalidece.) Meu pai morreu sem um centavo por causa daquele homem. Isso não prova nada.', 'requires' => 'x7', 'role' => 'detetive'],
        ],
        'bruno' => [
            ['id' => 'b1', 'question' => 'Onde você estava entre 1h e 2h?', 'answer' => 'Jogando cartas no restaurante até depois das duas. Pergunte a qualquer um da mesa.'],
            ['id' => 'b2', 'question' => 'É verdade que você tem dívidas?', 'answer' => 'É. Por isso mesmo eu não arriscaria o único emprego que tenho.'],
            ['id' => 'b3', 'question' => 'Você sabia a senha do cofre?', 'answer' => 'Depois do jantar, todo mundo sabia. O doutor adora contar essa história.'],
        ],
        'icaro' => [
            ['id' => 'i1', 'question' => 'Onde você estava entre 1h e 2h?', 'answer' => 'Na minha cabine. Não consegui dormir com o barulho do túnel.'],
            ['id' => 'i2', 'question' => 'Você viu algo estranho esta noite?', 'answer' => 'Logo que a luz voltou, vi alguém passar com um estojo comprido, rumo aos fundos do trem.', 'unlocks' => 'x8'],
            ['id' => 'i3', 'question' => 'Um joalheiro num trem com um diamante... coincidência?', 'answer' => 'Infeliz coincidência. Minhas ferramentas estão lacradas pela alfândega desde o embarque.'],
        ],
    ],
    'solution' => [
        'culprit' => 'livia',
        'motive' => 'vinganca',
        'key_clues' => ['x2', 'x6', 'x7', 'x8'],
        'explanation' => 'Lívia Martins é filha do dono da Joalheria Martins, levada à falência por um processo de Otávio Lacerda. A senha foi revelada pelo próprio colecionador no jantar. Durante os quatro minutos de escuridão do túnel, Lívia abriu o cofre e escondeu o diamante no fundo falso do estojo de violino; Ícaro a viu carregando o estojo comprido logo depois. O breu no teclado vem do arco de violino. Raul estava na locomotiva, Bruno no carteado e a maleta de Ícaro continuava lacrada.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 1, 'clue' => 1],
];
