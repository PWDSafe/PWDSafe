<template>
    <div
        class="card space-between flex w-full max-w-lg flex-col overflow-hidden rounded-md bg-white shadow dark:bg-gray-700"
    >
        <div class="card-body flex-1 p-4">
            <h5 class="text-xl">{{ credential.site }}</h5>
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
                    <ShareModal :credential="credential" />
                    <pwdsafe-modal
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
                                <pwdsafe-label for="site" class="mb-1"
                                    >Site</pwdsafe-label
                                >
                                <pwdsafe-input
                                    name="site"
                                    id="site"
                                    v-model="credentialint.site"
                                />
                            </div>
                            <div class="mb-2">
                                <pwdsafe-label for="username" class="mb-1"
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
                                    class="flex justify-between items-end mb-2"
                                >
                                    <pwdsafe-label for="password" class="mb-1">
                                        Password
                                    </pwdsafe-label>
                                    <pwdsafe-passwordgen
                                        button-size="small"
                                        @generated="
                                            (event) => {
                                                password = event
                                            }
                                        "
                                    />
                                </div>
                                <textarea
                                    v-model="password"
                                    :disabled="!passwordLoaded"
                                    :placeholder="
                                        !passwordLoaded ? 'Loading...' : ''
                                    "
                                    rows="5"
                                    class="focus:shadow-outline-blue block w-full rounded-md border border-gray-300 bg-white px-3 py-2 leading-5 placeholder-gray-500 transition duration-150 ease-in-out focus:border-indigo-500 focus:placeholder-gray-400 focus:outline-none disabled:bg-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:disabled:bg-gray-900 sm:text-sm"
                                ></textarea>
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
import { ref, reactive } from 'vue'
import { toClipboard } from '@soerenmartius/vue3-clipboard'
import { EyeIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline'
import ShareModal from './ShareModal.vue'

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
})
const password = ref('')
const passwordLoaded = ref(false)
const credentialint = reactive(props.credential)

const getPassword = function () {
    axios.get('/pwdfor/' + props.credential.id).then((response) => {
        password.value = response.data.pwd
        passwordLoaded.value = true
    })
}
const copyPwd = function () {
    axios.get('/pwdfor/' + props.credential.id).then((response) => {
        toClipboard(response.data.pwd)
    })
}
const resetData = function () {
    password.value = ''
    passwordLoaded.value = false
}
const saveCredentials = function () {
    axios
        .put('/credential/' + props.credential.id, {
            creds: credentialint.site,
            credu: credentialint.username,
            credp: password.value,
            credn: credentialint.notes,
            currentgroupid: credentialint.groupid,
        })
        .then(() => {
            window.location.reload()
        })
}
</script>
