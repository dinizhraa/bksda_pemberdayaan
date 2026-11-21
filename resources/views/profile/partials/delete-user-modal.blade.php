<x-modal name="confirm-user-deletion" max-width="md" :show="$errors->userDeletion->isNotEmpty()" focusable>
    <form method="post" action="{{ route('profile.destroy') }}" class="p-6 sm:p-8">
        @csrf
        @method('delete')

        <x-icon-badge icon="triangle-exclamation" tone="rose" class="size-14 text-xl" />

        <h2 class="mt-5 text-xl font-extrabold tracking-tight text-ink-900">Yakin ingin menghapus akun?</h2>
        <p class="mt-2 text-sm text-ink-500">
            Seluruh data akun akan dihapus permanen. Masukkan password Anda untuk mengonfirmasi.
        </p>

        <div class="mt-6">
            <x-password-field id="delete_account_password" name="password" label="Password"
                autocomplete="current-password" placeholder="Masukkan password" :messages="$errors->userDeletion->get('password')" />
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
            <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
            <x-danger-button><i class="fa-solid fa-trash-can" aria-hidden="true"></i> Hapus akun</x-danger-button>
        </div>
    </form>
</x-modal>
