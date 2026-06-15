<template>
    <form @submit.prevent="handleSubmit">
        <div class="px-8 py-4">
            <h3 class="mb-4 text-2xl">Add credentials</h3>
            <div class="mb-4">
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="name">Name</pwdsafe-label>
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
                        v-model="url"
                        id="url"
                        autocomplete="off"
                    ></pwdsafe-input>
                </div>
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="user">Username</pwdsafe-label>
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
                        <pwdsafe-label class="mb-1" for="pass">Password</pwdsafe-label>
                        <pwdsafe-passwordgen @generated="updatePassword"></pwdsafe-passwordgen>
                    </div>
                    <TextareaVue v-model="password" id="pass" rows="5" required></TextareaVue>
                </div>
                <div class="mb-2">
                    <pwdsafe-label class="mb-1" for="notes">Notes</pwdsafe-label>
                    <pwdsafe-textarea v-model="notes" id="notes" rows="5"></pwdsafe-textarea>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 dark:border-t dark:border-gray-800 dark:bg-gray-700">
            <div class="flex justify-end gap-x-2 px-8 py-4">
                <pwdsafe-button theme="secondary" :href="backlink">Back</pwdsafe-button>
                <pwdsafe-button type="submit" :disabled="submitting">Add credential</pwdsafe-button>
            </div>
        </div>
    </form>
</template>
<script setup>
import { ref } from 'vue'
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
const notes = ref('')
const submitting = ref(false)

const updatePassword = (event) => {
    password.value = event
}

const handleSubmit = async () => {
    submitting.value = true
    try {
        const { data: pubkeysData } = await axios.get(`/api/groups/${props.groupid}/pubkeys`)
        const encrypted = await Promise.all(
            pubkeysData.users.map(async ({ id, pubkey }) => ({
                userid: id,
                data: await encryptCredentialV2(password.value, pubkey),
            })),
        )

        await axios.post(`/groups/${props.groupid}/add`, {
            name: name.value,
            url: url.value || null,
            user: user.value,
            notes: notes.value,
            encrypted,
        })

        window.location.href = props.backlink
    } finally {
        submitting.value = false
    }
}
</script>
