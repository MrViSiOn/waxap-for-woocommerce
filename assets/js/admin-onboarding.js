/* global jQuery, waxapOnboarding */
(function ($) {
    'use strict';

    var OB = {
        data: window.waxapOnboarding || {},
        pollTimer: null,
        pollCount: 0,
        MAX_POLLS: 72, // 6 minutos a intervalos de 5s

        init: function () {
            $('#wan-ob-register-form').on('submit', this.onRegister.bind(this));
            $(document).on('click', '#wan-ob-pay-btn', this.onPayClick.bind(this));
            $(document).on('click', '#wan-ob-already-paid', this.onAlreadyPaid.bind(this));

            // Si hay tenant_id pero no api_key, arrancamos en el paso 2
            if (this.data.step === '2') {
                this.showStep(2);
            }

            // El usuario vuelve de Stripe (pago completado o en proceso):
            // ocultar el botón de pago y arrancar el polling directamente.
            if (this.data.paymentReturned === '1' && this.data.step === '2') {
                $('#wan-ob-pay-btn').hide();
                $('#wan-ob-pay-error').hide();
                $('#wan-ob-polling-wrap').show();
                this.startPolling();
            }
        },

        /* ---- Paso 1: Registro ---- */

        onRegister: function (e) {
            e.preventDefault();
            var $form = $(e.target);
            var $btn  = $form.find('[type=submit]');
            var $err  = $('#wan-ob-register-error');

            $err.hide().text('');
            $btn.prop('disabled', true).text('Creando cuenta…');

            $.post(this.data.ajaxUrl, {
                action:   'wan_onboarding_register',
                nonce:    this.data.nonce,
                email:    $form.find('[name=email]').val(),
                password: $form.find('[name=password]').val(),
            })
            .done(function (res) {
                if (!res.success) {
                    $err.text(res.data.message).show();
                    $btn.prop('disabled', false).text('Crear cuenta');
                    return;
                }
                OB.showStep(2);
            })
            .fail(function () {
                $err.text('Error de red. Comprueba tu conexión e inténtalo de nuevo.').show();
                $btn.prop('disabled', false).text('Crear cuenta');
            });
        },

        /* ---- Paso 2: Pago ---- */

        onPayClick: function () {
            var $btn = $('#wan-ob-pay-btn');
            $btn.prop('disabled', true).text('Preparando enlace de pago…');
            $('#wan-ob-pay-error').hide().text('');

            $.post(OB.data.ajaxUrl, {
                action: 'wan_onboarding_checkout_url',
                nonce:  OB.data.nonce,
            })
            .done(function (res) {
                if (!res.success) {
                    $('#wan-ob-pay-error').text(res.data.message).show();
                    $btn.prop('disabled', false).text('Ir a pagar →');
                    return;
                }
                window.open(res.data.url, '_blank');
                $btn.text('Pago abierto en nueva pestaña ↗');
                $('#wan-ob-polling-wrap').show();
                OB.startPolling();
            })
            .fail(function () {
                $('#wan-ob-pay-error').text('Error de red. Inténtalo de nuevo.').show();
                $btn.prop('disabled', false).text('Ir a pagar →');
            });
        },

        onAlreadyPaid: function () {
            $('#wan-ob-polling-wrap').show();
            OB.startPolling();
        },

        /* ---- Polling de activación ---- */

        startPolling: function () {
            if (this.pollTimer) return;
            this.pollCount = 0;
            $('#wan-ob-polling-status').text('Esperando confirmación del pago…');
            this.pollTimer = setInterval(function () { OB.pollOnce(); }, 5000);
            this.pollOnce();
        },

        stopPolling: function () {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        pollOnce: function () {
            OB.pollCount++;
            if (OB.pollCount > OB.MAX_POLLS) {
                OB.stopPolling();
                $('#wan-ob-polling-status').text('El tiempo de espera expiró. Si completaste el pago, recarga esta página.');
                return;
            }

            $.post(OB.data.ajaxUrl, {
                action: 'wan_onboarding_poll',
                nonce:  OB.data.nonce,
            })
            .done(function (res) {
                if (!res.success) return;
                if (res.data.status === 'active') {
                    OB.stopPolling();
                    OB.showStep(3);
                }
            });
        },

        /* ---- Navegación ---- */

        showStep: function (n) {
            $('.wan-ob-step').hide();
            $('#wan-ob-step-' + n).show();
        },
    };

    $(document).ready(function () {
        if ($('#wan-ob-step-1').length) {
            OB.init();
        }
    });

})(jQuery);
