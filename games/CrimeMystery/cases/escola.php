<?php
/**
 * Caso: As Provas Roubadas do Colégio Horizonte.
 */
defined('APP_ROOT') || exit;

return [
    'id' => 'escola',
    'title' => 'As Provas Roubadas do Colégio Horizonte',
    'difficulty' => 'Fácil',
    'intro' => 'Na véspera da prova final de matemática do Colégio Horizonte, o gabarito começou a circular em grupos de mensagens dos alunos. A prova estava trancada no cofre da coordenação. Alguém a fotografou — e vendeu.',
    'crime' => 'O gabarito da prova final foi fotografado dentro da coordenação e vendido aos alunos.',
    'victim' => 'Colégio Horizonte',
    'location' => 'Sala da coordenação',
    'time' => 'Entre 19h00 e 19h30',
    'suspects' => [
        'pedro' => ['name' => 'Pedro Alves', 'role' => 'Aluno do 3º ano', 'profile' => 'Precisa de nota 9 na prova final para passar de ano.', 'motive' => 'Estava desesperado com a nota.', 'alibi' => 'Diz que estava no laboratório de informática e pegou o ônibus das 19h.'],
        'selma' => ['name' => 'Professora Selma', 'role' => 'Professora de matemática', 'profile' => 'Elaborou a prova e é a única que conhece as questões.', 'motive' => 'Foi preterida para coordenadora este ano.', 'alibi' => 'Diz que estava no conselho de classe.'],
        'valdir' => ['name' => 'Valdir Souza', 'role' => 'Inspetor', 'profile' => 'Faz as rondas noturnas e tem acesso às chaves da portaria. Usa um celular antigo.', 'motive' => 'Nenhum motivo aparente: é querido pelos alunos.', 'alibi' => 'Diz que fazia a ronda do segundo andar.'],
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
        'e2' => ['title' => 'A chave do cofre', 'text' => 'O cofre só abre com uma chave guardada no claviculário da portaria, que registra quem a retira.', 'unlock' => ['type' => 'start'], 'important' => true],
        'e3' => ['title' => 'Fotos no escuro', 'text' => 'As fotos foram tiradas com flash, em cima da mesa da coordenação, com a luz da sala apagada.', 'unlock' => ['type' => 'time', 'at' => 0.3]],
        'e4' => ['title' => 'Dados das fotos', 'text' => 'As fotos foram tiradas às 19h12 com um celular de modelo antigo, já fora de linha.', 'unlock' => ['type' => 'role', 'role' => 'perito']],
        'e5' => ['title' => 'Aluno em recuperação', 'text' => 'Todo mundo sabe que Pedro precisava de nota 9 para não repetir o ano.', 'unlock' => ['type' => 'role', 'role' => 'jornalista']],
        'e6' => ['title' => 'Registro do claviculário', 'text' => 'A chave do cofre foi retirada às 19h05 e devolvida às 19h20. Assinatura: Valdir. Motivo anotado: "ronda".', 'unlock' => ['type' => 'search'], 'important' => true],
        'e7' => ['title' => 'Comprovantes', 'text' => 'No armário do inspetor, comprovantes de 23 transferências de R$ 50 recebidas por Valdir ontem à noite, de alunos de várias turmas.', 'unlock' => ['type' => 'search'], 'important' => true],
        'e8' => ['title' => 'O vendedor "Ronda"', 'text' => 'Pedro admite que um contato chamado "Ronda" ofereceu o gabarito por R$ 50. Ele não comprou porque não tinha dinheiro.', 'unlock' => ['type' => 'interrogation'], 'important' => true],
        'e9' => ['title' => 'Ata do conselho', 'text' => 'A ata do conselho de classe, assinada por todos, registra a presença de Selma das 18h30 às 20h.', 'unlock' => ['type' => 'time', 'at' => 0.6]],
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
            ['id' => 's1', 'question' => 'Onde você estava entre 19h e 19h30?', 'answer' => 'No conselho de classe, com todos os professores. Está na ata.'],
            ['id' => 's2', 'question' => 'Quem sabia que a prova estava no cofre?', 'answer' => 'A coordenação, a secretaria e o inspetor, que fecha a sala à noite.'],
        ],
        'valdir' => [
            ['id' => 'v1', 'question' => 'O que você fazia às 19h?', 'answer' => 'A ronda do segundo andar, como todo dia.'],
            ['id' => 'v2', 'question' => 'Por que retirou a chave do cofre?', 'answer' => 'Para conferir se estava trancado... faz parte da ronda.', 'requires' => 'e6'],
            ['id' => 'v3', 'question' => '(Pressionar) Por que alunos mandaram dinheiro para você?', 'answer' => '(Ele fica vermelho.) São... vaquinhas para a formatura. Eu ajudo a organizar.', 'requires' => 'e7', 'role' => 'detetive'],
        ],
        'rita' => [
            ['id' => 'ri1', 'question' => 'A que horas você saiu?', 'answer' => 'Às 17h, como sempre. Bati o ponto.'],
            ['id' => 'ri2', 'question' => 'Quem usa o apelido "Ronda"?', 'answer' => 'Os alunos chamam o inspetor Valdir assim, porque ele vive fazendo ronda.'],
        ],
    ],
    'solution' => [
        'culprit' => 'valdir',
        'motive' => 'dinheiro',
        'key_clues' => ['e2', 'e6', 'e7', 'e8'],
        'explanation' => 'O inspetor Valdir, apelidado de "Ronda" pelos alunos, retirou a chave do cofre às 19h05 com a desculpa da ronda, fotografou o gabarito com o flash do celular antigo às 19h12 e devolveu a chave às 19h20. À noite, vendeu as fotos por R$ 50 a vários alunos — os comprovantes estavam no armário dele. Pedro recebeu a oferta mas não comprou, e já tinha saído às 18h58; Selma estava no conselho de classe e Rita saiu às 17h.',
    ],
    'scoring' => ['culprit' => 5, 'motive' => 1, 'clue' => 1],
];
