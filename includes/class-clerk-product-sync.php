<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Clerk_Product_Sync {
	/** @var Clerk_Api */
	protected $api;
    protected $logger;

	public function __construct() {
		$this->includes();
		$this->initHooks();
        $this->logger = new ClerkLogger();
		$this->api = new Clerk_Api();
        $this->helper = new Clerk_Helpers();
	}

	private function includes() {
		require_once( __DIR__ . '/class-clerk-api.php' );
		require_once( __DIR__ . '/class-clerk-logger.php' );
        require_once( __DIR__ . '/class-clerk-helpers.php');
	}

	private function initHooks() {
		add_action( 'save_post_product', [ $this, 'save_product' ], 10, 2 );
		add_action( 'before_delete_post', [ $this, 'remove_product' ] );
	}

	public function save_product( $post_id, $post ) {
        $options = get_option('clerk_options');

        try {
            /** Disabled for troubleshooting 
            *if (!in_array('realtime_updates', $options)) {
            *    return;
            *}
            */
            if (!$post) {
                return;
            }

            if (!$product = wc_get_product($post)) {
                return;
            }

            if (clerk_check_version()) {
                if ($product->get_status() === 'publish') {
                    //Send product to Clerk
                    $this->add_product($product);
                } elseif (!$product->get_status() === 'draft') {
                    //Remove product
                    $this->remove_product($product->get_id());
                }
            } else {
                //Fix for WooCommerce 2.6
                if ($product->post->status === 'publish') {
                    //Send product to Clerk
                    $this->add_product($product);
                } elseif (!$product->post->status === 'draft') {
                    //Remove product
                    $this->remove_product($product->get_id());
                }
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR save_product', ['error' => $e->getMessage()]);

        }

	}

	/**
	 * Remove product from Clerk
	 *
	 * @param $post_id
	 */
	public function remove_product( $post_id ) {

        try {
            $options = get_option('clerk_options');
            if (!$options['realtime_updates'] == 1) {
                return;
            }
            //Remove product from Clerk
            $this->api->removeProduct($post_id);

        } catch (Exception $e) {

            $this->logger->error('ERROR remove_product', ['error' => $e->getMessage()]);

        }
	}

	/**
	 * Add product in Clerk
	 *
	 * @param WC_Product $product
	 */
	private function add_product( WC_Product $product ) {

        try {
            $options = get_option('clerk_options');
            if (!$options['realtime_updates'] == 1) {
                return;
            }
            
            $params = clerk_get_product_data($product);
            $this->api->addProduct($params);

        } catch (Exception $e) {

            $this->logger->error('ERROR add_product', ['error' => $e->getMessage()]);

        }

	}
}

new Clerk_Product_Sync();