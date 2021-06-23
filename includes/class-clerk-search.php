<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Clerk_Search
{
    /**
     * Clerk_Search constructor.
     */
    protected $logger;

    public function __construct()
    {
        $this->initHooks();
        require_once(__DIR__ . '/class-clerk-logger.php');
        $this->logger = new ClerkLogger();
    }

    /**
     * Init hooks
     */
    private function initHooks()
    {
        add_filter('query_vars', [$this, 'add_search_vars']);
        add_shortcode('clerk-search', [$this, 'handle_shortcode']);
    }

    /**
     * Add query var for searchterm
     *
     * @param $vars
     *
     * @return array
     */
    public function add_search_vars($vars)
    {

        try {

            $vars[] = 'searchterm';

            return $vars;

        } catch (Exception $e) {

            $this->logger->error('ERROR add_search_vars', ['error' => $e->getMessage()]);

        }

    }

    /**
     * Output clerk-search shortcode
     *
     * @param $atts
     */
    public function handle_shortcode($atts)
    {

        $facets_attributes = '[';
        $facets_titles = '{';
        $Attributes = [];

        $options = get_option('clerk_options');

        if ($options['faceted_navigation_enabled'] !== null && $options['faceted_navigation_enabled']) {

            $_Attributes = json_decode($options['faceted_navigation']);
            $count = 0;

            foreach ($_Attributes as $key => $_Attribute) {

                if ($_Attribute->checked) {

                    array_push($Attributes, $_Attribute);

                }

            }

            $Sorted_Attributes = [];

            foreach ($Attributes as $key => $Sorted_Attribute) {

                $Sorted_Attributes[$Sorted_Attribute->position] = $Sorted_Attribute;

            }

            foreach ($Sorted_Attributes as $key => $Attribute) {

                $count++;

                if ($count == count($Attributes)) {

                    $facets_attributes .= '"' . $Attribute->attribute . '"';
                    $facets_titles .= '"' . $Attribute->attribute . '": "' . $Attribute->title . '"';

                } else {

                    $facets_attributes .= '"' . $Attribute->attribute . '", ';
                    $facets_titles .= '"' . $Attribute->attribute . '": "' . $Attribute->title . '",';

                }
            }

        }

        $facets_attributes .= ']\'';
        $facets_titles .= '}\'';

        try {

            $options = get_option('clerk_options');
            wp_enqueue_style('clerk_search_css', plugins_url('../assets/css/search.css', __FILE__));
            wp_enqueue_script('clerk_search_js', plugins_url('../assets/js/search.js', __FILE__), array('jquery'));
            ?>
            <span id="clerk-search"
                  class="clerk"
                  data-template="@<?php echo esc_attr(strtolower(str_replace(' ', '-', $options['search_template']))); ?>"
                  data-limit="40"
                  data-offset="0"
                  data-target="#clerk-search-results"
                  data-after-render="_clerk_after_load_event"
                  <?php
                  if (count($Attributes) > 0) {

                      echo 'data-facets-target="#clerk-search-filters"';
                      echo "data-facets-attributes='".$facets_attributes;
                      echo "data-facets-titles='".$facets_titles;

                  }

                  ?>
                  data-query="<?php echo esc_attr(get_query_var('searchterm')); ?>">
		    </span>
            <div id="clerk-show-facets">Filter tonen/verbergen</div>
            <?php
                if (count($Attributes) > 0): ?>
                    <div class="clerk-container">
                        <div id="clerk-facets-container">
                            <div class="clerk-facet-group">
                                <div class="clerk-facet-group-title">Sorteer op: </div>
                                <select ng-model="sort" class="clerk-result-sort ng-pristine ng-valid ng-touched">
                                    <option value="relevance">Meest verkocht</option>
                                    <option value="upage">Nieuw naar oud</option>
                                    <option value="downage">Oud naar nieuw</option>
                                    <option value="upname">Naam, A - Z</option>
                                    <option value="downname">Naam, Z - A</option>
                                    <option value="upprice">Prijs laag naar hoog</option>
                                    <option value="downprice">Prijs van hoog naar laag</option>
                                </select>
                            </div>
                            <div id="clerk-search-filters"></div>
                        </div>
                        <div id="clerk-search-container">
                            <ul style="width: 100%;" id="clerk-search-results"></ul>
                        </div>
                    </div>
                <?php endif; ?>

            <div id="clerk-search-no-results" style="display: none; margin-left: 3em;"><h2><?php echo $options['search_no_results_text'] ?></h2></div>

            <?php

                } catch (Exception $e) {

                $this->logger->error('ERROR handle_shortcode', ['error' => $e->getMessage()]);

        }

    }
}

new Clerk_Search();