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
                            <div class="mb-4" v-if="credential.has_totp">
                                <div
                                    class="mb-1 flex items-center justify-between"
                                >
                                    <pwdsafe-label>TOTP Code</pwdsafe-label>
                                    <Menu
                                        as="div"
                                        class="relative"
                                        :class="{
                                            invisible: !totpDecryptedSecret,
                                        }"
                                    >
                                        <MenuButton
                                            type="button"
                                            class="inline-flex items-center rounded border px-2 py-1 text-gray-600 transition duration-150 hover:bg-gray-100 focus:outline-none dark:border-gray-400 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200"
                                        >
                                            <EllipsisHorizontalIcon
                                                class="h-4 w-4"
                                            />
                                        </MenuButton>
                                        <transition
                                            enter-active-class="transition duration-100 ease-out"
                                            enter-from-class="transform scale-95 opacity-0"
                                            enter-to-class="transform scale-100 opacity-100"
                                            leave-active-class="transition duration-75 ease-in"
                                            leave-from-class="transform scale-100 opacity-100"
                                            leave-to-class="transform scale-95 opacity-0"
                                        >
                                            <MenuItems
                                                class="absolute right-0 z-10 mt-1 w-48 origin-top-right divide-y divide-gray-200 rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none dark:divide-gray-800 dark:bg-gray-600"
                                            >
                                                <div class="py-1">
                                                    <MenuItem
                                                        v-slot="{ active }"
                                                    >
                                                        <button
                                                            type="button"
                                                            :class="
                                                                totpMenuItemClass(
                                                                    active,
                                                                )
                                                            "
                                                            @click="
                                                                copyTotpSecret
                                                            "
                                                        >
                                                            <ClipboardDocumentListIcon
                                                                class="mr-1.5 h-4 w-4"
                                                            />
                                                            Copy TOTP secret
                                                        </button>
                                                    </MenuItem>
                                                </div>
                                                <div
                                                    v-if="canUpdate"
                                                    class="py-1"
                                                >
                                                    <MenuItem
                                                        v-slot="{ active }"
                                                    >
                                                        <button
                                                            type="button"
                                                            :class="[
                                                                totpMenuItemClass(
                                                                    active,
                                                                ),
                                                                'text-red-600 dark:text-red-400',
                                                            ]"
                                                            @click="
                                                                confirmingRemoveTotp = true
                                                            "
                                                        >
                                                            <TrashIcon
                                                                class="mr-1.5 h-4 w-4"
                                                            />
                                                            Delete TOTP
                                                        </button>
                                                    </MenuItem>
                                                </div>
                                            </MenuItems>
                                        </transition>
                                    </Menu>
                                </div>
                                <div
                                    v-if="!totpDecryptedSecret"
                                    class="flex items-center gap-x-3"
                                >
                                    <div
                                        class="h-8 w-32 animate-pulse rounded bg-gray-200 dark:bg-gray-600"
                                    ></div>
                                    <div
                                        class="h-8 w-8 animate-pulse rounded-full bg-gray-200 dark:bg-gray-600"
                                    ></div>
                                </div>
                                <div v-else class="flex items-center gap-x-3">
                                    <span
                                        class="font-mono text-2xl tracking-widest text-gray-900 dark:text-white"
                                        >{{ totpCodeFormatted }}</span
                                    >
                                    <div
                                        class="relative flex h-8 w-8 items-center justify-center"
                                    >
                                        <svg
                                            class="h-8 w-8 -rotate-90 transform"
                                            viewBox="0 0 32 32"
                                        >
                                            <circle
                                                cx="16"
                                                cy="16"
                                                r="13"
                                                fill="none"
                                                stroke-width="3"
                                                class="stroke-gray-200 dark:stroke-gray-600"
                                            />
                                            <circle
                                                cx="16"
                                                cy="16"
                                                r="13"
                                                fill="none"
                                                stroke-width="3"
                                                class="stroke-indigo-500"
                                                :stroke-dasharray="82"
                                                :stroke-dashoffset="
                                                    82 *
                                                    (1 - totpCountdown / 30)
                                                "
                                            />
                                        </svg>
                                        <span
                                            class="absolute text-xs font-medium"
                                            >{{ totpCountdown }}</span
                                        >
                                    </div>
                                    <pwdsafe-button
                                        type="button"
                                        theme="secondary"
                                        size="small"
                                        @click="copyTotp"
                                        title="Copy TOTP code"
                                    >
                                        <ClipboardDocumentListIcon
                                            class="h-4 w-4"
                                        />
                                    </pwdsafe-button>
                                </div>
                                <div
                                    v-if="confirmingRemoveTotp"
                                    class="mt-3 flex items-center gap-x-2"
                                >
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                        >Remove TOTP secret?</span
                                    >
                                    <pwdsafe-button
                                        type="button"
                                        theme="secondary"
                                        size="small"
                                        @click="confirmingRemoveTotp = false"
                                    >
                                        Cancel
                                    </pwdsafe-button>
                                    <pwdsafe-button
                                        type="button"
                                        theme="danger"
                                        size="small"
                                        @click="confirmRemoveTotp"
                                    >
                                        Yes, remove
                                    </pwdsafe-button>
                                </div>
                            </div>
                            <div
                                class="mb-2"
                                v-if="canUpdate && !credential.has_totp"
                            >
                                <pwdsafe-label class="mb-1"
                                    >TOTP Secret</pwdsafe-label
                                >
                                <pwdsafe-input
                                    type="text"
                                    v-model="newTotpInput"
                                    placeholder="e.g. JBSWY3DPEHPK3PXP"
                                    autocomplete="off"
                                />
                                <p
                                    class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Base32 secret from your service's 2FA setup
                                    page.
                                </p>
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
import { ref, reactive, computed, onUnmounted } from 'vue'
import { toClipboard } from '@soerenmartius/vue3-clipboard'
import {
    EyeIcon,
    EyeSlashIcon,
    ClipboardDocumentListIcon,
    ArrowTopRightOnSquareIcon,
    EllipsisHorizontalIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import * as OTPAuth from 'otpauth'
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
const totpDecryptedSecret = ref('')
const totpCode = ref('')
const totpCountdown = ref(30)
const totpInterval = ref(null)
const newTotpInput = ref('')
const removingTotp = ref(false)
const confirmingRemoveTotp = ref(false)

const visitUrl = computed(() => normalizeUrl(credentialint.url))
const totpCodeFormatted = computed(() =>
    totpCode.value
        ? totpCode.value.slice(0, 3) + ' ' + totpCode.value.slice(3)
        : '',
)

const startTotpTimer = () => {
    const updateCode = () => {
        const totp = new OTPAuth.TOTP({
            secret: OTPAuth.Secret.fromBase32(totpDecryptedSecret.value),
        })
        totpCode.value = totp.generate()
        totpCountdown.value = 30 - Math.floor((Date.now() / 1000) % 30)
    }
    updateCode()
    totpInterval.value = setInterval(updateCode, 1000)
}

const stopTotpTimer = () => {
    if (totpInterval.value) {
        clearInterval(totpInterval.value)
        totpInterval.value = null
    }
}

const getPassword = async function () {
    try {
        const privkeyPem = await ensurePrivkey()
        const response = await axios.get('/pwdfor/' + props.credential.id)
        password.value = await decryptCredential(response.data.data, privkeyPem)
        passwordLoaded.value = true
        if (response.data.has_totp && response.data.totp_secret) {
            totpDecryptedSecret.value = await decryptCredential(
                response.data.totp_secret,
                privkeyPem,
            )
            startTotpTimer()
        }
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
const copyTotp = function () {
    if (totpCode.value) {
        toClipboard(totpCode.value)
        showToast('TOTP code copied!')
    }
}
const copyTotpSecret = function () {
    if (totpDecryptedSecret.value) {
        toClipboard(totpDecryptedSecret.value)
        showToast('TOTP secret copied!')
    }
}
const confirmRemoveTotp = async function () {
    removingTotp.value = true
    confirmingRemoveTotp.value = false
    try {
        await saveCredentials()
    } catch {
        removingTotp.value = false
        confirmingRemoveTotp.value = true
    }
}
onUnmounted(() => stopTotpTimer())
const totpMenuItemClass = (active) =>
    [
        active
            ? 'bg-gray-100 dark:bg-gray-700 dark:text-white'
            : 'dark:bg-gray-600',
        'group flex w-full items-center px-4 py-2 text-sm text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:focus:bg-gray-700',
    ].join(' ')

const resetData = function () {
    password.value = ''
    passwordLoaded.value = false
    passwordVisible.value = false
    totpDecryptedSecret.value = ''
    totpCode.value = ''
    totpCountdown.value = 30
    newTotpInput.value = ''
    removingTotp.value = false
    confirmingRemoveTotp.value = false
    stopTotpTimer()
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

    let effectiveTotpSecret = null
    let hasTotpValue = false
    if (!removingTotp.value) {
        const secretToEncrypt =
            newTotpInput.value.trim() || totpDecryptedSecret.value
        if (secretToEncrypt) {
            hasTotpValue = true
            effectiveTotpSecret = secretToEncrypt
        }
    }

    const encrypted = await Promise.all(
        pubkeysData.users.map(async ({ id, pubkey }) => ({
            userid: id,
            data: await encryptCredentialV2(password.value, pubkey),
            totp_secret: effectiveTotpSecret
                ? await encryptCredentialV2(effectiveTotpSecret, pubkey)
                : null,
        })),
    )

    await axios.put('/credential/' + props.credential.id, {
        creds: credentialint.name,
        credurl: credentialint.url,
        credu: credentialint.username,
        credn: credentialint.notes,
        currentgroupid: groupId,
        has_totp: hasTotpValue,
        encrypted,
    })

    credentialint.has_totp = hasTotpValue
    modalRef.value?.closeModal()
    emit('saved')
}
</script>
