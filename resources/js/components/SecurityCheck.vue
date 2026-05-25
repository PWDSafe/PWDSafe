<template>
    <div class="container">
        <div class="mb-12 max-w-3xl">
            <h3 class="mb-2 text-2xl">Security check</h3>
            <p>
                The security check groups credentials that share the same
                password together. Consider changing the passwords for one or
                several credentials in each group to make sure that you use a
                unique password for each application/site.
            </p>
        </div>

        <template v-if="loading && !decryptionStarted">
            <div class="mb-4 rounded-md bg-gray-100 p-4 dark:bg-gray-700">
                <p class="text-gray-600 dark:text-gray-400">
                    Loading your credentials...
                </p>
            </div>
        </template>

        <template v-else-if="credentials && !loading && !decryptionStarted">
            <div class="max-w-3xl">
                <div
                    class="mb-4 flex items-center gap-2 text-gray-600 dark:text-gray-400"
                >
                    <span
                        >You have {{ totalCredentials }} credential{{
                            totalCredentials === 1 ? '' : 's'
                        }}
                        in your vault.</span
                    >
                </div>

                <template v-if="totalCredentials >= 50">
                    <div
                        class="mb-4 rounded-md bg-yellow-50 p-3 text-sm text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200"
                    >
                        With {{ totalCredentials }} credentials this may take a
                        minute. Continue?
                    </div>
                </template>

                <Button @click="startDecryption"> Start Security Check </Button>
            </div>
        </template>

        <div v-else-if="loading && decryptionStarted" class="max-w-3xl">
            <div
                class="mb-2 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400"
            >
                <span>Decrypting credentials…</span>
                <span
                    >{{ progressIndex }} of
                    {{ totalCredentials }} decrypted</span
                >
            </div>
            <div
                class="mb-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700"
            >
                <div
                    class="h-2 rounded-full bg-indigo-500 transition-all duration-300"
                    :style="{ width: progressPercentage + '%' }"
                ></div>
            </div>
            <button
                class="mt-1 text-sm text-gray-500 underline hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                @click="cancelDecryption"
            >
                Cancel
            </button>
        </div>

        <template v-else-if="hasCancelled">
            <pwdsafe-alert theme="warning" classes="max-w-3xl">
                <strong>Check interrupted.</strong> The security check was
                interrupted and could not determine the amount of duplicate
                credentials.
            </pwdsafe-alert>
        </template>

        <template v-else-if="groups.length > 0">
            <div
                v-for="(group, index) in groups"
                :key="index"
                class="mb-8 rounded-md bg-white shadow-md dark:bg-gray-600"
            >
                <h5
                    class="rounded-t-md border-b bg-gray-300 px-4 py-3 text-lg dark:border-gray-800 dark:bg-gray-700"
                >
                    Password group
                </h5>
                <div class="px-2 py-3">
                    <div
                        class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <credential-card
                            v-for="cred in group"
                            :key="cred.id"
                            :credential="cred"
                            :groups="writableGroups"
                            :showgroupname="true"
                            :groupname="cred.groupname"
                        ></credential-card>
                    </div>
                </div>
            </div>
        </template>

        <pwdsafe-alert
            v-else-if="decryptionStarted"
            theme="success"
            classes="max-w-3xl"
        >
            <strong>No credentials found!</strong> This means that your
            credentials all have different passwords.
        </pwdsafe-alert>

        <template v-else-if="!loading && credentials === null">
            <div class="max-w-3xl space-y-4">
                <pwdsafe-alert theme="info" classes="mb-0">
                    <strong>No credentials in vault.</strong> You have no
                    credentials defined, so there's nothing to check.
                </pwdsafe-alert>
            </div>
        </template>
    </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue'
import { loadPrivkey, decryptCredential } from '../vault.js'
import Button from './Button.vue'

const props = defineProps({
    writableGroups: {
        type: Array,
        default: () => [],
    },
    privateGroupUrl: {
        type: String,
        default: '/',
    },
    groupsUrl: {
        type: String,
        default: '/groups',
    },
})

const loading = ref(true)
const groups = ref([])
const credentials = ref(null)
const progressIndex = ref(0)
const cancelled = ref(false)
const hasCancelled = ref(false)
const decryptionStarted = ref(false)

const totalCredentials = computed(() =>
    credentials.value ? credentials.value.length : 0,
)
const progressPercentage = computed(() => {
    if (totalCredentials.value === 0) return 0
    return Math.round((progressIndex.value / totalCredentials.value) * 100)
})

async function loadCredentials() {
    const privkeyPem = loadPrivkey()
    const { data: creds } = await axios.get('/api/securitycheck')

    if (!privkeyPem || creds.length === 0) {
        loading.value = false
        return
    }

    credentials.value = creds
    loading.value = false
}

async function startDecryption() {
    if (!credentials.value) return

    const privkeyPem = loadPrivkey()
    if (!privkeyPem) {
        loading.value = false
        return
    }

    hasCancelled.value = false
    decryptionStarted.value = true
    loading.value = true
    progressIndex.value = 0

    const byPassword = {}
    let index = 0
    const batchSize = 10

    for (let i = 0; i < credentials.value.length; i += batchSize) {
        if (cancelled.value) {
            hasCancelled.value = true
            loading.value = false
            return
        }

        const batch = credentials.value.slice(i, i + batchSize)

        for (const cred of batch) {
            const pwd = await decryptCredential(cred.data, privkeyPem)
            if (!byPassword[pwd]) {
                byPassword[pwd] = []
            }
            byPassword[pwd].push(cred)
            index++
            progressIndex.value = index
        }

        await new Promise((resolve) => setTimeout(resolve, 0))
    }

    if (!cancelled.value) {
        groups.value = Object.values(byPassword).filter((g) => g.length > 1)
    }

    loading.value = false
    credentials.value = null
}

function cancelDecryption() {
    cancelled.value = true
}

onMounted(loadCredentials)
</script>
