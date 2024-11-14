# Stripe to MailerLite Integration

**Version:** 1.0  
**Author:** Ryan T. M. Reiffenberger  
**Author URL:** [Falls Technology Group](https://www.fallstech.group)  
**Description:** Automatically subscribes Stripe checkout customers to specified MailerLite mailing lists.

## Overview

The Stripe to MailerLite Integration plugin allows you to automatically add customers who complete a Stripe checkout session to designated mailing lists in MailerLite. This can help streamline your email marketing efforts by ensuring that customers are automatically subscribed to relevant lists based on the products they purchase.

## Features

- Map specific Stripe products to MailerLite groups
- Automatically subscribes customers to appropriate MailerLite groups after checkout
- Easy configuration through the WordPress dashboard
- Securely authenticates Stripe webhook requests

## Requirements

- WordPress installation
- Stripe account with API keys and enabled webhooks
- MailerLite account with API key
- PHP 7.4 or higher

## Installation

1. Download the plugin or clone this repository into your WordPress plugins directory (`wp-content/plugins/`).
2. Activate the plugin through the WordPress dashboard under **Plugins** > **Installed Plugins**.
3. Configure your API keys and product mappings under **Settings** > **Stripe to MailerLite**.

## Setup

### API Configuration

1. Go to **Settings** > **Stripe to MailerLite** in your WordPress dashboard.
2. Enter your **Stripe Secret Key**, **Stripe Webhook Secret**, and **MailerLite API Key** in the provided fields.
3. Save changes.

### Webhook Configuration

1. In your Stripe account, go to **Developers** > **Webhooks** and create a new webhook.
2. Set the endpoint URL to your WordPress site, appending `/wp-json/smi/v1/webhook` (e.g., `https://yourwebsite.com/wp-json/smi/v1/webhook`).
3. Choose the `checkout.session.completed` event.
4. Copy the **Webhook Secret** provided by Stripe and enter it in the plugin settings.

### Product to Mailing List Mapping

1. In the plugin settings page, you’ll see a list of your Stripe products alongside available MailerLite groups.
2. Select the MailerLite group to associate with each Stripe product. When a customer purchases this product, they’ll be added to the corresponding MailerLite group.

## Usage

Once configured, the plugin will automatically subscribe customers to the appropriate MailerLite group(s) upon completing a Stripe checkout session. No further actions are required.

## Troubleshooting

- **Product List Empty:** Ensure your Stripe Secret Key is correct and has permissions to fetch products.
- **MailerLite Group List Empty:** Verify the MailerLite API Key and ensure it has permissions to access groups.
- **Webhook Not Triggering:** Confirm that the webhook endpoint is set correctly in Stripe and that the `checkout.session.completed` event is enabled.

## Development

### File Structure

- `stripe-mailerlite-integration.php`: Main plugin file
- `smi_add_settings_page()`: Registers the settings page in WordPress
- `smi_render_settings_page()`: Renders the settings page
- `smi_handle_stripe_webhook()`: Handles incoming Stripe webhooks
- `smi_add_to_mailerlite()`: Adds subscribers to MailerLite groups

### Code Overview

The plugin uses WordPress REST API routes and settings pages to handle configuration, webhook processing, and customer subscriptions.

## License

This plugin is open-source and released under the MIT License.

## Support

For assistance, please contact [Falls Technology Group](https://www.fallstech.group).
