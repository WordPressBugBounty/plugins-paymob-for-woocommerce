=== Paymob for WooCommerce ===
Contributors: nspaymob
Tags: paymob, payment, gateway, woocommerce
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.0
WC requires at least: 4.0
WC tested up to: 9.3
Stable tag: 2.0.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Service link: https://paymob.com

Paymob Payment for WooCommerce.

== Description ==
= Why should you choose Paymob Checkout? =
Paymob Checkout is a secure, pre-built payment solution and the easiest way to integrate payments into your Woo store.

We offer quick onboarding, seamless integration, and access to 20+ global and local payment methods, including Apple Pay and Google Pay. Enjoy quick payment options, superior customer service, and fast settlements.

== Key features ==

= Highest payment success rates =
We deliver industry-leading payment success rates, ensuring smoother transactions every time.
= 3DS OTP authentication =
Our 3DS one-time password (OTP) is embedded directly into the checkout, eliminating the need for redirection during authentication.
= Super-secure payments =
PCI-compliant, supporting 3D Secure transactions to protect cardholder data.
= Mobile-optimized design =
Built with a mobile-first approach.
= Fully customizable checkout =
Personalize the checkout experience by adding logos, business details (address & phone number), customising the background, and much more.
= Multiple Currency Support =
Accept payments in various currencies.
= Card Tokenization =
Securely store card details for easy and safe future transactions.
= Retry payment feature =
If the initial transaction fails, users can attempt payment up to three times, increasing their chances of success.
= Settlements =
We offer settlements within one business day (T+1) for most payment methods and instant settlements in select countries. For BNPL and more details on settlements and refunds, please see the FAQ section below.

== Insights at a glance: dashboards for data-driven decisions ==
* View transaction details and analytics. 
* Initiate refunds directly from the dashboard. 
* Check settlement summaries and available balance. 
* Download reports on payments, settlements, and refunds. 
* Control checkout customization. 
* Manage your API Keys && Payment Methods.

== Frequently Asked Questions ==
=Which payment methods are available through Paymob?=
* Egypt: Debit/credit cards, wallets, bank instalments, Kiosk, Instapay (launching soon) and various BNPL providers such as ValU, Souhoola, Halan, Premium6, SYMPL, Aman, Forsa, MidTakseet.
* United Arab Emirates (UAE): Debit/credit cards, Apple Pay, Google Pay, and BNPL services such as Tabby and Tamara.
* Oman: Debit/credit cards and Oman Net.
* Saudi Arabia (KSA): Debit/credit cards, Apple Pay, Google Pay, STC Pay and BNPL services such as Tabby and Tamara.
* Pakistan: Debit/credit cards, EasyPaisa, and Jazz Cash.

=What is the settlement cycle for primary payment methods such as cards, Apple Pay, and Google Pay?=
The settlement cycle for primary payment methods such as cards, Apple Pay, and Google Pay is trade date plus one day (T+1). This may vary for BNPL payment methods.

=How can merchants receive their funds instantly?=
Merchants can opt for instant settlement to receive funds immediately.
 
=How can merchants process refunds for their customers?=
Refunds can be initiated through API or the merchant dashboard.

=Does Paymob offer BNPL?=
 We offer payment flexibility through our partners in select regions.

== Installation ==
= Paymob Checkout for WooCommerce = 
Paymob is a leading payment service provider in Egypt, United Arab Emirates (UAE), Oman, Saudi Arabia (KSA), and Pakistan. Since its launch, Paymob has empowered enterprises and SMEs to accept online and in-store payments, revolutionizing payment infrastructure across the MENA-P region. 

= Sign up for a Paymob account =
1. [Click here](https://onboarding.paymob.com/?partner=woocommerce) to register, upload your business documents and select the payment methods you wish to integrate with .Process has few steps, and you can always resume from where you left .
2. After you complete all the steps , Document verification will take up to 3 days and then you can start accepting payments. If there are any issues with documentation, our sales representative will reach out to assist you.
3. After 3 days, please [click here](https://onboarding.paymob.com/?partner=woocommerce) to check if your documents have been approved. If you encounter any issues, please email us at support@paymob.com for assistance.
5. Once verification is complete, please refer to the “Main Configuration”, starting from Step 2 to configure the plugin.

= Install the Paymob extension =
1. In your WordPress Admin Dashboard, go to Plugins > Installed Plugins. 
2. If "Paymob for WooCommerce" is not listed, follow these steps to install the plugin

= Steps to Install the Paymob Plugin =
1. Navigate to Plugins > Add New. 
2. Click on Add New Plugin. 
3. Search for "Paymob for WooCommerce." 
4. Click on Install next to the plugin. 
5. Once installed, click Activate. 
6. After activation, the plugin will appear as "Paymob for WooCommerce." Click on Paymob Settings. 

= Main Configuration = 
1. If you are already registered with Paymob and onboarded, skip Steps 1. 
2. Enter your API Key, Secret Key, and Public Key (refer to instructions in Step 2). 
3. Step 3 is mandatory (Follow the provided instructions in Step 3). 
4. Steps 4 and 5 are optional. 
5. Click Save Changes to be redirected to the Payment Integrations Page. 

=  Payment Method Integration Settings =
* If you have entered Live Keys on the main configuration page, all live payment method integrations will be displayed on this page.
* All test payment methods will be displayed if you’ve entered Test Keys. By default, payment methods appear as a list on the store's checkout page. Users can then select any payment method and complete their payment via Paymob Checkout.  
* An option labeled Pay with Paymob is disabled by default. If enabled and selected by the user, they will be redirected to Paymob Checkout, where they can choose from all available payment methods.
* You can configure your store’s WooCommerce checkout to list payment methods individually, use the Paymob Main App, or display both options.  
* All payment methods are enabled by default when the Paymob Main App is disabled.
* You can reorder payment methods by dragging them across, editing their titles and descriptions, and enabling/disabling them. However, it is recommended that you do not edit payment method logos.  Payment methods will appear in the same order in the WooCommerce checkout as listed here.  

= Final Step - Enabling Paymob  =
1. Go to WooCommerce > Settings > Payments.
2. Enable Paymob. 

== Screenshots ==
1. main configuration.png
2. paymob keys.png
3. payment integrations.png
4. edit payment integration.png
5. store checkout.png
6. paymob checkout.png
7. saved cards.png

== Changelog ==
2024-10-31 - version 2.0.2
Add Paymob WooCommerce onboarding page
Fix handling exception.

See [changelog.txt](http://plugins.svn.wordpress.org/paymob-for-woocommerce/trunk/changelog.txt) for older logs.

