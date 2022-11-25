<template>
    <div
        :class="['rounded-md border p-4', colors[props.theme], props.classes]"
        role="alert"
    >
        <slot></slot>
    </div>
</template>
<script setup lang="ts">
import { PropType } from 'vue'
type AlertTheme = 'info' | 'secondary' | 'warning' | 'danger' | 'success'

const props = defineProps({
    theme: {
        type: String as PropType<AlertTheme>,
        default: 'info',
    },
    classes: {
        type: String,
        default: 'max-w-lg',
    },
})

const colors = {
    info: 'border-blue-100 bg-blue-200 text-blue-800 dark:border-blue-900 dark:bg-blue-900 dark:text-blue-200',
    secondary: 'border-gray-100 bg-gray-200 text-gray-800',
    danger: 'border-red-100 bg-red-200 text-red-800 dark:border-red-900 dark:bg-red-900 dark:text-red-200',
    warning:
        'border-orange-100 bg-orange-200 text-orange-800 dark:border-orange-900 dark:bg-orange-900 dark:text-orange-200',
    success:
        'border-green-100 bg-green-200 text-green-800 dark:border-green-900 dark:bg-green-900 dark:text-green-200',
}
</script>
