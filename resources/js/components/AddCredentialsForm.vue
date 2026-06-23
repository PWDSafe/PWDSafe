<template>
    <form @submit.prevent="handleSubmit">
        <div class="px-8 py-4">
            <h3 class="mb-4 text-2xl">Add credentials</h3>
            <div class="mb-4">
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="name" required
                        >Name</pwdsafe-label
                    >
                    <pwdsafe-input
                        type="text"
                        v-model="name"
                        id="name"
                        autocomplete="off"
                        required
                        autofocus
                    ></pwdsafe-input>
                </div>
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="url">URL</pwdsafe-label>
                    <pwdsafe-input
                        type="text"
                        placeholder="https://example.com"
                        v-model="url"
                        id="url"
                        autocomplete="off"
                    ></pwdsafe-input>
                </div>
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="user" required
                        >Username</pwdsafe-label
                    >
                    <pwdsafe-input
                        type="text"
                        v-model="user"
                        id="user"
                        autocomplete="off"
                        required
                    ></pwdsafe-input>
                </div>
                <div class="mb-2">
                    <div class="mb-2 flex items-end justify-between gap-x-2">
                        <pwdsafe-label class="mb-1" for="pass" required
                            >Password</pwdsafe-label
                        >
                        <div class="flex items-center gap-x-2">
                            <pwdsafe-button
                                type="button"
                                theme="secondary"
                                size="small"
                                @click="passwordVisible = !passwordVisible"
                                :title="
                                    passwordVisible
                                        ? 'Hide password'
                                        : 'Show password'
                                "
                            >
                                <EyeSlashIcon
                                    v-if="passwordVisible"
                                    class="h-4 w-4"
                                />
                                <EyeIcon v-else class="h-4 w-4" />
                            </pwdsafe-button>
                            <pwdsafe-passwordgen
                                @generated="updatePassword"
                            ></pwdsafe-passwordgen>
                        </div>
                    </div>
                    <TextareaVue
                        v-model="password"
                        id="pass"
                        rows="5"
                        required
                        :style="{
                            WebkitTextSecurity: passwordVisible
                                ? 'none'
                                : 'disc',
                        }"
                    ></TextareaVue>
                </div>
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="notes"
                        >Notes</pwdsafe-label
                    >
                    <pwdsafe-textarea
                        v-model="notes"
                        id="notes"
                        rows="5"
                    ></pwdsafe-textarea>
                </div>
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="totp_secret"
                        >TOTP Secret</pwdsafe-label
                    >
                    <pwdsafe-input
                        type="text"
                        v-model="totpSecret"
                        id="totp_secret"
                        placeholder="e.g. JBSWY3DPEHPK3PXP"
                        autocomplete="off"
                    ></pwdsafe-input>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Base32 secret from your service's 2FA setup page. Leave
                        blank if not applicable.
                    </p>
                </div>
            </div>
        </div>
        <div
            class="bg-gray-50 dark:border-t dark:border-gray-800 dark:bg-gray-700"
        >
            <div class="flex justify-end gap-x-2 px-8 py-4">
                <pwdsafe-button theme="secondary" :href="backlink"
                    >Back</pwdsafe-button
                >
                <pwdsafe-button type="submit" :disabled="submitting"
                    >Add credential</pwdsafe-button
                >
            </div>
        </div>
    </form>
</template>
<script setup>
import { ref } from 'vue'
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline'
import TextareaVue from './TextareaVue.vue'
import { encryptCredentialV2 } from '../vault.js'

const props = defineProps({
    backlink: {
        type: String,
        required: true,
    },
    groupid: {
        type: Number,
        required: true,
    },
})

const name = ref('')
const url = ref('')
const user = ref('')
const password = ref('')
const passwordVisible = ref(false)
const notes = ref('')
const totpSecret = ref('')
const submitting = ref(false)

const updatePassword = (event) => {
    password.value = event
}

const handleSubmit = async () => {
    submitting.value = true
    try {
        const { data: pubkeysData } = await axios.get(
            `/api/groups/${props.groupid}/pubkeys`,
        )
        const hasTotpSecret = totpSecret.value.trim() !== ''
        const encrypted = await Promise.all(
            pubkeysData.users.map(async ({ id, pubkey }) => ({
                userid: id,
                data: await encryptCredentialV2(password.value, pubkey),
                totp_secret: hasTotpSecret
                    ? await encryptCredentialV2(totpSecret.value.trim(), pubkey)
                    : null,
            })),
        )

        await axios.post(`/groups/${props.groupid}/add`, {
            name: name.value,
            url: url.value || null,
            user: user.value,
            notes: notes.value,
            has_totp: hasTotpSecret,
            encrypted,
        })

        window.location.href = props.backlink
    } finally {
        submitting.value = false
    }
}
</script>
