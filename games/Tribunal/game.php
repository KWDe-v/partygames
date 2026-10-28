<?php
/**
 * Manifesto de "O Tribunal".
 */
defined('APP_ROOT') || exit;

return [
    'slug' => 'o-tribunal',
    'class' => \Games\Tribunal\Game::class,
    'name' => 'O Tribunal',
    'tagline' => 'Julgamentos absurdos',
    'description' => 'Um caso ridículo, um réu, um promotor e uma defesa. Testemunhas depõem e o júri decide em segredo: culpado ou inocente?',
    'players' => [3, 12],
    // Melhor com conversa por voz: o catálogo mostra a recomendação de jogar em call.
    'call' => true,
    'duration' => '10–25 min',
    'color' => '#c9a227',
    'cover' => 'cover.svg',
    'order' => 70,
    'min_players_default' => 4,
    'max_players_default' => 10,
    'tables' => [],
    // Texto do botão "Como Jogar?".
    'how_to' => [
        'goal' => 'Promotoria e defesa disputam o veredito; o júri tenta descobrir a verdade.',
        'steps' => [
            'Papéis: cada um recebe um papel e informações secretas sobre o caso.',
            'Acusação: o promotor apresenta o caso contra o réu.',
            'Defesa: o advogado (ou o próprio réu, com poucos jogadores) defende o réu.',
            'Depoimentos: as testemunhas contam o que "viram".',
            'Palavra final: o réu se defende uma última vez.',
            'Votação: jurados e testemunhas votam em segredo — culpado ou inocente.',
            'Veredito: o resultado e a verdade do caso são revelados.',
        ],
        'rules' => [
            'Papéis conforme o número de jogadores: réu e promotor sempre; defesa a partir de 4; testemunha a partir de 5; juiz a partir de 6; os demais são jurados.',
            'Só o réu sabe se é culpado ou inocente. As testemunhas recebem fatos verdadeiros.',
            'Cada papel fala uma vez na sua etapa; quem não falar no prazo é pulado.',
            'Votam jurados e testemunhas, uma vez, em segredo. Empate: decide o voto do juiz; sem juiz, o réu é absolvido.',
        ],
        'scoring' => [
            'Culpado: o promotor ganha 3 pontos. Inocente: defesa e réu ganham 3 pontos.',
            'Quem votou de acordo com a verdade do caso ganha 1 ponto.',
        ],
        'tips' => [
            'Jurados: comparem os depoimentos — as testemunhas não mentem.',
            'Réu culpado? Capriche no teatro.',
        ],
    ],
    'settings' => [
        'rounds' => ['label' => 'Casos (rodadas)', 'summary' => 'Casos', 'min' => 1, 'max' => 8, 'default' => 2],
        'speech_time' => ['label' => 'Tempo de cada fala (s)', 'summary' => 'Falas', 'unit' => ' s', 'min' => 30, 'max' => 180, 'default' => 75],
        'vote_time' => ['label' => 'Tempo para votar (s)', 'summary' => 'Votação', 'unit' => ' s', 'min' => 15, 'max' => 120, 'default' => 40],
        'reveal_time' => ['label' => 'Tempo do veredito (s)', 'summary' => 'Veredito', 'unit' => ' s', 'min' => 8, 'max' => 60, 'default' => 20],
        'points_side' => ['label' => 'Pontos para o lado vencedor', 'summary' => 'Lado vencedor', 'unit' => ' pts', 'min' => 0, 'max' => 10, 'default' => 3],
        'points_truth' => ['label' => 'Pontos por voto de acordo com a verdade', 'summary' => 'Voto certo', 'unit' => ' pts', 'min' => 0, 'max' => 5, 'default' => 1],
    ],
];
