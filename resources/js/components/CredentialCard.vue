<template>
    <div
        v-show="!headless"
        class="card space-between flex w-full max-w-lg flex-col overflow-hidden rounded-md bg-white shadow dark:bg-gray-700"
    >
        <div class="card-body flex-1 p-4">
            <h5 class="text-xl">{{ credential.name }}</h5>
            <h6 class="mb-2 text-gray-700 dark:text-gray-300">
                {{ credential.username }}
            </h6>
            <p class="line-clamp-3">{{ credential.notes }}</p>
        </div>
        <div
            class="card-footer border-t bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-700"
        >
            <div class="flex justify-between">
                <div>
                    <span v-if="showgroupname">{{ groupname }}</span>
                    <span v-else>&nbsp;</span>
                </div>
                <div class="flex gap-x-2">
                    <pwdsafe-button
                        v-if="visitUrl"
                        theme="secondary"
                        :href="visitUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        title="Visit site"
                    >
                        <ArrowTopRightOnSquareIcon
                            class="h-5 w-5"
                        ></ArrowTopRightOnSquareIcon>
                    </pwdsafe-button>
                    <ShareModal :credential="credential" />
                    <pwdsafe-modal
                        ref="modalRef"
                        v-on:modal-open="getPassword"
                        v-on:modal-close="resetData"
                    >
                        <template v-slot:trigger="{ openModal }">
                            <pwdsafe-button
                                theme="secondary"
                                :data-id="credential.id"
                                title="Show"
                                @click="openModal"
                            >
                                <EyeIcon class="h-5 w-5"></EyeIcon>
                            </pwdsafe-button>
                        </template>
                        <form
                            method="post"
                            :action="'/credential/' + credential.id"
                            @submit.prevent="saveCredentials"
                        >
                            <input type="hidden" name="_method" value="put" />
                            <div class="mb-2">
                                <pwdsafe-label for="name" class="mb-1" required
                                    >Name</pwdsafe-label
                                >
                                <pwdsafe-input
                                    name="name"
                                    id="name"
                                    v-model="credentialint.name"
                                />
                            </div>
                            <div class="mb-2">
                                <pwdsafe-label for="url" class="mb-1"
                                    >URL</pwdsafe-label
                                >
                                <div class="flex gap-x-2">
                                    <pwdsafe-input
                                        name="url"
                                        id="url"
                                        v-model="credentialint.url"
                                    />
                                    <pwdsafe-button
                                        v-if="visitUrl"
                                        theme="secondary"
                                        :href="visitUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="Visit site"
                                    >
                                        <ArrowTopRightOnSquareIcon
                                            class="h-5 w-5"
                                        ></ArrowTopRightOnSquareIcon>
                                    </pwdsafe-button>
                                </div>
                            </div>
                            <div class="mb-2">
                                <pwdsafe-label
                                    for="username"
                                    class="mb-1"
                                    required
                                    >Username</pwdsafe-label
                                >
                                <pwdsafe-input
                                    name="username"
                                    id="username"
                                    v-model="credentialint.username"
                                />
                            </div>
                            <div class="mb-2">
                                <div
                                    class="mb-2 flex items-end justify-between"
                                >
                                    <pwdsafe-label
                                        for="password"
                                        class="mb-1"
                                        required
                                        >Password</pwdsafe-label
                                    >
                                    <pwdsafe-passwordgen
                                        v-if="canUpdate"
                                        button-size="small"
                                        @generated="
                                            (event) => {
                                                password = event
                                            }
                                        "
                                    />
                                </div>
                                <div class="flex gap-x-2">
                                    <!-- Masked input (default) -->
                                    <input
                                        v-if="!passwordVisible"
                                        type="password"
                                        value="****************"
                                        :placeholder="
                                            !passwordLoaded ? 'Loading...' : ''
                                        "
                                        readonly
                                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 leading-5 transition duration-150 ease-in-out placeholder:text-gray-500 disabled:bg-gray-200 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:disabled:bg-gray-900"
                                    />
                                    <!-- Visible textarea (for viewing/editing) -->
                                    <textarea
                                        v-else
                                        v-model="password"
                                        :disabled="!passwordLoaded"
                                        :readonly="!canUpdate"
                                        rows="4"
                                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 leading-5 transition duration-150 ease-in-out placeholder:text-gray-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none disabled:bg-gray-200 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:disabled:bg-gray-900"
                                    ></textarea>
                                    <!-- Copy + toggle buttons -->
                                    <div class="flex shrink-0 flex-col gap-y-1">
                                        <pwdsafe-button
                                            type="button"
                                            theme="secondary"
                                            size="small"
                                            :disabled="!passwordLoaded"
                                            @click="copyPasswordFromModal"
                                            title="Copy password"
                                        >
                                            <ClipboardDocumentListIcon
                                                class="h-4 w-4"
                                            />
                                        </pwdsafe-button>
                                        <pwdsafe-button
                                            type="button"
                                            theme="secondary"
                                            size="small"
                                            :disabled="!passwordLoaded"
                                            @click="
                                                passwordVisible =
                                                    !passwordVisible
                                            "
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
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <pwdsafe-label for="notes" class="mb-1"
                                    >Notes</pwdsafe-label
                                >
                                <pwdsafe-textarea
                                    name="notes"
                                    id="notes"
                                    rows="3"
                                    @changed="credentialint.notes = $event"
                                    >{{ credentialint.notes }}</pwdsafe-textarea
                                >
                            </div>
                            <div class="mb-2" v-if="canUpdate">
                                <pwdsafe-label for="notes" class="mb-1"
                                    >Move to group</pwdsafe-label
                                >
                                <pwdsafe-select
                                    name="group"
                                    id="group"
                                    @selected="
                                        credentialint.groupid = parseInt(
                                            $event.target.value,
                                        )
                                    "
                                >
                                    <option
                                        v-for="group in groups"
                                        :value="group.id"
                                        :selected="
                                            group.id === credential.groupid
                                        "
                                    >
                                        {{ group.name }}
                                    </option>
                                </pwdsafe-select>
                            </div>

                            <div
                                class="flex justify-between py-2"
                                v-if="canUpdate"
                            >
                                <pwdsafe-button
                                    :href="'/credential/' + credential.id"
                                    theme="danger"
                                >
                                    Delete
                                </pwdsafe-button>
                                <div>
                                    <pwdsafe-button type="submit">
                                        Save
                                    </pwdsafe-button>
                                </div>
                            </div>
                        </form>
                    </pwdsafe-modal>
                    <pwdsafe-button
                        theme="secondary"
                        @click.native="copyPwd"
                        title="Copy to clipboard"
                    >
                        <ClipboardDocumentListIcon
                            class="h-5 w-5"
                        ></ClipboardDocumentListIcon>
                    </pwdsafe-button>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, reactive, computed } from 'vue'
import { toClipboard } from '@soerenmartius/vue3-clipboard'
import {
    EyeIcon,
    EyeSlashIcon,
    ClipboardDocumentListIcon,
    ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'
import ShareModal from './ShareModal.vue'
import { decryptCredential, encryptCredentialV2 } from '../vault.js'
import { showToast } from '../composables/useToast.js'
import { ensurePrivkey } from '../composables/useVaultUnlock.js'
import { normalizeUrl } from '../utils/url.js'

const emit = defineEmits(['saved'])

const props = defineProps({
    credential: {
        type: Object,
    },
    groups: {
        type: Array,
    },
    showgroupname: {
        type: Boolean,
        default: false,
    },
    groupname: {
        type: String,
        default: '',
    },
    canUpdate: {
        type: Boolean,
    },
    headless: {
        type: Boolean,
        default: false,
    },
})

const modalRef = ref(null)
const password = ref('')
const passwordLoaded = ref(false)
const passwordVisible = ref(false)
const credentialint = reactive(props.credential)

const visitUrl = computed(() => normalizeUrl(credentialint.url))

const getPassword = async function () {
    try {
        const privkeyPem = await ensurePrivkey()
        const response = await axios.get('/pwdfor/' + props.credential.id)
        password.value = await decryptCredential(response.data.data, privkeyPem)
        passwordLoaded.value = true
    } catch {
        modalRef.value?.closeModal()
    }
}
const copyPwd = async function () {
    try {
        const privkeyPem = await ensurePrivkey()
        const response = await axios.get('/pwdfor/' + props.credential.id)
        toClipboard(await decryptCredential(response.data.data, privkeyPem))
        showToast('Copied!')
    } catch {
        // User cancelled unlock
    }
}
const copyPasswordFromModal = function () {
    if (password.value) {
        toClipboard(password.value)
        showToast('Copied!')
    }
}
const resetData = function () {
    password.value = ''
    passwordLoaded.value = false
    passwordVisible.value = false
}
defineExpose({
    openModal: () => modalRef.value?.openModal(),
    copyPwd,
})

const saveCredentials = async function () {
    const groupId = credentialint.groupid

    const { data: pubkeysData } = await axios.get(
        `/api/groups/${groupId}/pubkeys`,
    )
    const encrypted = await Promise.all(
        pubkeysData.users.map(async ({ id, pubkey }) => ({
            userid: id,
            data: await encryptCredentialV2(password.value, pubkey),
        })),
    )

    await axios.put('/credential/' + props.credential.id, {
        creds: credentialint.name,
        credurl: credentialint.url,
        credu: credentialint.username,
        credn: credentialint.notes,
        currentgroupid: groupId,
        encrypted,
    })

    modalRef.value?.closeModal()
    emit('saved')
}
</script>
