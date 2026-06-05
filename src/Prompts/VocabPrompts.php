<?php

namespace Platform\Vocab\Prompts;

class VocabPrompts
{
    public static function generateVocab(string $topic, string $sourceLang, string $targetLang, string $level, int $count): string
    {
        return <<<PROMPT
Du bist ein Sprachlehrer und Vokabelexperte. Erstelle eine Vokabelliste zum Thema "{$topic}".

Sprachen: {$sourceLang} → {$targetLang}
Niveau: {$level}
Anzahl: {$count} Vokabeln

Antworte NUR mit einem JSON-Array. Jedes Element hat folgende Felder:
- "term": Wort/Phrase in der Zielsprache ({$targetLang})
- "translation": Übersetzung in der Ausgangssprache ({$sourceLang})
- "gender": Genus ("m", "f", "n") oder null bei Verben/Adjektiven
- "plural": Pluralform oder null
- "word_type": Wortart ("noun", "verb", "adjective", "adverb", "phrase", "preposition", "conjunction")
- "example_sentence": Ein Beispielsatz in der Zielsprache
- "pronunciation": Aussprache-Hinweis oder null
- "notes": Eselsbrücke oder Lernhinweis oder null

Regeln:
- Wähle praxisrelevante, häufig verwendete Vokabeln
- Passe Schwierigkeit an das Niveau ({$level}) an
- Beispielsätze sollen kurz und verständlich sein
- Bei Nomen immer Genus angeben (wenn in der Zielsprache relevant)
- Antworte NUR mit dem JSON-Array, kein weiterer Text

JSON:
PROMPT;
    }

    public static function quizTranslate(array $entries, string $direction, string $sourceLang, string $targetLang, int $count): string
    {
        $entriesJson = json_encode($entries, JSON_UNESCAPED_UNICODE);
        $fromLang = $direction === 'source_to_target' ? $sourceLang : $targetLang;
        $toLang = $direction === 'source_to_target' ? $targetLang : $sourceLang;
        $fromField = $direction === 'source_to_target' ? 'translation' : 'term';

        return <<<PROMPT
Du erstellst eine Übersetzungs-Abfrage. Wähle {$count} Vokabeln aus der Liste und formuliere Übersetzungsfragen.

Richtung: {$fromLang} → {$toLang}
Zeige das Wort in {$fromLang}, der Benutzer muss in {$toLang} übersetzen.

Vokabeln:
{$entriesJson}

Antworte NUR mit einem JSON-Array. Jedes Element:
- "entry_id": ID des Eintrags
- "question": Das zu übersetzende Wort/Phrase (in {$fromLang})
- "correct_answer": Die korrekte Übersetzung (in {$toLang})
- "hint": Ein kurzer Hinweis (z.B. Wortart, Genus)

Wähle zufällig {$count} verschiedene Einträge. JSON:
PROMPT;
    }

    public static function quizFillBlank(array $entries, int $count): string
    {
        $entriesJson = json_encode($entries, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Du erstellst eine Lückentext-Abfrage. Verwende die Beispielsätze der Vokabeln und ersetze das Zielwort durch eine Lücke (___).

Vokabeln:
{$entriesJson}

Antworte NUR mit einem JSON-Array. Jedes Element:
- "entry_id": ID des Eintrags
- "question": Beispielsatz mit Lücke (___) statt des Zielworts
- "correct_answer": Das fehlende Wort
- "hint": Übersetzung als Hinweis

Wähle {$count} Einträge, die einen Beispielsatz haben. Falls weniger vorhanden, nimm alle mit Beispielsatz. JSON:
PROMPT;
    }

    public static function quizMultipleChoice(array $entries, int $count): string
    {
        $entriesJson = json_encode($entries, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Du erstellst eine Multiple-Choice-Abfrage. Für jede Frage zeigst du ein Wort und 4 Antwortmöglichkeiten (1 richtig, 3 falsch aber plausibel).

Vokabeln:
{$entriesJson}

Antworte NUR mit einem JSON-Array. Jedes Element:
- "entry_id": ID des Eintrags
- "question": Das Wort in der Zielsprache
- "correct_answer": Die korrekte Übersetzung
- "options": Array mit 4 Strings (inkl. korrekter Antwort, zufällig gemischt)
- "hint": Wortart oder Genus als Hinweis

Wähle {$count} verschiedene Einträge. Die falschen Optionen sollen aus derselben Liste stammen oder thematisch passen. JSON:
PROMPT;
    }

    public static function checkAnswer(string $term, string $correctAnswer, string $userAnswer, string $mode): string
    {
        return <<<PROMPT
Du bist ein freundlicher Sprachlehrer. Prüfe die Antwort eines Schülers.

Modus: {$mode}
Gesuchtes Wort: {$term}
Korrekte Antwort: {$correctAnswer}
Antwort des Schülers: {$userAnswer}

Antworte NUR mit einem JSON-Objekt:
- "correct": true/false (tolerant bei kleinen Tippfehlern, Groß-/Kleinschreibung, fehlenden Akzenten)
- "expected": Die exakt korrekte Antwort
- "feedback": Kurzes Feedback (1-2 Sätze). Bei richtig: Bestätigung + optional interessanter Fakt. Bei falsch: Korrektur + Erklärung/Eselsbrücke.

JSON:
PROMPT;
    }
}
