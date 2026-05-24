import { createApp } from 'vue'
import VaultUnlock from './components/VaultUnlock.vue'
import PwdsafeButton from './components/Button.vue'
import PwdsafeAlert from './components/Alert.vue'
import PwdsafeInput from './components/Input.vue'
import './bootstrap'

const app = createApp(VaultUnlock)
app.component('pwdsafe-button', PwdsafeButton)
app.component('pwdsafe-alert', PwdsafeAlert)
app.component('pwdsafe-input', PwdsafeInput)
app.mount('#vault-unlock-app')
