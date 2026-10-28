<?php
/**
 * Caso: O Sumiço da Taça do Centenário.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'taca',
    'title' => 'O Sumiço da Taça do Centenário',
    'difficulty' => 'Fácil',
    'intro' => 'O Esporte Clube Vila Nova comemorava cem anos com uma festa no salão social. Quando o presidente foi buscar a Taça do Centenário para a foto oficial, a vitrine da sala de troféus estava aberta — e vazia.',
    'crime' => 'A Taça do Centenário, de prata maciça, sumiu da vitrine da sala de troféus durante a festa.',
    'victim' => 'Esporte Clube Vila Nova',
    'location' => 'Sala de troféus do clube',
    'time' => 'Entre 21h00 e 22h00',
    'suspects' => [
        'ze' => ['name' => 'Seu Zé', 'role' => 'Roupeiro', 'profile' => 'Trabalha no clube há 40 anos e guarda uma das chaves da vitrine.', 'motive' => 'Vive dizendo que a taça "é mais dele que de qualquer diretor".', 'alibi' => 'Diz que ficou no salão servindo a feijoada.'],
        'kaique' => ['name' => 'Kaique Lima', 'role' => 'Goleiro reserva', 'profile' => 'Jovem e popular nas redes sociais. Adora fazer vídeos com os troféus.', 'motive' => 'Poderia querer "viralizar" com a taça.', 'alibi' => 'Diz que cantava no karaokê.'],
        'amaral' => ['name' => 'Dr. Amaral', 'role' => 'Vice-presidente', 'profile' => 'Cuida das finanças do clube e organizou a festa.', 'motive' => 'Nenhum motivo aparente: é o maior defensor do clube.', 'alibi' => 'Diz que estava cumprimentando os convidados.'],
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
        'f3' => ['title' => 'Queda de luz', 'text' => 'Às 21h30 a luz do corredor apagou por três minutos. O quadro de força fica ao lado da sala da diretoria.', 'unlock' => ['type' => 'time', 'at' => 0.3]],
        'f4' => ['title' => 'Pó azul na vitrine', 'text' => 'Na porta da vitrine há marcas de luva com pó azul de giz de sinuca. A mesa de sinuca do clube fica na sala da diretoria.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'f5' => ['title' => 'A mochila do mascote', 'text' => 'Torcedores comentam que Bolão andava com uma mochila enorme perto da sala de troféus.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'f6' => ['title' => 'Caixa sob medida', 'text' => 'No armário do Dr. Amaral há uma caixa forrada de espuma, recortada no formato exato da taça — vazia.', 'unlock' => ['type' => 'search'], 'important' => true],
        'f7' => ['title' => 'O balancete', 'text' => 'Na contabilidade, um desfalque de R$ 300 mil no caixa do clube, com todas as retiradas autorizadas por Amaral. Auditoria marcada para a próxima semana.', 'unlock' => ['type' => 'search'], 'important' => true],
        'f8' => ['title' => 'A chave da diretoria', 'text' => 'Seu Zé conta que, desde o ano passado, a chave da diretoria fica com o Dr. Amaral. A dele passou a festa inteira pendurada no pescoço.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'f9' => ['title' => 'Karaokê', 'text' => 'Dezenas de vídeos mostram Kaique cantando no karaokê das 21h20 às 21h45.', 'unlock' => ['type' => 'time', 'at' => 0.6]],
        'f10' => ['title' => 'Conteúdo da mochila', 'text' => 'A mochila de Bolão só tem a cabeça reserva da fantasia e um sanduíche.', 'unlock' => ['type' => 'search']],
        'f11' => ['title' => 'Telefonema ouvido', 'text' => 'Bolão ouviu Amaral ao telefone no corredor: "Amanhã cedo eu levo a peça. O dinheiro tem que estar pronto."', 'unlock' => ['type' => 'interrogation'], 'important' => true],
    ],
    'locations' => [
        'diretoria' => ['name' => 'Sala da diretoria', 'clue' => 'f6'],
        'contabilidade' => ['name' => 'Contabilidade', 'clue' => 'f7'],
        'vestiario' => ['name' => 'Vestiário do mascote', 'clue' => 'f10'],
    ],
    'interrogations' => [
        'ze' => [
            ['id' => 'z1', 'question' => 'Onde está a sua chave da vitrine?', 'answer' => 'Aqui no meu pescoço, a festa toda. A outra chave fica com o Dr. Amaral desde que ele "organizou" as chaves no ano passado.', 'unlocks' => 'f8'],
            ['id' => 'z2', 'question' => 'Você saiu do salão?', 'answer' => 'Nem um minuto. Servi feijoada para duzentas pessoas.'],
            ['id' => 'z3', 'question' => 'A taça é sua, Seu Zé?', 'answer' => 'De coração, é. Mas eu jamais tiraria ela do lugar dela.'],
        ],
        'kaique' => [
            ['id' => 'k1', 'question' => 'Onde você estava entre 21h e 22h?', 'answer' => 'No karaokê! Tem vídeo meu cantando em todos os celulares.'],
            ['id' => 'k2', 'question' => 'Você queria filmar a taça?', 'answer' => 'Queria, mas para a foto oficial. Pedi ao Dr. Amaral e ele disse que "não era hora".'],
        ],
        'amaral' => [
            ['id' => 'a1', 'question' => 'Onde você estava entre 21h e 22h?', 'answer' => 'Circulando pela festa, cumprimentando os sócios.'],
            ['id' => 'a2', 'question' => 'Onde está a chave da diretoria?', 'answer' => 'Fica guardada na diretoria. Qualquer diretor pode pegar.'],
            ['id' => 'a3', 'question' => '(Pressionar) Explique o desfalque no caixa.', 'answer' => '(Ele afrouxa a gravata.) São empréstimos temporários. Ia devolver tudo antes da auditoria.', 'requires' => 'f7', 'role' => 'detetive'],
        ],
        'bolao' => [
            ['id' => 'bo1', 'question' => 'O que tinha na sua mochila?', 'answer' => 'A cabeça reserva da fantasia e um sanduíche. Pode olhar!'],
            ['id' => 'bo2', 'question' => 'Você ouviu algo estranho no corredor?', 'answer' => 'Ouvi o Dr. Amaral no telefone: "Amanhã cedo eu levo a peça. O dinheiro tem que estar pronto."', 'unlocks' => 'f11'],
        ],
    ],
    'solution' => [
        'culprit' => 'amaral',
        'motive' => 'desfalque',
        'key_clues' => ['f2', 'f6', 'f7', 'f8', 'f11'],
        'explanation' => 'O Dr. Amaral desviou R$ 300 mil do clube e precisava cobrir o rombo antes da auditoria. Com a chave da diretoria — que ficava com ele —, aproveitou a queda de luz das 21h30, provocada no quadro de força ao lado da sala dele, abriu a vitrine e escondeu a taça para entregá-la a um comprador ("amanhã cedo eu levo a peça"). A caixa sob medida vazia estava em seu armário. Seu Zé nunca tirou a chave do pescoço, Kaique estava no karaokê e a mochila de Bolão só tinha a fantasia.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 1, 'clue' => 1],
];
