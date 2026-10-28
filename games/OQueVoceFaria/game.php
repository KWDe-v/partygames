<?php
/**
 * Manifesto do jogo "O que você faria se...".
 * Valores de 'settings' podem ter o padrão alterado em config/config.php:
 *   'games' => ['o-que-voce-faria-se' => ['question_time' => 90, 'question_max_length' => 200]]
 */
defined('APP_ROOT') || exit;

return [
    'slug' => 'o-que-voce-faria-se',
    'class' => \Games\OQueVoceFaria\Game::class,
    'name' => 'O que você faria se...',
    'tagline' => 'Perguntas e respostas embaralhadas',
    'description' => 'Cada um completa uma pergunta, responde à de outra pessoa sem saber de quem é — e no final tudo é misturado em combinações absurdas.',
    'players' => [3, 20],
    'duration' => '10–20 min',
    'color' => '#ff5d73',
    'cover' => 'cover.svg',
    'order' => 60,
    'scoring' => false, // jogo sem pontuação: só diversão
    'min_players_default' => 3,
    'max_players_default' => 10,
    // Tabelas do jogo (a plataforma confere se existem; criadas pelas migrações em migrations/).
    'tables' => ['oqvf_rounds', 'oqvf_participants', 'oqvf_questions', 'oqvf_answers', 'oqvf_results'],
    // Configurações escolhidas por quem cria a sala (validadas pela plataforma).
    // Texto do botão "Como Jogar?".
    'how_to' => [
        'goal' => 'Criar perguntas e respostas que, embaralhadas, viram combinações absurdas.',
        'steps' => [
            'Complete a pergunta "O que você faria se..." do seu jeito.',
            'Depois de embaralhadas, cada um responde à pergunta de outra pessoa, sem saber de quem é.',
            'No final, as perguntas aparecem com respostas de OUTRAS perguntas — uma de cada vez.',
        ],
        'rules' => [
            'Ninguém recebe a própria pergunta, e cada pergunta vai para exatamente uma pessoa.',
            'Quem não enviar a pergunta a tempo fica fora da rodada.',
            'Pergunta e resposta não podem ser editadas depois de enviadas.',
            'São necessárias pelo menos 3 perguntas para a rodada continuar.',
        ],
        'scoring' => [
            'Este jogo não tem pontuação: é só diversão.',
        ],
        'tips' => [
            'Escreva perguntas bem diferentes umas das outras: as combinações ficam mais engraçadas.',
        ],
    ],
    'settings' => [
        'question_time' => ['label' => 'Tempo para perguntar (s)', 'summary' => 'Perguntar', 'unit' => ' s', 'min' => 20, 'max' => 300, 'default' => 60],
        'answer_time' => ['label' => 'Tempo para responder (s)', 'summary' => 'Responder', 'unit' => ' s', 'min' => 20, 'max' => 300, 'default' => 60],
        'result_time' => ['label' => 'Segundos por resultado', 'hint' => '0 = manual', 'summary' => 'Resultados', 'unit' => ' s cada', 'zero' => 'manual', 'min' => 0, 'max' => 60, 'default' => 12],
        'max_rounds' => ['label' => 'Rodadas', 'hint' => '0 = sem limite', 'summary' => 'Rodadas', 'zero' => 'sem limite', 'min' => 0, 'max' => 50, 'default' => 0],
    ],
];
