<?php
/**
 * Manifesto de "Missão Secreta".
 */
defined('APP_ROOT') || exit;

return [
    'slug' => 'missao-secreta',
    'class' => \Games\SecretMission\Game::class,
    'name' => 'Missão Secreta',
    'tagline' => 'Cumpra sem ser descoberto',
    'description' => 'Cada um recebe uma missão secreta — como fazer alguém escrever "banana". Conversem no chat, cumpram as missões disfarçadamente e tentem adivinhar as dos outros.',
    'players' => [3, 12],
    'duration' => '8–20 min',
    'color' => '#3dd68c',
    'cover' => 'cover.svg',
    'order' => 90,
    'min_players_default' => 3,
    'max_players_default' => 10,
    'tables' => [],
    // Texto do botão "Como Jogar?".
    'how_to' => [
        'goal' => 'Cumprir sua missão secreta sem que os outros percebam qual era.',
        'steps' => [
            'Missão: cada um recebe em segredo uma missão (ex.: "Faça alguém escrever banana").',
            'Bate-papo: conversem sobre o assunto sugerido e tentem cumprir as missões de forma disfarçada.',
            'Palpites: adivinhe, numa lista, qual era a missão de cada jogador.',
            'Avaliação: missões subjetivas ("faça alguém pedir desculpas") são julgadas pelos outros jogadores.',
            'Revelação: todas as missões, quem cumpriu e quem acertou os palpites.',
        ],
        'rules' => [
            'Ninguém vê a missão dos outros até a revelação — nem pela tela, nem pelos dados do servidor.',
            'Missões de palavra, cor, animal, pergunta, número ou apelido são verificadas automaticamente nas mensagens do chat.',
            'Missões subjetivas valem se a maioria dos outros jogadores votar "cumprida".',
            'Escrever a palavra da sua própria missão não conta: tem que ser outra pessoa.',
            'Palpites e avaliações são secretos e só podem ser enviados uma vez.',
        ],
        'scoring' => [
            'Missão cumprida: +3 pontos.',
            'Palpite certo sobre a missão de alguém: +1 ponto.',
            'Missão cumprida sem ninguém adivinhar: +1 ponto de bônus.',
        ],
        'tips' => ['Puxe o assunto aos poucos — pedir a palavra direto entrega sua missão!'],
    ],
    'settings' => [
        'rounds' => ['label' => 'Rodadas', 'summary' => 'Rodadas', 'min' => 1, 'max' => 5, 'default' => 2],
        'chat_time' => ['label' => 'Tempo de bate-papo (s)', 'summary' => 'Bate-papo', 'unit' => ' s', 'min' => 60, 'max' => 600, 'default' => 180],
        'guessing' => ['type' => 'bool', 'label' => 'Palpites sobre as missões dos outros', 'summary' => 'Palpites', 'default' => true],
        'guess_time' => ['label' => 'Tempo para os palpites (s)', 'summary' => 'Palpitar', 'unit' => ' s', 'min' => 20, 'max' => 120, 'default' => 60],
        'eval_time' => ['label' => 'Tempo para avaliar missões (s)', 'summary' => 'Avaliar', 'unit' => ' s', 'min' => 15, 'max' => 90, 'default' => 30],
        'reveal_time' => ['label' => 'Tempo da revelação (s)', 'summary' => 'Revelação', 'unit' => ' s', 'min' => 8, 'max' => 60, 'default' => 25],
        'points_mission' => ['label' => 'Pontos por missão cumprida', 'summary' => 'Missão', 'unit' => ' pts', 'min' => 0, 'max' => 10, 'default' => 3],
        'points_guess' => ['label' => 'Pontos por palpite certo', 'summary' => 'Palpite', 'unit' => ' pts', 'min' => 0, 'max' => 5, 'default' => 1],
        'points_stealth' => ['label' => 'Bônus por não ser descoberto', 'summary' => 'Disfarce', 'unit' => ' pts', 'min' => 0, 'max' => 5, 'default' => 1],
    ],
];
