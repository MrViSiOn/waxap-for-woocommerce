<?php
/**
 * Checkbox de consentimiento WhatsApp en el checkout de WooCommerce.
 *
 * @package WaNotifier
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WaNotifier\Checkout;

use WaNotifier\Settings;
use WC_Order;

final class CheckoutOptIn {

    public function register(): void {
        add_action( 'woocommerce_review_order_before_submit', [ $this, 'render_checkbox' ] );
        add_action( 'woocommerce_checkout_create_order', [ $this, 'save_opt_in' ], 10, 2 );
    }

    public function render_checkbox(): void {
        if ( ! Settings::is_connected() || ! Settings::has_session() ) {
            return;
        }
        ?>
        <div class="waxap-checkout-consent">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                <input
                    type="checkbox"
                    class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
                    name="wa_notifier_opt_in"
                    id="wa_notifier_opt_in"
                    value="1"
                    checked
                >
                <span class="waxap-consent-label">
                    <svg class="waxap-consent-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.553 4.122 1.523 5.855L0 24l6.29-1.494A11.954 11.954 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-4.964-1.344l-.356-.212-3.736.887.943-3.641-.232-.374A9.819 9.819 0 012.182 12C2.182 6.57 6.57 2.182 12 2.182c5.43 0 9.818 4.388 9.818 9.818 0 5.43-4.388 9.818-9.818 9.818z"/>
                    </svg>
                    <?php esc_html_e( 'Quiero recibir actualizaciones de mi pedido por WhatsApp', 'wa-notifier' ); ?>
                </span>
            </label>
        </div>
        <?php
    }

    /**
     * @param WC_Order            $order
     * @param array<string,mixed> $data
     */
    public function save_opt_in( WC_Order $order, array $data ): void {
        // La nonce del checkout ya fue verificada por WooCommerce antes de este hook.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $opt_in = isset( $_POST['wa_notifier_opt_in'] )
            && '1' === sanitize_text_field( wp_unslash( (string) $_POST['wa_notifier_opt_in'] ) );

        $order->update_meta_data( '_wa_notifier_opt_in', $opt_in ? '1' : '0' );
    }
}
