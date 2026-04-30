document.addEventListener('DOMContentLoaded', () => {
    const flash = document.getElementById('serverFlash');
    if (flash) {
        showToast(flash.dataset.message || '', flash.dataset.type || 'success');
    }

    const searchInput = document.getElementById('searchPollada');
    const filterChips = document.querySelectorAll('.chip[data-filter]');
    const polladaCards = document.querySelectorAll('.pollada-card');
    if (searchInput && polladaCards.length > 0) {
        const filterCards = () => {
            const query = searchInput.value.toLowerCase();
            const active = document.querySelector('.chip.active[data-filter]');
            const selected = active ? active.dataset.filter : 'todas';
            polladaCards.forEach(card => {
                const text = card.innerText.toLowerCase();
                const status = card.dataset.status;
                card.style.display = (text.includes(query) && (selected === 'todas' || selected === status)) ? 'block' : 'none';
            });
        };
        searchInput.addEventListener('input', filterCards);
        filterChips.forEach(chip => chip.addEventListener('click', () => {
            filterChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            filterCards();
        }));
    }

    const photoInput = document.getElementById('photoInput');
    const photoPreviewContainer = document.getElementById('photoPreviewContainer');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const photoPreview = document.getElementById('photoPreview');
    if (photoInput && photoPreviewContainer && photoPreview) {
        photoInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (evt) => {
                photoPreview.src = evt.target.result;
                photoPreviewContainer.style.display = 'flex';
                if (photoPlaceholder) photoPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    document.querySelectorAll('.obs-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.obs-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            const input = document.getElementById('observationInput');
            if (input) input.value = chip.dataset.value || chip.innerText.trim();
        });
    });

    document.querySelectorAll('[data-pending-qty]').forEach(button => {
        button.addEventListener('click', () => {
            const qty = button.dataset.pendingQty;
            const total = button.dataset.pendingTotal;
            document.getElementById('pendingQty').value = qty;
            document.getElementById('pendingResume').innerText = `${qty} cerveza(s) por ${total}. Identifica a la persona o mesa.`;
            document.getElementById('pendingRef').value = '';
            showModal('pendienteModal');
            setTimeout(() => document.getElementById('pendingRef').focus(), 100);
            if (navigator.vibrate) navigator.vibrate(60);
        });
    });

    document.querySelectorAll('.confirm-form').forEach(form => {
        form.addEventListener('submit', (event) => {
            const message = form.dataset.confirm || '¿Confirmar acción?';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });

    const btnPrint = document.getElementById('btnPrint');
    if (btnPrint) {
        btnPrint.addEventListener('click', () => window.print());
    }
});

function showModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}

function hideModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

function showToast(msg, type = 'success') {
    if (!msg) return;
    let toast = document.getElementById('appToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'appToast';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    toast.innerText = msg;
    toast.style.backgroundColor = type === 'danger' ? 'var(--danger)' : type === 'warning' ? 'var(--warning)' : type === 'yape' ? 'var(--yape)' : 'var(--success)';
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}
