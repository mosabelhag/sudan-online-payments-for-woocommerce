=== Sudan Online Payments for WooCommerce ===
Contributors: mosabelhag
Donate link: https://mousab.com/support-me/
Tags: woocommerce, sudan, payment gateway, bank transfer, receipt upload
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept Sudanese bank transfers (Bankak, O-Cash, etc.) and validate uploaded payment receipts manually.

== Description ==

🚩 **Important Notice:** This is an **unofficial, independently developed** plugin. It is not affiliated with, endorsed by, or connected to any specific Sudanese bank, payment service (like Bankak or O-Cash), or financial company.

**Sudan Online Payments for WooCommerce** is an **unofficial** gateway that facilitates **manual** bank transfers for the Sudanese market.

It allows store owners to display their local bank or mobile money details (e.g., Bankak, O-Cash) at checkout. Customers complete their order by uploading a screenshot of their payment receipt for validation.

**Important:** This plugin **does not process payments automatically**. It streamlines the manual transfer process, but all transactions require merchant verification of the uploaded receipt.

**Key Features:**

*   **Support for Popular Sudanese Services**: Pre-configured options for major banks and mobile money services:
    *   Bankak (Bank of Khartoum)
    *   O-Cash
    *   Fawry (Faisal Islamic Bank)
    *   SyberPay
    *   MyCashi
    *   Bravo
    *   Custom Bank/Service
*   **Integrated Receipt Upload**:
    *   **AJAX-powered** upload without page reload.
    *   **Client-side Validation**: Accepts common image formats (JPG, PNG) with configurable size limits.
    *   **Upload Preview**: Customers see a thumbnail of their uploaded receipt.
*   **User-Friendly Checkout Interface**: Modern grid layout for payment options with a "Copy Account Number" button for convenience.
*   **Complete Admin Management**: Dynamically add, edit, or remove account details from the WooCommerce settings. View uploaded receipts directly in the order details.
*   **Localization Ready**: Includes translation files and is fully translated into **Arabic** and English.

== Installation ==

1.  Upload the `sudan-online-payments-for-woocommerce` folder to your `/wp-content/plugins/` directory, or install the plugin directly via the WordPress admin panel.
2.  Activate the plugin through the **'Plugins'** menu in WordPress.
3.  Navigate to **WooCommerce > Settings > Payments**.
4.  Find **"Sudan Online Payments"** in the list and click **"Enable"**.
5.  Click the **"Manage"** button to configure your bank account details and settings.

== Frequently Asked Questions ==

= Is this plugin officially made by Bankak, O-Cash, or any bank? =
No. This is an **unofficial, independently developed** plugin created by a freelance developer to serve the Sudanese e-commerce community. It supports interoperability with these services but is not a product of those companies.

= Does the plugin automate the payment confirmation? =
No. This gateway facilitates a **manual bank transfer process**. The customer makes the transfer via their banking app, uploads proof, and the store admin must manually verify the receipt and update the order status (e.g., to "Processing" or "Completed").

= Is it compatible with the WooCommerce Blocks (the new checkout)? =
The plugin is primarily built and tested for the classic WooCommerce checkout shortcode (`[woocommerce_checkout]`). For full functionality, including the receipt upload field, using the classic checkout is recommended.

= Can I add a bank or service that is not in the list? =
Yes. The settings include a **"Custom Bank/Service"** option where you can specify any other transfer method.

== Screenshots ==

1. WooCommerce Settings - The configuration tab for adding and managing bank accounts.
2. Checkout Page - Displaying bank account cards and the receipt upload field.
3. Order Details - Viewing the uploaded customer receipt in the admin backend.

== Changelog ==

= 1.0.0 =
*   Initial public release.
*   Complete rename to "Sudan Online Payments for WooCommerce" to clarify unofficial, independent status.