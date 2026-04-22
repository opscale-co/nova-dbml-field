import DetailField from './components/DetailField.vue'
import FormField from './components/FormField.vue'

Nova.booting((app, store) => {
    app.component('detail-nova-dbml-field', DetailField)
    app.component('form-nova-dbml-field', FormField)
})
