/**
 * Adapter: converts a @dbml/core Database object into Vue Flow nodes/edges.
 *
 * A table becomes one node whose `data.table` carries everything the
 * TableNode.vue component needs to render rows and handles. A ref becomes
 * one edge whose endpoints reference specific column handles on each side.
 */

/**
 * @param {object|null} database - Output of @dbml/core Parser#parse.
 * @returns {{ nodes: Array, edges: Array }}
 */
export function toGraph(database) {
    if (!database || !database.schemas) {
        return { nodes: [], edges: [] }
    }

    const nodes = []
    const edges = []

    database.schemas.forEach((schema) => {
        const schemaName = schema.name && schema.name !== 'public' ? schema.name : null

        schema.tables.forEach((table) => {
            const id = nodeId(schemaName, table.name)

            nodes.push({
                id,
                type: 'table',
                position: { x: 0, y: 0 },
                data: {
                    schemaName,
                    name: table.name,
                    note: noteText(table.note),
                    columns: table.fields.map((field) => columnData(field, table)),
                },
            })
        })

        schema.refs.forEach((ref, index) => {
            const edge = edgeFromRef(ref, schemaName, index)
            if (edge) {
                edges.push(edge)
            }
        })
    })

    return { nodes, edges }
}

export function nodeId(schemaName, tableName) {
    return schemaName ? `${schemaName}.${tableName}` : tableName
}

export function columnHandleId(columnName, side) {
    return `${side}-${columnName}`
}

function columnData(field, table) {
    const pkByIndex = (table.indexes ?? []).some(
        (idx) => idx.pk && idx.columns?.some((c) => c.value === field.name),
    )

    return {
        name: field.name,
        type: typeLabel(field.type),
        pk: Boolean(field.pk) || pkByIndex,
        unique: Boolean(field.unique),
        notNull: Boolean(field.not_null),
        default: field.dbdefault?.value ?? null,
        note: noteText(field.note),
    }
}

function typeLabel(type) {
    if (!type) {
        return ''
    }
    if (typeof type === 'string') {
        return type
    }
    const base = type.type_name ?? ''
    const args = type.args ? `(${type.args})` : ''
    return `${base}${args}`
}

function noteText(note) {
    if (!note) {
        return null
    }
    return typeof note === 'string' ? note : (note.value ?? null)
}

function edgeFromRef(ref, currentSchema, index) {
    if (!Array.isArray(ref.endpoints) || ref.endpoints.length !== 2) {
        return null
    }

    const [a, b] = ref.endpoints
    const sourceTable = nodeId(a.schemaName ?? currentSchema, a.tableName)
    const targetTable = nodeId(b.schemaName ?? currentSchema, b.tableName)
    const sourceCol = a.fieldNames?.[0]
    const targetCol = b.fieldNames?.[0]

    if (!sourceCol || !targetCol) {
        return null
    }

    return {
        id: `ref-${index}-${sourceTable}-${sourceCol}-${targetTable}-${targetCol}`,
        source: sourceTable,
        sourceHandle: columnHandleId(sourceCol, 'right'),
        target: targetTable,
        targetHandle: columnHandleId(targetCol, 'left'),
        type: 'relationship',
        data: { cardinality: cardinality(a.relation, b.relation) },
    }
}

function cardinality(left, right) {
    const map = { '1-1': '1-1', '1-*': '1-n', '*-1': '1-n', '*-*': 'n-n' }
    return map[`${left}-${right}`] ?? '1-n'
}
