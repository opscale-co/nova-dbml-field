import IndexField from './components/IndexField.vue'
import DetailField from './components/DetailField.vue'

Nova.booting((app, store) => {
    app.component('index-nova-dbml-field', IndexField)
    app.component('detail-nova-dbml-field', DetailField)
})
