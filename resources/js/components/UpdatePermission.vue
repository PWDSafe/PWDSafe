<template>
    <div class="flex flex-col justify-start gap-y-2">
        <pwdsafe-select name="permission" @selected="updatePermission">
            <option value="read" :selected="currentPermission === 'read'">
                Read
            </option>
            <option value="write" :selected="currentPermission === 'write'">
                Read & write
            </option>
            <option value="admin" :selected="currentPermission === 'admin'">
                Admin
            </option>
        </pwdsafe-select>
        <span
            v-show="updating"
            class="font-bold text-gray-600 transition duration-200"
        >
            Updating...
        </span>
        <span
            v-show="updated"
            class="font-bold text-green-600 transition duration-200"
        >
            Updated!
        </span>
    </div>
</template>
<script setup lang="ts">
import { PropType, ref } from 'vue'
import axios from 'axios'
type PermissionType = 'read' | 'write' | 'admin'

const props = defineProps({
    userid: Number,
    groupid: Number,
    permission: String as PropType<PermissionType>,
})

const currentPermission = ref(props.permission)
const updated = ref(false)
const updating = ref(false)

const updatePermission = function (data) {
    const newpermission = data.target.value
    updating.value = true
    axios
        .patch('/groups/' + props.groupid + '/members/' + props.userid, {
            permission: newpermission,
        })
        .then(() => {
            currentPermission.value = newpermission
            updated.value = true
            updating.value = false
            setTimeout(() => {
                updated.value = false
            }, 3000)
        })
}
</script>
