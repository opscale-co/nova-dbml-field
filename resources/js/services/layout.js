import dagre from 'dagre'

const DEFAULT_NODE_WIDTH = 260
const ROW_HEIGHT = 24
const HEADER_HEIGHT = 40

/**
 * Runs dagre on the graph and returns nodes with `position` filled in.
 *
 * @param {Array} nodes - Vue Flow nodes (table nodes carry column counts in data).
 * @param {Array} edges - Vue Flow edges.
 * @param {{ direction?: 'LR'|'TB' }} [options]
 * @returns {Array} Nodes cloned with computed positions.
 */
export function autoLayout(nodes, edges, options = {}) {
    const direction = options.direction ?? 'LR'

    const graph = new dagre.graphlib.Graph()
    graph.setDefaultEdgeLabel(() => ({}))
    graph.setGraph({ rankdir: direction, nodesep: 40, ranksep: 80 })

    nodes.forEach((node) => {
        const height = HEADER_HEIGHT + (node.data?.columns?.length ?? 1) * ROW_HEIGHT
        graph.setNode(node.id, { width: DEFAULT_NODE_WIDTH, height })
    })

    edges.forEach((edge) => {
        graph.setEdge(edge.source, edge.target)
    })

    dagre.layout(graph)

    return nodes.map((node) => {
        const laidOut = graph.node(node.id)
        return {
            ...node,
            position: {
                x: laidOut.x - laidOut.width / 2,
                y: laidOut.y - laidOut.height / 2,
            },
        }
    })
}
