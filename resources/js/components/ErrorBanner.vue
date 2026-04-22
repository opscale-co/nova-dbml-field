<template>
    <div class="dbml-error" role="alert" data-testid="dbml-error">
        <strong class="dbml-error__title">DBML parse error</strong>
        <span class="dbml-error__message">{{ error.message }}</span>
        <span v-if="location" class="dbml-error__location">({{ location }})</span>
        <pre v-if="source" class="dbml-error__source">{{ source }}</pre>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    error: { type: Object, required: true },
    source: { type: String, default: '' },
})

const location = computed(() => {
    if (!props.error.line) return null
    return props.error.column
        ? `line ${props.error.line}, col ${props.error.column}`
        : `line ${props.error.line}`
})
</script>
