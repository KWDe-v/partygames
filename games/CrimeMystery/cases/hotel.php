<?php
/**
 * Caso: Morte no Hotel da Nevasca.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'hotel',
    'title' => 'Morte no Hotel da Nevasca',
    'difficulty' => 'Difícil',
    'intro' => 'Uma nevasca isolou o Hotel Pico Branco nas montanhas. Entre os poucos hóspedes estava o famoso escritor Augusto Vilar, que terminava seu novo romance. Na manhã seguinte, a camareira o encontrou morto no quarto 7.',
    'crime' => 'Augusto Vilar, 59 anos, foi atingido na cabeça com um peso de papel de vidro. A porta não foi forçada: ele abriu para alguém que conhecia. O manuscrito do novo livro sumiu.',
    'victim' => 'Augusto Vilar, escritor',
    'location' => 'Quarto 7 do Hotel Pico Branco',
    'time' => 'Por volta das 23h00',
    'suspects' => [
        'regina' => ['name' => 'Regina Dutra', 'role' => 'Editora', 'profile' => 'Publica os livros de Augusto há 20 anos e veio buscar o original.', 'motive' => 'Augusto ameaçava trocar de editora.', 'alibi' => 'Diz que estava em reunião por vídeo no quarto dela.'],
        'tiago' => ['name' => 'Tiago Melo', 'role' => 'Recepcionista', 'profile' => 'Único funcionário acordado durante a madrugada.', 'motive' => 'Augusto o humilhou no check-in por causa de um quarto errado.', 'alibi' => 'Diz que não saiu da recepção.'],
        'norberto' => ['name' => 'Norberto Faria', 'role' => 'Fã do escritor', 'profile' => 'Seguiu Augusto até o hotel para pedir um autógrafo.', 'motive' => 'Tem fama de obcecado pelo autor.', 'alibi' => 'Diz que passou a noite no bar.'],
        'camila' => ['name' => 'Camila Rios', 'role' => 'Assistente do escritor', 'profile' => 'Revisa os originais de Augusto, sempre com caneta-tinteiro de tinta verde.', 'motive' => 'Nenhum motivo aparente: trabalhava com ele havia anos.', 'alibi' => 'Diz que dormia no quarto 3 desde as 22h.'],
    ],
    'motives' => [
        'credito' => 'Garantir o crédito pelo livro',
        'contrato' => 'Impedir a troca de editora',
        'obsessao' => 'Obsessão pelo autor',
        'humilhacao' => 'Vingar uma humilhação',
    ],
    'clues' => [
        'h1' => ['title' => 'O golpe', 'text' => 'Augusto foi atingido por volta das 23h com o peso de papel de vidro da escrivaninha. A porta não foi forçada.', 'unlock' => ['type' => 'start']],
        'h2' => ['title' => 'Hotel isolado', 'text' => 'Por causa da neve, ninguém entrou nem saiu do hotel. Além dos funcionários, só havia quatro hóspedes.', 'unlock' => ['type' => 'start']],
        'h3' => ['title' => 'O manuscrito sumiu', 'text' => 'O manuscrito do novo romance, que Augusto não largava, desapareceu do quarto 7.', 'unlock' => ['type' => 'time', 'at' => 0.3], 'important' => true],
        'h4' => ['title' => 'Mancha verde', 'text' => 'No peso de papel há uma mancha de tinta verde de caneta-tinteiro, ainda fresca.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'h5' => ['title' => 'O fã processado', 'text' => 'Norberto já foi processado por invadir uma noite de autógrafos de Augusto.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'h6' => ['title' => 'Manuscrito escondido', 'text' => 'Sob o colchão do quarto 3 está o manuscrito, com correções em tinta verde em todas as páginas e uma dedicatória riscada: "Para Camila, que escreveu cada linha".', 'unlock' => ['type' => 'search'], 'important' => true],
        'h7' => ['title' => 'Registro de chamadas', 'text' => 'O telefone do quarto 7 ligou para o quarto 3 às 22h50. A ligação durou 20 segundos.', 'unlock' => ['type' => 'search'], 'important' => true],
        'h8' => ['title' => 'A capa do livro', 'text' => 'Regina conta que, naquela tarde, Augusto se recusou a colocar o nome de Camila na capa e ameaçou demiti-la se ela insistisse.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'h9' => ['title' => 'Noite no bar', 'text' => 'O barman confirma que Norberto ficou no bar das 22h à meia-noite, sem sair.', 'unlock' => ['type' => 'time', 'at' => 0.6]],
        'h10' => ['title' => 'Videochamada', 'text' => 'O notebook de Regina registra uma videochamada com a gráfica das 22h40 às 23h20.', 'unlock' => ['type' => 'search']],
    ],
    'locations' => [
        'quarto3' => ['name' => 'Quarto 3', 'clue' => 'h6'],
        'recepcao' => ['name' => 'Recepção', 'clue' => 'h7'],
        'quarto_regina' => ['name' => 'Quarto de Regina', 'clue' => 'h10'],
    ],
    'interrogations' => [
        'regina' => [
            ['id' => 'r1', 'question' => 'Onde você estava às 23h?', 'answer' => 'Em videochamada com a gráfica, no meu quarto. Estamos atrasados com a impressão.'],
            ['id' => 'r2', 'question' => 'Houve alguma discussão ontem?', 'answer' => 'Camila pediu que o nome dela fosse na capa. Augusto se recusou e ameaçou demiti-la. Ela saiu chorando.', 'unlocks' => 'h8'],
            ['id' => 'r3', 'question' => 'Augusto ia trocar de editora?', 'answer' => 'Era uma ameaça antiga. Ele assinou a renovação mês passado.'],
        ],
        'tiago' => [
            ['id' => 't1', 'question' => 'Você saiu da recepção à noite?', 'answer' => 'Não. As câmeras da recepção mostram que fiquei lá até as seis.'],
            ['id' => 't2', 'question' => 'Houve ligações entre quartos?', 'answer' => 'O sistema registra todas. Pode conferir no computador da recepção.'],
        ],
        'norberto' => [
            ['id' => 'n1', 'question' => 'Onde você estava às 23h?', 'answer' => 'No bar, contando ao barman como conheci os livros do Augusto. Ele não aguentava mais.'],
            ['id' => 'n2', 'question' => 'Você foi processado pelo escritor?', 'answer' => 'Foi um mal-entendido. Eu só queria um autógrafo. Agora nunca vou ter.'],
        ],
        'camila' => [
            ['id' => 'c1', 'question' => 'Onde você estava às 23h?', 'answer' => 'Dormindo no quarto 3, desde as dez. Estava exausta.'],
            ['id' => 'c2', 'question' => 'Augusto ligou para você à noite?', 'answer' => '(Ela hesita.) Ligou... pediu para eu passar lá. Mas eu não fui.', 'requires' => 'h7'],
            ['id' => 'c3', 'question' => '(Pressionar) Quem escreveu o novo romance?', 'answer' => '(Lágrimas.) Cada linha daquele livro é minha. E ele ia publicar com o nome dele, como sempre.', 'requires' => 'h6', 'role' => 'detetive'],
        ],
    ],
    'solution' => [
        'culprit' => 'camila',
        'motive' => 'credito',
        'key_clues' => ['h3', 'h6', 'h7', 'h8'],
        'explanation' => 'Camila Rios escrevia os livros que Augusto publicava como seus. Naquela tarde ele se recusou a dar o crédito a ela e ameaçou demiti-la. Às 22h50 ele ligou para o quarto 3 chamando-a; Camila foi ao quarto 7 (por isso a porta não foi forçada), discutiram e ela o atingiu com o peso de papel — que ficou manchado com a tinta verde da caneta dela. Depois levou o manuscrito, escondido sob o próprio colchão. Regina estava em videochamada, Norberto no bar e Tiago na recepção, filmado.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 1, 'clue' => 1],
];
