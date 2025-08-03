<?php
function my_child_theme_enqueue_styles() {
    // Nejprve načteme styly parent šablony
    wp_enqueue_style( 'shoptimizer-style', get_template_directory_uri() . '/style.css' );

    // Poté child styly (které mohou přepisovat parent podle specifity nebo !important)
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'shoptimizer-style' ) // závislost = nejdřív se načte parent
    );
}
add_action( 'wp_enqueue_scripts', 'my_child_theme_enqueue_styles' );

function child_replace_site_branding_hook() {
    remove_action( 'shoptimizer_header', 'shoptimizer_site_branding', 20 );
}
add_action( 'after_setup_theme', 'child_replace_site_branding_hook', 20 );

function shoptimizer_site_branding_custom() {
    $shoptimizer_mobile_menu_text_display = '';
    $shoptimizer_mobile_menu_text_display = shoptimizer_get_option( 'shoptimizer_mobile_menu_text_display' );

    $shoptimizer_mobile_menu_text = shoptimizer_get_option( 'shoptimizer_mobile_menu_text' );
    ?>
    <div class="site-branding">
        <button class="menu-toggle" aria-label="Menu" aria-controls="site-navigation" aria-expanded="false">
            <span class="bar"></span><span class="bar"></span><span class="bar"></span>
            <?php if ( 'yes' === $shoptimizer_mobile_menu_text_display ) { ?>
                <span class="bar-text"><?php echo shoptimizer_safe_html( $shoptimizer_mobile_menu_text ); ?></span>
            <?php } ?>
        </button>
        <?php shoptimizer_site_title_or_logo();


        // Načteme jednou, abychom nevolali ACF opakovaně:
        $button_data = get_field( 'header_button', 'option' );

        // Zkontrolujeme, že jde o pole s indexem i objektem:
        if ( is_array( $button_data )
          && ! empty( $button_data['header_button_post'] )
          && is_object( $button_data['header_button_post'] )
          && ! empty( $button_data['header_button_post']->ID )
          && ! empty( $button_data['header_button_title'] )
        ) {
            // Raději použijeme permalink než GUID:
            $button_link  = get_permalink( $button_data['header_button_post']->ID );
            $button_title = $button_data['header_button_title'];

            // Vytiskneme jen pokud máme obojí, a zároveň data upravíme pro bezpečný výstup:
            printf(
                '<a class="mobile-hidden header-button" href="%s">%s</a>',
                esc_url(  $button_link  ),
                esc_html( $button_title )
            );
        }
        ?>


    </div>
    <?php
}

add_action( 'shoptimizer_header', 'shoptimizer_site_branding_custom', 20 );

add_action( 'shoptimizer_navigation', 'shoptimizer_myaccount_icon', 55 );


if ( ! function_exists( 'shoptimizer_myaccount_icon' ) ) {
    /**
     * Vlastní ikonka My Account
     */
    function shoptimizer_myaccount_icon() {
        ?>
        <div class="shoptimizer-myaccount">
            <a href="<?php echo esc_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ); ?>"
               title="<?php esc_attr_e( 'My Account', 'shoptimizer' ); ?>">

                <!-- sem vložte svou SVG ikonu nebo <i> font-icon -->
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none">
                    <rect width="40" height="40" rx="20" fill="#EEEFF8"/>
                    <path d="M22.6667 17.3333C22.6667 15.8606 21.4728 14.6667 20 14.6667C18.5272 14.6667 17.3333 15.8606 17.3333 17.3333C17.3333 18.8061 18.5272 20 20 20C21.4728 20 22.6667 18.8061 22.6667 17.3333ZM24 17.3333C24 18.6298 23.3826 19.7814 22.4264 20.5124C23.0971 20.8089 23.714 21.2289 24.2425 21.7575C25.3677 22.8827 26 24.4087 26 26C26 26.3682 25.7015 26.6667 25.3333 26.6667C24.9651 26.6667 24.6667 26.3682 24.6667 26C24.6667 24.7623 24.175 23.5754 23.2998 22.7002C22.452 21.8524 21.3116 21.3643 20.1159 21.3346L20 21.3333C18.7623 21.3333 17.5754 21.825 16.7002 22.7002C15.825 23.5754 15.3333 24.7623 15.3333 26C15.3333 26.3682 15.0349 26.6667 14.6667 26.6667C14.2985 26.6667 14 26.3682 14 26C14 24.4087 14.6323 22.8827 15.7575 21.7575C16.286 21.229 16.9027 20.8089 17.5732 20.5124C16.6172 19.7814 16 18.6297 16 17.3333C16 15.1242 17.7909 13.3333 20 13.3333C22.2091 13.3333 24 15.1242 24 17.3333Z" fill="#141414"/>
                </svg>

            </a>
        </div>
        <?php
    }
}

// v functions.php child-theme

// nejdřív odregistrovat původní košík
add_action( 'after_setup_theme', 'child_override_header_cart', 11 );
function child_override_header_cart() {
    // odregistrovat původní košík
    remove_action( 'shoptimizer_header',     'shoptimizer_header_cart', 50 );
    remove_action( 'shoptimizer_navigation', 'shoptimizer_header_cart', 60 );

    // zaregistrovat vlastní verzi
    add_action( 'shoptimizer_header',     'my_custom_header_cart', 50 );
    add_action( 'shoptimizer_navigation', 'my_custom_header_cart', 60 );
}

function my_custom_header_cart() {
    if ( ! function_exists( 'shoptimizer_is_woocommerce_activated' ) || ! shoptimizer_is_woocommerce_activated() ) {
        return;
    }

    // počet položek v košíku
    $count = WC()->cart->get_cart_contents_count();
    ?>

    <nav class="my-header-cart-wrapper">
        <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="my-cart-link">
            <span class="my-cart-icon">
                <!-- Inline SVG ikona košíku -->
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 40 40" fill="currentColor">
<rect width="40" height="40" rx="20" fill="#EEEFF8"/>
<g clip-path="url(#clip0_5_80)">
<path d="M16.0001 26.0001C16.0001 25.2637 16.597 24.6668 17.3334 24.6668C18.0697 24.6669 18.6667 25.2638 18.6667 26.0001C18.6667 26.7364 18.0697 27.3334 17.3334 27.3335C16.5971 27.3335 16.0002 26.7364 16.0001 26.0001ZM23.3334 26.0001C23.3334 25.2637 23.9304 24.6668 24.6667 24.6668C25.4031 24.6669 26.0001 25.2638 26.0001 26.0001C26 26.7364 25.403 27.3334 24.6667 27.3335C23.9304 27.3335 23.3335 26.7364 23.3334 26.0001ZM14.7 12.7L14.7582 12.7026C15.0474 12.7278 15.2902 12.9388 15.352 13.227L15.9529 16.0333H26.7266C26.9288 16.0333 27.12 16.1251 27.2465 16.2827C27.373 16.4404 27.4212 16.6471 27.3774 16.8445L26.2774 21.7964L26.2778 21.7967C26.1799 22.2412 25.9334 22.6392 25.5789 22.9246C25.2465 23.1922 24.8377 23.3459 24.4128 23.3647L24.3276 23.3667H17.8067V23.3663C17.3468 23.3732 16.8984 23.2217 16.5372 22.9363C16.1722 22.648 15.919 22.2414 15.8214 21.7866V21.7863L14.7628 16.8445C14.7613 16.8379 14.7598 16.8313 14.7585 16.8247L14.1609 14.0333H13.3666C12.9984 14.0333 12.7 13.7349 12.7 13.3667C12.7 12.9985 12.9984 12.7 13.3666 12.7H14.7ZM17.1251 21.507L17.1397 21.563C17.1793 21.6918 17.2572 21.806 17.3637 21.8901C17.4854 21.9862 17.6367 22.0371 17.7917 22.0337C17.7967 22.0335 17.8018 22.0333 17.8067 22.0333H24.3256C24.4773 22.0331 24.6244 21.981 24.7426 21.8859C24.8607 21.7907 24.9431 21.658 24.9757 21.5099V21.5089L25.8956 17.3667H16.2384L17.1251 21.507Z" fill="#141414"/>
</g>
<defs>
<clipPath id="clip0_5_80">
<rect width="16" height="16" fill="white" transform="translate(12 12)"/>
</clipPath>
</defs>
</svg>
            </span>
            <?php if ( $count > 0 ) : ?>
                <span class="my-cart-count"><?php echo esc_html( $count ); ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <?php
}


function theme_update_checker() {
    include 'plugin-update-checker/plugin-update-checker.php';
    $my_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/owdea/TomGrass',
        __FILE__,
        'shoptimizer-child'
    );

    $my_update_checker->setBranch( 'main' );

    $my_update_checker->setAuthentication(
            'github_pat_'.
            '11AYEW4KQ0o'.
            'ypeZtwQo0xN_c'.
            'iXVxVOzYaDutXY'.
            'VdVuumQ7kRCG'.
            '4SCbrQsR7evnX'.
            'K5q5JEHWCMO'.
            'nkTUsiVa');
}

add_action( 'after_setup_theme', 'theme_update_checker' );





add_filter('gettext', 'child_shoptimizer_custom_texts', 20, 3);
function child_shoptimizer_custom_texts($translated, $original, $domain)
{
    if ('shoptimizer' === $domain) {
        switch ($original) {
            case 'Shopping Cart':
                return 'Košík';
            case 'Shipping and Checkout':
                return 'Doprava a platba';
            case 'Confirmation':
                return 'Potvrzení objednávky';
            case 'Currently on step %s of 3':
                return 'Právě jste na kroku %s ze 3';
        }
    }
    return $translated;
}
