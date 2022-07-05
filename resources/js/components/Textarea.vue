<template>
    <textarea
        v-model="currentValue"
        class="block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:shadow-outline-blue sm:text-sm transition duration-150 ease-in-out"></textarea>
</template>
<script setup>
import {defineEmits, ref, watch, useSlots} from 'vue'

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
