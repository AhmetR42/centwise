// Thin wrapper around fetch for the Centwise REST API.

const TOKEN_KEY = 'centwise:token';
const BASE = '/api/v1';

export class ApiError extends Error {
  constructor(status, body) {
    super(body?.message || `Request failed (${status})`);
    this.status = status;
    this.body = body;
    this.errors = body?.errors ?? {};
  }
}

export function getToken() {
  try { return localStorage.getItem(TOKEN_KEY); } catch { return null; }
}

export function setToken(token) {
  try {
    if (token) localStorage.setItem(TOKEN_KEY, token);
    else localStorage.removeItem(TOKEN_KEY);
  } catch { /* private mode: the session simply ends on reload */ }
}

let onUnauthorized = () => {};
export function whenUnauthorized(callback) {
  onUnauthorized = callback;
}

export async function api(path, { method = 'GET', body, query } = {}) {
  const url = new URL(BASE + path, location.origin);
  for (const [key, value] of Object.entries(query ?? {})) {
    if (value !== undefined && value !== null && value !== '') url.searchParams.set(key, value);
  }

  const headers = { Accept: 'application/json' };
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body !== undefined) headers['Content-Type'] = 'application/json';

  const response = await fetch(url, {
    method,
    headers,
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  if (response.status === 204) return null;
  const data = await response.json().catch(() => null);

  if (response.status === 401 && token) {
    setToken(null);
    onUnauthorized();
  }
  if (!response.ok) throw new ApiError(response.status, data);
  return data;
}

/** Download a file from the API (the token is a header, so a plain link would not work). */
export async function download(path, query, filename) {
  const url = new URL(BASE + path, location.origin);
  for (const [key, value] of Object.entries(query ?? {})) {
    if (value) url.searchParams.set(key, value);
  }
  const response = await fetch(url, { headers: { Authorization: `Bearer ${getToken()}` } });
  if (!response.ok) throw new ApiError(response.status, null);
  const link = document.createElement('a');
  link.href = URL.createObjectURL(await response.blob());
  link.download = filename;
  link.click();
  setTimeout(() => URL.revokeObjectURL(link.href), 1000);
}
