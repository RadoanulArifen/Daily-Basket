# Foreign keys and their indexes

Every foreign key in `database/schema.sql` (and in the migrations) has an index that starts with
the foreign-key column, so joins and `ON DELETE` checks never scan a table. Generated from the
schema on MariaDB 10.11; `tests/Feature/...` and `php artisan migrate` produce the same set.

| Table | Column | References | Index |
|---|---|---|---|
| admin_views | admin_id | admins | `admin_views_admin_id_index` |
| audit_logs | admin_id | admins | `audit_logs_admin_id_index` |
| banners | image_id | media | `banners_image_id_index` |
| carts | user_id | users | `carts_user_id_unique` |
| cart_items | cart_id | carts | `cart_items_cart_id_product_variant_id_options_hash_unique` |
| cart_items | product_variant_id | product_variants | `cart_items_product_variant_id_index` |
| categories | image_id | media | `categories_image_id_index` |
| categories | parent_id | categories | `categories_parent_id_sort_order_index` |
| category_product | category_id | categories | `PRIMARY` |
| category_product | product_id | products | `category_product_product_id_index` |
| code_versions | admin_id | admins | `code_versions_admin_id_index` |
| customer_addresses | user_id | users | `customer_addresses_user_id_index,customer_addresses_user_id_is_default_index` |
| custom_order_requests | product_id | products | `custom_order_requests_product_id_index` |
| custom_order_requests | user_id | users | `custom_order_requests_user_id_index` |
| discount_usages | discount_id | discounts | `discount_usages_discount_id_email_index` |
| discount_usages | order_id | orders | `discount_usages_order_id_index` |
| fulfillments | admin_id | admins | `fulfillments_admin_id_index` |
| fulfillments | order_id | orders | `fulfillments_order_id_index` |
| fulfillment_items | fulfillment_id | fulfillments | `fulfillment_items_fulfillment_id_index` |
| fulfillment_items | order_item_id | order_items | `fulfillment_items_order_item_id_index` |
| inventory_movements | admin_id | admins | `inventory_movements_admin_id_index` |
| inventory_movements | product_variant_id | product_variants | `inventory_movements_product_variant_id_created_at_index` |
| media_product | media_id | media | `media_product_media_id_index` |
| media_product | product_id | products | `PRIMARY` |
| media_product_review | media_id | media | `media_product_review_media_id_index` |
| media_product_review | product_review_id | product_reviews | `PRIMARY` |
| menu_items | menu_id | menus | `menu_items_menu_id_parent_id_sort_order_index` |
| menu_items | parent_id | menu_items | `menu_items_parent_id_index` |
| orders | user_id | users | `orders_user_id_index,orders_user_id_status_index` |
| order_drafts | admin_id | admins | `order_drafts_admin_id_index` |
| order_drafts | customer_id | users | `order_drafts_customer_id_index` |
| order_events | admin_id | admins | `order_events_admin_id_index` |
| order_events | order_id | orders | `order_events_order_id_index` |
| order_items | order_id | orders | `order_items_order_id_index` |
| order_items | product_id | products | `order_items_product_id_index` |
| order_items | product_variant_id | product_variants | `order_items_product_variant_id_index` |
| payments | order_id | orders | `payments_order_id_index` |
| products | brand_id | brands | `products_brand_id_index` |
| product_options | product_id | products | `product_options_product_id_index` |
| product_option_values | product_option_id | product_options | `product_option_values_product_option_id_index` |
| product_reviews | product_id | products | `product_reviews_product_id_status_index,product_reviews_product_id_user_id_unique` |
| product_reviews | user_id | users | `product_reviews_user_id_index` |
| product_variants | media_id | media | `product_variants_media_id_index` |
| product_variants | product_id | products | `product_variants_product_id_position_index` |
| product_variant_option_value | product_option_value_id | product_option_values | `product_variant_option_value_product_option_value_id_index` |
| product_variant_option_value | product_variant_id | product_variants | `PRIMARY` |
| promotions | category_id | categories | `promotions_category_id_index` |
| refunds | admin_id | admins | `refunds_admin_id_index` |
| refunds | order_id | orders | `refunds_order_id_index` |
| refund_items | order_item_id | order_items | `refund_items_order_item_id_index` |
| refund_items | refund_id | refunds | `refund_items_refund_id_index` |
| return_items | order_item_id | order_items | `return_items_order_item_id_index` |
| return_items | return_request_id | return_requests | `return_items_return_request_id_index` |
| return_requests | order_id | orders | `return_requests_order_id_index` |
| stock_alerts | product_variant_id | product_variants | `stock_alerts_product_variant_id_email_unique` |
| stock_alerts | user_id | users | `stock_alerts_user_id_index` |
| wishlist_items | product_id | products | `wishlist_items_product_id_index` |
| wishlist_items | user_id | users | `wishlist_items_user_id_product_id_unique` |

58 foreign keys, 58 indexed, 0 missing.

## Search indexes

| Table | Column | Index | Used by |
|---|---|---|---|
| users | email | `users_email_unique` | customer search, sign-in |
| users | name | `users_name_index` | customer search |
| product_variants | sku | `product_variants_sku_index` | product and inventory search |
| sessions | user_id | `sessions_user_id_index` | signing a blocked customer out |

Product names are translated JSON; searching them uses the bound `Search::like()` on
`name->en` / `name->ar` (a full-text index is not portable across MySQL, MariaDB and SQLite).

## Columns asked about in reviews

Real table and column names in this schema (some reviews use generic names such as
`addresses` or `variant_id`):

| Asked about | Real column | Index |
|---|---|---|
| order_items.product_id | `order_items.product_id` | `order_items_product_id_index` |
| order_items.variant_id | `order_items.product_variant_id` | `order_items_product_variant_id_index` |
| product_reviews.product_id | `product_reviews.product_id` | `product_reviews_product_id_status_index` (and the unique `product_id, user_id`) |
| addresses.user_id | `customer_addresses.user_id` | `customer_addresses_user_id_index` |
