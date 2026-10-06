<?php
/**
 * The notification email's designs, chosen under 网站内容 → 询盘邮件 (or through REST: the bl_inquiry_mail_template
 * setting). "classic" is the card kept in the form's Mail tab, filled by CF7's tags; the others are drawn here from the
 * enquiry's data and take its place as the mail is written.
 */

const BL_INQUIRY_MAIL_TEMPLATES = [
    'classic' => ['name' => '经典卡片', 'note' => '蓝色顶条、浅灰来源框、产品卡片和信息行，最早上线的一版。'],
    'a' => ['name' => 'A 出厂工单', 'note' => '白色页眉加蓝线，按来源、产品、客户、来源追踪分区编号，像一张设备工单。'],
    'b' => ['name' => 'B 回复优先', 'note' => '白底，客户姓名大字放最上面，紧跟回复按钮，再看留言和产品。'],
    'c' => ['name' => 'C 设备铭牌', 'note' => '深色铭牌区突出询问的设备，下方白底放客户、留言和回复按钮。'],
    'd' => ['name' => 'D 来信', 'note' => '暖色纸张底，留言像一封信放在正中，其余信息收在「询盘详情」里。'],
];
const BL_INQUIRY_MAIL_TEMPLATE_DEFAULT = 'c';

/** The design the notification uses now. */
function bl_wp_inquiry_mail_template(): string
{
    $key = (string) get_option('bl_inquiry_mail_template', BL_INQUIRY_MAIL_TEMPLATE_DEFAULT);
    return isset(BL_INQUIRY_MAIL_TEMPLATES[$key]) ? $key : BL_INQUIRY_MAIL_TEMPLATE_DEFAULT;
}

add_action('init', static function (): void {
    register_setting('bl_inquiry_mail', 'bl_inquiry_mail_template', [
        'type' => 'string',
        'description' => '询盘通知邮件的模板：' . implode('、', array_map(static fn(string $k, array $t): string => $k . ' = ' . $t['name'], array_keys(BL_INQUIRY_MAIL_TEMPLATES), BL_INQUIRY_MAIL_TEMPLATES)),
        'default' => BL_INQUIRY_MAIL_TEMPLATE_DEFAULT,
        'sanitize_callback' => static fn($value): string => isset(BL_INQUIRY_MAIL_TEMPLATES[(string) $value]) ? (string) $value : bl_wp_inquiry_mail_template(),
        'show_in_rest' => ['schema' => ['enum' => array_keys(BL_INQUIRY_MAIL_TEMPLATES)]],
    ]);
});

/** Whether the form's Mail body is still the theme's classic card exactly as shipped, not edited in the Mail tab. */
function bl_wp_inquiry_stock_card(?WPCF7_ContactForm $form = null): bool
{
    $form ??= class_exists('WPCF7_ContactForm') && bl_wp_inquiry_form_id() ? WPCF7_ContactForm::get_instance(bl_wp_inquiry_form_id()) : null;
    return $form && trim(str_replace("\r\n", "\n", (string) (((array) $form->prop('mail'))['body'] ?? ''))) === trim(bl_wp_inquiry_mail()['body']);
}

// While a drawn design is chosen, the theme's card is sent as that design. The swap happens as the mail is written, so
// the form's saved Mail tab keeps the classic card. Only the card as shipped is swapped: a body an editor changed in any
// way (an added field, a note), and every other form, are sent as written.
add_filter('wpcf7_mail_html_body', static function ($body) {
    $key = bl_wp_inquiry_mail_template();
    if ($key === 'classic' || !bl_wp_inquiry_card_mail() || !bl_wp_inquiry_stock_card(WPCF7_ContactForm::get_current()) || !($submission = WPCF7_Submission::get_instance())) return $body;
    return BL_ENQUIRY_CARD_MARKER . "\n" . bl_wp_inquiry_render($key, bl_wp_inquiry_data([
        'name' => $submission->get_posted_string('name'),
        'email' => $submission->get_posted_string('email'),
        'company' => $submission->get_posted_string('company'),
        'message' => $submission->get_posted_string('message'),
        'family' => $submission->get_posted_string('product'),
        'model' => $submission->get_posted_string('model'),
        'url' => (string) $submission->get_meta('url'),
        'ip' => (string) $submission->get_meta('remote_ip'),
        'time' => (int) ($submission->get_meta('timestamp') ?: time()),
    ]));
}, 20);

/** Everything a design shows, worked out once: raw text (each design escapes it) plus the product, if the sender named one. */
function bl_wp_inquiry_data(array $raw): array
{
    $model = trim((string) ($raw['model'] ?? ''));
    $product = null;
    if ($model !== '') foreach (bl_products() as $record) if (strcasecmp((string) $record['model'], $model) === 0) { $product = $record; break; }
    $family = trim((string) ($raw['family'] ?? ''));
    $families = ['Skid steer loader' => '滑移装载机', 'Mini dumper' => '迷你翻斗车', 'Help me choose' => '帮我选型', 'Parts & service' => '配件与服务'];
    $categories = ['skid-steer-loaders' => '滑移装载机', 'mini-dumpers' => '迷你翻斗车'];
    // What the sender picked comes first (a parts & service request keeps its model); the product's category fills in
    // only when no family came through.
    $type = $family !== '' ? ($families[$family] ?? $family) : ($product ? ($categories[$product['category'] ?? ''] ?? '') : '');
    $ip = trim((string) ($raw['ip'] ?? ''));
    $email = trim((string) ($raw['email'] ?? ''));
    $productName = $product ? (string) $product['name'] : '';
    return [
        'name' => trim((string) ($raw['name'] ?? '')),
        'email' => $email,
        'company' => trim((string) ($raw['company'] ?? '')),
        'message' => trim(str_replace("\r\n", "\n", (string) ($raw['message'] ?? ''))),
        'model' => $model,
        'type' => $type !== '' ? $type : '未选择',
        'product' => $product ? ['name' => $productName, 'url' => (string) $product['url'], 'image' => $raw['image'] ?? bl_wp_inquiry_thumbnail($product)] + bl_wp_inquiry_title($productName, $model) : null,
        'kind' => $model !== '' ? '产品询盘' : '询盘表单',
        'url' => (string) ($raw['url'] ?? ''),
        'ip' => $ip,
        'country' => $raw['country'] ?? ($ip === '' ? '' : (bl_wp_ip_country($ip) ?: '未能识别')),
        'time' => wp_date('Y-m-d H:i', (int) ($raw['time'] ?? time())),
        'logo' => bl_asset('images/logo.png'),
        'site' => (string) wp_parse_url(home_url(), PHP_URL_HOST),
        'reply' => 'mailto:' . $email . '?subject=' . rawurlencode($productName !== '' ? 'Re: Your inquiry about ' . $productName : 'Re: Your inquiry to BOB-LIFT'),
    ];
}

/**
 * How a design heads the product: a name too long for one line on a phone is headed by its model instead, with the full
 * name under it in small type ("full"); a short name is the heading itself and "full" stays empty.
 */
function bl_wp_inquiry_title(string $name, string $model): array
{
    $long = $model !== '' && mb_strlen($name) > 24;
    return ['title' => $long ? $model : $name, 'full' => $long ? $name : ''];
}

/** A design drawn with the given data. Each design is a file in inc/mail-templates/ returning a function of the data. */
function bl_wp_inquiry_render(string $key, array $data): string
{
    static $designs = [];
    if (!isset(BL_INQUIRY_MAIL_TEMPLATES[$key]) || $key === 'classic') return '';
    $designs[$key] ??= require __DIR__ . '/mail-templates/' . $key . '.php';
    return ($designs[$key])($data);
}

/** The sender's message for HTML: escaped, its line breaks kept. */
function bl_wp_inquiry_text(string $text): string
{
    return str_replace(["\r", "\n"], '', nl2br(esc_html($text), false));
}

/** Made-up enquiries for the previews: one from a product page, one from the contact page with company and message empty. */
function bl_wp_inquiry_sample(string $kind): array
{
    $product = null;
    foreach (bl_products() as $record) if (($record['model'] ?? '') !== '') { $product = $record; if (strcasecmp((string) $record['model'], 'BL-T1000-H') === 0) break; }
    $fromProduct = $kind !== 'contact' && $product;
    return [
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'company' => $fromProduct ? 'Kowalski Landscaping Sp. z o.o.' : '',
        'message' => $fromProduct ? "Hello, we are interested in the {$product['model']} for garden landscaping work.\nCould you send the price for 2 units delivered to Gdansk?\nThank you." : '',
        'family' => $fromProduct ? (($product['category'] ?? '') === 'mini-dumpers' ? 'Mini dumper' : 'Skid steer loader') : 'Help me choose',
        'model' => $fromProduct ? (string) $product['model'] : '',
        'url' => $fromProduct ? (string) $product['url'] : home_url('/contact/'),
        'ip' => '216.232.48.35',
        'country' => '加拿大',
        'time' => time(),
    ];
}

/** The classic card as CF7 would fill it, for the preview only (sent mail goes through CF7 itself). */
function bl_wp_inquiry_classic_preview(array $raw): string
{
    $d = bl_wp_inquiry_data($raw);
    $link = static fn(string $url): string => '<a href="' . esc_url($url) . '" target="_blank" style="color:#215bdb;text-decoration:none;">' . esc_html($url) . '</a>';
    $tags = [
        '[bl_logo]' => '<img src="' . esc_url($d['logo']) . '" width="112" height="45" alt="BOB-LIFT" style="display:block;width:112px;height:45px;border:0;">',
        '[bl_time]' => esc_html($d['time']),
        '[bl_enquiry_kind]' => $d['kind'],
        '[bl_page_url]' => $link($d['url']),
        '[bl_product_card]' => bl_wp_inquiry_product_card((string) $raw['family'], $d['model']),
        '[bl_ip_country]' => esc_html($d['country']),
        '[bl_site]' => esc_html($d['site']),
        '[_remote_ip]' => esc_html($d['ip']),
        '[name]' => esc_html($d['name']),
        '[email]' => esc_html($d['email']),
        '[company]' => esc_html($d['company']),
        '[message]' => bl_wp_inquiry_text($d['message']),
    ];
    // CF7's "exclude blank": a line whose field is empty goes.
    $lines = array_filter(explode("\n", (string) bl_wp_inquiry_mail()['body']), static fn(string $line): bool => !(($d['company'] === '' && str_contains($line, '[company]')) || ($d['message'] === '' && str_contains($line, '[message]'))));
    return strtr(implode("\n", $lines), $tags);
}

add_action('admin_post_bl_inquiry_mail_preview', static function (): void {
    if (!current_user_can('edit_pages')) wp_die('', 403);
    $key = sanitize_key((string) ($_GET['template'] ?? ''));
    if (!isset(BL_INQUIRY_MAIL_TEMPLATES[$key])) $key = bl_wp_inquiry_mail_template();
    $raw = bl_wp_inquiry_sample(($_GET['sample'] ?? '') === 'contact' ? 'contact' : 'product');
    $card = $key === 'classic' ? bl_wp_inquiry_classic_preview($raw) : bl_wp_inquiry_render($key, bl_wp_inquiry_data($raw));
    nocache_headers();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=700"><title>' . esc_html(BL_INQUIRY_MAIL_TEMPLATES[$key]['name']) . ' · 预览</title></head><body style="margin:0;">' . $card . '</body></html>';
    exit;
});

// 询盘邮件 sits under 网站内容, after 资料下载.
add_action('admin_menu', static function (): void {
    add_submenu_page('bl-content-home', '询盘邮件', '询盘邮件', 'edit_pages', 'bl-inquiry-mail', 'bl_inquiry_mail_screen');
}, 21);
add_filter('option_page_capability_bl_inquiry_mail', static fn(): string => 'edit_pages');

function bl_inquiry_mail_screen(): void
{
    $current = bl_wp_inquiry_mail_template();
    $preview = static fn(string $key, string $sample): string => esc_url(admin_url('admin-post.php?action=bl_inquiry_mail_preview&template=' . $key . '&sample=' . $sample));
    echo '<div class="wrap bl-inquiry-mail"><div class="bl-page-head"><div><h1>询盘邮件</h1><p>网站询盘表单发到 ' . esc_html(bl_company('email')) . ' 的通知邮件用哪种样式。保存后，下一封询盘起生效；内容和字段都一样，只是排版不同。</p></div></div><hr class="wp-header-end">';
    settings_errors();
    echo '<form method="post" action="options.php">';
    settings_fields('bl_inquiry_mail');
    echo '<details class="bl-panel" data-bl-card open><summary class="bl-panel-head">' . bl_admin_card_head(1, '邮件模板', '当前：' . BL_INQUIRY_MAIL_TEMPLATES[$current]['name'], 0) . '</summary><div class="bl-panel-body"><div class="bl-mail-templates">';
    foreach (BL_INQUIRY_MAIL_TEMPLATES as $key => $template) {
        echo '<label class="bl-mail-template' . ($key === $current ? ' is-current' : '') . '"><input type="radio" name="bl_inquiry_mail_template" value="' . esc_attr($key) . '"' . checked($key, $current, false) . '>'
            . '<span class="bl-mail-template-text"><span><strong>' . esc_html($template['name']) . '</strong>' . ($key === $current ? ' <span class="bl-chip">使用中</span>' : '') . '</span><span>' . esc_html($template['note']) . '</span>'
            . '<span class="bl-mail-template-links">预览：<a href="' . $preview($key, 'product') . '" target="_blank" rel="noopener">产品页询盘</a> · <a href="' . $preview($key, 'contact') . '" target="_blank" rel="noopener">联系页询盘</a></span></span></label>';
    }
    echo '</div><p class="bl-field-help">预览用的是虚构的询盘。表单设置里的邮件正文若被手动改过，会一直按手改的内容发送，这里的选择不再起作用。</p>';
    if (class_exists('WPCF7_ContactForm') && bl_wp_inquiry_form_id() && !bl_wp_inquiry_stock_card()) echo '<p class="bl-field-help"><strong>注意：</strong>询盘表单的邮件正文已被手动改过，目前按手改的内容发送，上面的模板暂不生效。</p>';
    echo '</div></details>';
    echo '<div class="bl-savebar">';
    submit_button('保存邮件模板', 'primary', 'submit', false);
    echo '</div></form></div>';
}
