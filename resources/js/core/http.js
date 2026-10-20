export function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export async function requestJson(url, options = {}) {
    const { method = 'GET', body, headers = {}, signal } = options;
    const requestHeaders = {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        ...headers,
    };

    const requestOptions = {
        method,
        headers: requestHeaders,
        signal,
    };

    if (body !== undefined) {
        if (body instanceof FormData) {
            requestOptions.body = body;
        } else {
            requestHeaders['Content-Type'] = 'application/json';
            requestOptions.body = JSON.stringify(body);
        }
    }

    const response = await fetch(url, requestOptions);
    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const message = data?.error ?? data?.message ?? `Request failed (${response.status})`;
        const error = new Error(message);
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}
