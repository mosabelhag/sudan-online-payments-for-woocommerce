<?php
/**
 * Sudan Online Payments Payment Gateway Class
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Gateway_Sudan_Online_Payments Class.
 *
 * WooCommerce payment gateways must use WC_Gateway_ prefix per WooCommerce standards.
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
class WC_Gateway_Sudan_Online_Payments extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'sudan_online_payments';
		$this->icon               = apply_filters( 'sudan_online_payments_woocommerce_icon', '' );
		$this->has_fields         = true;
		$this->method_title       = __( 'Sudan Online Payments', 'sudan-online-payments-for-woocommerce' );
		$this->method_description = __( 'Accept payments via Sudanese Bank Transfers with receipt upload.', 'sudan-online-payments-for-woocommerce' );
		
		// Icon mapping
		$this->bank_icons = array(
			'bankak'   => 'bankak.png',
			'o-cash'   => 'o-cash.png',
			'fawry'    => 'fawery.png',
			'syberpay' => 'syberpay.png',
			'mycashi'  => 'mycashi.png',
			'bravo'    => 'bravo.png',
			'other'    => 'other.png',
		);

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );
		$this->accounts     = $this->get_option( 'accounts' ); // Stored as JSON or serialized array
		
		// Force enabled status for debug if needed, but relies on settings
		$this->enabled = $this->get_option( 'enabled' );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Check if the gateway is available for use.
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}
		return true;
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'sudan-online-payments-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Sudan Online Payments', 'sudan-online-payments-for-woocommerce' ),
				'default' => 'no',
			),
			'title' => array(
				'title'       => __( 'Title', 'sudan-online-payments-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'sudan-online-payments-for-woocommerce' ),
				'default'     => __( 'Bank Transfer (Sudan)', 'sudan-online-payments-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'sudan-online-payments-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'sudan-online-payments-for-woocommerce' ),
				'default'     => __( 'Make your payment directly into our bank account. Please upload your receipt.', 'sudan-online-payments-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'sudan-online-payments-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'sudan-online-payments-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			// We will output a custom field for the Accounts Table
			'accounts' => array(
				'type' => 'accounts_table',
			),
		);
	}

	/**
	 * Generate Accounts Table HTML
	 * Custom field type 'accounts_table'
	 */
	public function generate_accounts_table_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'       => '',
			'disabled'    => false,
			'class'       => '',
			'css'         => '',
			'placeholder' => '',
			'type'        => 'text',
			'desc_tip'    => false,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );
		$value = $this->get_option( $key );
        $accounts = is_string($value) ? json_decode($value, true) : $value;
		if (!is_array($accounts)) {
			$accounts = array();
		}

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php esc_html_e( 'Bank Accounts', 'sudan-online-payments-for-woocommerce' ); ?></label>
			</th>
			<td class="forminp">
				<div id="sudan-online-payments-accounts-wrapper">
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
							<tr>
								<th class="sort">&nbsp;</th>
								<th><?php esc_html_e( 'Bank Name', 'sudan-online-payments-for-woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Account Name', 'sudan-online-payments-for-woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Account Number', 'sudan-online-payments-for-woocommerce' ); ?></th>
                                <th><?php esc_html_e( 'Branch', 'sudan-online-payments-for-woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Phone', 'sudan-online-payments-for-woocommerce' ); ?></th>
							</tr>
						</thead>
						<tbody id="sudan-online-payments-accounts-list">
							<?php
                            // Handle existing accounts or empty row
                            // We will use JS to populate/manage this, but we render existing data
                            foreach ($accounts as $index => $account) {
                                // $this->render_account_row($index, $account); // Handled by JS
                            }
                            ?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="6">
									<a href="#" class="button button-primary sudan-online-payments-add-account"><?php esc_html_e( '+ Add Account', 'sudan-online-payments-for-woocommerce' ); ?></a>
									<a href="#" class="button sudan-online-payments-remove-account"><?php esc_html_e( 'Remove Selected', 'sudan-online-payments-for-woocommerce' ); ?></a>
								</th>
							</tr>
						</tfoot>
					</table>
                    <!-- Hidden input to store the JSON data -->
                    <input type="hidden" id="<?php echo esc_attr( $field_key ); ?>" name="<?php echo esc_attr( $field_key ); ?>" value="<?php echo esc_attr( json_encode($accounts) ); ?>" />
				</div>
                <p class="description"><?php esc_html_e( 'Enter the bank account details users can send money to.', 'sudan-online-payments-for-woocommerce' ); ?></p>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}
    
    private function render_account_row($index, $account) {
        // This is mainly for initial render if we wanted to do PHP rendering, 
        // but for simplicity with dynamic JS, we might rely on the hidden input and JS rendering.
        // However, for robust non-JS fallback (unlikely in WP Admin) or cleaner Code...
        // Let's implement the JS side to handle the display based on the hidden input value.
    }

	/**
	 * Payment Fields on Checkout
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( $this->description ) );
		}
        
        // Display Bank Accounts
        $accounts = $this->get_option('accounts');
        $accounts = is_string($accounts) ? json_decode($accounts, true) : $accounts;

        if ( ! empty( $accounts ) && is_array( $accounts ) ) {
            echo '<div class="sudan-online-payments-accounts-display">';
            echo '<h4>' . esc_html__( 'Select a Bank Account to Transfer:', 'sudan-online-payments-for-woocommerce' ) . '</h4>';
            echo '<div class="sudan-online-payments-accounts-grid">';
            foreach ( $accounts as $account ) {
				$bank_key = $account['bank_name'] ?? 'other';
				$icon_file = $this->bank_icons[ $bank_key ] ?? 'other.png';
				$icon_url = SUDAN_ONLINE_PAYMENTS_PLUGIN_URL . 'assets/images/' . $icon_file;
				
				$bank_labels = array(
					'bankak'   => 'Bankak | بنكك',
					'o-cash'   => 'O-Cash | اوو-كاش',
					'fawry'    => 'Fawry SD | فوري',
					'syberpay' => 'SyberPay | سايبر باي',
					'mycashi'  => 'MyCashi | ماي كاشي',
					'bravo'    => 'Bravo | برافو',
					'other'    => 'Other Bank | بنك آخر',
				);
				$bank_label = $bank_labels[ $bank_key ] ?? $account['bank_name'];

                echo '<div class="sudan-online-payments-account-card">';
				
				// Header
				echo '<div class="sudan-online-payments-card-header">';
				echo '<img src="' . esc_url( $icon_url ) . '" class="sudan-online-payments-card-icon" alt="' . esc_attr( $bank_label ) . '" />';
                echo '<span class="sudan-online-payments-card-title">' . esc_html( $bank_label ) . '</span>';
				echo '</div>';
				
				// Body
				echo '<div class="sudan-online-payments-card-body">';
                if (!empty($account['account_name'])) {
                    echo '<div class="sudan-online-payments-detail-row"><span class="label">' . esc_html__( 'Name:', 'sudan-online-payments-for-woocommerce' ) . '</span> <span class="value">' . esc_html( $account['account_name'] ) . '</span></div>';
                }
                
                if (!empty($account['account_number'])) {
                    echo '<div class="sudan-online-payments-detail-row highlight">';
                    echo '<span class="label">' . esc_html__( 'Account:', 'sudan-online-payments-for-woocommerce' ) . '</span>';
                    echo '<div class="sudan-online-payments-copy-wrapper">';
                    echo '<span class="value number-font">' . esc_html( $account['account_number'] ) . '</span>';
                    echo '<button type="button" class="sudan-online-payments-copy-btn" data-clipboard-text="' . esc_attr( $account['account_number'] ) . '" title="' . esc_attr__( 'Copy', 'sudan-online-payments-for-woocommerce' ) . '">';
                    echo '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
                    echo '<span class="sudan-online-payments-tooltip">' . esc_html__( 'Copied!', 'sudan-online-payments-for-woocommerce' ) . '</span>';
                    echo '</button>';
                    echo '</div>';
                    echo '</div>';
                }
                
                if (!empty($account['phone'])) {
                    echo '<div class="sudan-online-payments-detail-row"><span class="label">' . esc_html__( 'Phone:', 'sudan-online-payments-for-woocommerce' ) . '</span> <span class="value">' . esc_html( $account['phone'] ) . '</span></div>';
                }

                if (!empty($account['branch'])) {
                    echo '<div class="sudan-online-payments-detail-row"><span class="label">' . esc_html__( 'Branch:', 'sudan-online-payments-for-woocommerce' ) . '</span> <span class="value">' . esc_html( $account['branch'] ) . '</span></div>';
                }
				echo '</div>'; // End Body
                echo '</div>'; // End Card
            }
            echo '</div>'; // End Grid
            echo '</div>'; // End Display
        }

		echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
		
		// AJAX File Upload Input
		$nonce = wp_create_nonce( 'sudan_online_payments_upload_nonce' );
		echo '<div class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-receipt">' . esc_html__( 'Upload Receipt (Image Only)', 'sudan-online-payments-for-woocommerce' ) . ' <span class="required">*</span></label>
				
				<div class="sudan-online-payments-upload-container">
					<input type="file" id="' . esc_attr( $this->id ) . '-receipt" class="sudan-online-payments-file-input" accept="image/jpeg,image/png,image/jpg" />
					<input type="hidden" name="sudan_online_payments_receipt_id" id="sudan_online_payments_receipt_id" value="" />
					
					<!-- Security Nonce -->
					<input type="hidden" name="sudan_online_payments_upload_nonce_field" id="sudan_online_payments_upload_nonce_field" value="' . esc_attr( $nonce ) . '" />
					
					<div class="sudan-online-payments-upload-status" style="display:none; margin-top: 10px;">
						<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> ' . esc_html__( 'Uploading...', 'sudan-online-payments-for-woocommerce' ) . '
					</div>
					
					<div class="sudan-online-payments-upload-preview" style="display:none; margin-top: 10px;">
						<p class="sudan-online-payments-success-msg" style="color:green;">' . esc_html__( 'Receipt Uploaded Successfully', 'sudan-online-payments-for-woocommerce' ) . '</p>
						<img src="" id="sudan-online-payments-preview-img" style="max-width:150px; border:1px solid #ddd; padding:3px; border-radius:4px;" />
						<br>
						<button type="button" class="button sudan-online-payments-remove-upload" style="margin-top:5px; font-size:12px;">' . esc_html__( 'Remove / Change', 'sudan-online-payments-for-woocommerce' ) . '</button>
					</div>
				</div>

                <small>' . esc_html__( 'Please upload a clear image (JPG/PNG) of the bank transfer receipt.', 'sudan-online-payments-for-woocommerce' ) . '</small>
			  </div>';

		echo '<div class="clear"></div></fieldset>';
	}

	/**
	 * Handle AJAX File Upload
	 */
	public static function handle_ajax_upload() {
		// Verify nonce - use die=false to handle error gracefully
		if ( ! check_ajax_referer( 'sudan_online_payments_upload_nonce', 'security', false ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again.', 'sudan-online-payments-for-woocommerce' ) ) );
		}

		// Check if file was uploaded
		if ( ! isset( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No file uploaded.', 'sudan-online-payments-for-woocommerce' ) ) );
		}

		// Validate file array structure
		$file = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		
		// Check for upload errors
		if ( isset( $file['error'] ) && $file['error'] !== UPLOAD_ERR_OK ) {
			$error_messages = array(
				UPLOAD_ERR_INI_SIZE   => esc_html__( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'sudan-online-payments-for-woocommerce' ),
				UPLOAD_ERR_FORM_SIZE  => esc_html__( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'sudan-online-payments-for-woocommerce' ),
				UPLOAD_ERR_PARTIAL    => esc_html__( 'The uploaded file was only partially uploaded.', 'sudan-online-payments-for-woocommerce' ),
				UPLOAD_ERR_NO_FILE    => esc_html__( 'No file was uploaded.', 'sudan-online-payments-for-woocommerce' ),
				UPLOAD_ERR_NO_TMP_DIR => esc_html__( 'Missing a temporary folder.', 'sudan-online-payments-for-woocommerce' ),
				UPLOAD_ERR_CANT_WRITE => esc_html__( 'Failed to write file to disk.', 'sudan-online-payments-for-woocommerce' ),
				UPLOAD_ERR_EXTENSION  => esc_html__( 'File upload stopped by extension.', 'sudan-online-payments-for-woocommerce' ),
			);
			$error_message = isset( $error_messages[ $file['error'] ] ) ? $error_messages[ $file['error'] ] : esc_html__( 'Unknown upload error.', 'sudan-online-payments-for-woocommerce' );
			wp_send_json_error( array( 'message' => $error_message ) );
		}
		
		if ( ! isset( $file['name'], $file['type'], $file['tmp_name'], $file['error'], $file['size'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid file upload.', 'sudan-online-payments-for-woocommerce' ) ) );
		}
		
		// 1. Validate File Size (Max 5MB)
		if ( $file['size'] > 5 * 1024 * 1024 ) {
			wp_send_json_error( array( 'message' => esc_html__( 'File is too large. Max size 5MB.', 'sudan-online-payments-for-woocommerce' ) ) );
		}

		// 2. Validate MIME Type (Strictly Images)
		// We allow various mime types that map to jpg/png
		$allowed_mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
		);
		
		$file_name = isset( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '';
		$file_tmp  = isset( $file['tmp_name'] ) ? $file['tmp_name'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		
		// Check if tmp_name exists and is readable
		if ( empty( $file_tmp ) || ! is_readable( $file_tmp ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'File upload error. Please try again.', 'sudan-online-payments-for-woocommerce' ) ) );
		}
		
		$file_info = wp_check_filetype_and_ext( $file_tmp, $file_name, $allowed_mimes );
		
		// Debugging note: Sometimes client sends 'image/jpg', WP checks magic bytes. 
		// If verification fails, $file_info['type'] is false.
		
		if ( ! $file_info['ext'] || ! $file_info['type'] || ! in_array( $file_info['type'], $allowed_mimes, true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid file type. Only JPG and PNG images are allowed.', 'sudan-online-payments-for-woocommerce' ) ) );
		}

		// 3. Handle Upload using standard WP function
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}
		
		$attachment_id = media_handle_upload( 'file', 0 ); // 0 = not attached to a post yet

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
		}

		// Return success with attachment ID and thumbnail URL
		$image_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
		
		if ( ! $image_url ) {
			// Fallback to full image URL if thumbnail doesn't exist
			$image_url = wp_get_attachment_url( $attachment_id );
		}
		
		wp_send_json_success( array(
			'attachment_id' => $attachment_id,
			'image_url'     => $image_url,
		) );
	}

	/**
	 * Process the payment
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Verify nonce
		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
			wc_add_notice( esc_html__( 'Security check failed. Please try again.', 'sudan-online-payments-for-woocommerce' ), 'error' );
			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}

		// Save Receipt ID
		if ( ! empty( $_POST['sudan_online_payments_receipt_id'] ) ) {
			$attachment_id = absint( $_POST['sudan_online_payments_receipt_id'] );
			if ( $attachment_id ) {
				$order->update_meta_data( '_sudan_online_payments_receipt_id', $attachment_id );
				$order->save();
			}
		}

		// Mark as on-hold (we are waiting for the payment)
		$order->update_status( 'on-hold', esc_html__( 'Awaiting Sudan Online Payments confirmation.', 'sudan-online-payments-for-woocommerce' ) );

		// Reduce stock levels
		wc_reduce_stock_levels( $order_id );

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( $this->instructions ) );
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'sudan_online_payments' === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( $this->instructions ) );
		}
	}
}

/**
 * Handle File Upload logic separately to keep class clean or hook it here
 */
add_action( 'woocommerce_checkout_process', 'sudan_online_payments_validate_checkout' );

function sudan_online_payments_validate_checkout() {
	// Verify nonce
	if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
		return;
	}

    // Only validate if Sudan Online Payments is selected
    if ( isset( $_POST['payment_method'] ) && sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) === 'sudan_online_payments' ) {
        // Now checking the Hidden Input, not the file itself directly (since AJAX handled it)
        if ( empty( $_POST['sudan_online_payments_receipt_id'] ) ) {
            wc_add_notice( esc_html__( 'Please upload your payment receipt before placing the order.', 'sudan-online-payments-for-woocommerce' ), 'error' );
        }
    }
}


// Display Receipt in Admin Order
add_action( 'woocommerce_admin_order_data_after_billing_address', 'sudan_online_payments_display_admin_receipt', 10, 1 );

function sudan_online_payments_display_admin_receipt( $order ) {
    $receipt_id = $order->get_meta( '_sudan_online_payments_receipt_id' );
    if ( $receipt_id ) {
        $url = wp_get_attachment_url( $receipt_id );
        echo '<div class="sudan-online-payments-admin-receipt" style="margin-top:20px; padding:10px; background:#f9f9f9; border:1px solid #e5e5e5; border-radius:4px;">';
        echo '<h3>' . esc_html__( 'Payment Receipt', 'sudan-online-payments-for-woocommerce' ) . '</h3>';
        if ( $url ) {
            echo '<a href="' . esc_url( $url ) . '" target="_blank">';
            echo wp_get_attachment_image( $receipt_id, 'medium', false, array( 'style' => 'max-width:100%; height:auto; display:block; border:1px solid #ccc;' ) );
            echo '</a>';
            echo '<p style="margin-top:5px;"><a href="' . esc_url( $url ) . '" target="_blank" class="button button-small">' . esc_html__( 'View Full Height', 'sudan-online-payments-for-woocommerce' ) . '</a></p>';
        }
        echo '</div>';
    }
}

// Display Receipt in Customer Order View (My Account & Thank You Page)
add_action( 'woocommerce_order_details_after_order_table', 'sudan_online_payments_display_customer_receipt', 10, 1 );

// Display Receipt in Email (Invoice/Order Details)
add_action( 'woocommerce_email_after_order_table', 'sudan_online_payments_display_customer_receipt_email', 10, 4 );

function sudan_online_payments_display_customer_receipt( $order ) {
    sudan_online_payments_render_receipt_html($order);
}

function sudan_online_payments_display_customer_receipt_email( $order, $sent_to_admin, $plain_text, $email ) {
    sudan_online_payments_render_receipt_html($order);
}

function sudan_online_payments_render_receipt_html( $order ) {
    $receipt_id = $order->get_meta( '_sudan_online_payments_receipt_id' );
    if ( $receipt_id ) {
        $url = wp_get_attachment_url( $receipt_id );
        echo '<section class="sudan-online-payments-receipt-section" style="margin-top: 30px; margin-bottom: 30px;">';
        echo '<h2 class="woocommerce-column__title">' . esc_html__( 'Payment Receipt', 'sudan-online-payments-for-woocommerce' ) . '</h2>';
        echo '<div class="sudan-online-payments-receipt-box" style="padding: 15px; background: #fafafa; border: 1px solid #eee; border-radius: 5px; display:inline-block;">';
        
        if ( $url ) {
            echo '<a href="' . esc_url( $url ) . '" target="_blank" title="' . esc_attr__( 'Click to view full size', 'sudan-online-payments-for-woocommerce' ) . '">';
            echo wp_get_attachment_image( $receipt_id, 'medium', false, array( 'style' => 'max-width:100%; height:auto; display:block; border-radius:4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' ) );
            echo '</a>';
            echo '<div style="margin-top:10px; font-size: 0.9em; color:#666;">' . esc_html__( 'Click image to view full size', 'sudan-online-payments-for-woocommerce' ) . '</div>';
        }
        
        echo '</div>';
        echo '</section>';
    }
}
