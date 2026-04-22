<template>
    <div class="dbml-viewer" :class="{ 'is-compact': compact }" data-testid="dbml-viewer">
        <ErrorBanner v-if="parseResult && !parseResult.ok" :error="parseResult.error" :source="source" />

        <div v-else-if="!source" class="dbml-viewer__empty" data-testid="dbml-viewer-empty">
            No schema to display
        </div>

        <VueFlow
            v-else
            :nodes="nodes"
            :edges="edges"
            :node-types="nodeTypes"
            :edge-types="edgeTypes"
            :min-zoom="0.2"
            :max-zoom="2"
            :nodes-draggable="!compact"
            :nodes-connectable="false"
            :elements-selectable="!compact"
            :pan-on-scroll="compact"
            :zoom-on-scroll="!compact"
            fit-view-on-init
            class="dbml-viewer__flow"
        >
            <Background v-if="!compact" :pattern-color="'#aaa'" :gap="16" />
            <Controls v-if="!compact" position="bottom-left" />
            <MiniMap v-if="!compact" pannable zoomable />
        </VueFlow>
    </div>
</template>

<script setup>
import { computed, markRaw, watch, ref } from 'vue'
import { VueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'

import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'
import '@vue-flow/minimap/dist/style.css'

import TableNode from './TableNode.vue'
import RelationshipEdge from './RelationshipEdge.vue'
import ErrorBanner from './ErrorBanner.vue'

import { parseDbml } from '../services/parser'
import { toGraph } from '../services/graph'
import { autoLayout } from '../services/layout'

const props = defineProps({
    source: { type: String, default: '' },
    compact: { type: Boolean, default: false },
})

const nodeTypes = markRaw({ table: TableNode })
const edgeTypes = markRaw({ relationship: RelationshipEdge })

const parseResult = computed(() => parseDbml(props.source))

const nodes = ref([])
const edges = ref([])

watch(
    parseResult,
    (result) => {
        if (!result?.ok || !result.database) {
            nodes.value = []
            edges.value = []
            return
        }
        const graph = toGraph(result.database)
        nodes.value = autoLayout(graph.nodes, graph.edges, { direction: 'LR' })
        edges.value = graph.edges
    },
    { immediate: true },
)
</script>
