<?php
/**
 * Manifesto de "Manchete Absurda".
 */
defined('APP_ROOT') || exit;

$categories = [];
foreach (require __DIR__ . '/content/themes.php' as $key => $cat) {
    $categories[$key] = $cat['label'];
}

return [
    'slug' => 'manchete-absurda',
    'class' => \Games\Headlines\Game::class,
    'name' => 'Manchete Absurda',
    'tagline' => 'O jornal mais sem noção do mundo',
    'description' => 'Todos recebem o mesmo tema e escrevem a manchete mais absurda possível. A turma vota sem saber quem escreveu — e a melhor vai para a capa.',
    'players' => [3, 20],
    'duration' => '10–20 min',
    'color' => '#e8e2d0',
    'cover' => 'cover.svg',
    'order' => 100,
    'min_players_default' => 3,
    'max_players_default' => 12,
    'tables' => [],
    // Texto do botão "Como Jogar?".
    'how_to' => [
        'goal' => 'Escrever a manchete mais engraçada e ir para a capa do jornal.',
        'steps' => [
            'Um tema é sorteado (ex.: "Uma descoberta científica inútil").',
            'Cada um escreve uma manchete sobre o tema.',
            'As manchetes aparecem embaralhadas e sem autor.',
            'Vote na manchete mais engraçada (não vale a sua).',
            'A capa revela os autores, os votos e a classificação.',
        ],
        'rules' => [
            'Uma manchete por jogador, entre 8 e 140 caracteres, sem edição depois de enviada.',
            'Ninguém sabe quem escreveu cada manchete até o fim da votação.',
            'Um voto por jogador, nunca na própria manchete.',
            'Empate no primeiro lugar: todas as empatadas dividem a capa.',
            'Com menos de 2 manchetes a rodada é anulada.',
        ],
        'scoring' => [
            'Manchete mais votada: +3 pontos para o autor (empates: todos ganham).',
            'Cada voto recebido: +1 ponto.',
        ],
        'tips' => ['Manchetes curtas, com cara de jornal de verdade, funcionam melhor.'],
    ],
    'settings' => [
        'rounds' => ['label' => 'Rodadas', 'summary' => 'Rodadas', 'min' => 1, 'max' => 15, 'default' => 4],
        'write_time' => ['label' => 'Tempo para escrever (s)', 'summary' => 'Escrever', 'unit' => ' s', 'min' => 30, 'max' => 180, 'default' => 60],
        'vote_time' => ['label' => 'Tempo para votar (s)', 'summary' => 'Votar', 'unit' => ' s', 'min' => 15, 'max' => 120, 'default' => 40],
        'reveal_time' => ['label' => 'Tempo da capa (s)', 'hint' => '0 = o anfitrião avança', 'summary' => 'Capa', 'unit' => ' s', 'zero' => 'manual', 'min' => 0, 'max' => 60, 'default' => 15],
        'points_winner' => ['label' => 'Pontos para a manchete de capa', 'summary' => 'Capa', 'unit' => ' pts', 'min' => 0, 'max' => 10, 'default' => 3],
        'points_vote' => ['label' => 'Pontos por voto recebido', 'summary' => 'Por voto', 'unit' => ' pts', 'min' => 0, 'max' => 3, 'default' => 1],
        'categories' => ['type' => 'multi', 'label' => 'Editorias', 'summary' => 'Editorias', 'options' => $categories, 'default' => array_keys($categories), 'min' => 0],
        'custom' => ['type' => 'lines', 'label' => 'Temas personalizados', 'hint' => 'um por linha', 'summary' => 'Temas', 'max_items' => 30, 'min_len' => 5, 'max_len' => 100, 'default' => []],
    ],
];
