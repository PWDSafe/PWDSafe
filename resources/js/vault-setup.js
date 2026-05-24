import { createApp } from 'vue'
import VaultSetup from './components/VaultSetup.vue'
import PwdsafeButton from './components/Button.vue'
import PwdsafeAlert from './components/Alert.vue'
import PwdsafeInput from './components/Input.vue'
import './bootstrap'

const app = createApp(VaultSetup)
app.component('pwdsafe-button', PwdsafeButton)
app.component('pwdsafe-alert', PwdsafeAlert)
app.component('pwdsafe-input', PwdsafeInput)
app.mount('#vault-setup-app')
