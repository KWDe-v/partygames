<?php
/**
 * Caso: O Sumiço da Taça do Centenário.
 *
 * Nível médio: Bolão (mochila) e Seu Zé (apego + uma das chaves) são as iscas e seguem plausíveis.
 * Mas só o vice-presidente tinha o rombo a esconder, a caixa sob medida e a chave da diretoria —
 * e a queda de luz foi no quadro de força ao lado da sala dele.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'taca',
    'title' => 'O Sumiço da Taça do Centenário',
    'difficulty' => 'Médio',
    'intro' => 'O Esporte Clube Vila Nova comemorava cem anos com uma festa no salão social. Quando o presidente foi buscar a Taça do Centenário para a foto oficial, a vitrine da sala de troféus estava aberta — e vazia.',
    'crime' => 'A Taça do Centenário, de prata maciça, sumiu da vitrine da sala de troféus durante a festa.',
    'victim' => 'Esporte Clube Vila Nova',
    'location' => 'Sala de troféus do clube',
    'time' => 'Entre 21h00 e 22h00',
    'suspects' => [
        'ze' => ['name' => 'Seu Zé', 'role' => 'Roupeiro', 'profile' => 'Trabalha no clube há 40 anos e guarda uma das duas chaves da vitrine.', 'motive' => 'Vive dizendo que a taça "é mais dele que de qualquer diretor".', 'alibi' => 'Diz que ficou no salão servindo a feijoada, mas foi algumas vezes à despensa.'],
        'kaique' => ['name' => 'Kaique Lima', 'role' => 'Goleiro reserva', 'profile' => 'Jovem e popular nas redes sociais. Adora fazer vídeos com os troféus.', 'motive' => 'Poderia querer "viralizar" com a taça.', 'alibi' => 'Diz que cantava no karaokê.'],
        'amaral' => ['name' => 'Dr. Amaral', 'role' => 'Vice-presidente', 'profile' => 'Cuida das finanças do clube e organizou a festa.', 'motive' => 'Nenhum motivo aparente: é tido como o maior defensor do clube.', 'alibi' => 'Diz que estava cumprimentando os convidados.'],
        'bolao' => ['name' => 'Bolão', 'role' => 'Mascote (fantasiado)', 'profile' => 'Torcedor que veste a fantasia do mascote há dez anos.', 'motive' => 'Foi visto com uma mochila grande perto da sala de troféus.', 'alibi' => 'Diz que estava animando as crianças.'],
    ],
    'motives' => [
        'desfalque' => 'Cobrir um desfalque no caixa do clube',
        'fama' => 'Fazer sucesso nas redes sociais',
        'apego' => 'Guardar a taça para si por apego',
        'rival' => 'Ajudar o time rival',
    ],
    'clues' => [
        'f1' => ['title' => 'Vitrine aberta', 'text' => 'A vitrine foi aberta com chave, sem arrombamento, entre 21h e 22h.', 'unlock' => ['type' => 'start']],
        'f2' => ['title' => 'As duas chaves', 'text' => 'Existem duas chaves da vitrine: uma com o roupeiro Seu Zé e outra guardada pela diretoria.', 'unlock' => ['type' => 'start'], 'important' => true],
        'f3' => ['title' => 'Queda de luz', 'text' => 'Às 21h30 a luz do corredor dos troféus apagou por três minutos. O quadro de força fica dentro da sala da diretoria, trancada, à qual poucos têm acesso.', 'unlock' => ['type' => 'time', 'at' => 0.3], 'important' => true],
        'f4' => ['title' => 'Pó azul na vitrine', 'text' => 'Na porta da vitrine há marcas de luva com pó azul de giz de sinuca. A única mesa de sinuca do clube fica dentro da sala da diretoria.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'f5' => ['title' => 'A mochila do mascote', 'text' => 'Torcedores comentam que Bolão andava com uma mochila enorme perto da sala de troféus.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'f6' => ['title' => 'Caixa sob medida', 'text' => 'No armário do Dr. Amaral há uma caixa forrada de espuma, recortada no formato exato da taça — vazia.', 'unlock' => ['type' => 'search'], 'important' => true],
        'f7' => ['title' => 'O balancete', 'text' => 'Na contabilidade, um desfalque de R$ 300 mil no caixa do clube, com todas as retiradas autorizadas por Amaral. Auditoria marcada para a próxima semana.', 'unlock' => ['type' => 'search'], 'important' => true],
        'f8' => ['title' => 'A chave da diretoria', 'text' => 'Seu Zé conta que, desde o ano passado, a chave da diretoria — e a própria sala — ficam sob responsabilidade do Dr. Amaral.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'f9' => ['title' => 'Karaokê', 'text' => 'Dezenas de vídeos mostram Kaique cantando no karaokê das 21h20 às 21h45.', 'unlock' => ['type' => 'time', 'at' => 0.6]],
        'f10' => ['title' => 'Conteúdo da mochila', 'text' => 'A mochila de Bolão só tem a cabeça reserva da fantasia e um sanduíche — não há espaço nem peso de uma taça de prata maciça.', 'unlock' => ['type' => 'search']],
        'f11' => ['title' => 'Telefonema no corredor', 'text' => 'Bolão ouviu o Dr. Amaral ao telefone, tenso, falando baixo: "Amanhã de manhã tem que estar tudo resolvido." Não deu para entender o resto.', 'unlock' => ['type' => 'interrogation']],
    ],
    'locations' => [
        'diretoria' => ['name' => 'Sala da diretoria', 'clue' => 'f6'],
        'contabilidade' => ['name' => 'Contabilidade', 'clue' => 'f7'],
        'vestiario' => ['name' => 'Vestiário do mascote', 'clue' => 'f10'],
    ],
    'interrogations' => [
        'ze' => [
            ['id' => 'z1', 'question' => 'Quem fica com as chaves da vitrine?', 'answer' => 'A minha fica comigo. A outra, a da diretoria, é o Dr. Amaral quem guarda desde que ele "organizou" as chaves no ano passado. A sala dele também.', 'unlocks' => 'f8'],
            ['id' => 'z2', 'question' => 'Você saiu do salão?', 'answer' => 'Fui à despensa buscar mais feijão umas duas vezes. Rapidinho, mas saí, confesso.'],
            ['id' => 'z3', 'question' => 'A taça é sua, Seu Zé?', 'answer' => 'De coração, é. Mas eu jamais tiraria ela do lugar dela. Ali é a casa dela.'],
        ],
        'kaique' => [
            ['id' => 'k1', 'question' => 'Onde você estava entre 21h e 22h?', 'answer' => 'No karaokê! Tem vídeo meu cantando em todos os celulares.'],
            ['id' => 'k2', 'question' => 'Você queria filmar a taça?', 'answer' => 'Queria, mas para a foto oficial. Pedi ao Dr. Amaral e ele disse que "não era hora".'],
        ],
        'amaral' => [
            ['id' => 'a1', 'question' => 'Onde você estava entre 21h e 22h?', 'answer' => 'Circulando pela festa, cumprimentando os sócios. Ninguém fica de olho no relógio numa festa dessas.'],
            ['id' => 'a2', 'question' => 'Quem tem acesso à sala da diretoria?', 'answer' => 'Praticamente só eu, hoje em dia. É onde fica a mesa de sinuca e o quadro de força.'],
            ['id' => 'a3', 'question' => '(Pressionar) Explique o desfalque no caixa.', 'answer' => '(Ele afrouxa a gravata.) São empréstimos temporários. Ia devolver tudo antes da auditoria.', 'requires' => 'f7', 'role' => 'detetive'],
        ],
        'bolao' => [
            ['id' => 'bo1', 'question' => 'O que tinha na sua mochila?', 'answer' => 'A cabeça reserva da fantasia e um sanduíche. Pode olhar! Não cabe uma taça ali.'],
            ['id' => 'bo2', 'question' => 'Você ouviu algo no corredor?', 'answer' => 'Ouvi o Dr. Amaral no telefone, baixinho: "Amanhã de manhã tem que estar tudo resolvido." Fiquei sem entender.', 'unlocks' => 'f11'],
        ],
    ],
    'solution' => [
        'culprit' => 'amaral',
        'motive' => 'desfalque',
        'key_clues' => ['f2', 'f3', 'f6', 'f7'],
        'explanation' => 'O Dr. Amaral desviou R$ 300 mil do clube e precisava cobrir o rombo antes da auditoria. Só ele tinha a chave da diretoria e o acesso à sala onde ficam o quadro de força e a mesa de sinuca: às 21h30 provocou a queda de luz no corredor, abriu a vitrine (deixando o pó azul de giz nas marcas de luva) e guardou a taça na caixa sob medida encontrada vazia no armário dele, para entregá-la a um comprador. Seu Zé tem apego e a outra chave, e chegou a sair do salão — mas não tinha rombo a esconder nem a caixa, e jamais venderia a taça. Kaique estava no karaokê e na mochila de Bolão não cabia uma taça de prata maciça.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 2, 'clue' => 1],
];
