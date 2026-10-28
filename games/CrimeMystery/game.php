<?php
/**
 * Manifesto de "Quem Cometeu o Crime?".
 */
defined('APP_ROOT') || exit;

return [
    'slug' => 'quem-cometeu-o-crime',
    'class' => \Games\CrimeMystery\Game::class,
    'name' => 'Quem Cometeu o Crime?',
    'tagline' => 'Investigação em equipe',
    'description' => 'Um caso, quatro suspeitos e pistas que aparecem aos poucos. Cada um tem um papel — detetive, perito, jornalista ou investigador. Interrogue, vasculhe e acuse o culpado.',
    'players' => [2, 10],
    // Melhor com conversa por voz: o catálogo mostra a recomendação de jogar em call.
    'call' => true,
    'duration' => '15–30 min',
    'color' => '#e84a5f',
    'cover' => 'cover.svg',
    'order' => 50,
    'min_players_default' => 2,
    'max_players_default' => 8,
    'tables' => ['crime_unlocks', 'crime_actions', 'crime_accusations'],
    // Texto do botão "Como Jogar?".
    'how_to' => [
        'goal' => 'Trabalhar em equipe para descobrir quem cometeu o crime e por quê.',
        'steps' => [
            'Leitura do caso: conheça a história, a vítima e os suspeitos. Toque em "estou pronto".',
            'Investigação: use suas ações para interrogar suspeitos e vasculhar locais. Novas pistas surgem com o tempo.',
            'Compartilhe o que descobrir com a equipe — algumas pistas só você vê.',
            'Acusação: escolha em segredo o culpado e o motivo.',
            'Solução: o culpado e a explicação completa são revelados.',
        ],
        'rules' => [
            'Papéis: Detetive (perguntas de pressão), Perito (laudo técnico), Jornalista (boatos) e Investigador (+2 ações).',
            'Cada pergunta ou local custa 1 ação e só pode ser usado uma vez por você.',
            'Algumas perguntas só aparecem depois que você tem a pista certa.',
            'Quando todos estiverem prontos (ou o tempo acabar), começa a acusação.',
            'Cada um faz uma única acusação, secreta e definitiva.',
        ],
        'scoring' => [
            'Acertar o culpado: +5 pontos.',
            'Acertar o motivo: +1 ponto.',
            'Ser o primeiro a descobrir uma pista importante: +1 ponto (opcional).',
        ],
        'tips' => [
            'Compare os álibis com os horários das pistas.',
            'Desconfie das pistas fáceis demais: o caso tem pistas falsas.',
        ],
    ],
    'settings' => [
        'case' => ['type' => 'select', 'label' => 'Caso', 'summary' => 'Caso', 'default' => 'random', 'options' => \Games\CrimeMystery\CaseLibrary::options()],
        'briefing_time' => ['label' => 'Tempo de leitura do caso (s)', 'summary' => 'Leitura', 'unit' => ' s', 'min' => 20, 'max' => 240, 'default' => 90],
        'investigation_minutes' => ['label' => 'Tempo de investigação (min)', 'summary' => 'Investigação', 'unit' => ' min', 'min' => 3, 'max' => 40, 'default' => 12],
        'actions' => ['label' => 'Ações de investigação por jogador', 'hint' => 'investigador ganha +2', 'summary' => 'Ações', 'min' => 2, 'max' => 15, 'default' => 6],
        'accuse_time' => ['label' => 'Tempo para acusar (s)', 'summary' => 'Acusação', 'unit' => ' s', 'min' => 30, 'max' => 300, 'default' => 120],
        'clue_points' => ['type' => 'bool', 'label' => 'Pontos por descobrir pistas importantes', 'summary' => 'Pontos por pista', 'default' => true],
    ],
];
