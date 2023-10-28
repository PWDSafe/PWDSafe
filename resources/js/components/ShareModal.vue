<template>
    <PwdsafeModal v-on:modal-close="resetData">
        <template v-slot:trigger="{ openModal }">
            <PwdsafeButton theme="secondary" @click="openModal" title="Share">
                <ShareIcon class="h-5 w-5"></ShareIcon>
            </PwdsafeButton>
        </template>
        <form
            method="post"
            :action="'/credential/' + credential.id + '/share'"
            @submit.prevent="shareCredential"
        >
            <h2 class="text-xl">Share credential</h2>
            <div class="mt-4 bg-gray-600 px-4 py-2 rounded shadow">
                <h5 class="text-lg">{{ credential.site }}</h5>
                <h6 class="mb-2 text-gray-700 dark:text-gray-300">
                    {{ credential.username }}
                </h6>
                <p class="line-clamp-3 text-sm">{{ credential.notes }}</p>
            </div>
            <div class="mt-4">
                <span class="text-lg mb-1 block">Expires at</span>
                <PwdsafeInput
                    type="datetime-local"
                    :min="formatDate(new Date())"
                    :max="formatDate(new Date().addDays(30))"
                    v-model="expireAt"
                />
            </div>
            <div class="mt-4">
                <label>
                    <input type="checkbox" v-model="burnAfterRead" /> Burn after
                    read
                </label>
                <p class="italic text-sm">
                    This allows anyone with the link to look at the data only
                    once.
                </p>
            </div>
            <div class="mt-4">
                URL
                <PwdsafeInput
                    type="text"
                    readonly
                    placeholder="Not yet shared..."
                    v-model="url"
                ></PwdsafeInput>
                <p
                    class="text-green-500 dark:text-green-300"
                    v-if="url.length > 0"
                >
                    Link created!
                </p>
            </div>
            <div class="flex justify-end py-2" v-if="url.length === 0">
                <div>
                    <PwdsafeButton type="submit">Create link</PwdsafeButton>
                </div>
            </div>
        </form>
    </PwdsafeModal>
</template>
<script setup>
import PwdsafeButton from './Button.vue'
import { ShareIcon } from '@heroicons/vue/24/outline'
import PwdsafeModal from './Modal.vue'
import PwdsafeInput from './Input.vue'
import { ref } from 'vue'

const props = defineProps({
    credential: {
        type: Object,
        required: true,
    },
})
const shareCredential = () => {
    window.axios
        .post('/credential/' + props.credential.id + '/share', {
            expire_at: expireAt.value,
            burn_after_read: burnAfterRead.value,
        })
        .then((res) => {
            url.value = res.data.url
        })
}

Date.prototype.addDays = function (days) {
    var date = new Date(this.valueOf())
    date.setDate(date.getDate() + days)
    return date
}
const formatDate = (date) => {
    return new Date(date.getTime() - date.getTimezoneOffset() * 60000)
        .toISOString()
        .slice(0, -8)
}

const expireAt = ref(formatDate(new Date().addDays(7)))
const burnAfterRead = ref(true)
const url = ref('')
const resetData = () => {
    url.value = ''
    expireAt.value = formatDate(new Date().addDays(7))
    burnAfterRead.value = true
}
</script>
