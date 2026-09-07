=== Sales by State Report for EasyCart ===
Contributors: BusinessBloomer
Donate link: https://salesbystate.com/
Tags: sales-report, sales-by-state, easycart, analytics, ecommerce
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See a yearly breakdown of EasyCart sales by state / county / province for a given country, filterable by order status.

== Description ==

Sales by State Report for EasyCart adds a report showing net and gross sales grouped by state, county, or province, for a chosen year and a chosen set of order statuses.

It appears under **WP EasyCart → Sales by State**.

Use it when you need to know how much each state bought in a given year, counting only the order statuses that matter for sales tax or territory planning.

This plugin is an EasyCart extension. It requires [WP EasyCart](https://wordpress.org/plugins/wp-easycart/) to be installed and active. There are no settings screens to configure. After you activate the plugin, open the report and choose a country, year, and order statuses.

Documentation: [salesbystate.com](https://salesbystate.com/)

= How to use =

1. Install and activate WP EasyCart, then install and activate this plugin.
2. Go to **WP EasyCart → Sales by State**.
3. Choose a **country**, a **year**, and the **order statuses** that should count.
4. The table lists Net Sales and Gross Sales for every state in that country.

If the store already has orders, the plugin copies them into its report table in the background. A progress bar appears until that finishes. You can leave the page; the copy continues on its own.

= What the report shows =

* Net Sales and Gross Sales for every state in the selected country
* A summary of both figures across all states
* Sortable columns and paginated results
* States with no sales, shown as zero rather than hidden

= Filters =

* **Country** — United States, Canada, and the United Kingdom. Defaults to the store's default country.
* **Year** — a rolling list that starts ten years back and gains a year each January without dropping one. Defaults to the current year.
* **Order status** — a checkbox list of EasyCart order statuses. Defaults to approved statuses (shipped, confirmed, and similar).

= How the figures are calculated =

Gross Sales is the order grand total. Net Sales is the grand total minus tax (including VAT / GST / PST / HST) and shipping. Both use the values EasyCart stores on the order.

Refunds are not modelled as separate records. An order that has been fully refunded is controlled by the status filter. A partial refund is not deducted from its order's total.

= Performance =

Sales for a whole year are answered by one indexed query that returns one row per state. The response size does not grow with the number of orders.

= Data and privacy =

The plugin creates one custom database table holding, per order: the order ID, order status, creation and paid dates, billing and shipping country and state codes, currency, and the order, tax, shipping and net totals. It stores no names, addresses, email addresses or any other personal data.

Nothing is sent anywhere. The plugin makes no external HTTP requests, includes no third-party services, and collects no analytics or telemetry.

Deleting the plugin removes the table and its options.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/sales-by-state-report-for-easycart`, or install it through the Plugins screen.
2. Activate the plugin. WP EasyCart must already be installed and active.
3. Go to **WP EasyCart → Sales by State**.

On a store that already has orders, those orders are read into the report table once. This starts on its own when you open the report. If it has not finished, a progress bar shows how far along it is.

== Frequently Asked Questions ==

= The report shows zeros but I have orders. =

Your existing orders are still being read into the report table. Open the report and the progress bar will show how far along it is. It continues on its own; you can leave the page.

If only approved statuses are selected, tick any other statuses that should count.

= Which address does it group by? =

The shipping address. Digital / billing-only orders fall back to the billing address.

= Are refunds deducted? =

The status filter decides whether an order counts. Partial refunds are not deducted from the order's total.

= Which date does the year filter use? =

The date the order was placed. Approved orders also store that date as paid.

= Can I change the default order status? =

Yes, with the `sbsec_default_statuses` filter.

= Where can I get support? =

Use the [WordPress.org support forum](https://wordpress.org/support/plugin/sales-by-state-report-for-easycart/) for this plugin.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
