<?php
/**
 * Manifesto de "O Infiltrado".
 */
defined('APP_ROOT') || exit;

$categories = [];
foreach (require __DIR__ . '/content/words.php' as $key => $cat) {
    $categories[$key] = $cat['label'];
}

return [
    'slug' => 'o-infiltrado',
    'class' => \Games\Infiltrator\Game::class,
    'name' => 'O Infiltrado',
    'tagline' => 'Descubra quem não sabe a palavra',
    'description' => 'Todos recebem a palavra secreta — menos o infiltrado. Cada um dá uma dica, a turma discute e vota para desmascarar o impostor.',
    'players' => [3, 16],
    // Melhor com conversa por voz: o catálogo mostra a recomendação de jogar em call.
    'call' => true,
    'duration' => '10–20 min',
    'color' => '#8b6cff',
    'cover' => 'cover.svg',
    'order' => 20,
    'min_players_default' => 4,
    'max_players_default' => 10,
    'tables' => [],
    // Texto do botão "Como Jogar?".
    'how_to' => [
        'goal' => 'Grupo: descobrir quem é o infiltrado. Infiltrado: se misturar sem ser descoberto.',
        'steps' => [
            'Papéis: cada um vê em segredo a palavra — menos o infiltrado, que só sabe a categoria.',
            'Dicas: cada jogador escreve uma dica curta ligada à palavra.',
            'Discussão: as dicas aparecem com o nome de quem escreveu. Conversem!',
            'Votação: vote em quem você acha que é o infiltrado.',
            'Revelação: a palavra e os infiltrados são mostrados.',
        ],
        'rules' => [
            'A dica não pode conter a palavra secreta e tem no máximo 40 caracteres.',
            'Uma dica e um voto por jogador; você não pode votar em si mesmo.',
            'Com muitos jogadores pode haver mais de um infiltrado (sempre menos que o grupo).',
            'Modo "palavra parecida": o infiltrado recebe uma palavra semelhante e ninguém sabe o próprio papel.',
        ],
        'scoring' => [
            'O grupo vence se os mais votados forem exatamente os infiltrados: cada membro do grupo ganha 2 pontos.',
            'Empate, nenhum voto ou acusação errada: cada infiltrado ganha 3 pontos.',
            'Quem do grupo votou em um infiltrado ganha +1 ponto.',
        ],
        'tips' => [
            'Grupo: dê dicas específicas o bastante para os aliados, mas vagas para o infiltrado.',
            'Infiltrado: preste atenção nas primeiras dicas para adivinhar a palavra.',
        ],
    ],
    'settings' => [
        'mode' => ['type' => 'select', 'label' => 'Modo', 'summary' => 'Modo', 'default' => 'classic', 'options' => [
            'classic' => 'Clássico (o infiltrado sabe que é)',
            'similar' => 'Palavra parecida (o infiltrado não sabe)',
        ]],
        'infiltrators' => ['label' => 'Infiltrados por rodada', 'hint' => 'ajustado ao nº de jogadores', 'summary' => 'Infiltrados', 'min' => 1, 'max' => 5, 'default' => 1],
        'rounds' => ['label' => 'Rodadas', 'summary' => 'Rodadas', 'min' => 1, 'max' => 20, 'default' => 5],
        'hint_time' => ['label' => 'Tempo para a dica (s)', 'summary' => 'Dica', 'unit' => ' s', 'min' => 20, 'max' => 120, 'default' => 45],
        'discussion_time' => ['label' => 'Tempo de discussão (s)', 'hint' => '0 = sem discussão', 'summary' => 'Discussão', 'unit' => ' s', 'zero' => 'sem', 'min' => 0, 'max' => 300, 'default' => 60],
        'vote_time' => ['label' => 'Tempo para votar (s)', 'summary' => 'Votar', 'unit' => ' s', 'min' => 15, 'max' => 120, 'default' => 40],
        'reveal_time' => ['label' => 'Tempo da revelação (s)', 'summary' => 'Revelação', 'unit' => ' s', 'min' => 5, 'max' => 60, 'default' => 15],
        'points_group' => ['label' => 'Pontos: grupo vence', 'summary' => 'Grupo vence', 'unit' => ' pts', 'min' => 0, 'max' => 10, 'default' => 2],
        'points_infiltrator' => ['label' => 'Pontos: infiltrado vence', 'summary' => 'Infiltrado vence', 'unit' => ' pts', 'min' => 0, 'max' => 10, 'default' => 3],
        'points_vote' => ['label' => 'Pontos: voto certeiro', 'summary' => 'Voto certeiro', 'unit' => ' pts', 'min' => 0, 'max' => 5, 'default' => 1],
        'categories' => ['type' => 'multi', 'label' => 'Categorias de palavras', 'summary' => 'Categorias', 'options' => $categories, 'default' => array_keys($categories), 'min' => 1],
    ],
];
