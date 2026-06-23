import { type MaybeRefOrGetter, toValue, ref } from 'vue'
import axios from 'axios'
import { ensurePrivkey } from './useVaultUnlock.js'
import { decryptCredential, encryptCredentialV2 } from '../vault.js'
import { showToast } from './useToast.js'

interface DropPayload {
    credentialId: number
    sourceGroupId: number
    name: string
    url: string | null
    username: string
    notes: string
}

export function useCredentialDrop(targetGroupId: MaybeRefOrGetter<number>) {
    const isDragOver = ref(false)

    const onDragEnter = (event: DragEvent) => {
        const target = event.currentTarget as HTMLElement
        if (!target.contains(event.relatedTarget as Node)) {
            isDragOver.value = true
        }
    }

    const onDragLeave = (event: DragEvent) => {
        const target = event.currentTarget as HTMLElement
        if (!target.contains(event.relatedTarget as Node)) {
            isDragOver.value = false
        }
    }

    const onDrop = async (event: DragEvent) => {
        isDragOver.value = false

        const groupId = toValue(targetGroupId)
        if (!groupId) {
            return
        }

        const raw = event.dataTransfer?.getData('application/json')
        if (!raw) {
            return
        }

        let data: DropPayload
        try {
            data = JSON.parse(raw)
        } catch {
            return
        }

        if (data.sourceGroupId === groupId) {
            return
        }

        try {
            const privkeyPem = await ensurePrivkey()

            const pwdResp = await axios.get(`/pwdfor/${data.credentialId}`)
            const decryptedPassword = await decryptCredential(
                pwdResp.data.data,
                privkeyPem,
            )

            const hasTotp = Boolean(
                pwdResp.data.has_totp && pwdResp.data.totp_secret,
            )
            const decryptedTotpSecret = hasTotp
                ? await decryptCredential(pwdResp.data.totp_secret, privkeyPem)
                : null

            const pubkeysResp = await axios.get(
                `/api/groups/${groupId}/pubkeys`,
            )
            const encrypted = await Promise.all(
                pubkeysResp.data.users.map(
                    async ({ id, pubkey }: { id: number; pubkey: string }) => ({
                        userid: id,
                        data: await encryptCredentialV2(
                            decryptedPassword,
                            pubkey,
                        ),
                        totp_secret: decryptedTotpSecret
                            ? await encryptCredentialV2(
                                  decryptedTotpSecret,
                                  pubkey,
                              )
                            : null,
                    }),
                ),
            )

            await axios.put(`/credential/${data.credentialId}`, {
                creds: data.name,
                credurl: data.url,
                credu: data.username,
                credn: data.notes,
                currentgroupid: groupId,
                has_totp: hasTotp,
                encrypted,
            })

            window.dispatchEvent(new CustomEvent('credential-moved'))
        } catch (err: any) {
            if (err?.message !== 'cancelled') {
                showToast('Failed to move credential')
            }
        }
    }

    return { isDragOver, onDragEnter, onDragLeave, onDrop }
}
