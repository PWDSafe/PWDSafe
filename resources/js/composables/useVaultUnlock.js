import { ref } from 'vue'
import { loadPrivkey } from '../vault.js'

const unlockModalVisible = ref(false)
let _resolve = null
let _reject = null

export function useVaultUnlock() {
    return { unlockModalVisible }
}

export function ensurePrivkey() {
    const privkey = loadPrivkey()
    if (privkey) {
        return Promise.resolve(privkey)
    }

    unlockModalVisible.value = true
    return new Promise((resolve, reject) => {
        _resolve = resolve
        _reject = reject
    })
}

export function resolveUnlock(privkey) {
    unlockModalVisible.value = false
    _resolve?.(privkey)
    _resolve = null
    _reject = null
}

export function rejectUnlock() {
    unlockModalVisible.value = false
    _reject?.(new Error('cancelled'))
    _resolve = null
    _reject = null
}
