<?php
/**
 * Front end display of shortcode
 * can be overridden in child themes / themes
 *
 * To customise create a folder in your theme directory called get-directions and a modified version of this file called shortcode_layout_1.php
 *
 *
 * @var mixed  $data     Custom data for the template.
 */
?>
<div id="<?php echo $data->id; ?>" style="width: <?php echo $data->args['width']; ?>; height: <?php echo $data->args['height']; ?>;"></div>
