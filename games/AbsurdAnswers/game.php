<?php
/**
 * Manifesto de "A Resposta Mais Absurdamente Errada".
 */
defined('APP_ROOT') || exit;

$categories = [];
foreach (require __DIR__ . '/content/questions.php' as $key => $cat) {
    $categories[$key] = $cat['label'];
}

return [
    'slug' => 'resposta-absurda',
    'class' => \Games\AbsurdAnswers\Game::class,
    'name' => 'A Resposta Mais Absurdamente Errada',
    'tagline' => 'Quanto pior, melhor',
    'description' => 'Todos recebem a mesma pergunta e escrevem a resposta mais absurda possível. A turma vota anonimamente na melhor — os autores só aparecem no fim.',
    'players' => [3, 20],
    'duration' => '10–20 min',
    'color' => '#ff8a3d',
    'cover' => 'cover.svg',
    'order' => 40,
    'min_players_default' => 3,
    'max_players_default' => 12,
    'tables' => [],
    // Texto do botão "Como Jogar?".
    'how_to' => [
        'goal' => 'Escrever a resposta mais engraçada, absurda ou inesperada — e ganhar os votos da turma.',
        'steps' => [
            'Todos recebem a mesma pergunta.',
            'Cada um escreve uma resposta. Quanto mais absurda, melhor!',
            'As respostas aparecem embaralhadas e sem autor.',
            'Vote na resposta que você achou a melhor.',
            'Na revelação aparecem os votos e os autores.',
        ],
        'rules' => [
            'Uma resposta por jogador, sem edição depois de enviada.',
            'Você não pode votar na própria resposta; o voto é único e definitivo.',
            'Ninguém sabe quem escreveu o quê até o fim da votação.',
            'Com menos de 2 respostas a rodada é anulada.',
        ],
        'scoring' => [
            'Resposta mais votada: +3 pontos para o autor.',
            'Empate no primeiro lugar: todos os empatados ganham.',
            'Ninguém votou: ninguém pontua.',
        ],
        'tips' => [
            'Respostas curtas e inesperadas costumam arrancar mais votos.',
        ],
    ],
    'settings' => [
        'rounds' => ['label' => 'Rodadas', 'summary' => 'Rodadas', 'min' => 1, 'max' => 20, 'default' => 5],
        'answer_time' => ['label' => 'Tempo para responder (s)', 'summary' => 'Responder', 'unit' => ' s', 'min' => 30, 'max' => 180, 'default' => 60],
        'vote_time' => ['label' => 'Tempo para votar (s)', 'summary' => 'Votar', 'unit' => ' s', 'min' => 15, 'max' => 120, 'default' => 40],
        'reveal_time' => ['label' => 'Tempo da revelação (s)', 'hint' => '0 = o anfitrião avança', 'summary' => 'Revelação', 'unit' => ' s', 'zero' => 'manual', 'min' => 0, 'max' => 60, 'default' => 15],
        'points_winner' => ['label' => 'Pontos para a mais votada', 'summary' => 'Vencedor', 'unit' => ' pts', 'min' => 0, 'max' => 10, 'default' => 3],
        'categories' => ['type' => 'multi', 'label' => 'Categorias', 'summary' => 'Categorias', 'options' => $categories, 'default' => array_keys($categories), 'min' => 0],
        'custom' => ['type' => 'lines', 'label' => 'Perguntas personalizadas', 'hint' => 'uma por linha', 'summary' => 'Perguntas', 'max_items' => 40, 'min_len' => 8, 'max_len' => 160, 'default' => []],
    ],
];
