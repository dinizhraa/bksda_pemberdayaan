<div>
    <header class="panel-head">
        <x-icon-badge icon="triangle-exclamation" tone="rose" class="size-12 text-lg" />
        <div>
            <h2 class="text-lg font-bold text-ink-900">Hapus akun</h2>
            <p class="mt-1 text-sm text-ink-500">Tindakan ini permanen. Pastikan Anda benar-benar ingin menghapus akun ini.</p>
        </div>
    </header>

    <div class="rounded-2xl border border-rose-200/70 bg-rose-50/60 p-5">
        <ul class="space-y-3 text-sm text-rose-900/80">
            <li class="flex items-start gap-3"><i class="fa-solid fa-trash-can mt-0.5 w-4 text-rose-500" aria-hidden="true"></i>Akun beserta seluruh datanya akan dihapus secara permanen.</li>
            <li class="flex items-start gap-3"><i class="fa-solid fa-ban mt-0.5 w-4 text-rose-500" aria-hidden="true"></i>Tindakan ini tidak dapat dibatalkan.</li>
            <li class="flex items-start gap-3"><i class="fa-solid fa-download mt-0.5 w-4 text-rose-500" aria-hidden="true"></i>Simpan terlebih dahulu data atau informasi yang masih Anda perlukan.</li>
        </ul>

        <button type="button" class="btn-danger mt-5" @click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
            <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Hapus akun saya
        </button>
    </div>
</div>
