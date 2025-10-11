
// JavaScript customizado para Gestão Financeira

$(document).ready(function() {
    // Auto-hide alerts após 5 segundos
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Confirmação de exclusão
    $('.btn-danger[href*="/delete/"]').on('click', function(e) {
        if (!confirm('Tem certeza que deseja excluir este item?')) {
            e.preventDefault();
        }
    });

    // Máscaras para campos
    if (typeof $ !== 'undefined') {
        // Máscara para telefone
        $('input[name="phone"], input[name="telefone"], input[name="phone_number"]').on('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d{4,5})(\d{4})/, '($1) $2-$3');
            }
            this.value = value;
        });

        // Máscara para valores monetários
        $('input[name="amount"], input[name="budget"]').on('input', function() {
            let value = this.value.replace(/[^\d.,]/g, '');
            this.value = value;
        });
    }

    // Validação de formulários
    $('form').on('submit', function(e) {
        let isValid = true;

        // Validar campos obrigatórios
        $(this).find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Validar email
        $(this).find('input[type="email"]').each(function() {
            if ($(this).val() && !isValidEmail($(this).val())) {
                $(this).addClass('is-invalid');
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            showAlert('Por favor, preencha todos os campos obrigatórios corretamente.', 'danger');
        }
    });

    // Função para validar email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Função para mostrar alertas
    function showAlert(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        $('.container-fluid').prepend(alertHtml);
    }

    // Tooltip para botões
    $('[data-toggle="tooltip"]').tooltip();

    // Sidebar toggle para mobile
    $('#sidebarToggleTop').on('click', function() {
        $('#accordionSidebar').toggleClass('toggled');
    });

    initNotifications();
});

// Função para formatar moeda
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Função para formatar data
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleDateString('pt-BR');
}

function formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}

// Função para exportar tabela para CSV
function exportTableToCSV(tableId, filename) {
    const csv = [];
    const rows = document.querySelectorAll(`#${tableId} tr`);

    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');

        for (let j = 0; j < cols.length - 1; j++) { // -1 para excluir coluna de ações
            row.push(cols[j].innerText);
        }
        csv.push(row.join(','));
    }

    downloadCSV(csv.join('\n'), filename);
}

function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function initNotifications() {
    const bell = document.querySelector('#notificationsBell');
    if (!bell) return;

    const badge = bell.querySelector('.notification-count');
    const list = document.querySelector('#notificationsList');
    const soundEl = document.querySelector('#notificationSound');
    let knownIds = new Set();

    const fetchFeed = () => {
        fetch('/notifications/feed')
            .then((res) => res.json())
            .then((data) => {
                const items = data.items || [];
                renderNotifications(items);
                playIfNew(items);
            })
            .catch(() => {});
    };

    const renderNotifications = (items) => {
        if (!list) return;
        list.innerHTML = '';
        if (!items.length) {
            list.innerHTML = '<li class="dropdown-item text-muted small">Sem notificações</li>';
            badge.textContent = '';
            badge.classList.add('d-none');
            return;
        }
        badge.textContent = items.length;
        badge.classList.remove('d-none');

        items.forEach((item) => {
            const li = document.createElement('li');
            li.className = 'dropdown-item notification-item';
            li.dataset.id = item.id;
            li.innerHTML = `
                <div class="notification-title">${escapeHtml(item.title)}</div>
                <div class="notification-message small text-muted">${escapeHtml(item.message || '')}</div>
                <div class="notification-time small text-muted">${formatDateTime(item.trigger_at)}</div>
            `;
            list.appendChild(li);
        });
    };

    const playIfNew = (items) => {
        const newIds = items.filter((item) => !knownIds.has(item.id)).map((item) => item.id);
        if (newIds.length && soundEl) {
            soundEl.currentTime = 0;
            soundEl.play().catch(() => {});
        }
        knownIds = new Set(items.map((item) => item.id));
    };

    const markAllRead = () => {
        const ids = Array.from(knownIds);
        if (!ids.length) return;
        fetch('/notifications/mark-read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids }),
        }).then(() => {
            knownIds.clear();
            fetchFeed();
        }).catch(() => {});
    };

    bell.addEventListener('show.bs.dropdown', () => {
        markAllRead();
    });

    fetchFeed();
    setInterval(fetchFeed, 60000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text || '';
    return div.innerHTML;
}
