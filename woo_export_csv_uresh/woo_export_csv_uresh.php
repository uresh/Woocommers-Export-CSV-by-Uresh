<?php 
/**
 * Plugin Name:       Woocommers Export CSV by Uresh
 * Description:       woocomerce order export to csv file.
 * Version:           1.0.0
 * Author:            Uresh Hansaka Samarawickrama
 * Author URI:        http://uresh.me
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

class Woo_Export_CSV 
{
    public function __construct() {
        add_action( 'manage_posts_extra_tablenav', array( $this, 'add_export_button' ), 20, 1 );
        add_action( 'admin_post_export_csv', array( $this, 'add_export_csv_handler' ) );
        add_action( 'admin_post_nopriv_export_csv', array( $this, 'add_export_csv_handler' ) );
    }

    /**
     * 
     * Add export button to the woocommerce order page
     * 
     * @since 1.0.0
     *
     * @param string $which  wordpress defualt variable
     * @return string Return HTML string of the button
     */

    public function add_export_button( $which ) {
        global $typenow;

        if ( 'shop_order' === $typenow && 'top' === $which ) {
            ?>
                <div class="alignleft actions custom">
                    <a href="<?php get_admin_url(null, 'admin-post.php?action=export_csv') ?>" class="button button-primary">
                        <?php 
                            echo __( 'Export CSV', 'woocommerce' ); 
                        ?>
                    </a>
                </div>
            <?php
        }
    }

     /**
     * 
     * Filter and query current page woocommerce orders and selected columns send to generate CSV file
     * 
     * @since 1.0.0
     *
     * @return string Return csv file
     */

    public function add_export_csv_handler() {
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

        $args = array(
            'type' => 'shop_order',
            'paged' => $paged,
        );

        $orders = wc_get_orders( $args );
        $format_data = $this->format_order_data($orders);
        $this->render_order_csv($format_data);
    }

    /**
     * 
     * Formatte woocommerce orders
     * 
     * @since 1.0.0
     *
     * @param string $orders  woocommerce orders
     * @return array $dataArray formatted woocommerce orders
     */

    public function format_order_data($orders) {
        $dataArray = array();
        foreach ($orders as $order) {
            array_push($dataArray, 
                array(
                    ( $order->get_id() ) ? $order->get_id() : "NA",
                    ( $order->get_date_created() ) ? date('Y-m-d g:i a', strtotime($order->get_date_created()))  : "NA",
                    ( $order->get_billing_first_name() ) ? $order->get_billing_first_name() . ( ($order->get_billing_last_name()) ? ' ' . $order->get_billing_last_name() : null ) : 'NA' ,
                    ( $order->get_status() ) ? ucfirst( $order->get_status() ) : "NA",
                    ( $order->get_total() ) ? ($order->get_currency() . ' ' . $order->get_total()) : "NA",
                )
            );
        }
        return $dataArray;
    }

    /**
     * 
     * Generate CSV file for formatted woocommerce orders
     * 
     * @since 1.0.0
     *
     * @param string $data  formatted woocommerce orders
     * @return string $output generated CSV file
     */

    public function render_order_csv( $data ) {
        
        $filename = 'orders-csv-' . date('Y-m-d-His');

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"" . $filename . ".csv\";" );
        header("Content-Transfer-Encoding: binary");

        $output = fopen('php://output', 'w');
       
        fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) )); //utf 8 issue fix this
        fputcsv( $output, array('Order Number', 'Order Placed Date', 'Name of Customer', 'Order Status', 'Order Total'));

        if ($output && $data) {        
            foreach ($data as $r) {
                fputcsv($output, array_values($r));
            }
            fclose( $output );
            die;
        }

        fclose( $output );
        return $output;
    }
}

// Create  Woo_Export_CSV instance
$WC_export_csv = new Woo_Export_CSV();