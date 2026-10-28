<?php
/**
 * Manifesto de "A Bomba Relógio".
 */
defined('APP_ROOT') || exit;

$categories = require __DIR__ . '/content/categories.php';

return [
    'slug' => 'bomba-relogio',
    'class' => \Games\TimeBomb\Game::class,
    'name' => 'A Bomba Relógio',
    'tagline' => 'Responda antes que exploda',
    'description' => 'Uma categoria e uma bomba passando de mão em mão. Diga algo da categoria antes que o tempo acabe — ou boom!',
    'players' => [2, 12],
    // Melhor com conversa por voz: o catálogo mostra a recomendação de jogar em call.
    'call' => true,
    'duration' => '5–15 min',
    'color' => '#ff3b3b',
    'cover' => 'cover.svg',
    'order' => 80,
    'min_players_default' => 2,
    'max_players_default' => 10,
    'tables' => [],
    // Texto do botão "Como Jogar?".
    'how_to' => [
        'goal' => 'Ser o último sobrevivente: responda rápido e nunca deixe a bomba explodir na sua mão.',
        'steps' => [
            'Uma categoria aparece para todos (ex.: Animais).',
            'A bomba está com um jogador: ele precisa escrever algo da categoria antes do tempo acabar.',
            'Resposta aceita: a bomba passa para o próximo jogador, com o cronômetro reiniciado.',
            'Se o tempo acabar, a bomba explode: o jogador perde pontos e sai da rodada.',
            'O último que sobrar vence a rodada. Uma nova categoria começa.',
        ],
        'rules' => [
            'Respostas vazias, fora do prazo ou fora da sua vez são recusadas pelo servidor.',
            'Respostas repetidas na mesma rodada são recusadas (dá para desligar ao criar a sala); você pode tentar outra enquanto houver tempo.',
            'Acentos e maiúsculas não importam: "Pão" e "pao" contam como a mesma resposta.',
            'Se a resposta é mesmo da categoria, quem decide é a galera — vale a honestidade!',
        ],
        'scoring' => [
            'Cada resposta aceita: +1 ponto.',
            'Bomba explodiu na sua mão: −2 pontos.',
            'Último sobrevivente da rodada: +3 pontos.',
        ],
        'tips' => ['Pense na próxima resposta enquanto os outros jogam!'],
    ],
    'settings' => [
        'rounds' => ['label' => 'Rodadas (categorias)', 'summary' => 'Rodadas', 'min' => 1, 'max' => 10, 'default' => 3],
        'turn_time' => ['label' => 'Tempo de cada vez (s)', 'summary' => 'Pavio', 'unit' => ' s', 'min' => 5, 'max' => 30, 'default' => 12],
        'no_repeat' => ['type' => 'bool', 'label' => 'Proibir respostas repetidas', 'summary' => 'Sem repetir', 'default' => true],
        'points_answer' => ['label' => 'Pontos por resposta', 'summary' => 'Resposta', 'unit' => ' pts', 'min' => 0, 'max' => 3, 'default' => 1],
        'penalty' => ['label' => 'Penalidade da explosão', 'summary' => 'Explosão', 'unit' => ' pts', 'min' => 0, 'max' => 10, 'default' => 2],
        'points_survivor' => ['label' => 'Pontos do sobrevivente', 'summary' => 'Sobrevivente', 'unit' => ' pts', 'min' => 0, 'max' => 10, 'default' => 3],
        'reveal_time' => ['label' => 'Tempo do resultado (s)', 'summary' => 'Resultado', 'unit' => ' s', 'min' => 5, 'max' => 30, 'default' => 10],
        'categories' => ['type' => 'multi', 'label' => 'Categorias', 'summary' => 'Categorias', 'options' => $categories, 'default' => array_keys($categories), 'min' => 0],
        'custom' => ['type' => 'lines', 'label' => 'Categorias personalizadas', 'hint' => 'uma por linha', 'summary' => 'Personalizadas', 'max_items' => 30, 'min_len' => 3, 'max_len' => 80, 'default' => []],
    ],
];
