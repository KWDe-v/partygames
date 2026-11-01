<?php
/**
 * Caso: O Roubo no Museu Municipal.
 *
 * Nível médio: o suspeito óbvio (Félix, o restaurador) foi incriminado — toda a evidência física
 * aponta para ele. A culpada real é a curadora, que montou a cena. Só se chega nela juntando o
 * horário de saída de Félix, o código mestre usado no alarme e o que está na sala da curadoria.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'museu',
    'title' => 'O Roubo no Museu Municipal',
    'difficulty' => 'Médio',
    'intro' => 'Na véspera da grande exposição, o quadro mais valioso do Museu Municipal, "A Dama de Azul", desapareceu da moldura durante a noite. O alarme não tocou e nenhuma janela foi quebrada. Tudo aponta para o restaurador — talvez bem demais.',
    'crime' => 'A tela "A Dama de Azul" foi cortada e retirada da moldura. O quadro está avaliado em milhões.',
    'victim' => 'Museu Municipal (quadro "A Dama de Azul")',
    'location' => 'Ala de pintura clássica do Museu Municipal',
    'time' => 'Entre 23h00 e 23h40',
    'suspects' => [
        'felix' => ['name' => 'Félix Arantes', 'role' => 'Restaurador', 'profile' => 'Restaurava a moldura do quadro havia duas semanas. Trabalha até tarde com frequência e esteve no prédio naquela noite.', 'motive' => 'Reclama que ganha pouco pelo trabalho que faz, e uma galeria estrangeira lhe encomenda peças.', 'alibi' => 'Diz que entrou só para buscar os óculos e saiu rápido, antes das onze e pouco.'],
        'marta' => ['name' => 'Dra. Marta Leme', 'role' => 'Curadora', 'profile' => 'Organizou a exposição, negocia o seguro das obras e tem código mestre de todo o prédio.', 'motive' => 'Está endividada e reavaliou o seguro do quadro no mês passado.', 'alibi' => 'Diz que estava em casa desde as 20h preparando o discurso de abertura.'],
        'osvaldo' => ['name' => 'Osvaldo Reis', 'role' => 'Vigia noturno', 'profile' => 'Trabalha no museu há 15 anos. Conhece os códigos do prédio e emprestou um deles recentemente.', 'motive' => 'Está com o salário atrasado há dois meses.', 'alibi' => 'Diz que não saiu da guarita a noite toda.'],
        'sonia' => ['name' => 'Sônia Prado', 'role' => 'Faxineira', 'profile' => 'Limpa a ala de pintura todas as noites e circula pelo ateliê e pela curadoria.', 'motive' => 'Foi repreendida pela curadora na semana passada.', 'alibi' => 'Diz que foi embora às 22h, mas ninguém confirma o horário.'],
    ],
    'motives' => [
        'venda' => 'Vender a tela a um colecionador estrangeiro',
        'seguro' => 'Forjar o roubo para embolsar o seguro',
        'salario' => 'Compensar salários atrasados',
        'vinganca' => 'Vingar-se da curadora',
    ],
    'clues' => [
        // Aparência: tudo aponta para Félix.
        'm1' => ['title' => 'A moldura vazia', 'text' => 'A tela foi cortada rente à moldura, com um corte reto e preciso, feito com bisturi de restauro — não com faca comum.', 'unlock' => ['type' => 'start']],
        'm2' => ['title' => 'Alarme desligado', 'text' => 'O alarme da ala foi desligado às 23h12 com um código válido. A câmera da ala estava em manutenção desde a tarde.', 'unlock' => ['type' => 'start'], 'important' => true],
        'm4' => ['title' => 'Fibras e bisturi', 'text' => 'Na moldura há fibras de luvas de algodão branco de restauração. Mas as luvas são do estoque comum do ateliê: qualquer pessoa com acesso à sala pegaria um par.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'm6' => ['title' => 'Tubo de transporte', 'text' => 'No ateliê há um tubo de papelão para telas enroladas, vazio, com etiqueta de uma galeria estrangeira endereçada a Félix.', 'unlock' => ['type' => 'search']],
        'm8' => ['title' => 'O código emprestado', 'text' => 'Semana passada, Félix pediu ao vigia o código comum do alarme "para trabalhar até tarde". Osvaldo anotou num papel para ele.', 'unlock' => ['type' => 'interrogation']],
        // Quebra da aparência: o que realmente aconteceu.
        'm3' => ['title' => 'O pedido de manutenção', 'text' => 'A manutenção da câmera foi pedida ontem às 15h em nome de Félix — mas a ligação saiu de um ramal interno da curadoria, não do ateliê.', 'unlock' => ['type' => 'time', 'at' => 0.3], 'important' => true],
        'm7' => ['title' => 'Livro da portaria', 'text' => 'Félix assinou a entrada às 22h50 ("buscar óculos") e a SAÍDA às 23h05 — sete minutos ANTES de o alarme ser desligado às 23h12.', 'unlock' => ['type' => 'search'], 'important' => true],
        'm10' => ['title' => 'Qual código foi usado', 'text' => 'O registro eletrônico mostra que o código digitado às 23h12 não foi o código comum (o que Félix tinha): foi o código MESTRE da curadoria, que só a Dra. Marta possui.', 'unlock' => ['type' => 'time', 'at' => 0.6], 'important' => true],
        'm9' => ['title' => 'A pasta da curadoria', 'text' => 'Na sala da curadoria há a cópia de um e-mail a um intermediário combinando "a entrega" para depois da abertura, e extratos mostrando dívidas vencidas da Dra. Marta.', 'unlock' => ['type' => 'search'], 'important' => true],
        'm5' => ['title' => 'Boato do seguro', 'text' => 'Nos corredores comenta-se que a curadora reavaliou o seguro do quadro para um valor muito acima do habitual, pouco antes da exposição.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
    ],
    'locations' => [
        'atelie' => ['name' => 'Ateliê de restauro', 'clue' => 'm6'],
        'portaria' => ['name' => 'Portaria', 'clue' => 'm7'],
        'sala_curadoria' => ['name' => 'Sala da curadoria', 'clue' => 'm9'],
    ],
    'interrogations' => [
        'felix' => [
            ['id' => 'f1', 'question' => 'O que você fazia no museu às 23h?', 'answer' => 'Vim buscar meus óculos no ateliê e fui embora antes das onze e dez. Assinei a saída na portaria, podem conferir.'],
            ['id' => 'f2', 'question' => 'Por que seu nome está no pedido de manutenção da câmera?', 'answer' => 'Eu NÃO pedi manutenção nenhuma. Fiquei sabendo hoje. Alguém usou meu nome.'],
            ['id' => 'f3', 'question' => '(Pressionar) E o tubo da galeria estrangeira endereçado a você?', 'answer' => '(Nervoso.) Eles me encomendam molduras, não telas roubadas. Esse tubo chegou vazio, nem sei quem deixou no ateliê.', 'requires' => 'm6', 'role' => 'detetive'],
        ],
        'marta' => [
            ['id' => 'ma1', 'question' => 'Onde você estava ontem à noite?', 'answer' => 'Em casa, preparando o discurso. Morando sozinha, confesso que não tenho quem confirme.'],
            ['id' => 'ma2', 'question' => 'Por que o seguro do quadro foi reavaliado?', 'answer' => 'Procedimento normal antes de uma exposição grande. Não vejo problema nisso.'],
            ['id' => 'ma3', 'question' => '(Pressionar) Só o seu código mestre foi usado no alarme às 23h12. Explique.', 'answer' => '(Pausa longa.) Deve haver... um erro no sistema. Qualquer um pode ter clonado. Eu não estava aqui.', 'requires' => 'm10', 'role' => 'detetive'],
        ],
        'osvaldo' => [
            ['id' => 'o1', 'question' => 'Quem mais tinha o código do alarme?', 'answer' => 'Semana passada o Félix pediu o código comum para trabalhar até tarde. Anotei num papel pra ele.', 'unlocks' => 'm8'],
            ['id' => 'o2', 'question' => 'Você saiu da guarita?', 'answer' => 'Nem para ir ao banheiro. As câmeras externas mostram, pode checar.'],
            ['id' => 'o3', 'question' => 'Viu alguém depois das onze?', 'answer' => 'O restaurador saiu uns cinco minutos depois das onze. Depois disso, só vi a luz da curadoria acesa lá em cima.'],
        ],
        'sonia' => [
            ['id' => 's1', 'question' => 'A que horas você saiu?', 'answer' => 'Por volta das dez. Não bato ponto, então é minha palavra.'],
            ['id' => 's2', 'question' => 'Notou algo diferente?', 'answer' => 'A Dra. Marta ficou até tarde essa semana, trancada na sala dela. E apareceram uns tubos de papelão no ateliê.'],
        ],
    ],
    'solution' => [
        'culprit' => 'marta',
        'motive' => 'seguro',
        'key_clues' => ['m3', 'm7', 'm10', 'm9'],
        'explanation' => 'A Dra. Marta Leme forjou o roubo para embolsar o seguro que ela mesma havia inflado, pressionada por dívidas, e tratou de jogar a culpa em Félix. Pediu a manutenção da câmera em nome dele (de um ramal da curadoria), aproveitou que ele passaria no ateliê e usou materiais de restauro para cortar a tela. Félix, porém, já havia saído às 23h05 — e o alarme só foi desligado às 23h12, com o código MESTRE da curadoria, que só Marta tinha. O tubo e as luvas foram a isca. Osvaldo não saiu da guarita e Sônia já tinha ido embora.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 2, 'clue' => 1],
];
