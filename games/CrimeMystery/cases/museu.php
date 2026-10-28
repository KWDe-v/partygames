<?php
/**
 * Caso: O Roubo no Museu Municipal.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'museu',
    'title' => 'O Roubo no Museu Municipal',
    'difficulty' => 'Fácil',
    'intro' => 'Na véspera da grande exposição, o quadro mais valioso do Museu Municipal, "A Dama de Azul", desapareceu da moldura durante a noite. O alarme não tocou e nenhuma janela foi quebrada.',
    'crime' => 'A tela "A Dama de Azul" foi cortada e retirada da moldura. O quadro está avaliado em milhões.',
    'victim' => 'Museu Municipal (quadro "A Dama de Azul")',
    'location' => 'Ala de pintura clássica do Museu Municipal',
    'time' => 'Entre 23h00 e 23h40',
    'suspects' => [
        'felix' => ['name' => 'Félix Arantes', 'role' => 'Restaurador', 'profile' => 'Restaurava a moldura do quadro havia duas semanas. Trabalha até tarde com frequência.', 'motive' => 'Reclama que ganha pouco pelo trabalho que faz.', 'alibi' => 'Diz que passou rapidamente para buscar óculos esquecidos.'],
        'marta' => ['name' => 'Dra. Marta Leme', 'role' => 'Curadora', 'profile' => 'Organizou a exposição e negocia o seguro das obras.', 'motive' => 'O seguro do quadro foi aumentado no mês passado.', 'alibi' => 'Diz que estava em casa desde as 20h.'],
        'osvaldo' => ['name' => 'Osvaldo Reis', 'role' => 'Vigia noturno', 'profile' => 'Trabalha no museu há 15 anos. Conhece todos os códigos do prédio.', 'motive' => 'Está com o salário atrasado.', 'alibi' => 'Diz que não saiu da guarita a noite toda.'],
        'sonia' => ['name' => 'Sônia Prado', 'role' => 'Faxineira', 'profile' => 'Limpa a ala de pintura todas as noites.', 'motive' => 'Foi repreendida pela curadora na semana passada.', 'alibi' => 'Diz que foi embora às 22h.'],
    ],
    'motives' => [
        'venda' => 'Vender a tela a um colecionador estrangeiro',
        'seguro' => 'Fraudar o seguro',
        'salario' => 'Compensar salários atrasados',
        'vinganca' => 'Vingar-se da curadora',
    ],
    'clues' => [
        'm1' => ['title' => 'A moldura vazia', 'text' => 'A tela foi cortada rente à moldura, com um corte reto e preciso, sem rasgos.', 'unlock' => ['type' => 'start']],
        'm2' => ['title' => 'Alarme desligado', 'text' => 'O alarme da ala foi desligado às 23h12 com um código válido. A câmera da ala estava em manutenção desde a tarde.', 'unlock' => ['type' => 'start'], 'important' => true],
        'm3' => ['title' => 'O pedido de manutenção', 'text' => 'A manutenção da câmera da ala foi pedida ontem às 15h pelo restaurador Félix, alegando "reflexo no vidro".', 'unlock' => ['type' => 'time', 'at' => 0.3], 'important' => true],
        'm4' => ['title' => 'Fibras e bisturi', 'text' => 'Na moldura há fibras de luvas de algodão branco, de restauração. O corte foi feito com bisturi de restauro, não com faca comum.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'm5' => ['title' => 'A curadora e o seguro', 'text' => 'Dizem que a curadora aumentou o seguro do quadro no mês passado.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'm6' => ['title' => 'Tubo de transporte', 'text' => 'No ateliê de restauro há um tubo de papelão para telas enroladas, vazio, com etiqueta de uma galeria estrangeira endereçada a Félix.', 'unlock' => ['type' => 'search'], 'important' => true],
        'm7' => ['title' => 'Livro da portaria', 'text' => 'Félix assinou a entrada às 22h50 ("buscar óculos") e a saída às 23h40 — quase uma hora no prédio.', 'unlock' => ['type' => 'search'], 'important' => true],
        'm8' => ['title' => 'O código do alarme', 'text' => 'Na semana passada, Félix pediu ao vigia o código do alarme "para trabalhar até tarde", e Osvaldo anotou num papel.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'm9' => ['title' => 'Imagens da guarita', 'text' => 'As câmeras externas mostram Osvaldo na guarita das 22h30 às 0h30, sem sair.', 'unlock' => ['type' => 'time', 'at' => 0.6]],
        'm10' => ['title' => 'A apólice', 'text' => 'O seguro foi aumentado por exigência da seguradora após nova avaliação, e o valor é pago ao museu, não à curadora.', 'unlock' => ['type' => 'search']],
    ],
    'locations' => [
        'atelie' => ['name' => 'Ateliê de restauro', 'clue' => 'm6'],
        'portaria' => ['name' => 'Portaria', 'clue' => 'm7'],
        'sala_curadoria' => ['name' => 'Sala da curadoria', 'clue' => 'm10'],
    ],
    'interrogations' => [
        'felix' => [
            ['id' => 'f1', 'question' => 'O que você fazia no museu às 23h?', 'answer' => 'Vim buscar meus óculos no ateliê. Coisa rápida, juro.'],
            ['id' => 'f2', 'question' => 'Por que pediu manutenção na câmera?', 'answer' => 'O reflexo atrapalhava as fotos do restauro. Pedido técnico, só isso.'],
            ['id' => 'f3', 'question' => '(Pressionar) Quem é o destinatário do tubo de transporte?', 'answer' => '(Ele desvia o olhar.) Uma galeria me encomenda molduras... nada a ver com a Dama.', 'requires' => 'm6', 'role' => 'detetive'],
        ],
        'marta' => [
            ['id' => 'ma1', 'question' => 'Onde você estava ontem à noite?', 'answer' => 'Em casa, preparando o discurso da abertura. Meu marido confirma.'],
            ['id' => 'ma2', 'question' => 'Por que o seguro aumentou?', 'answer' => 'A seguradora exigiu depois da nova avaliação. O dinheiro vai para o museu, não para mim.'],
        ],
        'osvaldo' => [
            ['id' => 'o1', 'question' => 'Alguém mais sabe o código do alarme?', 'answer' => 'Semana passada o Félix pediu o código para trabalhar até tarde. Eu anotei num papel pra ele.', 'unlocks' => 'm8'],
            ['id' => 'o2', 'question' => 'Você saiu da guarita?', 'answer' => 'Nem para ir ao banheiro. As câmeras de fora mostram.'],
            ['id' => 'o3', 'question' => 'Viu alguém entrar à noite?', 'answer' => 'Só o restaurador, perto das onze. Disse que era rápido.'],
        ],
        'sonia' => [
            ['id' => 's1', 'question' => 'A que horas você saiu?', 'answer' => 'Às 22h, como sempre. Meu ponto eletrônico mostra.'],
            ['id' => 's2', 'question' => 'Notou algo diferente na ala?', 'answer' => 'O ateliê estava cheio de tubos de papelão. Achei que era coisa da exposição.'],
        ],
    ],
    'solution' => [
        'culprit' => 'felix',
        'motive' => 'venda',
        'key_clues' => ['m2', 'm3', 'm6', 'm7', 'm8'],
        'explanation' => 'Félix Arantes planejou o roubo para vender a tela a um colecionador estrangeiro. Pediu a manutenção da câmera da ala na tarde anterior e conseguiu o código do alarme com o vigia dias antes. Às 22h50 entrou "para buscar os óculos", desligou o alarme às 23h12, cortou a tela com o próprio bisturi de restauro e a enrolou no tubo de transporte da galeria estrangeira. O seguro não beneficiava a curadora, Osvaldo nunca saiu da guarita e Sônia já tinha ido embora.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 1, 'clue' => 1],
];
