<?php
/**
 * Missões de "Missão Secreta".
 *
 * Tipos verificados automaticamente pelo servidor nas mensagens do bate-papo:
 *   word           outro jogador escreve a palavra 'word'
 *   list           outro jogador escreve uma palavra da lista 'list' (ver 'lists' abaixo)
 *   target_question  o alvo escreve uma mensagem com "?"
 *   target_name      o alvo escreve o apelido do dono da missão
 *   target_number    o alvo escreve um número (algarismo ou por extenso)
 * Tipo 'vote': avaliado pelos outros jogadores no fim (maioria de "sim" = cumprida).
 * {alvo} é trocado pelo apelido de outro participante sorteado.
 * 'label' é a descrição genérica (sem nomes) usada na fase de palpites.
 */
defined('APP_ROOT') || exit;

$missions = [];
foreach (['banana', 'pinguim', 'abacaxi', 'foguete', 'sorvete', 'dinossauro', 'trampolim', 'cogumelo', 'chinelo', 'unicórnio', 'pipoca', 'vulcão'] as $w) {
    $missions[] = ['key' => 'word:' . $w, 'type' => 'word', 'word' => $w, 'text' => 'Faça outro jogador escrever a palavra “' . $w . '”.', 'label' => 'Fazer alguém escrever “' . $w . '”'];
}
$missions[] = ['key' => 'list:animais', 'type' => 'list', 'list' => 'animais', 'text' => 'Faça outro jogador mencionar um animal.', 'label' => 'Fazer alguém mencionar um animal'];
$missions[] = ['key' => 'list:cores', 'type' => 'list', 'list' => 'cores', 'text' => 'Faça outro jogador mencionar uma cor.', 'label' => 'Fazer alguém mencionar uma cor'];
$missions[] = ['key' => 'list:comidas', 'type' => 'list', 'list' => 'comidas', 'text' => 'Faça outro jogador mencionar uma comida.', 'label' => 'Fazer alguém mencionar uma comida'];
$missions[] = ['key' => 'list:paises', 'type' => 'list', 'list' => 'paises', 'text' => 'Faça outro jogador mencionar um país.', 'label' => 'Fazer alguém mencionar um país'];
$missions[] = ['key' => 'target_question', 'type' => 'target_question', 'text' => 'Faça {alvo} escrever uma pergunta (uma mensagem com “?”).', 'label' => 'Fazer um jogador específico fazer uma pergunta'];
$missions[] = ['key' => 'target_name', 'type' => 'target_name', 'text' => 'Faça {alvo} escrever o seu apelido.', 'label' => 'Fazer um jogador específico escrever seu apelido'];
$missions[] = ['key' => 'target_number', 'type' => 'target_number', 'text' => 'Faça {alvo} escrever um número.', 'label' => 'Fazer um jogador específico escrever um número'];
foreach ([
    'praia' => ['Convença alguém a escolher PRAIA em vez de montanha (e que isso fique claro no chat).', 'Convencer alguém a escolher praia'],
    'absurdo' => ['Faça outro participante defender uma ideia absurda no chat.', 'Fazer alguém defender uma ideia absurda'],
    'concordar' => ['Faça alguém concordar com você três vezes.', 'Fazer alguém concordar com você 3 vezes'],
    'desculpas' => ['Faça alguém pedir desculpas no chat.', 'Fazer alguém pedir desculpas'],
    'elogio' => ['Faça alguém elogiar você no chat.', 'Fazer alguém elogiar você'],
    'segredo' => ['Faça alguém contar um "segredo" (mesmo inventado) no chat.', 'Fazer alguém contar um segredo'],
] as $k => $m) {
    $missions[] = ['key' => 'vote:' . $k, 'type' => 'vote', 'text' => $m[0], 'label' => $m[1]];
}

return [
    'missions' => $missions,
    // Palavras aceitas nas missões de lista (comparadas sem acentos e maiúsculas, palavra inteira).
    'lists' => [
        'animais' => ['cachorro', 'cachorra', 'gato', 'gata', 'leao', 'tigre', 'cavalo', 'vaca', 'boi', 'porco', 'galinha', 'pato', 'peixe', 'tubarao', 'baleia', 'golfinho', 'cobra', 'sapo', 'macaco', 'elefante', 'girafa', 'zebra', 'urso', 'lobo', 'raposa', 'coelho', 'rato', 'passaro', 'papagaio', 'coruja', 'aguia', 'pinguim', 'tartaruga', 'jacare', 'abelha', 'formiga', 'borboleta', 'aranha', 'camelo', 'ovelha', 'cabra', 'polvo', 'hamster', 'dinossauro', 'cachorros', 'gatos', 'peixes'],
        'cores' => ['vermelho', 'vermelha', 'azul', 'verde', 'amarelo', 'amarela', 'roxo', 'roxa', 'rosa', 'laranja', 'preto', 'preta', 'branco', 'branca', 'cinza', 'marrom', 'dourado', 'dourada', 'prata', 'violeta', 'bege', 'lilas', 'turquesa'],
        'comidas' => ['pizza', 'hamburguer', 'arroz', 'feijao', 'macarrao', 'lasanha', 'sushi', 'bolo', 'chocolate', 'sorvete', 'pao', 'queijo', 'batata', 'salada', 'churrasco', 'coxinha', 'brigadeiro', 'pipoca', 'tapioca', 'feijoada', 'ovo', 'carne', 'frango', 'sopa', 'cafe', 'acai', 'pastel', 'esfiha'],
        'paises' => ['brasil', 'argentina', 'portugal', 'espanha', 'franca', 'italia', 'alemanha', 'japao', 'china', 'eua', 'estadosunidos', 'mexico', 'canada', 'chile', 'peru', 'uruguai', 'paraguai', 'inglaterra', 'russia', 'india', 'egito', 'australia', 'coreia', 'grecia', 'holanda', 'suica', 'angola', 'mocambique'],
    ],
    // Assuntos sugeridos para puxar conversa no bate-papo.
    'topics' => [
        'Planejem juntos a viagem de férias do grupo.',
        'Decidam o cardápio da festa de fim de ano.',
        'Montem uma banda: nome, estilo e quem toca o quê.',
        'Escolham o filme da sessão de sábado.',
        'Inventem um negócio para ficarem ricos.',
        'Organizem uma gincana para o fim de semana.',
        'Decidam o que levar para uma ilha deserta.',
    ],
];
