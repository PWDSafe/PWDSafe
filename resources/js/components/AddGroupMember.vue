<template>
    <div class="mt-8 max-w-lg rounded-md bg-white shadow dark:bg-gray-700 overflow-hidden">
        <form @submit.prevent="handleSubmit">
            <div class="px-8 py-4">
                <h4 class="mb-4 text-xl">Share group</h4>
                <div class="mb-4">
                    <label
                        for="user_search"
                        class="mb-1 block text-sm font-medium leading-5 text-gray-700 dark:text-gray-300"
                        >User</label
                    >
                    <div class="relative">
                        <input
                            id="user_search"
                            ref="inputRef"
                            type="text"
                            v-model="searchQuery"
                            @input="onSearchInput"
                            @keydown.down.prevent="highlightNext"
                            @keydown.up.prevent="highlightPrev"
                            @keydown.enter.prevent="selectHighlighted"
                            @keydown.escape="closeDropdown"
                            @blur="onBlur"
                            :placeholder="selectedUser ? '' : 'Search by name or email…'"
                            autocomplete="off"
                            :class="[
                                'block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:text-gray-200',
                                selectedUser && !searchQuery ? 'pl-3 pr-8' : 'px-3',
                            ]"
                        />
                        <div
                            v-if="selectedUser && !searchQuery"
                            class="absolute inset-0 flex items-center pl-3 pr-8 pointer-events-none"
                        >
                            <span class="text-sm text-gray-800 dark:text-gray-200 truncate">
                                {{ selectedUser.name ? `${selectedUser.name} (${selectedUser.email})` : selectedUser.email }}
                            </span>
                        </div>
                        <button
                            v-if="selectedUser"
                            type="button"
                            @click="clearSelection"
                            class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                            aria-label="Clear selection"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <ul
                            v-if="showDropdown && results.length"
                            class="absolute z-10 mt-1 w-full rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg max-h-48 overflow-auto"
                        >
                            <li
                                v-for="(user, index) in results"
                                :key="user.id"
                                @mousedown.prevent="selectUser(user)"
                                :class="[
                                    'px-3 py-2 text-sm cursor-pointer',
                                    index === highlightedIndex
                                        ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-200'
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300',
                                ]"
                            >
                                <span v-if="user.name" class="font-medium">{{ user.name }}</span>
                                <span :class="user.name ? 'text-gray-500 dark:text-gray-400 ml-1' : ''">{{ user.email }}</span>
                            </li>
                        </ul>
                        <p v-if="showDropdown && searchQuery.length >= 2 && !results.length && !searching" class="absolute z-10 mt-1 w-full rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg px-3 py-2 text-sm text-gray-500">
                            No users found
                        </p>
                    </div>
                    <label
                        for="permission"
                        class="mb-1 mt-3 block text-sm font-medium leading-5 text-gray-700 dark:text-gray-300"
                        >Permission</label
                    >
                    <pwdsafe-select v-model="permission" id="permission">
                        <option value="read">Read</option>
                        <option value="write" selected>Read &amp; write</option>
                        <option value="admin">Admin</option>
                    </pwdsafe-select>
                    <pwdsafe-alert v-if="errorMessage" theme="danger" classes="mt-4">
                        {{ errorMessage }}
                    </pwdsafe-alert>
                </div>
            </div>
            <div
                class="flex justify-end gap-x-2 border-t bg-gray-50 px-8 py-4 dark:border-gray-800 dark:bg-gray-700"
            >
                <pwdsafe-button theme="secondary" :href="backlink">Back</pwdsafe-button>
                <pwdsafe-button type="submit" :disabled="submitting || !selectedUser">Share group</pwdsafe-button>
            </div>
        </form>
    </div>
</template>
<script setup>
import { ref } from 'vue'
import { loadPrivkey, decryptCredential, encryptCredentialV2 } from '../vault.js'

const props = defineProps({
    groupid: {
        type: Number,
        required: true,
    },
    backlink: {
        type: String,
        required: true,
    },
    existingMemberIds: {
        type: Array,
        default: () => [],
    },
})

const searchQuery = ref('')
const results = ref([])
const selectedUser = ref(null)
const highlightedIndex = ref(-1)
const showDropdown = ref(false)
const searching = ref(false)
const permission = ref('write')
const submitting = ref(false)
const errorMessage = ref('')
const inputRef = ref(null)

let debounceTimer = null

const onSearchInput = () => {
    selectedUser.value = null
    highlightedIndex.value = -1

    clearTimeout(debounceTimer)
    if (searchQuery.value.length < 2) {
        results.value = []
        showDropdown.value = false
        return
    }

    debounceTimer = setTimeout(async () => {
        searching.value = true
        showDropdown.value = true
        try {
            const { data } = await axios.get('/api/users/search', { params: { q: searchQuery.value } })
            results.value = data.filter((u) => !props.existingMemberIds.includes(u.id))
        } catch {
            results.value = []
        } finally {
            searching.value = false
        }
    }, 300)
}

const selectUser = (user) => {
    selectedUser.value = user
    searchQuery.value = ''
    showDropdown.value = false
    highlightedIndex.value = -1
    errorMessage.value = ''
}

const clearSelection = () => {
    selectedUser.value = null
    searchQuery.value = ''
    results.value = []
    showDropdown.value = false
    highlightedIndex.value = -1
    inputRef.value?.focus()
}

const closeDropdown = () => {
    showDropdown.value = false
    highlightedIndex.value = -1
}

const onBlur = () => {
    setTimeout(closeDropdown, 150)
}

const highlightNext = () => {
    if (results.value.length) {
        highlightedIndex.value = (highlightedIndex.value + 1) % results.value.length
    }
}

const highlightPrev = () => {
    if (results.value.length) {
        highlightedIndex.value = highlightedIndex.value <= 0
            ? results.value.length - 1
            : highlightedIndex.value - 1
    }
}

const selectHighlighted = () => {
    if (highlightedIndex.value >= 0 && results.value[highlightedIndex.value]) {
        selectUser(results.value[highlightedIndex.value])
    }
}

const handleSubmit = async () => {
    if (!selectedUser.value) {
        errorMessage.value = 'Please select a user.'
        return
    }

    submitting.value = true
    errorMessage.value = ''

    try {
        const privkeyPem = loadPrivkey()

        let prepareData
        try {
            const { data } = await axios.post(`/api/groups/${props.groupid}/members/prepare`, {
                user_id: selectedUser.value.id,
                permission: permission.value,
            })
            prepareData = data
        } catch (e) {
            const msg = e.response?.data?.errors?.user_id?.[0] ?? 'An error occurred. Please try again.'
            errorMessage.value = msg
            return
        }

        const encrypted = await Promise.all(
            prepareData.credentials.map(async (cred) => {
                const plaintext = privkeyPem ? await decryptCredential(cred.data, privkeyPem) : cred.data
                return {
                    credentialid: cred.id,
                    data: privkeyPem
                        ? await encryptCredentialV2(plaintext, prepareData.user.pubkey)
                        : cred.data,
                }
            }),
        )

        await axios.post(`/api/groups/${props.groupid}/members/confirm`, {
            user_id: prepareData.user.id,
            permission: permission.value,
            encrypted,
        })

        window.location.reload()
    } finally {
        submitting.value = false
    }
}
</script>
