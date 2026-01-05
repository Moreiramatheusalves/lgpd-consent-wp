<?php
if (!defined('ABSPATH')) exit;

/** @var string $text */
/** @var string $class */
$cls = trim('brlgpd-manage-btn ' . $class);
?>
<a href="#" class="<?php echo esc_attr($cls); ?>" data-brlgpd-open="1">
  <?php echo esc_html($text); ?>
</a>