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
        get_stylesheet_uri(),
        [],
        filemtime(get_theme_file_path('style.css'))
    );

    wp_enqueue_style(
        'ihn-summary-palette',
        get_theme_file_uri('assets/css/summary-palette.css'),
        ['ihn-style'],
        filemtime(get_theme_file_path('assets/css/summary-palette.css'))
    );

    wp_enqueue_script(
        'ihn-theme-toggle',
        get_theme_file_uri('assets/js/theme-toggle.js'),
        [],
        '1.0.0',
        true
    );

    if (is_singular('post') && has_category('verhalen')) {
        wp_enqueue_script(
            'ihn-story-sharing',
            get_theme_file_uri('assets/js/story-sharing.js'),
            [],
            filemtime(get_theme_file_path('assets/js/story-sharing.js')),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'ihn_enqueue_assets');
add_editor_style('assets/css/summary-palette.css');

add_filter('body_class', function ($classes) {
    if (is_singular('post') && has_category('verhalen', get_queried_object_id())
        && !in_array('category-verhalen', $classes, true)) {
        $classes[] = 'category-verhalen';
    }

    return $classes;
});



// Samenvattingstekst voor deelacties en tekstuele toepassingen.
function ihn_samenvatting_tekst($post_id) {
    $opgemaakte_samenvatting = get_post_meta($post_id, '_ihn_samenvatting', true);
    if ($opgemaakte_samenvatting !== '') {
        $tekst = $opgemaakte_samenvatting;
    } elseif (has_excerpt($post_id)) {
        $tekst = get_post_field('post_excerpt', $post_id);
    } else {
        $tekst = get_post_field('post_content', $post_id);
        $tekst = preg_replace('/<!--.*?-->/s', '', $tekst);
        $tekst = strip_shortcodes($tekst);
    }
    $tekst = preg_replace('/<br\s*\/?>|<\/(?:p|div|li|blockquote|h[1-6])\s*>/i', "\n", $tekst);
    $tekst = wp_strip_all_tags($tekst);
    $tekst = str_replace(["\r\n", "\r"], "\n", $tekst);
    $tekst = trim(preg_replace("/\n{3,}/", "\n\n", $tekst));
    if (mb_strlen($tekst) > 280) {
        $tekst = mb_substr($tekst, 0, 280);
        $tekst = preg_replace('/\s+\S*$/u', '', $tekst) . '…';
    }
    return $tekst;
}

function ihn_samenvatting($post_id) {
    $opgemaakte_samenvatting = get_post_meta($post_id, '_ihn_samenvatting', true);
    if ($opgemaakte_samenvatting !== '') {
        return wp_kses_post($opgemaakte_samenvatting);
    }

    return nl2br(esc_html(ihn_samenvatting_tekst($post_id)));
}

function ihn_samenvatting_meta_box() {
    add_meta_box(
        'ihn-samenvatting',
        'Samenvatting voor tegels',
        'ihn_samenvatting_meta_box_html',
        'post',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_post', 'ihn_samenvatting_meta_box');

function ihn_samenvatting_meta_box_html($post) {
    wp_nonce_field('ihn_samenvatting_opslaan', 'ihn_samenvatting_nonce');
    $samenvatting = get_post_meta($post->ID, '_ihn_samenvatting', true);
    ?>
    <div class="ihn-summary-editor">
        <div class="ihn-summary-editor-toolbar" role="toolbar" aria-label="Tekstopmaak">
            <button type="button" class="button" data-command="bold" aria-label="Vet" title="Vet"><strong>B</strong></button>
            <button type="button" class="button" data-command="italic" aria-label="Cursief" title="Cursief"><em>I</em></button>
            <button type="button" class="button" data-command="underline" aria-label="Onderstrepen" title="Onderstrepen"><u>U</u></button>
            <button type="button" class="button" data-command="superscript" aria-label="Superscript" title="Superscript">x<sup>2</sup></button>
            <button type="button" class="button" data-command="subscript" aria-label="Subscript" title="Subscript">x<sub>2</sub></button>
            <label class="screen-reader-text" for="ihn-summary-font-size">Fontgrootte</label>
            <select id="ihn-summary-font-size" data-font-size aria-label="Fontgrootte">
                <option value="">Fontgrootte</option>
                <option value="12px">12 px</option>
                <option value="14px">14 px</option>
                <option value="16px">16 px</option>
                <option value="18px">18 px</option>
                <option value="24px">24 px</option>
                <option value="32px">32 px</option>
            </select>
            <button type="button" class="button" data-baseline-shift="0.7em" aria-label="1 teken hoger" title="1 teken hoger">↑</button>
            <button type="button" class="button" data-baseline-shift="-0.7em" aria-label="1 teken lager" title="1 teken lager">↓</button>
            <button type="button" class="button" data-reset-format aria-label="Opmaak wissen" title="Opmaak wissen">Tx</button>
            <?php
            $tekstkleuren = [
                'inkt' => 'Tekst',
                'marine' => 'Marine',
                'accent' => 'Accent',
                'wit' => 'Wit',
            ];
            $achtergrondkleuren = [
                'pagina' => 'Pagina',
                'kaart' => 'Kaart',
                'marine' => 'Marine',
                'inkt' => 'Inkt',
            ];
            ?>
            <fieldset class="ihn-summary-color-group">
                <legend>Tekstkleur</legend>
                <?php foreach ($tekstkleuren as $kleur => $naam) : ?>
                    <button type="button" class="button ihn-summary-color-button" data-color-kind="text" data-color-class="ihn-samenvatting-tekst-<?php echo esc_attr($kleur); ?>" aria-label="Tekstkleur: <?php echo esc_attr($naam); ?>" title="Tekstkleur: <?php echo esc_attr($naam); ?>">
                        <span class="ihn-summary-swatch-text ihn-summary-swatch-<?php echo esc_attr($kleur); ?>" aria-hidden="true">A</span>
                    </button>
                <?php endforeach; ?>
            </fieldset>
            <fieldset class="ihn-summary-color-group">
                <legend>Achtergrondkleur</legend>
                <?php foreach ($achtergrondkleuren as $kleur => $naam) : ?>
                    <button type="button" class="button ihn-summary-color-button" data-color-kind="background" data-color-class="ihn-samenvatting-achtergrond-<?php echo esc_attr($kleur); ?>" aria-label="Achtergrondkleur: <?php echo esc_attr($naam); ?>" title="Achtergrondkleur: <?php echo esc_attr($naam); ?>">
                        <span class="ihn-summary-swatch-background ihn-summary-swatch-<?php echo esc_attr($kleur); ?>" aria-hidden="true"></span>
                    </button>
                <?php endforeach; ?>
            </fieldset>
        </div>
        <div id="ihn_summary_editor" class="ihn-summary-editor-content" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Samenvatting voor tegels"><?php echo wp_kses_post($samenvatting); ?></div>
        <textarea id="ihn_samenvatting" name="ihn_samenvatting" hidden><?php echo esc_textarea($samenvatting); ?></textarea>
    </div>
    <?php
}

function ihn_samenvatting_editor_assets($hook_suffix) {
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'post') {
        return;
    }

    wp_enqueue_style(
        'ihn-summary-editor',
        get_theme_file_uri('assets/css/summary-palette.css'),
        [],
        filemtime(get_theme_file_path('assets/css/summary-palette.css'))
    );
    wp_enqueue_script(
        'ihn-summary-editor',
        get_theme_file_uri('assets/js/summary-editor.js'),
        [],
        filemtime(get_theme_file_path('assets/js/summary-editor.js')),
        true
    );
}
add_action('admin_enqueue_scripts', 'ihn_samenvatting_editor_assets');

function ihn_samenvatting_opslaan($post_id) {
    if (!isset($_POST['ihn_samenvatting_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ihn_samenvatting_nonce'])), 'ihn_samenvatting_opslaan')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || wp_is_post_revision($post_id)
        || !current_user_can('edit_post', $post_id)
        || !isset($_POST['ihn_samenvatting'])) {
        return;
    }

    $samenvatting = wp_kses_post(wp_unslash($_POST['ihn_samenvatting']));
    if ($samenvatting === '') {
        delete_post_meta($post_id, '_ihn_samenvatting');
    } else {
        update_post_meta($post_id, '_ihn_samenvatting', $samenvatting);
    }

    $platte_tekst = preg_replace('/<br\s*\/?>|<\/\s*(?:p|div|li|blockquote|h[1-6])\s*>/i', "\n", $samenvatting);
    $platte_tekst = html_entity_decode(wp_strip_all_tags($platte_tekst), ENT_QUOTES, get_bloginfo('charset'));
    $platte_tekst = trim(preg_replace("/\n{3,}/", "\n\n", str_replace(["\r\n", "\r"], "\n", $platte_tekst)));

    if (get_post_field('post_excerpt', $post_id) !== $platte_tekst) {
        remove_action('save_post_post', 'ihn_samenvatting_opslaan');
        wp_update_post([
            'ID' => $post_id,
            'post_excerpt' => $platte_tekst,
        ]);
        add_action('save_post_post', 'ihn_samenvatting_opslaan');
    }
}
add_action('save_post_post', 'ihn_samenvatting_opslaan');

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

add_filter('the_content', function ($content) {
    if (!is_singular('post') || !in_the_loop() || !is_main_query()
        || !has_category('verhalen', get_the_ID())) {
        return $content;
    }

    $url = get_permalink();
    $title = get_the_title();
    $share_text = ihn_samenvatting_tekst(get_the_ID())
        . "\n\nLees " . $title . " op In Het Nauw: " . $url;

    ob_start();
    ?>
    <nav class="ihn-story-sharing" aria-label="Deel dit verhaal">
        <p class="ihn-story-sharing-title">Deel dit verhaal</p>
        <div class="ihn-story-sharing-buttons">
            <a class="ihn-social-link" href="<?php echo esc_url('https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url) . '&quote=' . rawurlencode($share_text)); ?>"
               target="_blank" rel="noopener noreferrer" aria-label="Deel op Facebook" title="Facebook">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.5 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.6-1.5h1.7V3.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V10H7.3v3h2.8v8h3.4z"/></svg>
            </a>
            <button class="ihn-social-link" type="button" data-share-image="<?php echo esc_attr(wp_json_encode([
                'summary' => ihn_samenvatting_tekst(get_the_ID()),
                'title' => $title,
            ])); ?>"
                    aria-label="Kopieer afbeelding voor Instagram" title="Instagram">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
            </button>
            <a href="<?php echo esc_attr('https://wa.me/?text=' . rawurlencode($share_text)); ?>"
               class="ihn-social-link" target="_blank" rel="noopener noreferrer" aria-label="Deel via WhatsApp" title="WhatsApp">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.7a8.4 8.4 0 0 1-12.4 7.4L3 20.5l1.5-4.9a8.4 8.4 0 1 1 16-3.9Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8.2 7.8c.2-.5.4-.5.7-.5h.5c.2 0 .4.1.5.4l.8 1.8c.1.2.1.4 0 .6l-.5.7c-.2.2-.2.4 0 .6.5.8 1.2 1.5 2 2 .2.1.4.1.6-.1l.8-.9c.2-.2.4-.2.6-.1l1.7.8c.3.1.4.3.4.5 0 .3-.2 1.1-.7 1.5-.5.5-1.2.7-2 .5-1.1-.2-2.4-.9-3.7-2.1-1.1-1-2-2.3-2.3-3.3-.4-1.1 0-1.9.6-2.4Z" fill="currentColor"/></svg>
            </a>
            <a class="ihn-social-link" href="<?php echo esc_url('mailto:?subject=' . rawurlencode($title) . '&body=' . rawurlencode($share_text)); ?>"
               aria-label="Deel via e-mail" title="E-mail">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1" fill="none" stroke="currentColor" stroke-width="2"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <a class="ihn-social-link" href="<?php echo esc_attr('sms:?&body=' . rawurlencode($share_text)); ?>"
               aria-label="Deel via iMessage" title="iMessage">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-5.5a7.5 7.5 0 1 1 17-4Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="8" cy="11.5" r="1" fill="currentColor"/><circle cx="12.5" cy="11.5" r="1" fill="currentColor"/><circle cx="17" cy="11.5" r="1" fill="currentColor"/></svg>
            </a>
        </div>
        <p class="ihn-story-sharing-status" aria-live="polite"></p>
    </nav>
    <?php

    return $content . ob_get_clean();
});

// Eén verhaal-kaart: samenvatting en verwijzing (geen titel, geen nummer)
function ihn_verhaal_kaart($post_id, $nummer, $klasse) {
    ob_start();
    ?>
    <div class="ihn-verhaal <?php echo esc_attr($klasse); ?>">
        <div class="verhaal-excerpt"><?php echo ihn_samenvatting($post_id); ?></div>
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
            <p class="ihn-uitspraak-meer">
                <a href="<?php echo esc_url(home_url('/de-uitspraak')); ?>">Lees meer uitspraken</a>
            </p>
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
    <?php echo ihn_archief_link(); ?>
    <?php
    return ob_get_clean();
}

function ihn_archief_link() {
    return '<p class="ihn-archief-link"><a href="' . esc_url(home_url('/artikelen')) . '">Lees alle verhalen in het archief</a></p>';
}

// Verhalen: alleen titels met links
function ihn_archief_verhalen() {
    $category = get_category_by_slug('verhalen');
    if (!$category) {
        return '<p>Er zijn nog geen verhalen gepubliceerd.</p>';
    }
    $articles = get_posts([
        'category'       => $category->term_id,
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    if (empty($articles)) {
        return '<p>Er zijn nog geen verhalen gepubliceerd.</p>';
    }
    ob_start();
    ?>
    <ul class="ihn-archief-lijst">
        <?php foreach ($articles as $article) : ?>
            <li><a href="<?php echo esc_url(get_permalink($article)); ?>"><?php echo esc_html(get_the_title($article)); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
    return ob_get_clean();
}

// Uitspraken: alle artikelen volledig, onder elkaar
function ihn_archief_uitspraken() {
    $category = get_category_by_slug('uitspraak');
    if (!$category) {
        return '<p>Er zijn nog geen uitspraken gepubliceerd.</p>';
    }

    $articles = get_posts([
        'category'       => $category->term_id,
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    if (empty($articles)) {
        return '<p>Er zijn nog geen uitspraken gepubliceerd.</p>';
    }

    ob_start();
    ?>
    <div class="ihn-archief-volledig">
        <?php foreach ($articles as $article) : ?>
            <article class="ihn-archief-item">
                <div class="uitspraak-content">
                    <?php echo wpautop(do_shortcode($article->post_content)); ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

add_filter('the_content', function ($content) {
    if (!in_the_loop() || !is_main_query()) {
        return $content;
    }
    if (is_page('artikelen')) {
        return ihn_archief_verhalen();
    }
    if (is_page('de-uitspraak')) {
        return ihn_archief_uitspraken();
    }
    if (is_page('verhalen')) {
        return $content . ihn_archief_link();
    }
    return $content;
}, 20);

add_filter('query_vars', function ($query_vars) {
    $query_vars[] = 'ihn_archief';
    return $query_vars;
});

add_action('parse_request', function ($wp) {
    if (trim($wp->request, '/') === 'artikelen' && !get_page_by_path('artikelen')) {
        $wp->query_vars['ihn_archief'] = '1';
        unset($wp->query_vars['pagename']);
    }
});

add_action('template_redirect', function () {
    global $wp;

    if (!is_404() || trim($wp->request, '/') !== 'verhalen' || get_page_by_path('verhalen')) {
        return;
    }

    $category = get_category_by_slug('verhalen');
    if ($category) {
        wp_safe_redirect(get_category_link($category), 302);
        exit;
    }
});

add_filter('pre_handle_404', function ($preempt, $query) {
    if ($query->get('ihn_archief')) {
        $query->is_404 = false;
        return true;
    }

    return $preempt;
}, 10, 2);

add_filter('template_include', function ($template) {
    if (get_query_var('ihn_archief')) {
        return get_theme_file_path('templates/archive-artikelen.php');
    }

    return $template;
});

add_filter('pre_get_document_title', function ($title) {
    if (get_query_var('ihn_archief')) {
        return 'Archief – ' . get_bloginfo('name');
    }

    return $title;
});

add_filter('the_content', function ($content) {
    if (!in_the_loop() || !is_main_query()) {
        return $content;
    }
    if (is_page('artikelen')) {
        return ihn_archief_verhalen();
    }
    if (is_page('de-uitspraak')) {
        return ihn_archief_uitspraken();
    }
    if (is_page('verhalen')) {
        return $content . ihn_archief_link();
    }
    return $content;
}, 20);

add_filter('render_block', function ($block_content, $block) {
    $block_name = $block['blockName'] ?? '';

    if (is_page('artikelen') && $block_name === 'core/post-title') {
        return preg_replace(
            '/(<h[1-6]\b[^>]*>).*?(<\/h[1-6]>)/is',
            '$1Archief$2',
            $block_content,
            1
        );
    }

    if (is_category('verhalen') && $block_name === 'core/post-template') {
        return $block_content . ihn_archief_link();
    }

    return $block_content;
}, 20, 2);



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

// Houd het archief-label gelijk, ook als de header-template in WordPress is opgeslagen.
add_filter('render_block', function ($block_content, $block) {
    if (($block['blockName'] ?? '') !== 'core/template-part'
        || ($block['attrs']['slug'] ?? '') !== 'header') {
        return $block_content;
    }

    return preg_replace(
        '/(<a\b(?=[^>]*\bhref=["\']\/artikelen["\'])[^>]*>).*?(<\/a>)/is',
        '$1Archief$2',
        $block_content,
        1
    );
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
                <div class="verhaal-excerpt"><?php echo ihn_samenvatting($post->ID); ?></div>
                <p class="verhaal-lees-verder">
                    Lees <a href="<?php echo esc_url(get_permalink($post->ID)); ?>"><em><?php echo esc_html(get_the_title($post->ID)); ?></em></a>
                </p>
            </div>
            <?php endforeach; wp_reset_postdata(); ?>
        </div>
        <?php echo ihn_archief_link(); ?>
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

add_action('wp_footer', function () {
    if (!is_singular('post') || !has_category('verhalen')) {
        return;
    }
    $url      = esc_url(get_permalink());
    $titel    = esc_js(get_the_title());
    $voorwaarden = esc_url(home_url('/algemene-voorwaarden'));
    $share_url = get_permalink();
    $share_title = get_the_title();
    $share_text = ihn_samenvatting_tekst(get_the_ID())
        . "\n\nLees " . $share_title . " op In Het Nauw: " . $share_url;
    ?>
    <div id="ihn-copy-popup" class="ihn-copy-popup" role="dialog" aria-modal="true" aria-labelledby="ihn-copy-titel" hidden>
        <div class="ihn-copy-box">
            <p id="ihn-copy-titel">De copyright voor de gekopieerde tekst ligt bij In het Nauw. Voor meer informatie, zie <a href="<?php echo $voorwaarden; ?>">de algemene voorwaarden</a>.</p>
            <p>Je kan dit verhaal delen via diverse socials:</p>
            <ul class="ihn-copy-socials ihn-story-sharing-buttons">
                <li><a class="ihn-social-link" href="<?php echo esc_url('https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($share_url) . '&quote=' . rawurlencode($share_text)); ?>" target="_blank" rel="noopener noreferrer" aria-label="Deel op Facebook" title="Facebook">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.5 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.6-1.5h1.7V3.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V10H7.3v3h2.8v8h3.4z"/></svg>
                </a></li>
                <li><a class="ihn-social-link" href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
                </a></li>
                <li><a class="ihn-social-link" href="<?php echo esc_url('https://wa.me/?text=' . rawurlencode($share_text)); ?>" target="_blank" rel="noopener noreferrer" aria-label="Deel via WhatsApp" title="WhatsApp">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.7a8.4 8.4 0 0 1-12.4 7.4L3 20.5l1.5-4.9a8.4 8.4 0 1 1 16-3.9Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M8.2 7.8c.2-.5.4-.5.7-.5h.5c.2 0 .4.1.5.4l.8 1.8c.1.2.1.4 0 .6l-.5.7c-.2.2-.2.2 0 .6.5.8 1.2 1.5 2 2 .2.1.4.1.6-.1l.8-.9c.2-.2.4-.2.6-.1l1.7.8c.3.1.4.3.4.5 0 .3-.2 1.1-.7 1.5-.5.5-1.2.7-2 .5-1.1-.2-2.4-.9-3.7-2.1-1.1-1-2-2.3-2.3-3.3-.4-1.1 0-1.9.6-2.4Z" fill="currentColor"/></svg>
                </a></li>
                <li><a class="ihn-social-link" href="<?php echo esc_url('mailto:?subject=' . rawurlencode($share_title) . '&body=' . rawurlencode($share_text)); ?>" aria-label="Deel via e-mail" title="E-mail">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1" fill="none" stroke="currentColor" stroke-width="2"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a></li>
                <li><a class="ihn-social-link" href="<?php echo esc_attr('sms:?&body=' . rawurlencode($share_text)); ?>" aria-label="Deel via iMessage" title="iMessage">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-5.5a7.5 7.5 0 1 1 17-4Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="8" cy="11.5" r="1" fill="currentColor"/><circle cx="12.5" cy="11.5" r="1" fill="currentColor"/><circle cx="17" cy="11.5" r="1" fill="currentColor"/></svg>
                </a></li>
            </ul>
            <button type="button" class="ihn-copy-sluit">Sluiten</button>
        </div>
    </div>
    <script>
    (function () {
        var popup = document.getElementById('ihn-copy-popup');
        var voet = "\n\nDeze tekst is afkomstig van \"<?php echo $titel; ?>\", <?php echo $url; ?>, \u00A9 In het Nauw";
        document.addEventListener('copy', function (e) {
            var selectie = window.getSelection();
            if (!selectie || selectie.isCollapsed) return;
            popup.hidden = false;
            if (e.clipboardData) {
                e.preventDefault();
                e.clipboardData.setData('text/plain', selectie.toString() + voet);
            }
        });

        popup.querySelector('.ihn-copy-sluit').addEventListener('click', function () {
            popup.hidden = true;
        });
        popup.addEventListener('click', function (e) {
            if (e.target === popup) popup.hidden = true;
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') popup.hidden = true;
        });
    })();
    </script>
    <?php
});
