<template>
    <PanelItem :index="index" :field="field">
        <template #value>
            <pre class="text-sm whitespace-pre-wrap">{{ field.value }}</pre>
        </template>
    </PanelItem>
</template>

<script setup>
defineProps({
    index: { type: Number, required: true },
    resource: { type: Object, required: true },
    resourceName: { type: String, required: true },
    resourceId: { type: [Number, String] },
    field: { type: Object, required: true },
})
</script>
