<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Clerk_Helpers
{
    /** @var Clerk_Api */
	protected $api;
    protected $logger;

	public function __construct() {
		$this->includes();
        $this->logger = new ClerkLogger();
		$this->api = new Clerk_Api();
	}

	private function includes() {
		require_once( __DIR__ . '/class-clerk-api.php' );
		require_once( __DIR__ . '/class-clerk-logger.php' );
	}

    public function clerk_get_product_data($product){
        //Check include out of stock products
        if (!isset($options['outofstock_products'])) {

            if (!$product->is_in_stock()) {

                return false;

            }

        }

        /** @var WC_Product $product */
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');

        $on_sale = $product->is_on_sale();

        if ($product->is_type('variable')) {
            /**
             * Variable product sync fields
             * Will sync the lowest price, and set the sale flag if that variant is on sale.
             */

            $variation = $product->get_available_variations();
            $stock_quantity = 0;
            $displayPrice = array();
            $regularPrice = array();
            foreach ($variation as $v) {
                $vId = $v['variation_id'];
                $displayPrice[$vId] = $v['display_price'];
                $regularPrice[$vId] = $v['display_regular_price'];
                $variation_obj = new WC_Product_variation($v['variation_id']);
                $stock_quantity += $variation_obj->get_stock_quantity();
            }

            if (empty($displayPrice)) {

                return false;

            }

            $lowestDisplayPrice = array_keys($displayPrice, min($displayPrice)); // Find the corresponding product ID
            $price = $displayPrice[$lowestDisplayPrice[0]]; // Get the lowest price
            $list_price = $regularPrice[$lowestDisplayPrice[0]]; // Get the corresponding list price (regular price)

            if ($price === $list_price) $on_sale = false; // Remove the sale flag if the cheapest variant is not on sale
        } else {
            /**
             * Default single product sync fields
             */
            $price = $product->get_price();
            $list_price = $product->get_regular_price();
        }

        if ($product->managing_stock() && !isset($options['outofstock_products']) && $product->get_stock_quantity() === 0) {

            if (isset($stock_quantity) && $stock_quantity === 0) {

                return false;

            }elseif(!isset($stock_quantity)) {

                return false;

            }elseif(!$product->is_in_stock()) {

                return false;

            }
        } elseif (! $product->managing_stock() && ! $product->is_in_stock() && !isset($options['outofstock_products'])) {

            return false;

        }

        $productArray = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'description' => get_post_field('post_content', $product->get_id()),
            'excerpt' => get_the_excerpt( $product->get_id() ),
            'price' => (float)$price,
            'list_price' => (float)$list_price,
            'image' => wp_get_attachment_image_src($product->get_image_id(),'medium')[0],
            'url' => $product->get_permalink(),
            'categories' => wp_list_pluck($categories, 'term_id'),
            'sku' => $product->get_sku(),
            'on_sale' => $on_sale,
            'type' => $product->get_type(),
            'created_at' => strtotime($product->get_date_created())
        ];

        $productArray['all_images'] = [];

        foreach (get_intermediate_image_sizes() as $key => $image_size) {

            if (!in_array(wp_get_attachment_image_src($product->get_image_id(),$image_size)[0], $productArray['all_images'])) {

                array_push($productArray['all_images'] , wp_get_attachment_image_src($product->get_image_id(), $image_size)[0]);

            }

        }

        if (!empty($product->get_stock_quantity())) {

            $productArray['stock'] = $product->get_stock_quantity();

        }elseif (isset($stock_quantity)) {

            $productArray['stock'] = $stock_quantity;

        }

        //Append additional fields
        foreach ($this->getAdditionalFields() as $field) {

            if ($field == '') {

                return false;

            }

            if ($product->get_attribute($field)) {

                $productArray[$this->clerk_friendly_attributes($field)] = explode(', ',$product->get_attribute($field));

            }elseif (get_post_meta( $product->get_id(), $field, true )) {

                $productArray[$this->clerk_friendly_attributes($field)] = get_post_meta( $product->get_id(), $field, true );

            }elseif (wp_get_post_terms( $product->get_id(), strtolower($field), array('fields'=> 'names'))) {

                $attrubutefield = wp_get_post_terms( $product->get_id(), strtolower($field), array('fields'=> 'names'));

                if (!array_key_exists('errors', $attrubutefield )) {

                    $productArray[strtolower($this->clerk_friendly_attributes($field))] = $attrubutefield;

                }

            }

        }

        $productArray = apply_filters('clerk_product_array', $productArray, $product);

        return $productArray;

    }

    function clerk_friendly_attributes($attribute) {
        $attribute = strtolower($attribute);
        $attribute=str_replace('æ','ae',$attribute);
        $attribute=str_replace('ø','oe',$attribute);
        $attribute=str_replace('å','aa',$attribute);
        return urlencode($attribute);
    }

    private function getAdditionalFields()
    {

        try {

            $options = get_option('clerk_options');

            $additional_fields = $options['additional_fields'];

            $fields = explode(',', $additional_fields);

            foreach ($fields as $key => $field) {

                $fields[$key] = str_replace(' ','', $field);

            }

            if (!is_array($fields)) {
                return array();
            }

            return $fields;

        } catch (Exception $e) {

            $this->logger->error('ERROR getAdditionalFields', ['error' => $e->getMessage()]);

        }

    }
}
new Clerk_Helpers();