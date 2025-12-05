import { createApp } from 'vue'
import Dashboard from '../Components/Dashboard/Dashboard.vue'

createApp({})
    .component('dashboard-component', Dashboard)
    .mount('#dashboard-app')