import { Parser } from '@dbml/core'

const parser = new Parser()
const cache = new Map()

/**
 * Parse DBML source into a Database object using @dbml/core.
 * Results are memoized by the exact source string.
 *
 * @param {string} source - Raw DBML text.
 * @returns {{ ok: true, database: object } | { ok: false, error: { message: string, line: number|null, column: number|null } }}
 */
export function parseDbml(source) {
    if (typeof source !== 'string' || source.trim() === '') {
        return { ok: true, database: null }
    }

    if (cache.has(source)) {
        return cache.get(source)
    }

    let result
    try {
        const database = parser.parse(source, 'dbmlv2')
        result = { ok: true, database }
    } catch (error) {
        result = { ok: false, error: normalizeError(error) }
    }

    cache.set(source, result)
    return result
}

function normalizeError(error) {
    const diag = Array.isArray(error?.diags) ? error.diags[0] : null

    return {
        message: diag?.message ?? error?.message ?? 'Invalid DBML',
        line: diag?.location?.start?.line ?? null,
        column: diag?.location?.start?.column ?? null,
    }
}
