<?php
/**
 * Manifesto de "Quem Sou Eu?".
 */
defined('APP_ROOT') || exit;

$categories = [];
foreach (require __DIR__ . '/content/identities.php' as $key => $cat) {
    $categories[$key] = $cat['label'];
}

return [
    'slug' => 'quem-sou-eu',
    'class' => \Games\WhoAmI\Game::class,
    'name' => 'Quem Sou Eu?',
    'tagline' => 'Todos sabem, menos você',
    'description' => 'Cada um recebe uma identidade secreta que todos veem — menos o próprio jogador. Faça perguntas de sim ou não e descubra quem você é.',
    'players' => [2, 10],
    'duration' => '10–20 min',
    'color' => '#4fc3f7',
    'cover' => 'cover.svg',
    'order' => 110,
    'min_players_default' => 2,
    'max_players_default' => 8,
    'tables' => [],
    // Texto do botão "Como Jogar?".
    'how_to' => [
        'goal' => 'Descobrir a sua identidade secreta antes do tempo acabar.',
        'steps' => [
            'Cada jogador recebe uma identidade (personagem, animal, objeto...). Você vê a de todos, menos a sua.',
            'Na sua vez, faça uma pergunta de sim ou não (ex.: "Eu sou um animal?") — ou tente adivinhar quem você é.',
            'Os outros respondem: sim, não ou não sei.',
            'A vez passa para o próximo jogador que ainda não descobriu.',
            'A rodada termina quando todos acertarem ou o tempo acabar; aí todas as identidades são reveladas.',
        ],
        'rules' => [
            'Uma pergunta ou um palpite por vez. Palpitar encerra a sua vez.',
            'Cada jogador tem um número limitado de palpites (3 por padrão) e não pode repetir um palpite.',
            'Acentos, maiúsculas e espaços não importam no palpite.',
            'Por padrão, cada jogador recebe uma identidade diferente (dá para permitir repetidas).',
            'O servidor nunca envia a sua identidade para o seu celular antes de você acertar.',
        ],
        'scoring' => [
            'Acertar a identidade: +5 pontos.',
            'Acertar com até 3 perguntas: +2 de bônus.',
        ],
        'tips' => ['Comece pelas perguntas que dividem as possibilidades ao meio: "sou uma pessoa?", "sou de um jogo?".'],
    ],
    'settings' => [
        'rounds' => ['label' => 'Rodadas', 'summary' => 'Rodadas', 'min' => 1, 'max' => 5, 'default' => 1],
        'round_minutes' => ['label' => 'Duração da rodada (min)', 'summary' => 'Rodada', 'unit' => ' min', 'min' => 3, 'max' => 30, 'default' => 10],
        'ask_time' => ['label' => 'Tempo para perguntar (s)', 'summary' => 'Perguntar', 'unit' => ' s', 'min' => 15, 'max' => 120, 'default' => 45],
        'answer_time' => ['label' => 'Tempo para responder (s)', 'summary' => 'Responder', 'unit' => ' s', 'min' => 10, 'max' => 60, 'default' => 20],
        'max_guesses' => ['label' => 'Palpites por jogador', 'summary' => 'Palpites', 'min' => 1, 'max' => 10, 'default' => 3],
        'allow_repeat' => ['type' => 'bool', 'label' => 'Permitir identidades repetidas', 'summary' => 'Repetidas', 'default' => false],
        'points_solve' => ['label' => 'Pontos por acertar', 'summary' => 'Acerto', 'unit' => ' pts', 'min' => 1, 'max' => 10, 'default' => 5],
        'bonus_fast' => ['label' => 'Bônus por acertar com até 3 perguntas', 'summary' => 'Bônus', 'unit' => ' pts', 'min' => 0, 'max' => 5, 'default' => 2],
        'reveal_time' => ['label' => 'Tempo da revelação (s)', 'summary' => 'Revelação', 'unit' => ' s', 'min' => 8, 'max' => 60, 'default' => 20],
        'categories' => ['type' => 'multi', 'label' => 'Categorias', 'summary' => 'Categorias', 'options' => $categories, 'default' => array_keys($categories), 'min' => 1],
    ],
];
