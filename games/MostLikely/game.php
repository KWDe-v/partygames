<?php
/**
 * Manifesto de "Quem é Mais Provável?".
 */
defined('APP_ROOT') || exit;

$categories = [];
foreach (require __DIR__ . '/content/questions.php' as $key => $cat) {
    $categories[$key] = $cat['label'];
}

return [
    'slug' => 'quem-e-mais-provavel',
    'class' => \Games\MostLikely\Game::class,
    'name' => 'Quem é Mais Provável?',
    'tagline' => 'Votação secreta entre amigos',
    'description' => 'Uma pergunta, todos votam em segredo em quem tem mais cara de fazer aquilo. Depois, a revelação — sem saber quem votou em quem.',
    'players' => [3, 20],
    'duration' => '5–15 min',
    'color' => '#2ee6c5',
    'cover' => 'cover.svg',
    'order' => 10,
    'min_players_default' => 3,
    'max_players_default' => 12,
    'tables' => [],
    // Texto do botão "Como Jogar?".
    'how_to' => [
        'goal' => 'Descobrir o que a turma realmente pensa de cada um — e rir das escolhas.',
        'steps' => [
            'Uma pergunta aparece para todos: "Quem é mais provável de...?"',
            'Escolha em segredo o jogador que mais combina com a pergunta e confirme.',
            'Quando todos votarem (ou o tempo acabar), o resultado é revelado.',
            'Uma nova pergunta começa, até completar as rodadas.',
        ],
        'rules' => [
            'O voto é secreto: ninguém vê em quem você votou, só a contagem final.',
            'Você não pode votar em si mesmo.',
            'Cada jogador vota uma vez por rodada e não pode mudar o voto.',
            'Quem não votar a tempo fica sem voto naquela rodada.',
        ],
        'scoring' => [
            'O mais votado da rodada ganha os pontos (1 por padrão).',
            'Empate: todos os mais votados ganham.',
            'Ninguém votou: ninguém pontua. O anfitrião pode desligar a pontuação.',
        ],
        'tips' => [
            'Crie perguntas personalizadas sobre a sua turma para deixar tudo mais engraçado.',
        ],
    ],
    'settings' => [
        'rounds' => ['label' => 'Rodadas', 'summary' => 'Rodadas', 'min' => 1, 'max' => 30, 'default' => 10],
        'vote_time' => ['label' => 'Tempo para votar (s)', 'summary' => 'Votar', 'unit' => ' s', 'min' => 10, 'max' => 120, 'default' => 30],
        'reveal_time' => ['label' => 'Tempo da revelação (s)', 'summary' => 'Revelação', 'unit' => ' s', 'min' => 4, 'max' => 30, 'default' => 8],
        'scoring' => ['type' => 'bool', 'label' => 'Pontuação', 'summary' => 'Pontuação', 'default' => true],
        'points' => ['label' => 'Pontos para o mais votado', 'summary' => 'Pontos', 'min' => 1, 'max' => 10, 'default' => 1],
        'categories' => ['type' => 'multi', 'label' => 'Categorias', 'summary' => 'Categorias', 'options' => $categories, 'default' => array_keys($categories), 'min' => 0],
        'custom' => ['type' => 'lines', 'label' => 'Perguntas personalizadas', 'hint' => 'uma por linha, ex.: dormir no ônibus', 'summary' => 'Perguntas', 'max_items' => 40, 'min_len' => 5, 'max_len' => 140, 'default' => []],
    ],
];
