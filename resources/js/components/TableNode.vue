<template>
    <div class="dbml-table-node" data-testid="dbml-table-node">
        <header class="dbml-table-node__header">
            <span v-if="data.schemaName" class="dbml-table-node__schema">{{ data.schemaName }}.</span>
            <span class="dbml-table-node__name">{{ data.name }}</span>
        </header>
        <ul class="dbml-table-node__columns">
            <li
                v-for="col in data.columns"
                :key="col.name"
                class="dbml-table-node__column"
                :class="{ 'is-pk': col.pk }"
            >
                <Handle
                    :id="`left-${col.name}`"
                    type="target"
                    :position="Position.Left"
                    class="dbml-table-node__handle"
                />
                <span class="dbml-table-node__col-name">
                    <span v-if="col.pk" class="dbml-table-node__badge" title="Primary key">PK</span>
                    <span v-else-if="col.unique" class="dbml-table-node__badge" title="Unique">UQ</span>
                    {{ col.name }}
                </span>
                <span class="dbml-table-node__col-type">{{ col.type }}<span v-if="!col.notNull && !col.pk" class="dbml-table-node__nullable">?</span></span>
                <Handle
                    :id="`right-${col.name}`"
                    type="source"
                    :position="Position.Right"
                    class="dbml-table-node__handle"
                />
            </li>
        </ul>
    </div>
</template>

<script setup>
import { Handle, Position } from '@vue-flow/core'

defineProps({
    data: { type: Object, required: true },
})
</script>
