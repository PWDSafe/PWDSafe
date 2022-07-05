<template>
    <input
        ref="field"
        :value="modelValue"
        class="block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-indigo-500 focus:shadow-outline-blue sm:text-sm transition duration-150 ease-in-out"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
<script setup>
defineProps({
    modelValue: {
        type: String,
        default: '',
        required: false
    }
})
</script>
