<?php

// ============================================
// ANTI-FLASH: thema instellen vóór render
// ============================================

add_action('wp_head', function () {
    ?>
    <script>
    (function () {
        var saved = localStorage.getItem('ihn-theme');
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        var theme = saved || (prefersDark ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
    })();
    </script>
    <?php
}, 1);


// ============================================
// SCRIPTS EN STIJLEN LADEN
// ============================================

function ihn_enqueue_assets() {
    wp_enqueue_style(
        'ihn-style',
        get_stylesheet_uri()
    );

    wp_enqueue_script(
        'ihn-theme-toggle',
        get_theme_file_uri('assets/js/theme-toggle.js'),
        [],
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'ihn_enqueue_assets');



// Samenvatting: behoudt returns, zonder HTML-tags
function ihn_samenvatting($post_id) {
    if (has_excerpt($post_id)) {
        $tekst = get_post_field('post_excerpt', $post_id);
    } else {
        $tekst = get_post_field('post_content', $post_id);
        $tekst = preg_replace('/<!--.*?-->/s', '', $tekst);
        $tekst = strip_shortcodes($tekst);
        $tekst = wp_strip_all_tags($tekst);
    }
    $tekst = trim(preg_replace("/\n{3,}/", "\n\n", $tekst));
    if (mb_strlen($tekst) > 280) {
        $tekst = mb_substr($tekst, 0, 280);
        $tekst = preg_replace('/\s+\S*$/u', '', $tekst) . '…';
    }
    return nl2br(esc_html($tekst));
}

function ihn_logo_svg() {
    $pad = get_theme_file_path('assets/images/logo-inline.svg.php');
    if (!file_exists($pad)) {
        return '';
    }
    ob_start();
    include $pad;
    return ob_get_clean();
}


// ============================================
// LEESTIJD INDICATIE (verhalen)
// ============================================

function ihn_leestijd_html($post_id) {
    $html = get_post_field('post_content', $post_id);

    // Verwijder pull quotes (block-markup)
    $html = preg_replace('/<!--\s*wp:pullquote.*?<!--\s*\/wp:pullquote\s*-->/s', '', $html);

    // Verwijder het vonnis-kader (block-markup met class ihn-vonnis-kader)
    $html = preg_replace('/<!--\s*wp:group\s*\{[^}]*ihn-vonnis-kader[^}]*\}\s*-->.*?<!--\s*\/wp:group\s*-->/s', '', $html);

    // Verwijder overige block-commentaren, shortcodes en HTML-tags
    $html = preg_replace('/<!--.*?-->/s', '', $html);
    $html = strip_shortcodes($html);
    $tekst = wp_strip_all_tags($html);

    $woorden = count(preg_split('/\s+/u', trim($tekst), -1, PREG_SPLIT_NO_EMPTY));

    // Gemiddeld 200 woorden per minuut, naar boven afgerond, minimaal 1
    $minuten = max(1, (int) ceil($woorden / 200));

    ob_start();
    ?>
    <div class="ihn-leestijd">
        <svg class="ihn-leestijd-icon" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        <span><?php echo $minuten; ?> <?php echo $minuten === 1 ? 'minuut' : 'minuten'; ?> meeleven</span>
    </div>
    <?php
    return ob_get_clean();
}

add_filter('the_content', function ($content) {
    if (is_singular('post') && in_the_loop() && is_main_query()
        && has_category('verhalen', get_the_ID())) {
        return ihn_leestijd_html(get_the_ID()) . $content;
    }
    return $content;
});

// Eén verhaal-kaart: samenvatting en verwijzing (geen titel, geen nummer)
function ihn_verhaal_kaart($post_id, $nummer, $klasse) {
    ob_start();
    ?>
    <div class="ihn-verhaal <?php echo esc_attr($klasse); ?>">
        <p class="verhaal-excerpt"><?php echo ihn_samenvatting($post_id); ?></p>
        <div class="verhaal-lees-verder">
            Lees <a href="<?php echo esc_url(get_permalink($post_id)); ?>"><em><?php echo esc_html(get_the_title($post_id)); ?></em></a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function ihn_homepage_shortcode() {
    $verhalen = get_posts([
        'category_name'  => 'verhalen',
        'posts_per_page' => 4,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    $uitspraak = get_posts([
        'category_name'  => 'uitspraak',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    ob_start();
    ?>
    <div class="ihn-home-grid">
        <?php if (!empty($verhalen)) :
            echo ihn_verhaal_kaart(
                $verhalen[0]->ID,
                get_post_meta($verhalen[0]->ID, 'verhaal_nummer', true),
                'ihn-verhaal-groot'
            );
        endif; ?>

        <?php if (!empty($uitspraak)) : $u = $uitspraak[0]; ?>
        <div class="ihn-uitspraak-card">
            <div class="uitspraak-label">De uitspraak</div>
            <div class="uitspraak-content">
                <?php echo apply_filters('the_content', $u->post_content); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($verhalen) > 1) : ?>
        <div class="ihn-verhalen-rij2">
            <?php for ($i = 1; $i < count($verhalen); $i++) {
                echo ihn_verhaal_kaart(
                    $verhalen[$i]->ID,
                    get_post_meta($verhalen[$i]->ID, 'verhaal_nummer', true),
                    'ihn-verhaal-2'
                );
            } ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}



// ============================================
// BLOCK PATTERN: DE UITSPRAAK TEMPLATE
// ============================================

add_action('init', function () {
    register_block_pattern(
        'inhetnauw/uitspraak-template',
        [
            'title'       => 'De Uitspraak — nieuw artikel',
            'description' => 'Gebruik dit patroon voor elk nieuw artikel in de categorie Uitspraak. Volg de vaste opbouw: regel 1 datum+tijd, regel 2 locatie, dan de inhoud.',
            'categories'  => ['text'],
            'content'     => '<!-- wp:paragraph -->
<p>📅 [datum en tijd van de uitspraak, bijv: 14 oktober 2026, 10:30 uur]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>📍 [locatie, bijv: Rechtbank Amsterdam]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>[Eerste zin van de uitspraak. Maximaal 3-5 alinea\'s totaal.]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>[Tweede alinea inhoud.]</p>
<!-- /wp:paragraph -->',
        ]
    );
});

// ============================================
// BLOCK PATTERN: PULL QUOTE
// ============================================

add_action('init', function () {
    register_block_pattern(
        'inhetnauw/pull-quote',
        [
            'title'       => 'Pull quote',
            'description' => 'Een citaat of kernzin die inspringt naar rechts met grotere tekst.',
            'categories'  => ['text'],
            'content'     => '<!-- wp:pullquote -->
<figure class="wp-block-pullquote"><blockquote><p>Voeg hier de pull quote tekst in.</p></blockquote></figure>
<!-- /wp:pullquote -->',
        ]
    );
});

// ============================================
// BLOCK PATTERN: SCENEWISSELING
// ============================================

add_action('init', function () {
    register_block_pattern(
        'inhetnauw/scenewisseling',
        [
            'title'       => 'Scenewisseling',
            'description' => 'Een horizontaal lijntje dat een overgang naar een andere locatie of scène aangeeft.',
            'categories'  => ['text'],
            'content'     => '<!-- wp:html -->
<hr class="ihn-scenewisseling" aria-hidden="true">
<!-- /wp:html -->',
        ]
    );
});


// ============================================
// BLOCK PATTERN: HET VONNIS KADER
// ============================================

add_action('init', function () {
    register_block_pattern(
        'inhetnauw/het-vonnis',
        [
            'title'       => 'Het vonnis',
            'description' => 'Een kader waar het vonnis in staat. Tekst loopt erumheen.',
            'categories'  => ['text'],
            'content'     => '<!-- wp:group {"className":"ihn-vonnis-kader","lock":{"move":false,"remove":false}} -->
<div class="wp-block-group ihn-vonnis-kader">
    <!-- wp:heading {"level":3} -->
    <h3>Het vonnis</h3>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph -->
    <p>Voeg hier de tekst van het vonnis in.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
        ]
    );
});

// ============================================
// VERHAAL NUMMERING
// ============================================

add_action('init', function () {
    register_post_meta('post', 'verhaal_nummer', [
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function () { return current_user_can('edit_posts'); }
    ]);
});

// Auto-nummer toekennen als post in categorie "verhalen" wordt gepubliceerd
add_action('publish_post', function ($post_id) {
    if (has_category('verhalen', $post_id)) {
        // Check of nummer al bestaat
        $existing = get_post_meta($post_id, 'verhaal_nummer', true);
        if (!$existing) {
            // Tel alle posts in "verhalen" met een nummer
            $args = [
                'category_name' => 'verhalen',
                'meta_key' => 'verhaal_nummer',
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'posts_per_page' => 1,
            ];
            $query = new WP_Query($args);
            $highest = 0;
            if ($query->have_posts()) {
                $highest = (int) get_post_meta($query->posts[0]->ID, 'verhaal_nummer', true);
            }
            wp_reset_postdata();
            
            // Toekennen: highest + 1
            update_post_meta($post_id, 'verhaal_nummer', $highest + 1);
        }
    }
});

add_action('wp_body_open', function () {
    if (is_single() && has_category('verhalen')) {
        // Voeg CSS toe voor positioning
        echo '<style>
            .ihn-gerelateerde {
                position: relative;
                z-index: 1;
            }
        </style>';
    }
});


// ============================================
// GERELATEERDE VERHALEN — SINGLE PAGINA
// ============================================
// Voeg het verhaalnummer rechtstreeks toe aan de post-title markup.
// Zo is de cirkel niet afhankelijk van JavaScript of browsergedrag.
add_filter('render_block', function ($block_content, $block) {
    if (!is_single() || !has_category('verhalen') || ($block['blockName'] ?? '') !== 'core/post-title') {
        return $block_content;
    }

    $nummer = get_post_meta(get_the_ID(), 'verhaal_nummer', true);
    $nummer_text = esc_html($nummer ?: '—');
    $badge = '<span class="verhaal-nummer-single">' . $nummer_text . '</span>';

    return preg_replace('/(<h1\b[^>]*>)/i', '$1' . $badge, $block_content, 1);
}, 10, 2);


function ihn_gerelateerde_verhalen() {
    $current_post_id = get_the_ID();
    
    $verhalen = get_posts([
        'category_name'  => 'verhalen',
        'posts_per_page' => 6,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'exclude'        => $current_post_id,
    ]);

    if (empty($verhalen)) {
        return '';
    }

    ob_start();
    ?>
    <div class="ihn-gerelateerde">
        <h2>Meer verhalen</h2>
        <div class="ihn-gerelateerde-grid">
            <?php foreach ($verhalen as $post) : setup_postdata($post); ?>
            <div class="ihn-verhaal ihn-verhaal-2">
                <p class="verhaal-excerpt"><?php echo ihn_samenvatting($post->ID); ?></p>
                <p class="verhaal-lees-verder">
                    Lees <a href="<?php echo esc_url(get_permalink($post->ID)); ?>"><em><?php echo esc_html(get_the_title($post->ID)); ?></em></a>
                </p>
            </div>
            <?php endforeach; wp_reset_postdata(); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_action('wp_footer', function () {
    if (is_single() && has_category('verhalen')) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var placeholder = document.getElementById('ihn-gerelateerde-placeholder');
            if (placeholder) {
                placeholder.innerHTML = `<?php echo addslashes(ihn_gerelateerde_verhalen()); ?>`;
            }
        });
        </script>
        <?php
    }
});

add_action('init', function () {
    register_block_pattern(
        'inhetnauw/het-vonnis',
        [
            'title'       => 'Het vonnis',
            'description' => 'Plaats dit blok ongeveer 2/3 door het artikel waar het vonnis hoort. Tekst loopt erumheen.',
            'categories'  => ['text'],
            'content'     => '<!-- wp:group {"className":"ihn-vonnis-kader"} -->
<div class="wp-block-group ihn-vonnis-kader">
    <!-- wp:heading {"level":3} -->
    <h3>Het vonnis</h3>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph -->
    <p>Voeg hier de tekst van het vonnis in.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
        ]
    );
});


add_shortcode('ihn_homepage', 'ihn_homepage_shortcode');

add_filter('the_content', function ($content) {
    if (is_singular('post') && in_the_loop() && is_main_query()
        && has_category('verhalen', get_the_ID())) {
        return ihn_leestijd_html(get_the_ID()) . $content;
    }
    return $content;
});
