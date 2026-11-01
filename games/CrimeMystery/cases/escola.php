<?php
/**
 * Caso: As Provas Roubadas do Colégio Horizonte.
 *
 * Nível médio: a professora de matemática é a isca perfeita (foi preterida e conhece a prova),
 * e agora tem janela de oportunidade. Mas o gabarito foi FOTOGRAFADO do cofre com uma chave
 * registrada — e só o inspetor retirou a chave, usa o celular antigo das fotos e recebeu o dinheiro.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'escola',
    'title' => 'As Provas Roubadas do Colégio Horizonte',
    'difficulty' => 'Médio',
    'intro' => 'Na véspera da prova final de matemática do Colégio Horizonte, o gabarito começou a circular em grupos de mensagens dos alunos. A prova estava trancada no cofre da coordenação. Alguém a fotografou — e vendeu.',
    'crime' => 'O gabarito da prova final foi fotografado dentro da coordenação e vendido aos alunos.',
    'victim' => 'Colégio Horizonte',
    'location' => 'Sala da coordenação',
    'time' => 'Entre 19h00 e 19h30',
    'suspects' => [
        'pedro' => ['name' => 'Pedro Alves', 'role' => 'Aluno do 3º ano', 'profile' => 'Precisa de nota 9 na prova final para passar de ano.', 'motive' => 'Estava desesperado com a nota.', 'alibi' => 'Diz que estava no laboratório de informática e pegou o ônibus das 19h.'],
        'selma' => ['name' => 'Professora Selma', 'role' => 'Professora de matemática', 'profile' => 'Elaborou a prova e é a única que conhece as questões de cor. Foi preterida para coordenadora este ano.', 'motive' => 'Ressentida por ter sido passada para trás na promoção.', 'alibi' => 'Diz que estava no conselho de classe — que teve um intervalo por volta das 19h.'],
        'valdir' => ['name' => 'Valdir Souza', 'role' => 'Inspetor', 'profile' => 'Faz as rondas noturnas e tem acesso ao claviculário da portaria. Usa um celular antigo.', 'motive' => 'Nenhum motivo aparente: é querido pelos alunos.', 'alibi' => 'Diz que fazia a ronda do segundo andar.'],
        'rita' => ['name' => 'Rita Campos', 'role' => 'Secretária', 'profile' => 'Organiza os documentos da coordenação e conhece o claviculário.', 'motive' => 'Reclama do salário baixo.', 'alibi' => 'Diz que saiu às 17h.'],
    ],
    'motives' => [
        'dinheiro' => 'Ganhar dinheiro vendendo o gabarito',
        'aprovacao' => 'Garantir a própria aprovação',
        'vinganca' => 'Prejudicar a nova coordenadora',
        'salario' => 'Complementar o salário baixo',
    ],
    'clues' => [
        'e1' => ['title' => 'Gabarito vazado', 'text' => 'Fotos do gabarito circularam num grupo de alunos às 22h. A prova estava trancada no cofre da coordenação.', 'unlock' => ['type' => 'start']],
        'e2' => ['title' => 'A chave do cofre', 'text' => 'O cofre só abre com uma chave guardada no claviculário da portaria, que registra por escrito quem a retira. Não há outra cópia.', 'unlock' => ['type' => 'start'], 'important' => true],
        'e3' => ['title' => 'Fotos no escuro', 'text' => 'As fotos foram tiradas com flash, em cima da mesa da coordenação, com a luz da sala apagada. Quem vazou fotografou o papel — não escreveu o gabarito de memória.', 'unlock' => ['type' => 'time', 'at' => 0.3]],
        'e4' => ['title' => 'Dados das fotos', 'text' => 'As fotos foram tiradas às 19h12 com um celular de modelo antigo, já fora de linha há anos.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'e5' => ['title' => 'A professora preterida', 'text' => 'Todos sabem que Selma esperava ser a nova coordenadora e ficou furiosa quando o cargo foi para outra pessoa.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'e6' => ['title' => 'Registro do claviculário', 'text' => 'A chave do cofre foi retirada às 19h05 e devolvida às 19h20. Assinatura no livro: Valdir. Motivo anotado: "ronda".', 'unlock' => ['type' => 'search'], 'important' => true],
        'e7' => ['title' => 'Comprovantes', 'text' => 'No armário do inspetor, comprovantes de 23 transferências de R$ 50 recebidas por Valdir ontem à noite, de alunos de várias turmas.', 'unlock' => ['type' => 'search'], 'important' => true],
        'e8' => ['title' => 'O vendedor "Ronda"', 'text' => 'Pedro conta que um contato chamado "Ronda" ofereceu o gabarito por R$ 50 no grupo. Ele não comprou porque não tinha dinheiro.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'e9' => ['title' => 'Ata do conselho', 'text' => 'A ata registra o conselho das 18h30 às 20h, mas anota um intervalo das 19h05 às 19h25, em que os professores se dispersaram pelos corredores. Ninguém jura ter visto Selma nesse intervalo.', 'unlock' => ['type' => 'time', 'at' => 0.6]],
        'e10' => ['title' => 'Login no laboratório', 'text' => 'O sistema do laboratório mostra Pedro conectado até 18h50; a câmera do portão o registra saindo às 18h58.', 'unlock' => ['type' => 'search']],
    ],
    'locations' => [
        'portaria' => ['name' => 'Portaria', 'clue' => 'e6'],
        'armario_inspetor' => ['name' => 'Armário do inspetor', 'clue' => 'e7'],
        'laboratorio' => ['name' => 'Laboratório de informática', 'clue' => 'e10'],
    ],
    'interrogations' => [
        'pedro' => [
            ['id' => 'p1', 'question' => 'Você comprou o gabarito?', 'answer' => 'Não! Um tal de "Ronda" me ofereceu por R$ 50 no grupo, mas eu não tinha dinheiro.', 'unlocks' => 'e8'],
            ['id' => 'p2', 'question' => 'Onde você estava às 19h?', 'answer' => 'Saindo para o ônibus. Fiquei no laboratório até quase sete.'],
        ],
        'selma' => [
            ['id' => 's1', 'question' => 'Onde você estava entre 19h e 19h30?', 'answer' => 'No conselho de classe. Teve um intervalo lá pelas sete, confesso que fui tomar um café e esticar as pernas. Mas voltei.'],
            ['id' => 's2', 'question' => 'Você precisaria entrar no cofre para vazar a prova?', 'answer' => 'Eu fiz a prova, conheço cada questão. Se eu quisesse vazar, escrevia de cabeça — não ia fotografar papel no escuro.'],
            ['id' => 's3', 'question' => '(Pressionar) A senhora ficou com raiva da promoção?', 'answer' => '(Seca.) Fiquei. Mereço aquele cargo. Mas magoar os alunos na prova deles não me devolve nada.', 'role' => 'detetive'],
        ],
        'valdir' => [
            ['id' => 'v1', 'question' => 'O que você fazia às 19h?', 'answer' => 'A ronda do segundo andar, como todo dia.'],
            ['id' => 'v2', 'question' => 'Por que retirou a chave do cofre?', 'answer' => 'Para conferir se estava trancado... faz parte da ronda.', 'requires' => 'e6'],
            ['id' => 'v3', 'question' => '(Pressionar) Por que 23 alunos mandaram R$ 50 para você?', 'answer' => '(Ele fica vermelho.) São... vaquinhas para a formatura. Eu ajudo a organizar.', 'requires' => 'e7', 'role' => 'detetive'],
        ],
        'rita' => [
            ['id' => 'ri1', 'question' => 'A que horas você saiu?', 'answer' => 'Às 17h, como sempre. Bati o ponto.'],
            ['id' => 'ri2', 'question' => 'Quem usa o apelido "Ronda"?', 'answer' => 'É como chamam quem vive de ronda pelo prédio à noite. O inspetor, principalmente — mas o pessoal da limpeza também cruza o colégio todo.'],
        ],
    ],
    'solution' => [
        'culprit' => 'valdir',
        'motive' => 'dinheiro',
        'key_clues' => ['e2', 'e6', 'e7', 'e10'],
        'explanation' => 'O inspetor Valdir retirou a chave do cofre às 19h05 com a desculpa da ronda — e o livro do claviculário, que não tem outra cópia da chave, só traz a assinatura dele. Fotografou o gabarito às 19h12 com o celular antigo que ele mesmo usa e devolveu a chave às 19h20, vendendo depois as fotos por R$ 50 a vários alunos (os 23 comprovantes estavam no armário dele). Selma é a suspeita óbvia — foi preterida, conhece a prova e saiu do conselho no intervalo —, mas ela nunca retirou a chave e não precisaria fotografar papel para vazar questões que já sabia de cor. Pedro já tinha saído às 18h58 e Rita às 17h.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 2, 'clue' => 1],
];
