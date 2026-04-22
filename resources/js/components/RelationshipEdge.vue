<template>
    <BaseEdge
        :id="id"
        :path="path[0]"
        :marker-end="markerEnd"
        :style="style"
    />
    <EdgeLabelRenderer>
        <div
            :style="{
                position: 'absolute',
                transform: `translate(-50%, -50%) translate(${path[1]}px, ${path[2]}px)`,
                pointerEvents: 'all',
            }"
            class="dbml-edge-label"
        >
            {{ data?.cardinality ?? '1-n' }}
        </div>
    </EdgeLabelRenderer>
</template>

<script setup>
import { computed } from 'vue'
import { BaseEdge, EdgeLabelRenderer, getSmoothStepPath } from '@vue-flow/core'

const props = defineProps({
    id: { type: String, required: true },
    sourceX: { type: Number, required: true },
    sourceY: { type: Number, required: true },
    targetX: { type: Number, required: true },
    targetY: { type: Number, required: true },
    sourcePosition: { type: String, required: true },
    targetPosition: { type: String, required: true },
    data: { type: Object, default: () => ({}) },
    markerEnd: { type: String, default: '' },
    style: { type: Object, default: () => ({}) },
})

const path = computed(() =>
    getSmoothStepPath({
        sourceX: props.sourceX,
        sourceY: props.sourceY,
        targetX: props.targetX,
        targetY: props.targetY,
        sourcePosition: props.sourcePosition,
        targetPosition: props.targetPosition,
    }),
)
</script>
