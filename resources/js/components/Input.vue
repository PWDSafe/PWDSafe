<template>
    <input
        ref="field"
        :value="modelValue"
        class="focus:shadow-outline-blue block w-full rounded-md border border-gray-300 bg-white px-3 py-2 leading-5 placeholder-gray-500 transition duration-150 ease-in-out focus:border-indigo-500 focus:placeholder-gray-400 focus:outline-none dark:border-gray-700 dark:bg-gray-800 sm:text-sm"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
<script setup>
defineProps({
    modelValue: {
        type: String,
        default: '',
        required: false,
    },
})
</script>
