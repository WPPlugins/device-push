<?php
header("Content-Type: application/json");
require_once("../../../../wp-load.php");
$fcm = get_option( 'devicepush_fcm' );
$app = get_option( 'devicepush_app_name' );
if ($fcm != FALSE && $app != FALSE) {
?>
{
  "name": "<?php echo $app; ?>",
  "gcm_sender_id": "<?php echo $fcm; ?>"
}
<?php
}
?>