import { ref } from 'vue'

const message = ref(null)
let timer = null

export function showToast(msg) {
    message.value = msg
    clearTimeout(timer)
    timer = setTimeout(() => {
        message.value = null
    }, 2000)
}

export function useToast() {
    return { message }
}
