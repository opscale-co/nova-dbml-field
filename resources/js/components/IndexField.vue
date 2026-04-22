<template>
    <span>{{ fieldValue }}</span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    field: { type: Object, required: true },
})

const fieldValue = computed(() => props.field.value ?? '—')
</script>
