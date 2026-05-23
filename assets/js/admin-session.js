/* global jQuery, waNotifierData, ajaxurl */
(function ($) {
    'use strict';

    var WAN = {
        pollTimer: null,
        POLL_INTERVAL_MS: 2500,

        init: function () {
            $('#wa-notifier-register-form').on('submit', this.onRegister.bind(this));
            $(document).on('click', '#wa-notifier-link-btn', this.onLinkClick.bind(this));
            $(document).on('click', '#wa-notifier-unlink-btn', this.onUnlink.bind(this));
            $(document).on('click', '#wa-notifier-modal-close, #wa-notifier-modal-overlay', this.onModalClose.bind(this));
            $(document).on('click', '#wa-notifier-modal-content', function (e) { e.stopPropagation(); });
            $(document).on('submit', '#wa-notifier-test-form', this.onTestSubmit.bind(this));

            $(document).on('keyup', function (e) {
                if (e.key === 'Escape') WAN.onModalClose();
            });

            // Si ya hay sesión, refrescamos el estado en pantalla sin recargar la página.
            if (waNotifierData.hasSession === '1') {
                this.refreshStatus();
            }
        },

        /* ---- Registration ---- */

        onRegister: function (e) {
            e.preventDefault();
            var $form = $(e.target);
            this.setLoading($form, true);
            this.clearError('#wa-notifier-register-error');

            $.post(waNotifierData.ajaxUrl, {
                action:      'wa_notifier_register',
                nonce:       waNotifierData.nonce,
                wrapper_url: $form.find('[name=wrapper_url]').val(),
                email:       $form.find('[name=email]').val(),
                password:    $form.find('[name=password]').val(),
            })
            .done(function (res) {
                if (res.success) {
                    location.reload();
                } else {
                    WAN.showError('#wa-notifier-register-error', res.data.message);
                    WAN.setLoading($form, false);
                }
            })
            .fail(function () {
                WAN.showError('#wa-notifier-register-error', 'Error de red. Comprueba que el servidor está accesible.');
                WAN.setLoading($form, false);
            });
        },

        /* ---- Session linking ---- */

        onLinkClick: function () {
            this.clearError('#wa-notifier-link-error');
            this.setLoading('#wa-notifier-link-btn', true);

            $.post(waNotifierData.ajaxUrl, {
                action: 'wa_notifier_create_session',
                nonce:  waNotifierData.nonce,
            })
            .done(function (res) {
                WAN.setLoading('#wa-notifier-link-btn', false);
                if (res.success) {
                    WAN.openModal();
                    WAN.startPolling();
                } else {
                    WAN.showError('#wa-notifier-link-error', res.data.message);
                }
            })
            .fail(function () {
                WAN.setLoading('#wa-notifier-link-btn', false);
                WAN.showError('#wa-notifier-link-error', 'Error de red.');
            });
        },

        /* ---- Session polling ---- */

        // Sólo actualiza el dot/texto — nunca recarga la página.
        // Se usa en carga inicial cuando ya existe sesión.
        refreshStatus: function () {
            $.post(waNotifierData.ajaxUrl, {
                action: 'wa_notifier_poll_session',
                nonce:  waNotifierData.nonce,
            })
            .done(function (res) {
                if (!res.success) return;
                WAN.updateStatusDisplay(res.data.status, res.data.phone);
            });
        },

        startPolling: function () {
            this.isLinking = true;
            this.stopPolling();
            this.pollOnce();
            this.pollTimer = setInterval(function () { WAN.pollOnce(); }, WAN.POLL_INTERVAL_MS);
        },

        stopPolling: function () {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        pollOnce: function () {
            $.post(waNotifierData.ajaxUrl, {
                action: 'wa_notifier_poll_session',
                nonce:  waNotifierData.nonce,
            })
            .done(function (res) {
                if (!res.success) return;
                var data = res.data;

                // Update QR image if available
                if (data.qr) {
                    $('#wa-notifier-qr-img').attr('src', data.qr).show();
                    $('#wa-notifier-qr-loading').hide();
                }

                // Handle terminal states
                if (data.status === 'ready') {
                    WAN.stopPolling();
                    WAN.onAuthenticated(data.phone);
                } else if (data.status === 'failed' || data.status === 'disconnected') {
                    WAN.stopPolling();
                    WAN.showError('#wa-notifier-modal-error', 'La sesión falló. Inténtalo de nuevo.');
                }

                // Update status in page (if modal is not open)
                if ($('#wa-notifier-modal').is(':hidden')) {
                    WAN.updateStatusDisplay(data.status, data.phone);
                }
            });
        },

        onAuthenticated: function (phone) {
            this.closeModal();
            location.reload();
        },

        /* ---- Status display ---- */

        updateStatusDisplay: function (status, phone) {
            var labels = {
                created:       '⏳ Sesión creada, arrancando…',
                initializing:  '⏳ Iniciando…',
                qr_ready:      '📱 Esperando escaneo del QR…',
                authenticating:'🔐 Autenticando…',
                ready:         '✅ Conectado',
                disconnected:  '❌ Desconectado',
                failed:        '❌ Error en la sesión',
            };
            var label = labels[status] || '⏳ ' + status;
            $('#wa-notifier-status-text').text(label);

            if (phone && status === 'ready') {
                var formatted = phone.startsWith('+') ? phone : '+' + phone;
                $('#wan-phone-display-number').text(formatted);
                $('#wan-phone-display').show();
            }
            $('#wa-notifier-status-dot')
                .removeClass('wan-green wan-red wan-yellow')
                .addClass(status === 'ready' ? 'wan-green' : (status === 'disconnected' || status === 'failed') ? 'wan-red' : 'wan-yellow');

            // Show "re-link" button if disconnected
            if (status === 'disconnected' || status === 'failed') {
                $('#wa-notifier-link-btn').show();
            }

            // Show/hide test form based on status
            if (status === 'ready') {
                $('#wa-notifier-test-wrap').show();
            } else {
                $('#wa-notifier-test-wrap').hide();
            }
        },

        /* ---- Test message ---- */

        onTestSubmit: function (e) {
            e.preventDefault();
            var $form = $(e.target);
            var to = $form.find('[name=to]').val().trim();
            if (!to) {
                WAN.showTestResult('error', 'Introduce un número de teléfono.');
                return;
            }
            this.setLoading($form, true);
            this.clearTestResult();

            $.post(waNotifierData.ajaxUrl, {
                action: 'wa_notifier_send_test',
                nonce:  waNotifierData.nonce,
                to:     to,
            })
            .done(function (res) {
                WAN.setLoading($form, false);
                if (res.success) {
                    WAN.showTestResult('success', '✅ Mensaje enviado correctamente. Comprueba el teléfono.');
                } else {
                    WAN.showTestResult('error', res.data && res.data.message ? res.data.message : 'Error al enviar el mensaje.');
                }
            })
            .fail(function () {
                WAN.setLoading($form, false);
                WAN.showTestResult('error', 'Error de red. Comprueba la conexión con el servidor.');
            });
        },

        showTestResult: function (type, message) {
            $('#wa-notifier-test-result')
                .removeClass('notice-success notice-error')
                .addClass(type === 'success' ? 'notice-success' : 'notice-error')
                .text(message)
                .show();
        },

        clearTestResult: function () {
            $('#wa-notifier-test-result').text('').hide();
        },

        /* ---- Unlink ---- */

        onUnlink: function () {
            if (!confirm(waNotifierData.confirmUnlink)) return;
            $.post(waNotifierData.ajaxUrl, {
                action: 'wa_notifier_disconnect',
                nonce:  waNotifierData.nonce,
            })
            .always(function () { location.reload(); });
        },

        /* ---- Modal ---- */

        openModal: function () {
            $('#wa-notifier-qr-img').attr('src', '').hide();
            $('#wa-notifier-qr-loading').show();
            this.clearError('#wa-notifier-modal-error');
            $('#wa-notifier-modal').attr('aria-hidden', 'false').show();
            $('body').css('overflow', 'hidden');
        },

        closeModal: function () {
            this.stopPolling();
            $('#wa-notifier-modal').attr('aria-hidden', 'true').hide();
            $('body').css('overflow', '');
        },

        onModalClose: function () {
            this.closeModal();
        },

        /* ---- Helpers ---- */

        showError: function (selector, message) {
            $(selector).text(message).show();
        },

        clearError: function (selector) {
            $(selector).text('').hide();
        },

        setLoading: function (target, loading) {
            var $target = typeof target === 'string' ? $(target) : target;
            $target.prop('disabled', loading);
            $target.closest('form, p').find('.wa-notifier-spinner').toggleClass('is-active', loading);
        },
    };

    $(document).ready(function () {
        if (typeof waNotifierData !== 'undefined') {
            WAN.init();
        }
    });

})(jQuery);
