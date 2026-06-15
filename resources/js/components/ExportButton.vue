<template>
    <pwdsafe-button theme="secondary" classes="flex items-center" @click="handleExport" :disabled="exporting">
        <heroicons-arrow-down-on-square-icon class="mr-1 h-5 w-5"></heroicons-arrow-down-on-square-icon>
        Export
    </pwdsafe-button>
</template>
<script setup>
import { ref } from 'vue'
import { loadPrivkey, decryptCredential } from '../vault.js'

const props = defineProps({
    groupid: {
        type: Number,
        required: true,
    },
    groupname: {
        type: String,
        required: true,
    },
})

const exporting = ref(false)

const handleExport = async () => {
    exporting.value = true
    try {
        const privkeyPem = loadPrivkey()
        const { data: credentials } = await axios.get(`/api/groups/${props.groupid}/export-data`)

        const rows = await Promise.all(
            credentials.map(async (cred) => ({
                name: cred.name,
                url: cred.url,
                username: cred.username,
                password: privkeyPem ? await decryptCredential(cred.data, privkeyPem) : cred.data,
                notes: cred.notes,
            })),
        )

        const sanitized = props.groupname
            .replace(/ /g, '_')
            .replace(/[^\w\s\d\-~,;[\]().]/g, '')
            .replace(/\.{2,}/g, '')
            .slice(0, 200)

        const filename = `pwdsafe_export_${sanitized}_${new Date().toISOString().slice(0, 10)}.json`
        const blob = new Blob([JSON.stringify(rows)], { type: 'application/json' })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = filename
        a.click()
        URL.revokeObjectURL(url)
    } finally {
        exporting.value = false
    }
}
</script>
