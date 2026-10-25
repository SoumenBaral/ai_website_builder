/**
 * POST to an AI endpoint and read its NDJSON progress stream.
 *
 * The server sends one JSON object per line:
 *   {"event":"progress","message":"Writing the pricing section…","progress":0.62}
 *   {"event":"done", ...result}
 *   {"event":"error","error":"…"}
 *
 * Resolves with the `done` payload; rejects with an Error whose message is safe to show.
 */
const IDLE_TIMEOUT_MS = 90000; // no bytes for this long = the request is dead, not slow

export async function streamAi(url, body, onProgress, { signal } = {}) {
    const xsrf = decodeURIComponent((document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/) || [])[1] || '');

    // Our own controller aborts on idle; the caller's signal (Stop button, page leave) aborts too.
    const controller = new AbortController();
    signal?.addEventListener('abort', () => controller.abort(), { once: true });
    let idleTimer = 0;
    const armIdle = () => {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(() => controller.abort(), IDLE_TIMEOUT_MS);
    };
    armIdle();
    const abortMessage = () => (signal?.aborted ? 'Stopped.' : 'The AI stopped responding. Please try again.');

    let response;
    try {
        response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/x-ndjson',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrf,
            },
            body: JSON.stringify(body),
            signal: controller.signal,
        });
    } catch {
        clearTimeout(idleTimer);
        throw new Error(controller.signal.aborted ? abortMessage() : 'Could not reach the server. Check your connection and try again.');
    }

    // Validation errors, limits and auth come back as regular JSON before streaming starts.
    if (!response.ok || !response.body) {
        let data = {};
        try { data = await response.json(); } catch { /* not JSON */ }
        const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        throw new Error(data.error || firstError || data.message || 'AI request failed. Please try again.');
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';
    let result = null;

    const handle = (line) => {
        if (!line.trim()) return;
        let event;
        try { event = JSON.parse(line); } catch { return; }
        if (event.event === 'progress') onProgress?.(event);
        else if (event.event === 'done') result = event;
        else if (event.event === 'error') throw new Error(event.error || 'AI request failed. Please try again.');
    };

    for (;;) {
        let chunk;
        try {
            chunk = await reader.read();
        } catch {
            clearTimeout(idleTimer);
            throw new Error(controller.signal.aborted ? abortMessage() : 'The connection dropped. Please try again.');
        }
        const { value, done } = chunk;
        if (done) break;
        armIdle();
        buffer += decoder.decode(value, { stream: true });
        let newline;
        while ((newline = buffer.indexOf('\n')) >= 0) {
            handle(buffer.slice(0, newline));
            buffer = buffer.slice(newline + 1);
        }
    }
    handle(buffer);
    clearTimeout(idleTimer);

    if (!result) {
        throw new Error('The connection closed before the AI finished. Please try again.');
    }
    return result;
}
