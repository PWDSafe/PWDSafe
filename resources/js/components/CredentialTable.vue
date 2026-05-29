<template>
    <div>
        <table v-if="localCredentials.length > 0" class="min-w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-600">
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Site
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Username
                    </th>
                    <th
                        v-if="showGroupName"
                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
                    >
                        Group
                    </th>
                    <th class="w-px px-4 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-700">
                <tr
                    v-for="(credential, index) in localCredentials"
                    :key="credential.id"
                    draggable="true"
                    @dragstart="onDragStart(credential, $event)"
                    class="border-b border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition duration-100 cursor-pointer"
                    @click="editForRow(index)"
                >
                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ credential.site }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                        {{ credential.username }}
                    </td>
                    <td v-if="showGroupName" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                        {{ credential.display_group_name ?? credential.group?.name }}
                    </td>
                    <td class="w-px px-4 py-3" @click.stop>
                        <div class="flex items-center gap-x-1">
                            <pwdsafe-button
                                theme="secondary"
                                title="Copy password"
                                @click="copyForRow(index)"
                            >
                                <heroicons-clipboard-document-list-icon class="h-5 w-5" />
                            </pwdsafe-button>
                            <pwdsafe-button
                                theme="secondary"
                                title="Show / Edit"
                                @click="editForRow(index)"
                            >
                                <EyeIcon class="h-5 w-5" />
                            </pwdsafe-button>
                            <ShareModal :credential="credential" />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div v-else class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            No credentials yet. Use the menu above to add one.
        </div>

        <!-- Hidden CredentialCard instances for modal + copy access -->
        <credential-card
            v-for="(credential, index) in localCredentials"
            :key="'cc-modal-' + credential.id"
            :ref="(el) => { if (el) credCardRefs[index] = el }"
            :credential="credential"
            :groups="groups"
            :can-update="canUpdate"
            :headless="true"
            class="hidden"
            @saved="refresh()"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { EyeIcon } from '@heroicons/vue/24/outline'
import ShareModal from './ShareModal.vue'

const props = defineProps<{
    credentials: any[]
    groups: any[]
    canUpdate: boolean
    showGroupName?: boolean
    groupId?: number
}>()

const localCredentials = ref([...props.credentials])
const credCardRefs = ref<any[]>([])

const refresh = async () => {
    if (!props.groupId) { return }
    credCardRefs.value = []
    const { data } = await axios.get(`/api/groups/${props.groupId}/credentials`)
    localCredentials.value = data
}

onMounted(() => window.addEventListener('credential-moved', refresh))
onUnmounted(() => window.removeEventListener('credential-moved', refresh))

defineExpose({ refresh })

const editForRow = (index: number) => {
    credCardRefs.value[index]?.openModal()
}

const copyForRow = (index: number) => {
    credCardRefs.value[index]?.copyPwd()
}

const onDragStart = (credential: any, event: DragEvent) => {
    event.dataTransfer!.effectAllowed = 'move'
    event.dataTransfer!.setData('application/json', JSON.stringify({
        credentialId: credential.id,
        sourceGroupId: credential.groupid,
        site: credential.site,
        username: credential.username,
        notes: credential.notes ?? '',
    }))
}
</script>
