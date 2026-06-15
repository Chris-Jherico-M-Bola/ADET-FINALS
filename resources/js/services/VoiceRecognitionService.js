export class VoiceRecognitionService {
    constructor(config) {
        this.config = { ...config };
        this.recognition = null;
        this.startedByUser = false;
        this.restartTimer = null;
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
                    const text = result[0].transcript;

                    if (result.isFinal) {
                        finalTranscript += `${text} `;
                        this.evaluateCommand(text.trim().toLowerCase());
                    } else {
                        interimTranscript += text;
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

    evaluateCommand(phrase) {
        let text = phrase;

        if (this.config.wakeWordEnabled) {
            const wakeWordIndex = phrase.indexOf('jarvis');

            if (wakeWordIndex === -1) {
                return;
            }

            text = phrase.slice(wakeWordIndex + 6).trim();
        }

        if (!text) {
            return;
        }

        const commands = [
            { command: 'next', label: 'Next', terms: ['next', 'forward', 'siguiente', 'adelante'] },
            { command: 'previous', label: 'Previous', terms: ['previous', 'back', 'anterior'] },
            { command: 'exit', label: 'Exit', terms: ['exit', 'stop', 'salir'] },
        ];

        for (const item of commands) {
            if (item.terms.some((term) => text.includes(term))) {
                this.config.onCommandRecognized(item.command, item.label, phrase);
                return;
            }
        }
    }
}
