<template>
    <component
        :is="attrs['href'] ? 'a' : 'button'"
        :class="[
            'rounded border transition duration-200',
            colors[props.theme],
            props.classes,
            sizeclasses[props.size],
        ]"
    >
        <slot></slot>
    </component>
</template>
<script setup lang="ts">
import { PropType, useAttrs } from 'vue'
type ButtonTheme = 'primary' | 'secondary' | 'danger' | 'warning'
type ButtonSize = 'small' | 'medium'

const attrs = useAttrs()

const props = defineProps({
    theme: {
        type: String as PropType<ButtonTheme>,
        default: 'primary',
    },
    classes: {
        type: String,
        default: '',
    },
    size: {
        type: String as PropType<ButtonSize>,
        default: 'medium',
    },
})

const colors = {
    primary:
        'text-indigo-500 border-indigo-500 hover:bg-indigo-500 hover:text-white dark:text-indigo-300 dark:border-indigo-300 dark:hover:border-indigo-500 dark:hover:text-indigo-100',
    secondary:
        'text-gray-600 border-gray-600 hover:bg-gray-600 hover:text-gray-100 dark:text-gray-400 dark:border-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200',
    danger: 'text-red-600 border-red-600 hover:bg-red-600 hover:text-red-100 dark:text-red-400 dark:border-red-400 dark:hover:border-red-600 dark:hover:bg-red-600 dark:hover:text-red-200',
    warning:
        'text-orange-600 border-orange-600 hover:bg-orange-600 hover:text-orange-100 dark:text-orange-300 dark:border-orange-300 dark:hover:border-orange-700 dark:hover:bg-orange-700 dark:hover:text-orange-200',
}

const sizeclasses = {
    small: 'px-2 py-0.5 text-sm',
    medium: 'px-4 py-1',
}
</script>
