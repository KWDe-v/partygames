<?php
declare(strict_types=1);

namespace Games\CrimeMystery;

/**
 * Valida um caso antes de ele entrar no jogo. Para criar um caso novo, copie um arquivo de
 * games/CrimeMystery/cases/ e siga o formato:
 *
 *  id, title, difficulty, intro, crime, victim, location, time
 *  suspects:       id => [name, role, profile, motive, alibi]           (mínimo 3)
 *  motives:        id => texto                                          (mínimo 2, inclui o da solução)
 *  clues:          id => [title, text, unlock => [type, ...], important?]
 *                  unlock.type: start | time (at: 0–1 da investigação) | role (role: perito|jornalista)
 *                               | search (via 'locations') | interrogation (via 'unlocks' de uma pergunta)
 *  locations:      id => [name, clue]
 *  interrogations: suspeito => [[id, question, answer, unlocks?, requires?, role?], ...]  (mínimo 2 por suspeito)
 *  solution:       [culprit, motive, key_clues[], explanation]
 *  scoring:        [culprit, motive, clue]
 *
 * Coerência garantida: solução aponta para suspeito/motivo existentes; pistas-chave existem e não
 * são exclusivas de um papel (todos podem resolver); toda pista de busca/interrogatório tem um
 * caminho para ser liberada; perguntas só referenciam pistas existentes.
 */
final class CaseValidator
{
    public const ROLES = ['detetive', 'perito', 'jornalista', 'investigador'];
    private const UNLOCK_TYPES = ['start', 'time', 'role', 'search', 'interrogation'];

    /** @return string[] erros (vazio = caso válido) */
    public static function validate(array $c): array
    {
        $e = [];
        foreach (['id', 'title', 'difficulty', 'intro', 'crime', 'victim', 'location', 'time'] as $f) {
            if (!isset($c[$f]) || !is_string($c[$f]) || trim($c[$f]) === '') {
                $e[] = "campo '$f' ausente";
            }
        }
        if (isset($c['id']) && !preg_match('/^[a-z0-9_-]{2,40}$/', (string) $c['id'])) {
            $e[] = 'id inválido';
        }
        $suspects = (array) ($c['suspects'] ?? []);
        $motives = (array) ($c['motives'] ?? []);
        $clues = (array) ($c['clues'] ?? []);
        $locations = (array) ($c['locations'] ?? []);
        $interrogations = (array) ($c['interrogations'] ?? []);
        $solution = (array) ($c['solution'] ?? []);

        if (count($suspects) < 3) {
            $e[] = 'mínimo de 3 suspeitos';
        }
        foreach ($suspects as $sid => $s) {
            foreach (['name', 'role', 'profile', 'motive', 'alibi'] as $f) {
                if (empty($s[$f])) {
                    $e[] = "suspeito '$sid' sem '$f'";
                }
            }
            if (count((array) ($interrogations[$sid] ?? [])) < 2) {
                $e[] = "suspeito '$sid' precisa de pelo menos 2 perguntas de interrogatório";
            }
        }
        if (count($motives) < 2) {
            $e[] = 'mínimo de 2 motivos para a acusação';
        }

        $byInterrogation = [];
        $questionIds = [];
        foreach ($interrogations as $sid => $questions) {
            if (!isset($suspects[$sid])) {
                $e[] = "interrogatório de suspeito inexistente '$sid'";
            }
            foreach ((array) $questions as $q) {
                $qid = (string) ($q['id'] ?? '');
                if ($qid === '' || empty($q['question']) || empty($q['answer'])) {
                    $e[] = "pergunta incompleta em '$sid'";
                    continue;
                }
                if (isset($questionIds[$qid])) {
                    $e[] = "id de pergunta repetido '$qid'";
                }
                $questionIds[$qid] = true;
                foreach (['unlocks', 'requires'] as $ref) {
                    if (isset($q[$ref]) && !isset($clues[$q[$ref]])) {
                        $e[] = "pergunta '$qid' referencia pista inexistente '{$q[$ref]}'";
                    }
                }
                if (isset($q['role']) && !in_array($q['role'], self::ROLES, true)) {
                    $e[] = "pergunta '$qid' com papel inválido";
                }
                if (isset($q['unlocks'])) {
                    $byInterrogation[$q['unlocks']] = true;
                }
            }
        }
        $byLocation = [];
        foreach ($locations as $lid => $l) {
            if (empty($l['name']) || !isset($clues[$l['clue'] ?? ''])) {
                $e[] = "local '$lid' inválido";
            } else {
                $byLocation[$l['clue']] = true;
            }
        }
        foreach ($clues as $cid => $clue) {
            $type = $clue['unlock']['type'] ?? '';
            if (empty($clue['title']) || empty($clue['text'])) {
                $e[] = "pista '$cid' sem título ou texto";
            }
            if (!in_array($type, self::UNLOCK_TYPES, true)) {
                $e[] = "pista '$cid' com liberação inválida";
            } elseif ($type === 'time' && (!isset($clue['unlock']['at']) || $clue['unlock']['at'] <= 0 || $clue['unlock']['at'] >= 1)) {
                $e[] = "pista '$cid': 'at' deve estar entre 0 e 1";
            } elseif ($type === 'role' && !in_array($clue['unlock']['role'] ?? '', self::ROLES, true)) {
                $e[] = "pista '$cid' com papel inválido";
            } elseif ($type === 'search' && !isset($byLocation[$cid])) {
                $e[] = "pista de busca '$cid' não está em nenhum local";
            } elseif ($type === 'interrogation' && !isset($byInterrogation[$cid])) {
                $e[] = "pista de interrogatório '$cid' não é liberada por nenhuma pergunta";
            }
        }

        if (!isset($suspects[$solution['culprit'] ?? ''])) {
            $e[] = 'solução: culpado inexistente';
        }
        if (!isset($motives[$solution['motive'] ?? ''])) {
            $e[] = 'solução: motivo inexistente';
        }
        if (empty($solution['explanation'])) {
            $e[] = 'solução sem explicação';
        }
        $keys = (array) ($solution['key_clues'] ?? []);
        if (count($keys) < 2) {
            $e[] = 'solução precisa de pelo menos 2 pistas-chave';
        }
        foreach ($keys as $k) {
            if (!isset($clues[$k])) {
                $e[] = "pista-chave inexistente '$k'";
            } elseif (($clues[$k]['unlock']['type'] ?? '') === 'role') {
                $e[] = "pista-chave '$k' não pode ser exclusiva de um papel";
            }
        }
        foreach (['culprit', 'motive', 'clue'] as $s) {
            if (!isset($c['scoring'][$s]) || !is_int($c['scoring'][$s]) || $c['scoring'][$s] < 0) {
                $e[] = "pontuação '$s' ausente";
            }
        }
        return $e;
    }
}
