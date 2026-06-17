/**
 * Compute Levenshtein edit distance between two strings.
 * Used for fuzzy matching of speech recognition output against command terms.
 */
function levenshteinDistance(a, b) {
    if (a.length === 0) return b.length;
    if (b.length === 0) return a.length;

    const matrix = Array.from({ length: b.length + 1 }, (_, i) => [i]);
    for (let j = 0; j <= a.length; j++) matrix[0][j] = j;

    for (let i = 1; i <= b.length; i++) {
        for (let j = 1; j <= a.length; j++) {
            const cost = a[j - 1] === b[i - 1] ? 0 : 1;
            matrix[i][j] = Math.min(
                matrix[i - 1][j] + 1,
                matrix[i][j - 1] + 1,
                matrix[i - 1][j - 1] + cost,
            );
        }
    }

    return matrix[b.length][a.length];
}

/**
 * Check if `word` matches `term` within a phonetic distance threshold.
 * Short words (≤4 chars) allow distance 1; longer words allow distance 2.
 */
function isFuzzyMatch(word, term) {
    if (word === term) return true;
    const maxLen = Math.max(word.length, term.length);
    const threshold = maxLen <= 4 ? 1 : 2;
    return levenshteinDistance(word, term) <= threshold;
}

/**
 * Phonetic normalization map for Filipino English pronunciation patterns.
 *
 * Maps common misrecognitions back to their intended word so command
 * matching survives the browser's acoustic model quirks.
 *
 * Key phonological patterns addressed:
 *   /sl/ → /l/  — "slide" heard as "life"/"light"/"like"
 *   /st/ → /t/  — "stop" heard as "top"
 *   Final clusters simplified — "next" heard as "neck"/"ness"/"nice"
 *   /v/ → /b/   — "previous" heard as "prebius"
 *   /f/ → /p/   — "forward" heard as "porward"
 */
const PHONETIC_NORMALIZATION = new Map([
    // ── /sl/ cluster simplification ───────────────────────
    ['life', 'slide'],
    ['light', 'slide'],
    ['like', 'slide'],
    ['line', 'slide'],
    ['live', 'slide'],
    ['lives', 'slides'],
    ['lice', 'slide'],

    // ── /st/ cluster simplification ───────────────────────
    ['top', 'stop'],
    ['tap', 'stop'],
    ['stap', 'stop'],

    // ── "next" final cluster variants ─────────────────────
    ['neck', 'next'],
    ['necks', 'next'],
    ['ness', 'next'],
    ['nice', 'next'],
    ['nika', 'next'],
    ['nicks', 'next'],
    ['nesta', 'next'],
    ['mes', 'next'],
    ['mess', 'next'],

    // ── "forward" /f/ → /p/ ──────────────────────────────
    ['porwad', 'forward'],
    ['porward', 'forward'],

    // ── "previous" /v/ → /b/ ─────────────────────────────
    ['prebius', 'previous'],
    ['prebyus', 'previous'],

    // ── "exit" word-boundary fusion ───────────────────────
    ['egsit', 'exit'],
    ['egzit', 'exit'],
    ['eggsit', 'exit'],
    ['ekzit', 'exit'],
    ['ekis', 'exit'],
    ['ekisit', 'exit'],
]);

const COMMANDS = [
    {
        command: 'next',
        label: 'Next',
        terms: [
            'next', 'forward', 'siguiente', 'adelante',
            'neks', 'nekst', 'nex', 'nekis', 'nikst',
            'forwad', 'porward',
        ],
    },
    {
        command: 'previous',
        label: 'Previous',
        terms: [
            'previous', 'back', 'anterior',
            'prev', 'prebyus', 'previus', 'previyus', 'preevius',
            'bak', 'bek',
        ],
    },
    {
        command: 'exit',
        label: 'Exit',
        terms: [
            'exit', 'stop', 'salir',
            'egzit', 'ekzit', 'eksit', 'eggsit',
            'istop', 'estop',
        ],
    },
];

/** Minimum ms between dispatches of the same command. */
const COMMAND_COOLDOWN = 1500;

export class VoiceRecognitionService {
    constructor(config) {
        this.config = { ...config };
        this.recognition = null;
        this.startedByUser = false;
        this.restartTimer = null;
        this.lastCommandTimes = {};
        this.initRecognition();
    }

    initRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (!SpeechRecognition) {
            this.config.onStateChange(false, 'Speech recognition is not supported in this browser.');
            return;
        }

        try {
            this.recognition = new SpeechRecognition();
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            this.recognition.lang = this.config.language && this.config.language !== 'auto'
                ? this.config.language
                : navigator.language || 'en-US';

            // Request multiple alternatives for better fuzzy matching.
            this.recognition.maxAlternatives = 3;

            this.recognition.onstart = () => {
                this.config.onStateChange(true);
            };

            this.recognition.onend = () => {
                this.config.onStateChange(false);

                if (this.startedByUser) {
                    clearTimeout(this.restartTimer);
                    this.restartTimer = setTimeout(() => {
                        if (this.startedByUser) {
                            this.restartSilently();
                        }
                    }, 300);
                }
            };

            this.recognition.onerror = (event) => {
                if (event.error === 'not-allowed') {
                    this.startedByUser = false;
                    this.config.onStateChange(false, 'Microphone permission was denied.');
                } else if (event.error === 'network') {
                    this.config.onStateChange(false, 'Speech recognition network error.');
                } else {
                    this.config.onStateChange(false, event.error || 'Speech recognition error.');
                }
            };

            this.recognition.onresult = (event) => {
                let interimTranscript = '';
                let finalTranscript = '';

                for (let index = event.resultIndex; index < event.results.length; index += 1) {
                    const result = event.results[index];
                    const bestText = result[0].transcript;

                    if (result.isFinal) {
                        finalTranscript += `${bestText} `;

                        // Try each alternative for final results (most accurate).
                        for (let j = 0; j < result.length; j++) {
                            const alt = result[j].transcript.trim().toLowerCase();
                            if (alt && this.evaluateCommand(alt)) {
                                break;
                            }
                        }
                    } else {
                        // Check interim results immediately so commands fire
                        // ~200ms after speaking instead of waiting 1-3s for
                        // the API to decide the utterance is complete.
                        const interim = bestText.trim().toLowerCase();
                        if (interim) {
                            this.evaluateCommand(interim);
                        }
                        interimTranscript += bestText;
                    }
                }

                const currentTranscript = `${finalTranscript}${interimTranscript}`.trim();

                if (currentTranscript) {
                    this.config.onTranscriptChange(currentTranscript, finalTranscript.trim().length > 0);
                }
            };
        } catch (error) {
            this.config.onStateChange(false, error?.message || 'Unable to initialize speech recognition.');
        }
    }

    start() {
        if (!this.recognition) {
            this.initRecognition();
        }

        if (!this.recognition) {
            return;
        }

        this.startedByUser = true;

        try {
            this.recognition.start();
        } catch (error) {
            // Ignore double-start attempts.
        }
    }

    stop() {
        this.startedByUser = false;
        clearTimeout(this.restartTimer);

        if (!this.recognition) {
            return;
        }

        try {
            this.recognition.abort();
        } catch (error) {
            // Ignore stop errors.
        }
    }

    destroy() {
        this.stop();
        this.recognition = null;
    }

    updateConfig(language, wakeWordEnabled) {
        this.config.language = language;
        this.config.wakeWordEnabled = wakeWordEnabled;

        if (!this.recognition) {
            return;
        }

        const shouldRestart = this.startedByUser;
        this.stop();
        this.recognition = null;

        if (shouldRestart) {
            setTimeout(() => this.start(), 150);
        } else {
            this.initRecognition();
        }
    }

    restartSilently() {
        if (!this.startedByUser || !this.recognition) {
            return;
        }

        try {
            this.recognition.start();
        } catch (error) {
            // Safe to ignore.
        }
    }

    /**
     * Map known phonetic misrecognitions back to their intended words.
     * Runs as a preprocessing step before command matching so that
     * both exact and fuzzy matching benefit from corrected input.
     */
    normalizePhonetic(phrase) {
        return phrase.split(/\s+/).map((w) => PHONETIC_NORMALIZATION.get(w) || w).join(' ');
    }

    /**
     * Try to match `phrase` against known commands.
     * Returns true if a command was matched and dispatched.
     *
     * When multiple commands match the same phrase (common in continuous
     * mode where the API appends new speech to old buffer text), the
     * command whose term appears LATEST in the phrase wins — this is
     * almost always the most recently spoken word.
     *
     * Matching strategy (in order):
     * 0. Phonetic normalization — map known misrecognitions back to intended words
     * 1. Exact word match — a word in the phrase is in the terms list
     * 2. Substring match — a term is found anywhere within the phrase
     * 3. Fuzzy match — Levenshtein distance between a word and a term is ≤ threshold
     */
    evaluateCommand(phrase) {
        // --- Phonetic normalization for Filipino English ---
        let text = this.normalizePhonetic(phrase);

        // --- Wake word gate (operates on normalized text too) ---
        if (this.config.wakeWordEnabled) {
            const words = text.split(/\s+/);
            const wakeIndex = words.findIndex((w) => isFuzzyMatch(w, 'jarvis'));

            if (wakeIndex === -1) {
                return false;
            }

            words.splice(wakeIndex, 1);
            text = words.join(' ').trim();
        }

        if (!text) {
            return false;
        }

        const words = text.split(/\s+/);
        let bestItem = null;
        let bestIndex = -1;

        for (const item of COMMANDS) {
            const idx = this.findMatchIndex(item, words, text);
            if (idx > bestIndex) {
                bestIndex = idx;
                bestItem = item;
            }
        }

        if (bestItem) {
            return this.dispatchCommand(bestItem, phrase);
        }

        return false;
    }

    /**
     * Find the latest (highest) word index where `item` matches within
     * the phrase. Returns -1 if no match.
     *
     * Checks all three strategies in order of reliability, scanning
     * from the end of the word list so later words are preferred.
     */
    findMatchIndex(item, words, text) {
        // 1. Exact word match — scan from the end.
        for (let i = words.length - 1; i >= 0; i--) {
            if (item.terms.includes(words[i])) {
                return i;
            }
        }

        // 2. Substring match — find the last occurrence of any term.
        let best = -1;
        for (const term of item.terms) {
            const pos = text.lastIndexOf(term);
            if (pos !== -1) {
                // Approximate character offset → word index.
                const wordPos = text.slice(0, pos).split(/\s+/).length;
                if (wordPos > best) {
                    best = wordPos;
                }
            }
        }
        if (best !== -1) {
            return best;
        }

        // 3. Fuzzy match — scan from the end.
        for (let i = words.length - 1; i >= 0; i--) {
            if (item.terms.some((term) => isFuzzyMatch(words[i], term))) {
                return i;
            }
        }

        return -1;
    }

    /**
     * Fire a command callback if the per-command cooldown has elapsed.
     * Returns true (matched) regardless, so the caller stops scanning
     * alternatives, but only actually dispatches if enough time passed.
     */
    dispatchCommand(item, phrase) {
        const now = Date.now();
        const last = this.lastCommandTimes[item.command] || 0;

        if (now - last >= COMMAND_COOLDOWN) {
            this.lastCommandTimes[item.command] = now;
            this.config.onCommandRecognized(item.command, item.label, phrase);
        }

        return true;
    }
}
