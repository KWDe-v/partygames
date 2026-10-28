<?php
/**
 * Casos de "O Tribunal". Cada caso tem:
 *  charge       acusação lida para todos
 *  provas       informações privadas do promotor
 *  argumentos   informações privadas da defesa
 *  fatos        o que as testemunhas viram, conforme a verdade sorteada ('culpado' ou 'inocente')
 *  reu          o segredo do réu, conforme a verdade sorteada
 */
defined('APP_ROOT') || exit;

return [
    'pizza' => [
        'title' => 'O Caso da Última Fatia',
        'charge' => 'O réu é acusado de comer a última fatia de pizza da festa e culpar o cachorro.',
        'provas' => ['Havia molho de tomate na manga da camisa do réu.', 'O cachorro, Biscoito, é alérgico a queijo e nunca come pizza.'],
        'argumentos' => ['Biscoito já roubou um frango inteiro da mesa no Natal.', 'O réu estava de dieta e postou uma foto comendo salada às 21h.'],
        'fatos' => [
            'culpado' => ['Vi o réu na cozinha às 21h30 limpando a boca com um guardanapo.', 'Ouvi alguém dizendo "foi o cachorro!" antes mesmo de alguém perguntar.'],
            'inocente' => ['Vi o Biscoito saindo da cozinha com farelo no focinho.', 'O réu passou a noite toda na varanda falando ao telefone.'],
        ],
        'reu' => ['culpado' => 'Você comeu a fatia — e estava deliciosa. Convença o júri de que foi o cachorro.', 'inocente' => 'Você não comeu a pizza: foi mesmo o Biscoito. Prove sua inocência!'],
    ],
    'jokenpo' => [
        'title' => 'O Escândalo do Pedra, Papel e Tesoura',
        'charge' => 'O réu é acusado de trapacear na final do campeonato de pedra, papel e tesoura do bairro.',
        'provas' => ['As câmeras mostram o réu jogando sempre meio segundo depois do adversário.', 'O réu venceu 47 partidas seguidas, algo estatisticamente improvável.'],
        'argumentos' => ['O adversário sempre começa com pedra — qualquer um sabe disso.', 'O réu treina com o avô campeão há dez anos.'],
        'fatos' => [
            'culpado' => ['Vi o réu esperando a mão do adversário abrir antes de mostrar a dele.', 'O réu me pediu para ficar atrás do adversário fazendo sinais.'],
            'inocente' => ['O adversário mudou de jogada várias vezes e o réu ganhou mesmo assim.', 'O juiz da partida conferiu cada rodada e não viu nada estranho.'],
        ],
        'reu' => ['culpado' => 'Você jogava atrasado de propósito. Ninguém pode descobrir!', 'inocente' => 'Você é simplesmente muito bom. Defenda seu talento.'],
    ],
    'cadeiras' => [
        'title' => 'O Mistério das Cadeiras Desaparecidas',
        'charge' => 'O réu é acusado de roubar todas as cadeiras da festa de aniversário da tia Neide.',
        'provas' => ['Uma cadeira idêntica às da festa foi vista na casa do réu.', 'O réu chegou à festa com uma caminhonete vazia — e saiu com ela cheia.'],
        'argumentos' => ['O réu é dono de uma loja de móveis: tem cadeiras iguais em casa.', 'A caminhonete levava a mesa do bolo, emprestada pelo próprio réu.'],
        'fatos' => [
            'culpado' => ['Vi o réu empilhando cadeiras enquanto todos cantavam parabéns.', 'Encontrei a etiqueta "Festa da Tia Neide" na garagem do réu.'],
            'inocente' => ['A empresa de aluguel recolheu as cadeiras mais cedo por engano.', 'O réu estava na pista de dança na hora em que as cadeiras sumiram.'],
        ],
        'reu' => ['culpado' => 'As cadeiras estão na sua garagem. Ninguém precisa saber.', 'inocente' => 'Você não pegou nenhuma cadeira — a empresa de aluguel levou tudo.'],
    ],
    'cafe' => [
        'title' => 'O Café que Virou Sopa',
        'charge' => 'O réu é acusado de trocar o café do escritório por sopa de legumes.',
        'provas' => ['O réu é o único que chega antes das 7h, quando a garrafa é preparada.', 'Havia uma colher de sopa na mesa do réu.'],
        'argumentos' => ['O réu nem toma café: é alérgico a cafeína.', 'O pacote de sopa veio de outro andar.'],
        'fatos' => [
            'culpado' => ['Vi o réu despejando algo verde na garrafa térmica às 6h50.', 'O réu riu baixinho quando o chefe tomou o primeiro gole.'],
            'inocente' => ['A copeira confundiu as garrafas porque estavam sem etiqueta.', 'O réu chegou junto comigo, às 8h, quando a garrafa já estava cheia.'],
        ],
        'reu' => ['culpado' => 'Foi você. Era uma pegadinha... que deu muito certo.', 'inocente' => 'Você não fez nada: foi uma confusão da copa.'],
    ],
    'grupo' => [
        'title' => 'O Áudio de Quinze Minutos',
        'charge' => 'O réu é acusado de mandar um áudio de quinze minutos no grupo da família às 3h da manhã.',
        'provas' => ['O áudio saiu do celular do réu às 3h02.', 'No áudio, alguém fala "não conta para ninguém que fui eu".'],
        'argumentos' => ['O celular do réu estava carregando na sala, ao alcance de todos.', 'O réu dorme às 22h há anos.'],
        'fatos' => [
            'culpado' => ['Ouvi o réu falando sozinho no quarto de madrugada.', 'O réu me perguntou de manhã se "alguém tinha ouvido o áudio inteiro".'],
            'inocente' => ['Vi o primo mexendo no celular do réu durante a madrugada.', 'O réu roncava alto às 3h — dava para ouvir do corredor.'],
        ],
        'reu' => ['culpado' => 'Você mandou o áudio. Eram assuntos importantes!', 'inocente' => 'Você estava dormindo. Alguém usou seu celular.'],
    ],
    'controle' => [
        'title' => 'O Sumiço do Controle Remoto',
        'charge' => 'O réu é acusado de esconder o controle remoto durante a final do campeonato.',
        'provas' => ['O controle foi achado dentro da almofada favorita do réu.', 'O réu torce para o time adversário.'],
        'argumentos' => ['Todos sentaram naquela almofada durante o jogo.', 'O réu passou o segundo tempo inteiro na cozinha fazendo pipoca.'],
        'fatos' => [
            'culpado' => ['Vi o réu enfiando algo na almofada quando o outro time fez gol.', 'O réu trocou de canal "sem querer" bem no pênalti.'],
            'inocente' => ['O gato vive empurrando coisas para dentro das almofadas.', 'O réu estava na cozinha: o cheiro de pipoca não deixa mentir.'],
        ],
        'reu' => ['culpado' => 'Você escondeu o controle. Seu time agradece.', 'inocente' => 'Você nem tocou no controle. Foi o gato!'],
    ],
    'karaoke' => [
        'title' => 'A Sabotagem do Karaokê',
        'charge' => 'O réu é acusado de desligar o microfone toda vez que o vizinho ia cantar.',
        'provas' => ['O fio do microfone passa bem atrás da cadeira do réu.', 'O réu já reclamou da voz do vizinho em três reuniões de condomínio.'],
        'argumentos' => ['O microfone é velho e falha sozinho.', 'O réu estava cantando em dupla com o próprio vizinho.'],
        'fatos' => [
            'culpado' => ['Vi o pé do réu "tropeçando" no fio a cada refrão.', 'O réu comemorou baixinho quando o som cortou.'],
            'inocente' => ['O técnico disse que o microfone tem mau contato.', 'O réu estava do outro lado da sala, longe do fio.'],
        ],
        'reu' => ['culpado' => 'Era você puxando o fio. Pelo bem dos ouvidos de todos.', 'inocente' => 'Você não fez nada: o microfone é velho mesmo.'],
    ],
    'bolo' => [
        'title' => 'O Bolo Sem Recheio',
        'charge' => 'O réu é acusado de comer todo o recheio do bolo de aniversário e deixar só a massa.',
        'provas' => ['Havia brigadeiro nos dedos do réu às 18h.', 'O réu foi o único a ficar sozinho com o bolo.'],
        'argumentos' => ['O réu estava ajudando a fazer brigadeiros para os docinhos.', 'A confeitaria admitiu ter esquecido o recheio de outro bolo.'],
        'fatos' => [
            'culpado' => ['Vi o réu com uma colher na mão saindo da sala do bolo.', 'O réu recusou o bolo dizendo que "já estava satisfeito".'],
            'inocente' => ['A confeitaria ligou dizendo que mandou o bolo errado.', 'O réu passou a tarde enrolando docinhos comigo.'],
        ],
        'reu' => ['culpado' => 'Você comeu o recheio. Valeu cada colherada.', 'inocente' => 'Você não comeu nada: a confeitaria errou.'],
    ],
];
