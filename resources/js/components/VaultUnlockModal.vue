<template>
    <Teleport to="#modals">
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-to-class="transform opacity-100"
            enter-from-class="transform opacity-0"
            leave-active-class="transition ease-in duration-75"
            leave-to-class="transform opacity-0"
            leave-from-class="transform opacity-100"
        >
            <div v-if="unlockModalVisible" class="fixed inset-0 overflow-y-auto z-20">
                <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity">
                        <div class="absolute inset-0 bg-gray-500 opacity-75" @click="cancel"></div>
                    </div>

                    <span class="hidden sm:inline-block sm:h-screen sm:align-middle"></span>&#8203;

                    <div class="relative inline-block overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm sm:p-6 sm:align-middle dark:bg-gray-700">
                        <div class="flex items-center gap-x-2 mb-2">
                            <heroicons-lock-closed-icon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Safe locked</h3>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Your session expired. Enter your safe password to continue.
                        </p>

                        <pwdsafe-alert v-if="errorMessage" theme="danger" classes="mb-4">
                            {{ errorMessage }}
                        </pwdsafe-alert>

                        <form @submit.prevent="handleSubmit">
                            <div class="mb-4">
                                <pwdsafe-label for="vault_relock_password" class="mb-1">
                                    Safe password
                                </pwdsafe-label>
                                <pwdsafe-input
                                    type="password"
                                    id="vault_relock_password"
                                    v-model="safePassword"
                                    autocomplete="current-password"
                                />
                            </div>
                            <div class="flex gap-3">
                                <pwdsafe-button type="submit" :disabled="submitting">
                                    {{ submitting ? 'Unlocking…' : 'Unlock' }}
                                </pwdsafe-button>
                                <pwdsafe-button type="button" theme="secondary" :disabled="submitting" @click="cancel">
                                    Cancel
                                </pwdsafe-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { deriveVaultKey, decryptPrivkey, storePrivkey } from '../vault.js'
import { useVaultUnlock, resolveUnlock, rejectUnlock } from '../composables/useVaultUnlock.js'

const { unlockModalVisible } = useVaultUnlock()
const safePassword = ref('')
const submitting = ref(false)
const errorMessage = ref('')

watch(unlockModalVisible, async (visible) => {
    if (visible) {
        safePassword.value = ''
        errorMessage.value = ''
        submitting.value = false
        await nextTick()
        document.getElementById('vault_relock_password')?.focus()
    }
})

const handleSubmit = async () => {
    errorMessage.value = ''
    submitting.value = true

    try {
        const resp = await fetch('/api/vault/key-data', {
            headers: { Accept: 'application/json' },
        })
        if (!resp.ok) {
            throw new Error('Failed to load safe data.')
        }
        const keyData = await resp.json()

        const vaultKey = await deriveVaultKey(safePassword.value, keyData.salt)
        const privkeyPem = await decryptPrivkey(keyData.encrypted_privkey, vaultKey)

        storePrivkey(privkeyPem)
        resolveUnlock(privkeyPem)
        safePassword.value = ''
    } catch (err) {
        errorMessage.value = err.message || 'Incorrect password.'
        submitting.value = false
    }
}

const cancel = () => {
    rejectUnlock()
}
</script>
