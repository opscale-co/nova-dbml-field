<template>
    <PanelItem :index="index" :field="field" :full-width-content="true">
        <template #value>
            <div class="dbml-detail-wrapper" data-testid="dbml-detail-wrapper">
                <DbmlViewer :source="field.value || ''" />
            </div>
        </template>
    </PanelItem>
</template>

<script setup>
import DbmlViewer from './DbmlViewer.vue'

defineProps({
    index: { type: Number, required: true },
    resource: { type: Object, required: true },
    resourceName: { type: String, required: true },
    resourceId: { type: [Number, String] },
    field: { type: Object, required: true },
})
</script>

<style scoped>
.dbml-detail-wrapper {
    width: 100%;
    height: 600px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--color-border, #e5e7eb);
}
</style>
