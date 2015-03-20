<?php
/*
  Plugin Name: Gerador de Etiquetas WooCommerce
  Plugin URI: http://www.fernandoacosta.net
  Description: Um plugin simples para impressão de etiquetas do WooCommerce para envio por Correios. 
  Inspirado em elodigital
  Version: 0.2
  Author: Fernando Acosta
  Author URI: http://fernandoacosta.net
	License: GPL v3

	Plugin Simples.
*/

	function bulk_admin_etiqueta_footer() {
		global $post_type;

		if ( 'shop_order' == $post_type ) {
			?>
			<script type="text/javascript">
			jQuery(function() {
				jQuery('<option>').val('gerar_etiqueta').text('<?php _e( 'Gerar etiquetas', 'woocommerce' )?>').appendTo("select[name='action']");
				jQuery('<option>').val('gerar_etiqueta').text('<?php _e( 'Gerar etiquetas', 'woocommerce' )?>').appendTo("select[name='action2']");
			});
			</script>
			<?php
		}
	}

	/**
	 * Process the new bulk actions for changing order status
	 */
	function bulk_action_etiqueta() {

		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action = $wp_list_table->current_action();

		print_r($action);

		// Bail out if this is not a status-changing action
		if ( strpos( $action, 'gerar_' ) === false ) {
			return;
		}

		$new_status    = substr( $action, 5 ); // get the status name from action
		$report_action = 'gerada' . $new_status;

		$changed = 0;

		$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );

		$sendback = add_query_arg( array( 'post_type' => 'shop_order', $report_action => true, 'changed' => $changed, 'ids' => join( ',', $post_ids ) ), '' );
		wp_redirect( $sendback ); // esse é o padrão

		exit();
	}



function bulk_action_etiqueta_notices() {
		global $post_type, $pagenow;

		// Bail out if not on shop order list page
		if ( 'edit.php' !== $pagenow || 'shop_order' !== $post_type ) {
			return;
		}
			if ( isset( $_REQUEST[ 'gerada_etiqueta' ] ) ) {

				$number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
				$message = 'Etiquetas geradas em uma nova aba';
				echo '<div class="updated"><p>' . $message . '</p></div>';
			}
	}


function get_etiquetas_pdf(){

/**
 *
 * WooCommerce
 *
 * Biblioteca para PDF
 *
 */
require_once("dompdf/dompdf_config.inc.php");

$html .= '<!DOCTYPE html>';
$html .= ' <html>';
$html .= ' <head>';
$html .= ' 	<title>Etiquetas Correios</title>';
$html .= ' <style type="text/css">
*{font-size:15px;}
ul{list-style:none;padding:0;margin:0;}
div.one{width:374px;position:absolute;}
div.left{top:0;left:0;}
div.right{top:0;left:381px;}
div.one div{padding:13px 18px;line-height:19px;}
</style>';
$html .= ' </head>';
$html .= ' <body>';
$html .= ' <page>';

$arrei = $_GET['ids'];
$arrei = explode(",", $arrei);

$i=0; $a=0;
foreach ($arrei as $key => $value) {

	$pedido = $value;
	$order = wc_get_order( $value );

	//altura
	$height = 150;
	$top = ($height + 5) * $a;

	//esquerda//direita
	if($i%2){ $alinha = "right"; $a++; }else{ $alinha = "left";  }

	$nome 			= get_post_meta($pedido, '_billing_first_name', TRUE);
	$sobrenome 		= get_post_meta($pedido, '_billing_last_name', TRUE);
	$endereco 		= get_post_meta($pedido, '_billing_address_1', TRUE);
	$endereco2 		= get_post_meta($pedido, '_billing_address_2', TRUE);
	$cidade 		= get_post_meta($pedido, '_billing_city', TRUE);
	$uf 			= get_post_meta($pedido, '_billing_state', TRUE);
	$cep 			= get_post_meta($pedido, '_billing_postcode', TRUE);

$rates = $order->get_shipping_methods();
foreach ( $rates as $key => $rate ) {
        $tipoEnvio = $rate['method_id'];
            break;
}

$html .= '<div class="one ';
$html .= $alinha;
$html .= '" style="top:';
$html .= $top;
$html .= ';height:';
$html .= $height;
$html .= 'px;"><div>';
$html .= '#000';
$html .= $pedido;
$html .= ' - <b>';

if ( $tipoEnvio == 'free_shipping' ) {
	$html .= 'Carta Registrada';
} else {
	$html .= $tipoEnvio;
}
$html .= '</b><br /><b>';
$html .= $nome ." ". $sobrenome;
$html .= '</b><br />';
$html .= $endereco;
$html .= ' - ';


if ( is_plugin_active( 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php' ) ) {
	$numero 			= get_post_meta($pedido, '_billing_number', TRUE);
	$bairro 			= get_post_meta($pedido, '_billing_neighborhood', TRUE);
  	$html .= $numero;
  	$html .= '<br/>';
  	$html .= $bairro;
  	$html .= ' - ';
}


$html .= $endereco2;
$html .= '<br />';
$html .= $cidade;
$html .= ' - ';
$html .= $uf;
$html .= '<br /><b>';
$html .= 'CEP: ';
$html .= $cep;
$html .= '</b></div></div>';

if($i == 13){
	$html .= '</page><page>';
	$a=0;
}

$i++;
	
}


 
$html .= ' </body>';
$html .= '</html>';

$dompdf = new DOMPDF();
$dompdf->load_html($html);
$dompdf->render();
$dompdf->stream("etiqueta.pdf", array('Attachment'=>0));

	exit;
}


function custom_admin_etiqueta_js() {

	if ( $_GET['gerada_etiqueta'] == "1" ) {
    echo '<script type="text/javascript" language="Javascript">window.open("'. get_admin_url() .'admin-ajax.php/?action=get_etiquetas_pdf&ids='.$_GET['ids'].'")</script>';
	}
}


add_action('wp_ajax_get_etiquetas_pdf', 'get_etiquetas_pdf');
add_action('wp_ajax_nopriv_get_etiquetas_pdf', 'get_etiquetas_pdf');
add_action( 'admin_footer', 'bulk_admin_etiqueta_footer', 1000 );
add_action( 'load-edit.php', 'bulk_action_etiqueta' );
add_action( 'admin_notices', 'bulk_action_etiqueta_notices' );
add_action('admin_head', 'custom_admin_etiqueta_js');
