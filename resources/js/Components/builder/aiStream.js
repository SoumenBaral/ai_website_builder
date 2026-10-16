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
export async function streamAi(url, body, onProgress) {
    const xsrf = decodeURIComponent((document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/) || [])[1] || '');

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
        });
    } catch {
        throw new Error('Could not reach the server. Check your connection and try again.');
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
        const { value, done } = await reader.read();
        if (done) break;
        buffer += decoder.decode(value, { stream: true });
        let newline;
        while ((newline = buffer.indexOf('\n')) >= 0) {
            handle(buffer.slice(0, newline));
            buffer = buffer.slice(newline + 1);
        }
    }
    handle(buffer);

    if (!result) {
        throw new Error('The connection closed before the AI finished. Please try again.');
    }
    return result;
}
