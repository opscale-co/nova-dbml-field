<template>
    <div data-testid="dbml-form-field">
        <FormFileField
            ref="fileField"
            :field="wrappedField"
            :errors="errors"
            :resource-name="resourceName"
            :resource-id="resourceId"
            :full-width-content="fullWidthContent"
            :show-help-text="showHelpText"
        />
        <p v-if="clientError" class="mt-2 text-xs text-red-500" data-testid="dbml-form-field-error">
            {{ clientError }}
        </p>
        <p v-else-if="tableCount !== null" class="mt-2 text-xs text-green-600" data-testid="dbml-form-field-ok">
            Schema parsed — {{ tableCount }} tables.
        </p>
    </div>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import { parseDbml } from '../services/parser'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    provide() {
        return {
            removeFile: () => {
                this.clientError = null
                this.tableCount = null
            },
        }
    },

    data() {
        return {
            clientError: null,
            tableCount: null,
            input: null,
            wrappedField: null,
        }
    },

    created() {
        this.wrappedField = {
            ...this.field,
            acceptedTypes: '.dbml,.txt,text/plain',
            deletable: false,
            rounded: false,
        }
    },

    mounted() {
        this.field.fill = (formData) => {
            if (typeof this.wrappedField.fill === 'function') {
                this.wrappedField.fill(formData)
            }
        }

        this.$nextTick(() => {
            this.input = this.$el.querySelector('input[type="file"]')
            if (this.input) {
                this.input.addEventListener('change', this.onFileChange)
            }
        })
    },

    beforeUnmount() {
        if (this.input) {
            this.input.removeEventListener('change', this.onFileChange)
        }
    },

    methods: {
        async onFileChange(event) {
            const file = event.target.files?.[0]
            if (!file) {
                this.clientError = null
                this.tableCount = null
                return
            }
            const text = await file.text()
            const result = parseDbml(text)
            if (!result.ok) {
                const loc = result.error.line
                    ? ` (line ${result.error.line}${result.error.column ? `, col ${result.error.column}` : ''})`
                    : ''
                this.clientError = `${result.error.message}${loc}`
                this.tableCount = null
                return
            }
            this.clientError = null
            this.tableCount = countTables(result.database)
        },
    },
}

function countTables(database) {
    if (!database?.schemas) return 0
    return database.schemas.reduce((acc, schema) => acc + (schema.tables?.length ?? 0), 0)
}
</script>
