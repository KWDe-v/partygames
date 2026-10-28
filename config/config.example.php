<?php
/**
 * PartyGames — arquivo de configuração de exemplo.
 *
 * 1. Copie este arquivo para config/config.php
 * 2. Preencha os dados de acesso ao MySQL/MariaDB fornecidos pela hospedagem.
 * 3. Acesse o site: as tabelas serão criadas automaticamente na primeira visita.
 *
 * Todas as chaves de "platform", "games" e "security" são opcionais: se forem omitidas,
 * os valores padrão (mostrados abaixo) serão usados.
 */
defined('APP_ROOT') || exit;

return [
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'nome_do_banco',
        'user'    => 'usuario_do_banco',
        'pass'    => 'senha_do_banco',
        'charset' => 'utf8mb4',
        // Tenta executar CREATE DATABASE caso o banco não exista.
        // Normalmente só funciona em ambiente local; em hospedagens crie o banco pelo painel.
        'auto_create_database' => false,
    ],

    'app' => [
        // Endereço público do jogo (ex.: https://meusite.com/jogo/). Usado na og:image ao compartilhar
        // o link em redes sociais. Vazio = detectado automaticamente.
        'base_url' => '',
        // Em produção mantenha false. Com true, a página de erro mostra detalhes técnicos.
        'debug'    => false,
        'timezone' => 'America/Sao_Paulo',
        // null = detecta HTTPS automaticamente; true/false força o atributo "secure" do cookie.
        'secure_cookies' => null,
        // Somente ative se o site estiver atrás de um proxy confiável (Cloudflare, load balancer).
        'trust_proxy_headers' => false,
    ],

    // Regras comuns a todos os jogos da plataforma.
    'platform' => [
        'name'                  => 'PartyGames', // nome exibido no site
        'max_players_limit'     => 20,   // teto de jogadores por sala (cada jogo também tem o seu)
        'poll_interval_ms'      => 1500, // intervalo de atualização nas fases ativas
        'offline_after'         => 15,   // segundos sem contato até o jogador aparecer como desconectado
        'abandon_after'         => 180,  // segundos sem contato até o jogador ser considerado fora da sala
        'host_transfer_after'   => 25,   // segundos com o anfitrião desconectado até transferir o controle
        'room_idle_expire'      => 1800, // sala sem nenhum jogador ativo por esse tempo é encerrada
        'cleanup_finished_after' => 172800, // salas encerradas são apagadas depois desse tempo (s)
    ],

    // Ajustes por jogo (opcionais). A chave é o "slug" definido no game.php de cada jogo.
    // Campos de 'settings' do manifesto mudam o valor padrão do formulário de criação de sala.
    'games' => [
        'o-que-voce-faria-se' => [
            // 'question_time'       => 60,  // segundos para escrever a pergunta
            // 'answer_time'         => 60,  // segundos para responder
            // 'result_time'         => 12,  // segundos por resultado (0 = o anfitrião avança)
            // 'shuffle_seconds'     => 4,   // tela "Embaralhando perguntas" (0 = sem animação)
            // 'question_min_length' => 5,
            // 'question_max_length' => 250,
            // 'answer_min_length'   => 2,
            // 'answer_max_length'   => 250,
        ],
    ],

    'security' => [
        'join_max_attempts'      => 8,   // senhas erradas por IP e sala
        'join_window'            => 300,
        'create_max_attempts'    => 10,  // salas criadas por IP
        'create_window'          => 600,
    ],
];
