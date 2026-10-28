<?php
/**
 * Identidades de "Quem Sou Eu?" por categoria: [nome, [outras formas aceitas no palpite]].
 * Os palpites são comparados sem acentos, maiúsculas, espaços e pontuação.
 */
defined('APP_ROOT') || exit;

return [
    'anime' => [
        'label' => 'Personagens de anime',
        'items' => [
            ['Naruto', ['Naruto Uzumaki']], ['Goku', ['Son Goku', 'Kakaroto']], ['Luffy', ['Monkey D. Luffy']], ['Pikachu', []],
            ['Sailor Moon', ['Usagi']], ['Tanjiro', ['Tanjiro Kamado']], ['Eren', ['Eren Yeager', 'Eren Jaeger']], ['Vegeta', []],
            ['Sasuke', ['Sasuke Uchiha']], ['Doraemon', []], ['Totoro', []], ['Light Yagami', ['Light', 'Kira']],
        ],
    ],
    'games' => [
        'label' => 'Personagens de videogames',
        'items' => [
            ['Mario', ['Super Mario']], ['Sonic', []], ['Link', []], ['Pac-Man', ['Pacman']], ['Kratos', []], ['Lara Croft', ['Lara']],
            ['Steve', ['Steve do Minecraft']], ['Donkey Kong', []], ['Kirby', []], ['Master Chief', []], ['Zelda', ['Princesa Zelda']], ['Yoshi', []],
        ],
    ],
    'filmes' => [
        'label' => 'Personagens de filmes e séries',
        'items' => [
            ['Harry Potter', ['Harry']], ['Darth Vader', ['Vader']], ['Homer Simpson', ['Homer']], ['Shrek', []], ['Batman', ['Bruce Wayne']],
            ['Homem-Aranha', ['Spider-Man', 'Peter Parker']], ['Elsa', []], ['Bob Esponja', ['SpongeBob']], ['Sherlock Holmes', ['Sherlock']],
            ['Chaves', ['El Chavo']], ['Jack Sparrow', ['Capitão Jack Sparrow']], ['Wandinha', ['Wednesday', 'Wandinha Addams']],
        ],
    ],
    'animais' => [
        'label' => 'Animais',
        'items' => [
            ['Leão', []], ['Girafa', []], ['Pinguim', []], ['Elefante', []], ['Polvo', []], ['Tubarão', []], ['Coruja', []],
            ['Camaleão', []], ['Canguru', []], ['Bicho-preguiça', ['Preguiça']], ['Tartaruga', []], ['Golfinho', []],
        ],
    ],
    'profissoes' => [
        'label' => 'Profissões',
        'items' => [
            ['Bombeiro', ['Bombeira']], ['Astronauta', []], ['Médico', ['Médica']], ['Professor', ['Professora']], ['Chef de cozinha', ['Chef', 'Cozinheiro']],
            ['Palhaço', []], ['Piloto de avião', ['Piloto']], ['Dentista', []], ['Detetive', []], ['Jardineiro', ['Jardineira']],
            ['Youtuber', ['Influenciador']], ['Mágico', []],
        ],
    ],
    'objetos' => [
        'label' => 'Objetos',
        'items' => [
            ['Guarda-chuva', []], ['Escova de dentes', []], ['Geladeira', []], ['Controle remoto', []], ['Travesseiro', []],
            ['Óculos', []], ['Chave', []], ['Vassoura', []], ['Relógio', []], ['Liquidificador', []], ['Mochila', []], ['Lanterna', []],
        ],
    ],
    'ragnarok' => [
        'label' => 'Personagens e monstros de Ragnarok',
        'items' => [
            ['Poring', []], ['Lunático', ['Lunatic']], ['Baphomet', []], ['Osíris', ['Osiris']], ['Drake', []], ['Angeling', []],
            ['Deviling', []], ['Doppelganger', []], ['Orc Herói', ['Orc Hero']], ['Maya', []], ['Fabre', []], ['Sohee', []],
        ],
    ],
];
