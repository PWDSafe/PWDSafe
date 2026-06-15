export function normalizeUrl(url?: string | null): string | null {
    const trimmed = url?.trim()
    if (!trimmed) {
        return null
    }

    return /^[a-z][a-z0-9+.-]*:\/\//i.test(trimmed) ? trimmed : `https://${trimmed}`
}
