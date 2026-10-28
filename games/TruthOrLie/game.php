<?php
/**
 * Manifesto de "Mentira ou Verdade?".
 */
defined('APP_ROOT') || exit;

$categories = [];
foreach (require __DIR__ . '/content/themes.php' as $key => $cat) {
    $categories[$key] = $cat['label'];
}

return [
    'slug' => 'mentira-ou-verdade',
    'class' => \Games\TruthOrLie\Game::class,
    'name' => 'Mentira ou Verdade?',
    'tagline' => 'Engane seus amigos',
    'description' => 'Escreva uma afirmação sobre você — verdadeira ou inventada — e marque em segredo. Depois, adivinhe quem está mentindo.',
    'players' => [3, 16],
    // Melhor com conversa por voz: o catálogo mostra a recomendação de jogar em call.
    'call' => true,
    'duration' => '10–20 min',
    'color' => '#ffc145',
    'cover' => 'cover.svg',
    'order' => 30,
    'min_players_default' => 3,
    'max_players_default' => 10,
    'tables' => [],
    // Texto do botão "Como Jogar?".
    'how_to' => [
        'goal' => 'Enganar os amigos com suas afirmações e descobrir quem está mentindo.',
        'steps' => [
            'Um tema é sorteado (ex.: "Algo que já aconteceu com você").',
            'Escreva uma afirmação sobre você e marque em segredo se é VERDADE ou MENTIRA.',
            'As afirmações aparecem embaralhadas e sem autor.',
            'Vote verdade ou mentira em cada afirmação dos outros.',
            'Na revelação aparecem a resposta certa, o autor e os votos.',
        ],
        'rules' => [
            'Uma afirmação por rodada; depois de enviada, nem o texto nem a marcação mudam.',
            'Quem não escrever a tempo fica fora da votação daquela rodada.',
            'Você não vota na própria afirmação, e cada voto é definitivo.',
            'Com menos de 2 afirmações a rodada é anulada.',
        ],
        'scoring' => [
            'Cada acerto: +1 ponto.',
            'Mentira que enganou a maioria (mais da metade votou "verdade"): +2 pontos para o autor.',
            'Opcional: verdade que a maioria acertou dá +1 ponto ao autor.',
        ],
        'tips' => [
            'Verdades estranhas costumam enganar mais que mentiras óbvias.',
        ],
    ],
    'settings' => [
        'rounds' => ['label' => 'Rodadas', 'summary' => 'Rodadas', 'min' => 1, 'max' => 10, 'default' => 3],
        'write_time' => ['label' => 'Tempo para escrever (s)', 'summary' => 'Escrever', 'unit' => ' s', 'min' => 30, 'max' => 180, 'default' => 75],
        'vote_time' => ['label' => 'Tempo para votar (s)', 'summary' => 'Votar', 'unit' => ' s', 'min' => 20, 'max' => 180, 'default' => 60],
        'reveal_time' => ['label' => 'Tempo da revelação (s)', 'summary' => 'Revelação', 'unit' => ' s', 'min' => 8, 'max' => 60, 'default' => 20],
        'points_correct' => ['label' => 'Pontos por acerto', 'summary' => 'Acerto', 'unit' => ' pts', 'min' => 0, 'max' => 5, 'default' => 1],
        'points_fool' => ['label' => 'Pontos: mentira que engana a maioria', 'summary' => 'Enganou', 'unit' => ' pts', 'min' => 0, 'max' => 10, 'default' => 2],
        'truth_bonus' => ['type' => 'bool', 'label' => 'Bônus para verdade identificada', 'summary' => 'Bônus verdade', 'default' => false],
        'points_truth' => ['label' => 'Pontos do bônus de verdade', 'summary' => 'Bônus', 'unit' => ' pts', 'min' => 0, 'max' => 5, 'default' => 1],
        'categories' => ['type' => 'multi', 'label' => 'Categorias de temas', 'summary' => 'Categorias', 'options' => $categories, 'default' => array_keys($categories), 'min' => 0],
        'custom' => ['type' => 'lines', 'label' => 'Temas personalizados', 'hint' => 'um por linha', 'summary' => 'Temas', 'max_items' => 20, 'min_len' => 4, 'max_len' => 80, 'default' => []],
    ],
];
