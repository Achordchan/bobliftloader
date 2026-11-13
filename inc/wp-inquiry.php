<?php
declare(strict_types=1);

/**
 * The enquiry form, sent to the BOB-LIFT inquiry plugin (achord-wp-inquiry, shared with the crane site): it keeps every
 * enquiry under 询盘 in the admin, sends the classic card email and the DingTalk message, and checks Turnstile, the
 * blocklist and how often one network sends. The theme draws the form (its CSS is unchanged), names the fields and hands the
 * plugin what only it knows: the product card in the mail, where notifications go, the site's words.
 * Without the plugin active the theme keeps its email-draft form.
 */

// The form's enquiry types as the sales team reads them.
const BL_ENQUIRY_FAMILIES_ZH = ['Skid steer loader' => '滑移装载机', 'Mini dumper' => '迷你翻斗车', 'Help me choose' => '帮我选型', 'Parts & service' => '配件与服务'];
// The equipment choices of the form: the page's key => the value sent.
const BL_ENQUIRY_FAMILIES = ['loader' => 'Skid steer loader', 'dumper' => 'Mini dumper', 'help' => 'Help me choose', 'service' => 'Parts & service'];

// The CF7 form markup the theme created (md5 of the trimmed form, without and with the country field it had until version 3).
const BL_INQUIRY_THEME_FORMS = ['8bdf283d0d601eb62cd7453c2388fa3f', 'c3c9d5d6f7fa17ef3bee2c7d68256e18'];

/**
 * What the Contact Form 7 form did that the inquiry plugin does not: fields edited in CF7, a confirmation mail to the
 * customer (Mail (2)) and copies to Cc or Bcc addresses. Empty when there is no such form or it did none of these.
 */
function bl_wp_inquiry_legacy_extras(): array
{
    $form = (int) get_option('bl_inquiry_form');
    if ($form <= 0) return [];
    $extras = [];
    $mail = get_post_meta($form, '_mail', true);
    if (is_array($mail) && preg_match('/^\s*(Cc|Bcc)\s*:\s*(.+)$/mi', (string) ($mail['additional_headers'] ?? ''), $match)) $extras[] = '通知邮件还抄送给 ' . trim($match[2]) . '（' . $match[1] . '），新插件不会抄送';
    // The form the theme made (with or without the country field it had until version 3), compared as saved.
    $saved = trim(str_replace("\r\n", "\n", (string) get_post_meta($form, '_form', true)));
    // The theme wrote the privacy link from home_url(), so a site under a subdirectory has its own path there.
    $saved = (string) preg_replace('#<a href="[^"]*">privacy information</a>#', '<a href="/privacy/">privacy information</a>', $saved);
    if ($saved !== '' && !in_array(md5($saved), BL_INQUIRY_THEME_FORMS, true)) $extras[] = 'CF7 表单的字段在后台改过（新表单是主题固定的字段：设备、姓名、邮箱、WhatsApp、公司、留言），改动的字段不会带过来';
    // A recipient the plugin cannot send to (a form tag such as [email], which CF7 filled per submission) stops being mailed.
    $odd = [];
    foreach (explode(',', is_array($mail) ? (string) ($mail['recipient'] ?? '') : '') as $part) {
        $part = trim($part);
        $address = preg_match('/<([^<>]+)>\s*$/', $part, $found) ? trim($found[1]) : $part;
        if ($part !== '' && $address !== '[_site_admin_email]' && !is_email($address)) $odd[] = $part;
    }
    if ($odd) $extras[] = '通知邮件的收件人里有 ' . implode('、', $odd) . '（按提交内容填的地址），新插件不会发给它';
    $reply = get_post_meta($form, '_mail_2', true);
    if (is_array($reply) && !empty($reply['active'])) $extras[] = '给客户的自动回复邮件（Mail (2)）已开启，新插件不发自动回复';
    return $extras;
}

/**
 * Whether the theme hands enquiries to the plugin now. A site whose CF7 form did something the plugin does not (above)
 * waits until an administrator confirms the switch (bl_inquiry_switch_confirmed), so nothing stops silently.
 *
 * Boundary: this theme no longer carries the Contact Form 7 path (the owner chose on 2026-11-03 to replace it, mail designs
 * included), so while a switch waits the front shows the theme's email-draft form, which still reaches the sales inbox
 * through the visitor's own email. The wait lasts only from deployment until the administrator who deploys it answers the
 * notice; a site that must keep CF7 meanwhile stays on the previous theme version until then.
 */
function bl_wp_inquiry_switch_allowed(): bool
{
    return get_option('bl_inquiry_switch_confirmed') === '1' || !bl_wp_inquiry_legacy_extras();
}

add_action('admin_notices', static function (): void {
    if (!current_user_can('manage_options') || bl_wp_inquiry_switch_allowed() || !function_exists('achord_inquiry_active')) return;
    $items = implode('', array_map(static fn(string $text): string => '<li>' . esc_html($text) . '</li>', bl_wp_inquiry_legacy_extras()));
    $url = wp_nonce_url(admin_url('admin-post.php?action=bl_inquiry_switch'), 'bl_inquiry_switch');
    echo '<div class="notice notice-warning"><p><strong>询盘插件还没接管表单</strong>（前台暂时是邮件草稿表单）。原来的 Contact Form 7 表单有新插件不做的地方：</p><ul style="list-style:disc;margin-left:20px">' . $items . '</ul><p>确认不再需要后再切换（切换后表单交给询盘插件）。<a class="button button-primary" href="' . esc_url($url) . '">确认切换到询盘插件</a></p></div>';
});
add_action('admin_post_bl_inquiry_switch', static function (): void {
    if (!current_user_can('manage_options')) wp_die('', '', ['response' => 403]);
    check_admin_referer('bl_inquiry_switch');
    update_option('bl_inquiry_switch_confirmed', '1', false);
    wp_safe_redirect(admin_url('edit.php?post_type=bl_inquiry'));
    exit;
});

/**
 * The theme no longer handles Contact Form 7 (no mail tags, honeypot, Akismet options or recipient override), so a page
 * cached before the switch that still posts the old CF7 form is turned back with a request to reload: nothing is mailed
 * or stored through that path, and the reloaded page carries the current form. Other CF7 forms are not affected.
 */
add_filter('wpcf7_validate', static function ($result, $tags = []) {
    $form = class_exists('WPCF7_ContactForm') ? WPCF7_ContactForm::get_current() : ($GLOBALS['cf7_form'] ?? null);
    $id = (int) get_option('bl_inquiry_form');
    if (!$form || $id <= 0 || (int) $form->id() !== $id || !is_object($result) || !method_exists($result, 'invalidate')) return $result;
    $target = null;
    foreach ((array) $tags as $tag) {
        if (is_object($tag) && ($target === null || ($tag->name ?? '') === 'email')) $target = $tag;
    }
    if ($target) $result->invalidate($target, 'This form has been updated. Please reload the page and send your enquiry again.');
    return $result;
}, 99, 2);

/**
 * A page or post whose saved content still holds the old form's CF7 shortcode ([contact-form-7 id="…"]) shows the theme's
 * form instead (the plugin's, or the email-draft form without it), so a reload after the message above really brings the
 * current form. With CF7 active its other forms render as CF7 draws them; with CF7 switched off (the plan) the theme
 * registers the two shortcode names itself, so the old form still becomes the theme's form and another form's leftover
 * shortcode shows nothing rather than its raw text. The form is recognised from its own post (number, CF7's hash, title),
 * without CF7's functions.
 */
function bl_wp_inquiry_is_legacy_shortcode(array $atts): bool
{
    $id = (int) get_option('bl_inquiry_form');
    if ($id <= 0) return false;
    $ref = trim((string) ($atts['id'] ?? ''));
    // A post number; a CF7 hash can be all digits too, so a number that is not this form's is checked as a hash below.
    if ($ref !== '' && ctype_digit($ref) && (int) $ref === $id) return true;
    // CF7 writes the first 7 characters of the form's _hash into the shortcode.
    $hash = (string) get_post_meta($id, '_hash', true);
    if ($ref !== '' && strlen($ref) >= 7 && $hash !== '' && str_starts_with($hash, $ref)) return true;
    // As CF7 does: the title counts when there is no id, or when the id names no form at all (a stale one, e.g. copied
    // from another site). An id of another existing form keeps that form.
    $title = trim((string) ($atts['title'] ?? ''));
    if ($title === '' || $title !== trim((string) get_post_field('post_title', $id))) return false;
    return $ref === '' || !bl_wp_inquiry_cf7_form_exists($ref);
}

/** Whether a CF7 shortcode id (post number or hash prefix) names an existing form, read from the posts without CF7. */
function bl_wp_inquiry_cf7_form_exists(string $ref): bool
{
    if (ctype_digit($ref) && get_post_type((int) $ref) === 'wpcf7_contact_form') return true;
    if (strlen($ref) < 7) return false;
    foreach (get_posts(['post_type' => 'wpcf7_contact_form', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true]) as $form) {
        if (str_starts_with((string) get_post_meta((int) $form, '_hash', true), $ref)) return true;
    }
    return false;
}

/**
 * A model the site sells, as written on its product (case aside), or ''. Read from the products' saved records only: the
 * shortcode can sit inside a product's own text, and loading the catalogue (bl_products) renders those texts again.
 */
function bl_wp_inquiry_known_model(string $model): string
{
    $model = trim($model);
    if ($model === '') return '';
    foreach (get_posts(['post_type' => 'bl_product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true]) as $id) {
        $record = get_post_meta((int) $id, '_bl_record', true);
        $known = is_array($record) ? trim((string) ($record['model'] ?? '')) : '';
        if ($known !== '' && strcasecmp($known, $model) === 0) return $known;
    }
    return '';
}

/**
 * The theme's form in place of the old CF7 one, with what the saved shortcode chose for it (the old form's product, model
 * and message took their defaults from the shortcode): the plugin's form (any number of them work on one page). Without
 * the plugin the page's own email-draft form is the one the script prepares (it is unique by id), so a second one is not
 * drawn: a button opens the visitor's email app addressed as that form would.
 */
function bl_wp_inquiry_legacy_shortcode_form(array $atts = []): string
{
    if (!bl_inquiry_live()) {
        return '<p class="bl-legacy-enquiry"><a class="bl-button bl-button-blue" href="' . esc_url(bl_mailto(bl_inquiry_fallback_email())) . '">Email your enquiry</a></p>';
    }
    $product = trim((string) ($atts['product'] ?? ''));
    $family = isset(BL_ENQUIRY_FAMILIES[$product]) ? $product : (array_search(strtolower($product), array_map('strtolower', BL_ENQUIRY_FAMILIES), true) ?: 'loader');
    $model = bl_wp_inquiry_known_model((string) ($atts['model'] ?? ''));
    $form = bl_wp_inquiry_form(['product' => $family, 'model' => $model, 'message' => (string) ($atts['message'] ?? ''), 'source' => $model !== '' ? 'Product page form' : 'Enquiry form']);
    return '<div class="bl-inquiry-live" data-thanks="' . esc_attr(bl_url('/thank-you/')) . '" data-model="' . esc_attr($model) . '" data-model-family="' . esc_attr($family) . '">' . $form . '</div>';
}

add_filter('pre_do_shortcode_tag', static function ($output, string $tag, $atts) {
    if (!in_array($tag, ['contact-form-7', 'contact-form'], true) || !bl_wp_inquiry_is_legacy_shortcode((array) $atts)) return $output;
    return bl_wp_inquiry_legacy_shortcode_form((array) $atts);
}, 10, 3);

// Without CF7 the names are not registered, and WordPress would print the saved shortcode as text.
add_action('init', static function (): void {
    foreach (['contact-form-7', 'contact-form'] as $name) {
        if (!shortcode_exists($name)) add_shortcode($name, static fn($atts = []): string => bl_wp_inquiry_is_legacy_shortcode((array) $atts) ? bl_wp_inquiry_legacy_shortcode_form((array) $atts) : '');
    }
}, 99);

/** Hand enquiries to the plugin. Its posted names keep this theme's bl_ prefix, which assets/js/attribution.js fills in. */
add_action('after_setup_theme', static function (): void {
    if (!bl_wp_inquiry_switch_allowed()) return;
    add_theme_support('achord-inquiry', [
        'site_label' => '装载设备站',
        'dingtalk_emoji' => '📩',
        'prefix' => 'bl_',
        'post_type' => 'bl_inquiry',
        // Flamingo's history keeps its own /wp/v2/bl_enquiries (wp-enquiries-rest.php); copies of it are in this list too.
        'rest_base' => 'bl_inquiries',
        'rest_record' => 'bl_inquiry',
        'required' => ['name', 'email'],
        'form_selector' => '.bl-inquiry-form',
        'attribution_key' => 'bl_attribution',
    ]);
});

// Addresses need the site's routes, so they are worked out when the plugin asks.
add_filter('achord_inquiry_config', static function (array $config): array {
    if (!current_theme_supports('achord-inquiry')) return $config;
    $config['success_url'] = home_url('/thank-you/');
    $config['fallback_url'] = home_url('/contact/');
    $config['logo_url'] = bl_wp_inquiry_absolute_url(bl_brand_logo());
    return $config;
});

/** Whether enquiries are sent through the plugin; otherwise the theme's email-draft form is shown. */
function bl_wp_inquiry_ready(): bool
{
    return function_exists('achord_inquiry_active') && function_exists('achord_inquiry_hidden_fields') && achord_inquiry_active();
}

// The theme's own script (assets/js/attribution.js) fills in the ad source, following the site's cookie banner; the
// plugin's script would do the same a second time.
add_action('wp_enqueue_scripts', static fn() => wp_dequeue_script('achord-inquiry'), 20);

/** The fields the form sends, as the sales team reads them (the mail, DingTalk and the 询盘 list). */
add_filter('achord_inquiry_fields', static fn(): array => [
    'product' => ['label' => '设备类型', 'short' => '', 'admin' => '设备类型', 'type' => 'text', 'mail' => false],
    'model' => ['label' => '型号', 'short' => '型号', 'admin' => '型号', 'type' => 'text', 'mail' => false],
    'name' => ['label' => '姓名', 'short' => '姓名', 'admin' => '姓名', 'type' => 'text'],
    'email' => ['label' => '邮箱', 'short' => '邮箱', 'admin' => '邮箱', 'type' => 'email'],
    'phone' => ['label' => 'WhatsApp', 'short' => 'WhatsApp', 'admin' => 'WhatsApp', 'type' => 'text'],
    'company' => ['label' => '公司', 'short' => '公司', 'admin' => '公司', 'type' => 'text'],
    // Not asked on the form: the country the sender's IP address belongs to, for the list's 国家 column.
    'country' => ['label' => '国家（按 IP）', 'short' => '', 'admin' => '国家（按 IP）', 'type' => 'text', 'mail' => false],
    'message' => ['label' => '留言', 'short' => '留言', 'admin' => '项目详情', 'type' => 'textarea', 'long' => true, 'mail' => false],
    // Asked only on the dealer application (/dealers/); empty on an ordinary enquiry, so the mail and DingTalk leave them out.
    'business' => ['label' => '业务类型', 'short' => '业务类型', 'admin' => '业务类型', 'type' => 'text'],
    'market' => ['label' => '经营国家/地区', 'short' => '经营地区', 'admin' => '经营国家/地区', 'type' => 'text'],
    'website' => ['label' => '公司网站', 'short' => '网站', 'admin' => '公司网站', 'type' => 'url'],
]);

/**
 * What the theme works out from the form: the equipment in Chinese (one of the four choices, else nothing), the model only
 * when it is one the site sells, and the country from the IP address (whatever was posted under that name).
 */
function bl_wp_inquiry_submission_fields(array $fields): array
{
    // The dealer application (inc/dealers.php): its own lead type, so the mail, DingTalk and the 询盘 list name it; what the
    // applicant would sell and their kind of business in Chinese. A value the form does not offer is dropped.
    if (sanitize_key((string) wp_unslash($_POST['bl_lead'] ?? '')) === 'dealer') {
        $fields['lead_type'] = 'dealer';
        $fields['product'] = BL_DEALER_INTERESTS[(string) ($fields['product'] ?? '')] ?? '';
        $fields['business'] = BL_DEALER_BUSINESSES[(string) ($fields['business'] ?? '')] ?? '';
        $fields['model'] = '';
        $ip = function_exists('achord_inquiry_client_ip') ? achord_inquiry_client_ip() : '';
        $fields['country'] = $ip !== '' && function_exists('achord_inquiry_ip_country') ? achord_inquiry_ip_country($ip) : '';
        if (($fields['language'] ?? '') === '') $fields['language'] = 'en';
        return $fields;
    }
    // Asked only on the dealer form: an ordinary enquiry never carries them.
    $fields['business'] = $fields['market'] = $fields['website'] = '';
    $family = (string) ($fields['product'] ?? '');
    $fields['product'] = BL_ENQUIRY_FAMILIES_ZH[$family] ?? (in_array($family, BL_ENQUIRY_FAMILIES_ZH, true) ? $family : '');
    $model = trim((string) ($fields['model'] ?? ''));
    $fields['model'] = '';
    if ($model !== '') foreach (bl_products() as $record) if (strcasecmp((string) $record['model'], $model) === 0) { $fields['model'] = (string) $record['model']; break; }
    $ip = function_exists('achord_inquiry_client_ip') ? achord_inquiry_client_ip() : '';
    $fields['country'] = $ip !== '' && function_exists('achord_inquiry_ip_country') ? achord_inquiry_ip_country($ip) : '';
    if (($fields['language'] ?? '') === '') $fields['language'] = 'en';
    return $fields;
}
add_filter('achord_inquiry_submission_fields', 'bl_wp_inquiry_submission_fields');

// A dealer application: its own headline, subject and DingTalk title, so the sales team tells it from an enquiry at a glance.
add_filter('achord_inquiry_lead_types', static fn(array $types): array => $types + ['dealer' => [
    'headline' => '新经销商申请',
    'intro' => '装载设备站收到一条经销商申请，内容如下，请及时跟进。',
    'subject' => '【装载设备站】经销商申请，请及时跟进：',
    'kind' => '经销商申请',
    'section' => '意向产品',
    'form_name' => '经销商申请表',
    'dingtalk_title' => '🤝 装载设备站经销商申请',
    'title' => 'Dealer application from %s - %s',
]]);

// DingTalk lists what a dealer would sell first (an enquiry's equipment type shows in its mail card instead).
add_filter('achord_inquiry_dingtalk_rows', static function (array $rows, array $fields): array {
    if (($fields['lead_type'] ?? '') !== 'dealer' || trim((string) ($fields['product'] ?? '')) === '') return $rows;
    return [['label' => '意向产品', 'value' => (string) $fields['product'], 'long' => false], ...$rows];
}, 10, 2);

// The mail's 产品信息: the model's photo, name and link when the sender wrote from a product page, else the equipment type.
add_filter('achord_inquiry_mail_products_html', static fn($html, array $fields): string => bl_wp_inquiry_product_card((string) ($fields['product'] ?? ''), (string) ($fields['model'] ?? '')), 10, 2);

/**
 * The inboxes the Contact Form 7 form sent to, as CF7 read its recipient: a comma-separated list of addresses, each bare or
 * with a display name ("Sales <sales@x>"), and the [_site_admin_email] tag. Empty when no form was made or none is usable.
 */
function bl_wp_inquiry_legacy_recipients(): array
{
    $form = (int) get_option('bl_inquiry_form');
    $mail = $form > 0 ? get_post_meta($form, '_mail', true) : '';
    $text = is_array($mail) ? (string) ($mail['recipient'] ?? '') : '';
    $text = str_replace('[_site_admin_email]', (string) get_option('admin_email'), $text);
    $found = [];
    foreach (explode(',', $text) as $part) {
        $part = trim($part);
        if (preg_match('/<([^<>]+)>\s*$/', $part, $match)) $part = trim($match[1]);
        if ($part !== '' && is_email($part) && !in_array($part, $found, true)) $found[] = $part;
    }
    return $found;
}

/** Whether the sales email of 网站内容 → 全站设置 was set there (rather than left at the theme's default). */
function bl_wp_inquiry_global_email_set(): bool
{
    $saved = get_option('bl_content_global', []);
    return is_array($saved) && (!empty($saved['_bl_email_explicit']) || (is_array($saved['company'] ?? null) && array_key_exists('email', $saved['company'])));
}

/**
 * Where notifications go unless 设置 → 询盘设置 names an address: the sales email of 全站设置 once it was set there, else the
 * inboxes the Contact Form 7 form sent to (the plugin takes one address; the others are added as the mail goes, below),
 * else the site's email.
 */
function bl_wp_inquiry_default_recipient(): string
{
    $legacy = bl_wp_inquiry_global_email_set() ? [] : bl_wp_inquiry_legacy_recipients();
    return $legacy[0] ?? (string) bl_company('email');
}
add_filter('achord_inquiry_recipient', 'bl_wp_inquiry_default_recipient');

// The rest of the CF7 form's inboxes ride along with the plugin's notification, only when it goes to the first of them.
add_action('achord_inquiry_before_mail', static function (): void {
    $GLOBALS['bl_inquiry_mail_pending'] = true;
});
function bl_wp_inquiry_mail_recipients(array $args): array
{
    if (empty($GLOBALS['bl_inquiry_mail_pending'])) return $args;
    $GLOBALS['bl_inquiry_mail_pending'] = false;
    $legacy = bl_wp_inquiry_global_email_set() ? [] : bl_wp_inquiry_legacy_recipients();
    if (count($legacy) > 1 && ($args['to'] ?? null) === $legacy[0]) $args['to'] = $legacy;
    return $args;
}
add_filter('wp_mail', 'bl_wp_inquiry_mail_recipients');
add_filter('achord_inquiry_page_language', static fn(): string => 'en');

/**
 * Until 设置 → 询盘设置 is saved: the IP is kept with each enquiry (Flamingo kept it too, and the privacy page says so), and
 * Turnstile keys already saved in Contact Form 7's integration are used.
 */
add_filter('achord_inquiry_default_options', static function (array $defaults): array {
    $defaults['store_ip'] = '1';
    $cf7 = get_option('wpcf7');
    $turnstile = is_array($cf7) && is_array($cf7['turnstile'] ?? null) ? $cf7['turnstile'] : [];
    foreach ($turnstile as $site => $secret) {
        if (is_string($site) && is_string($secret) && $site !== '' && $secret !== '') {
            $defaults['turnstile_site_key'] = $site;
            $defaults['turnstile_secret_key'] = $secret;
            $defaults['turnstile_enabled'] = '1';
            break;
        }
    }
    return $defaults;
});

/** Where notifications go right now (shown in 网站内容 → 全站设置). */
function bl_wp_inquiry_current_recipient(): string
{
    return bl_wp_inquiry_ready() && function_exists('achord_inquiry_recipient') ? achord_inquiry_recipient() : '';
}

/**
 * A fresh form key for a page the cache kept longer than the key lasts (a day): the form asks for one and sends again
 * once when the plugin turns the old one away.
 */
function bl_wp_inquiry_fresh_nonce(): void
{
    nocache_headers();
    wp_send_json_success(['nonce' => bl_wp_inquiry_ready() ? wp_create_nonce(achord_inquiry_name('submit_inquiry')) : '']);
}
add_action('wp_ajax_bl_inquiry_nonce', 'bl_wp_inquiry_fresh_nonce');
add_action('wp_ajax_nopriv_bl_inquiry_nonce', 'bl_wp_inquiry_fresh_nonce');

/**
 * The form for one page. $atts: product (loader, dumper, help or service: the choice made for the visitor), model (the
 * product page's model), message (a first line written for them) and source (which form it is, for the sales team).
 */
function bl_wp_inquiry_form(array $atts): string
{
    if (!bl_wp_inquiry_ready()) return '';
    $chosen = BL_ENQUIRY_FAMILIES[$atts['product'] ?? 'loader'] ?? BL_ENQUIRY_FAMILIES['loader'];
    $choices = '';
    foreach (BL_ENQUIRY_FAMILIES as $label) {
        $choices .= '<span class="bl-radio-item"><label><input type="radio" name="product" value="' . esc_attr($label) . '"' . ($label === $chosen ? ' checked' : '') . '><span class="bl-radio-label">' . esc_html($label) . '</span></label></span>';
    }
    $privacy = esc_url(wp_make_link_relative(home_url('/privacy/')));
    $field = static fn(string $label, string $input): string => '<label>' . $label . $input . '</label>';
    $hidden = achord_inquiry_hidden_fields(['source' => (string) ($atts['source'] ?? ''), 'language' => 'en']);
    $turnstile = achord_inquiry_turnstile_widget('bl-turnstile', true);
    return '<form class="bl-inquiry-form" method="post" action="' . esc_url(achord_inquiry_action_url()) . '" data-nonce-url="' . esc_url(admin_url('admin-ajax.php?action=bl_inquiry_nonce')) . '">'
        . '<fieldset><legend>01 / YOUR EQUIPMENT</legend><span class="bl-radio">' . $choices . '</span>'
        . '<input type="hidden" name="model" value="' . esc_attr((string) ($atts['model'] ?? '')) . '"></fieldset>'
        . '<fieldset><legend>02 / A LITTLE ABOUT YOU</legend><div class="bl-form-grid">'
        . $field('Your name <span>*</span>', '<input name="name" required maxlength="100" autocomplete="name" placeholder="Full name">')
        . $field('Business email <span>*</span>', '<input type="email" name="email" required maxlength="200" autocomplete="email" placeholder="you@company.com">')
        . $field('WhatsApp', '<input type="tel" name="phone" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="+ country code and number">')
        . $field('Company', '<input name="company" maxlength="150" autocomplete="organization" placeholder="Company name">')
        . '</div><label class="bl-message-label">Your project<textarea name="message" rows="3" maxlength="2000" placeholder="What work do you need to get done?">' . esc_textarea((string) ($atts['message'] ?? '')) . '</textarea></label>'
        . $hidden . '</fieldset>' . $turnstile
        . '<div class="bl-form-bottom"><p>Your details are used only to answer this enquiry. <br>See our <a href="' . $privacy . '">privacy information</a>.</p><input class="bl-inquiry-submit bl-button bl-button-blue" type="submit" value="Send enquiry"></div>'
        . '<p class="bl-form-status" role="status" aria-live="polite" hidden></p></form>';
}

/** The page an enquiry came from, for a mail: its title in bold (when known) above its address as a link. */
function bl_wp_inquiry_page_html(array $page, string $color, string $titleColor = '#0b1f33'): string
{
    $link = '<a href="' . esc_url($page['url']) . '" target="_blank" style="color:' . $color . ';text-decoration:none;word-break:break-all;">' . esc_html($page['url']) . '</a>';
    return ($page['title'] !== '' ? '<b style="color:' . $titleColor . ';">' . esc_html($page['title']) . '</b><br>' : '') . $link;
}

/** The product the sender chose: the model's photo, name and link when they wrote from a product page, else the family. */
function bl_wp_inquiry_product_card(string $family, string $model): string
{
    $product = null;
    if ($model !== '') foreach (bl_products() as $record) if (strcasecmp((string) $record['model'], $model) === 0) { $product = $record; break; }
    $pill = static fn(string $label, string $text): string => '<span style="display:inline-block;margin:0 6px 6px 0;padding:4px 10px;border-radius:4px;background:#f1f4f9;font-size:13px;color:#334155;white-space:nowrap;"><span style="color:#64748b;">' . $label . '</span> ' . esc_html($text) . '</span>';
    // The type in Chinese, as the designs and the crane site's card show it; a family the theme does not know stays as sent.
    $pills = ($model !== '' ? $pill('型号', $model) : '') . $pill('类型', $family !== '' ? (BL_ENQUIRY_FAMILIES_ZH[$family] ?? $family) : '—');
    $box = static fn(string $inner): string => '<table width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e3e8ef;border-radius:4px;border-collapse:separate;"><tr>' . $inner . '</tr></table>';
    if (!$product) return $box('<td style="padding:16px 18px 10px;">' . $pills . '</td>');
    $url = esc_url($product['url']);
    $image = bl_wp_inquiry_thumbnail($product);
    $photo = $image ? '<td width="96" style="width:96px;padding:16px 0 16px 16px;vertical-align:top;"><a href="' . $url . '" target="_blank"><img src="' . esc_url($image) . '" width="96" height="96" alt="' . esc_attr($product['name']) . '" style="display:block;width:96px;height:96px;border-radius:4px;border:1px solid #edf0f4;object-fit:cover;"></a></td>' : '';
    return $box($photo . '<td style="padding:16px 18px 10px;vertical-align:top;"><a href="' . $url . '" target="_blank" style="display:block;padding-bottom:10px;font-size:17px;line-height:1.45;font-weight:bold;color:#0b1f33;text-decoration:none;">' . esc_html($product['name']) . '</a>' . $pills . '</td>');
}

/**
 * The file in the uploads folder that a stored image address points to (the media library keeps it as a path from the site
 * root, "/wp-content/uploads/2026/10/x.webp", or as a full address), or '' for any other address.
 */
function bl_wp_inquiry_uploads_file(string $address): string
{
    $uploads = wp_upload_dir();
    $base = rtrim((string) wp_parse_url((string) $uploads['baseurl'], PHP_URL_PATH), '/');
    $host = wp_parse_url($address, PHP_URL_HOST);
    if ($base === '' || ($host && $host !== wp_parse_url((string) $uploads['baseurl'], PHP_URL_HOST))) return '';
    $path = rawurldecode((string) wp_parse_url($address, PHP_URL_PATH));
    if (!str_starts_with($path, $base . '/') || str_contains($path, '..')) return '';
    return trailingslashit((string) $uploads['basedir']) . substr($path, strlen($base) + 1);
}

/**
 * An image address a mail program can open: one kept as a path from the site root gets the site's scheme and host in front of
 * it (not the home URL, which on a site in a subfolder already ends with that folder, and the path names it too).
 */
function bl_wp_inquiry_absolute_url(string $address): string
{
    if (!str_starts_with($address, '/') || str_starts_with($address, '//')) return $address;
    $home = (array) wp_parse_url(home_url());
    if (empty($home['scheme']) || empty($home['host'])) return $address;
    return $home['scheme'] . '://' . $home['host'] . (isset($home['port']) ? ':' . $home['port'] : '') . $address;
}

/**
 * A square of the product's photo (twice its 104 px size, for sharp screens ), from its featured image, else the image the
 * product record names (the media library's, or the theme's own catalogue image), saved once as a JPEG in the uploads:
 * product photos are WebP, which some desktop mail programs do not show. Falls back to the image as it is, with its full
 * address: a path from the site root opens in the browser but is a broken picture in a mail.
 */
function bl_wp_inquiry_thumbnail(array $product): string
{
    $post = get_page_by_path((string) $product['slug'], OBJECT, 'bl_product');
    $attachment = $post ? (int) get_post_thumbnail_id($post) : 0;
    $file = $attachment ? (string) get_attached_file($attachment) : '';
    if (!$file && $post) {
        $record = get_post_meta($post->ID, '_bl_record', true);
        $stored = is_array($record) ? (string) ($record['image'] ?? '') : '';
        if (str_starts_with($stored, 'images/') && !str_contains($stored, '..')) $file = get_template_directory() . '/assets/' . $stored;
        elseif ($stored !== '') $file = bl_wp_inquiry_uploads_file($stored);
    }
    $fallback = bl_wp_inquiry_absolute_url((string) $product['image']);
    if (!$file || !is_file($file)) return $fallback;
    $uploads = wp_upload_dir();
    $name = 'bl-mail/' . substr(md5($file . filemtime($file)), 0, 12) . '-208.jpg';
    $path = trailingslashit($uploads['basedir']) . $name;
    if (!is_file($path)) {
        $editor = wp_get_image_editor($file);
        if (is_wp_error($editor) || is_wp_error($editor->resize(208, 208, true)) || is_wp_error($editor->save($path, 'image/jpeg'))) return $fallback;
    }
    return trailingslashit($uploads['baseurl']) . $name;
}

// Ad and analytics tracking parameters. They are left out of the page an enquiry came from (the attribution block lists the
// advert by itself), so the address says which page it was.
const BL_ENQUIRY_TRACKING = '/^(utm_[a-z_]+|gclid|gclsrc|gbraid|wbraid|dclid|gad_[a-z_]+|fbclid|msclkid|ttclid|twclid|li_fat_id|srsltid|_gl)$/i';

// WordPress's own public query variables (themes and plugins add theirs to $wp->public_query_vars); used when $wp is not there.
const BL_WP_QUERY_VARS = ['m', 'p', 'posts', 'w', 'cat', 'withcomments', 'withoutcomments', 's', 'search', 'exact', 'sentence', 'calendar', 'page', 'paged', 'more', 'tb', 'pb', 'author', 'order', 'orderby', 'year', 'monthnum', 'day', 'hour', 'minute', 'second', 'name', 'category_name', 'tag', 'feed', 'author_name', 'pagename', 'page_id', 'error', 'attachment', 'attachment_id', 'subpost', 'subpost_id', 'preview', 'robots', 'favicon', 'taxonomy', 'term', 'cpage', 'post_type', 'embed'];

/**
 * What an address's query asks WordPress for: 'post' when it names one by its ID (?p=9, ?page_id=9), 'listing' when it
 * asks for anything else WordPress reads (?s=, ?cat=), '' when it selects nothing (?ref=partner).
 */
function bl_wp_query_selects(string $query): string
{
    if ($query === '') return '';
    parse_str($query, $vars);
    foreach (['p', 'page_id', 'attachment_id'] as $key) {
        if (isset($vars[$key]) && is_string($vars[$key]) && ctype_digit($vars[$key]) && (int) $vars[$key] > 0) return 'post';
    }
    $public = isset($GLOBALS['wp']->public_query_vars) ? (array) $GLOBALS['wp']->public_query_vars : BL_WP_QUERY_VARS;
    return array_intersect_key($vars, array_flip($public)) ? 'listing' : '';
}

/**
 * The page an enquiry was sent from: its address without tracking parameters (a site-relative one gets the site's own
 * origin) and the page's title as the site titles it, empty for an address this site does not serve.
 */
function bl_wp_inquiry_page(string $url): array
{
    // A path from the site root already holds any folder the site lives in, so it takes only the scheme and host.
    $url = bl_wp_inquiry_absolute_url(trim($url));
    $hash = '';
    if (($at = strpos($url, '#')) !== false) [$url, $hash] = [substr($url, 0, $at), substr($url, $at)];
    if (($at = strpos($url, '?')) !== false) {
        $kept = array_filter(explode('&', substr($url, $at + 1)), static fn(string $pair): bool => $pair !== '' && !preg_match(BL_ENQUIRY_TRACKING, rawurldecode(explode('=', $pair, 2)[0])));
        $url = substr($url, 0, $at) . ($kept ? '?' . implode('&', $kept) : '');
    }
    $url .= $hash;
    $title = '';
    $host = static fn($address): string => preg_replace('/^www\./', '', strtolower((string) wp_parse_url((string) $address, PHP_URL_HOST)));
    if ($url !== '' && $host($url) === $host(home_url('/'))) {
        // An address that names a post by its ID (?p=9, ?page_id=9) is that post, whatever its path looks like. The home
        // page's address asking for a search or archive (?s=, ?cat=) is a WordPress listing, not the home page; one with a
        // query that selects nothing (?ref=partner) is still the home page.
        $selects = bl_wp_query_selects((string) wp_parse_url($url, PHP_URL_QUERY));
        $path = (string) (wp_parse_url($url, PHP_URL_PATH) ?: '/');
        $home = (string) (wp_parse_url(home_url('/'), PHP_URL_PATH) ?: '/');
        $listing = $selects === 'listing' && rtrim($path, '/') === rtrim($home, '/');
        $route = $selects === 'post' || $listing ? ['status' => 404] : bl_resolve_route($path);
        if ($route['status'] === 200) $title = bl_plain_title((string) $route['title']);
        elseif (!$listing && ($id = url_to_postid($url)) > 0) $title = bl_plain_title(get_the_title($id));
    }
    return ['url' => $url, 'title' => $title];
}

// The DingTalk plugin heads each message with the site, and names the page an enquiry came from the way this theme does.
add_filter('achord_dingtalk_site_name', static fn($name) => trim((string) $name) !== '' ? $name : '装载设备站');
add_filter('achord_dingtalk_page_title', static fn($title, $url) => trim((string) $title) !== '' ? $title : bl_wp_inquiry_page((string) $url)['title'], 10, 2);

/**
 * The thank-you page the form leads to once the enquiry is sent (/thank-you/; words in 网站内容 → 询盘成功页). WordPress
 * needs a page at that address; it is made once, and kept out of the sitemaps since the page is for people who just wrote.
 */
function bl_wp_thanks_page(): void
{
    if (get_option('bl_thanks_page') || !($token = bl_lock_acquire('thanks_page', 1))) return;
    try {
        $page = get_page_by_path('thank-you');
        $id = $page ? $page->ID : wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Thank you', 'post_name' => 'thank-you'], true);
        if (is_wp_error($id) || !$id) return;
        if (get_post_field('post_name', (int) $id) !== 'thank-you') { wp_delete_post((int) $id, true); $id = -1; }
        update_option('bl_thanks_page', (int) $id);
    } finally {
        bl_lock_release('thanks_page', $token);
    }
}
add_action('init', 'bl_wp_thanks_page', 20);

/**
 * The dealer page (/dealers/; words in 网站内容 → 经销商). WordPress needs a page at that address so the theme can answer it;
 * it is made once and, unlike the thank-you page, stays in the sitemaps. A draft or private page an editor already keeps at
 * that address is never published from here, and a media file holding the slug "dealers" is not touched either: it is left
 * as it is, the editor is told in the dashboard, and the check runs again an hour later (or as soon as the page is published
 * or deleted, or the file renamed or deleted, and the notice is gone).
 */
function bl_wp_dealers_page(): void
{
    if (get_option('bl_dealers_page') || get_transient('bl_dealers_page_conflict') || !($token = bl_lock_acquire('dealers_page', 1))) return;
    try {
        // Pages only: given a single type, get_page_by_path() also returns a media file whose slug is "dealers".
        $page = get_page_by_path('dealers', OBJECT, ['page']);
        if ($page && $page->post_status !== 'publish') { set_transient('bl_dealers_page_conflict', (int) $page->ID, HOUR_IN_SECONDS); return; }
        if (!$page) {
            // A media file named "dealers" reserves the slug against new pages wherever it is attached, so look it up by name
            // alone rather than by path; WordPress would otherwise give the new page /dealers-2/.
            $media = get_posts(['name' => 'dealers', 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'suppress_filters' => true]);
            if ($media) { set_transient('bl_dealers_page_conflict', (int) $media[0], HOUR_IN_SECONDS); return; }
        }
        $id = $page ? $page->ID : wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Become a dealer', 'post_name' => 'dealers'], true);
        if (is_wp_error($id) || !$id) return;
        if (get_post_field('post_name', (int) $id) !== 'dealers') {
            // Something else still holds the slug: take the stray page away and try again later instead of giving up for good.
            wp_delete_post((int) $id, true);
            set_transient('bl_dealers_page_conflict', -1, HOUR_IN_SECONDS);
            return;
        }
        update_option('bl_dealers_page', (int) $id);
    } finally {
        bl_lock_release('dealers_page', $token);
    }
}
add_action('init', 'bl_wp_dealers_page', 20);
add_action('admin_notices', static function (): void {
    if (!current_user_can('edit_pages') || !($id = (int) get_transient('bl_dealers_page_conflict'))) return;
    if ($id < 0) { echo '<div class="notice notice-warning"><p>经销商页的网址 /dealers/ 已被网站里的其他内容占用，暂时建不了，网站上的「Become a dealer」链接打不开。请在页面、文章和媒体库里找到别名为 dealers 的内容，改掉别名或删除；系统每小时会再试一次。</p></div>'; return; }
    $media = get_post_type($id) === 'attachment';
    // Resolved: the page was published or deleted, or the media file was deleted or given another slug. The next load retries.
    if (!get_post_status($id) || get_post_field('post_name', $id) !== 'dealers' || (!$media && get_post_status($id) === 'publish')) { delete_transient('bl_dealers_page_conflict'); return; }
    $what = $media ? '媒体库里的一个文件（别名同为 dealers）' : '一个未发布的页面（草稿或私密）';
    $fix = $media ? '把这个文件的别名改掉或删除这个文件' : '发布或删除这个页面';
    echo '<div class="notice notice-warning"><p>经销商页的网址 /dealers/ 被' . $what . '占用，网站上的「Become a dealer」链接现在打不开。请<a href="' . esc_url((string) get_edit_post_link($id)) . '">' . $fix . '</a>，处理后系统会自动建好经销商页。</p></div>';
});

add_filter('wp_sitemaps_posts_query_args', static function (array $args, string $postType): array {
    if ($postType === 'page' && ($id = (int) get_option('bl_thanks_page')) > 0) $args['post__not_in'] = array_merge($args['post__not_in'] ?? [], [$id]);
    return $args;
}, 10, 2);
add_filter('jetpack_sitemap_skip_post', static fn($skip, $post) => $skip || (is_object($post) && (int) $post->ID === (int) get_option('bl_thanks_page')), 10, 2);
