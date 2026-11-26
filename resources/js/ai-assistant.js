const palette = {
    green: '#17816f',
    teal: '#43b7a5',
    mint: '#9ad9c9',
    blue: '#60a5c9',
    amber: '#e5b45b',
    coral: '#df8675',
    purple: '#9b8ac6',
    slate: '#91a4a0',
};

async function postJson(url, options) {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), 125_000);

    try {
        const response = await fetch(url, {
            ...options,
            credentials: 'same-origin',
            signal: controller.signal,
        });

        if (response.status === 401 || response.status === 419 || response.redirected) {
            throw new Error('Sesi admin mungkin telah berakhir. Muat ulang halaman, masuk kembali jika diminta, lalu coba lagi.');
        }

        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error(`Server aplikasi mengirim respons yang tidak dapat dibaca (HTTP ${response.status}). Coba muat ulang halaman.`);
        }

        return { response, data };
    } catch (error) {
        if (error instanceof Error && error.name === 'AbortError') {
            throw new Error('Permintaan melewati batas waktu. Coba kembali beberapa saat lagi.');
        }

        if (error instanceof TypeError) {
            throw new Error('Koneksi ke server aplikasi gagal. Pastikan server Laravel aktif dan halaman dibuka melalui alamat aplikasi yang sama, lalu coba lagi.');
        }

        throw error;
    } finally {
        window.clearTimeout(timeout);
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        ?? document.querySelector('input[name="_token"]')?.value
        ?? '';
}

function appendMessage(container, role, content) {
    const article = document.createElement('article');
    article.className = `flex gap-3 ${role === 'user' ? 'justify-end' : ''}`;

    if (role !== 'user') {
        const icon = document.createElement('span');
        icon.className = 'grid size-8 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-700';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = '<i class="fa-solid fa-robot text-xs"></i>';
        article.append(icon);
    }

    const bubble = document.createElement('div');
    bubble.className = `max-w-[88%] whitespace-pre-wrap break-words rounded-2xl px-4 py-3 text-sm leading-6 ${
        role === 'user' ? 'bg-brand-700 text-white' : 'border border-ink-100 bg-white/80 text-ink-700'
    }`;
    const messageContent = document.createElement('span');
    messageContent.dataset.messageContent = '';
    messageContent.textContent = content;
    bubble.append(messageContent);

    if (role === 'user') {
        const retry = document.createElement('button');
        retry.type = 'button';
        retry.dataset.aiRetryMessage = '';
        retry.className = 'mt-2 block text-[11px] font-semibold underline decoration-white/60 underline-offset-2';
        retry.textContent = 'Coba lagi';
        retry.hidden = true;
        bubble.append(retry);
    }

    article.append(bubble);

    if (role === 'user') {
        const icon = document.createElement('span');
        icon.className = 'grid size-8 shrink-0 place-items-center rounded-xl bg-ink-100 text-ink-600';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = '<i class="fa-solid fa-user text-xs"></i>';
        article.append(icon);
    }

    container.append(article);
    container.scrollTop = container.scrollHeight;

    return article;
}

function createMessageId() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
        const random = Math.floor(Math.random() * 16);
        const value = character === 'x' ? random : ((random & 0x3) | 0x8);

        return value.toString(16);
    });
}

function setupChat() {
    const chat = document.querySelector('[data-ai-chat]');
    const form = chat?.querySelector('#ai-chat-form');
    const input = chat?.querySelector('#ai-chat-input');
    const submit = chat?.querySelector('#ai-chat-submit');
    const messages = chat?.querySelector('#ai-chat-messages');
    const error = chat?.querySelector('#ai-chat-error');
    const errorMessage = chat?.querySelector('#ai-chat-error-message');
    const retryButton = chat?.querySelector('#ai-chat-retry');
    const endpoint = chat?.dataset.sendUrl;

    if (!chat || !form || !input || !submit || !messages || !error || !errorMessage || !retryButton || !endpoint) {
        return;
    }

    const headerTitle = chat.querySelector('header h2');
    const pendingMessages = new Map();
    let lastFailedMessage = null;
    let isSending = false;

    async function sendMessage(request) {
        if (isSending || submit.disabled) {
            return;
        }

        isSending = true;
        error.classList.add('hidden');
        errorMessage.textContent = '';
        retryButton.hidden = true;
        request.article.querySelector('[data-ai-retry-message]')?.setAttribute('hidden', '');
        submit.disabled = true;
        input.disabled = true;
        retryButton.disabled = true;
        submit.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>';

        try {
            const body = new FormData(form);
            body.set('message', request.message);
            body.set('client_message_id', request.id);
            const { response, data } = await postJson(endpoint, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body,
            });

            if (!response.ok) {
                const validationMessage = data.errors?.message?.[0];

                throw new Error(validationMessage ?? data.message ?? `Pesan belum dapat diproses (HTTP ${response.status}).`);
            }

            const answer = data.message?.content;

            if (typeof answer !== 'string' || answer.trim() === '') {
                throw new Error('AI belum mengirim jawaban. Pesan tetap tersimpan; tekan Coba lagi untuk melanjutkan.');
            }

            appendMessage(messages, 'model', answer);
            pendingMessages.delete(request.id);
            request.article.querySelector('[data-ai-retry-message]')?.remove();

            if (lastFailedMessage?.id === request.id) {
                lastFailedMessage = null;
            }

            if (data.title && headerTitle) {
                headerTitle.textContent = data.title;
                const conversationLink = chat.querySelector(
                    `nav a[data-conversation-id="${chat.dataset.conversationId}"] span.block`,
                );
                if (conversationLink) {
                    conversationLink.textContent = data.title;
                }
            }

            if (input.value.trim() === request.message) {
                input.value = '';
            }
        } catch (requestError) {
            errorMessage.textContent = requestError instanceof Error
                ? requestError.message
                : 'Terjadi kesalahan saat menghubungi AI.';
            error.classList.remove('hidden');
            retryButton.hidden = false;
            request.article.querySelector('[data-ai-retry-message]')?.removeAttribute('hidden');
            lastFailedMessage = request;

            if (!input.value.trim()) {
                input.value = request.message;
            }
        } finally {
            isSending = false;
            submit.disabled = false;
            input.disabled = false;
            retryButton.disabled = false;
            submit.innerHTML = '<i class="fa-solid fa-paper-plane" aria-hidden="true"></i>';
            input.focus();
        }
    }

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();

        if (!message || isSending || submit.disabled) {
            return;
        }

        let request = Array.from(pendingMessages.values()).find((pending) => pending.message === message);

        if (!request) {
            request = {
                id: createMessageId(),
                message,
                article: appendMessage(messages, 'user', message),
            };
            request.article.dataset.clientMessageId = request.id;
            request.article.querySelector('[data-ai-retry-message]').dataset.aiRetryMessage = request.id;
            pendingMessages.set(request.id, request);
        }

        input.value = '';
        await sendMessage(request);
    });

    messages.addEventListener('click', (event) => {
        const target = event.target instanceof Element
            ? event.target.closest('[data-ai-retry-message]')
            : null;

        if (!target || isSending || submit.disabled) {
            return;
        }

        const article = target.closest('article');
        const message = article?.querySelector('[data-message-content]')?.textContent?.trim();
        const id = target.dataset.aiRetryMessage;

        if (!article || !message || !id) {
            return;
        }

        const request = pendingMessages.get(id) ?? { id, message, article };
        pendingMessages.set(id, request);
        lastFailedMessage = request;
        void sendMessage(request);
    });

    retryButton.addEventListener('click', () => {
        if (lastFailedMessage) {
            void sendMessage(lastFailedMessage);
        }
    });

    messages.scrollTop = messages.scrollHeight;
}

function createChart(Chart, canvasId, config) {
    const canvas = document.getElementById(canvasId);

    if (!canvas) {
        return;
    }

    if (!(config.data?.labels ?? []).length) {
        const empty = document.createElement('p');
        empty.className = 'grid h-full place-items-center text-sm text-ink-400';
        empty.textContent = 'Belum ada data untuk filter ini.';
        canvas.replaceWith(empty);
        return;
    }

    new Chart(canvas, {
        ...config,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                tooltip: { intersect: false, mode: 'index' },
                ...config.options?.plugins,
            },
            ...config.options,
        },
    });
}

function chartDataset(label, values, color, extra = {}) {
    return {
        label,
        data: values,
        backgroundColor: color,
        borderColor: color,
        borderWidth: 2,
        borderRadius: 6,
        maxBarThickness: 36,
        ...extra,
    };
}

async function setupCharts() {
    const root = document.querySelector('[data-ai-analytics]');

    if (!root) {
        return;
    }

    let charts;
    try {
        charts = JSON.parse(root.dataset.aiCharts ?? '{}');
    } catch (error) {
        const notice = document.createElement('p');
        notice.className = 'rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800';
        notice.setAttribute('role', 'alert');
        notice.textContent = 'Data grafik tidak dapat dibaca. Muat ulang halaman untuk mencoba kembali.';
        root.prepend(notice);
        console.error('Unable to parse AI analytics chart data.', error);
        return;
    }

    let Chart;
    try {
        ({ default: Chart } = await import('chart.js/auto'));
    } catch (error) {
        const notice = document.createElement('p');
        notice.className = 'rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800';
        notice.setAttribute('role', 'alert');
        notice.textContent = 'Pustaka grafik tidak dapat dimuat. Muat ulang halaman untuk mencoba kembali.';
        root.prepend(notice);
        console.error('Unable to load Chart.js for AI analytics.', error);
        return;
    }

    const labels = (rows) => rows.map((row) => row.label);
    const values = (rows) => rows.map((row) => row.value);
    const baseBar = (rows, label, color, extra = {}) => ({
        type: 'bar',
        data: { labels: labels(rows), datasets: [chartDataset(label, values(rows), color, extra)] },
    });

    createChart(Chart, 'ai-chart-status', {
        type: 'doughnut',
        data: {
            labels: labels(charts.status ?? []),
            datasets: [{
                data: values(charts.status ?? []),
                backgroundColor: [palette.green, palette.coral, palette.slate, palette.amber],
                borderWidth: 0,
            }],
        },
        options: { cutout: '62%' },
    });
    createChart(Chart, 'ai-chart-kawasan', baseBar(charts.kawasan ?? [], 'Jumlah kelompok', palette.green));
    createChart(Chart, 'ai-chart-desa', baseBar(charts.desa ?? [], 'Jumlah kelompok', palette.teal));
    createChart(Chart, 'ai-chart-gender', {
        type: 'doughnut',
        data: {
            labels: labels(charts.gender ?? []),
            datasets: [{
                data: values(charts.gender ?? []),
                backgroundColor: [palette.blue, palette.coral],
                borderWidth: 0,
            }],
        },
        options: { cutout: '62%' },
    });

    const businesses = charts.business ?? [];
    createChart(Chart, 'ai-chart-business', {
        type: 'bar',
        data: {
            labels: labels(businesses),
            datasets: [
                chartDataset('Jumlah kelompok', values(businesses), palette.teal, { yAxisID: 'y' }),
                chartDataset('Bantuan (Rp)', businesses.map((row) => row.aid), palette.amber, { yAxisID: 'y1' }),
            ],
        },
        options: {
            scales: {
                y: { beginAtZero: true, position: 'left' },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } },
            },
        },
    });

    createChart(Chart, 'ai-chart-formation', {
        type: 'line',
        data: {
            labels: labels(charts.formation_year ?? []),
            datasets: [chartDataset('Jumlah kelompok', values(charts.formation_year ?? []), palette.green, {
                fill: true,
                tension: 0.32,
                pointRadius: 4,
                backgroundColor: 'rgba(23, 129, 111, 0.12)',
            })],
        },
        options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
    });
    createChart(Chart, 'ai-chart-nte', baseBar(charts.nte_by_kawasan ?? [], 'Total NTE', palette.purple));
}

function setupInsight() {
    const button = document.getElementById('ai-analytics-insight-button');
    const output = document.getElementById('ai-analytics-insight');
    const filterForm = document.querySelector('[data-ai-analytics] form');

    if (!button || !output || !filterForm) {
        return;
    }

    button.addEventListener('click', async () => {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        output.textContent = 'Menyusun insight berdasarkan statistik database...';
        output.classList.remove('hidden');

        try {
            const { response, data } = await postJson(button.dataset.url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: new URLSearchParams(new FormData(filterForm)),
            });

            if (!response.ok) {
                throw new Error(data.message ?? 'Insight belum dapat dibuat.');
            }

            output.textContent = data.insight;
        } catch (requestError) {
            output.textContent = requestError instanceof Error
                ? requestError.message
                : 'Terjadi kesalahan saat membuat insight.';
        } finally {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    });
}

function setupPrint() {
    document.querySelectorAll('[data-print-report]').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });
}

setupChat();
setupCharts();
setupInsight();
setupPrint();
