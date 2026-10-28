<p align="center"><img src="assets/img/og-image.png" alt="PartyGames — jogos pra jogar com a galera" width="720"></p>

# PartyGames

Plataforma de jogos sociais online para jogar com os amigos, cada um no seu celular: crie uma sala
(com senha opcional), mande o código e escolha o jogo. PHP puro (POO) + MySQLi + JavaScript nativo —
sem Composer, frameworks, WebSockets ou serviços externos. Funciona em hospedagem compartilhada.

## Jogos

| Jogo | Jogadores | Resumo |
|---|---|---|
| **Quem é Mais Provável?** | 3–20 | Todos votam em segredo em quem tem mais cara de fazer algo. |
| **O Infiltrado** | 3–16 | Todos recebem a palavra secreta, menos o infiltrado. Dicas, discussão e votação. |
| **Mentira ou Verdade?** | 3–16 | Cada um escreve uma afirmação e marca em segredo se é verdade; os outros tentam acertar. |
| **A Resposta Mais Absurdamente Errada** | 3–20 | Respostas anônimas à mesma pergunta; vence a mais votada. |
| **Quem Cometeu o Crime?** | 2–10 | Investigação com papéis, pistas, interrogatórios e acusação final (10 casos). |
| **O que você faria se...** | 3–20 | Perguntas e respostas de pessoas diferentes misturadas no final (sem pontuação). |
| **O Tribunal** | 3–12 | Réu, promotor, defesa, testemunhas e juiz; o júri decide o veredito de um caso absurdo. |
| **A Bomba Relógio** | 2–12 | Cada um responde algo da categoria antes do pavio acabar; quem trava explode. |
| **Missão Secreta** | 3–12 | Cada um recebe uma missão secreta para cumprir no chat sem ser percebido. |
| **Manchete Absurda** | 3–20 | Todos escrevem a manchete de jornal mais absurda para o tema; a mais votada vira capa. |
| **Quem Sou Eu?** | 2–10 | Todos veem a sua identidade, menos você; descubra com perguntas de sim ou não. |

**Sons.** Efeitos sintetizados no navegador (Web Audio, sem arquivos de áudio) em `assets/js/sfx.js`: tic-tac nos últimos 10 segundos de cada cronômetro, fim do tempo, entrada de jogadores, troca de fase e sons próprios de cada jogo (explosão da bomba, martelo do juiz, nova pista, mensagem no chat, acerto/erro de palpite, rotativa do jornal...). O botão 🔊 na barra superior silencia (preferência salva no aparelho). Cada jogo declara `sounds: { fase: 'nome' }` no `registerGame` e pode tocar outros com `R.sfx.play('nome')`.

**Jogar em call.** Jogos com `'call' => true` no manifesto mostram no card e no "Como Jogar?" a recomendação de jogar em call (Infiltrado, Mentira ou Verdade?, Crime, Tribunal, Bomba Relógio e Quem Sou Eu?).

## Publicação (3 passos, sem instalador)

1. Envie todos os arquivos para a hospedagem (raiz do domínio ou subpasta).
2. Copie `config/config.example.php` para `config/config.php` e preencha os dados do banco
   (`host`, `port`, `name`, `user`, `pass`). Crie o banco pelo painel da hospedagem.
3. Acesse o domínio. As tabelas da plataforma e de cada jogo são criadas automaticamente.

Requisitos: PHP 7.4+ (testado no 8.2) com `mysqli` (mysqlnd); MySQL 5.7+ ou MariaDB 10.2+ (InnoDB);
Apache com `.htaccess` (para Nginx, veja **Segurança**). Se o banco estiver inacessível ou sem
permissão de `CREATE`, aparece uma mensagem objetiva sem expor credenciais; detalhes em `storage/logs/`.
Para a imagem de compartilhamento (og:image) apontar para o endereço certo, preencha `app.base_url`.

## Como se joga

**Como Jogar?:** cada jogo tem um botão com objetivo, passo a passo, regras, pontuação e dicas — no catálogo, no lobby e na barra superior durante a partida.

**Sala:** quem cria escolhe o jogo, o nome da sala, a senha (opcional), o mínimo/máximo de jogadores e
as regras do jogo. No lobby o anfitrião pode **trocar o jogo e as regras** entre as partidas. Cada partida
tem placar próprio; ao terminar, todos voltam ao lobby com o placar final e podem jogar de novo ou outro
jogo. Durante o jogo, o botão **Placar** mostra a pontuação total, a posição, os pontos da rodada e o
histórico de rodadas. "Encerrar partida" (anfitrião) volta ao lobby; no lobby, "Fechar sala" fecha a sala.

Entrada no meio do jogo: só nas fases de revelação (o jogador participa a partir da próxima rodada) —
nunca no meio de uma votação. No Crime, não há entrada durante o caso.

### Regras detalhadas (padrão; pontos configuráveis)

**Quem é Mais Provável?** — voto secreto, um por rodada, sem alteração, nunca em si mesmo. A revelação
mostra só a contagem por jogador. Os mais votados (todos, se houver empate) ganham os pontos da rodada.
Sem votos ⇒ ninguém pontua (votos nunca são inventados). Configura: rodadas, tempos, categorias,
perguntas personalizadas, pontuação liga/desliga e pontos.

**O Infiltrado** — papéis sorteados no servidor; cada jogador só recebe o próprio segredo.
Fases: papéis → dicas (uma por jogador, máx. 40 caracteres, não pode conter a palavra) → discussão
(dicas com autor) → votação → revelação. Infiltrados por rodada: o configurado, limitado a
⌊(jogadores − 1)/2⌋ (mínimo 1), sempre menos que o grupo.
*Vitória:* os k mais votados (k = nº de infiltrados) são os acusados; o **grupo vence** se forem exatamente
os infiltrados. Empate na fronteira, nenhum voto ou acusação errada ⇒ **infiltrados vencem**.
*Pontos:* vencedores recebem "grupo vence" ou "infiltrado vence"; cada membro do grupo que votou num
infiltrado ganha "voto certeiro". *Modo palavra parecida:* o infiltrado recebe uma palavra semelhante
e ninguém sabe o próprio papel.

**Mentira ou Verdade?** — afirmação + marcação secreta (verdade/mentira), envio único. Quem não escreve
fica fora da rodada. Votação com códigos aleatórios e ordem embaralhada (sem autor); um voto por
afirmação, nunca na própria. Menos de 2 afirmações ⇒ rodada anulada. *Pontos:* acerto (+1);
mentira que enganou a maioria — mais da metade dos votos foi "verdade" (+2 ao autor); bônus opcional
para verdade identificada pela maioria (+1).

**A Resposta Mais Absurdamente Errada** — uma resposta por jogador; votação anônima por código público
(o navegador não recebe autor, id nem contagem até a revelação). Votam todos os participantes, nunca na
própria resposta. Menos de 2 respostas ⇒ rodada anulada. Respostas de quem desconectou continuam valendo.
Mais votadas (empates incluídos) +3; sem votos ⇒ ninguém pontua. Revelação automática ou manual.

**Quem Cometeu o Crime?** — leitura do caso → investigação → acusação → solução.
Papéis (em ciclo): *Detetive* (perguntas de pressão exclusivas), *Perito* (laudo técnico secreto),
*Jornalista* (boatos), *Investigador* (+2 ações). Pistas: iniciais e por tempo (públicas), de papel
(só o papel), de busca (só quem vasculhou) e de interrogatório (públicas quando alguém faz a pergunta
certa). Cada pergunta/local custa 1 ação e só pode ser usado uma vez por jogador. A acusação
(suspeito + motivo) é única e secreta até a solução. *Pontos (por caso):* culpado +5, motivo +1,
primeira descoberta de pista importante +1 (opcional).
Casos incluídos: Mansão Blackwood, Expresso Estrela do Sul, Laboratório Aurora, Museu Municipal,
Teatro Real, Vinícola Serra Alta, Taça do Centenário, Hotel da Nevasca, Circo Estrela e Colégio Horizonte
(fácil, médio e difícil). O anfitrião escolhe um caso ou deixa sortear (sem repetir na mesma sala).

**O que você faria se...** — cada um completa a pergunta, responde à de outra pessoa (sorteio sem
auto-atribuição) e os pares são misturados na apresentação final.

**O Tribunal** — briefing → acusação → defesa → testemunhos → alegações finais → votação → veredito.
Papéis por número de jogadores: réu e promotor; defesa (4+), testemunha (5+), juiz (6+), 2ª testemunha
(8+); os demais são jurados. Sem defesa, o réu se defende sozinho. Etapas sem quem fale são puladas.
Só quem tem a palavra fala (10–400 caracteres, uma fala por etapa). Votam jurados, testemunhas e juiz
(voto único). Maioria decide; empate ⇒ voto do juiz, ou inocente. A verdade do caso fica no servidor até o
veredito. *Pontos:* lado vencedor (promotor ou defesa+réu) +3; jurado que acertou a verdade +1. 8 casos.

**A Bomba Relógio** — uma categoria por rodada; a vez segue a ordem sorteada. Só quem está com a bomba
responde (1–60 caracteres), e o prazo de cada vez é do servidor. Sem repetição (ignora acentos e
maiúsculas, configurável). Prazo vencido ⇒ explode: −2 e fora da rodada; quem sai da sala só deixa a
rodada. *Pontos:* +1 por resposta; sobrevivente +3. 16 categorias + personalizadas.

**Missão Secreta** — cada jogador recebe só a própria missão (palavra, item de lista, fazer alguém
perguntar ou dizer algo, número etc.) e conversa no chat do jogo. Missões objetivas são verificadas
automaticamente pelo servidor (só contam mensagens de outros/do alvo); as subjetivas vão para avaliação
da mesa. Depois, palpites sobre a missão dos outros (opções com códigos aleatórios). *Pontos:* missão
cumprida +3; palpite certo +1; missão cumprida sem ser descoberta +1.

**Manchete Absurda** — tema da seção do jornal → cada um escreve uma manchete (8–140 caracteres, envio
único) → votação anônima por código, nunca na própria → capa. Empate: capa dividida. Menos de 2
manchetes ⇒ rodada anulada. *Pontos:* capa +3; +1 por voto recebido. Revelação automática ou manual.

**Quem Sou Eu?** — cada jogador recebe uma identidade (distintas, salvo se repetidas forem permitidas).
O servidor nunca envia a própria identidade ao jogador antes de ele acertar ou da revelação. Na vez, faça
uma pergunta (3–150 caracteres) que os outros respondem com sim, não ou não sei (uma resposta cada), ou
dê um palpite (encerra a vez). Palpites limitados (3 por padrão), sem repetir; acentos e maiúsculas não
importam e apelidos são aceitos. Quem acertou é pulado nos turnos. A rodada acaba quando todos acertam
ou o tempo total termina. *Pontos:* acerto +5; acerto com até 3 perguntas +2.

## Arquitetura

```
index.php, api/index.php     entradas (página única + API JSON)
app/Core/                    configuração, MySQLi, migrações, sessão, CSRF, validação
app/Platform/                RoomEngine, AbstractGame, RoundGame, GameContext, GameRegistry, Settings
app/Repositories/            partidas, placar/histórico, rodadas/envios/votos
app/Services/                RoomService, StateBuilder, Auth, RateLimiter, Shuffler, limpeza
views/                       layout da plataforma
assets/                      CSS, platform.js, logo e ícones
database/migrations/         tabelas da plataforma
games/<Pasta>/               um jogo: game.php, src/, content/, migrations/, views/, assets/
storage/logs/                registros de erro
```

**Banco.** Plataforma: `rooms`, `players`, `game_sessions` (partidas), `score_entries` (pontos; chave
única por jogador/partida/motivo ⇒ nunca contados duas vezes), `game_events` (histórico), e as tabelas
genéricas de rodada `game_rounds` (dados públicos × secretos), `game_round_players` (papéis e segredos
individuais), `game_entries` (respostas/dicas/afirmações; únicas por jogador e rodada) e `game_votes`
(únicos por eleitor e cédula). Jogos com necessidades próprias têm tabelas prefixadas:
`crime_unlocks`, `crime_actions`, `crime_accusations` e `oqvf_*`. Os casos do Crime ficam em arquivos
(`games/CrimeMystery/cases/*.php`), validados ao carregar. Os 5 jogos da versão 4 (Tribunal, Bomba
Relógio, Missão Secreta, Manchete Absurda, Quem Sou Eu?) não criam tabelas: usam as genéricas com
tipos de envio numerados por turno/mensagem (`t12`, `m3`, `q5`, `g2`), mantendo a proteção `UNIQUE`.

**Concorrência.** Toda mudança de estado ocorre em transação com a sala bloqueada (`SELECT … FOR UPDATE`)
e é reavaliada após o bloqueio; restrições `UNIQUE` impedem envios e votos duplicados mesmo com
requisições simultâneas. Os prazos são do servidor: o navegador calcula o cronômetro a partir do horário
de término enviado, e o backend recusa envios fora do prazo. Sem WebSocket: polling a cada ~1,5 s com
versão de estado (respostas mínimas quando nada mudou); prazos vencidos são processados durante o polling.

## Criar um novo jogo

1. Crie `games/MeuJogo/game.php` com `slug`, `name`, `class`, `players`, `settings`, `tables`...
   Tipos de configuração: `int`, `select`, `bool`, `multi` (categorias) e `lines` (lista personalizada).
   O texto do botão "Como Jogar?" fica em `how_to` (`goal`, `steps`, `rules`, `scoring`, `tips`).
2. Em `games/MeuJogo/src/Game.php`, estenda `App\Platform\RoundGame` (jogos em rodadas) implementando
   `planRound()`, `step()`, `handle()` e `roundView()` — ou `AbstractGame` para fluxos livres.
   Use `$this->repo()` (envios/votos únicos), `$this->ctx->award()` (pontos idempotentes),
   `$this->ctx->event()` (histórico) e `$this->phase()` (fases e prazos).
3. Telas em `views/screens.php` (`<?= $timer ?>` insere o cronômetro) e interface em `assets/game.js`:
   `PartyGames.registerGame('meu-jogo', { phases, init(R), render(state, prev, R) })`, com
   `R.action()`, `R.ui.choices()`, `R.ui.scoreboard()`, `R.ui.submitText()`, `R.ui.setTimer()`...
4. Tabelas próprias (se precisar) em `migrations/001_schema.php`. Envie a pasta: o jogo aparece no
   catálogo e as tabelas são criadas na próxima visita.

**Novo caso do Crime:** copie um arquivo de `games/CrimeMystery/cases/`. O formato está documentado em
`src/CaseValidator.php`, que garante culpado e motivo existentes, pistas-chave acessíveis a todos (nunca
exclusivas de um papel), toda pista com um caminho de liberação e perguntas que só referenciam pistas
existentes. Casos inválidos são ignorados e registrados no log.

## Segurança

Prepared statements MySQLi; validação de tipo e limite de todos os parâmetros; texto inserido no HTML
sempre via `textContent`/`htmlspecialchars`; CSP sem scripts inline; token CSRF + verificação de
origem em toda operação que altera estado; cookies `HttpOnly`/`SameSite`/`Secure` e regeneração do ID de
sessão; senhas de sala e tokens de reconexão só como hash; limites de tentativas (senha, criação de sala,
reconexão) e de ações por jogador; erros genéricos ao público.

**O que a API nunca envia antes da hora:** papéis e palavras dos outros (Infiltrado), autores e
marcações de verdade (Mentira ou Verdade), autores e contagens (Resposta Absurda), votos individuais
(todos os jogos), solução, pistas privadas e respostas de interrogatório alheias (Crime).

As pastas internas (`config/`, `app/`, `database/`, `storage/`, `views/`, `games/*/{src,migrations,views,content,cases}`
e `games/*/game.php`) são bloqueadas por `.htaccess`. **Em Nginx**, adicione:

```nginx
location ~ ^/(config|app|database|storage|views)/ { deny all; }
location ~ ^/games/[^/]+/(src|migrations|views|content|cases|game\.php) { deny all; }
location ~ /\. { deny all; }
```

## API

`GET session · games · state · players` — `POST create_room · join_room · reconnect · configure_room ·
start_game · leave_room · end_game · game&op=<ação do jogo>`. POST envia JSON e o cabeçalho
`X-CSRF-Token`. Respostas: `{"ok":true,"data":{...}}` ou `{"ok":false,"error":{"code":"...","message":"..."}}`.

Ações por jogo: `vote` (Mais Provável) · `hint`, `vote` (Infiltrado) · `statement`, `vote` (Mentira ou
Verdade) · `answer`, `vote` (Resposta Absurda) · `ready`, `ask`, `search`, `accuse` (Crime) ·
`submit_question`, `submit_answer`, `advance` (O que você faria se) · `speak`, `vote` (Tribunal) · `answer` (Bomba Relógio) · `say`, `guess`, `evaluate` (Missão Secreta) · `headline`, `vote` (Manchete Absurda) · `ask`, `answer`, `guess` (Quem Sou Eu?) · `skip` (anfitrião encerra a fase).

## Testes realizados

Testes automatizados de API cobrindo: sala protegida por senha; início de cada jogo; votos, respostas e
acusações duplicados (inclusive 5 requisições simultâneas — só 1 aceita); voto em si mesmo e na própria
resposta; anonimato e ausência de segredos no JSON antes da revelação (papéis, palavra, autores, votos,
solução, pistas privadas); prazos recusados pelo servidor; reconexão em outra sessão com placar e
histórico preservados; transferência do anfitrião; pontuação e empates em cada jogo; rodadas anuladas e
sem votos; validação dos casos; nos 5 jogos novos: papéis e falas por etapa, veredito com desempate do juiz, turnos e explosão por prazo, respostas repetidas, verificação automática de missões e privacidade do chat de missões, manchetes anônimas e capa dividida, identidade própria ausente do JSON, limite e repetição de palpites; inicialização repetida sem alterar dados e recriação de tabela ausente.
Também foi jogada uma partida de cada jogo no navegador, em largura de celular.
