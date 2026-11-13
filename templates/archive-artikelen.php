<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#wp--skip-link--target">Ga naar de inhoud</a>
<?php block_template_part('header'); ?>

<main id="wp--skip-link--target" class="wp-block-group is-layout-constrained">
    <h1 class="wp-block-post-title">Archief</h1>
    <div class="wp-block-post-content">
        <?php echo ihn_archief_html(); ?>
    </div>
</main>

<?php block_template_part('footer'); ?>
<?php wp_footer(); ?>
</body>
</html>
