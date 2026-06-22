<template>
    <pwdsafe-modal ref="modalRef">
        <template #trigger="{ openModal }">
            <pwdsafe-button
                theme="secondary"
                class="flex items-center"
                @click="open(openModal)"
            >
                <heroicons-arrow-up-on-square-icon
                    class="mr-1 h-5 w-5"
                ></heroicons-arrow-up-on-square-icon>
                Import
            </pwdsafe-button>
        </template>

        <h3 class="mb-4 text-2xl">Import credentials</h3>
        <p>Import a JSON file containing an array with the following fields:</p>
        <ul class="my-2 ml-10 list-disc">
            <li>name</li>
            <li>username</li>
            <li>password</li>
            <li>url <em>(optional)</em></li>
            <li>notes <em>(optional)</em></li>
        </ul>
        <p class="mb-4 text-red-500">
            Warning: Malformed rows will be skipped.
        </p>

        <div v-if="error" class="mb-3 text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </div>
        <div
            v-if="successMessage"
            class="mb-3 text-sm text-green-600 dark:text-green-400"
        >
            {{ successMessage }}
        </div>

        <form @submit.prevent="handleImport">
            <input
                type="file"
                accept=".json,application/json"
                required
                ref="fileInput"
                class="border rounded px-2 md:px-4 py-1"
                :disabled="importing"
            />
            <div class="mt-8 flex justify-end">
                <pwdsafe-button type="submit" :disabled="importing">
                    {{ importing ? 'Importing…' : 'Import' }}
                </pwdsafe-button>
            </div>
        </form>
    </pwdsafe-modal>
</template>
<script setup>
import { ref } from 'vue'
import { encryptCredentialV2 } from '../vault.js'

const props = defineProps({
    groupid: { type: Number, required: true },
})

const modalRef = ref(null)
const fileInput = ref(null)
const importing = ref(false)

defineExpose({
    openImport: () => {
        error.value = ''
        successMessage.value = ''
        modalRef.value?.openModal()
    },
})
const error = ref('')
const successMessage = ref('')

const open = (openModal) => {
    error.value = ''
    successMessage.value = ''
    openModal()
}

const handleImport = async () => {
    error.value = ''
    successMessage.value = ''

    const file = fileInput.value?.files?.[0]
    if (!file) return

    importing.value = true
    try {
        let rows
        try {
            rows = JSON.parse(await file.text())
        } catch {
            error.value = 'Cannot parse file. Is it valid JSON?'
            return
        }

        if (!Array.isArray(rows)) {
            error.value = 'JSON detected, but base element is not an array.'
            return
        }

        const valid = rows.filter((r) => r.name && r.username && r.password)
        const skipped = rows.length - valid.length

        const { data: pubkeysData } = await axios.get(
            `/api/groups/${props.groupid}/pubkeys`,
        )

        const credentials = await Promise.all(
            valid.map(async (row) => ({
                name: row.name,
                url: row.url ?? null,
                username: row.username,
                notes: row.notes ?? '',
                encrypted: await Promise.all(
                    pubkeysData.users.map(async ({ id, pubkey }) => ({
                        userid: id,
                        data: await encryptCredentialV2(
                            String(row.password),
                            pubkey,
                        ),
                    })),
                ),
            })),
        )

        const { data } = await axios.post('/import', {
            group: props.groupid,
            credentials,
        })

        successMessage.value =
            skipped > 0
                ? `Imported ${data.count} credentials. ${skipped} skipped due to missing fields.`
                : `Imported ${data.count} credentials successfully.`

        fileInput.value.value = ''
        setTimeout(() => window.location.reload(), 1500)
    } catch {
        error.value = 'Import failed. Please try again.'
    } finally {
        importing.value = false
    }
}
</script>
