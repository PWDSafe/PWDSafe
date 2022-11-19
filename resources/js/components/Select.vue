<template>
    <div class="relative inline-block" :class="wrapperclasses">
        <select
            v-bind="$attrs"
            class="block w-full appearance-none rounded border bg-white py-2 pl-3 pr-8 focus:border-2 focus:border-blue-400 disabled:bg-gray-200 dark:border-gray-700 dark:bg-gray-800"
            :class="classes"
            @change="$emit('selected', $event)"
        >
            <slot></slot>
        </select>
        <div
            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300"
        >
            <svg
                class="h-4 w-4 fill-current"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
            >
                <path
                    d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                />
            </svg>
        </div>
    </div>
</template>
<script>
export default {
    inheritAttrs: false,
    props: {
        wrapperclasses: {
            default: 'w-full',
        },
        classes: {
            default: '',
        },
    },
}
</script>
