(function () {
    function createPopup() {
        let overlay = document.getElementById('appPopup');
        if (overlay) {
            return overlay;
        }

        overlay = document.createElement('div');
        overlay.id = 'appPopup';
        overlay.className = 'app-popup-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        overlay.innerHTML = `
            <section class="app-popup" role="alertdialog" aria-modal="true" aria-labelledby="appPopupTitle" aria-describedby="appPopupMessage">
                <div class="app-popup-icon" aria-hidden="true"></div>
                <p class="app-popup-kicker" id="appPopupTitle">Notification</p>
                <p class="app-popup-message" id="appPopupMessage"></p>
                <div class="app-popup-actions">
                    <button type="button" class="app-popup-secondary" data-popup-cancel>Keep appointment</button>
                    <button type="button" class="app-popup-primary" data-popup-ok>OK</button>
                </div>
            </section>`;
        document.body.appendChild(overlay);
        return overlay;
    }

    function closePopup(overlay, result) {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
        window.dispatchEvent(new CustomEvent('appPopupClosed'));
        if (overlay._resolve) {
            overlay._resolve(result);
            overlay._resolve = null;
        }
    }

    function openPopup(message, options) {
        const overlay = createPopup();
        const popup = overlay.querySelector('.app-popup');
        const icon = overlay.querySelector('.app-popup-icon');
        const kicker = overlay.querySelector('.app-popup-kicker');
        const messageElement = overlay.querySelector('.app-popup-message');
        const cancelButton = overlay.querySelector('[data-popup-cancel]');
        const okButton = overlay.querySelector('[data-popup-ok]');
        const settings = Object.assign({ type: 'info', title: 'Notification', confirm: false }, options);

        popup.dataset.type = settings.type;
        icon.textContent = settings.type === 'success' ? '✓' : settings.type === 'error' ? '!' : '?';
        kicker.textContent = settings.title;
        messageElement.textContent = message;
        cancelButton.hidden = !settings.confirm;
        okButton.textContent = settings.confirm ? 'Confirm cancellation' : 'OK';
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');

        return new Promise(function (resolve) {
            overlay._resolve = resolve;
            okButton.onclick = function () { closePopup(overlay, true); };
            cancelButton.onclick = function () { closePopup(overlay, false); };
            overlay.onclick = function (event) {
                if (event.target === overlay && settings.confirm) {
                    closePopup(overlay, false);
                }
            };
            popup.querySelector('[data-popup-ok]').focus();
        });
    }

    window.AppPopup = {
        show: function (message, type, redirect) {
            openPopup(message, { type: type || 'info', title: type === 'success' ? 'Success' : type === 'error' ? 'Something went wrong' : 'Notification' })
                .then(function () {
                    if (redirect) {
                        window.location.href = redirect;
                    }
                });
        },
        confirm: function (message) {
            return openPopup(message, { type: 'warning', title: 'Confirm cancellation', confirm: true });
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.confirmed === 'true') {
                    form.dataset.confirmed = 'false';
                    return;
                }
                event.preventDefault();
                AppPopup.confirm(form.dataset.confirm).then(function (confirmed) {
                    if (confirmed) {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                });
            });
        });
    });
}());
