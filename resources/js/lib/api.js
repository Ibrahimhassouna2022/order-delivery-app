const TOKEN_KEY = 'wasalli_token';

export function getAuthToken() {
    return window.localStorage.getItem(TOKEN_KEY);
}

export async function apiJson(path, options = {}) {
    return apiFetch(path, options);
}

export async function apiFetch(path, { method = 'GET', body, headers = {}, auth = true } = {}) {
    const requestHeaders = {
        Accept: 'application/json',
        ...headers,
    };

    const token = getAuthToken();
    if (auth && token) {
        requestHeaders.Authorization = `Bearer ${token}`;
    }

    let requestBody = body;
    if (body !== undefined && body !== null && !(body instanceof FormData)) {
        requestHeaders['Content-Type'] = 'application/json';
        requestBody = JSON.stringify(body);
    }

    const response = await fetch(path, {
        method,
        headers: requestHeaders,
        body: requestBody,
    });

    const text = await response.text();
    let payload = null;

    if (text) {
        try {
            payload = JSON.parse(text);
        } catch {
            payload = { message: text };
        }
    }

    if (!response.ok) {
        const error = new Error(payload?.message || `HTTP ${response.status}`);
        error.status = response.status;
        error.data = payload;
        throw error;
    }

    return payload;
}
