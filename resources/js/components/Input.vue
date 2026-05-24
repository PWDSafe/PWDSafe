<template>
    <input
        ref="field"
        :value="modelValue"
        :class="[
            'block w-full rounded-md border px-3 py-2 leading-5 transition duration-150 ease-in-out placeholder:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none focus:placeholder:text-gray-400 sm:text-sm dark:bg-gray-800',
            error
                ? 'border-red-500 dark:border-red-500 focus:border-red-500'
                : 'border-gray-300 bg-white focus:border-indigo-500 dark:border-gray-700',
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
        required: false,
    },
    error: {
        type: Boolean,
        default: false,
    },
})
</script>
