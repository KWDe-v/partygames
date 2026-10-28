<?php
/**
 * Caso: Envenenamento na Vinícola Serra Alta.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'vinicola',
    'title' => 'Envenenamento na Vinícola Serra Alta',
    'difficulty' => 'Médio',
    'intro' => 'A Vinícola Serra Alta lançava sua safra especial com um jantar para convidados. No brinde das 20h, a fundadora, Dona Iolanda Serra, bebeu um gole, levou a mão ao peito e caiu. O vinho dos outros convidados era o mesmo — e ninguém mais passou mal.',
    'crime' => 'Iolanda Serra, 68 anos, morreu envenenada por um pesticida usado no vinhedo. Só a taça dela tinha o veneno.',
    'victim' => 'Iolanda Serra, fundadora da vinícola',
    'location' => 'Salão de degustação da Vinícola Serra Alta',
    'time' => 'Brinde às 20h00',
    'suspects' => [
        'mateus' => ['name' => 'Mateus Costa', 'role' => 'Enólogo', 'profile' => 'Responsável pela produção e pelo engarrafamento há oito anos.', 'motive' => 'Nenhum motivo aparente: era o braço direito de Iolanda.', 'alibi' => 'Diz que estava no porão de barris conferindo a safra.'],
        'lucia' => ['name' => 'Lúcia Serra', 'role' => 'Filha da vítima', 'profile' => 'Herdeira da vinícola. Mora na capital e raramente visita a mãe.', 'motive' => 'Brigou com a mãe por dinheiro na semana passada.', 'alibi' => 'Diz que dava uma entrevista por telefone.'],
        'pierre' => ['name' => 'Pierre Lanot', 'role' => 'Sócio investidor', 'profile' => 'Comprou 30% da vinícola no ano passado e quer exportar os vinhos.', 'motive' => 'Iolanda vetou a exportação.', 'alibi' => 'Diz que acabara de chegar do aeroporto.'],
        'davi' => ['name' => 'Davi Rocha', 'role' => 'Sommelier', 'profile' => 'Serviu todas as taças do brinde em uma bandeja.', 'motive' => 'Foi advertido por Iolanda por chegar atrasado.', 'alibi' => 'Diz que só serviu o vinho nas taças que recebeu.'],
    ],
    'motives' => [
        'heranca' => 'Antecipar a herança',
        'fraude' => 'Esconder a fraude nos rótulos',
        'sociedade' => 'Assumir o controle da sociedade',
        'demissao' => 'Vingar-se de uma advertência',
    ],
    'clues' => [
        'v1' => ['title' => 'O veneno', 'text' => 'O veneno é um pesticida usado no vinhedo. Só a taça de Iolanda o continha.', 'unlock' => ['type' => 'start']],
        'v2' => ['title' => 'A taça dourada', 'text' => 'A taça de Iolanda tinha borda dourada, exclusiva da fundadora. Todas as taças foram servidas por Davi numa bandeja às 20h.', 'unlock' => ['type' => 'start'], 'important' => true],
        'v3' => ['title' => 'O costume da casa', 'text' => 'Pela tradição, a taça dourada é enxaguada com o vinho do brinde pelo enólogo, no porão, antes de subir. Mateus a levou ao porão às 19h40.', 'unlock' => ['type' => 'time', 'at' => 0.3], 'important' => true],
        'v4' => ['title' => 'Resíduo seco', 'text' => 'O pesticida estava seco no fundo da taça: foi colocado antes do vinho, e não misturado depois.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'v5' => ['title' => 'Briga em família', 'text' => 'Funcionários comentam que Lúcia gritou com a mãe por causa de dinheiro na semana passada.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'v6' => ['title' => 'Frasco escondido', 'text' => 'Atrás do barril 12 — o barril pessoal de Mateus — há um frasco de pesticida com o lacre recém-aberto.', 'unlock' => ['type' => 'search'], 'important' => true],
        'v7' => ['title' => 'Relatório do laboratório', 'text' => 'No escritório, um laudo encomendado por Iolanda: o "Reserva 2018" é vinho de mesa com rótulo falso. Responsável pelo engarrafamento: Mateus. Reunião com a polícia marcada para segunda.', 'unlock' => ['type' => 'search'], 'important' => true],
        'v8' => ['title' => 'A bandeja', 'text' => 'Davi recebeu a taça dourada já pronta das mãos de Mateus às 19h55. Ele apenas serviu o vinho.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'v9' => ['title' => 'Entrevista no rádio', 'text' => 'Lúcia falava ao vivo numa rádio, por telefone, das 19h30 às 20h05. A gravação confirma.', 'unlock' => ['type' => 'time', 'at' => 0.6]],
        'v10' => ['title' => 'Recibo do táxi', 'text' => 'O recibo do táxi de Pierre mostra a chegada à vinícola às 19h58, direto do aeroporto.', 'unlock' => ['type' => 'search']],
    ],
    'locations' => [
        'porao' => ['name' => 'Porão de barris', 'clue' => 'v6'],
        'escritorio' => ['name' => 'Escritório de Iolanda', 'clue' => 'v7'],
        'quarto_hospedes' => ['name' => 'Quarto de hóspedes de Pierre', 'clue' => 'v10'],
    ],
    'interrogations' => [
        'mateus' => [
            ['id' => 'ma1', 'question' => 'O que você fazia antes do brinde?', 'answer' => 'Preparava a taça da Dona Iolanda, como manda a tradição. Enxaguei com o vinho e entreguei ao Davi.'],
            ['id' => 'ma2', 'question' => 'Quem tem acesso ao pesticida?', 'answer' => 'Fica no galpão do vinhedo. Qualquer funcionário pega.'],
            ['id' => 'ma3', 'question' => '(Pressionar) O que diz o laudo sobre o Reserva 2018?', 'answer' => '(Ele aperta as mãos.) Laboratórios erram. Aquela safra foi difícil, só isso.', 'requires' => 'v7', 'role' => 'detetive'],
        ],
        'lucia' => [
            ['id' => 'l1', 'question' => 'Onde você estava às 20h?', 'answer' => 'Ao vivo numa rádio da capital, falando da safra. Pode ouvir a gravação.'],
            ['id' => 'l2', 'question' => 'Por que brigou com sua mãe?', 'answer' => 'Pedi um empréstimo e ela negou. Brigamos, mas fizemos as pazes no domingo.'],
        ],
        'pierre' => [
            ['id' => 'p1', 'question' => 'Quando você chegou?', 'answer' => 'Dois minutos antes do brinde. Mal tirei o casaco.'],
            ['id' => 'p2', 'question' => 'Iolanda vetou a exportação?', 'answer' => 'Vetou, mas ia reconsiderar. Ela me disse que primeiro precisava "resolver um problema na produção".'],
        ],
        'davi' => [
            ['id' => 'd1', 'question' => 'Quem preparou a taça dourada?', 'answer' => 'O Mateus me entregou já pronta às 19h55. Eu só servi o vinho, como em todas as outras.', 'unlocks' => 'v8'],
            ['id' => 'd2', 'question' => 'Alguém mexeu na bandeja?', 'answer' => 'Não. Ela ficou o tempo todo comigo, do porão até o salão.'],
            ['id' => 'd3', 'question' => 'Você foi advertido por Iolanda?', 'answer' => 'Por atraso, sim. Mas ela me deu outra chance. Eu gostava dela.'],
        ],
    ],
    'solution' => [
        'culprit' => 'mateus',
        'motive' => 'fraude',
        'key_clues' => ['v2', 'v3', 'v6', 'v7', 'v8'],
        'explanation' => 'Mateus Costa engarrafava vinho comum com rótulo de "Reserva", e Iolanda tinha o laudo que provava a fraude, com reunião marcada com a polícia. Aproveitando a tradição de preparar a taça dourada no porão, ele colocou o pesticida no fundo dela às 19h40 e a entregou pronta a Davi, que só serviu o vinho. O frasco aberto estava atrás do barril dele. Lúcia estava ao vivo no rádio e Pierre chegou às 19h58, depois de a taça já estar preparada.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 1, 'clue' => 1],
];
