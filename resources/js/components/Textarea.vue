<template>
    <textarea
        v-model="currentValue"
        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 leading-5 placeholder:text-gray-500 transition duration-150 ease-in-out focus:border-indigo-500 focus:outline-none focus:placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 sm:text-sm"
    ></textarea>
</template>
<script setup>
import { ref, watch, useSlots } from 'vue'

let initialData = ''

const slots = useSlots()
if (slots.default && slots.default().length) {
    initialData = slots.default()[0].children
}

const currentValue = ref(initialData)
const emit = defineEmits()

watch(currentValue, (value) => {
    emit('changed', value)
})
</script>
