=== SPEI Mexico for WooCommerce ===
Contributors: dleon55
Tags: spei, clabe, mexico, bank transfer, payment gateway
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept SPEI bank transfers to your Mexican CLABE with no gateway fees: clear instructions, auto-cancel and tracking keys to verify in Banxico CEP.

== Description ==

WooCommerce ships with "Direct bank transfer", but it is designed around IBAN and SWIFT. In Mexico people pay by **SPEI to an 18-digit CLABE**. This plugin adds a payment method built for that flow.

**For your customers**

* Clear payment details on the order received page and in the order email: bank, beneficiary, CLABE, exact amount and payment concept.
* A "Copy" button next to each value, so customers paste it into their banking app without typos.
* Payment concept and numeric reference based on the order number, so you can match every deposit.
* An "I already paid" form where the customer enters the SPEI tracking key (clave de rastreo).

**For your store**

* Validates your CLABE check digit when you save it, so you never publish a mistyped account.
* Orders stay "On hold" and stock is reserved.
* Unpaid orders are cancelled automatically after the hours you choose (48 by default), releasing stock.
* If the customer already reported a payment, the order is not cancelled and you get an email.
* An order screen box with everything you need to verify the transfer in Banxico's CEP (Comprobante Electrónico de Pago).
* Compatible with the Cart and Checkout blocks and with High-Performance Order Storage (HPOS).
* No gateway account, no per-transaction fees, and no data sent to third parties.
* Includes Spanish (Mexico and Spain) translations.

The method is only offered at checkout when the store currency is Mexican pesos (MXN).

Built by [Lion Systems](https://www.lionsystems.com.mx/), Mexico.

== Installation ==

1. Install and activate the plugin.
2. Go to WooCommerce → Settings → Payments → SPEI transfer.
3. Enter bank, beneficiary and CLABE, and choose how many hours customers have to pay.
4. Enable the method and save.

== Frequently Asked Questions ==

= Does it confirm payments automatically? =

No. This version does not connect to your bank. The customer reports the tracking key and you verify the transfer in Banxico's CEP with one click from the order. Then you change the order to "Processing".

= What happens if a customer pays after the order was cancelled? =

The order stays cancelled and the stock is released. You can reactivate it by changing its status manually.

= Are there fees? =

No. Money goes straight to your account by SPEI. Only your bank's own fees apply, if any.

= Does it store customer banking data? =

Only the tracking key, the transfer date and the bank name the customer types. It never asks for account or card numbers.

= Does it use external services? =

No. The plugin only shows a link to Banxico's public CEP page (https://www.banxico.org.mx/cep/) in the order screen, which the store owner can open manually. No data is sent automatically.

== External services ==

This plugin does not connect to any external service by itself and sends no data to third parties.

It only displays a link to Banco de México's public CEP (Comprobante Electrónico de Pago) page, https://www.banxico.org.mx/cep/, in the order screen and in the payment-report email sent to the store. The store owner may open it manually to verify an SPEI transfer; whatever they type there is sent by their own browser to Banco de México (https://www.banxico.org.mx/), under that site's own terms and privacy notice.

== Screenshots ==

1. Checkout with the SPEI transfer method (store in Spanish).
2. Payment details with copy buttons on the order received page.
3. "I already paid" form where the customer enters the SPEI tracking key.
4. Order screen box with everything needed to verify the transfer in Banxico's CEP.
5. Settings with CLABE check-digit validation.

== Changelog ==

= 0.1.1 =
* Settings: short note about SPEI Mexico Pro (only when Pro is not installed).

= 0.1.0 =
* First release: SPEI payment method with validated CLABE, copyable instructions, automatic cancellation, tracking-key payment reports and Banxico CEP verification box.
