<?php
/**
 * Temas de "Manchete Absurda" por editoria: [tema, exemplo de manchete].
 */
defined('APP_ROOT') || exit;

return [
    'ciencia' => [
        'label' => 'Ciência',
        'items' => [
            ['Uma descoberta científica inútil', 'Cientistas comprovam que meias somem por preguiça própria'],
            ['Uma invenção que ninguém pediu', 'Startup lança torradeira com Wi-Fi que só torra às terças'],
            ['Um experimento que deu errado', 'Laboratório tenta clonar ovelha e cria uma cadeira de praia'],
            ['Uma nova lei da física', 'Física quântica explica por que a fila ao lado anda mais rápido'],
        ],
    ],
    'cotidiano' => [
        'label' => 'Cotidiano',
        'items' => [
            ['Um acontecimento absurdo em um supermercado', 'Carrinho de compras foge e é visto em lua de mel com cesta'],
            ['Uma situação inexplicável em uma escola', 'Quadro-negro pede demissão alegando excesso de giz'],
            ['Um caos no trânsito', 'Semáforo fica indeciso e libera os dois lados ao mesmo tempo'],
            ['Um mistério no condomínio', 'Síndico descobre que o elevador fazia hora extra escondido'],
        ],
    ],
    'animais' => [
        'label' => 'Animais',
        'items' => [
            ['Uma notícia sobre animais', 'Gato é eleito síndico e proíbe segundas-feiras no prédio'],
            ['Um animal com um talento estranho', 'Papagaio aprende a imitar notificação e enlouquece família'],
            ['Uma rebelião no zoológico', 'Pinguins exigem ar-condicionado e ameaçam greve'],
        ],
    ],
    'trabalho' => [
        'label' => 'Trabalho',
        'items' => [
            ['Um problema no trabalho', 'Reunião que podia ser um e-mail é tombada como patrimônio histórico'],
            ['Uma demissão inesperada', 'Funcionário é demitido por responder "ok" com ponto final'],
            ['Uma nova regra da empresa', 'Empresa proíbe café e produtividade cai para números negativos'],
        ],
    ],
    'tecnologia' => [
        'label' => 'Tecnologia',
        'items' => [
            ['Uma notícia sobre inteligência artificial', 'IA se recusa a trabalhar sem plano odontológico'],
            ['Uma falha no aplicativo', 'App de mapas manda motoristas para a casa da sogra de propósito'],
            ['Um celular com vida própria', 'Corretor automático assume o controle e declara independência'],
        ],
    ],
    'games' => [
        'label' => 'Games e cultura pop',
        'items' => [
            ['Um acontecimento estranho no mundo dos games', 'Chefão final pede para ser derrotado logo porque tem compromisso'],
            ['Um personagem que saiu do jogo', 'Encanador famoso é visto consertando pia de verdade em Niterói'],
            ['Uma polêmica de streaming', 'Série cancelada processa os fãs por abandono'],
        ],
    ],
    'esporte' => [
        'label' => 'Esporte',
        'items' => [
            ['Um jogo de futebol bizarro', 'Bola desiste da partida no intervalo e vai para casa'],
            ['Uma nova modalidade olímpica', 'Comitê aprova corrida de cadeira de escritório como esporte oficial'],
        ],
    ],
];
