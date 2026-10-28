<?php
/**
 * Caso: Sabotagem no Laboratório Aurora.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'aurora',
    'title' => 'Sabotagem no Laboratório Aurora',
    'difficulty' => 'Difícil',
    'intro' => 'O Laboratório Aurora estava a uma semana de anunciar a pesquisa que salvaria a empresa. No fim do expediente, a Dra. Paula Reis foi encontrada desacordada ao lado do computador — e anos de dados haviam sido apagados.',
    'crime' => 'A Dra. Paula Reis foi dopada e os dados da pesquisa foram apagados do computador dela, junto com o backup local. Ela se recupera no hospital e não lembra de nada depois das 18h40.',
    'victim' => 'Dra. Paula Reis, pesquisadora-chefe',
    'location' => 'Laboratório 2 do Instituto Aurora',
    'time' => 'Entre 18h30 e 19h00',
    'suspects' => [
        'caio' => [
            'name' => 'Caio Nunes',
            'role' => 'Estagiário',
            'profile' => 'Ajudava Paula com planilhas. Seu crachá foi usado para abrir o laboratório.',
            'motive' => 'Paula havia criticado seu trabalho na frente da equipe.',
            'alibi' => 'Diz que saiu às 18h e pegou o ônibus.',
        ],
        'marcos' => [
            'name' => 'Marcos Valente',
            'role' => 'Chefe de segurança',
            'profile' => 'Controla câmeras, crachás e o console de segurança do prédio.',
            'motive' => 'Foi advertido por Paula por deixar portas abertas.',
            'alibi' => 'Diz que fazia a ronda no 3º andar.',
        ],
        'renata' => [
            'name' => 'Dra. Renata Lobo',
            'role' => 'Pesquisadora rival',
            'profile' => 'Disputou com Paula a chefia do projeto e perdeu.',
            'motive' => 'Fez uma entrevista na concorrente Helix no mês passado.',
            'alibi' => 'Diz que apresentava um webinar ao vivo.',
        ],
        'sergio' => [
            'name' => 'Sérgio Albuquerque',
            'role' => 'Diretor do instituto',
            'profile' => 'Responsável pelas finanças, que vão mal. Tem acesso administrativo a tudo.',
            'motive' => 'Nenhum motivo aparente: a pesquisa era a salvação do instituto.',
            'alibi' => 'Diz que revisava orçamentos em sua sala até as 19h.',
        ],
    ],
    'motives' => [
        'venda' => 'Vender a pesquisa para a concorrente',
        'promocao' => 'Ficar com a chefia do projeto',
        'inveja' => 'Inveja do sucesso de Paula',
        'vinganca' => 'Vingar-se de uma advertência',
    ],
    'clues' => [
        'a1' => ['title' => 'Dados apagados', 'text' => 'Às 18h52 os dados foram apagados no computador de Paula, com a conta dela. Os paramédicos estimam que ela desmaiou por volta das 18h45.', 'unlock' => ['type' => 'start']],
        'a2' => ['title' => 'Café com sedativo', 'text' => 'O copo de café na mesa de Paula tinha um sedativo forte. Ela sempre trancava o laboratório ao sair.', 'unlock' => ['type' => 'start']],
        'a3' => ['title' => 'Crachá usado', 'text' => 'O registro da porta mostra que o laboratório foi aberto às 18h48 com o crachá do estagiário Caio.', 'unlock' => ['type' => 'time', 'at' => 0.25]],
        'a4' => ['title' => 'Rastro no teclado', 'text' => 'O teclado de Paula tem resíduo de álcool em gel com perfume de lavanda — o mesmo do dispenser exclusivo da sala da diretoria.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'a5' => ['title' => 'A proposta da Helix', 'text' => 'A concorrente Helix ofereceu uma fortuna pela pesquisa. Renata foi vista numa entrevista lá no mês passado.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'a6' => ['title' => 'Câmera desligada', 'text' => 'A câmera do corredor foi desligada às 18h40 pelo console de segurança, que exige senha de administrador. Só Marcos e o diretor Sérgio têm essa senha.', 'unlock' => ['type' => 'search'], 'important' => true],
        'a7' => ['title' => 'Envelope da Helix', 'text' => 'No lixo da sala do diretor, um envelope da Helix com um contrato: "transferência exclusiva dos dados — pagamento após a exclusão do original".', 'unlock' => ['type' => 'search'], 'important' => true],
        'a8' => ['title' => 'O crachá perdido', 'text' => 'Caio comunicou a perda do crachá às 14h. Às 15h, o diretor contou a Marcos que tinha achado o crachá no estacionamento e que o devolveria pessoalmente ao estagiário — mas nunca devolveu.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'a9' => ['title' => 'Webinar ao vivo', 'text' => 'Renata apresentou um webinar das 18h30 às 19h15; a gravação mostra o rosto dela o tempo todo.', 'unlock' => ['type' => 'time', 'at' => 0.55]],
        'a10' => ['title' => 'Quem trouxe o café', 'text' => 'Às 18h30 o diretor passou pelos laboratórios oferecendo café a todos. Paula aceitou um copo.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'a11' => ['title' => 'Livro de ronda', 'text' => 'Marcos registrou a ronda no 3º andar das 18h35 às 19h05, com a assinatura do porteiro do andar a cada 10 minutos.', 'unlock' => ['type' => 'search']],
    ],
    'locations' => [
        'sala_seguranca' => ['name' => 'Sala de segurança', 'clue' => 'a6'],
        'sala_diretor' => ['name' => 'Sala do diretor', 'clue' => 'a7'],
        'portaria' => ['name' => 'Portaria do 3º andar', 'clue' => 'a11'],
    ],
    'interrogations' => [
        'caio' => [
            ['id' => 'c1', 'question' => 'Onde você estava às 18h48?', 'answer' => 'No ônibus! Saí às 18h. Meu crachá sumiu à tarde e eu avisei a segurança.'],
            ['id' => 'c2', 'question' => 'Alguém levou café para a Paula?', 'answer' => 'O diretor Sérgio passou às 18h30 com uma bandeja de cafés. Eu recusei, já estava saindo.', 'unlocks' => 'a10'],
            ['id' => 'c3', 'question' => 'Você conseguiria apagar os dados?', 'answer' => 'Eu só tenho acesso de leitura. Nem sei onde fica o backup.'],
        ],
        'marcos' => [
            ['id' => 'm1', 'question' => 'Onde você estava entre 18h30 e 19h?', 'answer' => 'Na ronda do 3º andar. Está tudo no livro, com a assinatura do porteiro.'],
            ['id' => 'm2', 'question' => 'Aconteceu algo com crachás hoje?', 'answer' => 'O Caio perdeu o dele às 14h. Às 15h o diretor disse que tinha achado o crachá no estacionamento e que ele mesmo devolveria.', 'unlocks' => 'a8'],
            ['id' => 'm3', 'question' => 'Quem pode desligar as câmeras?', 'answer' => 'Só quem tem a senha de administrador do console: eu e o diretor.'],
            ['id' => 'm4', 'question' => '(Pressionar) Foi você quem desligou a câmera?', 'answer' => 'Às 18h40 eu estava no 3º andar, ao lado do porteiro. Confira as assinaturas!', 'role' => 'detetive'],
        ],
        'renata' => [
            ['id' => 'n1', 'question' => 'Onde você estava entre 18h30 e 19h?', 'answer' => 'Apresentando um webinar ao vivo, das 18h30 às 19h15. Há a gravação.'],
            ['id' => 'n2', 'question' => 'Você negociou com a Helix?', 'answer' => 'Fiz uma entrevista e recusei a proposta. Não troco a Aurora por dinheiro.'],
            ['id' => 'n3', 'question' => 'O que acha da pesquisa de Paula?', 'answer' => 'Brilhante. Queria que fosse minha, confesso — mas não desse jeito.'],
        ],
        'sergio' => [
            ['id' => 's1', 'question' => 'Onde você estava entre 18h30 e 19h?', 'answer' => 'Na minha sala, revisando orçamentos. Saí às 19h em ponto.'],
            ['id' => 's2', 'question' => 'Você ofereceu café a alguém?', 'answer' => 'A todos. É um costume meu. Agora isso é crime?'],
            ['id' => 's3', 'question' => 'O instituto passa por dificuldades?', 'answer' => 'Como toda empresa de pesquisa. Nada que uma boa parceria não resolva.'],
            ['id' => 's4', 'question' => '(Pressionar) O que é este contrato da Helix?', 'answer' => '(Longo silêncio.) Isso é confidencial. Não digo mais nada sem o meu advogado.', 'requires' => 'a7', 'role' => 'detetive'],
        ],
    ],
    'solution' => [
        'culprit' => 'sergio',
        'motive' => 'venda',
        'key_clues' => ['a6', 'a7', 'a8', 'a10'],
        'explanation' => 'Sérgio Albuquerque fechou um acordo secreto com a Helix: receberia o pagamento depois de entregar uma cópia dos dados e destruir o original. Às 18h30 ofereceu café com sedativo a Paula; às 18h40 desligou a câmera do corredor pelo console, cuja senha só ele e Marcos tinham — e Marcos estava na ronda, com testemunha. Entrou às 18h48 com o crachá de Caio — que ele dizia ter achado no estacionamento e nunca devolveu — e apagou os dados às 18h52 com a sessão aberta de Paula, deixando no teclado o perfume do álcool em gel da diretoria. Renata estava no webinar e Caio já tinha ido embora.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 1, 'clue' => 1],
];
